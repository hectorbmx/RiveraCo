<?php

namespace App\Services\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MicrosoftGraphFollowupClient
{
    public function accessToken(): string
    {
        $tenantId = (string) config('services.mail_followup.microsoft_graph.tenant_id');
        $clientId = (string) config('services.mail_followup.microsoft_graph.client_id');
        $clientSecret = (string) config('services.mail_followup.microsoft_graph.client_secret');

        if (!Str::isUuid($tenantId) || !Str::isUuid($clientId) || trim($clientSecret) === '') {
            throw new RuntimeException('Configura GRAPH_FOLLOWUP_TENANT_ID, GRAPH_FOLLOWUP_CLIENT_ID y GRAPH_FOLLOWUP_CLIENT_SECRET.');
        }

        try {
            $response = $this->http()->asForm()->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                ],
            );
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo conectar con Microsoft Entra.');
        }

        if (!$response->successful()) {
            throw new RuntimeException('Microsoft Entra rechazó la solicitud de token (HTTP ' . $response->status() . ').');
        }

        $token = $response->json('access_token');

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Microsoft Entra respondió sin un token de acceso válido.');
        }

        return $token;
    }

    public function folderHasMessages(string $mailbox, string $folder, string $token): bool
    {
        return $this->messages($mailbox, $folder, $token, ['$top' => 1, '$select' => 'id']) !== [];
    }

    public function recentMessages(string $mailbox, string $folder, string $token, int $limit): array
    {
        $date = $folder === 'sentitems' ? 'sentDateTime' : 'receivedDateTime';

        return array_slice($this->messages($mailbox, $folder, $token, [
            '$top' => $limit,
            '$orderby' => $date . ' desc',
            '$select' => 'id,conversationId,subject,bodyPreview,from,sender,receivedDateTime,sentDateTime,webLink,internetMessageHeaders',
        ]), 0, $limit);
    }

    private function messages(string $mailbox, string $folder, string $token, array $query): array
    {
        $mailbox = rawurlencode($mailbox);
        $folder = rawurlencode($folder);

        try {
            $response = $this->http()
                ->withToken($token)
                ->withHeaders(['Prefer' => 'IdType="ImmutableId"'])
                ->get("https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders/{$folder}/messages", $query);
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo conectar con Microsoft Graph.');
        }

        if (!$response->successful()) {
            throw new RuntimeException('Microsoft Graph rechazó la lectura (HTTP ' . $response->status() . ').');
        }

        $messages = $response->json('value');

        if (!is_array($messages) || !array_is_list($messages)) {
            throw new RuntimeException('Microsoft Graph respondió sin una lista de mensajes válida.');
        }

        return $messages;
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->connectTimeout(5)->timeout(15)->withoutRedirecting();
    }
}
