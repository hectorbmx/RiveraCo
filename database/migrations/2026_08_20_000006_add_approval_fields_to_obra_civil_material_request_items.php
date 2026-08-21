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
            return;
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            if (! Schema::hasColumn($this->tableName, 'approved_quantity')) {
                $table->decimal('approved_quantity', 15, 4)->nullable()->after('quantity');
            }

            if (! Schema::hasColumn($this->tableName, 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('notes');
            }

            if (! Schema::hasColumn($this->tableName, 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approval_notes');
            }

            if (! Schema::hasColumn($this->tableName, 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'approved_by') && ! $this->indexExists('oc_mat_req_items_appr_by_idx')) {
                $table->index('approved_by', 'oc_mat_req_items_appr_by_idx');
            }

            if (Schema::hasColumn($this->tableName, 'approved_by') && ! $this->foreignKeyExists('oc_mat_req_items_appr_by_fk')) {
                $table->foreign('approved_by', 'oc_mat_req_items_appr_by_fk')
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
            if ($this->foreignKeyExists('oc_mat_req_items_appr_by_fk')) {
                $table->dropForeign('oc_mat_req_items_appr_by_fk');
            }

            if ($this->indexExists('oc_mat_req_items_appr_by_idx')) {
                $table->dropIndex('oc_mat_req_items_appr_by_idx');
            }
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn($this->tableName, 'approved_at') ? 'approved_at' : null,
                Schema::hasColumn($this->tableName, 'approved_by') ? 'approved_by' : null,
                Schema::hasColumn($this->tableName, 'approval_notes') ? 'approval_notes' : null,
                Schema::hasColumn($this->tableName, 'approved_quantity') ? 'approved_quantity' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
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
