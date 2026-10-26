<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Http\Request;
use App\Services\Mobile\AppMobileAccessService;

class AppContextController extends Controller
{
    public function __construct(private AppMobileContextService $contextService, private AppMobileAccessService $accessService)
    {
    }

    public function opciones(Request $request)
    {
        $data = $request->validate([
            'obra_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $usuarioApp = $this->accessService->usuarioActivo($user);

        if (! $this->contextService->puedeVerPanelResidente($user)
            && ! $this->contextService->puedeVerGerencial($user)
            && ! $this->contextService->puedeVerPanelMecanico($user)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes acceso a la app.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            ...$this->contextService->opciones($user, $usuarioApp, $data['obra_id'] ?? null),
        ]);
    }

}
