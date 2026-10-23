<?php

namespace App\Http\Controllers\Maquinas;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Models\MaquinaFoto;
use App\Services\Maquinas\MaquinaFotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MaquinaFotoController extends Controller
{
    public function store(Request $request, Maquina $maquina, MaquinaFotoService $service): RedirectResponse
    {
        $redirect = redirect()->route('maquinas.show', ['maquina' => $maquina, 'tab' => 'fotos']);

        try {
            $service->guardar($maquina, [
                'fotos' => $request->file('fotos', []),
                'foto_descripciones' => $request->input('foto_descripciones'),
            ], $request->user()->getAuthIdentifier());
        } catch (ValidationException $exception) {
            return $redirect->withErrors($exception->errors())
                ->withInput($request->only('foto_descripciones'));
        } catch (Throwable $exception) {
            report($exception);

            return $redirect->withErrors(['fotos' => 'No se pudieron guardar las fotos. Intenta nuevamente.'])
                ->withInput($request->only('foto_descripciones'));
        }

        return $redirect->with('success', 'Fotos de la máquina guardadas correctamente.');
    }

    public function update(Request $request, Maquina $maquina, MaquinaFoto $foto): RedirectResponse
    {
        abort_unless((int) $foto->maquina_id === (int) $maquina->getKey(), 404);
        $redirect = redirect()->route('maquinas.show', ['maquina' => $maquina, 'tab' => 'fotos']);
        $validator = Validator::make($request->only('descripcion'), [
            'descripcion' => ['present', 'nullable', 'string', 'max:200'],
        ], [
            'descripcion.present' => 'Envía la descripción de la foto; puedes dejarla vacía.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe superar los 200 caracteres.',
        ]);

        if ($validator->fails()) {
            return $redirect->withErrors($validator)->withInput($request->only('descripcion'));
        }

        try {
            if (! $foto->update($validator->validated())) {
                throw new RuntimeException('No se pudo actualizar el registro de la foto.');
            }
        } catch (Throwable $exception) {
            report($exception);

            return $redirect->withErrors(['fotos' => 'No se pudo actualizar la descripción. Intenta nuevamente.']);
        }

        return $redirect->with('success', 'Descripción de la foto actualizada correctamente.');
    }

    public function destroy(Maquina $maquina, MaquinaFoto $foto): RedirectResponse
    {
        abort_unless((int) $foto->maquina_id === (int) $maquina->getKey(), 404);
        $redirect = redirect()->route('maquinas.show', ['maquina' => $maquina, 'tab' => 'fotos']);

        try {
            DB::transaction(function () use ($foto) {
                if (! $foto->delete()) {
                    throw new RuntimeException('No se pudo eliminar el registro de la foto.');
                }

                if (! Storage::disk('public')->delete($foto->ruta)) {
                    throw new RuntimeException('No se pudo eliminar el archivo de la foto.');
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            return $redirect->withErrors(['fotos' => 'No se pudo eliminar la foto. Intenta nuevamente.']);
        }

        return $redirect->with('success', 'Foto de la máquina eliminada correctamente.');
    }
}
