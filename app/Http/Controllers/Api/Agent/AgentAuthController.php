<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
        $computerName = $request->input('computer_name', $request->input('agent.computer_name'));
        $deviceUuid = $request->input('device_uuid', $request->input('device_id'));
        $rememberWebSession = $this->booleanPreference($request, 'remember_web_session', true);
        $openNotificationsInBrowser = $this->booleanPreference($request, 'open_notifications_in_browser', true);
        $notificationClickBehavior = $request->input('notification_click_behavior', $request->input('preferences.notification_click_behavior', 'open_detail'));
        $trustedUntil = $request->input('trusted_until', $request->input('preferences.trusted_until'));

        validator([
            'email' => $email,
            'secret' => $secret,
            'device_uuid' => $deviceUuid,
            'computer_name' => $computerName,
            'remember_web_session' => $rememberWebSession,
            'open_notifications_in_browser' => $openNotificationsInBrowser,
            'notification_click_behavior' => $notificationClickBehavior,
            'trusted_until' => $trustedUntil,
        ], [
            'email' => ['required', 'email'],
            'secret' => ['required', 'string'],
            'device_uuid' => ['nullable', 'string', 'max:100'],
            'computer_name' => ['nullable', 'string', 'max:255'],
            'remember_web_session' => ['boolean'],
            'open_notifications_in_browser' => ['boolean'],
            'notification_click_behavior' => ['required', Rule::in(['open_detail', 'mark_read_only', 'disabled'])],
            'trusted_until' => ['nullable', 'date'],
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

        $computerName = $computerName ?: $request->userAgent() ?: 'unknown-agent';
        $deviceUuid = $deviceUuid ?: 'legacy-' . substr(hash('sha256', implode('|', [
            $user->id,
            $computerName,
            $request->ip() ?: 'unknown-ip',
        ])), 0, 57);

        $plainTextToken = null;

        $agentDevice = DB::transaction(function () use ($user, $deviceUuid, $computerName, $rememberWebSession, $openNotificationsInBrowser, $notificationClickBehavior, $trustedUntil, &$plainTextToken) {
            $user->tokens()
                ->where('name', 'sirico-agent')
                ->delete();

            AgentDevice::where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update([
                    'is_default' => false,
                    'revoked_at' => now(),
                ]);

            $newAccessToken = $user->createToken('sirico-agent');
            $plainTextToken = $newAccessToken->plainTextToken;

            return AgentDevice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_uuid' => $deviceUuid,
                ],
                [
                    'computer_name' => $computerName,
                    'token_id' => $newAccessToken->accessToken->id,
                    'is_default' => true,
                    'remember_web_session' => $rememberWebSession,
                    'open_notifications_in_browser' => $openNotificationsInBrowser,
                    'notification_click_behavior' => $notificationClickBehavior,
                    'trusted_until' => $trustedUntil,
                    'last_seen_at' => now(),
                    'revoked_at' => null,
                ]
            )->fresh();
        });

        return response()->json([
            'ok' => true,
            'token' => $plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'device' => [
                'id' => $agentDevice->id,
                'device_uuid' => $agentDevice->device_uuid,
                'computer_name' => $agentDevice->computer_name,
                'is_default' => $agentDevice->is_default,
                'remember_web_session' => $agentDevice->remember_web_session,
                'open_notifications_in_browser' => $agentDevice->open_notifications_in_browser,
                'notification_click_behavior' => $agentDevice->notification_click_behavior,
                'trusted_until' => $agentDevice->trusted_until?->toDateTimeString(),
                'last_seen_at' => $agentDevice->last_seen_at?->toDateTimeString(),
            ],
        ]);
    }

    private function booleanPreference(Request $request, string $key, bool $default): bool
    {
        if ($request->has($key)) {
            return $request->boolean($key);
        }

        $nestedKey = "preferences.{$key}";
        if ($request->has($nestedKey)) {
            return $request->boolean($nestedKey);
        }

        return $default;
    }

    /**
     * Cerrar sesion del agente (revocar token actual).
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            AgentDevice::where('token_id', $token->id)
                ->whereNull('revoked_at')
                ->update([
                    'is_default' => false,
                    'revoked_at' => now(),
                ]);

            $token->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Sesion cerrada correctamente.'
        ]);
    }
}
