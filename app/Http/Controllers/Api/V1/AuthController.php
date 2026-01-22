<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Obra;
use App\Models\ObraPila;
use App\Models\Cliente;
use App\Models\ObraEmpleado;
use App\Models\ObraMaquina;
use App\Models\UsuarioApp;
use App\Models\VehiculoEmpleado;


class AuthController extends Controller
{
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email'    => ['required', 'email'],
    //         'password' => ['required', 'string'],
    //     ]);

    //     $user = User::where('email', $request->email)->first();

    //     if (!$user || !Hash::check($request->password, $user->password)) {
    //         throw ValidationException::withMessages([
    //             'email' => ['Credenciales inválidas.'],
    //         ]);
    //     }
    //         $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();

    //         if (!$usuarioApp) {
    //             return response()->json([
    //                 'ok' => false,
    //                 'message' => 'Este usuario no está habilitado para la app.'
    //             ], 403);
    //         }

    //         if (!$usuarioApp->is_active) {
    //             return response()->json([
    //                 'ok' => false,
    //                 'message' => 'Tu acceso a la app está desactivado.'
    //             ], 403);
    //         }

    //         // Por ahora: solo residente
    //         if (!$user->hasRole('residente')) {
    //             return response()->json([
    //                 'ok' => false,
    //                 'message' => 'Tu rol no tiene acceso a la app.'
    //             ], 403);
    //         }

    //     // Opcional: revocar tokens anteriores (evita multi-sesión)
    //     // $user->tokens()->delete();

    //     $tokenName = $request->header('X-Device-Name', 'mobile');
    //     $token = $user->createToken($tokenName)->plainTextToken;

    //     return response()->json([
    //             'ok' => true,
    //             'token' => $token,
    //             'user' => [
    //                 'id' => $user->id,
    //                 'email' => $user->email,
    //                 'name' => $user->name ?? null,
    //             ],
    //             'app' => [
    //                 'user_app_id' => $usuarioApp->id,
    //                 'empleado_id' => $usuarioApp->empleado_id,
    //                 'is_active' => (bool) $usuarioApp->is_active,
    //             ],
    //         ]);

    // }
public function login(Request $request)
{
    $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Credenciales inválidas.'],
        ]);
    }

    $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();

    if (!$usuarioApp) {
        return response()->json([
            'ok' => false,
            'message' => 'Este usuario no está habilitado para la app.'
        ], 403);
    }

    if (!$usuarioApp->is_active) {
        return response()->json([
            'ok' => false,
            'message' => 'Tu acceso a la app está desactivado.'
        ], 403);
    }

    // Por ahora: solo residente
    if (!$user->hasRole('residente')) {
        return response()->json([
            'ok' => false,
            'message' => 'Tu rol no tiene acceso a la app.'
        ], 403);
    }

    // =========================
    // 1) Obtener la OBRA (única) asignada a este residente
    // =========================
    $empleadoId = $usuarioApp->empleado_id; // debe ser id_Empleado

    $asignacionResidente = ObraEmpleado::query()
        ->select('id', 'obra_id', 'empleado_id', 'rol_id')
        ->where('empleado_id', $empleadoId)
        ->where('activo', 1)
        ->whereNull('fecha_baja')
        ->latest('id')
        ->first();

    if (!$asignacionResidente) {
        return response()->json([
            'ok' => false,
            'message' => 'No tienes una obra activa asignada.'
        ], 403);
    }

    // Cargar la obra (cabecera)
    $obra = Obra::query()
        ->select('id', 'cliente_id', 'nombre', 'clave_obra', 'ubicacion', 'estatus_nuevo', 'fecha_inicio_programada', 'fecha_inicio_real')
        ->with(['cliente:id,nombre_comercial'])
        ->where('id', $asignacionResidente->obra_id)
        ->first();

    if (!$obra) {
        return response()->json([
            'ok' => false,
            'message' => 'La obra asignada no existe o fue eliminada.'
        ], 403);
    }


    // =========================
    // 2) Empleados de esa obra (máx 5)
    // =========================
    $empleadosObra = ObraEmpleado::query()
        ->with([
            'empleado:id_Empleado,Nombre,Apellidos,Telefono', // ajusta campos según tu tabla empleados
            'rol:id,rol_key,nombre' // ajusta según tu catalogo_roles
        ])
        ->where('obra_id', $obra->id)
        ->where('activo', 1)
        ->whereNull('fecha_baja')
        ->orderBy('id')
        ->limit(5)
        ->get()
        ->map(function ($oe) {
            return [
                'obra_empleado_id' => $oe->id,
                'empleado_id'      => $oe->empleado_id,
                'rol_id'           => $oe->rol_id,
                'rol'              => $oe->rol ? [
                    'id'      => $oe->rol->id,
                    'rol_key'  => $oe->rol->rol_key ?? null,
                    'nombre'  => $oe->rol->nombre ?? null,
                ] : null,
                'empleado'         => $oe->empleado ? [
                    'id_Empleado' => $oe->empleado->id_Empleado,
                    'nombre'      => trim(($oe->empleado->Nombre ?? '') . ' ' . ($oe->empleado->Apellidos ?? '') ),
                    'telefono'    => $oe->empleado->telefono ?? null,
                ] : null,
            ];
        });

    // =========================
    // 3) Máquina activa de la obra (siempre 1)
    // =========================
    $maquinaActiva = ObraMaquina::query()
        ->with(['maquina']) // ajusta campos si quieres select() en maquina
        ->where('obra_id', $obra->id)
        ->activas()
        ->latest('fecha_inicio')
        ->first();

    $vehiculoAsignado = VehiculoEmpleado::query()
        ->with('vehiculo')
        ->where('empleado_id', $empleadoId)
        ->whereNull('fecha_fin')
        ->latest('id')
        ->first();
   $pilasRaw = ObraPila::query()
    ->where('obra_id', $obra->id)
    ->orderBy('numero_pila') // si existe
    ->get();

    $pilasTotalProgramado = (int) $pilasRaw->sum('cantidad_programada');

    $pilas = $pilasRaw->map(function ($p) {
        return [
            'id' => (int) $p->id,
            'obra_id' => (int) $p->obra_id,
            'numero_pila' => $p->numero_pila ?? null,
            'tipo' => $p->tipo ?? null,
            'cantidad_programada' => $p->cantidad_programada !== null ? (int)$p->cantidad_programada : 0,

            // OJO: en tu DB el campo es diametro_proyecto / profundidad_proyecto
            'diametro' => $p->diametro_proyecto !== null ? (float)$p->diametro_proyecto : null,
            'profundidad' => $p->profundidad_proyecto !== null ? (float)$p->profundidad_proyecto : null,

            'ubicacion' => $p->ubicacion ?? null,
            'activo' => (bool) $p->activo,
        ];
    })->values();





    $maquinaPayload = null;
    if ($maquinaActiva) {
        $maquinaPayload = [
            'obra_maquina_id'  => $maquinaActiva->id,
            'maquina_id'       => $maquinaActiva->maquina_id,
            'fecha_inicio'     => optional($maquinaActiva->fecha_inicio)->toDateString(),
            'horometro_inicio' => $maquinaActiva->horometro_inicio,
            'estado'           => $maquinaActiva->estado,
            'maquina'          => $maquinaActiva->maquina ? [
                'id'     => $maquinaActiva->maquina->id ?? null,
                'nombre' => $maquinaActiva->maquina->nombre ?? null,
                // agrega campos que necesites (marca, modelo, etc.)
            ] : null,
        ];
    }

    // Token
    $tokenName = $request->header('X-Device-Name', 'mobile');
    $token = $user->createToken($tokenName)->plainTextToken;

    return response()->json([
        'ok' => true,
        'token' => $token,
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
        'contexto' => [
            'obra' => [
                'id' => $obra->id,
                'cliente_id' => $obra->cliente_id,
                'cliente_nombre' => $obra->cliente?->nombre_comercial,
                'nombre' => $obra->nombre,
                'clave_obra' => $obra->clave_obra,
                'ubicacion' => $obra->ubicacion,
                'estatus_nuevo' => $obra->estatus_nuevo,
                // 'pilas' => $pilas->cantidad_programada ?? null,
                // 'profundidad_proyecto' => $pilas->profundidad_proyecto ?? null,
                'fecha_inicio_programada' => optional($obra->fecha_inicio_programada)->toDateString(),
                'fecha_inicio_real'       => optional($obra->fecha_inicio_real)->toDateString(),
                'pilas_total_programado' => $pilasTotalProgramado,
            ],
            'empleados' => $empleadosObra,
            'maquina'   => $maquinaPayload,
            'pilas'     => $pilas,
            
           'vehiculo' => $vehiculoAsignado ? [
            'vehiculo_id' => $vehiculoAsignado->vehiculo_id,
            'fecha_asignacion' => $vehiculoAsignado->fecha_asignacion?->toDateString(),
            'fecha_fin' => $vehiculoAsignado->fecha_fin?->toDateString(),
            'notas' => $vehiculoAsignado->notas,
            'vehiculo' => $vehiculoAsignado->vehiculo ? [
                'id'     => $vehiculoAsignado->vehiculo->id,
                'marca'  => $vehiculoAsignado->vehiculo->marca ?? null,
                'modelo' => $vehiculoAsignado->vehiculo->modelo ?? null,
                'placas' => $vehiculoAsignado->vehiculo->placas ?? null,
                'anio'   => $vehiculoAsignado->vehiculo->anio ?? null,
                'color'  => $vehiculoAsignado->vehiculo->color ?? null,
                'tipo'   => $vehiculoAsignado->vehiculo->tipo ?? null,
                'estatus'=> $vehiculoAsignado->vehiculo->estatus ?? null,
            ] : null,
        ] : null,
        ],
    ]);
}
    // public function me(Request $request)
    //     {
    //         $user = $request->user();

    //         $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();

    //         return response()->json([
    //             'ok' => true,
    //             'user' => [
    //                 'id' => $user->id,
    //                 'email' => $user->email,
    //                 'name' => $user->name ?? null,
    //             ],
    //             'app' => $usuarioApp ? [
    //                 'user_app_id' => $usuarioApp->id,
    //                 'empleado_id' => $usuarioApp->empleado_id,
    //                 'is_active' => (bool) $usuarioApp->is_active,
    //             ] : null,
    //         ]);
    //     }
    public function me(Request $request)
{
    $user = $request->user();

    $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();

    // Si no hay UsuarioApp o está inactivo, responde 403 (consistente con login)
    if (!$usuarioApp) {
        return response()->json([
            'ok' => false,
            'message' => 'Este usuario no está habilitado para la app.',
        ], 403);
    }

    if (!$usuarioApp->is_active) {
        return response()->json([
            'ok' => false,
            'message' => 'Tu acceso a la app está desactivado.',
        ], 403);
    }

    // Opcional: permitir no cargar contexto
    $withContext = $request->boolean('with_context', true);

    $contexto = null;

    if ($withContext) {
        $empleadoId = $usuarioApp->empleado_id;

        $asignacionResidente = ObraEmpleado::query()
            ->select('id', 'obra_id', 'empleado_id', 'rol_id')
            ->where('empleado_id', $empleadoId)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->latest('id')
            ->first();

        if ($asignacionResidente) {
            $obra = Obra::query()
                ->select('id', 'cliente_id', 'nombre', 'clave_obra', 'ubicacion', 'estatus_nuevo', 'fecha_inicio_programada', 'fecha_inicio_real')
                ->where('id', $asignacionResidente->obra_id)
                ->first();

            if ($obra) {
                $empleadosObra = ObraEmpleado::query()
                    ->with([
                        'empleado:id_Empleado,Nombre,Apellidos,telefono',
                        'rol:id,rol_key,nombre'
                    ])
                    ->where('obra_id', $obra->id)
                    ->where('activo', 1)
                    ->whereNull('fecha_baja')
                    ->orderBy('id')
                    ->limit(5)
                    ->get()
                    ->map(function ($oe) {
                        return [
                            'obra_empleado_id' => $oe->id,
                            'empleado_id'      => $oe->empleado_id,
                            'rol_id'           => $oe->rol_id,
                            'rol'              => $oe->rol ? [
                                'id'      => $oe->rol->id,
                                'rol_key' => $oe->rol->rol_key ?? null,
                                'nombre'  => $oe->rol->nombre ?? null,
                            ] : null,
                            'empleado'         => $oe->empleado ? [
                                'id_Empleado' => $oe->empleado->id_Empleado,
                                'nombre'      => trim(($oe->empleado->Nombre ?? '') . ' ' . ($oe->empleado->Apellidos ?? '') ),
                                'telefono'    => $oe->empleado->telefono ?? null,
                            ] : null,
                        ];
                    });

                $maquinaActiva = ObraMaquina::query()
                    ->with(['maquina'])
                    ->where('obra_id', $obra->id)
                    ->activas()
                    ->latest('fecha_inicio')
                    ->first();

                $maquinaPayload = null;
                if ($maquinaActiva) {
                    $maquinaPayload = [
                        'obra_maquina_id'  => $maquinaActiva->id,
                        'maquina_id'       => $maquinaActiva->maquina_id,
                        'fecha_inicio'     => optional($maquinaActiva->fecha_inicio)->toDateString(),
                        'horometro_inicio' => $maquinaActiva->horometro_inicio,
                        'estado'           => $maquinaActiva->estado,
                        'maquina'          => $maquinaActiva->maquina ? [
                            'id'     => $maquinaActiva->maquina->id ?? null,
                            'nombre' => $maquinaActiva->maquina->nombre ?? null,
                        ] : null,
                    ];
                }

                $contexto = [
                    'obra' => [
                        'id' => $obra->id,
                        'cliente_id' => $obra->cliente_id,
                        'nombre' => $obra->nombre,
                        'clave_obra' => $obra->clave_obra,
                        'ubicacion' => $obra->ubicacion,
                        'estatus_nuevo' => $obra->estatus_nuevo,
                        'fecha_inicio_programada' => optional($obra->fecha_inicio_programada)->toDateString(),
                        'fecha_inicio_real'       => optional($obra->fecha_inicio_real)->toDateString(),
                    ],
                    'empleados' => $empleadosObra,
                    'maquina'   => $maquinaPayload,
                ];
            }
        }
    }

    return response()->json([
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
        'contexto' => $contexto,
    ]);
}


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }
}
