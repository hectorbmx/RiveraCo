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
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['nullable', 'string', 'required_without:password_b64'],
            'password_b64' => ['nullable', 'string', 'required_without:password'],
        ]);

        $password = $request->input('password');
        if (!$password && $request->filled('password_b64')) {
            $decodedPassword = base64_decode((string) $request->input('password_b64'), true);
            if ($decodedPassword === false) {
                throw ValidationException::withMessages([
                    'password' => ['La contrasena enviada por el agente no tiene un formato valido.'],
                ]);
            }

            $password = $decodedPassword;
        }

        $user = User::where('email', $request->email)->first();

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

