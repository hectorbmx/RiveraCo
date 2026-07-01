<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Listado de notificaciones del usuario
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Marcar una notificación como leída
     */
    public function read($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Si la notificación tiene una URL, redirigir
        if (isset($notification->data['url'])) {
            return redirect($notification->data['url']);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    /**
     * Marcar todas como leídas
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }
public function unreadJson()
{
    $notifications = Auth::user()
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
public function markReadJson($id)
{
    $notification = Auth::user()->notifications()->findOrFail($id);
    $notification->markAsRead();

    return response()->json([
        'ok' => true,
        'id' => $notification->id,
    ]);
}
}
