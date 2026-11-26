<?php

namespace App\Services\Presupuestos;

use App\Models\Presupuesto;
use App\Models\PresupuestoVersion;
use App\Support\PresentacionPresupuesto;
use App\Support\PropuestaApupi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ConsultaPresupuesto
{
    public function listado(string $buscar, ?string $tipo): Builder
    {
        return Presupuesto::query()
            ->withCount('versiones')
            ->with('cliente:id,nombre_comercial')
            ->when($tipo !== null, fn ($query) => $query->where('presupuestos.tipo', $tipo))
            ->when($buscar !== '', function ($query) use ($buscar) {
                $literal = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar) . '%';
                $query->where(function ($query) use ($literal) {
                    $query->whereRaw("nombre_cliente LIKE ? ESCAPE '!'", [$literal])
                        ->orWhereRaw("codigo_proyecto LIKE ? ESCAPE '!'", [$literal])
                        ->orWhereHas('cliente', fn ($cliente) => $cliente->whereRaw(
                            "nombre_comercial LIKE ? ESCAPE '!'", [$literal]
                        ));
                });
            })
            ->latest('presupuestos.created_at')
            ->orderByDesc('presupuestos.id');
    }

    public function cargarImportes(EloquentCollection $presupuestos): void
    {
        $presupuestos->where('versiones_count', 1)->load('ultimaVersion');
        $presupuestos->filter(fn (Presupuesto $p) => $p->estaAprobado())->load('versionAprobada');
        $presupuestos->reject(fn (Presupuesto $p) => $p->esImportado()
            || $p->estaAprobado() || $this->versionComercialUnica($p))
            ->load(['detalles', 'pilas', 'pilasVol', 'resumenes']);
    }

    public function versionComercialUnica(Presupuesto $presupuesto): ?PresupuestoVersion
    {
        if ($presupuesto->versiones_count !== 1) {
            return null;
        }

        $version = $presupuesto->ultimaVersion;

        return $version?->tieneVenta() ? $version : null;
    }

    public function versionPreferida(Presupuesto $presupuesto): ?PresupuestoVersion
    {
        if ($presupuesto->estaAprobado()) {
            return $presupuesto->versionAprobada;
        }

        if ($presupuesto->versiones_count === null) {
            $presupuesto->loadCount('versiones');
        }

        return $this->versionComercialUnica($presupuesto);
    }

    public function importes(Presupuesto $presupuesto, ?PresupuestoVersion $version): array
    {
        if ($version) {
            $datos = $version->datos;

            return [
                'directo' => $datos['previo']['escenarios']['realista']['directo']
                    ?? $datos['apupi']['resumen']['realista']['total_directo']
                    ?? $datos['presupuesto']['total_costo_directo'] ?? null,
                'total' => $datos['presentacion']['total'] ?? $version->total_venta,
            ];
        }

        if ($presupuesto->esImportado()) {
            return ['directo' => $presupuesto->total_costo_directo, 'total' => $presupuesto->total_presupuesto];
        }

        $borrador = $this->borradorComercial($presupuesto);

        return ['directo' => $borrador['directo'], 'total' => $borrador['presentacion']['total']];
    }

    public function borradorComercial(Presupuesto $presupuesto): array
    {
        $vol = $presupuesto->calcularVolumetria();
        $duracion = $this->duracionApupiDesdeVol($presupuesto, $vol);
        [$costos, $resumen] = $this->calcularApupi($presupuesto, $vol, $duracion);
        $directo = $resumen['realista']['total_directo'];

        return [
            'directo' => $directo === null ? null : round($directo + ($resumen['propuesta']['filas']['venta-218']['importe'] ?? 0), 2),
            'presentacion' => PresentacionPresupuesto::preparar($presupuesto, $costos, $resumen, $duracion),
        ];
    }

    public function presentacionVersion(PresupuestoVersion $version): array
    {
        return $version->datos['presentacion'] ?? [
            'filas' => collect($version->datos['propuesta']['filas'] ?? [])->where('cantidad', '>', 0)
                ->map(fn (array $fila) => ['partida' => ''] + $fila)->all(),
            'total' => $version->total_venta,
        ];
    }

    public function duracionApupiDesdeVol(Presupuesto $presupuesto, array $vol): array
    {
        return $presupuesto->duracionApupi($vol['filas']->isEmpty() ? null : $vol['totales']['jornales_exactos']);
    }

    public function filasPropuesta(Presupuesto $presupuesto, array $vol, array $duracion): Collection
    {
        return PropuestaApupi::filas($presupuesto, $vol, $presupuesto->filasApupi($duracion), $duracion['realista']['semanas']);
    }

    public function calcularApupi(Presupuesto $presupuesto, array $vol, array $duracion): array
    {
        $filas = $this->filasPropuesta($presupuesto, $vol, $duracion);
        $costos = $presupuesto->filasApupi($duracion, PropuestaApupi::totalVenta($filas));
        $resumen = $presupuesto->resumenApupi($costos, $vol['totales']['perforacion']);
        $resumen['propuesta'] = PropuestaApupi::calcular($filas, $vol, $resumen, $costos, $duracion);

        return [$costos, $resumen];
    }
}
