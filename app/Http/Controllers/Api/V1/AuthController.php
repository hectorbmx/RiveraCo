<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AppMobileContextService $contextService)
    {
    }

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
        $isResidente = $this->contextService->puedeVerPanelResidente($user);
        $isGerencial = $this->contextService->puedeVerGerencial($user);

        if (!$isResidente && !$isGerencial) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu rol no tiene acceso a la app.',
            ], 403);
        }

        $opciones = $this->contextService->opciones($user, $usuarioApp);
        $contexto = null;

        if ($isResidente) {
            $contexto = $this->contextService->contextoResidente($user, $usuarioApp);

            if (!$contexto && !$isGerencial) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes una obra activa asignada.',
                ], 403);
            }
        }

        $tokenName = $request->header('X-Device-Name', 'mobile');
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json($this->payloadSesion($user, $usuarioApp, $contexto, $token, $opciones));
    }

    public function me(Request $request)
    {
        $data = $request->validate([
            'obra_id' => ['nullable', 'integer'],
            'with_context' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $usuarioApp = $this->validarUsuarioApp($user);

        $isResidente = $this->contextService->puedeVerPanelResidente($user);
        $isGerencial = $this->contextService->puedeVerGerencial($user);

        if (!$isResidente && !$isGerencial) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu rol no tiene acceso a la app.',
            ], 403);
        }

        $withContext = $request->boolean('with_context', true);
        $obraId = $data['obra_id'] ?? null;
        $opciones = $this->contextService->opciones($user, $usuarioApp, $obraId);
        $contexto = null;

        if ($withContext && $isResidente) {
            $contexto = $this->contextService->contextoResidente($user, $usuarioApp, $obraId);

            if (!$contexto && !$isGerencial) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes una obra activa asignada.',
                ], 403);
            }
        }

        return response()->json($this->payloadSesion($user, $usuarioApp, $contexto, null, $opciones));
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

    private function payloadSesion(User $user, UsuarioApp $usuarioApp, ?array $contexto, ?string $token = null, ?array $opciones = null): array
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

        if ($opciones !== null) {
            $payload['paneles_disponibles'] = $opciones['paneles'] ?? [];
            $payload['obras_residente'] = $opciones['obras_residente'] ?? [];
            $payload['defaults'] = $opciones['defaults'] ?? [];
        }

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

}





