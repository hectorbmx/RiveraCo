<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\EmpresaViaticoTarifa;
use App\Models\EmpresaAlertaDestinatario;
use App\Models\User;
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
use App\Models\DocumentoFirmaDefinicion;
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
use App\Models\TipoRetencion;
use Illuminate\Validation\Rule;
use App\Services\Nomina\ListaRayaResolver;
use App\Services\Maquinas\PreventivoMaquinaService;
use App\Services\Empresa\EmpresaViaticoTarifaService;

class EmpresaConfigController extends Controller
{
private const TIPOS_OBRA_FOLIO = [
    'PILAS' => 'PI',
    'POZOS' => 'PO',
];

public function index(){
      $areas = Area::with(['horarioActivo', 'almacen'])->orderBy('codigo')->orderBy('nombre')->get();
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
        $areas = Area::with(['horarioActivo', 'almacen'])->orderBy('codigo')->orderBy('nombre')->get();
        app(ListaRayaResolver::class)->syncObrasVivas();
        $listasRaya = NominaListaRaya::query()
            ->with(['area', 'obra', 'almacen'])
            ->orderBy('orden')
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();
        $almacenes = Almacen::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'area_id']);
        $vehiculoAlertaDestinatarios = EmpresaAlertaDestinatario::query()
            ->with('user')
            ->where('empresa_config_id', $config->id)
            ->modulo('vehiculos')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->orderBy('email')
            ->get();
        $usuariosNotificables = User::query()
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);

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

        $documentoFirmaDefiniciones = DocumentoFirmaDefinicion::query()
            ->ordenadas()
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
        $tiposRetencion = TipoRetencion::query()
            ->orderByDesc('activo')
            ->orderBy('porcentaje')
            ->get();

        $tarifaViaticoActual = EmpresaViaticoTarifa::actual();
        $historialViaticoTarifas = EmpresaViaticoTarifa::query()
            ->orderByDesc('vigencia_desde')
            ->orderByDesc('id')
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


        // Tipos configurados dinamicamente, por ejemplo OBRA_CIVIL.
        ObraTipoConfiguracion::query()
            ->get()
            ->each(function (ObraTipoConfiguracion $tipo) use ($anioFoliosObra) {
                ObraFolio::firstOrCreate(
                    ['tipo_obra' => $tipo->tipo_obra, 'anio' => $anioFoliosObra],
                    [
                        'prefijo' => $tipo->prefijo,
                        'ultimo_consecutivo' => $this->ultimoConsecutivoObraExistente($tipo->prefijo, $anioFoliosObra),
                    ]
                );
            });
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

        // ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“vigenteÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â = el mÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡s reciente (por ahora solo 1)
        $tarifarioVigente = $tarifarios->first();

        // detalles del vigente (si existe)
        $tarifarioDetalles = $tarifarioVigente
            ? ComisionTarifarioDetalle::with(['rol','uom']) // rol = CatalogoRol
                ->where('tarifario_id', $tarifarioVigente->id)
                ->orderBy('rol_id')
                ->orderBy('trabajo_id')
                ->get()
            : collect();

        // ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ Seguridad (solo para admin/super-admin)
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

            // SelecciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n de rol: por query (?role=ID) o el primero
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
        'documentoFirmaDefiniciones',
        'equiposComputo',
        'empleadosResponsables',
        'centrosCosto',
        'tiposIva',
        'tiposRetencion',
        'tarifaViaticoActual',
        'historialViaticoTarifas',
        'foliosObra',
        'tiposObraConfiguraciones',
        'anioFoliosObra',
        'preventivosMaquinaria',
        'listasRaya',
        'almacenes',
        'vehiculoAlertaDestinatarios',
        'usuariosNotificables',
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

        return back()->with('success', 'ConfiguraciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n general actualizada.');
    }

    /**
     * Secciones nuevas (tabs): por ahora no persisten en empresa_config
     * pero tampoco rompen la app.
     *
     * AquÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­ despuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©s conectamos a tabla meta o a tablas especÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­ficas.
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
            ->with('success', 'ConfiguraciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n de maquinaria guardada.');
    }

    if ($section === 'vehiculos') {
        $data = $request->validate([
            'vehiculo_servicio_km' => ['required', 'integer', 'min:1', 'max:1000000'],
            'vehiculo_servicio_meses' => ['required', 'integer', 'min:1', 'max:120'],
            'vehiculo_alerta_km' => ['required', 'integer', 'min:0', 'max:1000000'],
            'vehiculo_alerta_dias' => ['required', 'integer', 'min:0', 'max:365'],
            'vehiculo_alertas_activas' => ['nullable', 'boolean'],
            'destinatarios' => ['nullable', 'array'],
            'destinatarios.*.id' => ['nullable', 'integer', 'exists:empresa_alerta_destinatarios,id'],
            'destinatarios.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'destinatarios.*.email' => ['nullable', 'email', 'max:255'],
            'destinatarios.*.notificar_correo' => ['nullable', 'boolean'],
            'destinatarios.*.notificar_sistema' => ['nullable', 'boolean'],
            'destinatarios.*.activo' => ['nullable', 'boolean'],
            'nuevo_destinatario.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'nuevo_destinatario.email' => ['nullable', 'email', 'max:255'],
            'nuevo_destinatario.notificar_correo' => ['nullable', 'boolean'],
            'nuevo_destinatario.notificar_sistema' => ['nullable', 'boolean'],
            'nuevo_destinatario.activo' => ['nullable', 'boolean'],
        ]);

        $data['vehiculo_alertas_activas'] = $request->boolean('vehiculo_alertas_activas');

        $config->update(collect($data)->only([
            'vehiculo_servicio_km',
            'vehiculo_servicio_meses',
            'vehiculo_alerta_km',
            'vehiculo_alerta_dias',
            'vehiculo_alertas_activas',
        ])->all());

        $this->guardarDestinatariosAlertaVehiculos($config, $data, $request);

        return redirect()
            ->route('empresa_config.edit', ['tab' => 'vehiculos'])
            ->with('success', 'Configuracion de vehiculos guardada.');
    }

    if (in_array($section, ['rrhh', 'comisiones', 'reglas', 'alertas'], true)) {
        $request->validate([]);

        return back()->with('success', 'Configuracion guardada.');
    }

    return back()->with('error', 'SecciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n de configuraciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n invÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡lida.');
}
private function guardarDestinatariosAlertaVehiculos(EmpresaConfig $config, array $data, Request $request): void
{
    foreach ($data['destinatarios'] ?? [] as $destinatarioData) {
        if (empty($destinatarioData['id'])) {
            continue;
        }

        $destinatario = EmpresaAlertaDestinatario::query()
            ->where('empresa_config_id', $config->id)
            ->modulo('vehiculos')
            ->find($destinatarioData['id']);

        if (! $destinatario) {
            continue;
        }

        $this->persistirDestinatarioAlerta($destinatario, $destinatarioData, $request, 'destinatarios.' . $destinatarioData['id']);
    }

    $nuevo = $data['nuevo_destinatario'] ?? [];
    $hayNuevo = !empty($nuevo['user_id']) || !empty($nuevo['email']);

    if ($hayNuevo) {
        $destinatario = new EmpresaAlertaDestinatario([
            'empresa_config_id' => $config->id,
            'modulo' => 'vehiculos',
        ]);

        $this->persistirDestinatarioAlerta($destinatario, $nuevo, $request, 'nuevo_destinatario');
    }
}

private function persistirDestinatarioAlerta(EmpresaAlertaDestinatario $destinatario, array $data, Request $request, string $prefix): void
{
    $user = !empty($data['user_id']) ? User::find($data['user_id']) : null;
    $email = $data['email'] ?? $user?->email;
    $nombre = $data['nombre'] ?? $destinatario->nombre ?? $user?->name ?? $email;

    if (! $user && ! $email) {
        return;
    }

    $destinatario->fill([
        'user_id' => $user?->id,
        'nombre' => $nombre,
        'email' => $email,
        'notificar_correo' => $request->boolean($prefix . '.notificar_correo'),
        'notificar_sistema' => $request->boolean($prefix . '.notificar_sistema') && (bool) $user,
        'activo' => $request->boolean($prefix . '.activo'),
    ]);

    if (! $destinatario->notificar_correo && ! $destinatario->notificar_sistema) {
        $destinatario->notificar_correo = (bool) $email;
    }

    $destinatario->save();
}

public function storeViaticoTarifa(Request $request, EmpresaViaticoTarifaService $tarifaService)
{
    $data = $request->validate([
        'importe_diario' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        'vigencia_desde' => ['required', 'date'],
        'notas' => ['nullable', 'string', 'max:2000'],
    ]);

    $tarifaService->registrarNuevaTarifa(
        importeDiario: (float) $data['importe_diario'],
        vigenciaDesde: $data['vigencia_desde'],
        creadoPor: auth()->id(),
        notas: $data['notas'] ?? null,
    );

    return redirect()
        ->route('empresa_config.edit')
        ->with('success', 'Tarifa de viaticos registrada correctamente.');
}

public function storeTipoObraConfiguracion(Request $request)
{
    $data = $request->validate([
        'tipo_obra' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/i', 'unique:obra_tipo_configuraciones,tipo_obra'],
        'label' => ['required', 'string', 'max:100'],
        'prefijo' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/i'],
        'area_id' => ['nullable', 'exists:areas,id'],
        'activo' => ['nullable', 'boolean'],
        'folio_anio' => ['required', 'integer', 'min:2020', 'max:2100'],
        'ultimo_consecutivo' => ['nullable', 'integer', 'min:0', 'max:999999'],
    ], [
        'tipo_obra.regex' => 'El tipo de obra solo puede usar letras, numeros y guion bajo.',
        'prefijo.regex' => 'El prefijo solo puede usar letras y numeros.',
    ]);

    $tipoObra = strtoupper(trim($data['tipo_obra']));
    $prefijo = strtoupper(trim($data['prefijo']));
    $anio = (int) $data['folio_anio'];

    $minimo = $this->ultimoConsecutivoObraExistente($prefijo, $anio);
    $ultimoConsecutivo = max($minimo, (int) ($data['ultimo_consecutivo'] ?? $minimo));

    DB::transaction(function () use ($data, $tipoObra, $prefijo, $anio, $ultimoConsecutivo) {
        ObraTipoConfiguracion::create([
            'tipo_obra' => $tipoObra,
            'label' => trim($data['label']),
            'prefijo' => $prefijo,
            'area_id' => $data['area_id'] ?? null,
            'activo' => request()->boolean('activo'),
        ]);

        ObraFolio::firstOrCreate(
            ['tipo_obra' => $tipoObra, 'anio' => $anio],
            [
                'prefijo' => $prefijo,
                'ultimo_consecutivo' => $ultimoConsecutivo,
            ]
        );
    });

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'folios', 'folio_anio' => $anio])
        ->with('success', 'Tipo de obra creado correctamente.');
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
        ->with('success', 'ConfiguraciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n de tipo de obra actualizada.');
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

    // si es la primera cuenta -> principal automÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡tica
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

public function storeDocumentoFirmaDefinicion(Request $request)
{
    $data = $request->validate([
        'documento' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/i'],
        'documento_label' => ['required', 'string', 'max:150'],
        'ambito' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/i'],
        'ambito_label' => ['required', 'string', 'max:150'],
        'campo' => [
            'required',
            'string',
            'max:80',
            'regex:/^[a-z0-9_]+$/i',
            Rule::unique('documento_firma_definiciones', 'campo')
                ->where('documento', Str::lower(trim((string) $request->input('documento'))))
                ->where('ambito', Str::lower(trim((string) $request->input('ambito')))),
        ],
        'campo_label' => ['required', 'string', 'max:150'],
        'orden' => ['required', 'integer', 'min:0', 'max:65535'],
        'activo' => ['nullable', 'boolean'],
    ], [
        'documento.regex' => 'El documento solo puede usar letras, numeros y guion bajo.',
        'ambito.regex' => 'El ambito solo puede usar letras, numeros y guion bajo.',
        'campo.regex' => 'El campo solo puede usar letras, numeros y guion bajo.',
    ]);

    DocumentoFirmaDefinicion::create([
        'documento' => Str::lower(trim($data['documento'])),
        'documento_label' => trim($data['documento_label']),
        'ambito' => Str::lower(trim($data['ambito'])),
        'ambito_label' => trim($data['ambito_label']),
        'campo' => Str::lower(trim($data['campo'])),
        'campo_label' => trim($data['campo_label']),
        'orden' => (int) $data['orden'],
        'activo' => $request->boolean('activo', true),
    ]);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'firmas_imprimibles'])
        ->with('success', 'Definicion de firma agregada correctamente.');
}

public function updateDocumentoFirmaDefinicion(Request $request, DocumentoFirmaDefinicion $firmaDefinicion)
{
    $data = $request->validate([
        'documento_label' => ['required', 'string', 'max:150'],
        'ambito_label' => ['required', 'string', 'max:150'],
        'campo_label' => ['required', 'string', 'max:150'],
        'orden' => ['required', 'integer', 'min:0', 'max:65535'],
        'activo' => ['nullable', 'boolean'],
    ]);

    $firmaDefinicion->update([
        'documento_label' => trim($data['documento_label']),
        'ambito_label' => trim($data['ambito_label']),
        'campo_label' => trim($data['campo_label']),
        'orden' => (int) $data['orden'],
        'activo' => $request->boolean('activo'),
    ]);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'firmas_imprimibles'])
        ->with('success', 'Definicion de firma actualizada correctamente.');
}

public function toggleDocumentoFirmaDefinicion(DocumentoFirmaDefinicion $firmaDefinicion)
{
    $firmaDefinicion->update([
        'activo' => ! $firmaDefinicion->activo,
    ]);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'firmas_imprimibles'])
        ->with('success', 'Estado de la definicion de firma actualizado.');
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
public function storeTipoRetencion(Request $request)
{
    $validated = $request->validate([
        'nombre' => [
            'required',
            'string',
            'max:100',
            Rule::unique('tipos_retencion', 'nombre'),
        ],
        'porcentaje' => [
            'required',
            'numeric',
            'min:0',
            'max:100',
        ],
    ]);

    TipoRetencion::create([
        'nombre' => trim($validated['nombre']),
        'porcentaje' => $validated['porcentaje'],
        'activo' => true,
    ]);

    return redirect()
        ->route('empresa_config.edit', ['tab' => 'tipos_iva'])
        ->with('success', 'El tipo de retenciÃƒÂ³n fue creado correctamente.');
}
}



