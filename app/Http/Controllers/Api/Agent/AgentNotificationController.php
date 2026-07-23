<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentOpenLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentNotificationController extends Controller
{
    /**
     * Obtener notificaciones no leidas del usuario.
     */
    public function unread(Request $request)
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? $data['titulo'] ?? 'SIRICO',
                    'message' => $data['message'] ?? $data['mensaje'] ?? 'Tienes una nueva notificacion.',
                    'url' => $data['url'] ?? null,
                    'icon' => $data['icon'] ?? 'info',
                    'priority' => $data['priority'] ?? 'normal',
                    'created_at' => $notification->created_at?->toDateTimeString(),
                ];
            });

        return response()->json([
            'ok' => true,
            'count' => $notifications->count(),
            'notifications' => $notifications,
        ]);
    }

    public function openLink(Request $request, string $id)
    {
        $user = $request->user();
        $agentDevice = $request->attributes->get('agent_device');

        if (!$agentDevice) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro el equipo activo del agente.',
            ], 403);
        }

        if (!$agentDevice->open_notifications_in_browser || $agentDevice->notification_click_behavior === 'disabled') {
            return response()->json([
                'ok' => false,
                'message' => 'La apertura de notificaciones en navegador esta desactivada para este equipo.',
            ], 403);
        }

        $notification = $user->notifications()->findOrFail($id);
        $targetUrl = $notification->data['url'] ?? null;

        if (!$targetUrl) {
            return response()->json([
                'ok' => false,
                'message' => 'Esta notificacion no tiene una pagina asociada.',
            ], 422);
        }

        if ($agentDevice->notification_click_behavior === 'mark_read_only') {
            $notification->markAsRead();

            return response()->json([
                'ok' => true,
                'action' => 'marked_read',
                'id' => $notification->id,
            ]);
        }

        $plainToken = Str::random(64);
        $expiresAt = now()->addMinutes(2);

        AgentOpenLink::create([
            'user_id' => $user->id,
            'agent_device_id' => $agentDevice->id,
            'notification_id' => $notification->id,
            'token_hash' => hash('sha256', $plainToken),
            'target_url' => $targetUrl,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'ok' => true,
            'action' => 'open_url',
            'open_url' => url('/agent/open/' . $plainToken),
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /**
     * Marcar una notificacion especifica como leida.
     */
    public function markRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'ok' => true,
            'id' => $notification->id,
        ]);
    }
}
