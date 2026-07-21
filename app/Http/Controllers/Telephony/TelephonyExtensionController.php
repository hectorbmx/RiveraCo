<?php

namespace App\Http\Controllers\Telephony;

use App\Http\Controllers\Controller;
use App\Models\PhoneCall;
use App\Models\PhoneExtension;
use App\Models\User;
use App\Models\TelephonyPhoneNumber;
use App\Services\Telephony\PhoneNumberNormalizer;
use App\Services\Telephony\TelephonyCallMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TelephonyExtensionController extends Controller
{
    public function index(Request $request, PhoneNumberNormalizer $normalizer, TelephonyCallMatcher $matcher)
    {
        $tab = $request->query('tab', 'extensions');
        if (!in_array($tab, ['extensions', 'calls', 'missed', 'settings'], true)) {
            $tab = 'extensions';
        }

        $extensions = PhoneExtension::query()
            ->with('user')
            ->orderBy('extension')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $callFilters = [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'direction' => $request->query('direction'),
            'status' => $request->query('status'),
            'extension_id' => $request->query('extension_id'),
            'user_id' => $request->query('user_id'),
            'search' => trim((string) $request->query('search', '')),
        ];

        $callsQuery = $this->baseCallsQuery();
        $this->applyCallFilters($callsQuery, $callFilters);

        $callStats = [
            'total' => (clone $callsQuery)->count(),
            'answered' => (clone $callsQuery)->where('status', 'answered')->count(),
            'missed' => (clone $callsQuery)->whereIn('status', ['no_answer', 'busy', 'failed'])->count(),
            'unassigned' => (clone $callsQuery)->whereNull('user_id')->count(),
        ];

        $calls = $callsQuery
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'calls_page')
            ->withQueryString();

        $this->attachPhoneMatches($calls, $normalizer, $matcher);

        $missedFilters = [
            'date_from' => $request->query('missed_date_from', $request->query('date_from')),
            'date_to' => $request->query('missed_date_to', $request->query('date_to')),
            'extension_id' => $request->query('missed_extension_id'),
            'user_id' => $request->query('missed_user_id'),
            'search' => trim((string) $request->query('missed_search', '')),
        ];

        $missedQuery = $this->baseCallsQuery()
            ->where('direction', 'incoming')
            ->whereIn('status', ['no_answer', 'busy', 'failed']);
        $this->applyCallFilters($missedQuery, $missedFilters);

        $missedStats = [
            'total' => (clone $missedQuery)->count(),
            'unknown' => (clone $missedQuery)->where(function ($query) {
                $query->whereNull('source_number')
                    ->orWhere('source_number', '')
                    ->orWhere('source_number', 'like', '%Privado%')
                    ->orWhere('source_number', 'like', '%anonymous%');
            })->count(),
            'unassigned' => (clone $missedQuery)->whereNull('user_id')->count(),
            'with_user' => (clone $missedQuery)->whereNotNull('user_id')->count(),
        ];

        $missedCalls = $missedQuery
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'missed_page')
            ->withQueryString();

        $this->attachPhoneMatches($missedCalls, $normalizer, $matcher);

        $directionOptions = PhoneCall::query()
            ->whereNotNull('direction')
            ->distinct()
            ->orderBy('direction')
            ->pluck('direction');

        $statusOptions = PhoneCall::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

         $usesLocalTelephonyAgent = config('grandstream.mode') === 'agent';

        return view('telephony.index', compact(
            'usesLocalTelephonyAgent',
            'tab',
            'extensions',
            'users',
            'calls',
            'callFilters',
            'callStats',
            'directionOptions',
            'statusOptions',
            'missedCalls',
            'missedFilters',
            'missedStats'
        ));
    }


    public function show(PhoneExtension $phoneExtension)
    {
        $phoneExtension->load('user');

        $extension = $phoneExtension->extension;

        $baseQuery = PhoneCall::query()
            ->with(['extension', 'user'])
            ->where(function ($query) use ($phoneExtension, $extension) {
                $query->where('phone_extension_id', $phoneExtension->id)
                    ->orWhere('source_extension', $extension)
                    ->orWhere('destination_extension', $extension);
            });

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'made' => (clone $baseQuery)
                ->where(function ($query) use ($extension) {
                    $query->where('source_extension', $extension)
                        ->orWhere(function ($owned) use ($extension) {
                            $owned->whereNotNull('phone_extension_id')
                                ->where('source_extension', $extension);
                        });
                })
                ->count(),
            'answered' => (clone $baseQuery)->where('status', 'answered')->count(),
            'duration' => (clone $baseQuery)->sum('duration_seconds'),
        ];

        $calls = (clone $baseQuery)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('telephony.extension_show', compact('phoneExtension', 'calls', 'stats'));
    }
    public function assign(Request $request, PhoneExtension $phoneExtension)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $phoneExtension->update([
            'user_id' => $data['user_id'] ?: null,
        ]);

        $this->syncImportedCallsForExtension($phoneExtension);

        return redirect()
            ->route('telephony.extensions.index', ['tab' => 'extensions'])
            ->with('success', "Extension {$phoneExtension->extension} actualizada.");
    }

    public function syncExtensions()
    {
        if (config('grandstream.mode') === 'agent') {
            return redirect()
                ->route('telephony.extensions.index', ['tab' => 'extensions'])
                ->with('error', 'La sincronizacion de extensiones se ejecuta desde el agente local en la red del UCM. En este servidor no se conecta directo a Grandstream.');
        }
        $exitCode = Artisan::call('grandstream:sync-extensions');

        if ($exitCode !== 0) {
            $outputLines = array_values(array_filter(preg_split('/\R/', trim(Artisan::output())) ?: []));
            $lastLine = $outputLines ? end($outputLines) : null;
            $message = 'No se pudieron sincronizar las extensiones.';
            $message .= $lastLine ? ' Detalle: ' . $lastLine : ' Ejecuta php artisan grandstream:sync-extensions para ver el detalle.';

            return redirect()
                ->route('telephony.extensions.index', ['tab' => 'extensions'])
                ->with('error', $message);
        }

        return redirect()
            ->route('telephony.extensions.index', ['tab' => 'extensions'])
            ->with('success', 'Extensiones sincronizadas desde el UCM.');
    }
    public function importToday(Request $request)
    {
        $tab = $request->input('tab', 'calls');
        if (!in_array($tab, ['calls', 'missed'], true)) {
            $tab = 'calls';
        }

        $exitCode = Artisan::call('grandstream:import-cdr', [
            '--today' => true,
        ]);

        if ($exitCode !== 0) {
            $outputLines = array_values(array_filter(preg_split('/\R/', trim(Artisan::output())) ?: []));
            $lastLine = $outputLines ? end($outputLines) : null;
            $message = 'No se pudieron importar las llamadas de hoy.';
            $message .= $lastLine ? ' Detalle: ' . $lastLine : ' Ejecuta php artisan grandstream:import-cdr --today para ver el detalle.';

            return redirect()
                ->route('telephony.extensions.index', ['tab' => $tab])
                ->with('error', $message);
        }

        return redirect()
            ->route('telephony.extensions.index', ['tab' => $tab])
            ->with('success', 'Llamadas de hoy importadas desde el UCM.');
    }
    private function attachPhoneMatches($paginator, PhoneNumberNormalizer $normalizer, TelephonyCallMatcher $matcher): void
    {
        $calls = $paginator->getCollection();
        $normalizedByCallId = [];

        foreach ($calls as $call) {
            $rawNumber = $matcher->matchableNumber($call);
            $normalized = $normalizer->normalize($rawNumber);

            if ($normalized) {
                $normalizedByCallId[$call->id] = $normalized;
            }
        }

        $matches = collect();
        $numbers = array_values(array_unique(array_filter($normalizedByCallId)));

        if ($numbers) {
            $matches = TelephonyPhoneNumber::query()
                ->with("phoneable")
                ->where("is_active", true)
                ->whereIn("normalized_number", $numbers)
                ->orderByDesc("is_primary")
                ->orderBy("phoneable_type")
                ->orderBy("display_name")
                ->get()
                ->groupBy("normalized_number");
        }

        foreach ($calls as $call) {
            $normalized = $normalizedByCallId[$call->id] ?? null;
            $call->setRelation("telephonyMatches", $normalized ? $matches->get($normalized, collect()) : collect());
        }
    }

    private function baseCallsQuery(): Builder
    {
        return PhoneCall::query()->with(['extension', 'user']);
    }

    private function applyCallFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['date_from'] ?? null, function ($query, $date) {
                $query->whereDate('started_at', '>=', $date);
            })
            ->when($filters['date_to'] ?? null, function ($query, $date) {
                $query->whereDate('started_at', '<=', $date);
            })
            ->when($filters['direction'] ?? null, function ($query, $direction) {
                $query->where('direction', $direction);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['extension_id'] ?? null, function ($query, $extensionId) {
                $query->where('phone_extension_id', $extensionId);
            })
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when(($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($nested) use ($search) {
                    $nested->where('source_number', 'like', "%{$search}%")
                        ->orWhere('destination_number', 'like', "%{$search}%")
                        ->orWhere('source_extension', 'like', "%{$search}%")
                        ->orWhere('destination_extension', 'like', "%{$search}%")
                        ->orWhere('caller_name', 'like', "%{$search}%")
                        ->orWhere('clid', 'like', "%{$search}%")
                        ->orWhere('session', 'like', "%{$search}%")
                        ->orWhere('ucm_cdr_id', 'like', "%{$search}%");
                });
            });
    }

    private function syncImportedCallsForExtension(PhoneExtension $phoneExtension): void
    {
        if (!$phoneExtension->user_id) {
            PhoneCall::where('phone_extension_id', $phoneExtension->id)->update([
                'phone_extension_id' => null,
                'user_id' => null,
            ]);

            return;
        }

        PhoneCall::where(function ($query) use ($phoneExtension) {
            $query->where(function ($incoming) use ($phoneExtension) {
                $incoming->where('direction', 'incoming')
                    ->where('destination_extension', $phoneExtension->extension);
            })->orWhere(function ($owned) use ($phoneExtension) {
                $owned->where(function ($direction) {
                    $direction->whereNull('direction')
                        ->orWhere('direction', '!=', 'incoming');
                })->where('source_extension', $phoneExtension->extension);
            });
        })->update([
            'phone_extension_id' => $phoneExtension->id,
            'user_id' => $phoneExtension->user_id,
        ]);
    }
}

