<?php

namespace App\Console\Commands;

use App\Models\PhoneCall;
use App\Models\PhoneExtension;
use App\Services\Telephony\TelephonyCallMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TelephonyMatchCalls extends Command
{
    protected $signature = 'telephony:match-calls
        {--date= : Fecha local YYYY-MM-DD para procesar}
        {--direction= : Filtrar por incoming/outgoing/internal}
        {--dry-run : Resume sin guardar}';

    protected $description = 'Relaciona phone_calls existentes contra telephony_phone_numbers y refresca snapshots locales';

    public function handle(TelephonyCallMatcher $matcher): int
    {
        $date = $this->option('date');
        $direction = $this->option('direction');
        $dryRun = (bool) $this->option('dry-run');

        $calls = PhoneCall::query()
            ->with(['extension.user'])
            ->when($date, fn ($query) => $query->whereDate('started_at', $date))
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->orderBy('started_at')
            ->orderBy('id')
            ->get();

        $summary = [
            'matched' => 0,
            'ambiguous' => 0,
            'unknown' => 0,
            'no_number' => 0,
        ];
        $updates = [];

        foreach ($calls as $call) {
            $attributes = $matcher->matchAttributes($call);
            $summary[$attributes['match_status']] = ($summary[$attributes['match_status']] ?? 0) + 1;

            $attributes = array_merge($this->snapshotAttributes($call), $attributes);
            $updates[$call->id] = $attributes;
        }

        $this->info('== Telephony match calls ==');
        $this->line('Fecha: ' . ($date ?: 'todas'));
        $this->line('Direccion: ' . ($direction ?: 'todas'));
        $this->line('Modo: ' . ($dryRun ? 'dry-run' : 'guardar'));
        $this->newLine();

        $this->line('Llamadas evaluadas: ' . $calls->count());
        $this->line('Identificadas: ' . ($summary['matched'] ?? 0));
        $this->line('Ambiguas: ' . ($summary['ambiguous'] ?? 0));
        $this->line('Desconocidas: ' . ($summary['unknown'] ?? 0));
        $this->line('Sin numero util: ' . ($summary['no_number'] ?? 0));

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry-run completado. No se guardo nada en BD.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $id => $attributes) {
                PhoneCall::whereKey($id)->update($attributes);
            }
        });

        $this->newLine();
        $this->info('Matching de llamadas completado.');

        return self::SUCCESS;
    }

    private function snapshotAttributes(PhoneCall $call): array
    {
        $extensionNumber = $call->extension_snapshot ?: $this->ownerExtension($call);
        $extension = $call->extension;

        if (!$extension && $extensionNumber) {
            $extension = PhoneExtension::with('user')->where('extension', $extensionNumber)->first();
        }

        return [
            'phone_extension_id' => $call->phone_extension_id ?: $extension?->id,
            'user_id' => $call->user_id ?: $extension?->user_id,
            'extension_snapshot' => $call->extension_snapshot ?: $extensionNumber,
            'extension_name_snapshot' => $call->extension_name_snapshot ?: $extension?->fullname,
            'user_name_snapshot' => $call->user_name_snapshot ?: $extension?->user?->name,
        ];
    }

    private function ownerExtension(PhoneCall $call): ?string
    {
        if ($call->direction === 'incoming') {
            return $call->destination_extension;
        }

        return $call->source_extension ?: $call->destination_extension;
    }
}