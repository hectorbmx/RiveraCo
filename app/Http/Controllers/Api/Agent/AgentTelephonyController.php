<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\PhoneCall;
use App\Models\PhoneExtension;
use App\Services\Telephony\GrandstreamCallMapper;
use App\Services\Telephony\GrandstreamExtensionMapper;
use App\Services\Telephony\TelephonyCallMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentTelephonyController extends Controller
{
    public function syncExtensions(Request $request, GrandstreamExtensionMapper $mapper)
    {
        $data = $request->validate([
            'extensions' => ['required', 'array'],
            'extensions.*' => ['array'],
        ]);

        $mapped = [];
        $skipped = 0;

        foreach ($data['extensions'] as $extension) {
            $row = $mapper->map($extension);

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

        DB::transaction(function () use ($mapped) {
            foreach ($mapped as $row) {
                PhoneExtension::updateOrCreate(
                    ['extension' => $row['extension']],
                    $row
                );
            }
        });

        return response()->json([
            'ok' => true,
            'received' => count($data['extensions']),
            'mapped' => count($mapped),
            'skipped' => $skipped,
            'new' => $newCount,
            'updated' => $updateCount,
        ]);
    }

    public function importCalls(Request $request, GrandstreamCallMapper $mapper, TelephonyCallMatcher $matcher)
    {
        $data = $request->validate([
            'calls' => ['required', 'array'],
            'calls.*' => ['array'],
        ]);

        $mapped = [];
        $skipped = 0;

        foreach ($data['calls'] as $call) {
            $row = $mapper->map($call);

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

        DB::transaction(function () use ($mapped) {
            foreach ($mapped as $row) {
                PhoneCall::updateOrCreate(
                    ['ucm_cdr_id' => $row['ucm_cdr_id']],
                    $row
                );
            }
        });

        return response()->json([
            'ok' => true,
            'received' => count($data['calls']),
            'mapped' => count($mapped),
            'skipped' => $skipped,
            'new' => $newCount,
            'updated' => $updateCount,
        ]);
    }
}
