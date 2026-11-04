<?php

namespace App\Services\Comisiones;

use App\Models\Comision;
use App\Models\Obra;
use App\Models\User;
use App\Models\UsuarioApp;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;

/** Entrada movil: conserva el contrato y la autorizacion de la app. */
class ResidenteComisionesService
{
    public function __construct(
        private AppMobileContextService $contextService,
        private ComisionEtapasService $etapas,
    ) {
    }

    public function indexForUser(User $user, ?int $obraId = null): array
    {
        return $this->etapas->index($this->obraActiva($user, $obraId));
    }

    public function showForUser(User $user, Comision $comision, ?int $obraId = null): array
    {
        return $this->etapas->show($this->obraActiva($user, $obraId), $comision);
    }

    public function createForUser(User $user, array $data, ?int $obraId = null): array
    {
        $obra = $this->obraActiva($user, $obraId);

        return $this->etapas->create($user, $obra, $data, $this->empleadoIdApp($user));
    }

    public function updateEtapaForUser(User $user, Comision $comision, string $etapaKey, array $data, ?int $obraId = null): array
    {
        return $this->etapas->updateEtapa($user, $this->obraActiva($user, $obraId), $comision, $etapaKey, $data);
    }

    public function storeFotoForUser(User $user, Comision $comision, string $etapaKey, UploadedFile $foto, ?string $comentario = null, ?int $obraId = null): array
    {
        return $this->etapas->storeFoto($user, $this->obraActiva($user, $obraId), $comision, $etapaKey, $foto, $comentario);
    }

    private function obraActiva(User $user, ?int $obraId = null): Obra
    {
        $usuarioApp = UsuarioApp::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $usuarioApp) {
            throw new AuthorizationException('Este usuario no esta habilitado para la app.');
        }

        $contexto = $this->contextService->contextoResidente($user, $usuarioApp, $obraId);
        $contextoObraId = $contexto['obra']['id'] ?? null;

        if (! $contextoObraId) {
            throw new AuthorizationException('No tienes una obra activa asignada.');
        }

        $obra = Obra::query()
            ->with('cliente:id,nombre_comercial')
            ->find($contextoObraId);

        if (! $obra) {
            throw new AuthorizationException('No tienes una obra activa asignada.');
        }

        return $obra;
    }

    private function empleadoIdApp(User $user): ?int
    {
        return UsuarioApp::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->value('empleado_id');
    }

}
