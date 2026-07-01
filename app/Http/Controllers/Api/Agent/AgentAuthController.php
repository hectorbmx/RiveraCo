<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AgentAuthController extends Controller
{
    /**
     * Iniciar sesion desde el agente y obtener Bearer Token.
     */
    public function login(Request $request)
    {
        $email = $request->input('email', $request->input('e'));
        $secret = $request->input('password', $request->input('password_b64', $request->input('s')));
        $secretIsEncoded = $request->filled('password_b64') || $request->filled('s');

        validator([
            'email' => $email,
            'secret' => $secret,
        ], [
            'email' => ['required', 'email'],
            'secret' => ['required', 'string'],
        ])->validate();

        $password = (string) $secret;
        if ($secretIsEncoded) {
            $decodedPassword = base64_decode($password, true);
            if ($decodedPassword === false) {
                throw ValidationException::withMessages([
                    'secret' => ['La credencial enviada por el agente no tiene un formato valido.'],
                ]);
            }

            $password = $decodedPassword;
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check((string) $password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales invalidas.'],
            ]);
        }

        // Generar el token de Sanctum
        $token = $user->createToken('sirico-agent')->plainTextToken;

        return response()->json([
            'ok' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Cerrar sesion del agente (revocar token actual).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Sesion cerrada correctamente.'
        ]);
    }
}

