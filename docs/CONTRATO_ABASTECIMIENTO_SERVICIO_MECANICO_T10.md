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
5. Si no existe una activa del mismo tipo, crea solicitud y detalles con atendido cero y estado pendiente. Para almacén, solicita lo pendiente de entregar; para compra, solicita únicamente `por_comprar`, descontando lo cubierto por el inventario disponible. La cantidad requerida conserva lo pendiente total del servicio como referencia.
6. Actualiza `abastecimiento_tipo` y `abastecimiento_estado` según el tipo. Cambia el servicio a `pendiente`, conservando `en_proceso` si ya había iniciado; nunca retrocede esa ejecución.
7. Un fallo revierte solicitud, folio, detalles y cambios de cabecera. Hasta tres intentos para errores transaccionales de concurrencia reconocidos por Laravel.

El bloqueo del mismo mantenimiento coordina generación, ajustes T09 y creación de solicitudes web/API: si el ajuste termina primero, se solicita su selección confirmada; si la solicitud termina primero, el ajuste se rechaza. La prueba simultánea real requiere InnoDB y sigue en T14; SQLite prueba relectura/atomicidad/reintentos, pero no bloqueos de fila.

Se conserva la regla existente de una solicitud activa **por tipo**. Puede coexistir una de almacén y otra de compra. Desde el ajuste del 2026-10-29 la solicitud de compra comprende únicamente lo pendiente que no cubre el stock neto de Giralda. Si el inventario cubre todo, se rechaza una compra nueva y se orienta al surtido de almacén. Una solicitud activa se recupera sin reescribir su cantidad. No reserva cantidades ni garantiza existencias; no sustituye las validaciones posteriores de compras/surtido. No se crea automáticamente una orden de compra ni un documento de salida.

## Snapshot y avance

Una orden emitida usa siempre sus materiales congelados, incluso si no tiene filas. Se corrigió la caída a la plantilla actual cuando el snapshot estaba vacío: agregar una plantilla después de generar la orden ya no permite solicitar materiales nuevos implícitamente.

GET del detalle conserva `solicitudes`/documentos del servicio e incluye `avance_materiales` compartido con web:

- Requerido, surtido acumulado, disponible neto (`stock_actual - stock_reservado`, mínimo cero) de Giralda, surtible, faltante de entrega, `por_comprar` y estado. Las partidas repetidas comparten el saldo de inventario y lo ya surtido; no duplican la cobertura del mismo producto.
- Folios de salidas aplicadas vinculadas a las solicitudes.
- Cada solicitud mantiene sus materiales y cantidades requerida/solicitada/atendida; se publican folios/estados de documentos y compras vinculados.

El stock es informativo. Crear o repetir una solicitud no descuenta stock, no reserva, no registra surtido ni crea documentos/órdenes de compra. El surtido continúa en el flujo web de inventario y sus documentos.

**Vehículos:** se conserva el límite previo: no tienen cálculo automático de materiales/abastecimiento. Esta ruta devuelve 409 por ausencia de materiales calculables; sus acciones de solicitar permanecen en false. No se agrega configuración ni se simula ese flujo.

## Acciones y compatibilidad web

`solicitar_almacen`/`solicitar_compra` se calculan en listado y detalle para el propio servicio de maquinaria con orden, etapa válida y materiales incluidos pendientes con producto. Cada acción es false cuando ya existe solicitud activa de ese tipo. El endpoint admite recuperar esa solicitud aunque el botón esté deshabilitado.

El listado precarga solo columnas mínimas de materiales/solicitudes/cantidades para estas acciones, sin consultar stock por fila. En el detalle se habilita `solicitar_compra` únicamente si además hay una cantidad por comprar. El endpoint recalcula el inventario al crear y sigue admitiendo recuperar una solicitud activa.

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

## Autorización de compra — ajuste del 2026-10-29

- Crear una OC en `BORRADOR` no adelanta la solicitud a `en_compra`. La autorización de la OC y la sincronización de su solicitud se guardan en una misma transacción.
- `MantenimientoCompraEstadoService` calcula si existe alguna OC vinculada autorizada, aprobada o verificada. Al revocar la última autorización, la solicitud vuelve a pendiente; si hay otra OC autorizada, conserva el avance. No retrocede recepción parcial, material en almacén, surtido ni cancelación de la solicitud.
- El badge móvil muestra **Compra en proceso** cuando hay una OC autorizada. Para solicitudes antiguas que quedaron en `en_compra` con solo borradores, muestra **Compra pendiente** sin modificar registros durante la consulta. El seguimiento desplegable usa las mismas etiquetas.
- No requiere migración. La compra autorizada todavía no equivale a material recibido ni entregado al servicio.
- Validación: cinco pruebas de backend con 16 comprobaciones (creación real de borrador, autorización, rechazo, revocación con varias OCs y conservación de recepciones/surtidos); 18 pruebas de consulta móvil y compilación Angular correctas. Sintaxis PHP y UTF-8 verificados, sin modificar registros reales.

## Inventario visible y compra del faltante — ajuste del 2026-10-29

- El card móvil de ajuste muestra Requerido y Disponible en Giralda, más Por comprar y la orientación de surtido. La cantidad original se conserva en el snapshot, aunque ya no ocupa una caja del card.
- Cada material del detalle recibe su disponible, también cuando está excluido. Así puede volver a incluirse sin perder la referencia de inventario. Las exclusiones siguen exigiendo motivo y mostrando requerido cero.
- La compra nueva solicita solo `max(0, pendiente_de_entrega - cobertura_de_inventario)`. Almacén conserva la necesidad pendiente completa para permitir entregas parciales; no se crean dos compras por la misma cobertura de stock ni se reescriben solicitudes activas.
- El cálculo reparte el inventario compartido entre partidas repetidas de un producto. El cliente recalcula la orientación al marcar/desmarcar, y el servidor vuelve a calcular al solicitar. Un dato de inventario ausente se muestra como Sin dato, sin inventar existencias cero.
- Verificación local: 76 pruebas PHP y 826 comprobaciones; 82 pruebas móviles; compilación Angular y vistas Blade correctas. Consulta del servicio 17: aceite requerido 25 L, disponible 6 L y por comprar 19 L; base de filtro requerida 1 pieza, disponible 3 y por comprar cero. No se modificaron registros reales ni se requiere migración.
