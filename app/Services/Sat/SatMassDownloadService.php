<?php

namespace App\Services\Sat;
use App\Models\SatCfdi;
use App\Models\SatCfdiConcepto;
use SimpleXMLElement;

use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;

use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;

use PhpCfdi\SatWsDescargaMasiva\PackageReader\CfdiPackageReader;
use PhpCfdi\SatWsDescargaMasiva\PackageReader\Exceptions\OpenZipFileException;

class SatMassDownloadService
{
    protected Service $service;

    public function __construct(string $cerPath, string $keyPath, string $password)
    {
        $fiel = Fiel::create(
            file_get_contents($cerPath),
            file_get_contents($keyPath),
            $password
        );

        if (! $fiel->isValid()) {
            throw new \Exception('FIEL inválida o vencida');
        }

        $webClient = new GuzzleWebClient();
        $requestBuilder = new FielRequestBuilder($fiel);

        $this->service = new Service($requestBuilder, $webClient);
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function createQuery(string $start, string $end, string $downloadType = 'received'): string
{
    $type = match ($downloadType) {
        'issued' => DownloadType::issued(),
        default => DownloadType::received(),
    };

   $query = QueryParameters::create()
    ->withPeriod(DateTimePeriod::createFromValues($start, $end))
    ->withDownloadType($type)
    ->withRequestType(RequestType::xml())
    ->withDocumentStatus(DocumentStatus::active());

    $errors = $query->validate();

    if ([] !== $errors) {
        throw new \Exception('Errores de consulta SAT: ' . implode(' | ', $errors));
    }

    $result = $this->service->query($query);

    if (! $result->getStatus()->isAccepted()) {
        throw new \Exception('Fallo al presentar la consulta: ' . $result->getStatus()->getMessage());
    }

    return $result->getRequestId();
}

public function verifyQuery(string $requestId): array
{
    $verify = $this->service->verify($requestId);

    if (! $verify->getStatus()->isAccepted()) {
        throw new \Exception(
            "Fallo al verificar la consulta {$requestId}: " . $verify->getStatus()->getMessage()
        );
    }

    if (! $verify->getCodeRequest()->isAccepted()) {
        throw new \Exception(
            "La solicitud {$requestId} fue rechazada: " . $verify->getCodeRequest()->getMessage()
        );
    }

    $statusRequest = $verify->getStatusRequest();

    if ($statusRequest->isExpired() || $statusRequest->isFailure() || $statusRequest->isRejected()) {
        throw new \Exception("La solicitud {$requestId} no se puede completar");
    }

    if ($statusRequest->isInProgress() || $statusRequest->isAccepted()) {
        return [
            'ready' => false,
            'packages_ids' => [],
            'message' => "La solicitud {$requestId} se está procesando",
        ];
    }

    if ($statusRequest->isFinished()) {
        return [
            'ready' => true,
            'packages_ids' => $verify->getPackagesIds(),
            'message' => "La solicitud {$requestId} está lista",
        ];
    }

    return [
        'ready' => false,
        'packages_ids' => [],
        'message' => "Estado no reconocido para la solicitud {$requestId}",
    ];
}
public function downloadPackages(array $packagesIds, string $storagePath): array
{
    if (! file_exists($storagePath)) {
        mkdir($storagePath, 0777, true);
    }

    $allXml = [];
    $debug = [];

    foreach ($packagesIds as $packageId) {

        $download = $this->service->download($packageId);

        $debug[] = [
            'package_id' => $packageId,
            'status_code' => $download->getStatus()->getCode(),
            'status_message' => $download->getStatus()->getMessage(),
            'accepted' => $download->getStatus()->isAccepted(),
        ];

        if (! $download->getStatus()->isAccepted()) {
    $debug[] = [
        'package_id' => $packageId,
        'status_code' => $download->getStatus()->getCode(),
        'status_message' => $download->getStatus()->getMessage(),
        'accepted' => false,
    ];
    continue;
}

        $zipPath = $storagePath . '/' . $packageId . '.zip';

        file_put_contents($zipPath, $download->getPackageContent());

        try {
            $reader = CfdiPackageReader::createFromFile($zipPath);
        } catch (OpenZipFileException $e) {
            $debug[] = [
                'package_id' => $packageId,
                'zip_error' => $e->getMessage(),
            ];
            continue;
        }

        foreach ($reader->cfdis() as $uuid => $xmlContent) {
            $xmlPath = $storagePath . '/' . $uuid . '.xml';
            file_put_contents($xmlPath, $xmlContent);

            $allXml[] = [
                'uuid' => $uuid,
                'path' => $xmlPath,
            ];
        }
    }

    return [
        'xml_files' => $allXml,
        'debug' => $debug,
    ];
}
public function resumeDownload(string $requestId, string $storagePath, int $downloadRequestId = null): array
{
    $verify = $this->verifyQuery($requestId);

    if (! $verify['ready']) {
        return [
            'ready' => false,
            'xml_files' => [],
            'message' => 'Aún no está listo en SAT',
        ];
    }

    $packagesIds = $verify['packages_ids'];

    $downloadResult = $this->downloadPackages($packagesIds, $storagePath);

    $persisted = 0;

    if ($downloadRequestId) {
        foreach ($packagesIds as $packageId) {
            $persisted += $this->persistXmlFiles(
                $downloadRequestId,
                $downloadResult['xml_files'],
                $packageId
            );
        }

        $request = \App\Models\SatDownloadRequest::find($downloadRequestId);

        if ($request) {
            $request->update([
                'packages_ids' => $packagesIds,
                'total_xml' => count($downloadResult['xml_files']),
                'estado' => 'completed',
                'error_message' => null,
            ]);
        }
    }

    return [
        'ready' => true,
        'xml_files' => $downloadResult['xml_files'],
        'packages_ids' => $packagesIds,
        'persisted' => $persisted,
        'debug' => $downloadResult['debug'] ?? [],
    ];
}
//guarda los datos de los XML en la base de datos
public function persistXmlFiles(int $downloadRequestId, array $xmlFiles, string $packageId = null): int
{
    $saved = 0;

    foreach ($xmlFiles as $file) {
        $uuid = $file['uuid'] ?? null;
        $path = $file['path'] ?? null;

        if (! $uuid || ! $path || ! file_exists($path)) {
            continue;
        }

        if (SatCfdi::where('uuid', $uuid)->exists()) {
            continue;
        }

        $xmlContent = file_get_contents($path);

        if (false === $xmlContent || '' === $xmlContent) {
            continue;
        }

        try {
            $xml = new \SimpleXMLElement($xmlContent);

            $comprobanteAttrs = $xml->attributes();

            $namespaces = $xml->getNamespaces(true);
            $cfdiNs = $namespaces['cfdi'] ?? null;

            $emisorNode = null;
            $receptorNode = null;

            if ($cfdiNs) {
                $children = $xml->children($cfdiNs);
                $emisorNode = $children->Emisor ?? null;
                $receptorNode = $children->Receptor ?? null;
            } else {
                $emisorNode = $xml->Emisor ?? null;
                $receptorNode = $xml->Receptor ?? null;
            }

            $emisorAttrs = $emisorNode ? $emisorNode->attributes() : null;
            $receptorAttrs = $receptorNode ? $receptorNode->attributes() : null;

            // SatCfdi::create([
        $cfdi = SatCfdi::create([

                'sat_download_request_id' => $downloadRequestId,
                'uuid' => $uuid,

                'version' => $this->xmlAttr($comprobanteAttrs, ['Version', 'version']),
                'serie' => $this->xmlAttr($comprobanteAttrs, ['Serie', 'serie']),
                'folio' => $this->xmlAttr($comprobanteAttrs, ['Folio', 'folio']),
                'fecha_emision' => $this->xmlAttr($comprobanteAttrs, ['Fecha', 'fecha']),
                'tipo_comprobante' => $this->xmlAttr($comprobanteAttrs, ['TipoDeComprobante', 'tipodecomprobante']),
                'subtotal' => $this->xmlAttr($comprobanteAttrs, ['SubTotal', 'Subtotal', 'subTotal']),
                'descuento' => $this->xmlAttr($comprobanteAttrs, ['Descuento', 'descuento']),
                'total' => $this->xmlAttr($comprobanteAttrs, ['Total', 'total']),
                'moneda' => $this->xmlAttr($comprobanteAttrs, ['Moneda', 'moneda']),
                'tipo_cambio' => $this->xmlAttr($comprobanteAttrs, ['TipoCambio', 'tipocambio']),
                'forma_pago' => $this->xmlAttr($comprobanteAttrs, ['FormaPago', 'formapago']),
                'metodo_pago' => $this->xmlAttr($comprobanteAttrs, ['MetodoPago', 'metodopago']),
                'lugar_expedicion' => $this->xmlAttr($comprobanteAttrs, ['LugarExpedicion', 'lugarexpedicion']),
                'exportacion' => $this->xmlAttr($comprobanteAttrs, ['Exportacion', 'exportacion']),
                'no_certificado' => $this->xmlAttr($comprobanteAttrs, ['NoCertificado', 'nocertificado']),
                'certificado' => $this->xmlAttr($comprobanteAttrs, ['Certificado', 'certificado']),
                'sello' => $this->xmlAttr($comprobanteAttrs, ['Sello', 'sello']),

                'rfc_emisor' => $this->xmlAttr($emisorAttrs, ['Rfc', 'RFC', 'rfc']),
                'rfc_receptor' => $this->xmlAttr($receptorAttrs, ['Rfc', 'RFC', 'rfc']),

                'emisor_rfc' => $this->xmlAttr($emisorAttrs, ['Rfc', 'RFC', 'rfc']),
                'emisor_nombre' => $this->xmlAttr($emisorAttrs, ['Nombre', 'nombre']),
                'emisor_regimen_fiscal' => $this->xmlAttr($emisorAttrs, ['RegimenFiscal', 'regimenfiscal']),

                'receptor_rfc' => $this->xmlAttr($receptorAttrs, ['Rfc', 'RFC', 'rfc']),
                'receptor_nombre' => $this->xmlAttr($receptorAttrs, ['Nombre', 'nombre']),
                'receptor_domicilio_fiscal' => $this->xmlAttr($receptorAttrs, ['DomicilioFiscalReceptor', 'domiciliofiscalreceptor']),
                'receptor_regimen_fiscal' => $this->xmlAttr($receptorAttrs, ['RegimenFiscalReceptor', 'regimenfiscalreceptor']),
                'receptor_uso_cfdi' => $this->xmlAttr($receptorAttrs, ['UsoCFDI', 'Usocfdi', 'usocfdi']),

                'xml_path' => $path,
                'package_id' => $packageId,
            ]);
            $this->persistConceptos($cfdi, $xml, $cfdiNs);
            $saved++;
        } catch (\Throwable $e) {
    \Log::error('Error persistiendo CFDI XML', [
        'uuid' => $uuid,
        'path' => $path,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
    ]);
    continue;
}
    }

    return $saved;
}
private function persistConceptos(SatCfdi $cfdi, SimpleXMLElement $xml, ?string $cfdiNs): void
{
    $conceptoNodes = $xml->xpath('//*[local-name()="Conceptos"]/*[local-name()="Concepto"]');

    if (empty($conceptoNodes)) {
        return;
    }

    foreach ($conceptoNodes as $conceptoNode) {
        $attrs = $conceptoNode->attributes();

        $informacionAduanera = [];
        $cuentaPredial = [];
        $partes = [];

        $infoAduaneraNodes = $conceptoNode->xpath('./*[local-name()="InformacionAduanera"]') ?: [];
        foreach ($infoAduaneraNodes as $childNode) {
            $childAttrs = $childNode->attributes();
            $informacionAduanera[] = [
                'numero_pedimento' => $this->xmlAttr($childAttrs, ['NumeroPedimento', 'numeropedimento']),
            ];
        }

        $cuentaPredialNodes = $conceptoNode->xpath('./*[local-name()="CuentaPredial"]') ?: [];
        foreach ($cuentaPredialNodes as $childNode) {
            $childAttrs = $childNode->attributes();
            $cuentaPredial[] = [
                'numero' => $this->xmlAttr($childAttrs, ['Numero', 'numero']),
            ];
        }

        $parteNodes = $conceptoNode->xpath('./*[local-name()="Parte"]') ?: [];
        foreach ($parteNodes as $childNode) {
            $childAttrs = $childNode->attributes();
            $partes[] = [
                'clave_prod_serv'   => $this->xmlAttr($childAttrs, ['ClaveProdServ', 'claveprodserv']),
                'no_identificacion' => $this->xmlAttr($childAttrs, ['NoIdentificacion', 'noidentificacion']),
                'cantidad'          => $this->xmlAttr($childAttrs, ['Cantidad', 'cantidad']),
                'unidad'            => $this->xmlAttr($childAttrs, ['Unidad', 'unidad']),
                'descripcion'       => $this->xmlAttr($childAttrs, ['Descripcion', 'descripcion']),
                'valor_unitario'    => $this->xmlAttr($childAttrs, ['ValorUnitario', 'valorunitario']),
                'importe'           => $this->xmlAttr($childAttrs, ['Importe', 'importe']),
            ];
        }

        $cfdi->conceptos()->create([
            'clave_prod_serv' => $this->xmlAttr($attrs, ['ClaveProdServ', 'claveprodserv']),
            'no_identificacion' => $this->xmlAttr($attrs, ['NoIdentificacion', 'noidentificacion']),
            'cantidad' => $this->xmlAttr($attrs, ['Cantidad', 'cantidad']),
            'clave_unidad' => $this->xmlAttr($attrs, ['ClaveUnidad', 'claveunidad']),
            'unidad' => $this->xmlAttr($attrs, ['Unidad', 'unidad']),
            'descripcion' => $this->xmlAttr($attrs, ['Descripcion', 'descripcion']),
            'valor_unitario' => $this->xmlAttr($attrs, ['ValorUnitario', 'valorunitario']),
            'importe' => $this->xmlAttr($attrs, ['Importe', 'importe']),
            'descuento' => $this->xmlAttr($attrs, ['Descuento', 'descuento']),
            'objeto_impuesto' => $this->xmlAttr($attrs, ['ObjetoImp', 'objetoimp']),
            'informacion_aduanera_json' => !empty($informacionAduanera) ? $informacionAduanera : null,
            'cuenta_predial_json' => !empty($cuentaPredial) ? $cuentaPredial : null,
            'parte_json' => !empty($partes) ? $partes : null,
            'complemento_concepto_json' => null,
            'meta_json' => null,
        ]);
    }
}
private function xmlAttr($attributes, array $keys): ?string
{
    if (! $attributes) {
        return null;
    }

    foreach ($keys as $key) {
        if (isset($attributes[$key])) {
            $value = trim((string) $attributes[$key]);
            return '' === $value ? null : $value;
        }
    }

    return null;
}
}