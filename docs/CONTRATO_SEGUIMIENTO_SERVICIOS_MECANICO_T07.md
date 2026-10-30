# T07. Consulta y seguimiento de servicios programados

Fecha: 2026-10-23. Backend implementado; pantallas móviles pendientes en T13.

## Acceso y alcance

Ambas rutas requieren Sanctum, usuario app activo y `app.mecanico.access`, sin obra asignada. No requieren acceso gerencial ni permisos web de mantenimiento.

- Se consultan todos los mantenimientos `tipo = programado`, propios, ajenos y sin asignar, creados desde web o API. No hay tabla móvil paralela.
- Emergencias y registros inexistentes devuelven 404 en el detalle. El buscador y los filtros nunca amplían ese alcance.
- D01 permite ver servicios ajenos, pero PATCH T06 los rechaza con 403. La app no toma ni reasigna servicios.
- Un usuario sin empleado válido puede consultar; no tiene servicios propios ni permiso de edición. `solo_mios=1` devuelve una lista vacía en ese caso.

## Listado

`GET /api/v1/mecanico/servicios`

| Parámetro opcional | Regla / comportamiento |
|---|---|
| `q` | Texto de hasta 120 caracteres. Busca descripción, categoría, folio de orden, nombre/código de máquina y marca/modelo/placas de vehículo. |
| `estatus` | `borrador`, `orden_generada`, `pendiente`, `en_proceso`, `completado`, `cancelado`. |
| `equipo_tipo` | `maquinas` o `vehiculos`; filtra todos los servicios de ese tipo sin exigir ID. Vacío conserva ambos. Se rechaza un ID del tipo opuesto. |
| `maquina_id` / `vehiculo_id` | Entero positivo, mutuamente excluyentes. ID sin coincidencias devuelve lista vacía. |
| `obra_id` | Entero positivo; no implica asignación del usuario a la obra. |
| `solo_mios` | Booleano Laravel: `0` o `1` en query string. Por defecto consulta todos. |
| `fecha_desde` / `fecha_hasta` | `YYYY-MM-DD`, sobre fecha programada, extremos incluidos. Cualquiera puede enviarse sola; juntas deben estar ordenadas. Los servicios sin fecha no coinciden con filtros de fechas. |
| `page` | Entero desde 1. |
| `per_page` | Entero entre 1 y 50; por defecto 25. |

Orden fijo por ID descendente, con paginación de total. Respuesta `200`:

```json
{
  "ok": true,
  "data": [],
  "meta": {"current_page": 1, "per_page": 25, "total": 0, "last_page": 1}
}
```

Cada elemento contiene cabecera de servicio, costos de consulta, fechas programada/inicio/fin, estado, abastecimiento, folio, `created_at` y `updated_at`; IDs/km enteros, costos/horómetro numéricos y valores opcionales nulos. Se agregan:

- `maquina`: ID, nombre y código; o null.
- `vehiculo`: ID, marca, modelo y placas; o null.
- `obra`: ID y nombre; o null (también si fue eliminada lógicamente).
- `mecanico`: ID y nombre completo; o null. Se excluyen datos personales, nómina y contactos.
- `servicio_preventivo`: ID, nombre, ámbito, `intervalo_valor` numérico y `unidad` (`horas` o `km`); o null si la referencia ya no existe. El listado móvil usa nombre e intervalo del catálogo en su segunda línea, conservando la descripción manual para el detalle.
- `es_propio` y `acciones_permitidas`.

El listado precarga las relaciones y usa subconsultas de existencia para materiales y solicitudes; no calcula inventario ni trae evidencias por cada fila.

## Detalle

`GET /api/v1/mecanico/servicios/{servicio}`

Respuesta `200`: `{ "ok": true, "data": {...} }`. Incluye los campos del listado y:

| Campo | Contenido |
|---|---|
| `orden` | Folio, fecha de generación y `tiene_snapshot` (existen filas congeladas). |
| `materiales` | Snapshot ordenado: ID, producto/tipo de material preventivo, nombre y SKU congelados, unidad, cantidad requerida, inclusión y motivo de exclusión. Incluye las filas excluidas. |
| `avance_materiales` | Resultado de `MantenimientoAbastecimientoService::calcular`, compartido con la web: requerido, surtido, disponible neto de Giralda, surtible, faltante, estado y folios de salida aplicados. Solo materiales incluidos. |
| `solicitudes` | ID, folio, tipo, estado, fecha y materiales con cantidades requerida/solicitada/atendida. Cada solicitud incluye `documentos_inventario` y `ordenes_compra` vinculados directamente por su FK. |
| `conceptos` | Concepto, tipo, cantidad, costo unitario y total; solo lectura. |
| `evidencias` | ID, descripción y URL absoluta del disco público existente. No expone la ruta interna. |

Los documentos incluyen únicamente ID, folio, estado, tipo/fecha y referencia de compra cuando aplica; las compras incluyen estado de recepción. No se publican URLs web que requieran sesión/permisos ajenos ni endpoints nuevos de inventario/compras. Los documentos eliminados lógicamente no se incluyen. Las relaciones vacías se representan con `[]`.

La consulta no genera órdenes, solicitudes, costos ni fotos; no reserva ni descuenta existencias. Los cálculos de avance conservan la semántica web actual. Antes de generar snapshot pueden mostrar la plantilla vigente; `materiales` siempre representa exclusivamente filas ya congeladas. Un snapshot vacío no puede distinguirse de ausencia de snapshot usando solo filas; consultar también folio/estado de la orden.

**Límite de vehículos:** el cálculo automático de abastecimiento actual retorna vacío para vehículos. No se considera resuelto por T07; se conservan los límites de T05. Solicitudes/documentos y materiales congelados existentes sí se consultan mediante sus relaciones.

## Acciones calculadas en servidor

`acciones_permitidas.editar_borrador` es true únicamente si:

1. Hay empleado vinculado existente y coincide con el mecánico asignado.
2. El estado es `borrador` y no hay folio de orden.
3. No existen materiales congelados ni solicitudes de abastecimiento.

El acceso mecánico se valida antes de ejecutar el controlador. PATCH T06 vuelve a verificar propiedad/estado/documentos dentro de su transacción: este indicador orienta la interfaz y no sustituye la autorización al guardar.

Al cierre original de T07 las claves `generar_orden`, `ajustar_materiales`, `solicitar_almacen`, `solicitar_compra`, `agregar_evidencias`, `iniciar`, `completar` y `cancelar` devolvían false. Desde [T08](CONTRATO_ORDEN_SERVICIO_MECANICO_T08.md), `generar_orden` se calcula para borradores propios con equipo/tipo válido; las demás siguen pendientes de sus endpoints/reglas. Cancelación permanece exclusivamente web según D04. No se anuncian acciones ejecutables sin implementación API.

Desde [T09](CONTRATO_MATERIALES_SERVICIO_MECANICO_T09.md), `ajustar_materiales` se calcula para órdenes propias con folio, snapshot y sin solicitudes. Las demás acciones aún no publicadas permanecen en false.

Desde [T10](CONTRATO_ABASTECIMIENTO_SERVICIO_MECANICO_T10.md), las acciones de solicitar almacén/compra se calculan según propiedad, etapa y faltantes del snapshot; cada una se deshabilita ante una solicitud activa del mismo tipo. La consulta de orden emitida sin filas conserva el snapshot vacío y no consulta una plantilla agregada posteriormente.

Desde [T11](CONTRATO_EVIDENCIAS_SERVICIO_MECANICO_T11.md), `agregar_evidencias` es true para el servicio propio con menos de ocho fotos. Se conserva la carga permitida en las etapas existentes de la web. Ejecución continúa pendiente de T12 y cancelación sigue exclusivamente web.

## Errores

- 401: sin autenticación.
- 403: sin acceso mecánico o usuario app inactivo/no registrado.
- 404: detalle inexistente o fuera del alcance programado; ID no numérico tampoco coincide con la ruta.
- 422: filtro inválido.

## Checkpoint y validación

- Pruebas reales sobre SQLite en memoria: alcance de web/API, servicios ajenos, emergencias, filtros, fechas, paginación, empleado ausente, máquina/vehículo, propiedad y estados, borradores congelados y aislamiento de documentos/evidencias entre servicios.
- Comprobado avance compartido con surtido parcial, stock reservado, folios de salida y órdenes de compra. Las lecturas conservan stock y solicitudes.
- Suite móvil completa: **56 pruebas / 511 comprobaciones**. Ejecutar con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobile --no-progress`; no se modifica php.ini ni la base local.
- Sintaxis PHP, rutas protegidas, UTF-8/mojibake y actualización graphify al cerrar la tarea.

Siguiente: T08, generación de orden compartida con web y protección de reintentos/concurrencia.
