<?php

namespace App\Console\Commands;

use App\Exceptions\Telephony\GrandstreamApiException;
use App\Models\PhoneCall;
use App\Services\Telephony\GrandstreamCallMapper;
use App\Services\Telephony\GrandstreamClient;
use App\Services\Telephony\TelephonyCallMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrandstreamImportCdr extends Command
{
    protected $signature = 'grandstream:import-cdr {--date= : Fecha local YYYY-MM-DD} {--today : Usa la fecha local actual} {--dry-run : Mapea y resume sin guardar} {--limit= : Limita registros despues del filtro local}';

    protected $description = 'Importa CDR de Grandstream UCM a phone_calls de forma idempotente';

    public function handle(GrandstreamClient $client, GrandstreamCallMapper $mapper, TelephonyCallMatcher $matcher): int
    {
        if ($this->option('today') && $this->option('date')) {
            $this->error('Usa solo --today o --date=YYYY-MM-DD, no ambos.');

            return self::FAILURE;
        }

        $date = $this->option('today')
            ? now(config('grandstream.cdr.timezone'))->toDateString()
            : ($this->option('date') ?: now(config('grandstream.cdr.timezone'))->toDateString());
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;

        if (!config('grandstream.enabled')) {
            $this->warn('GRANDSTREAM_ENABLED=false. La importacion continuara para diagnostico local.');
        }

        $this->info('== Grandstream CDR import ==');
        $this->line('Fecha local: ' . $date);
        $this->line('Modo: ' . ($dryRun ? 'dry-run' : 'guardar'));
        if ($limit !== null) {
            $this->line('Limit: ' . $limit);
        }
        $this->newLine();

        try {
            $rawCalls = $client->cdrForLocalDate($date);

            if ($limit !== null) {
                $rawCalls = array_slice($rawCalls, 0, $limit);
            }

            $this->line('CDR recibidos filtrados: ' . count($rawCalls));

            $mapped = [];
            $skipped = 0;

            foreach ($rawCalls as $rawCall) {
                $row = $mapper->map($rawCall);

                if (!$row) {
                    $skipped++;
                    continue;
                }

                $row = array_merge($row, $matcher->matchAttributes(new PhoneCall($row)));

                $mapped[] = $row;
            }

            $ids = array_values(array_filter(array_column($mapped, 'ucm_cdr_id')));
            $existing = $ids
                ? PhoneCall::whereIn('ucm_cdr_id', $ids)->pluck('ucm_cdr_id')->all()
                : [];
            $existingLookup = array_fill_keys($existing, true);
            $newCount = 0;
            $updateCount = 0;

            foreach ($mapped as $row) {
                if (isset($existingLookup[$row['ucm_cdr_id']])) {
                    $updateCount++;
                } else {
                    $newCount++;
                }
            }

            $this->line('Mapeados: ' . count($mapped));
            $this->line('Omitidos sin ID: ' . $skipped);
            $this->line('Nuevos: ' . $newCount);
            $this->line('Ya existentes/actualizables: ' . $updateCount);

            $starts = array_values(array_filter(array_map(fn ($row) => optional($row['started_at'])->toDateTimeString(), $mapped)));
            sort($starts);
            if ($starts) {
                $this->line('Min start: ' . $starts[0]);
                $this->line('Max start: ' . $starts[count($starts) - 1]);
            }

            if ($dryRun) {
                $this->newLine();
                $this->info('Dry-run completado. No se guardo nada en BD.');

                return self::SUCCESS;
            }

            DB::transaction(function () use ($mapped) {
                foreach ($mapped as $row) {
                    PhoneCall::updateOrCreate(
                        ['ucm_cdr_id' => $row['ucm_cdr_id']],
                        $row
                    );
                }
            });

            $this->newLine();
            $this->info('Importacion completada.');

            return self::SUCCESS;
        } catch (GrandstreamApiException $e) {
            $this->error($e->getMessage());

            if ($e->statusCode() !== null) {
                $this->line('Status/API code: ' . $e->statusCode());
            }

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error inesperado al importar CDR: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}