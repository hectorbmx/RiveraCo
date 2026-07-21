<?php

namespace App\Http\Controllers\Telephony;

use App\Exceptions\Telephony\GrandstreamApiException;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\TelephonyCallRequest;
use App\Models\TelephonyPhoneNumber;
use App\Services\Telephony\GrandstreamClient;
use App\Services\Telephony\PhoneNumberNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TelephonyClickToCallController extends Controller
{
    public function cliente(Request $request, Cliente $cliente, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client, PhoneNumberNormalizer $normalizer)
    {
        return $this->dial($request, $cliente, $phoneNumber, $client, $normalizer);
    }

    public function proveedor(Request $request, Proveedor $proveedor, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client, PhoneNumberNormalizer $normalizer)
    {
        return $this->dial($request, $proveedor, $phoneNumber, $client, $normalizer);
    }

    public function phoneNumber(Request $request, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client, PhoneNumberNormalizer $normalizer)
    {
        abort_unless($phoneNumber->is_active, 404);

        return $this->dialPhoneNumber($request, $phoneNumber, $client, $normalizer);
    }

    private function dial(Request $request, Model $phoneable, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client, PhoneNumberNormalizer $normalizer)
    {
        abort_unless(
            $phoneNumber->phoneable_type === $phoneable::class
                && (int) $phoneNumber->phoneable_id === (int) $phoneable->getKey()
                && $phoneNumber->is_active,
            404
        );

        return $this->dialPhoneNumber($request, $phoneNumber, $client, $normalizer);
    }

    private function dialPhoneNumber(Request $request, TelephonyPhoneNumber $phoneNumber, GrandstreamClient $client, PhoneNumberNormalizer $normalizer)
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

        if (config('grandstream.mode') === 'agent') {
            $callRequest = TelephonyCallRequest::create([
                'requested_by_user_id' => $request->user()?->id,
                'phone_extension_id' => $extension->id,
                'telephony_phone_number_id' => $phoneNumber->id,
                'caller_extension' => $extension->extension,
                'outbound_number' => $outbound,
                'normalized_outbound_number' => $normalizer->normalize($outbound),
                'phoneable_type' => $phoneNumber->phoneable_type,
                'phoneable_id' => $phoneNumber->phoneable_id,
                'phoneable_name' => $phoneNumber->display_name,
                'status' => TelephonyCallRequest::STATUS_PENDING,
                'source' => 'web',
                'request_payload' => [
                    'route' => $request->route()?->getName(),
                    'server_mode' => config('grandstream.mode'),
                    'phone_number' => [
                        'id' => $phoneNumber->id,
                        'label' => $phoneNumber->label,
                        'raw_number' => $phoneNumber->raw_number,
                        'normalized_number' => $phoneNumber->normalized_number,
                    ],
                    'extension' => [
                        'id' => $extension->id,
                        'extension' => $extension->extension,
                        'fullname' => $extension->fullname,
                    ],
                ],
            ]);

            return back()->with('success', "Solicitud de llamada #{$callRequest->id} enviada al agente local: extension {$extension->extension} -> {$outbound}.");
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
