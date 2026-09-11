<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $testEmails = [
            'hectorbmx@gmail.com' => 'Héctor',
            'pruebas@gmail.com' => 'Residente de prueba',
        ];

        foreach ($testEmails as $email => $name) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => bcrypt('12345678'),
                ]
            );

            if (!$user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
            }
        }
    }
}