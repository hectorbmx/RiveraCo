<?php

namespace App\Console\Commands;

use App\Models\Seguro;
use App\Models\User;
use App\Notifications\SeguroVehiculoVencimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckInsuranceExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-insurance-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for insurance policies about to expire and notify administrators';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking insurance expirations...');

        // Buscar seguros que venzan en los próximos 15 días, con alertas activas y no alertados hoy
        $seguros = Seguro::where('alerta_vencimiento_activa', true)
            ->where('vigencia_hasta', '<=', now()->addDays(15))
            ->where('vigencia_hasta', '>=', now())
            ->where(function($query) {
                $query->whereNull('ultima_alerta_enviada_at')
                      ->orWhere('ultima_alerta_enviada_at', '<', now()->startOfDay());
            })
            ->get();

        if ($seguros->isEmpty()) {
            $this->info('No insurance policies near expiration found.');
            return;
        }

        $admins = User::role('administrador')->get();

        if ($admins->isEmpty()) {
            $this->error('No administrators found to notify.');
            return;
        }

        foreach ($seguros as $seguro) {
            Notification::send($admins, new SeguroVehiculoVencimiento($seguro));
            
            $seguro->update([
                'ultima_alerta_enviada_at' => now()
            ]);

            $this->info("Notification sent for policy: {$seguro->poliza_numero}");
        }

        $this->info('Done!');
    }
}
