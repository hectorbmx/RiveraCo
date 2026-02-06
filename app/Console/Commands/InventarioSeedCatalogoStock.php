<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\Almacen;

class InventarioSeedCatalogoStock extends Command
{
    protected $signature = 'inventario:seed-catalogo-stock
                            {almacen_id : ID del almacén}
                            {--only-missing : Solo crea filas faltantes en inventario_stock}
                            {--chunk=1000 : Tamaño de chunk}';

    protected $description = 'Crea filas en inventario_stock (en cero) para todos los productos en un almacén, sin documentos ni movimientos.';

    public function handle(): int
    {
        $almacenId    = (int) $this->argument('almacen_id');
        $onlyMissing  = (bool) $this->option('only-missing');
        $chunk        = (int) $this->option('chunk');

        $almacen = Almacen::find($almacenId);
        if (!$almacen) {
            $this->error("No existe almacén con id={$almacenId}");
            return self::FAILURE;
        }

        $this->info("Almacén: {$almacen->nombre} (id={$almacen->id})");
        $this->info("Modo: " . ($onlyMissing ? 'only-missing' : 'forzar (upsert)'));
        $this->info("chunk={$chunk}");

        $q = Producto::query()->select(['id']);

        if ($onlyMissing) {
            $q->whereNotExists(function ($sub) use ($almacenId) {
                $sub->selectRaw('1')
                    ->from('inventario_stock')
                    ->whereColumn('inventario_stock.producto_id', 'productos.id')
                    ->where('inventario_stock.almacen_id', $almacenId);
            });
        }

        $total = (clone $q)->count();
        if ($total === 0) {
            $this->warn('No hay productos para procesar (total=0).');
            return self::SUCCESS;
        }

        $this->info("Productos a procesar: {$total}");

        $procesados = 0;

        $q->orderBy('id')->chunkById($chunk, function ($productos) use ($almacenId, &$procesados, $onlyMissing) {
            $now = now();

            $rows = [];
            foreach ($productos as $p) {
                $rows[] = [
                    'almacen_id'  => $almacenId,
                    'producto_id' => $p->id,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                    // Los demás campos se quedan en defaults (0) definidos en DB.
                ];
            }

            DB::transaction(function () use ($rows, $onlyMissing) {
                if ($onlyMissing) {
                    DB::table('inventario_stock')->insert($rows);
                } else {
                    // Si no es only-missing, hacemos upsert por seguridad (unique almacen_id+producto_id)
                    DB::table('inventario_stock')->upsert(
                        $rows,
                        ['almacen_id', 'producto_id'],
                        ['updated_at']
                    );
                }
            });

            $procesados += count($rows);
        });

        $this->info("Listo. Filas procesadas: {$procesados}");
        $this->line("Revisa inventario_stock (almacen_id={$almacenId}).");

        return self::SUCCESS;
    }
}
