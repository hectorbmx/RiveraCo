<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Empleado;
use App\Models\UsuarioApp;

class UsuariosAppSeeder extends Seeder
{
    public function run(): void
    {
        $empleadoId = 666;
        $email = 'pruebas@gmail.com';
        $passwordPlano = 'Rivera1234*'; // temporal para pruebas

        $empleado = Empleado::where('id_Empleado', $empleadoId)->first();

        if (!$empleado) {
            $this->command->error("Empleado {$empleadoId} no existe en empleados.id_Empleado");
            return;
        }

        // 1) crear/obtener user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => trim(($empleado->Nombre ?? 'Empleado').' '.($empleado->ApellidoP ?? '')),
                'password' => Hash::make($passwordPlano),
            ]
        );

        // 2) asegurar rol spatie
        if (!$user->hasRole('residente')) {
            $user->assignRole('residente');
        }

        // 3) crear/actualizar perfil app
        UsuarioApp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'empleado_id'  => $empleado->id_Empleado,
                'is_active'    => true,
                'activated_at' => now(),
            ]
        );

        $this->command->info("OK: User {$email} vinculado a empleado {$empleadoId} y habilitado en usuarios_app.");
    }
}
