<?php

namespace App\Services\Facturacion;

use Facturapi\Facturapi;

class FacturapiService
{
    protected Facturapi $facturapi;

    public function __construct()
    {
        $secretKey = config('services.facturapi.secret_key');

        if (!$secretKey) {
            throw new \RuntimeException('No está configurada la llave FACTURAPI_SECRET_KEY.');
        }

        $this->facturapi = new Facturapi($secretKey);
    }

    public function client(): Facturapi
    {
        return $this->facturapi;
    }
//    public function getOrganizations()
//     {
//         return $this->facturapi->Organizations->all();
//     }
}