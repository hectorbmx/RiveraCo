# Idea: Homologacion de almacenes

## Meta

Homologar el funcionamiento de los almacenes para que Giralda y Huentitan compartan el mismo criterio operativo base:

```txt
entrada -> stock
salida a obra -> borrador -> aplicar -> descuenta stock -> genera kardex -> genera gasto de obra
```

La fabricacion debe quedar como una capacidad opcional del almacen, no como regla obligatoria para todos.

## Contexto operativo

- Huentitan ya tiene un flujo mas completo para salidas a obra y fabricacion.
- Giralda no fabrica por el momento; opera mas como almacen/area de gasto, compras, caja chica, asistencias y horas extras.
- A futuro Giralda podria producir productos internos, pero no debemos forzar ese flujo ahora.
- Para costos de obra, una salida aplicada a una obra debe representar gasto de obra.

## Hallazgos: inventario general

Archivos principales:

- `app/Models/InventarioDocumento.php`
- `app/Models/InventarioDocumentoDetalle.php`
- `app/Models/InventarioMovimiento.php`
- `app/Http/Controllers/Inventario/InventarioDocumentoController.php`
- `app/Services/Inventario/InventarioDocumentoService.php`

Tablas principales:

- `inventario_documentos`
- `inventario_documento_detalles`
- `inventario_stock`
- `inventario_movimientos`

Hallazgos:

- `inventario_documentos` ya tiene `obra_id`.
- Para documentos tipo `salida` o `resguardo`, el controlador exige `obra_id`.
- Al aplicar una salida, el servicio descuenta stock.
- El costo de salida se toma del `costo_promedio` del stock al momento de aplicar.
- El costo final queda guardado en `inventario_movimientos.costo_unitario`.
- El importe no queda como campo directo del detalle de inventario general.
- Para calcular gasto de obra desde inventario general, la fuente confiable es `inventario_movimientos`:

```txt
obra_id = obra actual
tipo_movimiento = out
importe = cantidad * costo_unitario
```

## Hallazgos: Huentitan

Archivos principales:

- `app/Models/HuentitanSalida.php`
- `app/Models/HuentitanSalidaDetalle.php`
- `app/Http/Controllers/Inventario/HuentitanSalidaController.php`
- `app/Http/Controllers/Inventario/HuentitanInventarioController.php`

Tablas principales:

- `huentitan_salidas`
- `huentitan_salida_detalles`
- `huentitan_formulas`
- `huentitan_formula_materiales`
- `huentitan_formula_herramientas`
- `huentitan_ordenes_fabricacion`
- `huentitan_orden_fabricacion_materiales`

Hallazgos de salidas:

- `huentitan_salidas` tiene `obra_id`.
- Las salidas tienen estado `borrador`, `aplicada`, `cancelada`.
- Los detalles guardan:
  - `cantidad_solicitada`
  - `cantidad_salida`
  - `stock_disponible_snapshot`
  - `cantidad_faltante`
  - `requiere_compra`
  - `cantidad_sugerida_compra`
  - `costo_unitario`
  - `importe`
- Al aplicar una salida, descuenta stock.
- Al aplicar, tambien crea registros en `inventario_movimientos`.
- El modelo `HuentitanSalida` ya tiene `getTotalImporteAttribute()`.
- Para gasto de obra, Huentitan se puede calcular directamente con:

```txt
huentitan_salidas.estado = aplicada
huentitan_salidas.obra_id = obra actual
sum(huentitan_salida_detalles.importe)
```

Pero hay que evitar duplicar si tambien se suma desde `inventario_movimientos`.

## Hallazgos: fabricacion Huentitan

Productos ya tienen campos generales:

- `tipo_inventario`
- `origen_abastecimiento`
- `requiere_formula`

Estos campos no son exclusivos de Huentitan y pueden servir para preparar produccion futura en cualquier almacen.

Flujo Huentitan:

- Un producto puede requerir formula.
- La formula define materiales, cantidad base, unidad base, merma, tiempo estimado y notas.
- La orden de fabricacion congela la formula vigente.
- Se calculan materiales requeridos y costo estimado.
- Se puede apartar material.
- Se puede enviar a produccion.
- Al enviar a produccion se consumen insumos del inventario.

## Diferencia conceptual actual

| Tema | Inventario general / Giralda | Huentitan |
|---|---|---|
| Salidas a obra | Si, via `inventario_documentos` | Si, via `huentitan_salidas` |
| Detalle con importe persistido | No claramente | Si, `huentitan_salida_detalles.importe` |
| Kardex | Si, `inventario_movimientos` | Si, `inventario_movimientos` |
| Obra | `obra_id` en documento y movimiento | `obra_id` en salida y movimiento |
| Produccion interna | No activa | Si, formulas y ordenes de fabricacion |
| Estado aplicado | `aplicado` | `aplicada` |

## Criterio recomendado

No pensar en:

```txt
Huentitan tiene salidas
Giralda tiene salidas
```

Pensar en:

```txt
Almacen tiene salidas
Almacen puede o no puede fabricar
```

Modelo operativo esperado:

```txt
almacen sin fabricacion:
  entradas
  salidas a obra
  kardex
  gasto de obra

almacen con fabricacion:
  entradas
  salidas a obra
  kardex
  gasto de obra
  formulas
  ordenes de fabricacion
  consumo de insumos
  entrada de producto terminado
```

## Decision de arquitectura sugerida

Agregar una capacidad al almacen, por ejemplo:

```txt
almacenes.permite_fabricacion
```

Si `permite_fabricacion = false`:

- El almacen solo ve entradas, salidas, stock y kardex.
- No ve formulas.
- No ve ordenes de fabricacion.
- Giralda quedaria aqui por ahora.

Si `permite_fabricacion = true`:

- El almacen ve entradas, salidas, stock, kardex.
- Tambien ve formulas y ordenes de fabricacion.
- Huentitan quedaria aqui.

## Fuente de gastos de obra

Para evitar duplicidades, conviene elegir una fuente unica para el total global de gasto de almacen.

Opcion recomendada para total global:

```txt
inventario_movimientos
```

Motivo:

- Es la capa comun.
- Huentitan tambien registra movimientos al aplicar salidas.
- Representa el impacto real de inventario.
- Permite sumar cualquier almacen con una sola consulta.

Regla de gasto:

```txt
movimiento con obra_id
movimiento tipo salida/out
cantidad * costo_unitario
```

Para detalle operativo o trazabilidad, se puede mostrar el documento origen:

- Si viene de Huentitan, link a `huentitan_salidas`.
- Si viene de inventario general, link a `inventario_documentos`.

## Riesgos a cuidar

- No duplicar gastos sumando `huentitan_salida_detalles.importe` y tambien `inventario_movimientos`.
- No migrar fabricacion a Giralda antes de necesitarla.
- No romper ligas actuales de Huentitan con ordenes de compra.
- Normalizar criterios de estado sin forzar migracion historica inmediata.
- Revisar que cancelaciones queden fuera del gasto.

## Plan por fases

### Fase 1: lectura comun de gastos de almacen

- Crear consulta/servicio que calcule gasto de almacen por obra desde `inventario_movimientos`.
- Filtrar solo movimientos de salida.
- Calcular importe como `cantidad * costo_unitario`.
- Mostrar fuente, almacen, fecha, producto, cantidad, costo e importe.
- Checkpoint: una salida aplicada de Huentitan aparece como gasto de obra sin duplicarse.

### Fase 2: homologar salidas de inventario general

- Completar detalles de inventario general con campos similares a Huentitan si hace falta:
  - `descripcion`
  - `unidad`
  - `cantidad_solicitada`
  - `cantidad_salida`
  - `stock_disponible_snapshot`
  - `cantidad_faltante`
  - `requiere_compra`
  - `cantidad_sugerida_compra`
  - `importe`
- Mantener compatibilidad con datos actuales.
- Checkpoint: Giralda puede crear salida a obra con costo/importe visible.

### Fase 3: servicio comun de salidas

- Extraer logica comun a un servicio tipo `AlmacenSalidaService`.
- Responsabilidades:
  - crear salida en borrador
  - calcular stock disponible
  - calcular faltantes
  - tomar costo promedio
  - calcular importe
  - aplicar salida
  - descontar stock
  - crear kardex
- Checkpoint: Huentitan y Giralda usan el mismo criterio de aplicacion.

### Fase 4: fabricacion opcional

- Agregar bandera/capacidad por almacen.
- Mantener Huentitan con fabricacion activa.
- Dejar Giralda sin fabricacion por default.
- Preparar UI para mostrar formulas solo si el almacen permite fabricacion.
- Checkpoint: Giralda no ve fabricacion, Huentitan si.

### Fase 5: reportes y obra

- En `obras/{id}/edit`, agregar seccion/tab de gastos de almacen.
- Sumar desde movimientos.
- Mostrar detalle por almacen/documento/producto.
- Integrar el total al resumen de gastos de obra junto con caja chica, sueldos, OC y otras fuentes.
- Checkpoint: gasto de almacen se ve en obra y suma coherente.

## Estado actual de la idea

Esta es una idea de homologacion. No se ha ejecutado ningun cambio de codigo en esta fase.

Prioridad sugerida:

1. Primero crear lectura comun de gastos desde `inventario_movimientos`.
2. Despues mejorar Giralda para que sus salidas se comporten como Huentitan.
3. Por ultimo generalizar fabricacion como capacidad opcional.
