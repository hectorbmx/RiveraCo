<?php

namespace App\Services\Mobile;

use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\ObraMaquina;
use App\Models\ObraPila;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Models\VehiculoEmpleado;
use Illuminate\Support\Collection;

class AppMobileContextService
{
    private const ADMIN_ROLES = [
        'super-admin',
        'admin-rivera',
        'Super Admin',
        'Admin Rivera',
        'Administrador',
        'admin',
    ];

    public function opciones(User $user, UsuarioApp $usuarioApp, ?int $obraId = null): array
    {
        $puedeGerencial = $this->puedeVerGerencial($user);
        $puedeResidente = $this->puedeVerPanelResidente($user);
        $obrasResidente = $puedeResidente ? $this->obrasDisponiblesResidente($user, $usuarioApp) : collect();
        $obraSeleccionada = $this->resolverObraSeleccionada($obrasResidente, $obraId);

        return [
            'paneles' => $this->panelesDisponibles($puedeResidente, $puedeGerencial),
            'obras_residente' => $obrasResidente
                ->map(fn (Obra $obra) => $this->mapObraOpcion($obra))
                ->values(),
            'defaults' => [
                'panel' => $this->resolverPanelDefault($puedeResidente, $puedeGerencial),
                'obra_id' => $obraSeleccionada?->id,
            ],
        ];
    }

    public function contextoResidente(User $user, UsuarioApp $usuarioApp, ?int $obraId = null): ?array
    {
        if (! $this->puedeVerPanelResidente($user)) {
            return null;
        }

        $obras = $this->obrasDisponiblesResidente($user, $usuarioApp);
        $obra = $this->resolverObraSeleccionada($obras, $obraId);

        return $obra ? $this->armarContextoResidente($user, $usuarioApp, $obra) : null;
    }

    public function puedeVerPanelResidente(User $user): bool
    {
        return $user->hasRole('residente') || $this->esAdminRivera($user);
    }

    public function puedeVerGerencial(User $user): bool
    {
        return $user->can('app.gerencial.access');
    }

    public function puedeVerTodasLasObrasResidente(User $user): bool
    {
        return $this->esAdminRivera($user);
    }

    public function vehiculoActivoParaContexto(User $user, UsuarioApp $usuarioApp, Obra $obra): ?VehiculoEmpleado
    {
        if ($this->esAdminRivera($user)) {
            $residenteId = $this->empleadoResidenteDeObra($obra);

            if ($residenteId) {
                $vehiculoResidente = $this->vehiculoActivoDeEmpleado($residenteId);

                if ($vehiculoResidente) {
                    return $vehiculoResidente;
                }
            }
        }

        return $usuarioApp->empleado_id
            ? $this->vehiculoActivoDeEmpleado((int) $usuarioApp->empleado_id)
            : null;
    }

    public function obrasDisponiblesResidente(User $user, UsuarioApp $usuarioApp): Collection
    {
        if ($this->esAdminRivera($user)) {
            return Obra::query()
                ->select('id', 'cliente_id', 'nombre', 'clave_obra', 'tipo_obra', 'ubicacion', 'estatus_nuevo', 'fecha_inicio_programada', 'fecha_inicio_real')
                ->with(['cliente:id,nombre_comercial'])
                ->whereNotIn('estatus_nuevo', [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA])
                ->orderBy('nombre')
                ->get();
        }

        if (! $usuarioApp->empleado_id) {
            return collect();
        }

        return ObraEmpleado::query()
            ->with(['obra.cliente:id,nombre_comercial'])
            ->where('empleado_id', $usuarioApp->empleado_id)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->whereHas('obra', function ($query) {
                $query->whereNotIn('estatus_nuevo', [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA]);
            })
            ->orderByDesc('id')
            ->get()
            ->map(fn (ObraEmpleado $asignacion) => $asignacion->obra)
            ->filter()
            ->unique('id')
            ->values();
    }

    private function panelesDisponibles(bool $puedeResidente, bool $puedeGerencial): array
    {
        $paneles = [];

        if ($puedeResidente) {
            $paneles[] = [
                'key' => 'residente',
                'label' => 'Residente',
            ];
        }

        if ($puedeGerencial) {
            $paneles[] = [
                'key' => 'gerencial',
                'label' => 'Gerencial',
            ];
        }

        return $paneles;
    }

    private function resolverPanelDefault(bool $puedeResidente, bool $puedeGerencial): ?string
    {
        if ($puedeResidente && ! $puedeGerencial) {
            return 'residente';
        }

        if ($puedeGerencial && ! $puedeResidente) {
            return 'gerencial';
        }

        return null;
    }

    private function resolverObraSeleccionada(Collection $obras, ?int $obraId): ?Obra
    {
        if ($obraId) {
            return $obras->first(fn (Obra $obra) => (int) $obra->id === $obraId);
        }

        return $obras->first();
    }

    private function armarContextoResidente(User $user, UsuarioApp $usuarioApp, Obra $obra): array
    {
        $empleadoId = $usuarioApp->empleado_id;

        $empleadosObra = ObraEmpleado::query()
            ->with([
                'empleado:id_Empleado,Nombre,Apellidos,Telefono',
                'rol:id,rol_key,nombre',
            ])
            ->where('obra_id', $obra->id)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->orderBy('id')
            ->get()
            ->map(function ($oe) {
                return [
                    'obra_empleado_id' => $oe->id,
                    'empleado_id' => $oe->empleado_id,
                    'rol_id' => $oe->rol_id,
                    'rol' => $oe->rol ? [
                        'id' => $oe->rol->id,
                        'rol_key' => $oe->rol->rol_key ?? null,
                        'nombre' => $oe->rol->nombre ?? null,
                    ] : null,
                    'empleado' => $oe->empleado ? [
                        'id_Empleado' => $oe->empleado->id_Empleado,
                        'nombre' => trim(($oe->empleado->Nombre ?? '') . ' ' . ($oe->empleado->Apellidos ?? '')),
                        'telefono' => $oe->empleado->Telefono ?? $oe->empleado->telefono ?? null,
                    ] : null,
                ];
            })
            ->values();

        $maquinaActiva = ObraMaquina::query()
            ->with(['maquina'])
            ->where('obra_id', $obra->id)
            ->activas()
            ->latest('fecha_inicio')
            ->first();

        $vehiculoAsignado = $this->vehiculoActivoParaContexto($user, $usuarioApp, $obra);

        $pilasRaw = ObraPila::query()
            ->where('obra_id', $obra->id)
            ->orderBy('numero_pila')
            ->get();

        $pilas = $pilasRaw->map(function ($p) {
            return [
                'id' => (int) $p->id,
                'obra_id' => (int) $p->obra_id,
                'numero_pila' => $p->numero_pila ?? null,
                'tipo' => $p->tipo ?? null,
                'cantidad_programada' => $p->cantidad_programada !== null ? (int) $p->cantidad_programada : 0,
                'diametro' => $p->diametro_proyecto !== null ? (float) $p->diametro_proyecto : null,
                'profundidad' => $p->profundidad_proyecto !== null ? (float) $p->profundidad_proyecto : null,
                'ubicacion' => $p->ubicacion ?? null,
                'activo' => (bool) $p->activo,
            ];
        })->values();

        return [
            'obra' => $this->mapObraContexto($obra, (int) $pilasRaw->sum('cantidad_programada')),
            'empleados' => $empleadosObra,
            'maquina' => $maquinaActiva ? [
                'obra_maquina_id' => $maquinaActiva->id,
                'maquina_id' => $maquinaActiva->maquina_id,
                'fecha_inicio' => optional($maquinaActiva->fecha_inicio)->toDateString(),
                'horometro_inicio' => $maquinaActiva->horometro_inicio,
                'estado' => $maquinaActiva->estado,
                'maquina' => $maquinaActiva->maquina ? [
                    'id' => $maquinaActiva->maquina->id ?? null,
                    'nombre' => $maquinaActiva->maquina->nombre ?? null,
                ] : null,
            ] : null,
            'pilas' => $pilas,
            'vehiculo' => $vehiculoAsignado ? [
                'vehiculo_id' => $vehiculoAsignado->vehiculo_id,
                'fecha_asignacion' => $vehiculoAsignado->fecha_asignacion?->toDateString(),
                'fecha_fin' => $vehiculoAsignado->fecha_fin?->toDateString(),
                'notas' => $vehiculoAsignado->notas,
                'vehiculo' => $vehiculoAsignado->vehiculo ? [
                    'id' => $vehiculoAsignado->vehiculo->id,
                    'marca' => $vehiculoAsignado->vehiculo->marca ?? null,
                    'modelo' => $vehiculoAsignado->vehiculo->modelo ?? null,
                    'placas' => $vehiculoAsignado->vehiculo->placas ?? null,
                    'anio' => $vehiculoAsignado->vehiculo->anio ?? null,
                    'color' => $vehiculoAsignado->vehiculo->color ?? null,
                    'tipo' => $vehiculoAsignado->vehiculo->tipo ?? null,
                    'estatus' => $vehiculoAsignado->vehiculo->estatus ?? null,
                ] : null,
            ] : null,
        ];
    }

    private function mapObraOpcion(Obra $obra): array
    {
        return [
            'id' => (int) $obra->id,
            'cliente_id' => $obra->cliente_id !== null ? (int) $obra->cliente_id : null,
            'cliente_nombre' => $obra->cliente?->nombre_comercial,
            'nombre' => $obra->nombre,
            'clave_obra' => $obra->clave_obra,
            'tipo_obra' => $obra->tipo_obra,
            'ubicacion' => $obra->ubicacion,
            'estatus_nuevo' => $obra->estatus_nuevo !== null ? (int) $obra->estatus_nuevo : null,
        ];
    }

    private function mapObraContexto(Obra $obra, int $pilasTotalProgramado): array
    {
        return [
            'id' => $obra->id,
            'cliente_id' => $obra->cliente_id,
            'cliente_nombre' => $obra->cliente?->nombre_comercial,
            'nombre' => $obra->nombre,
            'clave_obra' => $obra->clave_obra,
            'tipo_obra' => $obra->tipo_obra,
            'ubicacion' => $obra->ubicacion,
            'estatus_nuevo' => $obra->estatus_nuevo,
            'fecha_inicio_programada' => optional($obra->fecha_inicio_programada)->toDateString(),
            'fecha_inicio_real' => optional($obra->fecha_inicio_real)->toDateString(),
            'pilas_total_programado' => $pilasTotalProgramado,
        ];
    }

    private function esAdminRivera(User $user): bool
    {
        return $user->hasAnyRole(self::ADMIN_ROLES);
    }

    private function vehiculoActivoDeEmpleado(int $empleadoId): ?VehiculoEmpleado
    {
        return VehiculoEmpleado::query()
            ->with(['vehiculo', 'empleado'])
            ->where('empleado_id', $empleadoId)
            ->whereNull('fecha_fin')
            ->latest('id')
            ->first();
    }

    private function empleadoResidenteDeObra(Obra $obra): ?int
    {
        $asignacion = ObraEmpleado::query()
            ->with(['empleado', 'rol'])
            ->where('obra_id', $obra->id)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->orderByDesc('id')
            ->get()
            ->first(fn (ObraEmpleado $asignacion) => $this->esAsignacionResidente($asignacion));

        return $asignacion?->empleado_id ? (int) $asignacion->empleado_id : null;
    }

    private function esAsignacionResidente(ObraEmpleado $asignacion): bool
    {
        $campos = [
            $asignacion->empleado?->Puesto,
            $asignacion->empleado?->puesto_base,
            $asignacion->puesto_en_obra,
            $asignacion->rol?->rol_key,
            $asignacion->rol?->nombre,
        ];

        foreach ($campos as $campo) {
            if (str_contains(strtoupper((string) $campo), 'RESIDENTE')) {
                return true;
            }
        }

        return false;
    }
}

