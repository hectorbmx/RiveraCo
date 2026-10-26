# T10. Solicitudes de abastecimiento y avance

Fecha: 2026-10-23. Backend implementado, sin pantallas móviles ni cambios de inventario desde la API mecánica.

## Contrato

`POST /api/v1/mecanico/servicios/{servicio}/abastecimientos`

```json
{"tipo": "almacen"}
```

Único campo permitido: `tipo`, obligatorio, `almacen` o `compra`. Se rechazan cantidades, productos, materiales, precios, estados, almacén, autor y cualquier campo adicional. Se usa el snapshot incluido de la orden y se calcula el faltante en servidor.

Requiere Sanctum, usuario app activo, `app.mecanico.access`, empleado vinculado existente y servicio programado asignado al propio empleado. Sin obra asignada ni permiso gerencial.

Respuesta `200` tanto al crear como al recuperar una solicitud activa del mismo tipo:

```json
{
  "ok": true,
  "data": {},
  "solicitud_id": 42
}
```

`data` es el detalle completo de T07, con estado, snapshot, `avance_materiales`, solicitudes y documentos. `solicitud_id` identifica la solicitud creada o recuperada, incluida en `data.solicitudes`. Su folio se genera como `SMA-YY-ID`, mínimo seis dígitos para el ID, mediante el evento de modelo existente. El autor es el usuario autenticado.

## Flujo compartido y límites

`MantenimientoAbastecimientoService::crearSolicitud()` ahora ejecuta toda la decisión y el guardado dentro de una transacción, con bloqueo y relectura del mantenimiento:

1. Verifica tipo/propiedad en llamadas móviles sobre el registro bloqueado.
2. Requiere folio de orden y estado `orden_generada`, `pendiente` o `en_proceso`. No permite borradores, completados, cancelados ni etapas desconocidas.
3. Recalcula los materiales incluidos y su faltante; rechaza selección vacía, necesidad completamente surtida o materiales pendientes sin producto vinculado.
4. Si existe solicitud del mismo tipo cuyo estado no sea `surtida` ni `cancelada`, devuelve esa solicitud sin duplicarla ni reescribir sus cantidades. Esta recuperación requiere que el servicio siga en etapa válida y aún haya faltante; una petición posterior al surtido completo devuelve 409.
5. Si no existe una activa del mismo tipo, crea solicitud y detalles con cantidades iguales al faltante, atendido cero y estado pendiente. Las solicitudes posteriores a una cerrada representan solo lo aún no surtido.
6. Actualiza `abastecimiento_tipo` y `abastecimiento_estado` según el tipo. Cambia el servicio a `pendiente`, conservando `en_proceso` si ya había iniciado; nunca retrocede esa ejecución.
7. Un fallo revierte solicitud, folio, detalles y cambios de cabecera. Hasta tres intentos para errores transaccionales de concurrencia reconocidos por Laravel.

El bloqueo del mismo mantenimiento coordina generación, ajustes T09 y creación de solicitudes web/API: si el ajuste termina primero, se solicita su selección confirmada; si la solicitud termina primero, el ajuste se rechaza. La prueba simultánea real requiere InnoDB y sigue en T14; SQLite prueba relectura/atomicidad/reintentos, pero no bloqueos de fila.

Se conserva la regla existente de una solicitud activa **por tipo**. Puede coexistir una de almacén y otra de compra. La solicitud de compra comprende el faltante de surtido, no únicamente lo que carece de stock. No reserva cantidades ni garantiza existencias; no sustituye las validaciones posteriores de compras/surtido. No se crea automáticamente una orden de compra ni un documento de salida.

## Snapshot y avance

Una orden emitida usa siempre sus materiales congelados, incluso si no tiene filas. Se corrigió la caída a la plantilla actual cuando el snapshot estaba vacío: agregar una plantilla después de generar la orden ya no permite solicitar materiales nuevos implícitamente.

GET del detalle conserva `solicitudes`/documentos del servicio e incluye `avance_materiales` compartido con web:

- Requerido, surtido acumulado, disponible neto (`stock_actual - stock_reservado`, mínimo cero) de Giralda, surtible, faltante y estado.
- Folios de salidas aplicadas vinculadas a las solicitudes.
- Cada solicitud mantiene sus materiales y cantidades requerida/solicitada/atendida; se publican folios/estados de documentos y compras vinculados.

El stock es informativo. Crear o repetir una solicitud no descuenta stock, no reserva, no registra surtido ni crea documentos/órdenes de compra. El surtido continúa en el flujo web de inventario y sus documentos.

**Vehículos:** se conserva el límite previo: no tienen cálculo automático de materiales/abastecimiento. Esta ruta devuelve 409 por ausencia de materiales calculables; sus acciones de solicitar permanecen en false. No se agrega configuración ni se simula ese flujo.

## Acciones y compatibilidad web

`solicitar_almacen`/`solicitar_compra` se calculan en listado y detalle para el propio servicio de maquinaria con orden, etapa válida y materiales incluidos pendientes con producto. Cada acción es false cuando ya existe solicitud activa de ese tipo. El endpoint admite recuperar esa solicitud aunque el botón esté deshabilitado.

El listado precarga solo columnas mínimas de materiales/solicitudes/cantidades para estas acciones, sin consultar stock por fila. El detalle mantiene las proyecciones completas.

La web conserva ruta, campos, redirect y folios/mensajes, usando el mismo servicio bloqueado. Su acción exige explícitamente `maquinas.servicios.edit.access`; la API usa acceso mecánico y propiedad. Los conflictos de negocio se muestran como errores de formulario, sin convertir fallos SQL inesperados en conflictos aparentes.

## Errores

| HTTP | Caso |
|---|---|
| 401 | Sin sesión. |
| 403 | Sin acceso mecánico/usuario activo o servicio ajeno. |
| 404 | Servicio inexistente o emergencia fuera del alcance móvil. |
| 409 | Sin orden/etapa inválida, cancelado/completado, materiales vacíos, totalmente surtidos o sin producto. |
| 422 | Empleado vinculado ausente/inexistente, tipo inválido o campos adicionales. |

## Validación

Nueve pruebas nuevas sobre SQLite efímero: creación de almacén, reintentos, compra y estado en proceso, solo materiales incluidos, faltantes posteriores/surtido total, entrada/propiedad/permisos, etapas y modelo obsoleto, orden congelada vacía, materiales excluidos/sin producto, interoperabilidad web/API y rollback por fallo en un detalle. Se verifican folios, autor, acciones y stock sin cambios.

Suite móvil completa: **80 pruebas / 783 comprobaciones**, comando `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobile --no-progress`. Sin modificar base local ni php.ini. Sintaxis/rutas/UTF-8 y graphify al cierre.

Siguiente: T11, carga de evidencias con optimización WebP y limpieza ante fallos.
