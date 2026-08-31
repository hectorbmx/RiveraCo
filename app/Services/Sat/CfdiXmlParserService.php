<?php

namespace App\Services\Sat;

use SimpleXMLElement;
use Throwable;

class CfdiXmlParserService
{
    /**
     * Parsea el contenido de un string o archivo XML CFDI (3.3 o 4.0).
     */
    public function parse(string $xmlContent): array
    {
        // Limpiar BOM si existe
        $cleanXml = preg_replace('/^\xEF\xBB\xBF/', '', trim($xmlContent));

        if (empty($cleanXml)) {
            throw new \InvalidArgumentException('El archivo XML está vacío.');
        }

        // Cargar XML suprimiendo warnings de libxml
        libxml_use_internal_errors(true);
        try {
            $xml = new SimpleXMLElement($cleanXml);
        } catch (Throwable $e) {
            libxml_clear_errors();
            throw new \InvalidArgumentException('El archivo no tiene un formato XML válido: ' . $e->getMessage());
        }

        $comprobanteAttrs = $xml->attributes();
        $namespaces = $xml->getNamespaces(true);
        $cfdiNs = $namespaces['cfdi'] ?? null;

        // Extraer nodos Emisor y Receptor
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

        // Extraer Timbre Fiscal Digital (UUID)
        $uuid = null;
        $fechaTimbrado = null;
        $tfdNodes = $xml->xpath('//*[local-name()="TimbreFiscalDigital"]');
        if (!empty($tfdNodes)) {
            $tfdAttrs = $tfdNodes[0]->attributes();
            $uuid = $this->attr($tfdAttrs, ['UUID', 'uuid']);
            $fechaTimbrado = $this->attr($tfdAttrs, ['FechaTimbrado', 'fechatimbrado']);
        }

        // Extraer Conceptos
        $conceptos = [];
        $conceptoNodes = $xml->xpath('//*[local-name()="Conceptos"]/*[local-name()="Concepto"]');
        foreach ($conceptoNodes as $node) {
            $attrs = $node->attributes();
            $conceptos[] = [
                'clave_prod_serv' => $this->attr($attrs, ['ClaveProdServ', 'claveprodserv']),
                'no_identificacion' => $this->attr($attrs, ['NoIdentificacion', 'noidentificacion']),
                'descripcion' => $this->attr($attrs, ['Descripcion', 'descripcion']),
                'cantidad' => (float) $this->attr($attrs, ['Cantidad', 'cantidad']),
                'unidad' => $this->attr($attrs, ['Unidad', 'unidad', 'ClaveUnidad', 'claveunidad']),
                'valor_unitario' => (float) $this->attr($attrs, ['ValorUnitario', 'valorunitario']),
                'importe' => (float) $this->attr($attrs, ['Importe', 'importe']),
            ];
        }

        // Concatenar descripción general si hay conceptos
        $descripciones = array_filter(array_column($conceptos, 'descripcion'));
        $conceptoGeneral = !empty($descripciones) ? implode('; ', array_slice($descripciones, 0, 3)) : 'Gasto con factura';
        if (count($descripciones) > 3) {
            $conceptoGeneral .= ' (y más...)';
        }

        // Extraer forma de pago
        $formaPagoSat = $this->attr($comprobanteAttrs, ['FormaPago', 'formapago']);
        $formaPagoMapeada = $this->mapearFormaPago($formaPagoSat);

        // Fecha del comprobante (YYYY-MM-DD)
        $fechaEmisionRaw = $this->attr($comprobanteAttrs, ['Fecha', 'fecha']);
        $fechaEmision = $fechaEmisionRaw ? substr($fechaEmisionRaw, 0, 10) : date('Y-m-d');

        return [
            'uuid' => $uuid,
            'version' => $this->attr($comprobanteAttrs, ['Version', 'version']),
            'serie' => $this->attr($comprobanteAttrs, ['Serie', 'serie']),
            'folio' => $this->attr($comprobanteAttrs, ['Folio', 'folio']),
            'fecha' => $fechaEmision,
            'fecha_timbrado' => $fechaTimbrado,
            'emisor_rfc' => strtoupper((string) $this->attr($emisorAttrs, ['Rfc', 'RFC', 'rfc'])),
            'emisor_nombre' => (string) $this->attr($emisorAttrs, ['Nombre', 'nombre']),
            'receptor_rfc' => strtoupper((string) $this->attr($receptorAttrs, ['Rfc', 'RFC', 'rfc'])),
            'receptor_nombre' => (string) $this->attr($receptorAttrs, ['Nombre', 'nombre']),
            'subtotal' => (float) $this->attr($comprobanteAttrs, ['SubTotal', 'Subtotal', 'subTotal']),
            'descuento' => (float) $this->attr($comprobanteAttrs, ['Descuento', 'descuento']),
            'total' => (float) $this->attr($comprobanteAttrs, ['Total', 'total']),
            'moneda' => $this->attr($comprobanteAttrs, ['Moneda', 'moneda']) ?: 'MXN',
            'forma_pago_sat' => $formaPagoSat,
            'forma_pago' => $formaPagoMapeada,
            'metodo_pago' => $this->attr($comprobanteAttrs, ['MetodoPago', 'metodopago']),
            'concepto' => $conceptoGeneral,
            'conceptos' => $conceptos,
        ];
    }

    private function mapearFormaPago(?string $satFormaPago): string
    {
        return match ($satFormaPago) {
            '01' => 'efectivo',
            '04', '28', '05', '06' => 'tarjeta',
            '03' => 'transferencia',
            default => 'desconocido',
        };
    }

    private function attr($attributes, array $keys): ?string
    {
        if (!$attributes) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($attributes[$key])) {
                $val = trim((string) $attributes[$key]);
                return $val !== '' ? $val : null;
            }
        }

        return null;
    }
}

