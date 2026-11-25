<?php

namespace App\Console\Commands;

use App\Services\Mail\MailFollowupPilot;
use App\Services\Mail\MicrosoftGraphFollowupClient;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class MailFollowupPilotSync extends Command
{
    protected $signature = 'mail-followup:pilot-sync
        {--limite=10 : Mensajes recientes por carpeta, de 1 a 20}
        {--guardar : Clasificar y guardar la muestra en la bitácora local}';

    protected $description = 'Lee una muestra acotada de Recibidos y Enviados para el piloto de Seguimientos';

    public function handle(MicrosoftGraphFollowupClient $graph, MailFollowupPilot $pilot): int
    {
        $limit = filter_var($this->option('limite'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
        $mailbox = (string) config('services.mail_followup.mailbox');
        $save = $this->option('guardar');

        if ($limit === false || !filter_var($mailbox, FILTER_VALIDATE_EMAIL)) {
            $this->error('Indica un límite de 1 a 20 y configura un buzón válido.');

            return self::FAILURE;
        }

        if (!app()->environment('local') || ($save && !config('services.mail_followup.pilot_enabled'))) {
            $this->error('La prueba es local. Para guardar, activa MAIL_FOLLOWUP_PILOT_ENABLED y aplica la migración.');

            return self::FAILURE;
        }

        $lock = Cache::lock('mail-followup-pilot:' . strtolower($mailbox), 900);
        if (!$lock->get()) {
            $this->error('Ya se está ejecutando la sincronización del buzón.');

            return self::FAILURE;
        }

        try {
            $token = $graph->accessToken();
            $inbox = $graph->recentMessages($mailbox, 'inbox', $token, $limit);
            $sent = $graph->recentMessages($mailbox, 'sentitems', $token, $limit);
            $this->info('Muestra leída: ' . count($inbox) . ' recibidos y ' . count($sent) . ' enviados.');

            if (!$save) {
                return self::SUCCESS;
            }

            $counts = $pilot->import($mailbox, $inbox, $sent);
        } catch (QueryException) {
            $this->error('Falló la escritura del piloto. Comprueba la migración y la conexión local. Puedes reintentar la muestra.');

            return self::FAILURE;
        } catch (RuntimeException $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }

        $this->line('Clasificación: ' . (config('services.mail_followup.openai.enabled') ? 'reglas y OpenAI' : 'reglas locales'));
        foreach ($counts as $label => $count) {
            $this->line("{$label}: {$count}");
        }

        return self::SUCCESS;
    }
}
