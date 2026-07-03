@extends('layouts.admin')

@section('title', 'Notificaciones')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Centro de Notificaciones</h1>
        
        @if(auth()->user()->unreadNotifications->count() > 0)
            <form action="{{ route('notifications.markAllRead') }}" method="POST">
                @csrf
                <button type="submit" class="text-sm bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg transition-colors font-medium">
                    Marcar todas como leídas
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                <div class="p-6 transition-colors {{ $notification->unread() ? 'bg-blue-50/30' : '' }}">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-2xl {{ $notification->unread() ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center flex-shrink-0 text-xl">
                            @switch($notification->data['tipo'] ?? '')
                                @case('factura_borrador') BF @break
                                @case('factura_borrador_autorizado') OK @break
                                @case('factura_borrador_listo_facturar') FAC @break
                                @case('factura_borrador_rechazado') NO @break
                                @case('solicitud_gasto') 💰 @break
                                @case('orden_compra') 🛒 @break
                                @case('vencimiento_seguro') 🛡️ @break
                                @default 🔔
                            @endswitch
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-semibold text-slate-900">
                                    {{ $notification->data['mensaje'] ?? 'Nueva notificación' }}
                                </h3>
                                <span class="text-xs text-slate-400 whitespace-nowrap ml-4">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <div class="text-sm text-slate-500 mb-4">
                                @if(($notification->data['tipo'] ?? '') === 'solicitud_gasto')
                                    Obra: <span class="font-medium text-slate-700">{{ $notification->data['obra_nombre'] }}</span> | 
                                    Semana: <span class="font-medium text-slate-700">{{ $notification->data['semana'] }}</span> | 
                                    Total: <span class="font-medium text-slate-700">${{ number_format($notification->data['total'], 2) }}</span>
                                @elseif(($notification->data['tipo'] ?? '') === 'orden_compra')
                                    Folio: <span class="font-medium text-slate-700">{{ $notification->data['folio'] }}</span> | 
                                    Obra: <span class="font-medium text-slate-700">{{ $notification->data['obra_nombre'] }}</span> | 
                                    Total: <span class="font-medium text-slate-700">${{ number_format($notification->data['total'], 2) }}</span>
                                    @if(($notification->data['evento'] ?? '') === 'pago_programado')
                                        | Fecha pago: <span class="font-medium text-slate-700">{{ $notification->data['fecha_programada_formatted'] ?? $notification->data['fecha_programada'] ?? 'N/A' }}</span>
                                        | Autorizo: <span class="font-medium text-slate-700">{{ $notification->data['autorizado_por_name'] ?? 'N/A' }}</span>
                                        | Programo: <span class="font-medium text-slate-700">{{ $notification->data['programado_por_name'] ?? 'N/A' }}</span>
                                    @endif
                                @elseif(($notification->data['tipo'] ?? '') === 'vencimiento_seguro')
                                    Vence el: <span class="font-medium text-slate-700">{{ $notification->data['vence'] }}</span> | 
                                    {{ $notification->data['asegurable_nombre'] }}
                                @elseif(($notification->data['tipo'] ?? '') === 'factura_borrador')
                                    Obra: <span class="font-medium text-slate-700">{{ $notification->data['obra_nombre'] }}</span> | 
                                    Cliente: <span class="font-medium text-slate-700">{{ $notification->data['cliente'] }}</span> | 
                                    Total: <span class="font-medium text-slate-700">${{ number_format($notification->data['total'], 2) }}</span>
                                @elseif(($notification->data['tipo'] ?? '') === 'factura_borrador_autorizado')
                                    Obra: <span class="font-medium text-slate-700">{{ $notification->data['obra_nombre'] }}</span> | 
                                    Total: <span class="font-medium text-slate-700">${{ number_format($notification->data['total'], 2) }}</span> | 
                                    Autorizo: <span class="font-medium text-slate-700">{{ $notification->data['autorizado_por_name'] }}</span>
                                @elseif(($notification->data['tipo'] ?? '') === 'factura_borrador_listo_facturar')
                                    Obra: <span class="font-medium text-slate-700">{{ $notification->data['obra_nombre'] }}</span> | 
                                    Cliente: <span class="font-medium text-slate-700">{{ $notification->data['cliente'] }}</span> | 
                                    Total: <span class="font-medium text-slate-700">${{ number_format($notification->data['total'], 2) }}</span> | 
                                    Autorizo: <span class="font-medium text-slate-700">{{ $notification->data['autorizado_por_name'] }}</span>
                                @elseif(($notification->data['tipo'] ?? '') === 'factura_borrador_rechazado')
                                    Obra: <span class="font-medium text-slate-700">{{ $notification->data['obra_nombre'] }}</span> | 
                                    Total: <span class="font-medium text-slate-700">${{ number_format($notification->data['total'], 2) }}</span> | 
                                    Rechazo: <span class="font-medium text-slate-700">{{ $notification->data['rechazado_por_name'] }}</span>
                                    @if(!empty($notification->data['observaciones_revision']))
                                        <div class="mt-1 text-slate-500">{{ $notification->data['observaciones_revision'] }}</div>
                                    @endif
                                @endif
                            </div>

                            <div class="flex gap-3">
                                @if(isset($notification->data['url']))
                                    <a href="{{ route('notifications.read', $notification->id) }}" class="text-sm bg-[#0B265A] text-white px-4 py-1.5 rounded-lg hover:bg-[#0B265A]/90 transition-colors">
                                        Ver detalle
                                    </a>
                                @endif

                                @if($notification->unread())
                                    <form action="{{ route('notifications.read', $notification->id) }}" method="GET" class="inline">
                                        <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 px-4 py-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                                            Marcar como leída
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="w-20 h-20 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mx-auto mb-4 text-4xl">
                        📭
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">Sin notificaciones</h3>
                    <p class="text-slate-500">Te avisaremos cuando haya algo nuevo que requiera tu atención.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
