<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Empleado;
use App\Models\EmpleadoEppEntrega;
use App\Models\GiraldaAsistencia;
use App\Models\GiraldaHoraExtra;
use App\Models\Obra;
use App\Models\OrdenCompra;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiraldaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAny(['giralda.access']);

        $areaGiralda = $this->areaGiralda();
        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->endOfMonth()->toDateString());
        $empleadoId = $request->query('empleado_id');
        $estatus = $request->query('estatus', 'activo');

        $empleados = Empleado::with('areaRef')
            ->where('Area', $areaGiralda?->id)
            ->when($estatus === 'activo', fn ($q) => $q->where('Estatus', 1))
            ->when($estatus === 'baja', fn ($q) => $q->where('Estatus', 2))
            ->orderBy('Nombre')
            ->orderBy('Apellidos')
            ->get();

        $horasExtras = GiraldaHoraExtra::with(['empleado', 'autorizadoPor'])
            ->when($areaGiralda, function ($query) use ($areaGiralda) {
                $query->whereHas('empleado', fn ($empleado) => $empleado->where('Area', $areaGiralda->id));
            })
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($empleadoId, fn ($q) => $q->where('empleado_id', $empleadoId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $ordenesCompra = OrdenCompra::with(['proveedor', 'areaCatalogo'])
            ->when($areaGiralda, fn ($q) => $q->where('area_id', $areaGiralda->id))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('giralda.index', compact(
            'areaGiralda',
            'empleados',
            'horasExtras',
            'ordenesCompra',
            'desde',
            'hasta',
            'empleadoId',
            'estatus'
        ));
    }

    public function empleados(Request $request)
    {
        $this->authorizeAny(['giralda.access']);

        $areaGiralda = $this->areaGiralda();

        if ($areaGiralda) {
            $areaGiralda->loadMissing('horarioActivo');
        }
        $tab = $request->query('tab', 'listado');
        $tab = in_array($tab, ['listado', 'asistencia', 'epp', 'horas_extras'], true) ? $tab : 'listado';
        try {
            $semanaInicio = $request->query('semana')
                ? Carbon::parse($request->query('semana'))->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable $exception) {
            $semanaInicio = now()->startOfWeek(Carbon::MONDAY);
        }

        $semanaActualInicio = now()->startOfWeek(Carbon::MONDAY);
        if ($tab === 'asistencia' && $semanaInicio->gt($semanaActualInicio)) {
            $semanaInicio = $semanaActualInicio->copy();
        }

        $semanaFin = $semanaInicio->copy()->endOfWeek(Carbon::SUNDAY);
        $semana = $semanaInicio->toDateString();
        $semanaAnterior = $semanaInicio->copy()->subWeek()->toDateString();
        $semanaSiguiente = $semanaInicio->copy()->addWeek()->toDateString();
        $semanaActual = $semanaActualInicio->toDateString();
        $semanaTitulo = $semanaInicio->format('d/m/Y') . ' al ' . $semanaFin->format('d/m/Y');
        $weekDays = collect(range(0, 6))->map(fn (int $offset) => $semanaInicio->copy()->addDays($offset));
        $hoy = now()->toDateString();
        $puedeOverrideAsistencia = auth()->user()?->can('giralda.asistencia.override.access') ?? false;
        $asistenciaOverrideDesde = now()->startOfWeek(Carbon::MONDAY)->subWeek()->startOfDay();
        $asistenciaEditableFechas = $weekDays
            ->filter(function (Carbon $day) use ($hoy, $puedeOverrideAsistencia, $asistenciaOverrideDesde) {
                if ($day->isAfter(now()->startOfDay())) {
                    return false;
                }

                if ($puedeOverrideAsistencia) {
                    return $day->greaterThanOrEqualTo($asistenciaOverrideDesde);
                }

                return $day->toDateString() === $hoy;
            })
            ->map(fn (Carbon $day) => $day->toDateString())
            ->values();
        $asistenciaEditableFecha = $asistenciaEditableFechas->first();
        $esSemanaActual = $semana === $semanaActual;
        $esSemanaHorasExtrasEditable = $this->isHoraExtraSemanaEditable($semanaInicio);
        $desde = in_array($tab, ['horas_extras', 'asistencia'], true)
            ? $semanaInicio->toDateString()
            : $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = in_array($tab, ['horas_extras', 'asistencia'], true)
            ? $semanaFin->toDateString()
            : $request->query('hasta', now()->endOfMonth()->toDateString());
        $empleadoId = $request->query('empleado_id');
        $estatus = $request->query('estatus', 'activo');
        $estatus = in_array($estatus, ['activo', 'baja', 'todos'], true) ? $estatus : 'activo';
        $busqueda = trim((string) $request->query('q', ''));

        $empleados = Empleado::with(['areaRef', 'eppEntregas.entregadoPor', 'eppEntregas.obra', 'eppEntregas.area'])
            ->withCount([
                'eppEntregas',
                'giraldaHorasExtras' => function ($query) use ($tab, $desde, $hasta) {
                    $query->when($tab === 'horas_extras', function ($horas) use ($desde, $hasta) {
                        $horas->whereDate('fecha', '>=', $desde)
                            ->whereDate('fecha', '<=', $hasta);
                    });
                },
            ])
            ->withSum([
                'giraldaHorasExtras as giralda_horas_extras_semana_horas' => function ($query) use ($desde, $hasta) {
                    $query->whereDate('fecha', '>=', $desde)
                        ->whereDate('fecha', '<=', $hasta);
                },
            ], 'total_horas')
            ->where('Area', $areaGiralda?->id)
            ->when($estatus === 'activo', fn ($q) => $q->where('Estatus', 1))
            ->when($estatus === 'baja', fn ($q) => $q->where('Estatus', 2))
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($empleado) use ($busqueda) {
                    $empleado->where('Nombre', 'like', "%{$busqueda}%")
                        ->orWhere('Apellidos', 'like', "%{$busqueda}%")
                        ->orWhere('Puesto', 'like', "%{$busqueda}%")
                        ->orWhere('id_Empleado', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('Nombre')
            ->orderBy('Apellidos')
            ->get();

        $horasExtras = GiraldaHoraExtra::with(['empleado', 'autorizadoPor'])
            ->when($areaGiralda, function ($query) use ($areaGiralda) {
                $query->whereHas('empleado', fn ($empleado) => $empleado->where('Area', $areaGiralda->id));
            })
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($empleadoId, fn ($q) => $q->where('empleado_id', $empleadoId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'horas_page')
            ->withQueryString();

        $asistencias = GiraldaAsistencia::query()
            ->whereDate('fecha', '>=', $semanaInicio->toDateString())
            ->whereDate('fecha', '<=', $semanaFin->toDateString())
            ->whereIn('empleado_id', $empleados->pluck('id_Empleado'))
            ->get()
            ->keyBy(fn (GiraldaAsistencia $asistencia) => $asistencia->empleado_id . '|' . $asistencia->fecha->toDateString());

        $obrasActivas = Obra::query()
            ->whereNotIn('estatus_nuevo', [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'clave_obra', 'estatus_nuevo']);

        $areas = Area::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
        $eppEntregas = EmpleadoEppEntrega::with(['empleado', 'entregadoPor', 'obra', 'area'])
            ->when($areaGiralda, function ($query) use ($areaGiralda) {
                $query->whereHas('empleado', fn ($empleado) => $empleado->where('Area', $areaGiralda->id));
            })
            ->when($empleadoId, fn ($q) => $q->where('empleado_id', $empleadoId))
            ->orderByDesc('fecha_entrega')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'epp_page')
            ->withQueryString();

        return view('giralda.empleados', compact(
            'areaGiralda',
            'tab',
            'empleados',
            'horasExtras',
            'eppEntregas',
            'desde',
            'hasta',
            'empleadoId',
            'estatus',
            'busqueda',
            'semana',
            'semanaAnterior',
            'semanaSiguiente',
            'semanaActual',
            'semanaTitulo',
            'weekDays',
            'hoy',
            'asistenciaEditableFecha',
            'asistenciaEditableFechas',
            'puedeOverrideAsistencia',
            'esSemanaActual',
            'esSemanaHorasExtrasEditable',
            'asistencias',
            'obrasActivas',
            'areas'
        ));
    }
    public function storeAsistencia(Request $request)
    {
        $this->authorizeAny(['giralda.asistencia.edit.access', 'giralda.asistencia.override.access']);

        $areaGiralda = $this->areaGiralda();
        abort_unless($areaGiralda, 422, 'No existe un area Giralda activa para registrar asistencia.');

        $data = $request->validate([
            'fechas' => ['nullable', 'array'],
            'fechas.*' => ['date'],
            'fecha' => ['nullable', 'date'],
            'empleados' => ['required', 'array'],
            'empleados.*' => ['integer', 'exists:empleados,id_Empleado'],
            'presentes' => ['nullable', 'array'],
            'presentes.*' => ['array'],
            'presentes.*.*' => ['integer', 'exists:empleados,id_Empleado'],
        ]);

        $hoy = now()->toDateString();
        $puedeOverride = auth()->user()?->can('giralda.asistencia.override.access') ?? false;
        $overrideDesde = now()->startOfWeek(Carbon::MONDAY)->subWeek()->toDateString();
        $fechas = collect($data['fechas'] ?? [$data['fecha'] ?? null])
            ->filter()
            ->map(fn ($fecha) => Carbon::parse($fecha)->toDateString())
            ->unique()
            ->values();

        abort_if($fechas->isEmpty(), 422, 'Selecciona al menos una fecha para guardar asistencia.');

        foreach ($fechas as $fecha) {
            abort_if($fecha > $hoy, 422, 'No se puede guardar asistencia futura.');

            if ($puedeOverride) {
                abort_if($fecha < $overrideDesde, 422, 'Solo se puede corregir asistencia de la semana actual o la semana anterior.');
            } else {
                abort_unless($fecha === $hoy, 422, 'Solo se puede guardar asistencia del dia actual.');
            }
        }

        $empleadoIds = collect($data['empleados'])->map(fn ($id) => (int) $id)->unique()->values();
        $empleados = Empleado::query()
            ->whereIn('id_Empleado', $empleadoIds)
            ->where('Area', $areaGiralda?->id)
            ->get(['id_Empleado', 'Area'])
            ->keyBy('id_Empleado');

        abort_unless($empleados->count() === $empleadoIds->count(), 422, 'La lista incluye empleados que no pertenecen a Giralda.');

        $presentesPorFecha = collect($data['presentes'] ?? [])
            ->map(fn ($ids) => collect($ids)->map(fn ($id) => (int) $id)->unique());

        foreach ($fechas as $fecha) {
            $presentes = $presentesPorFecha->get($fecha, collect());

            foreach ($empleadoIds as $empleadoId) {
                $asistencia = GiraldaAsistencia::firstOrNew([
                    'empleado_id' => $empleadoId,
                    'fecha' => $fecha,
                ]);

                if (!$asistencia->exists) {
                    $asistencia->registrado_por = auth()->id();
                }

                $asistencia->fill([
                    'area_id' => $areaGiralda?->id,
                    'estado' => $presentes->contains($empleadoId) ? 'presente' : 'ausente',
                    'origen' => 'manual',
                    'actualizado_por' => auth()->id(),
                ]);
                $asistencia->save();
            }
        }

        return redirect()
            ->route('giralda.empleados', [
                'tab' => 'asistencia',
                'estatus' => $request->input('estatus', 'activo'),
                'semana' => $request->input('semana', now()->startOfWeek(Carbon::MONDAY)->toDateString()),
            ])
            ->with('success', $fechas->count() > 1 ? 'Asistencia semanal guardada.' : 'Asistencia del dia guardada.');
    }
    public function printAsistencia(Request $request)
    {
        $this->authorizeAny(['giralda.access']);

        $areaGiralda = $this->areaGiralda();
        $semanaData = $this->resolverSemana($request->query('semana'));
        $semanaInicio = $semanaData['inicio'];
        $semanaFin = $semanaData['fin'];
        $estatus = $request->query('estatus', 'activo');
        $estatus = in_array($estatus, ['activo', 'baja', 'todos'], true) ? $estatus : 'activo';
        $busqueda = trim((string) $request->query('q', ''));
        $weekDays = collect(range(0, 6))->map(fn (int $offset) => $semanaInicio->copy()->addDays($offset));

        $empleados = Empleado::with('areaRef')
            ->where('Area', $areaGiralda?->id)
            ->when($estatus === 'activo', fn ($q) => $q->where('Estatus', 1))
            ->when($estatus === 'baja', fn ($q) => $q->where('Estatus', 2))
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($empleado) use ($busqueda) {
                    $empleado->where('Nombre', 'like', "%{$busqueda}%")
                        ->orWhere('Apellidos', 'like', "%{$busqueda}%")
                        ->orWhere('Puesto', 'like', "%{$busqueda}%")
                        ->orWhere('id_Empleado', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('Nombre')
            ->orderBy('Apellidos')
            ->get();

        $asistencias = GiraldaAsistencia::query()
            ->whereDate('fecha', '>=', $semanaInicio->toDateString())
            ->whereDate('fecha', '<=', $semanaFin->toDateString())
            ->whereIn('empleado_id', $empleados->pluck('id_Empleado'))
            ->get()
            ->keyBy(fn (GiraldaAsistencia $asistencia) => $asistencia->empleado_id . '|' . $asistencia->fecha->toDateString());

        $horasExtrasPorDia = GiraldaHoraExtra::query()
            ->selectRaw('empleado_id, fecha, SUM(total_horas) as total_horas')
            ->whereDate('fecha', '>=', $semanaInicio->toDateString())
            ->whereDate('fecha', '<=', $semanaFin->toDateString())
            ->whereIn('empleado_id', $empleados->pluck('id_Empleado'))
            ->groupBy('empleado_id', 'fecha')
            ->get()
            ->keyBy(fn (GiraldaHoraExtra $horaExtra) => $horaExtra->empleado_id . '|' . $horaExtra->fecha->toDateString());

        $totalesHorasExtras = $horasExtrasPorDia
            ->groupBy('empleado_id')
            ->map(fn ($registros) => (float) $registros->sum('total_horas'));

        return view('giralda.asistencia-print', compact(
            'areaGiralda',
            'empleados',
            'weekDays',
            'asistencias',
            'horasExtrasPorDia',
            'totalesHorasExtras',
            'estatus',
            'semanaData'
        ));
    }
    public function horasExtrasEmpleado(Request $request, Empleado $empleado)
    {
        $this->authorizeAny(['giralda.access']);

        $areaGiralda = $this->areaGiralda();
        abort_if($areaGiralda && (int) $empleado->Area !== (int) $areaGiralda->id, 422, 'El empleado no pertenece a Giralda.');

        $semanaData = $this->resolverSemana($request->query('semana'));
        $semanaInicio = $semanaData['inicio'];
        $semanaFin = $semanaData['fin'];

        $registros = GiraldaHoraExtra::with('autorizadoPor')
            ->where('empleado_id', $empleado->id_Empleado)
            ->whereDate('fecha', '>=', $semanaInicio->toDateString())
            ->whereDate('fecha', '<=', $semanaFin->toDateString())
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        $totalHoras = (float) $registros->sum('total_horas');
        $semana = $semanaData['semana'];
        $semanaAnterior = $semanaData['anterior'];
        $semanaSiguiente = $semanaData['siguiente'];
        $semanaActual = $semanaData['actual'];
        $semanaTitulo = $semanaData['titulo'];
        $esSemanaActual = $semana === $semanaActual;
        $esSemanaHorasExtrasEditable = $this->isHoraExtraSemanaEditable($semanaInicio);

        return view('giralda.horas-extras-empleado', compact(
            'areaGiralda',
            'empleado',
            'registros',
            'totalHoras',
            'semana',
            'semanaAnterior',
            'semanaSiguiente',
            'semanaActual',
            'semanaTitulo',
            'esSemanaActual',
            'esSemanaHorasExtrasEditable'
        ));
    }

    public function storeHoraExtra(Request $request)
    {
        $this->authorizeAny(['giralda.access']);

        $areaGiralda = $this->areaGiralda();

        $data = $request->validate([
            'empleado_id' => ['required', 'integer', 'exists:empleados,id_Empleado'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'total_horas' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'motivo' => ['required', 'string', 'max:255'],
            'responsable_solicita' => ['required', 'string', 'max:150'],
            'responsable_autoriza' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $empleado = Empleado::findOrFail($data['empleado_id']);
        abort_if($areaGiralda && (int) $empleado->Area !== (int) $areaGiralda->id, 422, 'El empleado no pertenece a Giralda.');
        $fecha = Carbon::parse($data['fecha']);
        if (!$this->isHoraExtraFechaEditable($fecha)) {
            return $this->horaExtraStoreErrorResponse($request, $empleado, 'Solo se pueden registrar horas extras de la semana actual o la semana anterior.', 'fecha');
        }

        if ($request->filled('total_horas')) {
            $data['total_horas'] = round((float) $data['total_horas'], 2);
            $data['hora_fin'] = $this->calcularHoraFinDesdeTotal($data['hora_inicio'], $data['total_horas']);
        } else {
            $data['total_horas'] = $this->calcularTotalHoras($data['hora_inicio'], $data['hora_fin']);
        }

        $data['estado'] = 'pendiente';

        GiraldaHoraExtra::create($data);

        return redirect()
            ->route('giralda.empleados', array_merge($request->only(['desde', 'hasta', 'empleado_id', 'semana', 'estatus']), ['tab' => 'horas_extras']))
            ->with('success', 'Horas extra registradas.');
    }

    public function autorizarHoraExtra(GiraldaHoraExtra $horaExtra)
    {
        $this->authorizeAny(['giralda.horas_extras.authorize.access', 'giralda.access']);

        if ($horaExtra->estado === 'autorizado') {
            return back()->with('success', 'El registro ya estaba autorizado.');
        }

        $horaExtra->update([
            'estado' => 'autorizado',
            'responsable_autoriza' => $horaExtra->responsable_autoriza ?: (auth()->user()->name ?? null),
            'autorizado_por' => auth()->id(),
            'fecha_autorizacion' => now(),
        ]);

        return back()->with('success', 'Horas extra autorizadas.');
    }

    public function redirectHoraExtra(Request $request, GiraldaHoraExtra $horaExtra)
    {
        return redirect()->route('giralda.empleados.horas-extras', [
            'empleado' => $horaExtra->empleado_id,
            'semana' => $request->query(
                'semana',
                $horaExtra->fecha?->copy()->startOfWeek(Carbon::MONDAY)->toDateString() ?? now()->startOfWeek(Carbon::MONDAY)->toDateString()
            ),
        ]);
    }
    public function updateHoraExtra(Request $request, GiraldaHoraExtra $horaExtra)
    {
        $this->authorizeAny(['giralda.horas_extras.edit.access']);

        $horaExtra->loadMissing('empleado');
        $areaGiralda = $this->areaGiralda();

        if ($areaGiralda && (int) $horaExtra->empleado?->Area !== (int) $areaGiralda->id) {
            return $this->horaExtraErrorResponse($request, $horaExtra, 'El registro no pertenece a Giralda.');
        }

        if (!$this->isHoraExtraFechaEditable($horaExtra->fecha)) {
            return $this->horaExtraErrorResponse($request, $horaExtra, 'Solo se pueden editar registros de la semana actual o la semana anterior.');
        }

        $validator = Validator::make($request->all(), [
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'total_horas' => ['required', 'numeric', 'min:0', 'max:24'],
            'motivo' => ['required', 'string', 'max:255'],
            'responsable_solicita' => ['required', 'string', 'max:150'],
            'responsable_autoriza' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->horaExtraErrorResponse($request, $horaExtra, $validator->errors()->first(), $validator->errors()->keys()[0] ?? 'hora_extra');
        }

        $data = $validator->validated();
        $fecha = Carbon::parse($data['fecha']);
        if (!$this->isHoraExtraFechaEditable($fecha)) {
            return $this->horaExtraErrorResponse($request, $horaExtra, 'La fecha editada debe pertenecer a la semana actual o la semana anterior.', 'fecha');
        }

        $data['total_horas'] = round((float) $data['total_horas'], 2);
        $data['hora_fin'] = $this->calcularHoraFinDesdeTotal($data['hora_inicio'], $data['total_horas']);
        $data['estado'] = 'pendiente';
        $data['autorizado_por'] = null;
        $data['fecha_autorizacion'] = null;

        $horaExtra->update($data);

        return redirect()
            ->route('giralda.empleados.horas-extras', [
                'empleado' => $horaExtra->empleado_id,
                'semana' => $request->query('semana', now()->startOfWeek(Carbon::MONDAY)->toDateString()),
            ])
            ->with('success', 'Registro de horas extras actualizado.');
    }

    public function destroyHoraExtra(Request $request, GiraldaHoraExtra $horaExtra)
    {
        $this->authorizeAny(['giralda.horas_extras.delete.access']);

        $horaExtra->loadMissing('empleado');
        $areaGiralda = $this->areaGiralda();

        if ($areaGiralda && (int) $horaExtra->empleado?->Area !== (int) $areaGiralda->id) {
            return $this->horaExtraErrorResponse($request, $horaExtra, 'El registro no pertenece a Giralda.');
        }

        if (!$this->isHoraExtraFechaEditable($horaExtra->fecha)) {
            return $this->horaExtraErrorResponse($request, $horaExtra, 'Solo se pueden eliminar registros de la semana actual o la semana anterior.');
        }

        $empleadoId = $horaExtra->empleado_id;
        $semana = $request->query('semana', now()->startOfWeek(Carbon::MONDAY)->toDateString());

        $horaExtra->delete();

        return redirect()
            ->route('giralda.empleados.horas-extras', ['empleado' => $empleadoId, 'semana' => $semana])
            ->with('success', 'Registro de horas extras eliminado.');
    }

    private function horaExtraEditableDesde(): Carbon
    {
        return now()->startOfWeek(Carbon::MONDAY)->subWeek()->startOfDay();
    }

    private function horaExtraEditableHasta(): Carbon
    {
        return now()->endOfWeek(Carbon::SUNDAY)->endOfDay();
    }

    private function isHoraExtraFechaEditable(?Carbon $fecha): bool
    {
        return $fecha?->betweenIncluded($this->horaExtraEditableDesde(), $this->horaExtraEditableHasta()) ?? false;
    }

    private function isHoraExtraSemanaEditable(Carbon $semanaInicio): bool
    {
        return $semanaInicio->betweenIncluded(
            now()->startOfWeek(Carbon::MONDAY)->subWeek(),
            now()->startOfWeek(Carbon::MONDAY)
        );
    }

    private function horaExtraStoreErrorResponse(Request $request, Empleado $empleado, string $message, string $field = 'hora_extra')
    {
        return redirect()
            ->route('giralda.empleados', [
                'tab' => 'horas_extras',
                'estatus' => $request->input('estatus', 'activo'),
                'semana' => $request->input('semana', now()->startOfWeek(Carbon::MONDAY)->toDateString()),
            ])
            ->withErrors([$field => $message])
            ->withInput();
    }
    private function horaExtraErrorResponse(Request $request, GiraldaHoraExtra $horaExtra, string $message, string $field = 'hora_extra')
    {
        $semana = $request->query(
            'semana',
            $horaExtra->fecha?->copy()->startOfWeek(Carbon::MONDAY)->toDateString() ?? now()->startOfWeek(Carbon::MONDAY)->toDateString()
        );

        return redirect()
            ->route('giralda.empleados.horas-extras', [
                'empleado' => $horaExtra->empleado_id,
                'semana' => $semana,
            ])
            ->withErrors([$field => $message])
            ->withInput();
    }
    public function printHorasExtras(Request $request)
    {
        $this->authorizeAny(['giralda.access']);

        [$desde, $hasta, $empleadoId] = [
            $request->query('desde'),
            $request->query('hasta'),
            $request->query('empleado_id'),
        ];

        $registros = $this->horasExtrasFiltradas($desde, $hasta, $empleadoId)->get();

        return view('giralda.horas-extras-print', compact('registros', 'desde', 'hasta'));
    }

    public function exportHorasExtras(Request $request): StreamedResponse
    {
        $this->authorizeAny(['giralda.access']);

        [$desde, $hasta, $empleadoId] = [
            $request->query('desde'),
            $request->query('hasta'),
            $request->query('empleado_id'),
        ];

        $filename = 'horas_extras_giralda_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($desde, $hasta, $empleadoId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Empleado', 'Fecha', 'Hora inicial', 'Hora final', 'Total horas', 'Motivo', 'Solicita', 'Autoriza', 'Estado', 'Observaciones']);

            $this->horasExtrasFiltradas($desde, $hasta, $empleadoId)
                ->chunk(200, function ($registros) use ($out) {
                    foreach ($registros as $registro) {
                        fputcsv($out, [
                            $registro->empleado?->nombre_completo,
                            optional($registro->fecha)->format('Y-m-d'),
                            $registro->hora_inicio,
                            $registro->hora_fin,
                            $registro->total_horas,
                            $registro->motivo,
                            $registro->responsable_solicita,
                            $registro->responsable_autoriza,
                            $registro->estado,
                            $registro->observaciones,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function resolverSemana(?string $semana): array
    {
        try {
            $inicio = $semana
                ? Carbon::parse($semana)->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable $exception) {
            $inicio = now()->startOfWeek(Carbon::MONDAY);
        }

        $fin = $inicio->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'semana' => $inicio->toDateString(),
            'anterior' => $inicio->copy()->subWeek()->toDateString(),
            'siguiente' => $inicio->copy()->addWeek()->toDateString(),
            'actual' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'titulo' => $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y'),
        ];
    }

    private function horasExtrasFiltradas(?string $desde, ?string $hasta, ?string $empleadoId)
    {
        $areaGiralda = $this->areaGiralda();

        return GiraldaHoraExtra::with('empleado')
            ->when($areaGiralda, function ($query) use ($areaGiralda) {
                $query->whereHas('empleado', fn ($empleado) => $empleado->where('Area', $areaGiralda->id));
            })
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($empleadoId, fn ($q) => $q->where('empleado_id', $empleadoId))
            ->orderBy('fecha')
            ->orderBy('empleado_id');
    }

    private function areaGiralda(): ?Area
    {
        return Area::query()
            ->where('codigo', 'GL')
            ->orWhere('nombre', 'like', '%Giralda%')
            ->first();
    }

    private function calcularHoraFinDesdeTotal(string $inicio, float $totalHoras): string
    {
        return Carbon::createFromFormat('H:i', $inicio)
            ->addMinutes((int) round($totalHoras * 60))
            ->format('H:i');
    }

    private function calcularTotalHoras(string $inicio, string $fin): float
    {
        $inicioAt = Carbon::createFromFormat('H:i', $inicio);
        $finAt = Carbon::createFromFormat('H:i', $fin);

        if ($finAt->lessThan($inicioAt)) {
            $finAt->addDay();
        }

        return round($inicioAt->diffInMinutes($finAt) / 60, 2);
    }

    private function authorizeAny(array $permissions, string $message = 'No tienes permiso para acceder a Giralda.'): void
    {
        $user = auth()->user();

        if (!$user || !$user->canAny($permissions)) {
            throw new AuthorizationException($message);
        }
    }
}
