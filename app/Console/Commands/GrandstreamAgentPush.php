<?php

namespace App\Console\Commands;

use App\Services\Telephony\GrandstreamClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GrandstreamAgentPush extends Command
{
    protected $signature = 'grandstream:agent-push
        {--extensions : Envia extensiones al servidor SIRICO}
        {--calls : Envia CDR al servidor SIRICO}
        {--date= : Fecha local YYYY-MM-DD para CDR}
        {--today : Usa la fecha local actual para CDR}';

    protected $description = 'Agente local: lee Grandstream en la red interna y publica extensiones/CDR a SIRICO produccion';

    public function handle(GrandstreamClient $client): int
    {
        $pushExtensions = (bool) $this->option('extensions');
        $pushCalls = (bool) $this->option('calls');

        if (!$pushExtensions && !$pushCalls) {
            $pushExtensions = true;
            $pushCalls = true;
        }

        if ($this->option('today') && $this->option('date')) {
            $this->error('Usa solo --today o --date=YYYY-MM-DD, no ambos.');

            return self::FAILURE;
        }

        try {
            $http = $this->serverHttp();

            if ($pushExtensions) {
                $this->pushExtensions($client, $http);
            }

            if ($pushCalls) {
                $this->pushCalls($client, $http);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('No se pudo publicar desde el agente local: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function pushExtensions(GrandstreamClient $client, PendingRequest $http): void
    {
        $this->info('== Agent push: extensiones ==');

        $extensions = $client->listAccounts();
        $this->line('Extensiones leidas del UCM: ' . count($extensions));

        $response = $http->post($this->serverUrl('/api/agent/telephony/extensions'), [
            'extensions' => $extensions,
        ]);

        $this->printServerResponse($response->json() ?: []);
    }

    private function pushCalls(GrandstreamClient $client, PendingRequest $http): void
    {
        $date = $this->option('today')
            ? now(config('grandstream.cdr.timezone'))->toDateString()
            : ($this->option('date') ?: now(config('grandstream.cdr.timezone'))->toDateString());

        CarbonImmutable::parse($date, config('grandstream.cdr.timezone'));

        $this->info('== Agent push: CDR ==');
        $this->line('Fecha local: ' . $date);

        $calls = $client->cdrForLocalDate($date);
        $this->line('CDR leidos del UCM: ' . count($calls));

        $response = $http->post($this->serverUrl('/api/agent/telephony/calls'), [
            'date' => $date,
            'calls' => $calls,
        ]);

        $this->printServerResponse($response->json() ?: []);
    }

    private function serverHttp(): PendingRequest
    {
        $token = (string) config('grandstream.agent.token');

        if ($token === '') {
            $token = $this->loginToken();
        }

        return Http::timeout((int) config('grandstream.agent.timeout', 30))
            ->acceptJson()
            ->asJson()
            ->withToken($token)
            ->throw();
    }

    private function loginToken(): string
    {
        $email = (string) config('grandstream.agent.email');
        $password = (string) config('grandstream.agent.password');

        if ($email === '' || $password === '') {
            throw new \RuntimeException('Configura GRANDSTREAM_AGENT_TOKEN o GRANDSTREAM_AGENT_EMAIL/GRANDSTREAM_AGENT_PASSWORD en el agente local.');
        }

        $response = Http::timeout((int) config('grandstream.agent.timeout', 30))
            ->acceptJson()
            ->asJson()
            ->post($this->serverUrl('/api/agent/login'), [
                'email' => $email,
                'password' => $password,
            ])
            ->throw()
            ->json();

        $token = Arr::get($response, 'token');

        if (!$token) {
            throw new \RuntimeException('El servidor no devolvio token para el agente.');
        }

        return (string) $token;
    }

    private function serverUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('grandstream.agent.server_url'), '/');

        if ($baseUrl === '') {
            throw new \RuntimeException('Configura GRANDSTREAM_AGENT_SERVER_URL en el agente local.');
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    private function printServerResponse(array $payload): void
    {
        $this->line('Servidor ok: ' . ((bool) Arr::get($payload, 'ok') ? 'si' : 'no'));
        $this->line('Recibidos: ' . (int) Arr::get($payload, 'received', 0));
        $this->line('Mapeados: ' . (int) Arr::get($payload, 'mapped', 0));
        $this->line('Omitidos: ' . (int) Arr::get($payload, 'skipped', 0));
        $this->line('Nuevos: ' . (int) Arr::get($payload, 'new', 0));
        $this->line('Actualizados: ' . (int) Arr::get($payload, 'updated', 0));
        $this->newLine();
    }
}
