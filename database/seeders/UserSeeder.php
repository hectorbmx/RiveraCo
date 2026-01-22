<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si existe un usuario admin
        $user = User::firstOrCreate(
            ['email' => 'pruebas@gmail.com'],
            [
                'name' => 'Residente de prueba',
                'password' => bcrypt('12345678'), // cámbiala luego
            ]
        );

        // Asignar rol super-admin
        if (!$user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }
    }
}