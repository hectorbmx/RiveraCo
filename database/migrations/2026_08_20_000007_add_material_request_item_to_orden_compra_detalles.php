<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tableName = 'orden_compra_detalles';

    public function up(): void
    {
        if (! Schema::hasTable($this->tableName)) {
            return;
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            if (! Schema::hasColumn($this->tableName, 'obra_civil_material_request_item_id')) {
                $table->unsignedBigInteger('obra_civil_material_request_item_id')
                    ->nullable()
                    ->after('obra_civil_insumo_snapshot');
            }
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'obra_civil_material_request_item_id') && ! $this->indexExists('oc_det_mat_req_item_idx')) {
                $table->index('obra_civil_material_request_item_id', 'oc_det_mat_req_item_idx');
            }

            if (Schema::hasColumn($this->tableName, 'obra_civil_material_request_item_id') && ! $this->foreignKeyExists('oc_det_mat_req_item_fk')) {
                $table->foreign('obra_civil_material_request_item_id', 'oc_det_mat_req_item_fk')
                    ->references('id')
                    ->on('obra_civil_material_request_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->tableName)) {
            return;
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            if ($this->foreignKeyExists('oc_det_mat_req_item_fk')) {
                $table->dropForeign('oc_det_mat_req_item_fk');
            }

            if ($this->indexExists('oc_det_mat_req_item_idx')) {
                $table->dropIndex('oc_det_mat_req_item_idx');
            }
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'obra_civil_material_request_item_id')) {
                $table->dropColumn('obra_civil_material_request_item_id');
            }
        });
    }

    private function foreignKeyExists(string $constraintName): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $this->tableName)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $this->tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
