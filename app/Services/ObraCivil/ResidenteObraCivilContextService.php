<?php

namespace App\Services\ObraCivil;

use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\User;
use App\Models\UsuarioApp;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResidenteObraCivilContextService
{
    private const TIPOS_OBRA_CIVIL = [
        'OBRA_CIVIL',
        'CIVIL',
        'obra_civil',
        'civil',
    ];

    public function resolve(User $user): ResidenteObraCivilContext
    {
        if (! $user->hasRole('residente')) {
            $this->deny('Solo el perfil residente puede usar este modulo.', 403);
        }

        $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();

        if (! $usuarioApp || ! $usuarioApp->is_active) {
            $this->deny('Este usuario no esta habilitado para la app.', 403);
        }

        $asignacion = ObraEmpleado::query()
            ->select('id', 'obra_id', 'empleado_id', 'rol_id')
            ->where('empleado_id', $usuarioApp->empleado_id)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->whereHas('obra', function ($query) {
                $query->whereNotIn('estatus_nuevo', [
                    Obra::ESTATUS_TERMINADA,
                    Obra::ESTATUS_CANCELADA,
                ]);
            })
            ->latest('id')
            ->first();

        if (! $asignacion) {
            $this->deny('No tienes una obra activa asignada.', 403);
        }

        $obra = Obra::query()
            ->with(['cliente:id,nombre_comercial'])
            ->find($asignacion->obra_id);

        if (! $obra) {
            $this->deny('No tienes una obra activa asignada.', 403);
        }

        if (! $this->isObraCivil($obra)) {
            $this->deny('La obra activa no es una obra civil.', 403);
        }

        return new ResidenteObraCivilContext($user, $usuarioApp, $obra);
    }

    private function isObraCivil(Obra $obra): bool
    {
        return in_array((string) $obra->tipo_obra, self::TIPOS_OBRA_CIVIL, true);
    }

    private function deny(string $message, int $status): void
    {
        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => $message,
        ], $status));
    }
}
