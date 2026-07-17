<?php

namespace App\Services\Telephony;

use App\Models\PhoneExtension;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class GrandstreamCallMapper
{
    public function map(array|object $item): ?array
    {
        $raw = $this->toArray($item);
        $flat = $this->flatten($raw);
        $ucmCdrId = Arr::get($raw, 'cdr') ?: Arr::get($flat, 'cdr');

        if (!$ucmCdrId) {
            return null;
        }

        $sourceExtension = $this->value($flat, 'channel_ext') ?: $this->value($flat, 'new_src') ?: $this->value($flat, 'src');
        $destinationExtension = $this->value($flat, 'dstchannel_ext') ?: $this->value($flat, 'dst');
        $direction = $this->mapDirection($this->value($flat, 'userfield'));
        $ownerExtension = $this->ownerExtension($direction, $sourceExtension, $destinationExtension);
        $phoneExtension = $ownerExtension ? PhoneExtension::with('user')->where('extension', $ownerExtension)->first() : null;

        return [
            'ucm_cdr_id' => (string) $ucmCdrId,
            'phone_extension_id' => $phoneExtension?->id,
            'user_id' => $phoneExtension?->user_id,
            'session' => $this->value($flat, 'session'),
            'acct_id' => $this->value($flat, 'AcctId'),
            'uniqueid' => $this->value($flat, 'uniqueid'),
            'action_type' => $this->value($flat, 'action_type'),
            'action_owner' => $this->value($flat, 'action_owner'),
            'direction' => $direction,
            'status' => $this->mapStatus($this->value($flat, 'disposition')),
            'disposition' => $this->value($flat, 'disposition'),
            'ucm_userfield' => $this->value($flat, 'userfield'),
            'source_number' => $this->value($flat, 'src'),
            'destination_number' => $this->value($flat, 'dst'),
            'source_extension' => $sourceExtension,
            'destination_extension' => $destinationExtension,
            'answered_by' => $this->value($flat, 'dstanswer'),
            'caller_name' => $this->value($flat, 'caller_name'),
            'clid' => $this->value($flat, 'clid'),
            'started_at' => $this->date($this->value($flat, 'start')),
            'answered_at' => $this->date($this->value($flat, 'answer')),
            'ended_at' => $this->date($this->value($flat, 'end')),
            'duration_seconds' => (int) ($this->value($flat, 'duration') ?: 0),
            'billsec' => (int) ($this->value($flat, 'billsec') ?: 0),
            'source_trunk_name' => $this->value($flat, 'src_trunk_name'),
            'destination_trunk_name' => $this->value($flat, 'dst_trunk_name'),
            'channel' => $this->value($flat, 'channel'),
            'destination_channel' => $this->value($flat, 'dstchannel'),
            'lastapp' => $this->value($flat, 'lastapp'),
            'lastdata' => $this->value($flat, 'lastdata'),
            'device_info' => $this->value($flat, 'device_info'),
            'device_info_peer' => $this->value($flat, 'device_info_peer'),
            'recordfiles' => $this->value($flat, 'recordfiles'),
            'reason' => $this->value($flat, 'reason'),
            'raw_payload' => $raw,
            'imported_at' => now(),
        ];
    }

    public function flatten(array $raw): array
    {
        if (!isset($raw['main_cdr']) || !is_array($raw['main_cdr'])) {
            return $raw;
        }

        return array_merge($raw['main_cdr'], [
            'cdr' => Arr::get($raw, 'cdr'),
            'action_type' => Arr::get($raw, 'action_type', Arr::get($raw, 'main_cdr.action_type')),
            'action_owner' => Arr::get($raw, 'action_owner', Arr::get($raw, 'main_cdr.action_owner')),
        ]);
    }

    private function toArray(array|object $item): array
    {
        if (is_array($item)) {
            return $item;
        }

        return json_decode(json_encode($item), true) ?: [];
    }

    private function value(array $row, string $key): ?string
    {
        $value = Arr::get($row, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function date(?string $value): ?CarbonImmutable
    {
        if (!$value) {
            return null;
        }

        return CarbonImmutable::parse($value, config('grandstream.cdr.timezone', 'America/Mexico_City'));
    }

    private function mapDirection(?string $userfield): ?string
    {
        return match (strtolower((string) $userfield)) {
            'inbound' => 'incoming',
            'outbound' => 'outgoing',
            'internal' => 'internal',
            default => $userfield ? strtolower($userfield) : null,
        };
    }

    private function mapStatus(?string $disposition): ?string
    {
        return match (strtoupper((string) $disposition)) {
            'ANSWERED' => 'answered',
            'NO ANSWER' => 'no_answer',
            'BUSY' => 'busy',
            'FAILED' => 'failed',
            'CONGESTION' => 'failed',
            default => $disposition ? strtolower(str_replace(' ', '_', $disposition)) : null,
        };
    }

    private function ownerExtension(?string $direction, ?string $sourceExtension, ?string $destinationExtension): ?string
    {
        if ($direction === 'incoming') {
            return $destinationExtension;
        }

        return $sourceExtension ?: $destinationExtension;
    }
}