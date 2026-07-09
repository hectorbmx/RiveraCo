# Roadmap - Nomina: corridas, recibos, extras, comisiones y promedios

## Objetivo

Consolidar el flujo de nomina para que la creacion de corridas, generacion de recibos, captura de ajustes, extras, comisiones y promedios sea confiable, auditable y resistente a perdida de avance.

Punto de entrada actual:

- `GET /nomina/generador`
- `POST /nomina/corridas`
- `GET /nomina/corridas/{corrida}`
- `POST /nomina/corridas/{corrida}/recibos/generar`
- `POST /nomina/corridas/{corrida}/recibos/guardar`

## Auditoria inicial

### Flujo actual confirmado

- El generador muestra corridas por rango, tipo y estatus.
- La corrida se crea sin recibos, con `tipo_pago`, fechas, periodo y `status = abierta`.
- Los recibos se generan en un segundo paso.
- La generacion de recibos filtra empleados activos por `empleados.Estatus = 1`.
- El tipo de pago se traduce a `empleados.Sueldo_tipo`:
  - `semanal` => `1`
  - `quincenal` => `2`
  - `mensual` => `3`
- Las comisiones se consultan por rango usando `comision_personal`, `obra_empleado` y `comisiones`.
- La pantalla de corrida permite editar deducciones, horas extra, metros, comisiones, obra, notas y un extra.
- El guardado actual es general: si no se presiona "Guardar cambios", se pierde lo capturado.

### Hallazgos tecnicos

- `nomina_pagos_extra` tiene `UNIQUE(recibo_id)`, por eso hoy solo permite un extra por recibo.
- El controlador usa `updateOrInsert(['recibo_id' => $recibo->id])`, reforzando el extra unico.
- `NominaRecibo` no tiene relacion formal `pagosExtra()` o `extras()`.
- La relacion `Empleado::obraActiva()` valida solo `obra_empleado.activo = 1`, pero no valida que la obra este viva.
- El select de obras en la corrida carga obras sin filtrar por estado operativo.
- Al generar recibos se calculan comisiones y horas extra, pero los totales iniciales guardados pueden quedar solo con el sueldo base hasta que el usuario guarde cambios.
- Las comisiones quedan como monto final en `nomina_recibos.comisiones_monto`, pero no queda trazabilidad de que comisiones alimentaron cada recibo.
- Existe `empleados.listaraya`, pero actualmente se captura como numero libre y no se encontro un catalogo formal de listas de raya.
- Existen `areas`, `almacenes` y obras, pero no hay una fuente de verdad que agrupe listas de raya de obra, oficina, almacenes, pilas y pozos.

### Regla operativa nueva: listas de raya

- Cada obra viva debe funcionar como una lista de raya.
- Si hay N obras vivas, deberian existir N listas de raya de obra.
- Ademas existen listas de raya operativas que no son obra:
  - Oficina
  - Almacen Giralda
  - Almacen Huentitan
  - Pilas
  - Pozos
- Cada empleado debe tener una lista de raya primaria.
- Si el empleado esta asignado a una obra viva, su recibo debe ir a la lista de raya de esa obra.
- Si el empleado no esta asignado a obra viva, debe regresar a su lista primaria/area.
- Esta regla debe aplicarse al generar recibos y debe quedar guardada como snapshot en cada recibo.

## Checkpoints de implementacion

### Fase 1 - Catalogo de listas de raya en configuracion de empresa

- [ ] Agregar tab `Listas de raya` en configuracion de empresa.
- [ ] Definir entidad/catalogo `nomina_listas_raya`.
- [ ] Tipos sugeridos:
  - [ ] `obra`
  - [ ] `area`
  - [ ] `almacen`
  - [ ] `oficina`
  - [ ] `operativa`
- [ ] Campos sugeridos:
  - [ ] `nombre`
  - [ ] `tipo`
  - [ ] `area_id`
  - [ ] `obra_id`
  - [ ] `almacen_id`
  - [ ] `activo`
  - [ ] `es_automatica`
  - [ ] `orden`
- [ ] Migrar/interpretar `empleados.listaraya`.
- [ ] Decidir si `empleados.listaraya` se conserva como legacy o se reemplaza por `lista_raya_id`.
- [ ] Agregar campo `lista_raya_principal_id` en empleados.
- [ ] Agregar select `Lista de raya principal` en crear/editar empleado.
- [ ] Agregar lista de raya primaria al empleado.
- [ ] Crear/actualizar automaticamente listas de raya para obras vivas como `es_automatica = true`.
- [ ] Crear listas fijas:
  - [ ] Oficina
  - [ ] Almacen Giralda
  - [ ] Almacen Huentitan
  - [ ] Pilas
  - [ ] Pozos
- [ ] Definir regla de resolucion:
  - [ ] Si empleado tiene asignacion activa a obra viva => lista de raya de esa obra (override temporal).
  - [ ] Si no tiene obra viva => `empleados.lista_raya_principal_id`.
  - [ ] Si no tiene lista principal => fallback por area, si existe mapeo.
  - [ ] Si no tiene area => lista "Sin clasificar" o bloquear generacion.
- [ ] Agregar snapshots al recibo:
  - [ ] `lista_raya_id`
  - [ ] `lista_raya_nombre`
  - [ ] `lista_raya_tipo`
- [ ] Mostrar la corrida agrupada por listas de raya.
- [ ] Permitir imprimir/exportar una lista de raya individual.
- [ ] Agregar filtro por lista de raya en la vista de corrida.

## AQUI UNA NOTA, LAS LISTAS DE RAYA CUANDO SON OBRAS, NO NECESITAN ESTAR EN MI CONFIG YA QUE SON LISTAS "TEMPORALES" O hay que buscar la manera porque con el paso del tiempo esas listas temporales iran creciendo, porque siempre hay obras nuevas, lo que podriamos hacer es dejarlas como referencia, para en un futuro auditar las rayas de la obra ejemplo entrar ala lista de raya de la obra X y ver cuantas corridas tienen, que empleados tiene y cuanto se pago 

### Fase 2 - Correccion de base actual

- [x] Filtrar obras vivas en el select de obra de la corrida.
- [x] Revisar/ajustar `Empleado::obraActiva()` para que no sugiera obras canceladas, eliminadas o no vigentes.
- [x] Definir regla exacta de "obra viva":
  - [x] Excluir soft deleted.
  - [x] Excluir canceladas.
  - [x] Confirmar si se excluyen terminadas o solo canceladas.
- [x] Al generar recibos, guardar totales iniciales incluyendo:
  - [x] sueldo real/base
  - [x] horas extra
  - [x] metros lineales monto
  - [x] comisiones
  - [x] infonavit
  - [x] deducciones iniciales
- [x] Verificar que KPIs de la corrida cuadren inmediatamente despues de generar recibos.
- [x] Mantener snapshot de lista de raya al guardar cambios de obra en recibos.
- [x] Si el recibo queda sin obra, resolver de nuevo la lista principal del empleado.
- [x] Confirmar que `guardarRecibos()` y autosave cargan `empleado` para poder resolver lista principal.
- [x] Agregar pruebas/manual QA para corrida semanal y quincenal.

**Cierre Fase 2:** se valido sintaxis de `NominaCorridaController` y `ListaRayaResolver`. La corrida ya recalcula lista de raya por obra seleccionada y vuelve a lista principal cuando el recibo queda sin obra.

### Fase 3 - Guardado parcial/autosave

- [x] Crear endpoint para guardar una fila/recibo individual.
- [x] Validar que solo se pueda autosave si la corrida esta `abierta`.
- [x] Guardar campos editables por recibo:
  - [x] infonavit
  - [x] faltas
  - [x] descuentos
  - [x] horas extra
  - [x] metros lineales monto
  - [x] comisiones monto
  - [x] obra
  - [x] notas
- [x] Recalcular totales en backend por cada autosave.
- [x] Agregar UI de estado por fila:
  - [x] Pendiente de guardar
  - [x] Guardando
  - [x] Guardado
  - [x] Error
- [x] Mantener el boton "Guardar cambios" como respaldo general.
- [x] Agregar proteccion antes de salir si hay cambios pendientes.

### Fase 4 - Multiples extras por recibo

- [x] Crear migracion para permitir multiples extras por recibo.
- [x] Quitar `UNIQUE(recibo_id)` de `nomina_pagos_extra`.
- [x] Confirmar FK `recibo_id -> nomina_recibos.id`.
- [x] Agregar `recibo_id` a `$fillable` de `NominaPagoExtra` si falta.
- [x] Agregar relacion:
  - [x] `NominaRecibo::pagosExtra()`.
  - [x] `NominaPagoExtra::recibo()`.
- [x] Cargar extras con los recibos en `NominaCorridaController@show`.
- [x] Cambiar UI de un extra unico a lista de extras.
- [x] Agregar boton `+ Extra` por recibo.
- [x] Permitir eliminar extras.
- [x] Recalcular totales sumando todos los extras.
- [x] Ajustar guardado general y autosave para multiples extras.
- [x] Separar notas del recibo y notas propias de cada extra.

**Cierre Fase 4:** multiples extras por recibo quedan operativos. Se validaron alta, guardado, suma en totales, autosave, eliminacion y notas independientes por extra. La columna `Notas` del recibo quedo separada del boton `Extras`.

### Fase 5 - Comisiones trazables

- [x] Crear mecanismo de trazabilidad de comisiones por recibo.
- [x] Crear tabla `nomina_recibo_comisiones`.
- [x] Crear modelo `NominaReciboComision`.
- [x] Agregar relacion `NominaRecibo::comisionesTrazadas()`.
- [x] Guardar por cada comision/persona:
  - [x] `recibo_id`
  - [x] `corrida_id`
  - [x] `comision_id`
  - [x] `comision_personal_id`
  - [x] `obra_id`
  - [x] `empleado_id`
  - [x] `importe_comision`
  - [x] `tiempo_extra`
  - [x] fecha de comision
  - [x] rol snapshot
- [x] Evitar doble conteo de comisiones ya ligadas a otro recibo/corrida, salvo que se autorice recarga.
- [ ] Agregar indicador visual cuando las comisiones fueron cargadas y desde que registros.
- [ ] Definir si se podra "recalcular comisiones" en una corrida abierta.

**Avance Fase 5:** la generacion de recibos ya crea trazas por cada registro de `comision_personal` usado para calcular `comisiones_monto` y `horas_extra`. Tambien excluye comisiones ya ligadas a otra corrida para evitar doble pago.

### Fase 6 - Cierre, pago y auditoria

- [ ] Al cerrar corrida, bloquear edicion y autosave.
- [ ] Registrar `closed_by` y `closed_at`.
- [ ] Al marcar pagada, registrar `paid_by` y `paid_at`.
- [ ] Decidir si tambien se actualiza `nomina_recibos.status = pagado`.
- [ ] Agregar historial visible de cambios criticos.
- [ ] Revisar permisos de acceso a nomina y acciones de cierre/pago.

### Fase 7 - Pantalla de promedios de nomina

- [ ] Crear pantalla de promedios por empleado.
- [ ] Filtros iniciales:
  - [ ] empleado
  - [ ] tipo de pago
  - [ ] rango de meses
  - [ ] area
  - [ ] obra
- [ ] Promedio real:
  - [ ] sueldo neto real
  - [ ] menos deducciones/faltas
  - [ ] mas extras
  - [ ] mas comisiones
- [ ] Promedio teorico:
  - [ ] sueldo base/real
  - [ ] mas comisiones
  - [ ] mas extras
  - [ ] sin castigar deducciones o faltas
- [ ] Promedio empresa/costo:
  - [ ] sueldo base
  - [ ] complemento
  - [x] comisiones
  - [ ] extras
  - [ ] sin deducciones personales
- [ ] Definir si solo se toman corridas pagadas o tambien cerradas.
- [ ] Agregar exportacion a Excel si aplica.

## Orden recomendado de ejecucion

1. Fase 1: crear catalogo de listas de raya en configuracion, asignacion principal en empleado y override por obra viva.
2. Fase 2: corregir filtros de obras vivas y totales iniciales.
3. Fase 3: guardado parcial para proteger captura.
4. Fase 4: multiples extras.
5. Fase 5: trazabilidad de comisiones.
6. Fase 6: cierre/pago/auditoria.
7. Fase 7: promedios.

## QA minimo por fase

- Crear corrida semanal.
- Generar recibos.
- Confirmar que solo incluye empleados `Sueldo_tipo = 1` y `Estatus = 1`.
- Confirmar obra sugerida viva.
- Confirmar comisiones del rango.
- Editar campos y guardar.
- Confirmar que totales y KPIs cuadran.
- Cerrar corrida y validar bloqueo de edicion.
- Reabrir si aplica y validar que no se pierda informacion.

## Pendientes de definicion

- Confirmar si una obra terminada se considera "viva" para nomina o no.
- Confirmar si las comisiones deben tomarse solo cuando la comision este cerrada/aprobada.
- Confirmar si faltas se capturan como monto o dias.
- Confirmar si horas extra se capturan como monto o cantidad de horas con tarifa.
- Confirmar si el promedio final debe usar corridas pagadas, cerradas o ambas.
- Confirmar nombres definitivos de listas fijas: Oficina, Almacen Giralda, Almacen Huentitan, Pilas, Pozos.
- Confirmar si las listas de raya fijas deben vivir en catalogo propio o derivarse de areas/almacenes.
- Confirmar como interpretar los valores actuales de empleados.listaraya.




