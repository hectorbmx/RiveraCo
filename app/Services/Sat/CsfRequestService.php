<?php

namespace App\Services\Sat;

use App\Models\SatDocumentRequest;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpCfdi\CsfSatScraper\HttpClientFactory;
use PhpCfdi\CsfSatScraper\Scraper;
use PhpCfdi\ImageCaptchaResolver\Resolvers\AntiCaptchaResolver;
use App\Services\Sat\Resolvers\DatabaseCaptchaResolver;  // ← con Resolvers


// use PhpCfdi\ImageCaptchaResolver\Resolvers\ConsoleResolver;

use App\Services\Sat\Resolvers\StoreCaptchaResolver;

class CsfRequestService
{
 public function handle(SatDocumentRequest $documentRequest): void
    {
        $documentRequest->update([
            'status'        => SatDocumentRequest::STATUS_PROCESSING,
            'error_message' => null,
        ]);

        $documentRequest->loadMissing('empresa');
        $empresa = $documentRequest->empresa;

        if (!$empresa) {
            throw new \RuntimeException('La solicitud no tiene empresa SAT relacionada.');
        }
        if (empty($empresa->rfc)) {
            throw new \RuntimeException('La empresa SAT no tiene RFC configurado.');
        }
        if (empty($empresa->sat_password)) {
            throw new \RuntimeException('La empresa SAT no tiene contraseña SAT/CIEC configurada.');
        }

        $client = HttpClientFactory::create([
            'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
            RequestOptions::VERIFY => false,
        ]);

        $captchaToken = Str::uuid()->toString();

        $documentRequest->update(['captcha_token' => $captchaToken]);

        $captchaSolver = new DatabaseCaptchaResolver(
            token: $captchaToken,
            timeoutSeconds: 300,
            pollIntervalSeconds: 3,
        );

        $scraper = Scraper::create(
            $client,
            $captchaSolver,
            $empresa->rfc,
            $empresa->sat_password
        );

      try {
            // Bloquea aquí hasta que el usuario resuelva el captcha
            $pdfContent = $scraper->download();
            $binaryPdf  = (string) $pdfContent;

            if ('' === $binaryPdf) {
                throw new \RuntimeException('El SAT devolvió un PDF vacío.');
            }

            $folder   = 'sat/csf/' . $empresa->rfc;
            $fileName = 'csf_' . now()->format('Ymd_His') . '_' . Str::uuid() . '.pdf';
            $path     = $folder . '/' . $fileName;

            Storage::put($path, $binaryPdf);

            $documentRequest->update([
                'status'               => SatDocumentRequest::STATUS_COMPLETED,
                'file_path'            => $path,
                'file_name'            => $fileName,
                'mime_type'            => 'application/pdf',
                'file_size'            => Storage::size($path),
                'processed_at'         => now(),
                'error_message'        => null,
                'captcha_token'        => null,
                'captcha_path'         => null,
                'captcha_answer'       => null,
                'captcha_requested_at' => null,
            ]);

        } catch (\Throwable $e) {
            logger()->error('CSF scraper error', [
                'message'  => $e->getMessage(),
                'class'    => get_class($e),
                'previous' => $e->getPrevious()?->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            $documentRequest->update([
                'status'        => SatDocumentRequest::STATUS_ERROR,
                'error_message' => $e->getMessage(),
                'captcha_token' => null,
            ]);

            throw $e;
        }
    }
}