<?php

namespace App\Console\Commands;

use App\Models\EmpresaAlertaDestinatario;
use App\Models\EmpresaConfig;
use App\Models\Maquina;
use App\Notifications\MaquinaServicioPreventivoNotification;
use App\Services\Maquinas\PreventivoMaquinaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MaquinasAlertasPreventivo extends Command
{
    protected $signature = 'maquinas:alertas-preventivo {--dry-run : Muestra alertas sin enviarlas}';
    protected $description = 'Notifica preventivos próximos o vencidos de maquinaria.';

    public function handle(PreventivoMaquinaService $service): int
    {
        $config = EmpresaConfig::first();
        if (! $config) {
            $this->warn('No hay configuración de empresa.');
            return self::SUCCESS;
        }
        $usuarios = EmpresaAlertaDestinatario::query()->with('user')
            ->where('empresa_config_id', $config->id)->modulo('maquinaria')
            ->where('activo', true)->where('notificar_sistema', true)->get()
            ->pluck('user')->filter()->unique('id')->values();
        if ($usuarios->isEmpty()) {
            $this->warn('No hay destinatarios activos para alertas de maquinaria.');
            return self::SUCCESS;
        }
        $maquinas = Maquina::query()->where(function ($query) {
            $query->whereNull('estado')->orWhere('estado', '!=', Maquina::ESTADO_BAJA_DEFINITIVA);
        })->orderBy('id')->get();
        $resultados = $service->calcularParaColeccion($maquinas, $config);
        $enviadas = 0;
        foreach ($maquinas as $maquina) {
            foreach ($resultados[$maquina->id]['servicios'] ?? [] as $preventivo) {
                if (! in_array($preventivo['estado'], ['proximo', 'vencido'], true)) {
                    continue;
                }
                $this->line("{$maquina->codigo}: {$preventivo['servicio_nombre']} - {$preventivo['label']}");
                if ($this->option('dry-run')) {
                    continue;
                }
                foreach ($usuarios as $usuario) {
                    $enviadas += DB::transaction(function () use ($usuario, $maquina, $preventivo) {
                        $insertado = DB::table('maquina_alerta_entregas')->insertOrIgnore([
                            'maquina_id' => $maquina->id,
                            'user_id' => $usuario->id,
                            'servicio_clave' => (string) ($preventivo['servicio_id'] ?? 'legacy'),
                            'fecha_alerta' => now()->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        if ($insertado) {
                            $usuario->notify(new MaquinaServicioPreventivoNotification($maquina, $preventivo));
                        }
                        return $insertado;
                    });
                }
            }
        }
        $this->info($this->option('dry-run') ? 'Simulación: no se enviaron alertas.' : "Notificaciones enviadas: {$enviadas}.");
        return self::SUCCESS;
    }
}
