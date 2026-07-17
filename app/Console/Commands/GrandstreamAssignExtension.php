<?php

namespace App\Console\Commands;

use App\Models\PhoneExtension;
use App\Models\User;
use Illuminate\Console\Command;

class GrandstreamAssignExtension extends Command
{
    protected $signature = 'grandstream:assign-extension {extension : Extension UCM a asignar} {--user= : ID del usuario SIRICO} {--clear : Quita el usuario asignado a la extension}';

    protected $description = 'Asocia una extension Grandstream sincronizada con un usuario SIRICO';

    public function handle(): int
    {
        $extensionValue = (string) $this->argument('extension');
        $clear = (bool) $this->option('clear');
        $userId = $this->option('user');

        if (!$clear && !$userId) {
            $this->error('Debes indicar --user=ID o usar --clear.');
            return self::FAILURE;
        }

        if ($clear && $userId) {
            $this->error('Usa solo una opcion: --user=ID o --clear.');
            return self::FAILURE;
        }

        $extension = PhoneExtension::where('extension', $extensionValue)->first();

        if (!$extension) {
            $this->error("No existe la extension sincronizada {$extensionValue}. Ejecuta primero grandstream:sync-extensions.");
            return self::FAILURE;
        }

        if ($clear) {
            $previous = $extension->user_id;
            $extension->update(['user_id' => null]);

            $this->info("Extension {$extension->extension} quedo sin usuario asignado.");
            if ($previous) {
                $this->line("Usuario anterior: {$previous}");
            }

            return self::SUCCESS;
        }

        $user = User::find($userId);

        if (!$user) {
            $this->error("No existe el usuario SIRICO id={$userId}.");
            return self::FAILURE;
        }

        $previousUserId = $extension->user_id;
        $extension->update(['user_id' => $user->id]);

        $this->info("Extension {$extension->extension} asignada a usuario #{$user->id} {$user->name}.");

        if ($previousUserId && (int) $previousUserId !== (int) $user->id) {
            $this->line("Usuario anterior: {$previousUserId}");
        }

        return self::SUCCESS;
    }
}