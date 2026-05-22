<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\ObraMaquina;
use App\Models\ObraPila;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Models\VehiculoEmpleado;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales invalidas.'],
            ]);
        }

        $usuarioApp = $this->validarUsuarioApp($user);
        $isResidente = $user->hasRole('residente');
        $isGerencial = $user->can('app.gerencial.access');

        if (!$isResidente && !$isGerencial) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu rol no tiene acceso a la app.',
            ], 403);
        }

        $contexto = null;
        if ($isResidente) {
            $contexto = $this->contextoResidente($usuarioApp);

            if (!$contexto) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes una obra activa asignada.',
                ], 403);
            }
        }

        $tokenName = $request->header('X-Device-Name', 'mobile');
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json($this->payloadSesion($user, $usuarioApp, $contexto, $token));
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $usuarioApp = $this->validarUsuarioApp($user);

        $isResidente = $user->hasRole('residente');
        $isGerencial = $user->can('app.gerencial.access');

        if (!$isResidente && !$isGerencial) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu rol no tiene acceso a la app.',
            ], 403);
        }

        $withContext = $request->boolean('with_context', true);
        $contexto = null;

        if ($withContext && $isResidente) {
            $contexto = $this->contextoResidente($usuarioApp);

            if (!$contexto) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes una obra activa asignada.',
                ], 403);
            }
        }

        return response()->json($this->payloadSesion($user, $usuarioApp, $contexto));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    private function validarUsuarioApp(User $user): UsuarioApp
    {
        $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();

        if (!$usuarioApp) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Este usuario no esta habilitado para la app.',
            ], 403));
        }

        if (!$usuarioApp->is_active) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Tu acceso a la app esta desactivado.',
            ], 403));
        }

        return $usuarioApp;
    }

    private function payloadSesion(User $user, UsuarioApp $usuarioApp, ?array $contexto, ?string $token = null): array
    {
        $payload = [
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name ?? null,
            ],
            'app' => [
                'user_app_id' => $usuarioApp->id,
                'empleado_id' => $usuarioApp->empleado_id,
                'is_active' => (bool) $usuarioApp->is_active,
            ],
            'authz' => [
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
            'contexto' => $contexto,
        ];

        if ($token !== null) {
            $payload['token'] = $token;
        }

        if ($contexto === null && $user->can('app.gerencial.access')) {
            $payload['gerencial'] = [
                'kpis' => null,
                'obras_preview' => [],
            ];
        }

        return $payload;
    }

    private function contextoResidente(UsuarioApp $usuarioApp): ?array
    {
        $empleadoId = $usuarioApp->empleado_id;

        $asignacionResidente = ObraEmpleado::query()
            ->select('id', 'obra_id', 'empleado_id', 'rol_id')
            ->where('empleado_id', $empleadoId)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->latest('id')
            ->first();

        if (!$asignacionResidente) {
            return null;
        }

        $obra = Obra::query()
            ->select('id', 'cliente_id', 'nombre', 'clave_obra', 'ubicacion', 'estatus_nuevo', 'fecha_inicio_programada', 'fecha_inicio_real')
            ->with(['cliente:id,nombre_comercial'])
            ->where('id', $asignacionResidente->obra_id)
            ->first();

        if (!$obra) {
            return null;
        }

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

        $maquinaPayload = null;
        if ($maquinaActiva) {
            $maquinaPayload = [
                'obra_maquina_id' => $maquinaActiva->id,
                'maquina_id' => $maquinaActiva->maquina_id,
                'fecha_inicio' => optional($maquinaActiva->fecha_inicio)->toDateString(),
                'horometro_inicio' => $maquinaActiva->horometro_inicio,
                'estado' => $maquinaActiva->estado,
                'maquina' => $maquinaActiva->maquina ? [
                    'id' => $maquinaActiva->maquina->id ?? null,
                    'nombre' => $maquinaActiva->maquina->nombre ?? null,
                ] : null,
            ];
        }

        $vehiculoAsignado = VehiculoEmpleado::query()
            ->with('vehiculo')
            ->where('empleado_id', $empleadoId)
            ->whereNull('fecha_fin')
            ->latest('id')
            ->first();

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
            'obra' => [
                'id' => $obra->id,
                'cliente_id' => $obra->cliente_id,
                'cliente_nombre' => $obra->cliente?->nombre_comercial,
                'nombre' => $obra->nombre,
                'clave_obra' => $obra->clave_obra,
                'ubicacion' => $obra->ubicacion,
                'estatus_nuevo' => $obra->estatus_nuevo,
                'fecha_inicio_programada' => optional($obra->fecha_inicio_programada)->toDateString(),
                'fecha_inicio_real' => optional($obra->fecha_inicio_real)->toDateString(),
                'pilas_total_programado' => (int) $pilasRaw->sum('cantidad_programada'),
            ],
            'empleados' => $empleadosObra,
            'maquina' => $maquinaPayload,
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
}
