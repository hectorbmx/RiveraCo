<?php

namespace App\Services\ObraCivil;

use App\Models\Obra;
use App\Models\User;
use App\Models\UsuarioApp;

class ResidenteObraCivilContext
{
    public function __construct(
        public readonly User $user,
        public readonly UsuarioApp $usuarioApp,
        public readonly Obra $obra,
    ) {
    }

    public function empleadoId(): int
    {
        return (int) $this->usuarioApp->empleado_id;
    }
}
