<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use Illuminate\Http\Request;
use App\Models\Maquina;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EmpresaConfigController extends Controller
{

public function index(){
    return view('empresa_config.index');
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
    public function edit()
{
    $config = EmpresaConfig::firstOrCreate(['id' => 1], [
        'moneda_base'     => 'MXN',
        'iva_por_defecto' => 16.00,
        'activa'          => true,
    ]);

    $maquinas = Maquina::orderBy('nombre')->get();

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
        'permissions',
        'selectedRole',
        'selectedRolePermissionIds'
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
    if (in_array($section, ['vehiculos', 'maquinaria', 'rrhh', 'comisiones', 'reglas', 'alertas'], true)) {
        // Validación opcional mínima para que no aceptes basura (puedes ajustar)
        $request->validate([
            // ejemplos (opcionales). Si todavía no guardarás, puedes dejar vacío.
            // 'servicio_km' => ['nullable','integer','min:0'],
        ]);

        return back()->with('success', 'Configuración guardada.');
    }

    return back()->with('error', 'Sección de configuración inválida.');
}


}
