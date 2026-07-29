<?php

namespace App\Services\Calendario;

use App\Models\Empleado;
use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Models\Obra;
use App\Models\OrdenCompra;
use App\Models\Seguro;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CalendarioOperacionalService
{
    public const CATEGORIA_OBRAS = 'obras';
    public const CATEGORIA_VEHICULOS = 'vehiculos';
    public const CATEGORIA_MAQUINARIA = 'maquinaria';
    public const CATEGORIA_SEGUROS = 'seguros';
    public const CATEGORIA_ORDENES_COMPRA = 'ordenes_compra';
    public const CATEGORIA_RH = 'rh';

    public const CATEGORIAS_DEFAULT = [
        self::CATEGORIA_OBRAS,
        self::CATEGORIA_VEHICULOS,
        self::CATEGORIA_MAQUINARIA,
        self::CATEGORIA_SEGUROS,
        self::CATEGORIA_ORDENES_COMPRA,
        self::CATEGORIA_RH,
    ];

    private const COLORES = [
        self::CATEGORIA_OBRAS => '#2563eb',
        self::CATEGORIA_VEHICULOS => '#059669',
        self::CATEGORIA_MAQUINARIA => '#7c3aed',
        self::CATEGORIA_SEGUROS => '#dc2626',
        self::CATEGORIA_ORDENES_COMPRA => '#d97706',
        self::CATEGORIA_RH => '#0891b2',
    ];

    public function eventos(null|string|CarbonInterface $start = null, null|string|CarbonInterface $end = null, array $categorias = [], array $filtros = []): array
    {
        [$inicio, $fin] = $this->normalizarRango($start, $end);
        $categorias = $this->normalizarCategorias($categorias);

        $eventos = collect();

        if (in_array(self::CATEGORIA_OBRAS, $categorias, true)) {
            $eventos = $eventos->merge($this->eventosObras($inicio, $fin, $filtros));
        }

        if (in_array(self::CATEGORIA_VEHICULOS, $categorias, true)) {
            $eventos = $eventos->merge($this->eventosMantenimientosVehiculos($inicio, $fin, $filtros));
        }

        if (in_array(self::CATEGORIA_MAQUINARIA, $categorias, true)) {
            $eventos = $eventos->merge($this->eventosMantenimientosMaquinaria($inicio, $fin, $filtros));
        }

        if (in_array(self::CATEGORIA_SEGUROS, $categorias, true)) {
            $eventos = $eventos->merge($this->eventosSeguros($inicio, $fin, $filtros));
        }

        if (in_array(self::CATEGORIA_ORDENES_COMPRA, $categorias, true)) {
            $eventos = $eventos->merge($this->eventosOrdenesCompra($inicio, $fin, $filtros));
        }

        if (in_array(self::CATEGORIA_RH, $categorias, true)) {
            $eventos = $eventos->merge($this->eventosRh($inicio, $fin, $filtros));
        }

        return $eventos
            ->sortBy(['starts_at', 'title'])
            ->values()
            ->all();
    }

    public function categoriasDisponibles(): array
    {
        return [
            self::CATEGORIA_OBRAS => 'Obras',
            self::CATEGORIA_VEHICULOS => 'Vehiculos',
            self::CATEGORIA_MAQUINARIA => 'Maquinaria',
            self::CATEGORIA_SEGUROS => 'Seguros',
            self::CATEGORIA_ORDENES_COMPRA => 'Ordenes de compra',
            self::CATEGORIA_RH => 'RH',
        ];
    }

    private function eventosObras(CarbonInterface $inicio, CarbonInterface $fin, array $filtros): Collection
    {
        $obras = Obra::query()
            ->with(['cliente', 'responsable'])
            ->when($filtros['obra_id'] ?? null, fn ($query, $obraId) => $query->whereKey($obraId))
            ->when($filtros['cliente_id'] ?? null, fn ($query, $clienteId) => $query->where('cliente_id', $clienteId))
            ->when($filtros['responsable_id'] ?? null, fn ($query, $responsableId) => $query->where('responsable_id', $responsableId))
            ->where(function ($query) use ($inicio, $fin) {
                foreach (['fecha_inicio_programada', 'fecha_inicio_real', 'fecha_fin_programada', 'fecha_fin_real'] as $columna) {
                    $query->orWhereBetween($columna, [$inicio->toDateString(), $fin->toDateString()]);
                }
            })
            ->get();

        $tipos = [
            'fecha_inicio_programada' => ['inicio_programado', 'Inicio programado'],
            'fecha_inicio_real' => ['inicio_real', 'Inicio real'],
            'fecha_fin_programada' => ['fin_programado', 'Fin programado'],
            'fecha_fin_real' => ['fin_real', 'Fin real'],
        ];

        return $obras->flatMap(function (Obra $obra) use ($inicio, $fin, $tipos) {
            return collect($tipos)->map(function (array $config, string $campo) use ($obra, $inicio, $fin) {
                $fecha = $obra->{$campo};

                if (!$fecha || !$this->fechaEnRango($fecha, $inicio, $fin)) {
                    return null;
                }

                [$tipo, $label] = $config;
                $tituloObra = $obra->nombre ?: ('Obra #' . $obra->id);

                return $this->evento(
                    id: "obras:{$obra->id}:{$campo}",
                    source: 'obras',
                    sourceId: $obra->id,
                    category: self::CATEGORIA_OBRAS,
                    type: $tipo,
                    title: "{$label}: {$tituloObra}",
                    startsAt: $fecha,
                    status: (string) ($obra->estatus_nuevo ?? ''),
                    url: route('obras.edit', $obra),
                    meta: [
                        'cliente' => $obra->cliente->nombre ?? null,
                        'responsable' => $obra->responsable->nombre_completo ?? null,
                        'clave_obra' => $obra->clave_obra ?? null,
                    ]
                );
            })->filter();
        });
    }

    private function eventosMantenimientosVehiculos(CarbonInterface $inicio, CarbonInterface $fin, array $filtros): Collection
    {
        return $this->eventosMantenimientos(
            $inicio,
            $fin,
            $filtros,
            self::CATEGORIA_VEHICULOS,
            'vehiculo_id',
            ['vehiculo', 'mecanico', 'obra']
        );
    }

    private function eventosMantenimientosMaquinaria(CarbonInterface $inicio, CarbonInterface $fin, array $filtros): Collection
    {
        return $this->eventosMantenimientos(
            $inicio,
            $fin,
            $filtros,
            self::CATEGORIA_MAQUINARIA,
            'maquina_id',
            ['maquina', 'mecanico', 'obra']
        );
    }

    private function eventosMantenimientos(CarbonInterface $inicio, CarbonInterface $fin, array $filtros, string $categoria, string $foreignKey, array $relaciones): Collection
    {
        $mantenimientos = Mantenimiento::query()
            ->with($relaciones)
            ->whereNotNull($foreignKey)
            ->whereBetween('fecha_programada', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->when($categoria === self::CATEGORIA_VEHICULOS && ($filtros['vehiculo_id'] ?? null), fn ($query, $vehiculoId) => $query->where('vehiculo_id', $vehiculoId))
            ->when($categoria === self::CATEGORIA_MAQUINARIA && ($filtros['maquina_id'] ?? null), fn ($query, $maquinaId) => $query->where('maquina_id', $maquinaId))
            ->when($filtros['obra_id'] ?? null, fn ($query, $obraId) => $query->where('obra_id', $obraId))
            ->get();

        return $mantenimientos->map(function (Mantenimiento $mantenimiento) use ($categoria) {
            $equipo = $categoria === self::CATEGORIA_VEHICULOS
                ? $this->tituloVehiculo($mantenimiento->vehiculo)
                : $this->tituloMaquina($mantenimiento->maquina);

            $tipo = $mantenimiento->tipo ? ucfirst($mantenimiento->tipo) : 'Servicio';

            return $this->evento(
                id: "mantenimientos:{$mantenimiento->id}:fecha_programada",
                source: 'mantenimientos',
                sourceId: $mantenimiento->id,
                category: $categoria,
                type: 'servicio_programado',
                title: "{$tipo} programado: {$equipo}",
                startsAt: $mantenimiento->fecha_programada,
                allDay: false,
                status: $mantenimiento->estatus,
                url: route('mantenimiento.mantenimientos.edit', $mantenimiento),
                meta: [
                    'mecanico' => $mantenimiento->mecanico->nombre_completo ?? null,
                    'obra' => $mantenimiento->obra->nombre ?? null,
                    'categoria_mantenimiento' => $mantenimiento->categoria_mantenimiento,
                ]
            );
        });
    }

    private function eventosSeguros(CarbonInterface $inicio, CarbonInterface $fin, array $filtros): Collection
    {
        $seguros = Seguro::query()
            ->with('asegurable')
            ->where(function ($query) use ($inicio, $fin) {
                $query->whereBetween('vigencia_desde', [$inicio->toDateString(), $fin->toDateString()])
                    ->orWhereBetween('vigencia_hasta', [$inicio->toDateString(), $fin->toDateString()]);
            })
            ->get();

        return $seguros->flatMap(function (Seguro $seguro) use ($inicio, $fin) {
            $asegurable = $seguro->asegurable;
            $equipo = $this->tituloAsegurable($asegurable);

            return collect([
                ['vigencia_desde', 'inicio_vigencia', 'Inicio vigencia'],
                ['vigencia_hasta', 'vencimiento', 'Vence seguro'],
            ])->map(function (array $config) use ($seguro, $equipo, $inicio, $fin) {
                [$campo, $tipo, $label] = $config;
                $fecha = $seguro->{$campo};

                if (!$fecha || !$this->fechaEnRango($fecha, $inicio, $fin)) {
                    return null;
                }

                return $this->evento(
                    id: "seguros:{$seguro->id}:{$campo}",
                    source: 'seguros',
                    sourceId: $seguro->id,
                    category: self::CATEGORIA_SEGUROS,
                    type: $tipo,
                    title: "{$label}: {$equipo}",
                    startsAt: $fecha,
                    status: $seguro->estatus,
                    url: $this->urlSeguro($seguro),
                    meta: [
                        'aseguradora' => $seguro->aseguradora,
                        'poliza_numero' => $seguro->poliza_numero,
                        'tipo_seguro' => $seguro->tipo_seguro,
                    ]
                );
            })->filter();
        });
    }

    private function eventosOrdenesCompra(CarbonInterface $inicio, CarbonInterface $fin, array $filtros): Collection
    {
        $ordenes = OrdenCompra::query()
            ->with(['proveedor', 'obra'])
            ->when($filtros['proveedor_id'] ?? null, fn ($query, $proveedorId) => $query->where('proveedor_id', $proveedorId))
            ->when($filtros['obra_id'] ?? null, fn ($query, $obraId) => $query->where('obra_id', $obraId))
            ->where(function ($query) use ($inicio, $fin) {
                $query->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                    ->orWhereBetween('fecha_autorizacion', [$inicio->toDateString(), $fin->toDateString()]);
            })
            ->get();

        return $ordenes->flatMap(function (OrdenCompra $orden) use ($inicio, $fin) {
            return collect([
                ['fecha', 'fecha_oc', 'Orden de compra'],
                ['fecha_autorizacion', 'autorizacion', 'OC autorizada'],
            ])->map(function (array $config) use ($orden, $inicio, $fin) {
                [$campo, $tipo, $label] = $config;
                $fecha = $orden->{$campo};

                if (!$fecha || !$this->fechaEnRango($fecha, $inicio, $fin)) {
                    return null;
                }

                $folio = $orden->folio ?: ('OC #' . $orden->id);

                return $this->evento(
                    id: "ordenes_compra:{$orden->id}:{$campo}",
                    source: 'ordenes_compra',
                    sourceId: $orden->id,
                    category: self::CATEGORIA_ORDENES_COMPRA,
                    type: $tipo,
                    title: "{$label}: {$folio}",
                    startsAt: $fecha,
                    status: $orden->estado_normalizado,
                    url: route('ordenes_compra.print', $orden),
                    meta: [
                        'proveedor' => $orden->proveedor->nombre ?? null,
                        'obra' => $orden->obra->nombre ?? null,
                        'total' => $orden->total,
                    ]
                );
            })->filter();
        });
    }

    private function eventosRh(CarbonInterface $inicio, CarbonInterface $fin, array $filtros): Collection
    {
        $empleados = Empleado::query()
            ->where('Estatus', 1)
            ->where(function ($query) {
                $query->whereNotNull('Fecha_nacimiento')
                    ->orWhereNotNull('Fecha_ingreso');
            })
            ->get();

        return $empleados->flatMap(function (Empleado $empleado) use ($inicio, $fin) {
            return $this->eventosCumpleanosEmpleado($empleado, $inicio, $fin)
                ->merge($this->eventosAniversariosEmpleado($empleado, $inicio, $fin));
        });
    }

    private function eventosCumpleanosEmpleado(Empleado $empleado, CarbonInterface $inicio, CarbonInterface $fin): Collection
    {
        if (!$empleado->Fecha_nacimiento) {
            return collect();
        }

        return collect(range((int) $inicio->year, (int) $fin->year))
            ->map(fn (int $year) => $this->fechaAnual($empleado->Fecha_nacimiento, $year))
            ->filter(fn (CarbonInterface $fecha) => $this->fechaEnRango($fecha, $inicio, $fin))
            ->map(function (CarbonInterface $fecha) use ($empleado) {
                return $this->evento(
                    id: "empleados:{$empleado->id_Empleado}:cumpleanos:{$fecha->year}",
                    source: 'empleados',
                    sourceId: $empleado->id_Empleado,
                    category: self::CATEGORIA_RH,
                    type: 'cumpleanos',
                    title: 'Cumpleanos: ' . ($empleado->nombre_completo ?: 'Empleado #' . $empleado->id_Empleado),
                    startsAt: $fecha,
                    status: (string) ($empleado->Estatus ?? ''),
                    url: route('empleados.edit', $empleado),
                    meta: [
                        'puesto' => $empleado->Puesto,
                        'area' => $empleado->Area,
                    ]
                );
            });
    }

    private function eventosAniversariosEmpleado(Empleado $empleado, CarbonInterface $inicio, CarbonInterface $fin): Collection
    {
        if (!$empleado->Fecha_ingreso) {
            return collect();
        }

        return collect(range((int) $inicio->year, (int) $fin->year))
            ->map(function (int $year) use ($empleado) {
                $fecha = $this->fechaAnual($empleado->Fecha_ingreso, $year);
                $anios = $year - (int) $empleado->Fecha_ingreso->year;

                return [$fecha, $anios];
            })
            ->filter(fn (array $item) => $item[1] > 0 && $this->fechaEnRango($item[0], $inicio, $fin))
            ->map(function (array $item) use ($empleado) {
                [$fecha, $anios] = $item;
                $nombre = $empleado->nombre_completo ?: 'Empleado #' . $empleado->id_Empleado;

                return $this->evento(
                    id: "empleados:{$empleado->id_Empleado}:aniversario_laboral:{$fecha->year}",
                    source: 'empleados',
                    sourceId: $empleado->id_Empleado,
                    category: self::CATEGORIA_RH,
                    type: 'aniversario_laboral',
                    title: $this->tituloAniversarioLaboral($anios, $nombre),
                    startsAt: $fecha,
                    status: (string) ($empleado->Estatus ?? ''),
                    url: route('empleados.edit', $empleado),
                    meta: [
                        'puesto' => $empleado->Puesto,
                        'area' => $empleado->Area,
                        'fecha_ingreso' => $empleado->Fecha_ingreso->toDateString(),
                        'anios' => $anios,
                    ]
                );
            });
    }

    private function evento(string $id, string $source, int|string $sourceId, string $category, string $type, string $title, CarbonInterface|string $startsAt, ?CarbonInterface $endsAt = null, bool $allDay = true, ?string $status = null, ?string $url = null, array $meta = []): array
    {
        $inicio = $this->toCarbon($startsAt);

        return [
            'id' => $id,
            'source' => $source,
            'source_id' => $sourceId,
            'category' => $category,
            'type' => $type,
            'title' => $title,
            'starts_at' => $allDay ? $inicio->toDateString() : $inicio->toIso8601String(),
            'ends_at' => $endsAt ? ($allDay ? $endsAt->toDateString() : $endsAt->toIso8601String()) : null,
            'all_day' => $allDay,
            'status' => $status,
            'color' => self::COLORES[$category] ?? '#64748b',
            'url' => $url,
            'meta' => array_filter($meta, fn ($value) => $value !== null && $value !== ''),
        ];
    }

    private function normalizarRango(null|string|CarbonInterface $start, null|string|CarbonInterface $end): array
    {
        $inicio = $start ? $this->toCarbon($start)->startOfDay() : now()->startOfMonth();
        $fin = $end ? $this->toCarbon($end)->endOfDay() : now()->endOfMonth();

        if ($fin->lt($inicio)) {
            [$inicio, $fin] = [$fin->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        return [$inicio, $fin];
    }

    private function normalizarCategorias(array $categorias): array
    {
        $categorias = array_values(array_filter($categorias));

        if ($categorias === []) {
            return self::CATEGORIAS_DEFAULT;
        }

        return array_values(array_intersect($categorias, self::CATEGORIAS_DEFAULT));
    }

    private function tituloAniversarioLaboral(int $anios, string $nombre): string
    {
        $label = $anios === 1 ? 'Primer aniversario laboral' : "{$anios} aniversario laboral";

        return "{$label}: {$nombre}";
    }
    private function fechaAnual(CarbonInterface $fechaBase, int $year): Carbon
    {
        $month = (int) $fechaBase->month;
        $day = (int) $fechaBase->day;

        if ($month === 2 && $day === 29 && !Carbon::create($year)->isLeapYear()) {
            $day = 28;
        }

        return Carbon::create($year, $month, $day)->startOfDay();
    }
    private function fechaEnRango(CarbonInterface|string $fecha, CarbonInterface $inicio, CarbonInterface $fin): bool
    {
        $fecha = $this->toCarbon($fecha);

        return $fecha->betweenIncluded($inicio, $fin);
    }

    private function toCarbon(CarbonInterface|string $fecha): Carbon
    {
        if ($fecha instanceof CarbonInterface) {
            return Carbon::instance($fecha->toDateTime());
        }

        return Carbon::parse($fecha);
    }

    private function tituloVehiculo(?Vehiculo $vehiculo): string
    {
        if (!$vehiculo) {
            return 'Vehiculo sin referencia';
        }

        return trim(implode(' ', array_filter([
            $vehiculo->marca,
            $vehiculo->modelo,
            $vehiculo->placas ? '(' . $vehiculo->placas . ')' : null,
        ]))) ?: ('Vehiculo #' . $vehiculo->id);
    }

    private function tituloMaquina(?Maquina $maquina): string
    {
        if (!$maquina) {
            return 'Maquina sin referencia';
        }

        return trim(implode(' ', array_filter([
            $maquina->nombre,
            $maquina->codigo ? '(' . $maquina->codigo . ')' : null,
        ]))) ?: ('Maquina #' . $maquina->id);
    }

    private function tituloAsegurable(mixed $asegurable): string
    {
        if ($asegurable instanceof Vehiculo) {
            return $this->tituloVehiculo($asegurable);
        }

        if ($asegurable instanceof Maquina) {
            return $this->tituloMaquina($asegurable);
        }

        return 'Activo asegurado';
    }

    private function urlSeguro(Seguro $seguro): ?string
    {
        $asegurable = $seguro->asegurable;

        if ($asegurable instanceof Vehiculo) {
            return route('vehiculos.seguros.edit', ['vehiculo' => $asegurable, 'seguro' => $seguro]);
        }

        if ($asegurable instanceof Maquina) {
            return route('maquinas.seguros.edit', ['maquina' => $asegurable, 'seguro' => $seguro]);
        }

        return null;
    }
}
