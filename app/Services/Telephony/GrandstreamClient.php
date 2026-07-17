<?php

namespace App\Services\Telephony;

use App\Exceptions\Telephony\GrandstreamApiException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GrandstreamClient
{
    private ?string $cookie = null;

    public function __construct(private readonly array $config = [])
    {
    }

    public function login(): string
    {
        $username = $this->config('username');
        $password = $this->config('password');

        if (!$username || !$password) {
            throw new GrandstreamApiException('Grandstream no tiene usuario o password configurado.');
        }

        $challengeResponse = $this->request($this->action('challenge'), [
            'version' => $this->version(),
            'user' => $username,
        ]);

        $challenge = Arr::get($challengeResponse, 'response.challenge');

        if (!$challenge) {
            $status = Arr::get($challengeResponse, 'status');
            $errorMessage = Arr::get($challengeResponse, 'response.error_msg');
            $detail = $status !== null ? " Status/API code: {$status}." : '';
            $detail .= $errorMessage ? " Mensaje UCM: {$errorMessage}." : '';

            throw new GrandstreamApiException(
                'Grandstream no autorizo el challenge de autenticacion.' . $detail . ' Revisa que la API nueva este activa, que el usuario API este habilitado y que la IP actual del equipo/agente este en Address Whitelist.',
                is_numeric($status) ? (int) $status : null,
                ['status' => $status, 'error_msg' => $errorMessage]
            );
        }

        $token = md5($challenge . $password);
        $response = $this->request($this->action('login'), [
            'version' => $this->version(),
            'user' => $username,
            'token' => $token,
        ]);

        $status = Arr::get($response, 'status');
        $cookie = Arr::get($response, 'response.cookie');

        if ((string) $status !== '0' || !$cookie) {
            throw new GrandstreamApiException('Grandstream rechazo el login API.', is_numeric($status) ? (int) $status : null, [
                'status' => $status,
                'captcha_required' => Arr::has($response, 'response.captcha_filename'),
                'remain_num' => Arr::get($response, 'response.remain_num'),
            ]);
        }

        return $this->cookie = $cookie;
    }

    public function systemStatus(): array
    {
        return $this->responsePayload($this->authenticatedRequest($this->action('system_status')));
    }

    public function listAccounts(): array
    {
        $payload = $this->responsePayload($this->authenticatedRequest($this->action('extensions')));

        return Arr::wrap(Arr::get($payload, 'account'));
    }

    public function cdr(array $parameters = []): array
    {
        $response = $this->authenticatedRequest($this->action('cdr'), array_merge([
            'format' => $this->config('cdr.format', 'json'),
            'numRecords' => (string) $this->config('cdr.page_size', 100),
            'offset' => '0',
        ], $parameters));

        return Arr::wrap(Arr::get($response, 'cdr_root'));
    }

    public function cdrForLocalDate(string|CarbonImmutable $date): array
    {
        $timezone = $this->config('cdr.timezone', 'America/Mexico_City');
        $targetDate = $date instanceof CarbonImmutable
            ? $date->setTimezone($timezone)->startOfDay()
            : CarbonImmutable::parse($date, $timezone)->startOfDay();

        $paddingHours = (int) $this->config('cdr.padding_hours', 12);
        $queryStart = $targetDate->subHours($paddingHours);
        $queryEnd = $targetDate->addDay()->addHours($paddingHours);
        $pageSize = (int) $this->config('cdr.page_size', 100);
        $offset = 0;
        $items = [];

        do {
            $batch = $this->cdr([
                'numRecords' => (string) $pageSize,
                'offset' => (string) $offset,
                'startTime' => $queryStart->format('Y-m-d H:i:s'),
                'endTime' => $queryEnd->format('Y-m-d H:i:s'),
            ]);

            array_push($items, ...$batch);
            $offset += $pageSize;
        } while (count($batch) === $pageSize);

        return array_values(array_filter($items, function ($item) use ($targetDate, $timezone) {
            $start = $this->cdrStart($item);

            if (!$start) {
                return false;
            }

            return CarbonImmutable::parse($start, $timezone)->isSameDay($targetDate);
        }));
    }

    public function cdrStart(array|object $item): ?string
    {
        $item = json_decode(json_encode($item), true) ?: [];

        return Arr::get($item, 'start') ?: Arr::get($item, 'main_cdr.start');
    }

    public function dialOutbound(string $caller, string $outbound): array
    {
        $caller = trim($caller);
        $outbound = trim($outbound);

        if ($caller === '' || $outbound === '') {
            throw new GrandstreamApiException('Caller y outbound son obligatorios para dialOutbound.');
        }

        $response = $this->authenticatedRequest($this->action('dial_outbound'), [
            'caller' => $caller,
            'outbound' => $outbound,
        ]);

        $status = Arr::get($response, 'status');

        if ((string) $status !== '0') {
            throw new GrandstreamApiException('Grandstream rechazo dialOutbound.', is_numeric($status) ? (int) $status : null, [
                'status' => $status,
                'response' => Arr::except(Arr::get($response, 'response', []), ['cookie', 'token', 'password', 'secret']),
            ]);
        }

        return $response;
    }
    public function authenticatedCookie(): string
    {
        return $this->cookie ?: $this->login();
    }

    private function authenticatedRequest(string $action, array $payload = []): array
    {
        return $this->request($action, array_merge([
            'cookie' => $this->authenticatedCookie(),
        ], $payload));
    }

    private function request(string $action, array $payload = []): array
    {
        $response = $this->http()->post($this->endpoint(), [
            'request' => array_merge(['action' => $action], $payload),
        ]);

        if (!$response->successful()) {
            throw new GrandstreamApiException('Grandstream respondio con error HTTP.', $response->status());
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new GrandstreamApiException('Grandstream devolvio una respuesta JSON invalida.');
        }

        return $json;
    }

    private function responsePayload(array $response): array
    {
        $status = Arr::get($response, 'status');

        if ((string) $status !== '0') {
            throw new GrandstreamApiException('Grandstream respondio con status de API no exitoso.', is_numeric($status) ? (int) $status : null, [
                'status' => $status,
            ]);
        }

        return Arr::get($response, 'response', []);
    }

    private function http(): PendingRequest
    {
        $request = Http::timeout((int) $this->config('timeout', 15))
            ->connectTimeout((int) $this->config('connect_timeout', 5))
            ->acceptJson()
            ->asJson();

        if (!$this->config('verify_tls', true)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    private function endpoint(): string
    {
        $baseUrl = rtrim((string) $this->config('base_url'), '/');
        $apiPath = '/' . ltrim((string) $this->config('api_path', '/api'), '/');

        if (!$baseUrl) {
            throw new GrandstreamApiException('Grandstream no tiene GRANDSTREAM_BASE_URL configurado.');
        }

        return $baseUrl . $apiPath;
    }

    private function action(string $key): string
    {
        return (string) $this->config("actions.$key", $key);
    }

    private function version(): string
    {
        return (string) $this->config('version', '1.0');
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config ?: config('grandstream'), $key, $default);
    }
}