<?php

namespace App\Console\Commands;

use App\Exceptions\Telephony\GrandstreamApiException;
use App\Services\Telephony\GrandstreamClient;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class GrandstreamTestCall extends Command
{
    protected $signature = 'grandstream:test-call
        {--caller= : Extension interna que originara la llamada}
        {--outbound= : Numero externo destino}
        {--dry-run : Solo muestra el payload sin ejecutar la llamada}';

    protected $description = 'Prueba dialOutbound de Grandstream UCM para click-to-call';

    public function handle(GrandstreamClient $client): int
    {
        $caller = trim((string) $this->option('caller'));
        $outbound = trim((string) $this->option('outbound'));
        $dryRun = (bool) $this->option('dry-run');

        if ($caller === '' || $outbound === '') {
            $this->error('Debes indicar --caller=EXTENSION y --outbound=NUMERO.');
            return self::FAILURE;
        }

        $payload = [
            'request' => [
                'action' => config('grandstream.actions.dial_outbound', 'dialOutbound'),
                'cookie' => '(se obtiene despues de login)',
                'caller' => $caller,
                'outbound' => $outbound,
            ],
        ];

        $this->info('== Grandstream test call ==');
        $this->line('Caller extension: ' . $caller);
        $this->line('Outbound number: ' . $outbound);
        $this->line('Modo: ' . ($dryRun ? 'dry-run' : 'llamada real'));
        $this->newLine();

        if ($dryRun) {
            $this->line('Payload que se enviaria:');
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->newLine();
            $this->info('Dry-run completado. No se ejecuto llamada.');

            return self::SUCCESS;
        }

        if (!$this->confirm('Esto puede hacer sonar la extension y marcar el numero externo. Deseas continuar?', false)) {
            $this->warn('Prueba cancelada.');
            return self::FAILURE;
        }

        try {
            $response = $client->dialOutbound($caller, $outbound);

            $this->info('dialOutbound enviado correctamente.');
            $this->line('Status: ' . Arr::get($response, 'status'));

            $needApply = Arr::get($response, 'response.need_apply', Arr::get($response, 'need_apply'));
            if ($needApply !== null) {
                $this->line('need_apply: ' . $needApply);
            }

            return self::SUCCESS;
        } catch (GrandstreamApiException $e) {
            $this->error($e->getMessage());

            if ($e->statusCode() !== null) {
                $this->line('Status/API code: ' . $e->statusCode());
            }

            $context = $e->context();
            if ($context) {
                $this->line('Contexto: ' . json_encode(Arr::except($context, ['cookie', 'token', 'password', 'secret']), JSON_UNESCAPED_UNICODE));
            }

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error inesperado al probar llamada: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}