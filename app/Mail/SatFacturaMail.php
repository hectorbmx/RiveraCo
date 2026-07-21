<?php

namespace App\Mail;

use App\Models\SatFactura;
use App\Models\SatFacturaPago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SatFacturaMail extends Mailable
{
    use Queueable, SerializesModels;

    public SatFactura $factura;
    public ?SatFacturaPago $pago;

    public function __construct(SatFactura $factura, ?SatFacturaPago $pago = null)
    {
        $this->factura = $factura;
        $this->pago = $pago;
    }

    public function build()
    {
        $esComplemento = $this->pago !== null;
        $documento = $this->pago ?? $this->factura;
        $uuid = $documento->uuid ?? $documento->id;
        $prefijo = $esComplemento ? 'complemento-pago' : 'factura';

        $mail = $this->from(
                config('services.facturacion_mail.from_address', config('mail.from.address')),
                config('services.facturacion_mail.from_name', config('mail.from.name'))
            )
            ->subject($esComplemento
                ? 'Complemento de pago CFDI ' . $uuid
                : 'Factura CFDI ' . $this->factura->serie . '-' . $this->factura->folio
            )
            ->view('emails.sat.factura')
            ->with([
                'factura' => $this->factura,
                'pago' => $this->pago,
                'esComplemento' => $esComplemento,
            ]);

        if ($documento->xml_path && Storage::disk('local')->exists($documento->xml_path)) {
            $mail->attach(
                Storage::disk('local')->path($documento->xml_path),
                [
                    'as' => $prefijo . '-' . $uuid . '.xml',
                    'mime' => 'application/xml',
                ]
            );
        }

        if ($documento->pdf_path && Storage::disk('local')->exists($documento->pdf_path)) {
            $mail->attach(
                Storage::disk('local')->path($documento->pdf_path),
                [
                    'as' => $prefijo . '-' . $uuid . '.pdf',
                    'mime' => 'application/pdf',
                ]
            );
        }

        return $mail;
    }
}
