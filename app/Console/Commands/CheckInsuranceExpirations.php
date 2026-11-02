<?php

namespace App\Console\Commands;

use App\Models\Seguro;
use App\Models\User;
use App\Models\EmpresaAlertaDestinatario;
use App\Models\EmpresaConfig;
use App\Models\Maquina;
use App\Models\Vehiculo;
use App\Notifications\SeguroVehiculoVencimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class CheckInsuranceExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-insurance-expirations {--dry-run : Muestra los avisos sin enviarlos}';

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
            ->whereDate('vigencia_hasta', '<=', now()->addDays(15)->toDateString())
            ->whereDate('vigencia_hasta', '>=', now()->toDateString())
            ->where(function($query) {
                $query->whereNull('ultima_alerta_enviada_at')
                      ->orWhere('ultima_alerta_enviada_at', '<', now()->startOfDay());
            })
            ->get();

        if ($seguros->isEmpty()) {
            $this->info('No insurance policies near expiration found.');
            return;
        }

        $admins = Role::where('name', 'administrador')->where('guard_name', 'web')->exists()
            ? User::role('administrador')->get() : collect();
        $config = EmpresaConfig::first();
        $configurados = $config ? EmpresaAlertaDestinatario::query()
            ->with('user')->where('empresa_config_id', $config->id)->modulo('seguros')
            ->where('activo', true)->where('notificar_sistema', true)->get()
            ->pluck('user')->filter() : collect();
        if ($admins->isEmpty() && $configurados->isEmpty()) {
            $this->warn('No hay destinatarios activos para alertas de seguros.');
            return self::SUCCESS;
        }

        foreach ($seguros as $seguro) {
            $destinatarios = $admins;
            if ($seguro->asegurable instanceof Maquina || $seguro->asegurable instanceof Vehiculo) {
                $destinatarios = $destinatarios->concat($configurados)->unique('id')->values();
            }
            if ($destinatarios->isEmpty()) {
                continue;
            }
            if ($this->option('dry-run')) {
                $this->line("Seguro {$seguro->id}: {$seguro->poliza_numero} ({$destinatarios->count()} destinatarios)");
                continue;
            }
            Notification::send($destinatarios, new SeguroVehiculoVencimiento($seguro));
            
            $seguro->update([
                'ultima_alerta_enviada_at' => now()
            ]);

            $this->info("Notification sent for policy: {$seguro->poliza_numero}");
        }

        $this->info('Done!');
        return self::SUCCESS;
    }
}
