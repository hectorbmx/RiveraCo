<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\PhoneCall;
use App\Models\PhoneExtension;
use App\Models\TelephonyCallRequest;
use App\Models\TelephonySyncRun;
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
            'agent' => ['nullable', 'array'],
            'agent.computer_name' => ['nullable', 'string', 'max:255'],
            'agent.module' => ['nullable', 'string', 'max:50'],
            'extensions' => ['present', 'array'],
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

        $summary = [
            'ok' => true,
            'received' => count($data['extensions']),
            'mapped' => count($mapped),
            'skipped' => $skipped,
            'new' => $newCount,
            'updated' => $updateCount,
        ];

        $this->recordSyncRun($request, 'extensions', $summary);

        return response()->json($summary);
    }

    public function importCalls(Request $request, GrandstreamCallMapper $mapper, TelephonyCallMatcher $matcher)
    {
        $data = $request->validate([
            'agent' => ['nullable', 'array'],
            'agent.computer_name' => ['nullable', 'string', 'max:255'],
            'agent.module' => ['nullable', 'string', 'max:50'],
            'window' => ['nullable', 'array'],
            'window.from' => ['nullable', 'date'],
            'window.to' => ['nullable', 'date'],
            'window.timezone' => ['nullable', 'string', 'max:80'],
            'date' => ['nullable', 'date'],
            'calls' => ['present', 'array'],
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

        $summary = [
            'ok' => true,
            'received' => count($data['calls']),
            'mapped' => count($mapped),
            'skipped' => $skipped,
            'new' => $newCount,
            'updated' => $updateCount,
        ];

        $this->recordSyncRun($request, 'calls', $summary);

        return response()->json($summary);
    }


    public function claimCallRequests(Request $request)
    {
        $data = $request->validate([
            'agent' => ['nullable', 'array'],
            'agent.computer_name' => ['nullable', 'string', 'max:255'],
            'agent.module' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $agentName = $data['agent']['computer_name'] ?? $request->user()?->email ?? 'unknown-agent';
        $limit = (int) ($data['limit'] ?? 3);

        $requests = DB::transaction(function () use ($agentName, $limit) {
            $items = TelephonyCallRequest::query()
                ->where('status', TelephonyCallRequest::STATUS_PENDING)
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($limit)
                ->get();

            foreach ($items as $item) {
                $item->update([
                    'status' => TelephonyCallRequest::STATUS_PROCESSING,
                    'claimed_by_agent' => $agentName,
                    'claimed_at' => now(),
                ]);
            }

            return $items->fresh(['requestedBy:id,name,email', 'extension:id,extension,fullname,user_name', 'phoneNumber:id,label,raw_number,normalized_number,display_name']);
        });

        return response()->json([
            'ok' => true,
            'received' => $requests->count(),
            'requests' => $requests->map(fn (TelephonyCallRequest $callRequest) => $this->callRequestPayload($callRequest))->values(),
        ]);
    }

    public function completeCallRequest(Request $request, TelephonyCallRequest $callRequest)
    {
        $data = $request->validate([
            'agent' => ['nullable', 'array'],
            'agent.computer_name' => ['nullable', 'string', 'max:255'],
            'ucm_status' => ['nullable'],
            'response' => ['nullable', 'array'],
        ]);

        $agentName = $data['agent']['computer_name'] ?? null;
        if ($agentName && $callRequest->claimed_by_agent && $callRequest->claimed_by_agent !== $agentName) {
            return response()->json([
                'ok' => false,
                'message' => 'La solicitud fue reclamada por otro agente.',
            ], 409);
        }

        $callRequest->update([
            'status' => TelephonyCallRequest::STATUS_COMPLETED,
            'claimed_by_agent' => $callRequest->claimed_by_agent ?: $agentName,
            'completed_at' => now(),
            'failed_at' => null,
            'ucm_status' => $data['ucm_status'] ?? '0',
            'error_message' => null,
            'response_payload' => $data['response'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'request' => $this->callRequestPayload($callRequest->fresh(['requestedBy:id,name,email', 'extension:id,extension,fullname,user_name', 'phoneNumber:id,label,raw_number,normalized_number,display_name'])),
        ]);
    }

    public function failCallRequest(Request $request, TelephonyCallRequest $callRequest)
    {
        $data = $request->validate([
            'agent' => ['nullable', 'array'],
            'agent.computer_name' => ['nullable', 'string', 'max:255'],
            'ucm_status' => ['nullable'],
            'error_message' => ['nullable', 'string', 'max:2000'],
            'response' => ['nullable', 'array'],
        ]);

        $agentName = $data['agent']['computer_name'] ?? null;
        if ($agentName && $callRequest->claimed_by_agent && $callRequest->claimed_by_agent !== $agentName) {
            return response()->json([
                'ok' => false,
                'message' => 'La solicitud fue reclamada por otro agente.',
            ], 409);
        }

        $callRequest->update([
            'status' => TelephonyCallRequest::STATUS_FAILED,
            'claimed_by_agent' => $callRequest->claimed_by_agent ?: $agentName,
            'failed_at' => now(),
            'completed_at' => null,
            'ucm_status' => $data['ucm_status'] ?? null,
            'error_message' => $data['error_message'] ?? 'El agente no pudo ejecutar la llamada.',
            'response_payload' => $data['response'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'request' => $this->callRequestPayload($callRequest->fresh(['requestedBy:id,name,email', 'extension:id,extension,fullname,user_name', 'phoneNumber:id,label,raw_number,normalized_number,display_name'])),
        ]);
    }

    private function callRequestPayload(TelephonyCallRequest $callRequest): array
    {
        return [
            'id' => $callRequest->id,
            'status' => $callRequest->status,
            'caller_extension' => $callRequest->caller_extension,
            'outbound_number' => $callRequest->outbound_number,
            'normalized_outbound_number' => $callRequest->normalized_outbound_number,
            'phoneable_type' => $callRequest->phoneable_type,
            'phoneable_id' => $callRequest->phoneable_id,
            'phoneable_name' => $callRequest->phoneable_name,
            'claimed_by_agent' => $callRequest->claimed_by_agent,
            'claimed_at' => optional($callRequest->claimed_at)->toDateTimeString(),
            'created_at' => optional($callRequest->created_at)->toDateTimeString(),
            'requested_by' => $callRequest->requestedBy ? [
                'id' => $callRequest->requestedBy->id,
                'name' => $callRequest->requestedBy->name,
                'email' => $callRequest->requestedBy->email,
            ] : null,
            'extension' => $callRequest->extension ? [
                'id' => $callRequest->extension->id,
                'extension' => $callRequest->extension->extension,
                'fullname' => $callRequest->extension->fullname,
                'user_name' => $callRequest->extension->user_name,
            ] : null,
            'phone_number' => $callRequest->phoneNumber ? [
                'id' => $callRequest->phoneNumber->id,
                'label' => $callRequest->phoneNumber->label,
                'raw_number' => $callRequest->phoneNumber->raw_number,
                'normalized_number' => $callRequest->phoneNumber->normalized_number,
                'display_name' => $callRequest->phoneNumber->display_name,
            ] : null,
        ];
    }
    private function recordSyncRun(Request $request, string $module, array $summary): void
    {
        $agent = $request->input('agent', []);
        $window = $request->input('window', []);

        TelephonySyncRun::create([
            'module' => $module,
            'source' => 'agent',
            'status' => 'success',
            'agent_computer_name' => $agent['computer_name'] ?? null,
            'agent_module' => $agent['module'] ?? null,
            'window_from' => $window['from'] ?? null,
            'window_to' => $window['to'] ?? null,
            'window_timezone' => $window['timezone'] ?? null,
            'received' => (int) ($summary['received'] ?? 0),
            'mapped' => (int) ($summary['mapped'] ?? 0),
            'skipped' => (int) ($summary['skipped'] ?? 0),
            'new_count' => (int) ($summary['new'] ?? 0),
            'updated_count' => (int) ($summary['updated'] ?? 0),
            'metadata' => [
                'date' => $request->input('date'),
                'user_id' => $request->user()?->id,
            ],
        ]);
    }
}
