<?php

namespace App\Http\Middleware;

use App\Services\Mobile\AppMobileAccessService;
use App\Services\Mobile\AppMobileContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAppAccess
{
    public function __construct(private AppMobileAccessService $access, private AppMobileContextService $context)
    {
    }

    public function handle(Request $request, Closure $next, string $panel = 'legacy'): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $this->access->usuarioActivo($user);
        $permitido = match ($panel) {
            'legacy' => $this->context->puedeVerPanelResidente($user) || $this->context->puedeVerGerencial($user),
            'gerencial' => $this->context->puedeVerGerencial($user),
            'mecanico' => $this->context->puedeVerPanelMecanico($user),
            default => false,
        };

        if (! $permitido) {
            return response()->json(['ok' => false, 'message' => 'No tienes acceso a esta sección de la app.'], 403);
        }

        return $next($request);
    }
}
