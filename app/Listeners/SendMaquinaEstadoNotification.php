<?php

namespace App\Listeners;

use App\Events\MaquinaEstadoCambiado;
use App\Mail\MaquinaEstadoMail; // IMPORTANTE
use Illuminate\Contracts\Queue\ShouldQueue; // IMPORTANTE
use Illuminate\Support\Facades\Mail;

use Illuminate\Queue\InteractsWithQueue;


class SendMaquinaEstadoNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
//   public function handle(MaquinaEstadoCambiado $event): void
// {
//     // Obtiene la lista del .env, si no hay nada, usa un correo de soporte
//     $destinatarios = explode(',', env('NOTIFICACIONES_DESTINO', 'soporte@tuempresa.com'));

//     Mail::to($destinatarios)->send(
//         new MaquinaEstadoMail(
//             $event->maquina,
//             $event->anterior,
//             $event->nuevo,
//             $event->motivo,
//             $event->notas
//         )
//     );
// }
public function handle(MaquinaEstadoCambiado $event): void
{
    // Usamos config() en lugar de env()
    $emailsStr = config('mail.notificaciones_maquinas');
    
    // Limpiamos espacios por si acaso y convertimos en array
    $destinatarios = explode(',', str_replace(' ', '', $emailsStr));

    Mail::to($destinatarios)->send(
        new MaquinaEstadoMail(
            $event->maquina,
            $event->anterior,
            $event->nuevo,
            $event->motivo,
            $event->notas
        )
    );
}
}
