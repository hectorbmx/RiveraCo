<?php

namespace App\Services\Sat;

use App\Models\SatDocumentRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpCfdi\OpinionCumplimientoSatScraper\Scraper;

class D32RequestService
{
    public function __construct(private readonly SatCaptchaResolverFactory $captchaResolverFactory)
    {
    }

    public function handle(SatDocumentRequest $documentRequest): void
    {
        $documentRequest->update([
            'status' => SatDocumentRequest::STATUS_PROCESSING,
            'error_message' => null,
        ]);

        $documentRequest->loadMissing('empresa');
        $empresa = $documentRequest->empresa;

        if (! $empresa) {
            throw new \RuntimeException('La solicitud no tiene empresa SAT relacionada.');
        }
        if (empty($empresa->rfc)) {
            throw new \RuntimeException('La empresa SAT no tiene RFC configurado.');
        }
        if (empty($empresa->sat_password)) {
            throw new \RuntimeException('La empresa SAT no tiene contrasena SAT/CIEC configurada.');
        }

        $captchaToken = Str::uuid()->toString();
        $documentRequest->update(['captcha_token' => $captchaToken]);

        try {
            $scraper = new Scraper(
                $this->makeHttpClient(),
                $this->captchaResolverFactory->make($captchaToken),
                $empresa->rfc,
                $empresa->sat_password
            );

            $binaryPdf = (string) $scraper->download();

            if ($binaryPdf === '') {
                throw new \RuntimeException('El SAT devolvio un PDF vacio para D32.');
            }

            $folder = 'sat/d32/' . $empresa->rfc;
            $fileName = 'd32_' . now()->format('Ymd_His') . '_' . Str::uuid() . '.pdf';
            $path = $folder . '/' . $fileName;

            Storage::put($path, $binaryPdf);

            $documentRequest->update([
                'status' => SatDocumentRequest::STATUS_COMPLETED,
                'file_path' => $path,
                'file_name' => $fileName,
                'mime_type' => 'application/pdf',
                'file_size' => Storage::size($path),
                'processed_at' => now(),
                'error_message' => null,
                'captcha_token' => null,
                'captcha_path' => null,
                'captcha_answer' => null,
                'captcha_requested_at' => null,
            ]);
        } catch (\Throwable $e) {
            logger()->error('D32 scraper error', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'previous' => $e->getPrevious()?->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $documentRequest->update([
                'status' => SatDocumentRequest::STATUS_ERROR,
                'error_message' => $e->getMessage(),
                'captcha_token' => null,
            ]);

            throw $e;
        }
    }

    private function makeHttpClient(): Client
    {
        return new Client([
            'cookies' => new CookieJar(),
            'curl' => [
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1',
            ],
            RequestOptions::VERIFY => false,
        ]);
    }
}
