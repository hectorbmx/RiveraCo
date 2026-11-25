<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MailFollowupController extends Controller
{
    public function index(): View
    {
        $pilotoLocal = app()->environment('local') && config('services.mail_followup.pilot_enabled');
        $produccionHabilitada = app()->environment('production') && config('services.mail_followup.production_enabled');
        if (!$pilotoLocal && !$produccionHabilitada) {
            return view('seguimientos.index');
        }

        if ($produccionHabilitada) {
            $this->authorize('seguimientos.view.access');
        }

        $mailbox = (string) config('services.mail_followup.mailbox');
        $threads = DB::table('mail_followups')->where('mailbox', $mailbox)
            ->orderByDesc('last_activity_at')->get();
        $events = DB::table('mail_followup_events')->whereIn('mail_followup_id', $threads->pluck('id'))
            ->orderBy('occurred_at')->orderBy('id')->get()->groupBy('mail_followup_id');

        $hilosPiloto = $threads->map(fn ($thread) => [
            'id' => $thread->id,
            'cliente' => $thread->client_name,
            'asunto' => $thread->subject,
            'estado' => $thread->status,
            'responsable' => null,
            'actividad' => $this->localDate($thread->last_activity_at),
            'clasificacion' => $thread->classification_reason,
            'evidencia' => $thread->classification_evidence,
            'fuente' => match ($thread->classification_source) {
                'openai' => 'OpenAI',
                'ia_pendiente' => 'Revisión manual',
                default => 'Reglas locales',
            },
            'historial' => $events->get($thread->id, collect())->map(fn ($event) => [
                'tipo' => $event->automatic ? 'Mensaje automático'
                    : ($event->direction === 'sent' ? 'Respuesta enviada' : 'Correo recibido'),
                'autor' => $event->author ?? 'Autor por confirmar',
                'fecha' => $this->localDate($event->occurred_at),
                'asunto' => $event->subject,
                'nota' => $event->excerpt,
                'enlace' => $event->web_link,
            ]),
        ]);

        $empleadosResponsables = Empleado::query()->where('Estatus', 1)
            ->orderBy('Nombre')->orderBy('Apellidos')->orderBy('id_Empleado')
            ->get(['id_Empleado', 'Nombre', 'Apellidos', 'Puesto'])
            ->map(fn (Empleado $empleado) => [
                'id' => $empleado->getKey(),
                'nombre' => trim($empleado->Nombre . ' ' . $empleado->Apellidos),
                'puesto' => trim((string) $empleado->Puesto),
            ])
            ->filter(fn (array $empleado) => $empleado['nombre'] !== '')->values();

        return view('seguimientos.index', compact('hilosPiloto', 'mailbox', 'empleadosResponsables', 'pilotoLocal'));
    }

    private function localDate(string $date): string
    {
        return CarbonImmutable::parse($date, 'UTC')->setTimezone(config('app.timezone'))
            ->locale('es')->translatedFormat('j M Y, H:i');
    }
}
