<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\TelephonyPhoneNumber;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $tipo = $request->query('tipo', 'todos');
        $perPage = (int) $request->query('per_page', 25);
        $perPageOpciones = [15, 25, 50, 100];

        if (!in_array($perPage, $perPageOpciones, true)) {
            $perPage = 25;
        }

        $phoneableTypes = [
            'clientes' => Cliente::class,
            'proveedores' => Proveedor::class,
            'empleados' => Empleado::class,
        ];

        if (!array_key_exists($tipo, $phoneableTypes) && $tipo !== 'todos') {
            $tipo = 'todos';
        }

        $agenda = TelephonyPhoneNumber::query()
            ->where('is_active', true)
            ->whereIn('phoneable_type', array_values($phoneableTypes))
            ->when($tipo !== 'todos', fn ($query) => $query->where('phoneable_type', $phoneableTypes[$tipo]))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('display_name', 'like', "%{$search}%")
                        ->orWhere('raw_number', 'like', "%{$search}%")
                        ->orWhere('normalized_number', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhereHasMorph('phoneable', [Cliente::class], function ($cliente) use ($search) {
                            $cliente->where('nombre_comercial', 'like', "%{$search}%")
                                ->orWhere('razon_social', 'like', "%{$search}%")
                                ->orWhere('rfc', 'like', "%{$search}%")
                                ->orWhereHas('contactos', function ($contacto) use ($search) {
                                    $contacto->where('nombre', 'like', "%{$search}%")
                                        ->orWhere('cargo', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHasMorph('phoneable', [Proveedor::class], function ($proveedor) use ($search) {
                            $proveedor->where('nombre', 'like', "%{$search}%")
                                ->orWhere('razon_social', 'like', "%{$search}%")
                                ->orWhere('nombre_contacto', 'like', "%{$search}%")
                                ->orWhere('rfc', 'like', "%{$search}%");
                        })
                        ->orWhereHasMorph('phoneable', [Empleado::class], function ($empleado) use ($search) {
                            $empleado->where('Nombre', 'like', "%{$search}%")
                                ->orWhere('Apellidos', 'like', "%{$search}%")
                                ->orWhere('Puesto', 'like', "%{$search}%")
                                ->orWhereHas('areaRef', function ($area) use ($search) {
                                    $area->where('nombre', 'like', "%{$search}%")
                                        ->orWhere('codigo', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->with([
                'phoneable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        Empleado::class => ['areaRef'],
                    ]);
                },
            ])
            ->orderBy('display_name')
            ->orderByDesc('is_primary')
            ->orderBy('label')
            ->paginate($perPage)
            ->appends([
                'q' => $search,
                'tipo' => $tipo,
                'per_page' => $perPage,
            ]);

        $stats = [
            'total' => TelephonyPhoneNumber::where('is_active', true)
                ->whereIn('phoneable_type', array_values($phoneableTypes))
                ->count(),
            'clientes' => TelephonyPhoneNumber::where('is_active', true)->where('phoneable_type', Cliente::class)->count(),
            'proveedores' => TelephonyPhoneNumber::where('is_active', true)->where('phoneable_type', Proveedor::class)->count(),
            'empleados' => TelephonyPhoneNumber::where('is_active', true)->where('phoneable_type', Empleado::class)->count(),
        ];

        return view('agenda.index', compact(
            'agenda',
            'search',
            'tipo',
            'perPage',
            'perPageOpciones',
            'stats'
        ));
    }
}
