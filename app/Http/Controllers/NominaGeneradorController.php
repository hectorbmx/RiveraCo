<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\NominaRecibo;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Obra;

class NominaGeneradorController extends Controller
{
     public function index(Request $request)
    {
        // 1) Filtros básicos
        $tipo   = $request->input('tipo', 'semanal'); // semanal | quincenal | mensual
        $desde  = $request->input('desde');
        $hasta  = $request->input('hasta');
        $buscar = $request->input('buscar');
        $obraId = $request->input('obra_id');

        // Fechas por defecto: semana actual
        if (!$desde || !$hasta) {
            $startOfWeek = Carbon::now()->startOfWeek(); // lunes
            $endOfWeek   = Carbon::now()->endOfWeek();   // domingo

            $desde = $desde ?: $startOfWeek->format('Y-m-d');
            $hasta = $hasta ?: $endOfWeek->format('Y-m-d');
        }

        // Mapeo tipo → Sueldo_tipo
        $mapTipo = [
            'semanal'   => 1,
            'quincenal' => 2,
            'mensual'   => 3,
        ];
        $sueldoTipo = $mapTipo[$tipo] ?? 1;

        // 2) Query base de empleados: solo del tipo de sueldo y activos
        $query = Empleado::query()
            ->where('Sueldo_tipo', $sueldoTipo)
            ->where('Estatus', 1)// 👈 solo empleados activos
            ->with('obraActiva');

        // 2.a) Filtro de texto (nombre, apellidos o ID)
        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('Nombre', 'like', "%{$buscar}%")
                  ->orWhere('Apellidos', 'like', "%{$buscar}%")
                  ->orWhere('id_Empleado', $buscar);
            });
        }

        // 2.b) Filtro por obra (ajusta el nombre de la relación si es distinto)
        if ($obraId) {
            // Suponiendo relación en Empleado:
            // public function asignacionActiva() { return $this->belongsToMany(Obra::class, 'obra_empleado', 'empleado_id', 'obra_id'); }
            $query->whereHas('obraActiva', function ($q) use ($obraId) {
                // OJO: aquí solo usamos 'id', no 'obras.id'
                $q->where('obras.id', $obraId);
            });
        }

        // Ejecutar query de empleados
        $empleados = $query
            ->orderBy('Nombre')
            ->get();

        // 3) Traer TODAS las obras (para el filtro y para el select de cada fila)
        // $obras = Obra::orderBy('id', 'desc')->get();
        $obras = Obra::where('estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)
            ->orderBy('id', 'desc')
            ->get();

        // 4) Nominas ya generadas para ese periodo
        $nominas = NominaRecibo::query()
            ->whereIn('empleado_id', $empleados->pluck('id_Empleado'))
            ->where('tipo_pago', 'nomina')
            ->where('subtipo', 'nomina_normal')
            ->whereDate('fecha_inicio', $desde)
            ->whereDate('fecha_fin', $hasta)
            ->get()
            ->keyBy('empleado_id');

        // 5) Enviar TODO a la vista (incluyendo $obras, $buscar, $obraId)
        return view('nomina.generador', [
            'tipo'              => $tipo,
            'desde'             => $desde,
            'hasta'             => $hasta,
            'empleados'         => $empleados,
            'nominasPorEmpleado'=> $nominas,
            'obras'             => $obras,
            'buscar'            => $buscar,
            'obraId'            => $obraId,
        ]);
    }
    public function storeEmpleado(Request $request, Empleado $empleado)
    {
        $validated = $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date',
            'tipo'  => 'required|string',

            // Campos de nómina
            'faltas'        => 'nullable|numeric',
            'descuentos'    => 'nullable|numeric',
            'infonavit'     => 'nullable|numeric',
            'horas_extra'   => 'nullable|numeric',
            'metros_lin'    => 'nullable|numeric',
            'comisiones'    => 'nullable|numeric',
            // 'prima_vac'     => 'nullable|numeric',
            'notas'         => 'nullable|string',
            'obra_id'       => 'nullable|integer|exists:obras,id',
            'suma'          => 'required|numeric',
        ]);

        // 1. Intentar obtener recibo existente
        $recibo = NominaRecibo::where('empleado_id', $empleado->id_Empleado)
            ->where('tipo_pago', 'nomina')
            ->where('subtipo', 'nomina_normal')
            ->where('fecha_inicio', $validated['desde'])
            ->where('fecha_fin', $validated['hasta'])
            ->first();

        if (!$recibo) {
            // 2. Nuevo recibo
            $recibo = new NominaRecibo;
            $recibo->empleado_id = $empleado->id_Empleado;
            $recibo->tipo_pago   = 'nomina';
            $recibo->subtipo     = 'nomina_normal';
            $recibo->fecha_inicio = $validated['desde'];
            $recibo->fecha_fin    = $validated['hasta'];
            $recibo->fecha_pago   = now();
        }

        // 3. Actualizar campos
        $recibo->obra_id             = $validated['obra_id'] ?? null;
        $recibo->faltas              = $validated['faltas'] ?? 0;
        $recibo->descuentos_legacy   = $validated['descuentos'] ?? 0;
        $recibo->infonavit_legacy    = $validated['infonavit'] ?? 0;
        $recibo->horas_extra         = $validated['horas_extra'] ?? 0;
        $recibo->metros_lin_monto    = $validated['metros_lin'] ?? 0;
        $recibo->comisiones_monto    = $validated['comisiones'] ?? 0;
        $recibo->prima_vac_legacy    = $validated['prima_vac'] ?? 0;
        $recibo->notas_legacy        = $validated['notas'] ?? null;

        // Totales
        $recibo->sueldo_neto         = $validated['suma'];
        $recibo->total_percepciones  = $validated['suma']; // o desglose si deseas
        $recibo->total_deducciones   = 0; // lo ajustaremos según tu fórmula

        $recibo->save();

        return back()->with('success', 'Nómina guardada correctamente.');
    }

}
