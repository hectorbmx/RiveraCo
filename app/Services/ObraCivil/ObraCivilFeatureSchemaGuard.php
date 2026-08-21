<?php

namespace App\Services\ObraCivil;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Schema;

class ObraCivilFeatureSchemaGuard
{
    public function ensureTables(array $tables, string $featureName): void
    {
        $missing = collect($tables)
            ->reject(fn (string $table) => Schema::hasTable($table))
            ->values();

        if ($missing->isEmpty()) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => "El modulo {$featureName} esta pendiente de migracion.",
            'missing_tables' => $missing,
        ], 503));
    }
}
