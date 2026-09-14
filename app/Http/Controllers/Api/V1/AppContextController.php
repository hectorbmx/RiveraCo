<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppContextController extends Controller
{
    public function __construct(private AppMobileContextService $contextService)
    {
    }

    public function opciones(Request $request)
    {
        $data = $request->validate([
            'obra_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $usuarioApp = $this->validarUsuarioApp($user->id);

        if (! $this->contextService->puedeVerPanelResidente($user) && ! $this->contextService->puedeVerGerencial($user)) {
            return response()->json([
                'ok' => false,
                'message' => 'Tu rol no tiene acceso a la app.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            ...$this->contextService->opciones($user, $usuarioApp, $data['obra_id'] ?? null),
        ]);
    }

    private function validarUsuarioApp(int $userId): UsuarioApp
    {
        $usuarioApp = UsuarioApp::where('user_id', $userId)->first();

        if (! $usuarioApp) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Este usuario no esta habilitado para la app.',
            ], 403));
        }

        if (! $usuarioApp->is_active) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Tu acceso a la app esta desactivado.',
            ], 403));
        }

        return $usuarioApp;
    }
}
