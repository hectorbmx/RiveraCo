<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\InventarioDocumento;
use App\Models\InventarioDocumentoDetalle;
use App\Services\Inventario\InventarioDocumentoService;

class InventarioSeedInicial extends Command
{
    protected $signature = 'inventario:seed-inicial
                            {almacen_id : ID del almacén}
                            {--only-missing : Solo crea stock para productos que NO tengan fila en inventario_stock}
                            {--qty=0 : Cantidad inicial default}
                            {--cost=0 : Costo unitario default}
                            {--chunk=500 : Tamaño de chunk}';

    protected $description = 'Crea y aplica un documento de entrada inicial para poblar inventario_stock de forma consistente.';

    public function handle(): int
    {
        $almacenId = (int) $this->argument('almacen_id');
        $onlyMissing = (bool) $this->option('only-missing');
        $qtyDefault = (float) $this->option('qty');
        $costDefault = (float) $this->option('cost');
        $chunk = (int) $this->option('chunk');

        $almacen = Almacen::find($almacenId);
        if (!$almacen) {
            $this->error("No existe almacén con id={$almacenId}");
            return self::FAILURE;
        }

        $this->info("Almacén: {$almacen->nombre} (id={$almacen->id})");

        // Construye query de productos
        $q = Producto::query()->select(['id', 'nombre']);

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
            $this->warn("No hay productos para procesar (total=0).");
            return self::SUCCESS;
        }

        $this->info("Productos a procesar: {$total}");
        $this->info("qty_default={$qtyDefault} | cost_default={$costDefault} | chunk={$chunk}");
        $this->line("Crearemos 1 documento por chunk y lo aplicaremos.");

        $procesados = 0;

        $q->orderBy('id')->chunkById($chunk, function ($productos) use (
            $almacenId, $qtyDefault, $costDefault, &$procesados
        ) {
            DB::transaction(function () use ($productos, $almacenId, $qtyDefault, $costDefault, &$procesados) {

                $doc = InventarioDocumento::create([
                    'tipo'        => 'inicial',
                    'almacen_id'  => $almacenId,
                    'fecha'       => now()->toDateString(),
                    // 'referencia'  => 'Carga inicial por comando',
                    'notas'       => 'Carga inicial por comando',
                    // 'aplicado_at' => null,
                    // si tienes user_id o creado_por, agrega aquí
                ]);

                foreach ($productos as $p) {
                    // Si qty_default=0, esto creará filas con 0 (no afecta stock, pero crea fila al aplicar).
                    // Si quieres crear solo con qty>0, cambia la condición.
                    InventarioDocumentoDetalle::create([
                        'documento_id' => $doc->id,
                        // 'inventario_documento_id' => $doc->id,
                        'producto_id'             => $p->id,
                        'cantidad'                => $qtyDefault,
                        'costo_unitario'          => $costDefault,
                        // ajusta nombres si tu tabla usa otros campos: costo, importe, etc.
                    ]);
                }

                // APLICAR => aquí se generan movimientos y stock
                // InventarioDocumentoService::aplicar($doc);
                app(InventarioDocumentoService::class)->aplicar($doc);
                

                $procesados += $productos->count();
            });
        });

        $this->info("Listo. Productos procesados: {$procesados}");
        $this->line("Revisa inventario_stock y inventario_movimientos.");
        return self::SUCCESS;
    }
}
