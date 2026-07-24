<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\Area;
use App\Models\CatalogoRol;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\ComisionTarifario;
use App\Models\ComisionTarifarioDetalle;
use App\Models\CuentaBancoEmpresa;
use Illuminate\Support\Facades\DB;
use App\Models\EmpresaDocumentoTipo;
use Illuminate\Support\Str;
use App\Models\Empleado;
use App\Models\EquipoComputo;
use App\Models\CentroCosto;
use App\Models\TipoIva;
use App\Models\Obra;
use App\Models\ObraFolio;
use App\Models\ObraTipoConfiguracion;
use App\Models\NominaListaRaya;
use App\Models\Almacen;
use App\Services\Nomina\ListaRayaResolver;
use App\Services\Maquinas\PreventivoMaquinaService;

class EmpresaConfigController extends Controller
{
private const TIPOS_OBRA_FOLIO = [
    'PILAS' => 'PI',
    'POZOS' => 'PO',
];

public function index(){
      $areas = Area::orderBy('codigo')->orderBy('nombre')->get();
    return view('empresa_config.index',compact('areas'));
}

    // public function edit()
    // {
    //     $config = EmpresaConfig::firstOrCreate(['id' => 1], [
    //         'moneda_base'     => 'MXN',
    //         'iva_por_defecto' => 16.00,
    //         'activa'          => true,
    //     ]);
    //     $maquinas = Maquina::orderBy('nombre')->get();

    //     return view('empresa_config.edit', compact('config','maquinas'));
    // }
    public function edit(PreventivoMaquinaService $preventivoService)
    {

        $config = EmpresaConfig::firstOrCreate(['id' => 1], [
            'moneda_base'     => 'MXN',
            'iva_por_defecto' => 16.00,
            'activa'          => true,
        ]);
        $areas = Area::orderBy('codigo')->orderBy('nombre')->get();
        app(ListaRayaResolver::class)->syncObrasVivas();
        $listasRaya = NominaListaRaya::query()
            ->with(['area', 'obra', 'almacen'])
            ->orderBy('orden')
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();
        $almacenes = Almacen::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        $cuentasBancoEmpresa = CuentaBancoEmpresa::query()
            ->orderByDesc('principal')
            ->orderByDesc('activa')
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get();
        $documentosEmpleadoTipos = EmpresaDocumentoTipo::query()
            ->where('empresa_config_id', $config->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $equiposComputo = EquipoComputo::query()
            ->with([
                'area',
                'responsableActual',
                'fotos',
                'movimientos.responsableAnterior',
                'movimientos.responsableNuevo',
                'movimientos.areaAnterior',
                'movimientos.areaNueva',
                'movimientos.creador',
                'movimientos.fotos',
            ])
            ->orderByRaw("CASE WHEN estatus = 'baja' THEN 1 ELSE 0 END")
            ->orderBy('codigo_inventario')
            ->orderBy('marca')
            ->get();

        $empleadosResponsables = Empleado::query()
            ->where('Estatus', 1)
            ->orderBy('Nombre')
            ->orderBy('Apellidos')
            ->get();

        $centrosCosto = CentroCosto::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        $tiposIva = TipoIva::query()
            ->orderByDesc('activo')
            ->orderBy('porcentaje')
            ->get();

        $anioFoliosObra = (int) request()->integer('folio_anio', now('America/Mexico_City')->year);
        foreach (self::TIPOS_OBRA_FOLIO as $tipoObra => $prefijo) {
            ObraFolio::firstOrCreate(
                ['tipo_obra' => $tipoObra, 'anio' => $anioFoliosObra],
                [
                    'prefijo' => $prefijo,
                    'ultimo_consecutivo' => $this->ultimoConsecutivoObraExistente($prefijo, $anioFoliosObra),
                ]
            );
        }

        $foliosObra = ObraFolio::query()
            ->where('anio', $anioFoliosObra)
            ->orderBy('tipo_obra')
            ->get()
            ->map(function (ObraFolio $folio) {
                $folio->minimo_consecutivo = $this->ultimoConsecutivoObraExistente($folio->prefijo, $folio->anio);
                $folio->siguiente_folio = $this->formatearFolioObra($folio->prefijo, $folio->anio, $folio->ultimo_consecutivo + 1);

                return $folio;
            });

        $tiposObraConfiguraciones = ObraTipoConfiguracion::query()
            ->with('area')
            ->orderBy('tipo_obra')
            ->get();
        
        // $Catrol = CatalogoRol::orderBy('id')->orderBy('nombre')->get();

        $maquinas = Maquina::orderBy('nombre')->get();
        $preventivosMaquinaria = $preventivoService->calcularParaColeccion($maquinas, $config);
        $catalogoRoles = CatalogoRol::orderBy('nombre')->get();    
        $tarifarios = ComisionTarifario::orderByDesc('vigente_desde')->orderByDesc('id')->get();

        // â€œvigenteâ€ = el mÃ¡s reciente (por ahora solo 1)
        $tarifarioVigente = $tarifarios->first();

        // detalles del vigente (si existe)
        $tarifarioDetalles = $tarifarioVigente
            ? ComisionTarifarioDetalle::with(['rol','uom']) // rol = CatalogoRol
                ->where('tarifario_id', $tarifarioVigente->id)
                ->orderBy('rol_id')
                ->orderBy('trabajo_id')
                ->get()
            : collect();

        // âœ… Seguridad (solo para admin/super-admin)
        $roles = collect();
        $permissions = collect();
        
        $selectedRole = null;
        $selectedRolePermissionIds = [];

        if (auth()->check() && auth()->user()->hasAnyRole(['admin', 'super-admin'])) {

            $roles = Role::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get();

            

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get();

            // SelecciÃ³n de rol: por query (?role=ID) o el primero
            $roleId = request()->integer('role');
            $selectedRole = $roleId
                ? $roles->firstWhere('id', $roleId)
                : $roles->first();

            $selectedRolePermissionIds = $selectedRole
                ? $selectedRole->permissions()->pluck('id')->toArray()
                : [];
        }

    return view('empresa_config.edit', compact(
        'config',
        'maquinas',
        'roles',
        'catalogoRoles',
        'areas',
        'permissions',
        'selectedRole',
        'selectedRolePermissionIds',
        'tarifarios',
        'tarifarioVigente',
        'tarifarioDetalles',
        'cuentasBancoEmpresa',
        'documentosEmpleadoTipos',
        'equiposComputo',
        'empleadosResponsables',
        'centrosCosto',
        'tiposIva',
        'foliosObra',
        'tiposObraConfiguraciones',
        'anioFoliosObra',
        'preventivosMaquinaria',
        'listasRaya',
        'almacenes',
    ));
}


  public function update(Request $request)
{
    $config = EmpresaConfig::firstOrCreate(['id' => 1]);

    $section = $request->input('section', 'general');

    if ($section === 'general') {
        $data = $request->validate([
            'razon_social'      => ['nullable', 'string', 'max:200'],
            'nombre_comercial'  => ['nullable', 'string', 'max:200'],
            'rfc'               => ['nullable', 'string', 'max:20'],
            'telefono'          => ['nullable', 'string', 'max:50'],
            'email'             => ['nullable', 'string', 'max:150'],
            'domicilio_fiscal'  => ['nullable', 'string', 'max:255'],
            'moneda_base'       => ['required', 'in:MXN,USD,EUR'],
            'iva_por_defecto'   => ['required', 'numeric', 'min:0', 'max:100'],
            'activa'            => ['nullable', 'boolean'],
        ]);

        $data['activa'] = (bool) $request->boolean('activa');

        $config->update($data);

        return back()->with('success', 'ConfiguraciÃ³n general actualizada.');
    }

    /**
     * Secciones nuevas (tabs): por ahora no persisten en empresa_config
     * pero tampoco rompen la app.
     *
     * AquÃ­ despuÃ©s conectamos a tabla meta o a tablas especÃ­ficas.
     */
    if ($section === 'maquinaria') {
        $data = $request->validate([
            'maquinaria_servicio_horas' => ['required', 'integer', 'min:1', 'max:100000'],
            'maquinaria_servicio_meses' => ['required', 'integer', 'min:1', 'max:120'],
            'maquinaria_alerta_horas' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $config->update($data);

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'maquinaria'])
            ->with('success', 'ConfiguraciÃƒÂ³n de maquinaria guardada.');
    }

    if (in_array($section, ['vehiculos', 'rrhh', 'comisiones', 'reglas', 'alertas'], true)) {
        // ValidaciÃ³n opcional mÃ­nima para que no aceptes basura (puedes ajustar)
        $request->validate([
            // ejemplos (opcionales). Si todavÃ­a no guardarÃ¡s, puedes dejar vacÃ­o.
            // 'servicio_km' => ['nullable','integer','min:0'],
        ]);

        return back()->with('success', 'ConfiguraciÃ³n guardada.');
    }

    return back()->with('error', 'SecciÃ³n de configuraciÃ³n invÃ¡lida.');
}
public function updateFolioObra(Request $request, ObraFolio $folio)
{
    $data = $request->validate([
        'ultimo_consecutivo' => ['required', 'integer', 'min:0', 'max:999999'],
    ]);

    $minimo = $this->ultimoConsecutivoObraExistente($folio->prefijo, $folio->anio);

    if ((int) $data['ultimo_consecutivo'] < $minimo) {
        return back()
            ->withErrors([
                'ultimo_consecutivo' => "El consecutivo no puede ser menor a {$minimo}; ya existen obras con ese folio.",
            ])
            ->withInput();
    }

    $folio->update([
        'ultimo_consecutivo' => (int) $data['ultimo_consecutivo'],
    ]);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'folios', 'folio_anio' => $folio->anio])
        ->with('success', 'Consecutivo de obra actualizado.');
}

public function updateTipoObraConfiguracion(Request $request, ObraTipoConfiguracion $tipo)
{
    $data = $request->validate([
        'area_id' => ['nullable', 'exists:areas,id'],
        'activo' => ['nullable', 'boolean'],
    ]);

    $tipo->update([
        'area_id' => $data['area_id'] ?? null,
        'activo' => $request->boolean('activo'),
    ]);

    if ($tipo->area_id) {
        Obra::where('tipo_obra', $tipo->tipo_obra)->update([
            'area_id' => $tipo->area_id,
        ]);
    }

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'folios'])
        ->with('success', 'ConfiguraciÃ³n de tipo de obra actualizada.');
}

private function ultimoConsecutivoObraExistente(string $prefijo, int $anio): int
{
    return Obra::where('clave_obra', 'like', "{$prefijo}-{$anio}-%")
        ->pluck('clave_obra')
        ->map(function ($clave) use ($prefijo, $anio) {
            if (preg_match('/^' . preg_quote($prefijo, '/') . '-' . $anio . '-(\d+)$/', $clave, $matches)) {
                return (int) $matches[1];
            }

            return 0;
        })
        ->max() ?? 0;
}

private function formatearFolioObra(string $prefijo, int $anio, int $consecutivo): string
{
    return "{$prefijo}-{$anio}-{$consecutivo}";
}

public function storeCuentaBanco(Request $request)
{
    $data = $request->validate([

        'nombre'         => 'required|string|max:255',
        'banco'          => 'required|string|max:255',
        'titular'        => 'required|string|max:255',

        'numero_cuenta'  => 'nullable|string|max:255',
        'clabe'          => 'nullable|string|max:255',

        'moneda'         => 'required|string|max:10',

        'observaciones'  => 'nullable|string',

    ]);

    $data['activa'] = true;

    // si es la primera cuenta -> principal automÃ¡tica
    $data['principal'] = CuentaBancoEmpresa::count() === 0;

    CuentaBancoEmpresa::create($data);

    return back()->with('success', 'Cuenta bancaria registrada correctamente.');
}

public function toggleCuentaBancoActiva(CuentaBancoEmpresa $cuenta)
{
    $cuenta->update([
        'activa' => !$cuenta->activa
    ]);

    return back()->with('success', 'Estado de la cuenta actualizado.');
}

public function marcarCuentaBancoPrincipal(CuentaBancoEmpresa $cuenta)
{
    DB::transaction(function () use ($cuenta) {

        CuentaBancoEmpresa::query()->update([
            'principal' => false
        ]);

        $cuenta->update([
            'principal' => true
        ]);
    });

    return back()->with('success', 'Cuenta principal actualizada.');
}
public function storeDocumentoEmpleado(Request $request)
{
    $empresa = EmpresaConfig::firstOrFail();

    $data = $request->validate([
        'nombre'                 => 'required|string|max:150',
        'descripcion'            => 'nullable|string',
        'aplica_a'               => 'required|in:empleado,cliente,ambos',
        'obligatorio'            => 'nullable|boolean',
        'requiere_vencimiento'   => 'nullable|boolean',
        'activo'                 => 'nullable|boolean',
    ]);

    $codigoBase = Str::upper(
        Str::slug($data['nombre'], '_')
    );

    $codigo = $codigoBase;
    $contador = 1;

    while (
        EmpresaDocumentoTipo::where('empresa_config_id', $empresa->id)
            ->where('codigo', $codigo)
            ->exists()
    ) {
        $codigo = $codigoBase . '_' . $contador;
        $contador++;
    }

    $ultimoOrden = EmpresaDocumentoTipo::where('empresa_config_id', $empresa->id)
        ->max('orden');

    EmpresaDocumentoTipo::create([
        'empresa_config_id'      => $empresa->id,
        'codigo'                 => $codigo,
        'nombre'                 => $data['nombre'],
        'descripcion'            => $data['descripcion'] ?? null,
        'aplica_a'               => $data['aplica_a'],
        'obligatorio'            => $request->boolean('obligatorio'),
        'requiere_vencimiento'   => $request->boolean('requiere_vencimiento'),
        'activo'                 => $request->boolean('activo', true),
        'orden'                  => ($ultimoOrden ?? 0) + 1,
    ]);

    return back()->with('success', 'Documento agregado correctamente.');
}
public function updateDocumentoEmpleado(
    Request $request,
    EmpresaDocumentoTipo $documentoTipo
) {
    $data = $request->validate([
        'nombre'                 => 'required|string|max:150',
        'descripcion'            => 'nullable|string',
        'aplica_a'               => 'required|in:empleado,cliente,ambos',
        'obligatorio'            => 'nullable|boolean',
        'requiere_vencimiento'   => 'nullable|boolean',
        'activo'                 => 'nullable|boolean',
    ]);

    $documentoTipo->update([
        'nombre'                 => $data['nombre'],
        'descripcion'            => $data['descripcion'] ?? null,
        'aplica_a'               => $data['aplica_a'],
        'obligatorio'            => $request->boolean('obligatorio'),
        'requiere_vencimiento'   => $request->boolean('requiere_vencimiento'),
        'activo'                 => $request->boolean('activo', true),
    ]);

    return back()->with('success', 'Documento actualizado correctamente.');
}
public function toggleDocumentoEmpleadoActivo(
    EmpresaDocumentoTipo $documentoTipo
) {
    $documentoTipo->update([
        'activo' => !$documentoTipo->activo
    ]);

    return back()->with(
        'success',
        $documentoTipo->activo
            ? 'Documento activado.'
            : 'Documento desactivado.'
    );
}
public function destroyDocumentoEmpleado(
    EmpresaDocumentoTipo $documentoTipo
) {
    $documentoTipo->delete();

    return back()->with(
        'success',
        'Documento eliminado correctamente.'
    );
}

public function storeCentroCosto(Request $request)
{
    $data = $request->validate([
        'codigo' => ['nullable', 'string', 'max:40', 'unique:centros_costo,codigo'],
        'nombre' => ['required', 'string', 'max:160', 'unique:centros_costo,nombre'],
        'descripcion' => ['nullable', 'string'],
    ]);

    $data['activo'] = true;

    CentroCosto::create($data);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'centros_costo'])
        ->with('success', 'Centro de costo creado correctamente.');
}

public function toggleCentroCosto(CentroCosto $centroCosto)
{
    $centroCosto->update([
        'activo' => !$centroCosto->activo,
    ]);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'centros_costo'])
        ->with('success', 'Estado del centro de costo actualizado.');
}

public function storeTipoIva(Request $request)
{
    $data = $request->validate([
        'nombre' => ['required', 'string', 'max:80'],
        'porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
        'default' => ['nullable', 'boolean'],
    ]);

    DB::transaction(function () use ($request, $data) {
        if ($request->boolean('default')) {
            TipoIva::query()->update(['default' => false]);
        }

        TipoIva::create([
            'nombre' => $data['nombre'],
            'porcentaje' => $data['porcentaje'],
            'activo' => true,
            'default' => $request->boolean('default'),
        ]);
    });

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'iva'])
        ->with('success', 'Tipo de IVA registrado.');
}

public function toggleTipoIva(TipoIva $tipoIva)
{
    $tipoIva->update([
        'activo' => !$tipoIva->activo,
    ]);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'iva'])
        ->with('success', 'Estado del tipo de IVA actualizado.');
}

public function marcarTipoIvaDefault(TipoIva $tipoIva)
{
    DB::transaction(function () use ($tipoIva) {
        TipoIva::query()->update(['default' => false]);
        $tipoIva->update(['default' => true, 'activo' => true]);
    });

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'iva'])
        ->with('success', 'IVA por defecto actualizado.');
}
}



