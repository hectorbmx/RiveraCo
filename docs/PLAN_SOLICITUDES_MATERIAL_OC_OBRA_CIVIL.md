# Plan de accion: solicitudes de material de obra civil hacia ordenes de compra

Fecha: 2026-08-20  
Modulo: Obra civil / Ionic residente / Ordenes de compra Laravel  
Estado: Plan actualizado para conversion por items aprobados, listo para ejecutar en el siguiente ciclo

## Objetivo

Conectar las solicitudes de material capturadas desde Ionic con el flujo existente de ordenes de compra en Laravel, manteniendo trazabilidad por renglon aprobado de solicitud y permitiendo que compras genere una OC con solo los materiales que correspondan al proveedor elegido.

La unidad de conversion a OC ya no es la solicitud completa. La unidad correcta es el item aprobado de solicitud.

Ejemplo operativo:

1. El residente solicita 10 materiales.
2. Administracion revisa la solicitud y aprueba cantidades completas o modificadas.
3. Cada material aprobado queda como renglon pendiente de compra.
4. Compras crea una OC para un proveedor y selecciona solo los renglones que comprara con ese proveedor.
5. Los renglones no seleccionados siguen disponibles para otra OC.
6. Solo OC `AUTORIZADA` o `VERIFICADA` descuenta del disponible/usado.

## Reglas rectoras

- Seguir principios SOLID.
- No duplicar el modulo de ordenes de compra; extender el flujo actual.
- Mantener trazabilidad desde solicitud -> item aprobado -> detalle OC.
- Asegurar idempotencia en aprobaciones, precargas, seleccion de items y sincronizaciones.
- No borrar informacion historica ni datos de base de datos.
- No ejecutar migraciones sin autorizacion explicita.
- Mantener compatibilidad con OCs existentes y solicitudes previas.
- Agregar pantallas/modales de carga en procesos que guarden, autoricen, rechacen o carguen datos asincronos.
- Preferir servicios de dominio para reglas de negocio; controladores delgados.
- Validar cada paso con pruebas proporcionales: `php -l`, `route:list`, consultas read-only con tinker y pruebas manuales de UI.

## Estado actual implementado

Ya se implemento una primera version donde `/ordenes_compra/create` permite seleccionar una solicitud aprobada completa y precargar todos sus items autorizados.

Con el nuevo ajuste operativo, ese flujo debe cambiar:

- Quitar o reemplazar el select de `Solicitud de material aprobada`.
- Mostrar una lista/tabla de items aprobados pendientes de compra.
- Permitir seleccionar N items para cargar a la OC.
- No marcar toda la solicitud como `convertida_a_oc` por seleccionar solo algunos items.

Este cambio no invalida la trazabilidad ya creada. La columna `orden_compra_detalles.obra_civil_material_request_item_id` sigue siendo la base correcta.

## Hallazgos actuales

### Solicitudes de material

Tablas existentes:

- `obra_civil_material_requests`
- `obra_civil_material_request_items`

Campos importantes en `obra_civil_material_requests`:

- `obra_id`
- `user_id`
- `empleado_id`
- `folio`
- `status`
- `notes`
- `submitted_at`
- `reviewed_by`
- `reviewed_at`
- `orden_compra_id`
- `metadata`

Campos importantes en `obra_civil_material_request_items`:

- `quantity`: cantidad solicitada original.
- `approved_quantity`: cantidad autorizada final.
- `approval_notes`: notas de aprobacion/admin.
- `approved_by`
- `approved_at`
- `obra_civil_insumo_id`
- `insumo_snapshot`

### Ordenes de compra

Tablas existentes:

- `ordenes_compra`
- `orden_compra_detalles`

`orden_compra_detalles` ya tiene:

- `obra_civil_insumo_id`
- `obra_civil_insumo_snapshot`
- `obra_civil_material_request_item_id`

Esto permite que cada detalle de OC se relacione con un item aprobado especifico.

### Balance de insumos

Regla corregida:

- `Usado` y `Disponible` solo deben considerar OC con estado `AUTORIZADA` o `VERIFICADA`.
- OC `BORRADOR`, `PROGRAMADA` o `CANCELADA` no descuenta disponible.

### Vista de ordenes por insumo

En:

```text
/obra_civil/{obra}/insumos/{insumo}/ordenes
```

La tabla debe mostrar:

- OC.
- Proveedor.
- Solicitud origen, cuando exista.
- Estado.
- Fecha.
- Cantidad.
- Precio.
- Importe.

Habra casos sin solicitud origen porque antes no existia esa relacion.

## Decisiones de negocio acordadas

### Aprobacion cerrada

Una aprobacion parcial no deja como pendiente la parte no autorizada.

Ejemplo:

| Solicitado | Autorizado | No autorizado | Sigue a compra |
|---:|---:|---:|---:|
| 50 | 30 | 20 | 30 |

Los 20 no autorizados quedan cerrados. Si se necesitan despues, el residente debe solicitar de nuevo.

### Conversion a OC por item aprobado

Una solicitud puede traer materiales de varios proveedores. Por eso no debe convertirse completa a una sola OC.

La OC se arma seleccionando items aprobados pendientes.

Ejemplo:

Solicitud `SCM-000010` con 10 items aprobados:

| Item | Proveedor esperado | Autorizado | En OC actual |
|---|---|---:|---:|
| Cemento | Proveedor A | 30 | Si |
| Varilla | Proveedor B | 12 | No |
| Agua | Proveedor C | 56 | No |

Resultado:

- La OC actual solo carga Cemento.
- Varilla y Agua siguen disponibles para otra OC.
- La solicitud padre no debe cerrarse totalmente hasta que todos sus items aprobados queden comprados/cerrados segun regla final.

### Borradores no descuentan, pero deben advertirse

Una OC borrador no debe descontar disponible final.

Pero si un item aprobado ya esta en una OC borrador, conviene mostrarlo como advertencia:

```text
En OC borrador: 10
Pendiente autorizado: 20
```

Decision recomendada:

- Para disponibilidad final: contar solo `AUTORIZADA` y `VERIFICADA`.
- Para UI de seleccion: mostrar cantidades en borrador como advertencia, pero no bloquear automaticamente en el primer corte.

## Modelo objetivo

### Cantidades por item aprobado

No agregar todavia un campo editable `surtido`, porque puede confundirse con entrega fisica en obra.

Usar cantidades calculadas:

```text
requested_quantity = item.quantity
approved_quantity = item.approved_quantity
ordered_quantity = SUM(detalles OC AUTORIZADA/VERIFICADA ligados al item)
draft_quantity = SUM(detalles OC BORRADOR/PROGRAMADA ligados al item)
pending_order_quantity = approved_quantity - ordered_quantity
```

Notas:

- `pending_order_quantity` no incluye lo no autorizado.
- `draft_quantity` es solo advertencia operativa.
- Si se necesita cierre manual futuro, agregar estado/campo especifico como `closed_quantity` o `closed_reason`, no mezclarlo con surtido fisico.

### Relacion item aprobado -> detalle OC

La relacion principal es:

```php
orden_compra_detalles.obra_civil_material_request_item_id
```

Debe ser nullable para:

- OCs antiguas.
- Compras manuales sin solicitud.
- Insumos de obra civil agregados fuera del flujo de solicitud.

### Tabla puente solicitud - OC

La tabla `obra_civil_material_request_order_links` sigue siendo util, pero ya no debe interpretarse como conversion completa de solicitud.

Debe funcionar como trazabilidad de encabezados:

- una solicitud puede estar relacionada con varias OCs;
- una OC puede traer items de una o varias solicitudes, si el negocio lo permite;
- el detalle real vive en `orden_compra_detalles.obra_civil_material_request_item_id`.

## Estados recomendados

### Solicitud

- `enviada`: pendiente de revision.
- `en_revision`: en revision administrativa.
- `aprobada`: aprobacion completa.
- `aprobada_parcial`: aprobacion menor a lo solicitado en uno o mas items.
- `rechazada`: nada fue autorizado.
- `convertida_a_oc`: todos los items aprobados ya fueron cubiertos por OC autorizada/verificada o cerrados.
- `cancelada`: anulada manualmente.

Decision pendiente:

- Si conviene agregar `parcialmente_convertida_a_oc`.

Recomendacion actual:

- Evitar depender demasiado del estado de cabecera.
- Calcular avance de compra por item aprobado.
- Usar `convertida_a_oc` solo cuando todos los items aprobados tengan pendiente 0.

### Link solicitud - OC

- `borrador`
- `autorizada`
- `cancelada`

## Servicios recomendados

### `ObraCivilMaterialRequestApprovalService`

Responsabilidad: revisar, aprobar o rechazar solicitudes.

Metodos:

```php
approveFull(ObraCivilMaterialRequest $request, User $user): ObraCivilMaterialRequest
approveWithQuantities(ObraCivilMaterialRequest $request, array $items, User $user): ObraCivilMaterialRequest
reject(ObraCivilMaterialRequest $request, User $user, ?string $reason = null): ObraCivilMaterialRequest
```

Debe validar:

- solicitud en `enviada` o `en_revision`;
- cantidades autorizadas >= 0;
- cantidades autorizadas <= solicitadas;
- al menos un item > 0 para aprobar;
- auditoria de usuario/fecha/notas.

### `ObraCivilMaterialRequestItemBalanceService`

Nuevo servicio recomendado.

Responsabilidad: calcular comprado, borrador y pendiente por item aprobado.

Metodos sugeridos:

```php
summaryForItem(ObraCivilMaterialRequestItem $item): array
summariesForItems(iterable $itemIds): Collection
pendingApprovedItemsForObra(Obra $obra, array $filters = []): Collection
```

Debe calcular:

```text
ordered_quantity = SUM(detalles OC AUTORIZADA/VERIFICADA)
draft_quantity = SUM(detalles OC BORRADOR/PROGRAMADA)
pending_order_quantity = approved_quantity - ordered_quantity
```

Idempotencia:

- No almacenar acumulados si pueden calcularse desde detalles.
- Si se recalcula varias veces, debe devolver el mismo resultado.

### `ObraCivilMaterialRequestOrderService`

Responsabilidad actualizada: cargar items aprobados seleccionados a una OC.

Metodos nuevos sugeridos:

```php
approvedPendingItemsForObra(Obra $obra, array $filters = []): Collection
createDraftDetailsFromApprovedItems(OrdenCompra $orden, array $requestItemPayloads, User $user): Collection
syncRequestStatusesAfterOrderChange(OrdenCompra $orden): void
```

Debe validar:

- misma obra;
- obra civil;
- item pertenece a una solicitud `aprobada` o `aprobada_parcial`;
- `approved_quantity > 0`;
- cantidad a cargar > 0;
- cantidad a cargar <= pendiente autorizado, salvo permiso futuro;
- insumo activo y perteneciente a la obra.

Idempotencia:

- En una misma OC no duplicar el mismo item de solicitud.
- Usar llave logica `orden_compra_id + obra_civil_material_request_item_id`.
- Si se reintenta el submit, actualizar/omitir segun regla explicita, no duplicar.
- La sincronizacion debe recalcular desde detalles reales, no sumar acumulados.

### `ObraCivilInsumoBalanceService`

Responsabilidad: calcular usado/disponible de insumos.

Regla vigente:

- Usado = OC `AUTORIZADA` + `VERIFICADA`.
- No contar `BORRADOR`.
- No contar `CANCELADA`.

## Cambios en Laravel

### Checkpoint 1: Consolidar regla actual y revertir conversion por solicitud completa

Objetivo:

- Ajustar el flujo implementado hoy para dejar de seleccionar solicitud completa.

Acciones:

- Revisar `ObraCivilMaterialRequestOrderService` actual.
- Cambiar metodos basados en solicitud completa por metodos basados en item aprobado.
- Evitar marcar solicitud como `convertida_a_oc` al crear una OC con algunos items.
- Mantener `orden_compra_detalles.obra_civil_material_request_item_id`.
- Mantener tabla puente como trazabilidad opcional.

Validar:

- `php -l` servicio/controladores.
- Consulta read-only de items aprobados pendientes en obra 6.

### Checkpoint 2: Servicio de balance por item aprobado

Crear o ajustar:

- `ObraCivilMaterialRequestItemBalanceService`.

Debe devolver por item:

- cantidad solicitada;
- cantidad autorizada;
- cantidad en OC autorizada/verificada;
- cantidad en OC borrador/programada;
- pendiente por comprar;
- folio solicitud;
- solicitante;
- notas residente/admin;
- insumo/codigo/concepto/unidad/precio sugerido.

Validar:

- item sin OC: pendiente = autorizado.
- item con OC borrador: pendiente final no baja, draft aparece como advertencia.
- item con OC autorizada: pendiente baja.
- item con varias OCs autorizadas: suma correctamente.

### Checkpoint 3: Endpoint JSON de items aprobados pendientes por obra

Crear endpoint para `/ordenes_compra/create`:

```text
GET /ordenes-compra/items-solicitudes-material/obra/{obra}
```

Debe devolver items, no solicitudes:

- `request_item_id`
- `request_id`
- `request_folio`
- `insumo_id`
- `codigo`
- `concepto`
- `unidad`
- `requested_quantity`
- `approved_quantity`
- `ordered_quantity`
- `draft_quantity`
- `pending_order_quantity`
- `suggested_price`
- `resident_notes`
- `approval_notes`

Filtros futuros:

- buscar por folio/codigo/concepto;
- proveedor sugerido si existe relacion futura;
- mostrar/ocultar items con borrador.

Validar:

- No devolver rechazadas/canceladas/enviadas.
- No devolver items con `approved_quantity <= 0`.
- No devolver items con `pending_order_quantity <= 0`.

### Checkpoint 4: UI en `/ordenes_compra/create`

Reemplazar select de solicitud por tabla/lista de items aprobados pendientes.

UI sugerida:

| Sel | Solicitud | Codigo | Concepto | Unidad | Autorizado | OC autorizada | OC borrador | Pendiente | Cantidad a cargar |
|---|---|---|---|---|---:|---:|---:|---:|---:|

Comportamiento:

- Al elegir obra civil, cargar items aprobados pendientes.
- Permitir seleccionar N items.
- Cantidad a cargar inicia en `pending_order_quantity`.
- Validar en frontend que cantidad <= pendiente.
- Mostrar advertencia si hay `draft_quantity > 0`.
- Loading al cargar items.
- Loading al crear OC.

### Checkpoint 5: Store de OC con items seleccionados

En `StoreOrdenCompraRequest` aceptar:

```php
material_request_items => array nullable
material_request_items.*.id => integer exists:obra_civil_material_request_items,id
material_request_items.*.quantity => numeric min:0.0001
```

En `OrdenCompraController::store`:

- crear encabezado como hoy;
- si hay items seleccionados, llamar servicio;
- crear detalles de OC por item seleccionado;
- ligar cada detalle con `obra_civil_material_request_item_id`;
- crear/actualizar links de solicitud-OC por cada solicitud involucrada;
- no marcar solicitud completa como convertida salvo que todos sus items queden cubiertos segun calculo.

Idempotencia:

- bloquear doble submit en UI;
- en backend no duplicar `orden_compra_id + request_item_id`;
- si se reintenta, devolver estado estable o actualizar cantidad si se definio asi.

### Checkpoint 6: Edit de OC con trazabilidad

En `/ordenes_compra/{id}/edit`:

- mostrar en cada detalle ligado:
  - folio solicitud;
  - cantidad solicitada;
  - cantidad autorizada;
  - cantidad pendiente antes/despues;
  - notas residente/admin.

Reglas:

- Permitir editar precios/costos.
- No permitir aumentar cantidad por encima del pendiente autorizado sin permiso futuro.
- Si se elimina un detalle en borrador, el item vuelve a aparecer como pendiente porque el pendiente se calcula desde OC autorizadas/verificadas y/o borrador segun vista.

### Checkpoint 7: Autorizar OC y sincronizar estados

Al autorizar OC:

- actualizar links solicitud-OC a `autorizada`.
- recalcular por solicitud involucrada.
- si todos los items aprobados tienen `pending_order_quantity <= 0`, marcar `convertida_a_oc`.
- si aun hay items pendientes, mantener `aprobada` / `aprobada_parcial` o usar estado futuro `parcialmente_convertida_a_oc`.

Regla importante:

- La fuente de verdad es la suma de detalles OC autorizados/verificados, no un acumulado manual.

### Checkpoint 8: Vista de insumos

Ajustar columnas para reflejar el flujo real:

- `Por aprobar`: solicitudes enviadas/en revision.
- `Autorizado por comprar`: items aprobados con pendiente por comprar.
- `En OC borrador`: items aprobados cargados en borradores.
- `Usado`: OC autorizadas/verificadas.
- `Disponible`: presupuesto - usado.

Si el espacio no alcanza, mostrar `En OC borrador` dentro del detalle por insumo.

### Checkpoint 9: Pantallas de carga / UX

Agregar/revisar loading overlay en:

- aprobar solicitud;
- rechazar solicitud;
- cargar items aprobados al elegir obra;
- crear OC;
- agregar detalle;
- eliminar detalle;
- autorizar OC.

Requisitos:

- bloquear boton durante submit;
- mensaje claro;
- evitar doble click;
- preservar errores de validacion.

## Cambios UI propuestos

### `/obra_civil/{obra}/solicitudes-material/{solicitud}`

Mantener como pantalla de aprobacion administrativa.

Tabla:

| Codigo | Concepto | Solicitado | Autorizado | No autorizado | Notas aprobacion |
|---|---|---:|---:|---:|---|

Acciones:

- Aprobar completo.
- Aprobar cantidades capturadas.
- Rechazar.

### `/ordenes_compra/create`

Nuevo enfoque:

- Elegir proveedor.
- Elegir obra civil.
- Cargar tabla de items aprobados pendientes.
- Seleccionar N items.
- Capturar cantidad a cargar por item.
- Crear OC con esos detalles precargados.

Ya no se debe seleccionar la solicitud completa como unidad principal.

### `/ordenes_compra/{id}/edit`

Mostrar trazabilidad por renglon:

- Solicitud origen.
- Cantidad solicitada.
- Cantidad autorizada.
- Cantidad cargada en esta OC.
- Notas del residente/admin.

### `/obra_civil/{obra}/insumos/{insumo}/ordenes`

Mostrar todas las OCs del insumo, aunque sean borrador, pero aclarar:

- Solo autorizadas/verificadas afectan saldo.
- Mostrar solicitud origen si existe.
- Mostrar `Sin solicitud` para OCs antiguas/manuales.

## Riesgos y decisiones pendientes

### Riesgo 1: Borradores duplicados

Si dos usuarios crean borradores con el mismo item, puede parecer disponible dos veces.

Decision recomendada primer corte:

- No descontar borradores del disponible final.
- Mostrar `draft_quantity` como advertencia.
- En backend, impedir duplicar el mismo item dentro de la misma OC.

### Riesgo 2: Multiples proveedores por solicitud

Ya queda resuelto al seleccionar items individuales.

### Riesgo 3: `orden_compra_id` en cabecera de solicitud

Ese campo ya no representa bien el flujo cuando una solicitud se reparte en varias OCs.

Decision recomendada:

- Mantener por compatibilidad.
- No usarlo como fuente principal.
- La fuente principal debe ser:
  - `orden_compra_detalles.obra_civil_material_request_item_id`
  - tabla puente `obra_civil_material_request_order_links` para encabezados.

### Riesgo 4: Reducir cantidad en OC

Si compras selecciona menos de lo pendiente autorizado:

- Lo no seleccionado sigue pendiente por comprar.
- Si se quiere cerrar manualmente una parte, debe ser un flujo separado, no borrar el dato.

### Riesgo 5: Solicitud con insumo inactivo

Si el insumo ya no esta activo o no pertenece a la obra:

- Bloquear carga a OC.
- Mostrar mensaje claro.

## Checklist de pruebas manuales

### Caso A: Solicitud con varios proveedores

1. Crear solicitud desde Ionic con 3 items.
2. Aprobar todos los items.
3. Crear OC para proveedor A.
4. Seleccionar solo 1 item.
5. Confirmar OC con 1 detalle.
6. Confirmar los otros 2 items siguen pendientes para otra OC.

### Caso B: Aprobacion parcial cerrada

1. Solicitar 50.
2. Autorizar 30.
3. Crear OC por 10.
4. Confirmar pendiente por comprar = 20.
5. Confirmar no autorizado = 20, pero no es pendiente operativo.

### Caso C: OC borrador no descuenta disponible

1. Crear OC con item aprobado.
2. Dejarla en borrador.
3. Confirmar `Usado` no cambia.
4. Confirmar item muestra `En OC borrador` como advertencia.

### Caso D: Autorizar OC

1. Autorizar la OC.
2. Confirmar `Usado` aumenta.
3. Confirmar `Pendiente por comprar` baja.
4. Confirmar solicitud solo se marca `convertida_a_oc` si todos sus items aprobados quedaron cubiertos.

### Caso E: OCs antiguas/manuales

1. Ver insumo con OCs antiguas.
2. Confirmar que la tabla muestra `Sin solicitud`.
3. Confirmar que si estan autorizadas/verificadas afectan usado.
4. Confirmar que si estan borrador no afectan usado.

## Orden recomendado de implementacion para manana

1. Ajustar `ObraCivilMaterialRequestOrderService` para trabajar por items, no por solicitud completa.
2. Crear `ObraCivilMaterialRequestItemBalanceService`.
3. Crear endpoint JSON de items aprobados pendientes por obra.
4. Reemplazar select de solicitud en `/ordenes_compra/create` por tabla de items seleccionables.
5. Cambiar `StoreOrdenCompraRequest` para recibir items seleccionados.
6. Cambiar `OrdenCompraController::store` para precargar solo esos items.
7. Ajustar sincronizacion de estados al autorizar OC.
8. Ajustar vistas de insumos y detalle OC para reflejar pendiente por item.
9. Validar con los casos manuales anteriores.

## Criterios de listo

- Una solicitud puede generar varias OCs.
- Una OC puede contener items de una o varias solicitudes aprobadas, si se permite.
- La OC no obliga a comprar toda la solicitud.
- Los items no seleccionados siguen pendientes de compra.
- Lo no autorizado en aprobacion no queda pendiente operativo.
- Los detalles de OC guardan origen por item de solicitud.
- `Disponible` no baja por OC borrador.
- `Disponible` baja por OC autorizada/verificada.
- La autorizacion recalcula pendientes desde datos reales.
- La operacion es idempotente.
- Los formularios muestran loading y evitan doble submit.
- No se rompe el flujo manual actual de OCs sin solicitud.

## Checkpoint ejecutado - 2026-08-21

### Checkpoint 1: OC por partidas aprobadas

Estado: ejecutado.

Cambios aplicados:

- Se agrego `ObraCivilMaterialRequestItemBalanceService` para calcular por partida:
  - cantidad en OC autorizada/verificada,
  - cantidad en OC borrador/programada,
  - pendiente disponible para cargar.
- Se refactorizo `ObraCivilMaterialRequestOrderService` para trabajar con `obra_civil_material_request_items` aprobados, no con solicitud completa.
- El endpoint actual de solicitudes aprobadas por obra conserva la ruta, pero ahora devuelve partidas/items aprobados pendientes.
- La creacion de OC acepta `obra_civil_material_request_items[n][id]` y `obra_civil_material_request_items[n][quantity]`.
- La pantalla `ordenes_compra/create` ahora muestra una tabla seleccionable de materiales aprobados pendientes, para que administracion cargue solo los productos que correspondan al proveedor de esa OC.
- Se conserva compatibilidad temporal con `obra_civil_material_request_id`, pero queda marcado como flujo anterior.

Validaciones realizadas:

- `php -l` en los servicios, request y controlador modificados.
- Prueba read-only del servicio con obra 6: devolvio partidas aprobadas pendientes de `SCM-000003` correctamente, incluyendo cantidad autorizada parcial.

Siguiente checkpoint recomendado:

- Sincronizar estados al autorizar/cancelar OC:
  - actualizar links de solicitud-OC,
  - mantener solicitud como aprobada mientras tenga items pendientes,
  - marcar convertida a OC solo cuando todos los items autorizados esten cubiertos por OC autorizada/verificada,
  - asegurar que las vistas de solicitudes e insumos reflejen el nuevo calculo por item.

## Plan de implementacion - piezas comerciales hacia OC con impacto en unidad base

Fecha: 2026-08-30  
Estado: plan de ajuste para conservar piezas en compras y descontar insumos en su unidad original

### Objetivo especifico

Cuando el residente solicite materiales por piezas comerciales desde Ionic, Laravel debe:

1. Conservar la orden operativa en piezas para que compras arme la OC con lo que realmente se va a pedir al proveedor.
2. Convertir esas piezas a la unidad base del insumo de explosion, por ejemplo KG o TON.
3. Guardar en `orden_compra_detalles.cantidad` la cantidad ya convertida a la unidad base, porque esa es la cantidad que impacta presupuesto, usado y disponible.
4. Mantener en snapshot la trazabilidad de las piezas originales: SKU comercial, descripcion, unidad de compra, piezas, factor/peso y kg convertidos.

La regla importante es:

```text
Cantidad de compra visible = piezas comerciales
Cantidad de impacto = unidad original del insumo
Fuente de saldo = orden_compra_detalles.cantidad
```

### Estado actual detectado

Ya existe una base util:

- El API residente acepta `commercial_material_id`, `commercial_quantity` y `commercial_items`.
- `ResidenteObraCivilMaterialRequestService` convierte piezas comerciales a kg con `commercialQuantityToKg()`.
- `ResidenteObraCivilMaterialRequestService` convierte kg a la unidad del insumo con `kgToBudgetUnit()`.
- El resultado convertido se guarda en `obra_civil_material_request_items.quantity`.
- La trazabilidad comercial se guarda dentro de `obra_civil_material_request_items.insumo_snapshot.commercial_request`.
- `ObraCivilInsumoBalanceService` descuenta presupuesto usando `orden_compra_detalles.cantidad` de OCs `AUTORIZADA` o `VERIFICADA`.

Brechas actuales:

- `ObraCivilMaterialRequestOrderService` crea el detalle de OC con la cantidad convertida, pero reconstruye `obra_civil_insumo_snapshot` desde el insumo y pierde `commercial_request`.
- `ObraCivilFieldReviewService::convertMaterialRequestToOrdenCompra()` tambien reconstruye el snapshot y no conserva `commercial_request`.
- La conversion directa desde revision de campo no llena `obra_civil_material_request_item_id` en el detalle de OC, por lo que el balance por item aprobado no queda completamente trazable.
- Las vistas administrativas muestran la cantidad convertida, pero no muestran la orden de piezas que origino esa cantidad.
- La aprobacion parcial opera sobre `approved_quantity` en unidad base. Si se requiere aprobar menos piezas, falta una regla explicita para recalcular piezas contra unidad base.

### Modelo de datos recomendado

Primer corte recomendado: no crear columnas nuevas todavia.

Usar los campos existentes asi:

```text
obra_civil_material_request_items.quantity
  = cantidad solicitada ya convertida a unidad base del insumo

obra_civil_material_request_items.approved_quantity
  = cantidad aprobada ya convertida a unidad base del insumo

obra_civil_material_request_items.insumo_snapshot.commercial_request
  = detalle operativo en piezas

orden_compra_detalles.cantidad
  = cantidad de impacto en unidad base del insumo

orden_compra_detalles.unidad
  = unidad base del insumo

orden_compra_detalles.obra_civil_insumo_snapshot.commercial_request
  = copia historica de la orden comercial en piezas

orden_compra_detalles.obra_civil_material_request_item_id
  = trazabilidad del renglon aprobado
```

Ejemplo de snapshot esperado:

```json
{
  "codigo": "AC-001",
  "concepto": "Acero de refuerzo",
  "unidad": "TON",
  "commercial_request": {
    "items": [
      {
        "commercial_material_id": 15,
        "sku": "VAR-3-8-12M",
        "descripcion": "Varilla 3/8 x 12 m",
        "unidad_compra": "PZA",
        "commercial_quantity": 25,
        "peso_por_pieza": 6.68,
        "factor_conversion": 6.68,
        "kg": 167.0
      }
    ],
    "total_commercial_quantity": 25,
    "total_kg": 167.0,
    "converted_quantity": 0.167,
    "converted_unit": "TON"
  }
}
```

Segundo corte opcional, solo si las consultas/reportes lo necesitan:

- Agregar columnas JSON o tabla hija para lineas comerciales de solicitud/OC.
- Posibles campos: `obra_civil_material_request_item_commercial_lines` y `orden_compra_detalle_commercial_lines`.
- No es indispensable para el primer ajuste porque `insumo_snapshot` ya tiene casts JSON y conserva historico.

### Flujo objetivo

```text
Ionic
 -> residente selecciona insumo de explosion
 -> residente selecciona uno o varios hijos comerciales
 -> residente captura piezas

Laravel API
 -> valida que el hijo comercial pertenezca al grupo resuelto del insumo
 -> convierte piezas a kg
 -> convierte kg a unidad base del insumo
 -> guarda item de solicitud con quantity en unidad base
 -> guarda commercial_request en snapshot

Admin aprobacion
 -> muestra piezas solicitadas y cantidad equivalente en unidad base
 -> aprueba en unidad base por ahora
 -> conserva commercial_request

Compras / OC
 -> lista items aprobados pendientes
 -> muestra piezas originales junto a la cantidad base
 -> crea detalle de OC con cantidad base
 -> copia commercial_request al snapshot del detalle

Autorizacion OC
 -> usado/disponible se calcula desde orden_compra_detalles.cantidad
 -> la trazabilidad por piezas permanece para compras, PDF y auditoria
```

### Checkpoint 1: propagar `commercial_request` hacia OC

Archivos a tocar:

- `app/Services/ObraCivil/ObraCivilMaterialRequestOrderService.php`
- `app/Services/ObraCivil/ObraCivilFieldReviewService.php`

Acciones:

- En `ObraCivilMaterialRequestOrderService::insumoSnapshot()`, recibir tambien el item de solicitud o su snapshot.
- Copiar `commercial_request` desde `$item->insumo_snapshot['commercial_request']` hacia `orden_compra_detalles.obra_civil_insumo_snapshot`.
- En `attachApprovedItemsToOrder()`, cuando actualice un detalle existente, conservar o refrescar el snapshot comercial.
- En `ObraCivilFieldReviewService::convertMaterialRequestToOrdenCompra()`, usar el snapshot del item como base y no reconstruirlo perdiendo datos.
- En esa conversion directa, llenar `obra_civil_material_request_item_id`.

Criterio de listo:

- Una solicitud hecha con piezas conserva `commercial_request` en el detalle de OC.
- Una OC generada desde create y una OC generada desde revision de campo quedan con la misma trazabilidad.

### Checkpoint 2: exponer piezas en el selector de OC

Archivos a tocar:

- `app/Services/ObraCivil/ObraCivilMaterialRequestOrderService.php`
- `resources/views/ordencompra/create.blade.php`

Acciones:

- En `itemOptionPayload()`, agregar el bloque `commercial_request` desde el snapshot del item.
- En la tabla de materiales aprobados pendientes, mostrar piezas/comercial solicitado, total kg, equivalente en unidad base y pendiente en unidad base.
- Mantener el input `quantity` como cantidad en unidad base mientras no exista captura parcial por piezas.

Criterio de listo:

- Compras entiende que esta cargando, por ejemplo, `25 PZA Varilla 3/8`, y ve que impacta `0.167 TON`.

### Checkpoint 3: mostrar piezas en aprobacion y revision

Archivos a tocar:

- `resources/views/obra_civil/material_requests/show.blade.php`
- `resources/views/obra_civil/review/index.blade.php`
- Opcional: `app/Http/Controllers/Api/V1/ResidenteObraCivilMaterialController.php`

Acciones:

- Mostrar debajo del concepto el detalle de `commercial_request.items`.
- Mostrar resumen: `Solicitado: 25 PZA / 167.0000 KG / 0.1670 TON`.
- Si no existe `commercial_request`, dejar el comportamiento actual.

Criterio de listo:

- Administracion puede revisar lo que el residente pidio en piezas sin perder la cantidad de impacto.

### Checkpoint 4: resolver aprobacion parcial con piezas

Decision requerida:

- Opcion A, primer corte: aprobacion parcial sigue siendo por unidad base convertida.
- Opcion B, mas amigable: aprobacion parcial permite editar piezas y Laravel recalcula unidad base.

Recomendacion:

- Implementar primero Opcion A para no romper el flujo ya existente.
- Agregar texto visual claro: `La cantidad autorizada impacta en TON/KG; las piezas solicitadas quedan como referencia operativa`.
- En un segundo corte, agregar inputs por linea comercial si el negocio necesita autorizar piezas exactas.

Criterio de listo primer corte:

- Nadie pierde el dato original de piezas.
- El saldo se sigue calculando con la unidad base.
- La autorizacion parcial no inventa una nueva lista proporcional de piezas.

### Checkpoint 5: impresion y detalle de OC

Archivos a tocar:

- `resources/views/ordencompra/edit.blade.php`
- `app/Http/Controllers/OrdenCompraController.php` en metodo `print()`

Acciones:

- En la tabla de detalles, mostrar la cantidad base como hoy.
- Agregar debajo de la descripcion una linea de compra comercial cuando exista: `Compra solicitada: 25 PZA Varilla 3/8 x 12 m = 167.0000 KG = 0.1670 TON`.
- En PDF, incluir la misma referencia en descripcion/notas, sin cambiar columnas principales.

Criterio de listo:

- La OC se puede entregar a compras/proveedor entendiendo piezas, pero contabilidad/presupuesto sigue viendo unidad base.

### Checkpoint 6: pruebas y validacion

Validaciones tecnicas:

- `php -l` en servicios/controladores tocados.
- `php artisan route:list --name=ordenes_compra`.
- `php tools/check_mojibake.php <archivos tocados>`.

Casos manuales:

1. Solicitar desde Ionic 25 piezas de un hijo comercial ligado a un insumo en TON.
2. Confirmar que Laravel guarda `quantity` en TON y `commercial_request.total_commercial_quantity` en piezas.
3. Aprobar completo.
4. Crear OC desde `/ordenes_compra/create` seleccionando ese item.
5. Confirmar que el detalle de OC tiene `cantidad` en TON, `unidad` en TON, `obra_civil_material_request_item_id` y `obra_civil_insumo_snapshot.commercial_request`.
6. Autorizar OC.
7. Confirmar que `ObraCivilInsumoBalanceService` incrementa `used_quantity` con TON, no con piezas.
8. Confirmar que la vista/PDF muestran las piezas como trazabilidad operativa.

### Riesgos a cuidar

- No guardar piezas en `orden_compra_detalles.cantidad`, porque romperia el saldo del insumo.
- No recalcular pesos desde datos actuales del catalogo al generar OC historica; se debe conservar el snapshot que genero la solicitud.
- No perder `commercial_request` al actualizar un detalle existente.
- No duplicar el mismo item de solicitud dentro de una misma OC.
- No marcar una solicitud como `convertida_a_oc` solo por crear una OC borrador.

### Orden recomendado de ejecucion

1. Propagar `commercial_request` al detalle de OC en ambos caminos de generacion.
2. Asegurar `obra_civil_material_request_item_id` en la conversion directa desde revision.
3. Exponer `commercial_request` en el payload de items aprobados pendientes.
4. Mostrar piezas en create/edit de OC.
5. Mostrar piezas en aprobacion/revision.
6. Ajustar PDF.
7. Validar saldos con una solicitud real de Ionic.
