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
            if (! Schema::hasColumn($this->tableName, 'precio_tope')) {
                $table->decimal('precio_tope', 15, 4)
                    ->nullable()
                    ->after('precio_unitario');
            }

            if (! Schema::hasColumn($this->tableName, 'sobreprecio_autorizado_por')) {
                $table->unsignedBigInteger('sobreprecio_autorizado_por')
                    ->nullable()
                    ->after('precio_tope');
            }

            if (! Schema::hasColumn($this->tableName, 'sobreprecio_autorizado_at')) {
                $table->timestamp('sobreprecio_autorizado_at')
                    ->nullable()
                    ->after('sobreprecio_autorizado_por');
            }

            if (! Schema::hasColumn($this->tableName, 'sobreprecio_autorizacion_motivo')) {
                $table->text('sobreprecio_autorizacion_motivo')
                    ->nullable()
                    ->after('sobreprecio_autorizado_at');
            }
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            if (
                Schema::hasColumn($this->tableName, 'sobreprecio_autorizado_por')
                && ! $this->indexExists('oc_det_sobreprecio_aut_por_idx')
            ) {
                $table->index('sobreprecio_autorizado_por', 'oc_det_sobreprecio_aut_por_idx');
            }

            if (
                Schema::hasColumn($this->tableName, 'sobreprecio_autorizado_por')
                && ! $this->foreignKeyExists('oc_det_sobreprecio_aut_por_fk')
            ) {
                $table->foreign('sobreprecio_autorizado_por', 'oc_det_sobreprecio_aut_por_fk')
                    ->references('id')
                    ->on('users')
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
            if ($this->foreignKeyExists('oc_det_sobreprecio_aut_por_fk')) {
                $table->dropForeign('oc_det_sobreprecio_aut_por_fk');
            }

            if ($this->indexExists('oc_det_sobreprecio_aut_por_idx')) {
                $table->dropIndex('oc_det_sobreprecio_aut_por_idx');
            }
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            $columns = [
                'precio_tope',
                'sobreprecio_autorizado_por',
                'sobreprecio_autorizado_at',
                'sobreprecio_autorizacion_motivo',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn($this->tableName, $column)) {
                    $table->dropColumn($column);
                }
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