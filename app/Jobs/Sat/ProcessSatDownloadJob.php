<?php

namespace App\Jobs\Sat;

use App\Models\SatDownloadRequest;
use App\Services\Sat\SatMassDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSatDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1200;

    public function __construct(public int $downloadRequestId)
    {
    }

    public function handle(): void
    {
        $downloadRequest = SatDownloadRequest::with('empresa')->findOrFail($this->downloadRequestId);

        if (! $downloadRequest->empresa) {
            throw new \RuntimeException('La solicitud no tiene una empresa SAT asociada.');
        }

        $empresa = $downloadRequest->empresa;

        $cerPath = storage_path('app/' . $empresa->cer_path);
        $keyPath = storage_path('app/' . $empresa->key_path);
        $password = $empresa->fiel_password;

        if (! file_exists($cerPath)) {
            throw new \RuntimeException("No existe el archivo CER: {$cerPath}");
        }

        if (! file_exists($keyPath)) {
            throw new \RuntimeException("No existe el archivo KEY: {$keyPath}");
        }

        try {
            $service = new SatMassDownloadService(
                $cerPath,
                $keyPath,
                $password
            );

            $requestId = $downloadRequest->request_id_sat;
            $packagesIds = is_array($downloadRequest->packages_ids)
                ? $downloadRequest->packages_ids
                : [];

            /*
            |--------------------------------------------------------------------------
            | 1) CREATE QUERY SOLO SI NO EXISTE request_id_sat
            |--------------------------------------------------------------------------
            */
            if (! $requestId) {
                $downloadRequest->update([
                    'estado' => 'querying',
                    'error_message' => null,
                ]);

                $requestId = $service->createQuery(
                    $downloadRequest->fecha_inicio->format('Y-m-d H:i:s'),
                    $downloadRequest->fecha_fin->format('Y-m-d H:i:s'),
                    $downloadRequest->tipo_descarga
                );

                $downloadRequest->update([
                    'request_id_sat' => $requestId,
                    'estado' => 'verifying',
                    'error_message' => null,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 2) VERIFY SOLO SI NO HAY packages_ids
            |--------------------------------------------------------------------------
            */
            if ([] === $packagesIds) {
                $downloadRequest->update([
                    'estado' => 'verifying',
                    'error_message' => null,
                ]);

                $verify = $service->verifyQuery($requestId);

                if (! ($verify['ready'] ?? false)) {
                    $downloadRequest->update([
                        'estado' => 'verifying',
                        'error_message' => 'Solicitud en proceso en SAT. Reintentar verificación después.',
                    ]);

                    return;
                }

                $packagesIds = $verify['packages_ids'] ?? [];

                if ([] === $packagesIds) {
                    $downloadRequest->update([
                        'estado' => 'verifying',
                        'error_message' => 'SAT indicó solicitud lista, pero no devolvió paquetes.',
                    ]);

                    return;
                }

                $downloadRequest->update([
                    'packages_ids' => $packagesIds,
                    'estado' => 'downloading',
                    'error_message' => null,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3) DOWNLOAD + PERSIST
            |--------------------------------------------------------------------------
            */
            $downloadRequest->update([
                'estado' => 'downloading',
                'error_message' => null,
            ]);

            $storagePath = storage_path('app/sat/xml/' . $downloadRequest->id);

            if (! file_exists($storagePath)) {
                mkdir($storagePath, 0777, true);
            }

            // $xmlFiles = $service->downloadPackages($packagesIds, $storagePath);

            // $persisted = $service->persistXmlFiles(
            //     $downloadRequest->id,
            //     $xmlFiles,
            //     is_array($packagesIds) ? implode(',', $packagesIds) : null
            // );

            // $downloadRequest->update([
            //     'total_xml' => count($xmlFiles),
            //     'estado' => 'completed',
            //     'error_message' => $persisted . ' CFDIs procesados correctamente.',
            // ]);
            $downloadResult = $service->downloadPackages($packagesIds, $storagePath);

                $xmlFiles = $downloadResult['xml_files'] ?? [];
                $debug = $downloadResult['debug'] ?? [];

                $persisted = $service->persistXmlFiles(
                    $downloadRequest->id,
                    $xmlFiles,
                    is_array($packagesIds) ? implode(',', $packagesIds) : null
                );

                $downloadRequest->update([
                    'total_xml' => count($xmlFiles),
                    'estado' => 'completed',
                    'error_message' => $persisted . ' CFDIs procesados correctamente.',
                ]);

        } catch (\Throwable $e) {
            $downloadRequest->update([
                'estado' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}