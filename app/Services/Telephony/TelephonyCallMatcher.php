<?php

namespace App\Services\Telephony;

use App\Models\PhoneCall;
use App\Models\TelephonyPhoneNumber;
use Illuminate\Support\Collection;

class TelephonyCallMatcher
{
    public function __construct(private PhoneNumberNormalizer $normalizer)
    {
    }

    public function match(PhoneCall $call): array
    {
        $rawNumber = $this->matchableNumber($call);
        $normalized = $this->normalizer->normalize($rawNumber);

        if (!$rawNumber) {
            return [
                'status' => 'no_number',
                'raw_number' => null,
                'normalized_number' => null,
                'matches' => collect(),
            ];
        }

        if (!$normalized) {
            return [
                'status' => 'no_number',
                'raw_number' => $rawNumber,
                'normalized_number' => null,
                'matches' => collect(),
            ];
        }

        $matches = TelephonyPhoneNumber::query()
            ->where('is_active', true)
            ->where('normalized_number', $normalized)
            ->orderByDesc('is_primary')
            ->orderBy('phoneable_type')
            ->orderBy('display_name')
            ->get();

        return [
            'status' => $this->statusForMatches($matches),
            'raw_number' => $rawNumber,
            'normalized_number' => $normalized,
            'matches' => $matches,
        ];
    }

    public function matchAttributes(PhoneCall $call): array
    {
        $result = $this->match($call);
        $match = $result['status'] === 'matched' ? $result['matches']->first() : null;

        return [
            'matched_phone_number_id' => $match?->id,
            'phoneable_type' => $match?->phoneable_type,
            'phoneable_id' => $match?->phoneable_id,
            'phoneable_name' => $match?->display_name,
            'matched_number' => $result['normalized_number'],
            'match_status' => $result['status'],
        ];
    }

    public function matchableNumber(PhoneCall $call): ?string
    {
        if ($call->direction === 'outgoing') {
            return $call->destination_number
                ?: $call->destination_extension
                ?: $this->extractNumberFromClid($call->clid);
        }

        if ($call->direction === 'incoming') {
            return $call->source_number
                ?: $call->source_extension
                ?: $this->extractNumberFromClid($call->clid);
        }

        return $call->destination_number
            ?: $call->source_number
            ?: $call->destination_extension
            ?: $call->source_extension
            ?: $this->extractNumberFromClid($call->clid);
    }

    public function callerNumber(PhoneCall $call): ?string
    {
        return $this->matchableNumber($call);
    }

    private function extractNumberFromClid(?string $clid): ?string
    {
        if (!$clid) {
            return null;
        }

        if (preg_match('/<([^>]+)>/', $clid, $matches)) {
            return trim($matches[1]);
        }

        return $clid;
    }

    private function statusForMatches(Collection $matches): string
    {
        if ($matches->isEmpty()) {
            return 'unknown';
        }

        $entities = $matches
            ->map(fn (TelephonyPhoneNumber $match) => $match->phoneable_type . ':' . $match->phoneable_id)
            ->unique();

        return $entities->count() === 1 ? 'matched' : 'ambiguous';
    }
}