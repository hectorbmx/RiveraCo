# T09. Ajuste de materiales de la orden

Fecha: 2026-10-23. Backend implementado; pantallas móviles pendientes.

## Contrato API

`PATCH /api/v1/mecanico/servicios/{servicio}/materiales`

Requiere Sanctum, usuario app activo, `app.mecanico.access` y un empleado existente vinculado. Solo modifica servicios programados asignados al propio empleado, sin requisito de obra.

```json
{
  "materiales": [
    {"id": 25, "incluido": false, "motivo_exclusion": "No requiere cambio en esta revisión"},
    {"id": 26, "incluido": true}
  ]
}
```

- `materiales`: arreglo obligatorio, entre 1 y 500 filas.
- Cada fila admite exclusivamente `id`, `incluido` y `motivo_exclusion`.
- `id`: entero positivo, sin duplicados, perteneciente al snapshot de este servicio.
- `incluido`: booleano obligatorio. Se aceptan los valores booleanos estándar de validación Laravel (`true`, `false`, `0`, `1`, `"0"`, `"1"`).
- `motivo_exclusion`: texto de hasta 255 caracteres. Al excluir debe existir un motivo no vacío, también en web. Omitirlo conserva el existente; enviar null, vacío o espacios se rechaza si la partida queda excluida. Al incluir, siempre se limpia el motivo.
- Ajuste autorizado el 2026-10-28: el motivo pasa a ser obligatorio para justificar piezas o consumibles que no se aplican en este servicio.
- Es un ajuste parcial: las filas omitidas permanecen como están. Para excluir todas deben enviarse explícitamente; un arreglo vacío se rechaza.
- Se rechazan campos superiores desconocidos y campos adicionales dentro de cada fila. No se permite agregar/eliminar materiales, sustituir productos, cambiar cantidades, unidad, orden del snapshot ni precios.

Respuesta `200`: `{ok: true, data: ...}` con el mismo detalle de seguimiento T07, incluido snapshot actualizado y avance de materiales compartido con la web. Los materiales excluidos siguen visibles en `materiales`, pero no se consideran en `avance_materiales`. No se generan solicitudes, costos, movimientos ni reservas/descuentos de inventario.

## Reglas de etapa y pertenencia

`MantenimientoMaterialesService` comparte el guardado con la web:

1. Valida la estructura de entrada y permite únicamente los campos del contrato.
2. Inicia transacción y relee el mantenimiento con bloqueo de fila. En API vuelve a verificar tipo programado y mecánico asignado.
3. Exige `estatus = orden_generada`, folio existente y ausencia de cualquier solicitud de abastecimiento. Una solicitud surtida o cancelada también bloquea el ajuste, igual que en la web anterior.
4. Lee/bloquea las filas del snapshot de ese mantenimiento. Verifica la pertenencia de todos los IDs antes de escribir la primera fila.
5. Actualiza exclusivamente inclusión/motivo en una sola transacción. Si falla una fila, se revierten las anteriores. Hasta tres intentos ante errores transaccionales de concurrencia reconocidos por Laravel.

Los bloqueos coordinan estos ajustes con la generación y edición compartidas de T06/T08. En T10 se debe colocar también la creación de solicitudes dentro del bloqueo del mismo mantenimiento, para cerrar la carrera entre ajuste y una solicitud iniciada simultáneamente. La comprobación T09 rechaza solicitudes ya existentes; no se afirma que la creación de solicitudes previa a T10 ya esté coordinada.

Actualización T10: la creación web/API de solicitudes ya utiliza el mismo bloqueo y relectura, según el [contrato de abastecimiento](CONTRATO_ABASTECIMIENTO_SERVICIO_MECANICO_T10.md). La prueba concurrente InnoDB permanece en T14.

## Compatibilidad web y acciones

La URL web y su formulario conservan `incluidos[]` y `motivos[id]`, su redirección y mensaje de éxito. La web usa selección completa: filas sin checkbox se excluyen y motivos ausentes quedan vacíos. Se rechazan también referencias ajenas en las claves de `motivos`, aunque no se hayan seleccionado.

La acción web exige explícitamente `maquinas.servicios.edit.access`. La API utiliza el permiso mecánico y propiedad, sin otorgar acceso web/gerencial.

Desde T09, `acciones_permitidas.ajustar_materiales` es true solo para un servicio propio en orden generada, con folio, snapshot no vacío y sin solicitudes. El endpoint vuelve a comprobar las reglas al guardar. Una orden sin filas no muestra la acción y no admite inventar materiales por esta ruta. Se preserva el límite de plantillas/abastecimiento de vehículos de T05/T08.

## Errores

| HTTP | Caso |
|---|---|
| 401 | Sin autenticación. |
| 403 | Sin acceso mecánico/usuario activo o servicio ajeno. |
| 404 | Servicio inexistente o emergencia fuera del alcance móvil. |
| 409 | Etapa distinta de orden generada, sin folio o existe cualquier solicitud. |
| 422 | Empleado no vinculado, payload inválido, IDs duplicados/inexistentes/ajenos o intento de modificar campos congelados. |

## Checkpoint

Siete pruebas nuevas sobre SQLite en memoria: ajuste parcial/reintento, motivo omitido/null/reinclusión, cantidades/costos preservados, pertenencia por servicio, payload y campos congelados, todos los estados y solicitudes surtidas/canceladas, actor/permisos, selección completa web, permiso web y rollback real al fallar la segunda fila mediante trigger.

Suite móvil completa: **71 pruebas / 692 comprobaciones**, con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobile --no-progress`. Sin modificación de la base local ni php.ini. Sintaxis, rutas protegidas y UTF-8 verificados; graphify actualizado al cierre. La prueba de concurrencia real sobre InnoDB sigue en T14.

Siguiente: T10, solicitudes y avance de abastecimiento, coordinados con el bloqueo del mantenimiento.

## Ajuste operativo del 2026-10-28

- Caso: aceite sustituido en una reparación reciente que no debe solicitarse de nuevo en el preventivo.
- El móvil muestra cantidad original y cantidad efectiva para este servicio (cero al excluir), sin borrar partidas ni cambiar la plantilla o la cantidad del snapshot.
- La exclusión exige motivo en cliente y servidor. Los motivos se normalizan y validan antes de escribir cualquier fila.
- Se conserva el ajuste exclusivo en orden generada antes de cualquier solicitud. En borrador se explica que primero debe generarse la orden; no se genera automáticamente.
- El cliente bloquea solicitudes de almacén/compra mientras haya cambios locales de materiales sin guardar, también en el manejador de la acción.
- El resumen posterior muestra la partida excluida, cero para este servicio y su motivo. La cantidad parcial sigue fuera de este contrato.
- Verificación del ajuste: 67 pruebas del backend y 699 comprobaciones (`AppMobileServiceDraftTest`), 28 pruebas de captura móvil y compilación Angular de desarrollo correctas. Se comprobó que el aceite excluido no se solicita, que la plantilla y el snapshot conservan su cantidad y que una exclusión sin motivo no escribe cambios. No requiere migración ni modificación de registros reales para validar.
