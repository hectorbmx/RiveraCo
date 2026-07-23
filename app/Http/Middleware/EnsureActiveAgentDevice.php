<?php

namespace App\Http\Middleware;

use App\Models\AgentDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAgentDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$user || !$token) {
            return response()->json([
                'ok' => false,
                'message' => 'Agente no autenticado.',
            ], 401);
        }

        $device = AgentDevice::where('user_id', $user->id)
            ->where('token_id', $token->id)
            ->first();

        if (!$device || $device->revoked_at !== null) {
            return response()->json([
                'ok' => false,
                'message' => 'Este equipo ya no esta autorizado para el agente local.',
            ], 403);
        }

        $device->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('agent_device', $device);

        return $next($request);
    }
}
