<?php

namespace App\Console\Commands;

use App\Exceptions\Telephony\GrandstreamApiException;
use App\Models\PhoneExtension;
use App\Services\Telephony\GrandstreamClient;
use App\Services\Telephony\GrandstreamExtensionMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrandstreamSyncExtensions extends Command
{
    protected $signature = 'grandstream:sync-extensions {--dry-run : Mapea y resume sin guardar}';

    protected $description = 'Sincroniza extensiones Grandstream UCM hacia phone_extensions';

    public function handle(GrandstreamClient $client, GrandstreamExtensionMapper $mapper): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (!config('grandstream.enabled')) {
            $this->warn('GRANDSTREAM_ENABLED=false. La sincronizacion continuara para diagnostico local.');
        }

        $this->info('== Grandstream extensions sync ==');
        $this->line('Modo: ' . ($dryRun ? 'dry-run' : 'guardar'));
        $this->newLine();

        try {
            $accounts = $client->listAccounts();
            $this->line('Extensiones recibidas: ' . count($accounts));

            $mapped = [];
            $skipped = 0;

            foreach ($accounts as $account) {
                $row = $mapper->map($account);

                if (!$row) {
                    $skipped++;
                    continue;
                }

                $mapped[] = $row;
            }

            $extensions = array_values(array_filter(array_column($mapped, 'extension')));
            $existing = $extensions
                ? PhoneExtension::whereIn('extension', $extensions)->pluck('extension')->all()
                : [];
            $existingLookup = array_fill_keys($existing, true);
            $newCount = 0;
            $updateCount = 0;

            foreach ($mapped as $row) {
                if (isset($existingLookup[$row['extension']])) {
                    $updateCount++;
                } else {
                    $newCount++;
                }
            }

            $this->line('Mapeadas: ' . count($mapped));
            $this->line('Omitidas sin extension: ' . $skipped);
            $this->line('Nuevas: ' . $newCount);
            $this->line('Ya existentes/actualizables: ' . $updateCount);

            $sample = array_slice($extensions, 0, 5);
            if ($sample) {
                $this->line('Muestra: ' . implode(', ', $sample));
            }

            if ($dryRun) {
                $this->newLine();
                $this->info('Dry-run completado. No se guardo nada en BD.');

                return self::SUCCESS;
            }

            DB::transaction(function () use ($mapped) {
                foreach ($mapped as $row) {
                    PhoneExtension::updateOrCreate(
                        ['extension' => $row['extension']],
                        $row
                    );
                }
            });

            $this->newLine();
            $this->info('Sincronizacion de extensiones completada.');

            return self::SUCCESS;
        } catch (GrandstreamApiException $e) {
            $this->error($e->getMessage());

            if ($e->statusCode() !== null) {
                $this->line('Status/API code: ' . $e->statusCode());
            }

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error inesperado al sincronizar extensiones: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}