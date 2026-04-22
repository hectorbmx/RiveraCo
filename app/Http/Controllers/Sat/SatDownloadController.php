<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SatDownloadRequest;

use App\Models\SatEmpresa;
use App\Services\Sat\SatMassDownloadService;
use Illuminate\Support\Facades\Log;


class SatDownloadController extends Controller
{
    //
     public function index()
    {
        $requests = SatDownloadRequest::latest()->limit(20)->get();

    return view('sat.descargas.index', compact('requests'));
    }
      public function create()
    {
        $empresas = SatEmpresa::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('sat.descargas.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sat_empresa_id' => ['required', 'exists:sat_empresas,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'tipo_descarga' => ['required', 'in:received,issued'],
        ]);

        $empresa = SatEmpresa::findOrFail($data['sat_empresa_id']);

        $downloadRequest = SatDownloadRequest::create([
            'sat_empresa_id' => $empresa->id,
            'rfc_solicitante' => $empresa->rfc,
            'fecha_inicio' => $data['fecha_inicio'] . ' 00:00:00',
            'fecha_fin' => $data['fecha_fin'] . ' 23:59:59',
            'tipo_descarga' => $data['tipo_descarga'],
            'estado' => 'pending',
        ]);
 try {
        \App\Jobs\Sat\ProcessSatDownloadJob::dispatchSync($downloadRequest->id);

        return redirect()
            ->route('sat.descargas.index')
            ->with('success', 'Solicitud SAT creada y proceso ejecutado correctamente.');
    } catch (\Throwable $e) {
        return redirect()
            ->route('sat.descargas.index')
            ->with('error', 'La solicitud se creó, pero ocurrió un error al procesarla: ' . $e->getMessage());
    }
      
    }

     private function procesarSolicitud(SatDownloadRequest $downloadRequest, SatEmpresa $empresa): void
    {
        $cerPath = storage_path('app/' . $empresa->cer_path);
        $keyPath = storage_path('app/' . $empresa->key_path);
        $password = $empresa->fiel_password;

        if (! file_exists($cerPath)) {
            throw new \RuntimeException("No existe el archivo CER: {$cerPath}");
        }

        if (! file_exists($keyPath)) {
            throw new \RuntimeException("No existe el archivo KEY: {$keyPath}");
        }

        $service = new SatMassDownloadService(
            $cerPath,
            $keyPath,
            $password
        );

        // Usa tu flujo actual central
        // Si tu service ya tiene un método para procesar por request, aquí lo llamamos.
        // Cambia esta línea por el método exacto que ya tengas disponible.
        $service->processDownloadRequest($downloadRequest->id);
    }

    public function retry($id)
{
    $req = SatDownloadRequest::findOrFail($id);

    try {
        \App\Jobs\Sat\ProcessSatDownloadJob::dispatchSync($req->id);

        return back()->with('success', 'Verificación ejecutada correctamente.');
    } catch (\Throwable $e) {
        return back()->with('error', 'Ocurrió un error al verificar la solicitud: ' . $e->getMessage());
    }
}
}
