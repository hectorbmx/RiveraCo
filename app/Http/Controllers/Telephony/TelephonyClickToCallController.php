<?php

namespace App\Http\Controllers\Telephony;

use App\Exceptions\Telephony\GrandstreamApiException;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\TelephonyPhoneNumber;
use App\Services\Telephony\GrandstreamClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TelephonyClickToCallController extends Controller
{
    public function cliente(Request $request, Cliente $cliente, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client)
    {
        return $this->dial($request, $cliente, $phoneNumber, $client);
    }

    public function proveedor(Request $request, Proveedor $proveedor, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client)
    {
        return $this->dial($request, $proveedor, $phoneNumber, $client);
    }

    public function phoneNumber(Request $request, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client)
    {
        abort_unless($phoneNumber->is_active, 404);

        return $this->dialPhoneNumber($request, $phoneNumber, $client);
    }

    private function dial(Request $request, Model $phoneable, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client)
    {
        abort_unless(
            $phoneNumber->phoneable_type === $phoneable::class
                && (int) $phoneNumber->phoneable_id === (int) $phoneable->getKey()
                && $phoneNumber->is_active,
            404
        );

        return $this->dialPhoneNumber($request, $phoneNumber, $client);
    }

    private function dialPhoneNumber(Request $request, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client)
    {
        $extension = $request->user()?->phoneExtensions()
            ->where(function ($query) {
                $query->whereNull('out_of_service')
                    ->orWhere('out_of_service', false);
            })
            ->orderBy('extension')
            ->first();

        if (!$extension) {
            return back()->with('error', 'Tu usuario no tiene una extension telefonica activa asignada. Asignala en Telefonia > Extensiones.');
        }

        $outbound = trim((string) $phoneNumber->raw_number);

        if ($outbound === '') {
            return back()->with('error', 'El telefono seleccionado no tiene numero marcado disponible.');
        }

        try {
            $response = $client->dialOutbound($extension->extension, $outbound);

            $needApply = Arr::get($response, 'response.need_apply', Arr::get($response, 'need_apply'));
            $message = "Llamada enviada: extension {$extension->extension} -> {$outbound}.";
            if ($needApply !== null) {
                $message .= " need_apply: {$needApply}.";
            }

            return back()->with('success', $message);
        } catch (GrandstreamApiException $e) {
            $message = 'No se pudo iniciar la llamada desde el UCM. ' . $e->getMessage();
            if ($e->statusCode() !== null) {
                $message .= ' Status/API code: ' . $e->statusCode() . '.';
            }

            return back()->with('error', $message);
        }
    }
}