<?php

namespace App\Http\Controllers;

use App\Models\Comision;
use App\Models\ComisionEtapaFoto;
use App\Models\Obra;
use App\Services\Comisiones\ComisionEtapaCorreccionService;
use App\Services\Comisiones\ComisionEtapasService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ComisionEtapaCorreccionController extends Controller
{
    public function editar(Request $request, Obra $obra, Comision $comision, string $etapa, ComisionEtapaCorreccionService $service, ComisionEtapasService $motor)
    {
        try {
            $etapaSeleccionada = $service->formulario($request->user(), $obra, $comision, $etapa);
        } catch (ValidationException $exception) {
            return redirect()->route('obras.comisiones.show', [$obra, $comision])->withErrors($exception->errors());
        }
        $comision->load(['detalles', 'etapas.personal.empleado', 'etapas.personal.rol', 'etapas.fotos']);
        return view('obras.comisiones.editar-etapa', [
            'obra' => $obra, 'comision' => $comision, 'etapaSeleccionada' => $etapaSeleccionada,
            'etapa' => $comision->etapas->firstWhere('etapa', $etapa),
            'personalDisponibleEtapa' => $motor->personalDisponible($obra), 'esEdicion' => true,
            'gestionEtapas' => $service->permisos($request->user(), $obra),
            'etapaLabels' => ['perforacion' => 'Perforación', 'acero' => 'Colocación de acero', 'colado' => 'Colado'],
        ]);
    }

    public function actualizar(Request $request, Obra $obra, Comision $comision, string $etapa, ComisionEtapaCorreccionService $service)
    {
        try {
            $service->editar($request->user(), $obra, $comision, $etapa, $request->all());
        } catch (ValidationException $exception) {
            return redirect()->route('obras.comisiones.etapas.editar', [$obra, $comision, $etapa])
                ->withErrors($exception->errors())->withInput($request->except(['fotos', '_token', '_method']));
        } catch (\Throwable $exception) {
            if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException
                || $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw $exception;
            }
            return $this->fallo($request, $obra, $comision, $exception, $etapa);
        }
        return $this->resultado($obra, $comision, 'Cambios y fotografías de la etapa guardados correctamente.');
    }

    public function borrar(Request $request, Obra $obra, Comision $comision, string $etapa, ComisionEtapaCorreccionService $service)
    {
        try {
            $service->borrar($request->user(), $obra, $comision, $etapa);
        } catch (ValidationException $exception) {
            return redirect()->route('obras.comisiones.show', [$obra, $comision])->withErrors($exception->errors());
        } catch (\Illuminate\Database\QueryException|\League\Flysystem\FilesystemException $exception) {
            return $this->fallo($request, $obra, $comision, $exception);
        }
        return $this->resultado($obra, $comision, 'Captura borrada correctamente. La etapa quedó pendiente para registrarla nuevamente.');
    }

    public function borrarFoto(Request $request, Obra $obra, Comision $comision, string $etapa, ComisionEtapaFoto $foto, ComisionEtapaCorreccionService $service)
    {
        try {
            $service->borrarFoto($request->user(), $obra, $comision, $etapa, $foto);
        } catch (ValidationException $exception) {
            return redirect()->route('obras.comisiones.show', [$obra, $comision])->withErrors($exception->errors());
        } catch (\Illuminate\Database\QueryException|\League\Flysystem\FilesystemException $exception) {
            return $this->fallo($request, $obra, $comision, $exception);
        }
        return $this->resultado($obra, $comision, 'Fotografía borrada correctamente.');
    }

    private function resultado(Obra $obra, Comision $comision, string $mensaje)
    {
        return redirect()->route('obras.comisiones.show', [$obra, $comision])->withFragment('resultado-guardado')->with('success', $mensaje);
    }

    private function fallo(Request $request, Obra $obra, Comision $comision, \Throwable $exception, ?string $editar = null)
    {
        $referencia = \Illuminate\Support\Str::upper(substr((string) \Illuminate\Support\Str::uuid(), 0, 8));
        \Illuminate\Support\Facades\Log::error('Fallo al corregir captura de comisión.', [
            'referencia' => $referencia, 'obra_id' => $obra->id, 'comision_id' => $comision->id,
            'user_id' => $request->user()->id, 'exception' => $exception,
        ]);
        $destino = $editar
            ? redirect()->route('obras.comisiones.etapas.editar', [$obra, $comision, $editar])
            : redirect()->route('obras.comisiones.show', [$obra, $comision]);
        return $destino->withErrors(['etapa' => 'No se pudo completar la operación. Referencia: ' . $referencia . '.'])
            ->withInput($request->except(['fotos', '_token', '_method']));
    }
}
