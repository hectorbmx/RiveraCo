<?php

namespace App\Console\Commands;

use App\Services\Mail\MicrosoftGraphFollowupClient;
use Illuminate\Console\Command;
use RuntimeException;

class MailFollowupTestConnection extends Command
{
    protected $signature = 'mail-followup:test-connection';

    protected $description = 'Comprueba la lectura de Recibidos y Enviados del buzón comercial con Microsoft Graph';

    public function handle(MicrosoftGraphFollowupClient $client): int
    {
        $mailbox = (string) config('services.mail_followup.mailbox');

        if (!filter_var($mailbox, FILTER_VALIDATE_EMAIL)) {
            $this->error('Configura MAIL_FOLLOWUP_MAILBOX con el correo del buzón de Exchange Online.');

            return self::FAILURE;
        }

        try {
            $token = $client->accessToken();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Autenticación correcta. Buzón: {$mailbox}");
        $failed = false;

        foreach (['inbox' => 'Recibidos', 'sentitems' => 'Enviados'] as $folder => $label) {
            try {
                $hasMessages = $client->folderHasMessages($mailbox, $folder, $token);
            } catch (RuntimeException $e) {
                $this->error("{$label}: " . $e->getMessage());
                $failed = true;

                continue;
            }

            $this->info($label . ($hasMessages ? ': acceso correcto, hay mensajes.' : ': acceso correcto, carpeta vacía.'));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
