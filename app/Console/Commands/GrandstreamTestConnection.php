<?php

namespace App\Console\Commands;

use App\Exceptions\Telephony\GrandstreamApiException;
use App\Services\Telephony\GrandstreamClient;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class GrandstreamTestConnection extends Command
{
    protected $signature = 'grandstream:test-connection {--cdr-date= : Fecha local YYYY-MM-DD para probar CDR} {--skip-cdr : No consultar CDR}';

    protected $description = 'Prueba conexion y autenticacion con Grandstream UCM sin guardar datos';

    public function handle(): int
    {
        if (!config('grandstream.enabled')) {
            $this->warn('GRANDSTREAM_ENABLED=false. La prueba continuara para diagnostico local.');
        }

        $baseUrl = config('grandstream.base_url');
        $username = config('grandstream.username');

        $this->info('== Grandstream UCM test ==');
        $this->line('Base URL: ' . ($baseUrl ?: '(sin configurar)'));
        $this->line('API path: ' . config('grandstream.api_path'));
        $this->line('Usuario: ' . ($username ?: '(sin configurar)'));
        $this->line('TLS verify: ' . (config('grandstream.verify_tls') ? 'true' : 'false'));
        $this->line('Timeout: ' . config('grandstream.timeout') . 's');
        $this->newLine();

        $client = new GrandstreamClient();

        try {
            $started = microtime(true);
            $client->login();
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
            $this->info("Login OK ({$elapsedMs} ms)");

            $status = $client->systemStatus();
            $this->info('System status OK');
            $this->line('Modelo/part number: ' . (Arr::get($status, 'part-number') ?: '(no recibido)'));
            $this->line('Serial: ' . (Arr::get($status, 'serial-number') ?: '(no recibido)'));
            $this->line('MAC: ' . (Arr::get($status, 'mac') ?: '(no recibido)'));
            $this->line('System time: ' . (Arr::get($status, 'system-time') ?: '(no recibido)'));
            $this->newLine();

            $accounts = $client->listAccounts();
            $this->info('Extensiones OK');
            $this->line('Cuentas recibidas: ' . count($accounts));
            $firstExtension = Arr::get($accounts, '0.extension');
            if ($firstExtension) {
                $this->line('Primera extension recibida: ' . $firstExtension);
            }
            $this->newLine();

            if (!$this->option('skip-cdr')) {
                $date = $this->option('cdr-date') ?: now(config('grandstream.cdr.timezone'))->toDateString();
                $this->line("Probando CDR para fecha local: {$date}");

                $calls = $client->cdrForLocalDate($date);
                $this->info('CDR OK');
                $this->line('Registros filtrados: ' . count($calls));

                $starts = array_values(array_filter(array_map(fn ($item) => $client->cdrStart($item), $calls)));
                sort($starts);

                if ($starts) {
                    $this->line('Min start: ' . $starts[0]);
                    $this->line('Max start: ' . $starts[count($starts) - 1]);
                }
            }

            $this->newLine();
            $this->info('Prueba Grandstream completada sin guardar datos.');

            return self::SUCCESS;
        } catch (GrandstreamApiException $e) {
            $this->error($e->getMessage());

            if ($e->statusCode() !== null) {
                $this->line('Status/API code: ' . $e->statusCode());
            }

            $context = $e->context();
            if ($context) {
                $safeContext = Arr::except($context, ['cookie', 'token', 'password', 'secret']);
                $this->line('Contexto: ' . json_encode($safeContext, JSON_UNESCAPED_UNICODE));
            }

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error inesperado al probar Grandstream: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}