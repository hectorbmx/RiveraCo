<?php

namespace App\Services\Mobile;

use App\Models\User;
use App\Models\UsuarioApp;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppMobileAccessService
{
    public function __construct(private UsuarioApp $usuariosApp)
    {
    }

    public function usuarioActivo(User $user): UsuarioApp
    {
        $usuarioApp = $this->usuariosApp->newQuery()->where('user_id', $user->id)->first();

        if (! $usuarioApp) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Este usuario no esta habilitado para la app.',
            ], 403));
        }

        if (! $usuarioApp->is_active) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Tu acceso a la app esta desactivado.',
            ], 403));
        }

        return $usuarioApp;
    }
}
