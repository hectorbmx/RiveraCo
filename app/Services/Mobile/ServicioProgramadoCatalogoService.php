<?php

namespace App\Services\Mobile;

use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\Maquina;
use App\Models\Vehiculo;
use App\Services\Mantenimiento\MantenimientoCatalogoService;
use App\Services\Maquinas\MaquinaHorometroService;
use App\Services\Vehiculos\PreventivoVehiculoService;
use Illuminate\Support\Arr;

class ServicioProgramadoCatalogoService
{
    public function __construct(
        private MantenimientoCatalogoService $catalogos,
        private MaquinaHorometroService $horometro,
        private PreventivoVehiculoService $preventivoVehiculo
    ) {}

    public function obtener(?int $maquinaId = null, ?int $vehiculoId = null): array
    {
        $maquinaria = $this->catalogos->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_MAQUINARIA);
        $vehiculos = $this->catalogos->serviciosPreventivosPorAmbito(EmpresaServicioPreventivoTipo::AMBITO_VEHICULO);
        $almacen = $this->catalogos->almacenGiralda();
        $lectura = null;
        $equipo = null;
        $materiales = [];

        if ($maquinaId !== null) {
            $maquina = Maquina::query()->findOrFail($maquinaId);
            $equipo = ['ambito' => 'maquinaria', 'id' => (int) $maquina->id];
            $lectura = ['campo' => 'horometro', 'valor' => $this->horometro->horometroSugeridoParaAsignacion($maquina), 'unidad' => 'horas'];
            $materiales = $this->catalogos->materialesPreventivosParaMaquina($maquinaId);
        } elseif ($vehiculoId !== null) {
            $vehiculo = Vehiculo::query()->findOrFail($vehiculoId);
            $equipo = ['ambito' => 'vehiculo', 'id' => (int) $vehiculo->id];
            $preventivo = $this->preventivoVehiculo->calcularParaVehiculo($vehiculo);
            $lectura = ['campo' => 'km_actuales', 'valor' => $preventivo['km_actual'] ?? null, 'unidad' => 'km'];
        }

        $avisos = [];
        if ($vehiculos->isEmpty()) {
            $avisos[] = 'No hay tipos preventivos activos de vehículo configurados.';
        }
        if (! $almacen) {
            $avisos[] = 'No hay un almacén Giralda activo configurado; no se puede consultar su inventario.';
        }
        if ($vehiculoId !== null) {
            $avisos[] = 'Las plantillas automáticas de materiales y el abastecimiento de vehículos todavía no están implementados en el flujo web.';
        }

        $campos = ['id', 'ambito', 'nombre', 'codigo', 'unidad', 'intervalo_valor', 'intervalo_meses', 'alerta_valor', 'alerta_dias', 'orden'];

        return ['ok' => true, 'data' => [
            'tipo' => 'programado',
            'servicios_preventivos' => [
                'maquinaria' => $maquinaria->map(fn ($tipo) => Arr::only($tipo->toArray(), $campos))->values(),
                'vehiculo' => $vehiculos->map(fn ($tipo) => Arr::only($tipo->toArray(), $campos))->values(),
            ],
            'categoria_mantenimiento' => ['captura' => 'texto_libre', 'obligatoria' => true, 'max_caracteres' => 100],
            'estados_servicio' => ['borrador', 'orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado'],
            'equipo' => $equipo,
            'lectura_sugerida' => $lectura,
            'almacen' => $almacen ? ['id' => (int) $almacen->id, 'codigo' => $almacen->codigo, 'nombre' => $almacen->nombre] : null,
            'materiales_por_servicio' => (object) $materiales,
            'capacidades' => [
                'inventario_giralda' => $almacen !== null,
                'plantilla_materiales_maquinaria' => true,
                'plantilla_materiales_vehiculo' => false,
                'tipos_maquinaria_configurados' => $maquinaria->isNotEmpty(),
                'tipos_vehiculo_configurados' => $vehiculos->isNotEmpty(),
            ],
            'avisos' => $avisos,
        ]];
    }
}
