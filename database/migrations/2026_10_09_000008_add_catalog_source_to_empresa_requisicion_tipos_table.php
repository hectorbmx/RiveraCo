<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_requisicion_tipos', function (Blueprint $table) {
            $table->string('origen_catalogo', 40)->default('manual')->after('descripcion');
            $table->string('item_type', 40)->default('manual')->after('origen_catalogo');
            $table->foreignId('almacen_id')->nullable()->after('item_type')->constrained('almacenes')->nullOnDelete();
            $table->boolean('requiere_catalogo')->default(false)->after('almacen_id');
            $table->boolean('permite_manual')->default(true)->after('requiere_catalogo');

            $table->index(['origen_catalogo', 'item_type'], 'empresa_req_tipos_origen_item_index');
            $table->index('almacen_id', 'empresa_req_tipos_almacen_index');
        });

        $almacenGiraldaId = DB::table('almacenes')
            ->where(function ($query) {
                $query->where('codigo', 'like', '%GIRALDA%')
                    ->orWhere('nombre', 'like', '%GIRALDA%');
            })
            ->value('id');

        $updates = [
            'personal' => [
                'origen_catalogo' => 'giralda',
                'item_type' => 'empleado',
                'almacen_id' => null,
                'requiere_catalogo' => true,
                'permite_manual' => true,
            ],
            'maquinaria' => [
                'origen_catalogo' => 'maquinaria',
                'item_type' => 'maquina',
                'almacen_id' => null,
                'requiere_catalogo' => true,
                'permite_manual' => true,
            ],
            'herramienta' => [
                'origen_catalogo' => 'giralda',
                'item_type' => 'herramienta',
                'almacen_id' => $almacenGiraldaId,
                'requiere_catalogo' => true,
                'permite_manual' => true,
            ],
            'material' => [
                'origen_catalogo' => 'giralda',
                'item_type' => 'producto',
                'almacen_id' => $almacenGiraldaId,
                'requiere_catalogo' => true,
                'permite_manual' => true,
            ],
            'otro' => [
                'origen_catalogo' => 'manual',
                'item_type' => 'manual',
                'almacen_id' => null,
                'requiere_catalogo' => false,
                'permite_manual' => true,
            ],
        ];

        foreach ($updates as $codigo => $data) {
            DB::table('empresa_requisicion_tipos')
                ->where('codigo', $codigo)
                ->update(array_merge($data, ['updated_at' => now()]));
        }
    }

    public function down(): void
    {
        Schema::table('empresa_requisicion_tipos', function (Blueprint $table) {
            $table->dropIndex('empresa_req_tipos_origen_item_index');
            $table->dropIndex('empresa_req_tipos_almacen_index');
            $table->dropConstrainedForeignId('almacen_id');
            $table->dropColumn([
                'origen_catalogo',
                'item_type',
                'requiere_catalogo',
                'permite_manual',
            ]);
        });
    }
};
