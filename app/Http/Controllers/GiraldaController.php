<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Empleado;
use App\Models\EmpleadoEppEntrega;
use App\Models\GiraldaHoraExtra;
use App\Models\Obra;
use App\Models\OrdenCompra;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
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
        $tab = $request->query('tab', 'listado');
        $tab = in_array($tab, ['listado', 'epp', 'horas_extras'], true) ? $tab : 'listado';
        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->endOfMonth()->toDateString());
        $empleadoId = $request->query('empleado_id');
        $estatus = $request->query('estatus', 'activo');
        $estatus = in_array($estatus, ['activo', 'baja', 'todos'], true) ? $estatus : 'activo';

        $empleados = Empleado::with(['areaRef', 'eppEntregas.entregadoPor', 'eppEntregas.obra', 'eppEntregas.area'])->withCount(['eppEntregas', 'giraldaHorasExtras'])
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
            ->paginate(20, ['*'], 'horas_page')
            ->withQueryString();

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
            'obrasActivas',
            'areas'
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
            'motivo' => ['required', 'string', 'max:255'],
            'responsable_solicita' => ['required', 'string', 'max:150'],
            'responsable_autoriza' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $empleado = Empleado::findOrFail($data['empleado_id']);
        abort_if($areaGiralda && (int) $empleado->Area !== (int) $areaGiralda->id, 422, 'El empleado no pertenece a Giralda.');

        $data['total_horas'] = $this->calcularTotalHoras($data['hora_inicio'], $data['hora_fin']);
        $data['estado'] = 'pendiente';

        GiraldaHoraExtra::create($data);

        return redirect()
            ->route('giralda.empleados', array_merge($request->only(['desde', 'hasta', 'empleado_id']), ['tab' => 'horas_extras']))
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

    private function calcularTotalHoras(string $inicio, string $fin): float
    {
        $inicioAt = Carbon::createFromFormat('H:i', $inicio);
        $finAt = Carbon::createFromFormat('H:i', $fin);

        if ($finAt->lessThanOrEqualTo($inicioAt)) {
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
