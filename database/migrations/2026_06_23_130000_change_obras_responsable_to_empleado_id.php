<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE obras DROP FOREIGN KEY obras_responsable_id_foreign');
        } catch (Throwable $e) {
            // La llave pudo haber sido removida manualmente en alguna base local.
        }

        DB::statement("
            UPDATE obras
            INNER JOIN usuarios_app ON usuarios_app.user_id = obras.responsable_id
            SET obras.responsable_id = usuarios_app.empleado_id
            WHERE obras.responsable_id IS NOT NULL
              AND usuarios_app.empleado_id IS NOT NULL
        ");

        DB::statement('ALTER TABLE obras MODIFY responsable_id INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE obras MODIFY responsable_id BIGINT UNSIGNED NULL');

        DB::statement("
            UPDATE obras
            INNER JOIN usuarios_app ON usuarios_app.empleado_id = obras.responsable_id
            SET obras.responsable_id = usuarios_app.user_id
            WHERE obras.responsable_id IS NOT NULL
              AND usuarios_app.user_id IS NOT NULL
        ");

        try {
            DB::statement('ALTER TABLE obras ADD CONSTRAINT obras_responsable_id_foreign FOREIGN KEY (responsable_id) REFERENCES users(id) ON DELETE SET NULL');
        } catch (Throwable $e) {
            // Si quedan IDs sin usuario equivalente, evitamos bloquear el rollback completo.
        }
    }
};
