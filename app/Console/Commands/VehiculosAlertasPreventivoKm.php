<?php

namespace App\Console\Commands;

use App\Models\EmpresaAlertaDestinatario;
use App\Models\EmpresaConfig;
use App\Models\Vehiculo;
use App\Models\VehiculoAlertaLog;
use App\Notifications\VehiculoServicioPreventivoNotification;
use App\Services\Mail\MicrosoftGraphMailService;
use App\Services\Vehiculos\PreventivoVehiculoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

class VehiculosAlertasPreventivoKm extends Command
{
    protected $signature = 'vehiculos:alertas-preventivo-km {--dry-run : Calcula alertas sin enviar ni registrar bitacora} {--force : Ignora deduplicacion y vuelve a enviar}';

    protected $description = 'Envia alertas de servicio preventivo de vehiculos por KM usando correo y notificaciones internas.';

    public function handle(PreventivoVehiculoService $preventivoService, MicrosoftGraphMailService $graphMail): int
    {
        $config = EmpresaConfig::first();

        if (! $config || ! (bool) ($config->vehiculo_alertas_activas ?? true)) {
            $this->info('Alertas de vehiculos desactivadas o sin configuracion.');
            return self::SUCCESS;
        }

        $vehiculos = Vehiculo::query()
            ->where('estatus', 'activo')
            ->orderBy('id')
            ->get();

        if ($vehiculos->isEmpty()) {
            $this->info('No hay vehiculos activos para evaluar.');
            return self::SUCCESS;
        }

        $destinatarios = EmpresaAlertaDestinatario::query()
            ->with('user')
            ->where('empresa_config_id', $config->id)
            ->modulo('vehiculos')
            ->where('activo', true)
            ->get();

        $emails = $destinatarios
            ->where('notificar_correo', true)
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->unique()
            ->values()
            ->all();

        $usuarios = $destinatarios
            ->where('notificar_sistema', true)
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        if ($emails === [] && $usuarios->isEmpty()) {
            $this->warn('No hay destinatarios activos configurados para alertas de vehiculos.');

            if (! $this->option('dry-run')) {
                return self::SUCCESS;
            }
        }

        $preventivos = $preventivoService->calcularParaColeccion($vehiculos, $config);
        $evaluados = 0;
        $enviados = 0;
        $omitidos = 0;

        foreach ($vehiculos as $vehiculo) {
            $preventivo = $preventivos[$vehiculo->id] ?? null;
            $estado = $preventivo['estado'] ?? 'sin_datos';

            if (! in_array($estado, ['proximo', 'vencido'], true)) {
                continue;
            }

            $evaluados++;
            $hash = $this->hashContexto($vehiculo, $preventivo);
            $tipoAlerta = 'servicio_preventivo_km';

            $yaEnviado = VehiculoAlertaLog::query()
                ->where('vehiculo_id', $vehiculo->id)
                ->where('tipo_alerta', $tipoAlerta)
                ->where('hash_contexto', $hash)
                ->exists();

            if ($yaEnviado && ! $this->option('force')) {
                $omitidos++;
                $this->line("Omitido vehiculo {$vehiculo->id}: alerta ya enviada para este contexto.");
                continue;
            }

            $this->line("Alerta {$estado}: {$this->nombreVehiculo($vehiculo)} - {$preventivo['label']}");

            if ($this->option('dry-run')) {
                continue;
            }

            $notificacion = new VehiculoServicioPreventivoNotification($vehiculo, $preventivo);

            try {
                if ($usuarios->isNotEmpty()) {
                    Notification::send($usuarios, $notificacion);
                }

                if ($emails !== []) {
                    $this->enviarCorreo($graphMail, $vehiculo, $preventivo, $emails);
                }

                VehiculoAlertaLog::updateOrCreate(
                    [
                        'vehiculo_id' => $vehiculo->id,
                        'tipo_alerta' => $tipoAlerta,
                        'hash_contexto' => $hash,
                    ],
                    [
                        'estado' => $estado,
                        'km_actual' => $preventivo['km_actual'] ?? null,
                        'km_proximo_servicio' => $preventivo['km_proximo_servicio'] ?? null,
                        'km_restantes' => $preventivo['km_restantes'] ?? null,
                        'correos_enviados' => count($emails),
                        'notificaciones_enviadas' => $usuarios->count(),
                        'sent_at' => now(),
                    ]
                );

                $enviados++;
            } catch (Throwable $e) {
                $this->error("Error enviando alerta de vehiculo {$vehiculo->id}: {$e->getMessage()}");
            }
        }

        $this->info("Vehiculos con alerta evaluados: {$evaluados}. Enviados: {$enviados}. Omitidos por deduplicacion: {$omitidos}.");

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $preventivo
     * @param array<int, string> $emails
     */
    private function enviarCorreo(MicrosoftGraphMailService $graphMail, Vehiculo $vehiculo, array $preventivo, array $emails): void
    {
        $subject = 'Alerta de servicio preventivo: ' . $this->nombreVehiculo($vehiculo);
        $html = $this->htmlCorreo($vehiculo, $preventivo);

        if (strtolower((string) config('services.alertas_mail.provider')) === 'graph') {
            $graphMail->sendHtml(
                $subject,
                $html,
                $emails,
                (string) config('services.alertas_mail.microsoft_graph.user')
            );

            return;
        }

        Mail::html($html, function ($message) use ($emails, $subject) {
            $message
                ->from(
                    config('services.alertas_mail.from_address', config('mail.from.address')),
                    config('services.alertas_mail.from_name', config('mail.from.name'))
                )
                ->to($emails)
                ->subject($subject);
        });
    }

    /**
     * @param array<string, mixed> $preventivo
     */
    private function htmlCorreo(Vehiculo $vehiculo, array $preventivo): string
    {
        $url = route('mantenimiento.vehiculos.edit', [
            'vehiculo' => $vehiculo->id,
            'tab' => 'mantenimientos',
        ]);

        $rows = [
            'Vehiculo' => $this->nombreVehiculo($vehiculo),
            'Estado' => $preventivo['label'] ?? 'Servicio preventivo',
            'KM actual' => $this->formatKm($preventivo['km_actual'] ?? null),
            'Proximo servicio' => $this->formatKm($preventivo['km_proximo_servicio'] ?? null),
            'KM restantes' => $this->formatKm($preventivo['km_restantes'] ?? null),
        ];

        $trs = collect($rows)->map(function ($value, $label) {
            return '<tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">'
                . e($label)
                . '</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;color:#0f172a;">'
                . e($value)
                . '</td></tr>';
        })->implode('');

        return '<div style="font-family:Arial,sans-serif;color:#0f172a;line-height:1.45;">'
            . '<h2 style="margin:0 0 12px;">Alerta de servicio preventivo</h2>'
            . '<p style="margin:0 0 16px;color:#475569;">Un vehiculo esta proximo o vencido para servicio preventivo.</p>'
            . '<table style="border-collapse:collapse;width:100%;max-width:620px;border:1px solid #e5e7eb;">'
            . $trs
            . '</table>'
            . '<p style="margin:20px 0;"><a href="' . e($url) . '" style="background:#0B265A;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;">Ver vehiculo</a></p>'
            . '</div>';
    }

    /**
     * @param array<string, mixed> $preventivo
     */
    private function hashContexto(Vehiculo $vehiculo, array $preventivo): string
    {
        return hash('sha256', implode('|', [
            $vehiculo->id,
            $preventivo['estado'] ?? '',
            $preventivo['km_actual'] ?? '',
            $preventivo['km_proximo_servicio'] ?? '',
            $preventivo['km_restantes'] ?? '',
            $preventivo['intervalo_km'] ?? '',
        ]));
    }

    private function nombreVehiculo(Vehiculo $vehiculo): string
    {
        return trim("{$vehiculo->marca} {$vehiculo->modelo} {$vehiculo->placas}") ?: "Vehiculo #{$vehiculo->id}";
    }

    private function formatKm(mixed $km): string
    {
        return $km === null ? '-' : number_format((float) $km) . ' km';
    }
}
