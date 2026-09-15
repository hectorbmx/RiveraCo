<?php

namespace App\Services\Vehiculos;

use App\Models\Seguro;
use App\Models\Vehiculo;
use App\Models\VehiculoDocumento;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

class VehiculoDocumentosConsultaService
{
    public function map(Vehiculo $vehiculo, int $diasAdvertencia = 30): array
    {
        $vehiculo->loadMissing(['seguros', 'documentoTarjetaCirculacionVigente']);

        $hoy = now()->startOfDay();
        $limiteAdvertencia = $hoy->copy()->addDays($diasAdvertencia);

        $seguro = $this->resolverSeguroConsulta($vehiculo, $hoy);
        $tarjeta = $vehiculo->documentoTarjetaCirculacionVigente;

        $seguroMapeado = $seguro ? $this->mapSeguro($seguro, $hoy, $limiteAdvertencia) : null;
        $tarjetaMapeada = $tarjeta ? $this->mapTarjeta($tarjeta, $hoy, $limiteAdvertencia) : null;

        return [
            'estado' => $this->estadoGeneral($seguroMapeado, $tarjetaMapeada),
            'seguro' => $seguroMapeado,
            'tarjeta_circulacion' => $tarjetaMapeada,
        ];
    }

    private function resolverSeguroConsulta(Vehiculo $vehiculo, CarbonInterface $hoy): ?Seguro
    {
        $seguros = $vehiculo->seguros
            ->filter(fn (Seguro $seguro) => (string) $seguro->estatus !== 'cancelada')
            ->values();

        $vigente = $seguros->first(function (Seguro $seguro) use ($hoy) {
            return $seguro->vigencia_desde
                && $seguro->vigencia_hasta
                && $seguro->vigencia_desde->lte($hoy)
                && $seguro->vigencia_hasta->gte($hoy);
        });

        return $vigente ?: $seguros->first();
    }

    private function mapSeguro(Seguro $seguro, CarbonInterface $hoy, CarbonInterface $limiteAdvertencia): array
    {
        $vigenciaHasta = $seguro->vigencia_hasta;
        $documentoPath = $seguro->documento_path;

        return [
            'id' => $seguro->id,
            'estatus' => $this->estatusSeguro($seguro, $hoy),
            'estado_visual' => $this->estadoVisualFecha($vigenciaHasta, $hoy, $limiteAdvertencia),
            'aseguradora' => $seguro->aseguradora,
            'poliza_numero' => $seguro->poliza_numero,
            'vigencia_desde' => optional($seguro->vigencia_desde)->format('Y-m-d'),
            'vigencia_hasta' => optional($vigenciaHasta)->format('Y-m-d'),
            'dias_restantes' => $this->diasRestantes($vigenciaHasta, $hoy),
            'documento_url' => $documentoPath ? Storage::disk('public')->url($documentoPath) : null,
            'documento_disponible' => (bool) $documentoPath,
        ];
    }

    private function mapTarjeta(VehiculoDocumento $tarjeta, CarbonInterface $hoy, CarbonInterface $limiteAdvertencia): array
    {
        $fechaVencimiento = $tarjeta->fecha_vencimiento;
        $documentoPath = $tarjeta->archivo_path;

        return [
            'id' => $tarjeta->id,
            'estatus' => $this->estatusDocumento($fechaVencimiento, $hoy),
            'estado_visual' => $this->estadoVisualFecha($fechaVencimiento, $hoy, $limiteAdvertencia),
            'fecha_vencimiento' => optional($fechaVencimiento)->format('Y-m-d'),
            'dias_restantes' => $this->diasRestantes($fechaVencimiento, $hoy),
            'documento_url' => $documentoPath ? Storage::disk('public')->url($documentoPath) : null,
            'documento_disponible' => (bool) $documentoPath,
        ];
    }

    private function estatusSeguro(Seguro $seguro, CarbonInterface $hoy): string
    {
        if ($seguro->vigencia_desde && $seguro->vigencia_desde->gt($hoy)) {
            return 'futura';
        }

        if ($seguro->vigencia_hasta && $seguro->vigencia_hasta->lt($hoy)) {
            return 'vencida';
        }

        return 'vigente';
    }

    private function estatusDocumento(?CarbonInterface $fechaVencimiento, CarbonInterface $hoy): string
    {
        if ($fechaVencimiento && $fechaVencimiento->lt($hoy)) {
            return 'vencida';
        }

        return 'vigente';
    }

    private function estadoVisualFecha(?CarbonInterface $fecha, CarbonInterface $hoy, CarbonInterface $limiteAdvertencia): string
    {
        if (! $fecha) {
            return 'ok';
        }

        if ($fecha->lt($hoy)) {
            return 'danger';
        }

        if ($fecha->lte($limiteAdvertencia)) {
            return 'warning';
        }

        return 'ok';
    }

    private function estadoGeneral(?array $seguro, ?array $tarjeta): string
    {
        $estados = collect([
            $seguro['estado_visual'] ?? 'danger',
            $tarjeta['estado_visual'] ?? 'danger',
        ]);

        if ($estados->contains('danger')) {
            return 'danger';
        }

        if ($estados->contains('warning')) {
            return 'warning';
        }

        return 'ok';
    }

    private function diasRestantes(?CarbonInterface $fecha, CarbonInterface $hoy): ?int
    {
        if (! $fecha) {
            return null;
        }

        return (int) $hoy->diffInDays($fecha, false);
    }
}