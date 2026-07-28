<?php

namespace App\Services\Mail;

use App\Models\SatFactura;
use App\Models\SatFacturaPago;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MicrosoftGraphMailService
{
    /**
     * @param array<int, string> $destinatarios
     * @param array<int, array<string, mixed>> $attachments
     */
    public function sendHtml(
        string $subject,
        string $html,
        array $destinatarios,
        ?string $graphUser = null,
        array $attachments = []
    ): void {
        $destinatarios = $this->normalizeRecipients($destinatarios);

        if ($destinatarios === []) {
            throw new RuntimeException('No hay destinatarios validos para enviar el correo.');
        }

        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $html,
                ],
                'toRecipients' => array_map(fn (string $email) => [
                    'emailAddress' => ['address' => $email],
                ], $destinatarios),
                'attachments' => $attachments,
            ],
            'saveToSentItems' => true,
        ];

        $response = Http::withToken($this->accessToken($graphUser))
            ->acceptJson()
            ->asJson()
            ->post($this->sendMailUrl($graphUser), $payload);

        if (!$response->successful()) {
            $message = $response->json('error.message')
                ?: $response->body()
                ?: 'Microsoft Graph rechazo el envio.';

            throw new RuntimeException('Microsoft Graph no pudo enviar el correo: ' . $message);
        }
    }

    public function sendSatFactura(SatFactura $factura, array $destinatarios, ?SatFacturaPago $pago = null): void
    {
        $destinatarios = $this->normalizeRecipients($destinatarios);

        if ($destinatarios === []) {
            throw new RuntimeException('No hay destinatarios validos para enviar el correo.');
        }

        $documento = $pago ?? $factura;
        $esComplemento = $pago !== null;
        $uuid = $documento->uuid ?? $documento->id;
        $prefijo = $esComplemento ? 'complemento-pago' : 'factura';

        $subject = $esComplemento
            ? 'Complemento de pago CFDI ' . $uuid
            : 'Factura CFDI ' . $factura->serie . '-' . $factura->folio;

        $html = view('emails.sat.factura', [
            'factura' => $factura,
            'pago' => $pago,
            'esComplemento' => $esComplemento,
        ])->render();

        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $html,
                ],
                'toRecipients' => array_map(fn (string $email) => [
                    'emailAddress' => ['address' => $email],
                ], $destinatarios),
                'attachments' => $this->attachments($documento, $prefijo, (string) $uuid),
            ],
            'saveToSentItems' => true,
        ];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->post($this->sendMailUrl(), $payload);

        if (!$response->successful()) {
            $message = $response->json('error.message')
                ?: $response->body()
                ?: 'Microsoft Graph rechazo el envio.';

            throw new RuntimeException('Microsoft Graph no pudo enviar el correo: ' . $message);
        }
    }

    private function accessToken(?string $graphUser = null): string
    {
        $tenantId = $this->requiredConfig('tenant_id');
        $clientId = $this->requiredConfig('client_id');
        $clientSecret = $this->requiredConfig('client_secret');
        $cacheKey = 'facturacion_graph_token_' . sha1($tenantId . '|' . $clientId . '|' . $this->graphUser($graphUser));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($tenantId, $clientId, $clientSecret) {
            $response = Http::asForm()->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            );

            if (!$response->successful()) {
                $message = $response->json('error_description')
                    ?: $response->json('error')
                    ?: $response->body()
                    ?: 'No se pudo obtener token OAuth2.';

                throw new RuntimeException('Error al autenticar con Microsoft Graph: ' . $message);
            }

            $token = $response->json('access_token');

            if (!$token) {
                throw new RuntimeException('Microsoft Graph no regreso access_token.');
            }

            return $token;
        });
    }

    private function sendMailUrl(?string $graphUser = null): string
    {
        return 'https://graph.microsoft.com/v1.0/users/' . rawurlencode($this->graphUser($graphUser)) . '/sendMail';
    }

    private function graphUser(?string $graphUser = null): string
    {
        return $graphUser ?: $this->requiredConfig('user');
    }

    private function requiredConfig(string $key): string
    {
        $value = (string) config("services.facturacion_mail.microsoft_graph.{$key}");

        if ($value === '') {
            throw new RuntimeException("Falta configurar FACTURACION_GRAPH_" . strtoupper($key) . '.');
        }

        return $value;
    }

    private function attachments(SatFactura|SatFacturaPago $documento, string $prefijo, string $uuid): array
    {
        $attachments = [];

        if ($documento->xml_path && Storage::disk('local')->exists($documento->xml_path)) {
            $attachments[] = $this->fileAttachment(
                Storage::disk('local')->path($documento->xml_path),
                $prefijo . '-' . $uuid . '.xml',
                'application/xml'
            );
        }

        if ($documento->pdf_path && Storage::disk('local')->exists($documento->pdf_path)) {
            $attachments[] = $this->fileAttachment(
                Storage::disk('local')->path($documento->pdf_path),
                $prefijo . '-' . $uuid . '.pdf',
                'application/pdf'
            );
        }

        return $attachments;
    }

    private function fileAttachment(string $path, string $name, string $contentType): array
    {
        return [
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'name' => $name,
            'contentType' => $contentType,
            'contentBytes' => base64_encode((string) file_get_contents($path)),
        ];
    }

    private function normalizeRecipients(array $recipients): array
    {
        return collect($recipients)
            ->filter()
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}