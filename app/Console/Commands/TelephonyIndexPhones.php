<?php

namespace App\Console\Commands;

use App\Models\TelephonyPhoneNumber;
use App\Services\Telephony\TelephonyPhoneIndexBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TelephonyIndexPhones extends Command
{
    protected $signature = 'telephony:index-phones {--dry-run : Resume sin guardar}';

    protected $description = 'Construye el indice local de telefonos normalizados para matching de llamadas';

    public function handle(TelephonyPhoneIndexBuilder $builder): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('== Telephony phone index ==');
        $this->line('Modo: ' . ($dryRun ? 'dry-run' : 'guardar'));
        $this->newLine();

        $rows = $builder->rows();
        $normalized = array_column($rows, 'normalized_number');
        $duplicateGroups = collect($normalized)
            ->countBy()
            ->filter(fn (int $count) => $count > 1);

        $this->line('Telefonos normalizados detectados: ' . count($rows));
        $this->line('Numeros unicos: ' . count(array_unique($normalized)));
        $this->line('Numeros con multiples coincidencias: ' . $duplicateGroups->count());

        $byEntity = collect($rows)->groupBy(fn (array $row) => $row['metadata']['entity'] ?? 'otro');
        foreach ($byEntity as $entity => $entityRows) {
            $this->line("- {$entity}: " . $entityRows->count());
        }

        if ($duplicateGroups->isNotEmpty()) {
            $this->newLine();
            $this->warn('Ejemplos de numeros duplicados:');
            foreach ($duplicateGroups->take(5) as $number => $count) {
                $this->line("- {$number}: {$count} coincidencias");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry-run completado. No se guardo nada en BD.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            TelephonyPhoneNumber::query()->update(['is_active' => false]);

            foreach ($rows as $row) {
                TelephonyPhoneNumber::updateOrCreate(
                    [
                        'phoneable_type' => $row['phoneable_type'],
                        'phoneable_id' => $row['phoneable_id'],
                        'source_column' => $row['source_column'],
                        'normalized_number' => $row['normalized_number'],
                    ],
                    $row
                );
            }
        });

        $this->newLine();
        $this->info('Indice de telefonos actualizado.');

        return self::SUCCESS;
    }
}