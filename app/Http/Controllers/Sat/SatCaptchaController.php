<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatCaptchaSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SatCaptchaController extends Controller
{
    // La UI hace polling aquí hasta que appeared la imagen
    public function image(string $token)
    {
        $session = SatCaptchaSession::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return response()->json(['available' => false]);
        }

        return response()->json([
            'available' => true,
            'image'     => $session->image_inline_html,
        ]);
    }

    // El usuario envía su respuesta aquí
    public function submit(Request $request, string $token)
    {
        $data = $request->validate([
            'answer' => ['required', 'string', 'max:50'],
        ]);

        $updated = SatCaptchaSession::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('answered', false)
            ->update([
                'answer'   => trim($data['answer']),
                'answered' => true,
            ]);

        if (!$updated) {
            return response()->json(
                ['error' => 'Sesión de captcha no encontrada o expirada'], 
                404
            );
        }

        return response()->json(['ok' => true]);
    }
}