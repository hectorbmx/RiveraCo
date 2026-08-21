<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tableName = 'obra_civil_material_request_items';

    public function up(): void
    {
        if (! Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('obra_civil_material_request_id');
                $table->unsignedBigInteger('obra_civil_insumo_id');
                $table->decimal('quantity', 15, 4);
                $table->string('unit', 50)->nullable();
                $table->json('insumo_snapshot')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            if (! $this->foreignKeyExists('oc_mat_req_items_request_fk')) {
                $table->foreign('obra_civil_material_request_id', 'oc_mat_req_items_request_fk')
                    ->references('id')
                    ->on('obra_civil_material_requests')
                    ->cascadeOnDelete();
            }

            if (! $this->foreignKeyExists('oc_mat_req_items_insumo_fk')) {
                $table->foreign('obra_civil_insumo_id', 'oc_mat_req_items_insumo_fk')
                    ->references('id')
                    ->on('obra_civil_insumos')
                    ->restrictOnDelete();
            }

            if (! $this->indexExists('oc_mat_req_items_insumo_idx')) {
                $table->index('obra_civil_insumo_id', 'oc_mat_req_items_insumo_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
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
