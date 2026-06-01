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
use App\Services\Maquinas\PreventivoMaquinaService;

class EmpresaConfigController extends Controller
{

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
        
        // $Catrol = CatalogoRol::orderBy('id')->orderBy('nombre')->get();

        $maquinas = Maquina::orderBy('nombre')->get();
        $preventivosMaquinaria = $preventivoService->calcularParaColeccion($maquinas, $config);
        $catalogoRoles = CatalogoRol::orderBy('nombre')->get();    
        $tarifarios = ComisionTarifario::orderByDesc('vigente_desde')->orderByDesc('id')->get();

        // “vigente” = el más reciente (por ahora solo 1)
        $tarifarioVigente = $tarifarios->first();

        // detalles del vigente (si existe)
        $tarifarioDetalles = $tarifarioVigente
            ? ComisionTarifarioDetalle::with(['rol','uom']) // rol = CatalogoRol
                ->where('tarifario_id', $tarifarioVigente->id)
                ->orderBy('rol_id')
                ->orderBy('trabajo_id')
                ->get()
            : collect();

        // ✅ Seguridad (solo para admin/super-admin)
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

            // Selección de rol: por query (?role=ID) o el primero
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
        'preventivosMaquinaria',
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

        return back()->with('success', 'Configuración general actualizada.');
    }

    /**
     * Secciones nuevas (tabs): por ahora no persisten en empresa_config
     * pero tampoco rompen la app.
     *
     * Aquí después conectamos a tabla meta o a tablas específicas.
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
            ->with('success', 'ConfiguraciÃ³n de maquinaria guardada.');
    }

    if (in_array($section, ['vehiculos', 'rrhh', 'comisiones', 'reglas', 'alertas'], true)) {
        // Validación opcional mínima para que no aceptes basura (puedes ajustar)
        $request->validate([
            // ejemplos (opcionales). Si todavía no guardarás, puedes dejar vacío.
            // 'servicio_km' => ['nullable','integer','min:0'],
        ]);

        return back()->with('success', 'Configuración guardada.');
    }

    return back()->with('error', 'Sección de configuración inválida.');
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

    // si es la primera cuenta -> principal automática
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
        'obligatorio'            => 'nullable|boolean',
        'requiere_vencimiento'   => 'nullable|boolean',
        'activo'                 => 'nullable|boolean',
    ]);

    $documentoTipo->update([
        'nombre'                 => $data['nombre'],
        'descripcion'            => $data['descripcion'] ?? null,
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
