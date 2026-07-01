<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentNotificationController extends Controller
{
    /**
     * Obtener notificaciones no leídas del usuario.
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
                    'message' => $data['message'] ?? $data['mensaje'] ?? 'Tienes una nueva notificación.',
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

    /**
     * Marcar una notificación específica como leída.
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
