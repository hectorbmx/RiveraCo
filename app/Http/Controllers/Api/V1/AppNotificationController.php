<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Seguro;
use App\Models\Maquina;
use App\Models\Vehiculo;
use App\Services\Mobile\AppMobileAccessService;
use App\Services\Mobile\AppMobileContextService;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    public function __construct(private AppMobileAccessService $access, private AppMobileContextService $context)
    {
    }

    private function autorizar(Request $request): void
    {
        $user = $request->user();
        $this->access->usuarioActivo($user);
        abort_unless($this->context->puedeVerPanelMecanico($user)
            || $this->context->puedeVerGerencial($user)
            || $this->context->puedeVerPanelResidente($user), 403);
    }

    public function index(Request $request)
    {
        $this->autorizar($request);
        $datos = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $rows = $request->user()->notifications()->reorder()
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate((int) ($datos['per_page'] ?? 20), ['*'], 'page', (int) ($datos['page'] ?? 1));

        $seguroIds = $rows->getCollection()->map(fn ($row) => $row->data)
            ->filter(fn ($data) => ($data['tipo'] ?? null) === 'vencimiento_seguro' && ! empty($data['id']))
            ->pluck('id')->filter(fn ($id) => is_numeric($id) && (int) $id > 0)->unique();
        $seguros = Seguro::query()->with('asegurable')->whereIn('id', $seguroIds)->get()->keyBy('id');

        return response()->json([
            'ok' => true,
            'data' => $rows->getCollection()->map(function ($notification) use ($seguros) {
                $data = $notification->data;
                $titulo = $data['title'] ?? $data['titulo'] ?? 'SIRICO';
                $mensaje = $data['message'] ?? $data['mensaje'] ?? 'Tienes una nueva notificación.';
                $tipo = $data['tipo'] ?? null;
                $maquinaId = $tipo === 'maquina_servicio_preventivo' ? ($data['maquina_id'] ?? null) : null;
                $vehiculoId = $tipo === 'vehiculo_servicio_preventivo' ? ($data['vehiculo_id'] ?? null) : null;
                if ($tipo === 'vencimiento_seguro') {
                    $asegurable = $seguros->get($data['id'] ?? null)?->asegurable;
                    $maquinaId = $asegurable instanceof Maquina ? $asegurable->id : null;
                    $vehiculoId = $asegurable instanceof Vehiculo ? $asegurable->id : null;
                }

                return [
                    'id' => $notification->id,
                    'titulo' => is_string($titulo) ? $titulo : 'SIRICO',
                    'mensaje' => is_string($mensaje) ? $mensaje : 'Tienes una nueva notificación.',
                    'creada' => $notification->created_at?->toIso8601String(),
                    'leida' => $notification->read_at?->toIso8601String(),
                    'destino' => is_numeric($maquinaId) && (int) $maquinaId > 0
                        ? ['tipo' => 'maquina', 'id' => (int) $maquinaId]
                        : (is_numeric($vehiculoId) && (int) $vehiculoId > 0
                            ? ['tipo' => 'vehiculo', 'id' => (int) $vehiculoId] : null),
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(),
                'total' => $rows->total(), 'sin_leer' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function leer(Request $request, string $id)
    {
        $this->autorizar($request);
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['ok' => true, 'id' => $notification->id]);
    }
}
