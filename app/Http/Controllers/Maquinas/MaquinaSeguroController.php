<?php

namespace App\Http\Controllers\Maquinas;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Models\Seguro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MaquinaSeguroController extends Controller
{
    public function index(Maquina $maquina)
    {
        $seguros = $maquina->seguros()
            ->latest('vigencia_hasta')
            ->get();

        $hoy = now()->toDateString();

        $vigente = $seguros->first(function ($seguro) use ($hoy) {
            return $seguro->estatus !== 'cancelada'
                && $seguro->vigencia_desde?->format('Y-m-d') <= $hoy
                && $seguro->vigencia_hasta?->format('Y-m-d') >= $hoy;
        });

        return response()->json([
            'ok' => true,
            'data' => [
                'vigente' => $vigente ? $this->mapSeguro($vigente) : null,
                'historial' => $seguros->map(fn ($seguro) => $this->mapSeguro($seguro)),
            ],
        ]);
    }
    public function edit(Maquina $maquina, Seguro $seguro)
        {
            return view('maquinas.seguros.edit', compact('maquina', 'seguro'));
        }
        
 

    public function show(Maquina $maquina, Seguro $seguro)
    {
        $this->abortIfSeguroNoPertenece($maquina, $seguro);

        return response()->json([
            'ok' => true,
            'data' => $this->mapSeguro($seguro),
        ]);
    }

    public function store(Request $request, Maquina $maquina)
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($request, $maquina, $data, &$seguro) {
            $seguro = new Seguro();
            $seguro->fill($data);

            $seguro->estatus = $this->resolverEstatus(
                $data['estatus'] ?? null,
                $data['vigencia_desde'],
                $data['vigencia_hasta']
            );

            if ($request->hasFile('documento')) {
                $seguro->documento_path = $request->file('documento')->store('seguros/documentos', 'public');
            }

            if ($request->hasFile('comprobante')) {
                $seguro->comprobante_path = $request->file('comprobante')->store('seguros/comprobantes', 'public');
            }

            if (auth()->check()) {
                $seguro->created_by = auth()->id();
                $seguro->updated_by = auth()->id();
            }

            $maquina->seguros()->save($seguro);
        });

      return redirect()
            ->route('maquinas.show', $maquina)
            ->with('success', 'Póliza registrada correctamente.')
            ->with('tab', 'seguros');
            }

    public function update(Request $request, Maquina $maquina, Seguro $seguro)
    {
      
        $this->abortIfSeguroNoPertenece($maquina, $seguro);

        $data = $this->validateData($request, $seguro->id);

        DB::transaction(function () use ($request, $data, $seguro) {
            $seguro->fill($data);

            $seguro->estatus = $this->resolverEstatus(
                $data['estatus'] ?? $seguro->estatus,
                $data['vigencia_desde'],
                $data['vigencia_hasta']
            );

            if ($request->hasFile('documento')) {
                if ($seguro->documento_path && Storage::disk('public')->exists($seguro->documento_path)) {
                    Storage::disk('public')->delete($seguro->documento_path);
                }

                $seguro->documento_path = $request->file('documento')->store('seguros/documentos', 'public');
            }

            if ($request->hasFile('comprobante')) {
                if ($seguro->comprobante_path && Storage::disk('public')->exists($seguro->comprobante_path)) {
                    Storage::disk('public')->delete($seguro->comprobante_path);
                }

                $seguro->comprobante_path = $request->file('comprobante')->store('seguros/comprobantes', 'public');
            }

            if (auth()->check()) {
                $seguro->updated_by = auth()->id();
            }

            $seguro->save();
        });

        return redirect()
            ->route('maquinas.show', $maquina)
            ->with('success', 'Póliza actualizada correctamente.')
            ->with('tab', 'seguros');
    }

    public function destroy(Maquina $maquina, Seguro $seguro)
    {
        $this->abortIfSeguroNoPertenece($maquina, $seguro);

        DB::transaction(function () use ($seguro) {
            if ($seguro->documento_path && Storage::disk('public')->exists($seguro->documento_path)) {
                Storage::disk('public')->delete($seguro->documento_path);
            }

            if ($seguro->comprobante_path && Storage::disk('public')->exists($seguro->comprobante_path)) {
                Storage::disk('public')->delete($seguro->comprobante_path);
            }

            $seguro->delete();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Póliza eliminada correctamente.',
        ]);
    }

    protected function validateData(Request $request, ?int $seguroId = null): array
    {
        return $request->validate([
            'aseguradora' => ['required', 'string', 'max:255'],
            'poliza_numero' => [
                'required',
                'string',
                'max:255',
            ],
            'tipo_seguro' => ['nullable', 'string', 'max:100'],
            'metodo_pago' => ['nullable', 'string', 'max:100'],
            'frecuencia_pago' => ['nullable', 'string', 'max:100'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'moneda' => ['nullable', 'string', 'max:10'],
            'fecha_compra' => ['nullable', 'date'],
            'vigencia_desde' => ['required', 'date'],
            'vigencia_hasta' => ['required', 'date', 'after_or_equal:vigencia_desde'],
            'suma_asegurada' => ['nullable', 'numeric', 'min:0'],
            'deducible' => ['nullable', 'numeric', 'min:0'],
            'cobertura' => ['nullable', 'string'],
            'estatus' => ['nullable', Rule::in(['futura', 'vigente', 'vencida', 'cancelada'])],
            'alerta_vencimiento_activa' => ['nullable', 'boolean'],
            'dias_preaviso' => ['nullable', 'integer', 'min:1', 'max:365'],
            'observaciones' => ['nullable', 'string'],

            'documento' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'comprobante' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
    }

    protected function resolverEstatus(?string $estatusManual, string $desde, string $hasta): string
    {
        if ($estatusManual === 'cancelada') {
            return 'cancelada';
        }

        $hoy = now()->toDateString();

        if ($desde > $hoy) {
            return 'futura';
        }

        if ($hasta < $hoy) {
            return 'vencida';
        }

        return 'vigente';
    }

   protected function abortIfSeguroNoPertenece(Maquina $maquina, Seguro $seguro): void
{
    if (
        $seguro->asegurable_type !== $maquina->getMorphClass() ||
        (int) $seguro->asegurable_id !== (int) $maquina->getKey()
    ) {
        abort(404);
    }
}

    protected function mapSeguro(Seguro $seguro): array
    {
        $hoy = now()->toDateString();

        $estatusCalculado = $seguro->estatus === 'cancelada'
            ? 'cancelada'
            : (
                $seguro->vigencia_desde?->format('Y-m-d') > $hoy
                    ? 'futura'
                    : ($seguro->vigencia_hasta?->format('Y-m-d') < $hoy ? 'vencida' : 'vigente')
            );

        return [
            'id' => $seguro->id,
            'aseguradora' => $seguro->aseguradora,
            'poliza_numero' => $seguro->poliza_numero,
            'tipo_seguro' => $seguro->tipo_seguro,
            'metodo_pago' => $seguro->metodo_pago,
            'frecuencia_pago' => $seguro->frecuencia_pago,
            'costo' => $seguro->costo,
            'moneda' => $seguro->moneda,
            'fecha_compra' => optional($seguro->fecha_compra)->format('Y-m-d'),
            'vigencia_desde' => optional($seguro->vigencia_desde)->format('Y-m-d'),
            'vigencia_hasta' => optional($seguro->vigencia_hasta)->format('Y-m-d'),
            'suma_asegurada' => $seguro->suma_asegurada,
            'deducible' => $seguro->deducible,
            'cobertura' => $seguro->cobertura,
            'estatus' => $estatusCalculado,
            'alerta_vencimiento_activa' => (bool) $seguro->alerta_vencimiento_activa,
            'dias_preaviso' => $seguro->dias_preaviso,
            'ultima_alerta_enviada_at' => optional($seguro->ultima_alerta_enviada_at)?->format('Y-m-d H:i:s'),
            'observaciones' => $seguro->observaciones,
            'documento_path' => $seguro->documento_path,
            'documento_url' => $seguro->documento_path ? asset('storage/' . $seguro->documento_path) : null,
            'comprobante_path' => $seguro->comprobante_path,
            'comprobante_url' => $seguro->comprobante_path ? asset('storage/' . $seguro->comprobante_path) : null,
            'created_at' => optional($seguro->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($seguro->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
    public function create(Maquina $maquina)
{
    return view('maquinas.seguros.create', compact('maquina'));
}
}