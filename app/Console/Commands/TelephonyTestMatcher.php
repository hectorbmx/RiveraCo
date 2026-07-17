<?php

namespace App\Console\Commands;

use App\Models\PhoneCall;
use App\Services\Telephony\TelephonyCallMatcher;
use Illuminate\Console\Command;

class TelephonyTestMatcher extends Command
{
    protected $signature = 'telephony:test-matcher
        {--date= : Fecha local YYYY-MM-DD para evaluar}
        {--incoming-only : Evaluar solo llamadas entrantes}
        {--limit=5 : Ejemplos por categoria}';

    protected $description = 'Prueba el matching de phone_calls contra telephony_phone_numbers sin guardar cambios';

    public function handle(TelephonyCallMatcher $matcher): int
    {
        $date = $this->option('date');
        $incomingOnly = (bool) $this->option('incoming-only');
        $limit = max(1, (int) $this->option('limit'));

        $query = PhoneCall::query()
            ->when($date, fn ($query) => $query->whereDate('started_at', $date))
            ->when($incomingOnly, fn ($query) => $query->where('direction', 'incoming'))
            ->orderBy('started_at')
            ->orderBy('id');

        $calls = $query->get();

        $summary = [
            'matched' => 0,
            'ambiguous' => 0,
            'unknown' => 0,
            'no_number' => 0,
        ];
        $examples = [
            'matched' => [],
            'ambiguous' => [],
            'unknown' => [],
            'no_number' => [],
        ];

        foreach ($calls as $call) {
            $result = $matcher->match($call);
            $status = $result['status'];
            $summary[$status]++;

            if (count($examples[$status]) < $limit) {
                $examples[$status][] = $this->example($call, $result);
            }
        }

        $this->info('== Telephony call matcher test ==');
        $this->line('Fecha: ' . ($date ?: 'todas'));
        $this->line('Solo entrantes: ' . ($incomingOnly ? 'si' : 'no'));
        $this->newLine();

        $this->line('Llamadas evaluadas: ' . $calls->count());
        $this->line('Identificadas: ' . $summary['matched']);
        $this->line('Ambiguas: ' . $summary['ambiguous']);
        $this->line('Desconocidas: ' . $summary['unknown']);
        $this->line('Sin numero util: ' . $summary['no_number']);

        foreach ($examples as $status => $items) {
            if (!$items) {
                continue;
            }

            $this->newLine();
            $this->warn(strtoupper($status) . ' ejemplos:');
            foreach ($items as $item) {
                $this->line($item);
            }
        }

        return self::SUCCESS;
    }

    private function example(PhoneCall $call, array $result): string
    {
        $matches = $result['matches']->map(function ($match) {
            return sprintf(
                '%s [%s:%s]',
                $match->display_name ?: $match->label ?: 'Sin nombre',
                class_basename($match->phoneable_type),
                $match->phoneable_id
            );
        })->take(3)->implode(' | ');

        return sprintf(
            '#%s %s %s -> %s raw=%s normalized=%s%s',
            $call->ucm_cdr_id,
            optional($call->started_at)->format('Y-m-d H:i:s') ?: '-',
            $call->source_number ?: $call->source_extension ?: '-',
            $call->destination_number ?: $call->destination_extension ?: '-',
            $result['raw_number'] ?: '-',
            $result['normalized_number'] ?: '-',
            $matches ? ' match=' . $matches : ''
        );
    }
}