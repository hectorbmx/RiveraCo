<?php
namespace App\Exports;

use App\Models\Empleado;
use App\Models\EmpresaConfig;
use App\Models\EmpresaDocumentoTipo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Exports\EmpleadosExport;
use Maatwebsite\Excel\Facades\Excel;

class EmpleadosExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $search;
    protected $estatus;
    protected $area;
    protected $documentosObligatorios;

    public function __construct($search, $estatus, $area)
    {
        $this->search  = $search;
        $this->estatus = $estatus;
        $this->area    = $area;

        $empresa = EmpresaConfig::first();
        if ($empresa) {
            $this->documentosObligatorios = EmpresaDocumentoTipo::query()
                ->where('empresa_config_id', $empresa->id)
                ->where('activo', true)
                ->where('obligatorio', true)
                ->get();
        } else {
            $this->documentosObligatorios = collect();
        }
    }

    public function query()
    {
        return Empleado::with(['areaRef', 'documentos.documentoTipo'])
            ->when($this->search, function ($q) {
                $q->where(function($sub) {
                    $sub->where('Nombre', 'like', "%{$this->search}%")
                        ->orWhere('Apellidos', 'like', "%{$this->search}%")
                        ->orWhere('Puesto', 'like', "%{$this->search}%")
                        ->orWhereHas('areaRef', function ($areaQ) {
                            $areaQ->where('nombre', 'like', "%{$this->search}%")
                                  ->orWhere('codigo', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->estatus === 'activo', fn($q) => $q->where('Estatus', 1))
            ->when($this->estatus === 'baja', fn($q) => $q->where('Estatus', 2))
            ->when($this->area, fn($q) => $q->where('Area', $this->area))
            ->orderBy('Nombre');
    }

    public function headings(): array
    {
        return [
            'ID Empleado',
            'Nombre',
            'Apellidos',
            'Área',
            'Puesto',
            'Sueldo',
            'Fecha de Ingreso',
            '% Documentos',
            'Estatus'
        ];
    }

    public function map($emp): array
    {
        // Cálculo del porcentaje de documentos
        $obligatoriosIds = $this->documentosObligatorios->pluck('id')->toArray();

        $documentosCargadosIds = $emp->documentos
            ->filter(fn($doc) => $doc->documento_tipo_id)
            ->sortByDesc('created_at')
            ->unique('documento_tipo_id')
            ->pluck('documento_tipo_id')
            ->toArray();

        $totalObligatorios = count($obligatoriosIds);
        $totalCargados = collect($obligatoriosIds)->filter(fn($id) => in_array($id, $documentosCargadosIds))->count();

        $porcentaje = $totalObligatorios > 0
            ? round(($totalCargados / $totalObligatorios) * 100)
            : 0;

        return [
            $emp->id_Empleado,
            $emp->Nombre,
            $emp->Apellidos,
            $emp->areaRef->nombre ?? '-',
            $emp->Puesto ?? '-',
            $emp->Sueldo_real ?? $emp->Sueldo ?? 0,
            $emp->Fecha_ingreso ? $emp->Fecha_ingreso->format('d/m/Y') : '-',
            $porcentaje . '%',
            (int)$emp->Estatus === 2 ? 'Baja' : 'Activo',
        ];
    }
    

public function export(Request $request)
{
    $search  = $request->get('q');
    $estatus = $request->get('estatus', 'activo');
    $estatus = in_array($estatus, ['activo', 'baja', 'todos'], true) ? $estatus : 'activo';
    $area    = $request->get('area');
    $areaCodigo = $request->get('area_codigo');

    if ($areaCodigo && !$area) {
        $area = Area::where('codigo', $areaCodigo)->value('id');
    }

    $fileName = 'empleados_' . now()->format('Y_m_d_His') . '.xlsx';

    return Excel::download(new EmpleadosExport($search, $estatus, $area), $fileName);
}
}