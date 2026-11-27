<?php

namespace App\Models;

use App\Support\PlantillaApupi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Presupuesto extends Model
{
    use HasFactory;

    public const ORIGEN_EXCEL = 'excel';
    public const ORIGEN_SIRICO = 'sirico';
    public const TIPOS = ['PILAS' => 'PI', 'POZOS' => 'PO', 'MUROS' => 'MU', 'ANCLAS' => 'AN'];
    public const ESCENARIOS_APUPI = ['realista', 'optimista', 'pesimista'];

    // Opciones de perforadora de APUPI, filas 24 a 32.
    public const EQUIPOS_PROPUESTOS = [
        'retro' => 'Retro micropilas',
        'bore_b2_b3_b5_b6' => 'Bore B2, B3, B5 y B6',
        'bore_b7_b8' => 'Bore B7 y B8',
        'spiradrill' => 'Spiradrill S1, S2 y S3',
        'bauer_bg9' => 'Bauer BG-9V',
        'bauer_bg12' => 'Bauer BG-12 H',
        'bauer_bg18' => 'Bauer BG-18V',
        'bauer_bg25' => 'Bauer BG-25V',
        'bauer_bg26' => 'Bauer BG-26V',
    ];

    // Definimos el nombre de la tabla (opcional si sigue la convención plural)
    protected $table = 'presupuestos';

    protected $fillable = [
        'codigo_proyecto',
        'tipo',
        'nombre_cliente',
        'descripcion',
        'total_costo_directo',
        'total_presupuesto',
        'estatus',
        'origen',
        'cliente_id',
        'nombre_obra',
        'estado',
        'ciudad',
        'equipo_propuesto',
    ];

    /**
     * Opcional: Si quieres que los montos siempre se traten como números
     * con decimales al recuperarlos en Laravel.
     */
    protected $casts = [
        'presentacion_comercial' => 'array',
        'datos_previo' => 'array',
        'ajustes_gastos_semanales' => 'array',
        'consecutivo_cliente' => 'integer',
        'total_costo_directo' => 'decimal:2',
        'total_presupuesto'   => 'decimal:2',
        'duracion_apupi' => 'array',
        'ajustes_apupi' => 'array',
        'ajustes_propuesta_apupi' => 'array',
        'indirecto_traslado_apupi' => 'decimal:2',
        'indirectos_por_partida_apupi' => 'array',
    ];

    public function filasApupi(array $duracion, float $ventaPropuesta = 0): Collection
    {
        $ajustes = $this->ajustes_apupi ?? [];
        $apariciones = [];
        $importados = [];
        $adicionales = [];

        foreach ($this->detalles->sortBy('id') as $detalle) {
            $identidad = $detalle->identidadApupi();
            // Excel vuelve a crear los IDs al sincronizar. El ordinal distingue renglones repetidos.
            $apariciones[$identidad] = ($apariciones[$identidad] ?? 0) + 1;
            $clave = $identidad . ':' . $apariciones[$identidad];
            $codigo = PlantillaApupi::codigoImportado($detalle);
            if ($codigo !== null && ! isset($importados[$codigo])) {
                $importados[$codigo] = [$detalle, $clave];
                continue;
            }

            $adicionales[$detalle->id] = ['detalle' => $detalle, 'clave' => $clave] + $detalle->valoresApupi($ajustes[$clave] ?? null, $duracion);
        }

        $filas = [];
        foreach (PlantillaApupi::conceptos() as $codigo => [$partida, $concepto, $unidad, $precio, $regla]) {
            [$detalle, $anterior] = $importados[$codigo] ?? [null, null];
            $detalle ??= new PresupuestoDetalle([
                'partida' => $partida,
                'concepto' => $concepto,
                'unidad' => $unidad,
                'cantidad' => 0,
                'precio_unitario' => PlantillaApupi::precioBase($codigo, $precio, $filas, $ventaPropuesta),
                'importe' => 0,
                'importe_optimista' => 0,
                'importe_pesimista' => 0,
            ]);
            $ajuste = $ajustes[$codigo] ?? ($anterior === null ? null : ($ajustes[$anterior] ?? null));
            $filas[$codigo] = ['detalle' => $detalle, 'clave' => $codigo, 'clave_anterior' => $anterior]
                + $detalle->valoresApupi($ajuste, $duracion, $regla);
        }

        return collect($filas + $adicionales);
    }

    public function indirectoTrasladoApupi(): float
    {
        return (float) ($this->indirecto_traslado_apupi ?? PlantillaApupi::INDIRECTO);
    }

    public function porcentajeIndirectoApupi(string $partida): float
    {
        return $partida === PresupuestoDetalle::PARTIDA_TRASLADO
            ? $this->indirectoTrasladoApupi()
            : (float) ($this->indirectos_por_partida_apupi[$partida] ?? PlantillaApupi::INDIRECTO);
    }

    public function resumenApupi(Collection $filas, float $volumenPerforacion): array
    {
        $sumar = fn (Collection $grupo) => collect(self::ESCENARIOS_APUPI)->mapWithKeys(function ($escenario) use ($grupo) {
            $importes = $grupo->pluck('importes.' . $escenario)->whereNotNull();

            return [$escenario => $importes->isEmpty() ? null : round($importes->sum(), 2)];
        });
        $subtotales = $filas->groupBy(fn ($fila) => $fila['detalle']->partidaApupi())->map($sumar);
        $subtotalesConIndirecto = $subtotales->only(PlantillaApupi::partidasConIndirecto());
        $porcentajesIndirectos = $subtotalesConIndirecto->map(fn (Collection $valores, string $partida) => $this->porcentajeIndirectoApupi($partida));
        $indirectos = $subtotalesConIndirecto->map(function (Collection $valores, string $partida) use ($porcentajesIndirectos) {
            $porcentaje = $porcentajesIndirectos[$partida];

            return $valores->map(fn ($subtotal) => $subtotal === null ? null : round($subtotal * $porcentaje / 100, 2));
        });
        $totalesPartida = $subtotales->map(fn (Collection $valores, string $partida) => $valores->map(
            fn ($subtotal, $escenario) => $subtotal === null ? null : round($subtotal + ($indirectos[$partida][$escenario] ?? 0), 2)
        ));
        $total = $sumar($filas);
        $partidasPendientes = $filas->filter(fn ($fila) => $fila['cantidad'] > 0
            && $fila['precio_unitario'] > 0 && $fila['importes']['realista'] === null)
            ->map(fn ($fila) => $fila['detalle']->partidaApupi())->unique();
        $minimos = $totalesPartida->map(
            fn (Collection $valores, string $partida) => $partidasPendientes->contains($partida) ? null : $valores['realista']
        );
        $minimoPerforacion = $minimos['PERFORACION'];

        return [
            'filas' => $filas->map(fn ($fila) => $fila['importes']),
            'precios' => $filas->map(fn ($fila) => $fila['precio_unitario']),
            'subtotales' => $subtotales,
            'indirectos' => $indirectos,
            'porcentajes_indirectos' => $porcentajesIndirectos,
            'totales_partida' => $totalesPartida,
            'total' => $total,
            'realista' => [
                'minimos' => $minimos,
                'total_directo' => $partidasPendientes->isEmpty() ? $total['realista'] : null,
                'total_minimo' => $partidasPendientes->isEmpty() ? round($minimos->sum(), 2) : null,
                'perforacion_por_m3' => $volumenPerforacion > 0 && $minimoPerforacion !== null
                    ? round($minimoPerforacion / $volumenPerforacion, 2)
                    : null,
                'pendiente' => $partidasPendientes->isNotEmpty(),
            ],
        ];
    }

    public function duracionApupi(?float $jornalesVol): array
    {
        $manual = $this->duracion_apupi ?? [];
        $equipos = (int) ($manual['equipos'] ?? 1);
        $baseRealista = $manual['realista']['jornales'] ?? $jornalesVol;
        $realista = $baseRealista === null ? null : ceil(round((float) $baseRealista, 12));
        // APUPI F7:H7: el optimista redondea hacia abajo y el pesimista hacia arriba.
        $jornales = [
            'realista' => $realista,
            'optimista' => $realista === null ? null : floor($realista * 9 / 10),
            'pesimista' => $realista === null ? null : ceil($realista * 12 / 10),
        ];

        $duracion = [];
        foreach ($jornales as $escenario => $estimado) {
            $jornalesEscenario = isset($manual[$escenario]['jornales']) ? ceil((float) $manual[$escenario]['jornales']) : $estimado;
            $dias = $manual[$escenario]['dias'] ?? ($jornalesEscenario === null ? null : floor($jornalesEscenario + $jornalesEscenario / 6));
            $duracion[$escenario] = [
                'jornales' => $jornalesEscenario,
                'dias' => $dias,
                'semanas' => $manual[$escenario]['semanas'] ?? ($dias === null ? null : ceil($dias / 7)),
                'equipos' => $equipos,
            ];
        }

        return $duracion;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $presupuesto): void {
            if ($presupuesto->getOriginal('consecutivo_cliente') !== null
                && $presupuesto->isDirty(['cliente_id', 'consecutivo_cliente'])) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'El cliente y el consecutivo quedan fijos al asignar el número de presupuesto.',
                ]);
            }
        });
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(PresupuestoVersion::class);
    }

    public function ultimaVersion(): HasOne
    {
        return $this->hasOne(PresupuestoVersion::class)->ofMany('revision', 'max');
    }

    public function versionAprobada(): BelongsTo
    {
        return $this->belongsTo(PresupuestoVersion::class, 'version_aprobada_id');
    }

    public function versionParaDuplicar(): ?PresupuestoVersion
    {
        return $this->estaAprobado() ? $this->versionAprobada()->first() : $this->ultimaVersion()->first();
    }

    public function obraGenerada(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'obra_generada_id');
    }

    public function estaAprobado(): bool
    {
        return $this->version_aprobada_id !== null;
    }

    public function origenBorrador(): BelongsTo
    {
        return $this->belongsTo(PresupuestoVersion::class, 'version_origen_borrador_id');
    }

    public function eventos(): HasManyThrough
    {
        return $this->hasManyThrough(PresupuestoEvento::class, PresupuestoVersion::class, 'presupuesto_id', 'presupuesto_version_id');
    }

    public function ultimoMovimiento(): BelongsTo
    {
        return $this->belongsTo(PresupuestoEvento::class, 'ultimo_movimiento_id');
    }

    public function aceptacionVigente(): BelongsTo
    {
        return $this->belongsTo(PresupuestoEvento::class, 'aceptacion_vigente_id');
    }

    public function asignarConsecutivoCliente(): int
    {
        $consecutivo = DB::transaction(function (): int {
            // El bloqueo del cliente serializa las asignaciones entre sus presupuestos.
            $cliente = Cliente::whereKey($this->cliente_id)->lockForUpdate()->first();
            $presupuesto = self::lockForUpdate()->findOrFail($this->getKey());
            if ($cliente === null || (int) $presupuesto->cliente_id !== (int) $cliente->getKey()) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'Selecciona el cliente del presupuesto antes de asignar su consecutivo.',
                ]);
            }

            if ($presupuesto->consecutivo_cliente === null) {
                $ultimo = self::where('cliente_id', $cliente->getKey())
                    ->whereNotNull('consecutivo_cliente')
                    ->orderByDesc('consecutivo_cliente')
                    ->lockForUpdate()
                    ->value('consecutivo_cliente');
                $presupuesto->consecutivo_cliente = (int) $ultimo + 1;
                $presupuesto->save();
            }

            return $presupuesto->consecutivo_cliente;
        }, 3);

        $this->consecutivo_cliente = $consecutivo;
        $this->syncOriginalAttribute('consecutivo_cliente');

        return $consecutivo;
    }

    public function esImportado(): bool
    {
        return $this->origen === self::ORIGEN_EXCEL;
    }

    public function esBorrador(): bool
    {
        return strtolower(trim((string) $this->estatus)) === 'borrador';
    }

    public function nombreClienteVisible(): string
    {
        return $this->cliente?->nombre_comercial ?: $this->nombre_cliente;
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(PresupuestoPartida::class)->orderBy('orden')->orderBy('id');
    }

    public function usaEditorPorPartidas(): bool
    {
        return $this->origen === self::ORIGEN_SIRICO
            && in_array($this->tipo, ['MUROS', 'ANCLAS'], true)
            && ($this->presentacion_comercial['editor'] ?? null) === 'partidas';
    }

    public function detalles()
        {
            return $this->hasMany(PresupuestoDetalle::class);
        }

        public function pilas()
        {
            // Cambiamos PresupuestoPilas por PresupuestoPila
            return $this->hasMany(PresupuestoPila::class);
        }
        public function pilasVol(): HasMany
        {
            return $this->hasMany(PresupuestoVolPila::class);
        }

        public function calcularVolumetria(): array
        {
            $pilas = $this->relationLoaded('pilasVol')
                ? $this->pilasVol->sortBy('id')->values()
                : $this->pilasVol()->orderBy('id')->get();
            $filas = $pilas->map(fn (PresupuestoVolPila $pila) => [
                'pila' => $pila,
                'vol' => $pila->volumetria((float) $this->vol_traslape_varilla),
            ]);

            $totales = ['piezas' => $filas->sum('pila.piezas')];
            foreach (['jornales', 'jornales_exactos', 'perforacion', 'metros_lineales', 'concreto', 'bentonita', 'retiro', 'comision_ml', 'acero'] as $campo) {
                $totales[$campo] = $filas->sum('vol.' . $campo);
            }

            // H23 promedia únicamente las longitudes L0 distintas de cero.
            $totales['longitud_l0_promedio'] = $filas->where('vol.longitud_l0', '>', 0)->avg('vol.longitud_l0');
            $porDiametro = $filas->groupBy(fn ($fila) => number_format($fila['pila']->diametro, 3, '.', ''))->map(fn ($tipos) => [
                'piezas' => $tipos->sum('pila.piezas'),
                'metros_lineales' => $tipos->sum('vol.metros_lineales'),
            ])->union(array_fill_keys(PresupuestoVolPila::DIAMETROS_VOL, ['piezas' => 0, 'metros_lineales' => 0]))
                ->sortKeys(SORT_NUMERIC);

            return compact('filas', 'totales', 'porDiametro');
        }

        public function resumenes()
        {
            return $this->hasMany(PresupuestoResumen::class);
        }
        public function obras()
        {
            return $this->belongsToMany(Obra::class, 'obra_presupuesto');
        }
}
