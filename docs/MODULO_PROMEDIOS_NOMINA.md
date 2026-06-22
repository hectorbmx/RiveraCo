# Módulo: Promedios de Nómina por Empleado

**Fecha de creación del documento:** 22 de junio de 2026
**Estado:** Pendiente de implementación (Guardado para otro día)

## Contexto y Requerimiento
Se necesita crear un módulo dependiente de la generación de nóminas para calcular los promedios de sueldos de los empleados. La idea es ir acumulando o contando todo lo que gana un empleado para poder sacar su promedio mensual.

- Hay 3 tipos de pagos: Semanal (1), Quincenal (2), Mensual (3). Se debe poder sacar el promedio de cada tipo.
- Se deben calcular 2 tipos de promedios:
  1. **Promedio Real ("Lo que le cayó a la bolsa"):** Basado en el total percibido real.
  2. **Promedio Teórico:** Basado en el sueldo registrado.

## Consultas SQL Legacy de Referencia
El sistema legacy (`pagos_empleados3` y `empleados_web`) usaba las siguientes reglas:
- `p.suma` equivale al total neto pagado.
- Promedios mensuales se aproximaban dividiendo los días transcurridos entre 30.42.
- Cálculos teóricos según `Sueldo_tipo`:
  - 1 (Semanal): `(Sueldo_real / 7) * 30`
  - 2 (Quincenal): `(Sueldo_real / 15) * 30`

## Plan de Implementación para V2 (Laravel 10 + Tailwind/Alpine)

### 1. Modelo de Datos
En V2, los datos a consultar están en:
- **`Empleado`** (`app/Models/Empleado.php`): Contiene `id_Empleado`, `Nombre`, `Apellidos`, `Sueldo_tipo`, `Sueldo_real`.
- **`NominaRecibo`** (`app/Models/NominaRecibo.php`): Contiene `fecha_inicio`, `fecha_fin`, `sueldo_neto` (equivalente a `p.suma`), `faltas`, `descuentos_legacy`, `horas_extra`, `metros_lin_monto`, `comisiones_monto`.

### 2. Backend (Controlador)
Crear `app/Http/Controllers/Nomina/NominaPromedioController.php`:
- Método `index()`: 
  - Recibe por Request: `tipo` (1, 2, o 3), `desde` (fecha), `hasta` (fecha).
  - Usará `DB::table('empleados')` con un `join('nomina_recibos')` aplicando la agrupación y cálculos en crudo (`DB::raw`) idénticos a los del legacy, devolviendo el cálculo real y el teórico.

### 3. Rutas (`routes/web.php`)
Agregar dentro del grupo de middleware `auth`:
```php
Route::get('nomina/promedios', [\App\Http\Controllers\Nomina\NominaPromedioController::class, 'index'])
    ->name('nomina.promedios.index');
```

### 4. Frontend (Vista)
Crear `resources/views/nomina/promedios/index.blade.php`:
- Un formulario de filtrado (Fechas y Tipo de Sueldo).
- Una tabla de resultados mostrando:
  - Empleado
  - Días Transcurridos
  - Sueldo Teórico Diario y Mensual
  - Total Percibido (Real)
  - Promedio Mensual Real Exacto
  - Desglose de faltas, horas extra, etc.

## Preguntas Abiertas (Por resolver cuando se retome)
1. **Aguinaldo:** En el query legacy se sumaba `p.aguinaldo`. En V2, `NominaRecibo` no tiene ese campo explícito (posiblemente esté bajo `prima_vac_legacy` u otro). Queda pendiente revisar dónde se guarda.
2. **Ubicación en el Menú:** Decidir en qué parte de la navegación web del sistema V2 se colocará el botón para entrar a esta pantalla.
3. **Exportación:** Validar si se requerirá un botón para exportar este reporte a Excel / PDF.
