<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tableName = 'obra_civil_material_request_order_links';

    public function up(): void
    {
        if (! Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('obra_civil_material_request_id');
                $table->unsignedBigInteger('orden_compra_id');
                $table->string('status', 40)->default('borrador');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            if (! $this->indexExists('oc_mat_req_oc_link_unique')) {
                $table->unique(['obra_civil_material_request_id', 'orden_compra_id'], 'oc_mat_req_oc_link_unique');
            }

            if (! $this->indexExists('oc_mat_req_oc_link_status_idx')) {
                $table->index('status', 'oc_mat_req_oc_link_status_idx');
            }

            if (! $this->indexExists('oc_mat_req_oc_link_created_by_idx')) {
                $table->index('created_by', 'oc_mat_req_oc_link_created_by_idx');
            }

            if (! $this->foreignKeyExists('oc_mat_req_oc_link_req_fk')) {
                $table->foreign('obra_civil_material_request_id', 'oc_mat_req_oc_link_req_fk')
                    ->references('id')
                    ->on('obra_civil_material_requests')
                    ->cascadeOnDelete();
            }

            if (! $this->foreignKeyExists('oc_mat_req_oc_link_oc_fk')) {
                $table->foreign('orden_compra_id', 'oc_mat_req_oc_link_oc_fk')
                    ->references('id')
                    ->on('ordenes_compra')
                    ->cascadeOnDelete();
            }

            if (! $this->foreignKeyExists('oc_mat_req_oc_link_user_fk')) {
                $table->foreign('created_by', 'oc_mat_req_oc_link_user_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
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
