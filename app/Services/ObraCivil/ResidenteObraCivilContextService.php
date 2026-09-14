<?php

namespace App\Services\ObraCivil;

use App\Models\Obra;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResidenteObraCivilContextService
{
    private const TIPOS_OBRA_CIVIL = [
        'OBRA_CIVIL',
        'CIVIL',
        'obra_civil',
        'civil',
    ];

    public function __construct(private AppMobileContextService $contextService)
    {
    }

    public function resolve(User $user, ?int $obraId = null): ResidenteObraCivilContext
    {
        if (! $this->contextService->puedeVerPanelResidente($user)) {
            $this->deny('Solo el perfil residente puede usar este modulo.', 403);
        }

        $usuarioApp = UsuarioApp::where('user_id', $user->id)->where('is_active', true)->first();

        if (! $usuarioApp) {
            $this->deny('Este usuario no esta habilitado para la app.', 403);
        }

        $contexto = $this->contextService->contextoResidente($user, $usuarioApp, $obraId);
        $contextoObraId = $contexto['obra']['id'] ?? null;

        if (! $contextoObraId) {
            $this->deny('No tienes una obra activa asignada.', 403);
        }

        $obra = Obra::query()
            ->with(['cliente:id,nombre_comercial'])
            ->find($contextoObraId);

        if (! $obra) {
            $this->deny('No tienes una obra activa asignada.', 403);
        }

        if (! $this->isObraCivil($obra)) {
            $this->deny('La obra activa no es una obra civil.', 403);
        }

        return new ResidenteObraCivilContext($user, $usuarioApp, $obra);
    }

    private function isObraCivil(Obra $obra): bool
    {
        return in_array((string) $obra->tipo_obra, self::TIPOS_OBRA_CIVIL, true);
    }

    private function deny(string $message, int $status): void
    {
        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => $message,
        ], $status));
    }
}
