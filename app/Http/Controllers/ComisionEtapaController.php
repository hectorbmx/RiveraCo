<?php

namespace App\Http\Controllers;

use App\Models\Comision;
use App\Models\Obra;
use App\Services\Comisiones\ComisionesWebService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComisionEtapaController extends Controller
{
    public function crear(Request $request, Obra $obra, ComisionesWebService $service)
    {
        try {
            $comision = $service->createForUser($request->user(), $obra, $request->all());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()->route('obras.comisiones.create', [$obra])
                ->withErrors($exception->errors())->withInput($request->except(['fotos', '_token']));
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $referencia = Str::upper(substr((string) Str::uuid(), 0, 8));
            Log::error('Fallo al crear comisión por etapas.', [
                'referencia' => $referencia,
                'obra_id' => $obra->id,
                'user_id' => $request->user()->id,
                'exception' => $exception,
            ]);
            $motivo = match (true) {
                $exception instanceof \Illuminate\Database\QueryException => 'La base de datos rechazó el registro de la comisión.',
                $exception instanceof \Intervention\Image\Exceptions\RuntimeException => 'No se pudo procesar una de las fotografías. Prueba con otra imagen.',
                $exception instanceof \League\Flysystem\UnableToWriteFile,
                $exception->getMessage() === 'No se pudo guardar la fotografía.' => 'No se pudo almacenar una de las fotografías.',
                default => 'Ocurrió un error interno al guardar la comisión.',
            };

            return redirect()->route('obras.comisiones.create', [$obra])
                ->withErrors(['guardado' => $motivo . ' Referencia: ' . $referencia . '. Los datos de la captura se conservaron; vuelve a seleccionar las fotografías.'])
                ->withInput($request->except(['fotos', '_token']));
        }

        return redirect()->route('obras.comisiones.show', [$obra, $comision])
            ->withFragment('resultado-guardado')
            ->with('success', 'Comisión #' . $comision->id . ' creada correctamente. '
                . ($request->filled('perforacion.hora_fin')
                    ? 'Perforación completada. Ya puedes continuar con el siguiente paso.'
                    : 'Perforación iniciada y guardada en proceso. Puedes continuarla desde este detalle.'));
    }

    public function guardar(Request $request, Obra $obra, Comision $comision, string $etapa, ComisionesWebService $service)
    {
        try {
            $guardada = $service->completarEtapaForUser($request->user(), $obra, $comision, $etapa, $request->all());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $params = [$obra, $comision];
            if (! array_key_exists('etapa', $exception->errors())) {
                $params['etapa'] = $etapa;
            }

            return redirect()->route('obras.comisiones.show', $params)
                ->withErrors($exception->errors())->withInput($request->except(['fotos', '_token']));
        }

        return redirect()->route('obras.comisiones.show', [$obra, $comision])
            ->withFragment('resultado-guardado')
            ->with('success', $guardada->estado === 'cerrada'
                ? $this->nombreEtapa($etapa) . ' completada correctamente. Todas las etapas aplicables están completas y la comisión quedó cerrada.'
                : $this->nombreEtapa($etapa) . ' completada correctamente. Los datos, el personal y las fotografías quedaron guardados; el avance y el siguiente paso se actualizaron.');
    }

    public function activar(Request $request, Obra $obra, Comision $comision, string $etapa, ComisionesWebService $service)
    {
        $service->activarEtapaForUser($request->user(), $obra, $comision, $etapa);

        return redirect()->route('obras.comisiones.show', [$obra, $comision])
            ->withFragment('resultado-guardado')
            ->with('success', $this->nombreEtapa($etapa) . ' activada correctamente. Ya puedes registrar esta etapa.');
    }

    private function nombreEtapa(string $etapa): string
    {
        return match ($etapa) {
            'perforacion' => 'Perforación',
            'bentonita' => 'Bentonita',
            'ademe' => 'Ademe',
            'acero' => 'Colocación de acero',
            'colado' => 'Colado',
            default => 'Etapa',
        };
    }
}
