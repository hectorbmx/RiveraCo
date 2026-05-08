<?php

namespace App\Mail;

use App\Models\SatFactura;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SatFacturaMail extends Mailable
{
    use Queueable, SerializesModels;

    public SatFactura $factura;

    public function __construct(SatFactura $factura)
    {
        $this->factura = $factura;
    }

    public function build()
    {
        $mail = $this->subject('Factura CFDI ' . $this->factura->serie . '-' . $this->factura->folio)
            ->view('emails.sat.factura')
            ->with([
                'factura' => $this->factura,
            ]);

        if ($this->factura->xml_path && Storage::disk('local')->exists($this->factura->xml_path)) {
            $mail->attach(
                Storage::disk('local')->path($this->factura->xml_path),
                [
                    'as' => 'factura-' . $this->factura->uuid . '.xml',
                    'mime' => 'application/xml',
                ]
            );
        }

        if ($this->factura->pdf_path && Storage::disk('local')->exists($this->factura->pdf_path)) {
            $mail->attach(
                Storage::disk('local')->path($this->factura->pdf_path),
                [
                    'as' => 'factura-' . $this->factura->uuid . '.pdf',
                    'mime' => 'application/pdf',
                ]
            );
        }

        return $mail;
    }
}