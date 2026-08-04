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

## Avance 2026-07-13

Primera version implementada:

- Ruta: `/nomina/promedios`.
- Controlador: `App\Http\Controllers\Nomina\NominaPromedioController`.
- Vista: `resources/views/nomina/promedios/index.blade.php`.
- Menu: enlace `Promedios` debajo de `Nomina`.
- Fuente de datos inicial: corridas con `nomina_corridas.status = pagada` y recibos con `nomina_recibos.status = pagado`.
- Filtros iniciales:
  - fecha desde/hasta por `nomina_recibos.fecha_pago`;
  - tipo de pago;
  - empleado.
- Calculos iniciales:
  - total neto pagado;
  - promedio mensual real usando dias del periodo / 30.42;
  - promedio por recibo;
  - sueldo teorico diario;
  - sueldo teorico mensual;
  - desglose de deducciones y variables.

Pendientes posteriores:

- Validar numeros contra un caso real de nomina pagada.
- Decidir si el reporte debe permitir incluir corridas `cerradas` ademas de `pagadas`.
- Resolver campo equivalente a aguinaldo legacy si se requiere en el promedio.
- Agregar exportacion Excel/PDF si aplica.

## Hallazgos legacy 2026-07-13

Consulta base revisada: `riveraco.pagos_empleados3` con `riveraco.empleados_web`.

Datos observados:

- `pagos_empleados3` tiene 5,811 pagos.
- Rango historico: `2024-01-01` a `2025-11-23`.
- Empleados distintos en pagos: 152.
- La consulta legacy usa `INNER JOIN empleados_web`, por lo que excluye pagos sin empleado relacionado.
- Se detectaron 1,524 pagos legacy sin match contra `empleados_web`; esto tambien quedaba excluido en legacy.
- En legacy no existian corridas: los pagos directos quedaban pagados por default.

Equivalencias legacy a V2:

- `p.suma` -> `nomina_recibos.sueldo_neto`.
- `p.tiemp_ext` -> `nomina_recibos.horas_extra`.
- `p.metros_lin` -> `nomina_recibos.metros_lin_monto`.
- `p.comisiones` -> `nomina_recibos.comisiones_monto`.
- `p.descuentos` -> `nomina_recibos.descuentos + COALESCE(nomina_recibos.descuentos_legacy, 0)`.
- `p.infonavit` -> `COALESCE(nomina_recibos.infonavit_legacy, nomina_recibos.infonavit_snapshot)`.
- `p.faltas` -> `nomina_recibos.faltas`.
- `p.desde` / `p.hasta` -> `nomina_recibos.fecha_inicio` / `nomina_recibos.fecha_fin`.
- `e.Sueldo_tipo` -> `empleados.Sueldo_tipo`.

Conclusion tecnica:

- El filtro principal debe ser por `empleados.Sueldo_tipo`, no por `nomina_recibos.tipo_pago`.
- Para parecerse al legacy, el reporte no debe depender de `nomina_recibos.status = pagado`, porque actualmente todos los recibos estan como `pendiente`.
- Como en V2 ya existen corridas, la condicion equivalente a "pagado por default" debe salir de la corrida: `nomina_corridas.status IN ('cerrada', 'pagada')`.
- El rango debe filtrarse contra el periodo del recibo: `fecha_inicio >= desde` y `fecha_fin <= hasta`, no contra `fecha_pago`.

Calculos legacy a replicar:

- `diasTranscurridos = DATEDIFF(hasta, desde) + 1`.
- `PromedioMensual = (SUM(sueldo_neto) / diasTranscurridos) * 30`.
- `PromedioMensualExacto = SUM(sueldo_neto) / (diasTranscurridos / 30.42)`.
- `MesesDecimales = diasTranscurridos / 30.42`.
- `MesesCerrados = FLOOR((MesesDecimales * 4)) / 4`.
- `PromedioMensualCerrado = SUM(sueldo_neto) / NULLIF(MesesCerrados, 0)`.
- `PromedioComisionMensual = ((SUM(horas_extra) + SUM(metros_lin_monto) + SUM(comisiones_monto)) / diasTranscurridos) * 30`.

## Checkpoints de ejecucion

1. Ajustar filtros del controlador:
   - Cambiar filtro `tipo_pago` por `Sueldo_tipo`.
   - Filtrar por `nomina_recibos.fecha_inicio` y `nomina_recibos.fecha_fin`.
   - Usar corridas `cerrada` y `pagada`.
   - Quitar dependencia de `nomina_recibos.status = pagado`.

2. Replicar indicadores legacy:
   - Agregar `recibido`, `faltas`, `aguinaldo` si existe equivalente, `descuentos`, `infonavit`, `extra`, `metros`, `comisiones`.
   - Agregar `PromedioComisionMensual`.
   - Agregar `PromedioMensual`, `PromedioMensualExacto`, `MesesDecimales`, `MesesCerrados`, `PromedioMensualCerrado`.

3. Ajustar la vista:
   - Cambiar selector de `tipo_pago` por tipo de sueldo: semanal, quincenal, mensual.
   - Mostrar columnas legacy principales.
   - Separar columnas de importe recibido, variable mensual y promedios.
   - Mantener empleado opcional y rango de fechas.

4. Validar contra legacy:
   - Elegir un rango existente en legacy, por ejemplo `2024-01-01` a `2025-11-23`.
   - Ejecutar consulta legacy para tipo 1 y tipo 2.
   - Comparar formulas con el query V2 usando datos equivalentes.
   - Documentar diferencias esperadas si V2 no tiene la misma base historica.

5. Validar datos V2:
   - Confirmar que las corridas que deben contar esten `cerrada` o `pagada`.
   - Revisar si recibos pendientes dentro de corridas cerradas deben considerarse validos para promedio.
   - Revisar si faltan campos equivalentes a `aguinaldo`.

6. Cierre tecnico:
   - Ejecutar `php -l` al controlador.
   - Ejecutar `php artisan route:list --name=nomina.promedios`.
   - Ejecutar `php artisan view:cache`.
   - Ejecutar `graphify update .` despues de modificar codigo.

## Avance 2026-07-14

Checkpoint 1 ejecutado: ajustes de filtros del controlador.

Cambios aplicados:

- El reporte ahora filtra por `empleados.Sueldo_tipo` en lugar de `nomina_recibos.tipo_pago`.
- El rango ahora usa `nomina_recibos.fecha_inicio >= desde` y `nomina_recibos.fecha_fin <= hasta`.
- La fuente ahora toma corridas con `nomina_corridas.status IN ('cerrada', 'pagada')`.
- Se elimino la dependencia de `nomina_recibos.status = pagado` para que los recibos pendientes dentro de corridas validas puedan contar, alineado con el comportamiento legacy.
- La vista cambio el filtro visible a `Tipo de sueldo` con valores 1, 2 y 3.

Validacion del checkpoint:

- `php -l app/Http/Controllers/Nomina/NominaPromedioController.php`: OK.
- `php artisan view:cache`: OK.
- `php artisan route:list --name=nomina.promedios`: OK.
- Prueba directa del controlador: 55 empleados, 142 recibos, neto considerado 441,148.21 con rango default.
- `graphify update .`: OK.

## Avance 2026-07-14 - Detalle por empleado

Se agrego una vista de detalle por empleado dentro del modulo de promedios.

- Ruta: `nomina/promedios/empleados/{empleado}`.
- Nombre de ruta: `nomina.promedios.empleados.show`.
- Acceso desde la tabla principal: click en el renglon o en el nombre del empleado.
- La vista conserva filtros `desde`, `hasta` y `tipo`.
- Muestra cards superiores con recibos, sueldo + complemento, variable, deducciones y neto.
- Muestra tabla de recibos con periodo, fechas, fecha de pago, estado de corrida, sueldo + complemento, variable, percepciones, deducciones y neto.
- Incluye fila final de totales.

Validacion:

- `php -l app/Http/Controllers/Nomina/NominaPromedioController.php`: OK.
- `php artisan route:list --name=nomina.promedios`: OK.
- `php artisan view:cache`: OK.
- Prueba directa del detalle: vista `nomina.promedios.detalle`, empleado 3390, 28 recibos, neto 97,662.04.

## Avance 2026-07-14 - Regla de promedio base

Decision corregida:

- Los promedios deben calcularse con `Sueldo + Complemento`.
- No se debe usar `Sueldo_real`, `total_percepciones` o `sueldo_neto` para inflar el promedio si no hay datos clasificados que expliquen la diferencia.
- Las diferencias entre percepciones y `Sueldo + Complemento + variables` se muestran como `Revisar sueldo`.
- `Revisar sueldo` no forma parte del promedio; sirve para que auxiliar/admin rastree y actualice el dato correcto del empleado o recibo.

Caso observado:

- Empleado 3495 Antonio Quiroz.
- `Sueldo + Complemento`: 1,581.25 por recibo.
- `Sueldo_real` / percepcion registrada: 3,700.00.
- Diferencia por recibo normal: 2,118.75.
- Diferencia acumulada en el rango: 61,243.75.
- Comision real detectada: 200.00.

Validacion:

- Promedio general ahora usa `total_pago_base = SUM(sueldo_imss_snapshot + complemento_snapshot)`.
- Promedio mensual base y base por recibo salen de `total_pago_base`.
- Antonio queda con base por recibo 1,581.25 y `Revisar sueldo` acumulado 61,243.75.

## Avance 2026-07-14 - Recalculo por empleado

Se agrego accion manual para recalcular recibos desde el detalle del empleado.

- Ruta POST: `nomina/promedios/empleados/{empleado}/recalcular`.
- Nombre de ruta: `nomina.promedios.empleados.recalcular`.
- Boton visible en la vista de detalle: `Recalcular recibos`.
- Usa el rango visible (`desde`, `hasta`, `tipo`) para recalcular solo los recibos mostrados.
- Toma el `Sueldo + Complemento` actual del empleado.
- No inventa datos: si `Sueldo + Complemento` no es valido, no recalcula.
- Conserva variables existentes: horas extra, metros y comisiones.
- Conserva deducciones existentes: faltas, descuentos, descuentos legacy e infonavit.
- Actualiza snapshots y totales del recibo: `sueldo_imss_snapshot`, `complemento_snapshot`, `total_percepciones`, `total_deducciones`, `sueldo_neto`.

Validacion controlada:

- `php -l app/Http/Controllers/Nomina/NominaPromedioController.php`: OK.
- `php artisan route:list --name=nomina.promedios`: OK.
- `php artisan view:cache`: OK.
- Prueba con rollback para empleado 3495: 29 recibos; `Revisar sueldo` baja de 61,243.75 a 0.00 durante el recalculo y vuelve a 61,243.75 despues del rollback.

## Avance 2026-07-14 - Promedios teorico y real en detalle

Se agregaron dos cards al detalle por empleado:

- `Prom. teorico`: se calcula con el sueldo actual del empleado segun su tipo de pago.
  - Semanal: `(Sueldo + Complemento) / 7 * 30`.
  - Quincenal: `(Sueldo + Complemento) / 15 * 30`.
  - Mensual: `Sueldo + Complemento`.
- `Prom. real`: se calcula con lo realmente percibido neto en el rango, incluyendo variables y deducciones ya registradas: `SUM(sueldo_neto) / (dias_periodo / 30.42)`.

Caso de validacion empleado 3495:

- Recibos: 29.
- Base acumulada: 107,242.00.
- Neto acumulado: 107,442.00.
- Dias del periodo: 190.
- Meses equivalentes: 6.2459.
- Prom. teorico: 15,848.57.
- Prom. real: 17,202.03.
