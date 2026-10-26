# T08. Generación de orden de servicio

Fecha: 2026-10-23. Implementación de backend; no incluye pantallas móviles.

## Contrato API

`POST /api/v1/mecanico/servicios/{servicio}/generar-orden`

- Autenticación Sanctum, usuario app activo y `app.mecanico.access`.
- Empleado vinculado existente, servicio programado y asignación al propio empleado. Sin requisito de obra.
- Cuerpo vacío (`{}` o sin cuerpo). Se rechaza con 422 cualquier campo, incluidos folio, materiales, autor, estado y mecánico. Estos valores se calculan en servidor a partir del borrador guardado.
- Primera generación y reintentos válidos responden `200`, con `{ok: true, data: ...}`. `data` tiene el mismo detalle de seguimiento de T07, con orden, materiales y acciones actualizadas.
- Al generar se guarda `estatus = orden_generada`, folio `OSM-YY-ID` (ID con mínimo seis dígitos), fecha y usuario autenticado como autor. Ninguno proviene del teléfono.
- No genera solicitudes, movimientos de inventario ni costos. No reserva ni descuenta stock.

## Reglas compartidas con la web

`MantenimientoOrdenService::generar()` comparte folio, fecha, autor, snapshot y transacción entre la web y la API.

1. Inicia transacción y relee el mantenimiento con `lockForUpdate()`. No confía en el estado ni en el propietario del modelo de ruta recibido.
2. En llamadas móviles valida tipo programado y propiedad sobre ese registro bloqueado, incluso en reintentos.
3. Si ya tiene folio y está fuera de borrador, devuelve el registro sin escribir. No sustituye materiales, fecha ni autor; tampoco revierte el estado si avanzó a otra etapa. El reintento es idempotente, incluso si cambió la plantilla o se desactivó el tipo después de generar.
4. Para crear una orden nueva exige borrador sin folio, materiales congelados ni solicitudes. Un folio en estado borrador se considera inconsistente y devuelve conflicto, sin repararlo automáticamente.
5. Exige exactamente un equipo y tipo preventivo activo del ámbito correcto para un programado. No vuelve a comparar el horómetro planificado con la lectura actual: el equipo puede haber trabajado desde la creación del borrador.
6. Usa el mismo catálogo web para seleccionar materiales: plantilla específica de máquina si existe; de lo contrario, plantilla general. Solo tipos/materiales activos. Copia nombre, SKU, unidad, cantidad, referencia de producto/material preventivo, orden e inclusión.
7. Folio y materiales se guardan en la misma transacción. Un fallo revierte también los materiales insertados antes del error y la metadata de orden. Se permiten hasta tres intentos de transacción ante errores de concurrencia reconocidos por Laravel.

Dos generadores que compiten por el mismo servicio se serializan mediante la fila del mantenimiento en motores con bloqueo de fila, como InnoDB. El segundo relee el folio confirmado y no inserta otro snapshot. La edición de borradores T06 utiliza el mismo bloqueo de mantenimiento y, después de generar, rechaza la edición de cabecera.

La web conserva su URL, redirect, mensaje de éxito y soporte de emergencias sin plantilla. La generación web exige ahora explícitamente `maquinas.servicios.edit.access`, igual que la edición de mantenimiento; no depende del permiso móvil. Los errores de validación regresan con los errores habituales y un conflicto regresa con mensaje de error.

**Límite de vehículos preservado:** con un tipo de vehículo configurado se puede generar folio sin snapshot de materiales, igual que en la web existente. Esto no implementa plantillas ni abastecimiento de vehículos. En maquinaria también puede existir una orden sin materiales configurados. Un reintento no consulta ni agrega una plantilla creada después de generar la orden original.

## Acciones de seguimiento

Desde T08, `acciones_permitidas.generar_orden` se habilita para un borrador propio sin folio/materiales/solicitudes, con un solo equipo y un tipo preventivo activo del ámbito correcto. Tras generar, `editar_borrador` y `generar_orden` son false. El endpoint acepta reintentos aunque el botón ya no esté habilitado, para recuperar una respuesta perdida.

Las demás acciones futuras continúan en false hasta implementar sus contratos. Ningún indicador sustituye la validación transaccional de la operación.

## Errores

| HTTP | Caso |
|---|---|
| 401 | Sin sesión. |
| 403 | Sin permiso mecánico, usuario app no activo o servicio asignado a otro empleado. |
| 404 | Servicio inexistente o emergencia fuera del alcance móvil. |
| 409 | Sin orden previa y etapa no borrador; borrador con folio, snapshot o solicitud previa. |
| 422 | Cuerpo no vacío, empleado vinculado ausente/inexistente, equipo inválido o tipo preventivo ausente/inactivo/ámbito incorrecto. |

## Checkpoint y pruebas

Ocho pruebas nuevas sobre SQLite efímero, añadidas a la suite de servicios existente:

- Generación con materiales y unidades; folio, autor y acciones.
- Reintentos que conservan snapshot, metadata y estado posterior aun con cambios de catálogo.
- Permisos, propiedad, tipo de servicio, empleado, inexistencia y rechazo de campos enviados.
- Relectura del estado persistido con un modelo obsoleto; conflictos en etapas no válidas y borradores inconsistentes.
- Tipo preventivo válido en el momento de generar y disponibilidad coherente de la acción.
- Fallo real mediante trigger en el segundo material: rollback de metadata y todas las filas del snapshot.
- Generación web seguida de reintentos API/web sin duplicación; permiso web explícito.
- Vehículo con tipo configurado, sin plantilla, genera orden vacía de forma idempotente.

Suite móvil completa: **64 pruebas / 606 comprobaciones**. Comando: `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobile --no-progress`. No se modifica php.ini ni la base de datos local.

**Límite de la validación original:** SQLite no implementa `SELECT FOR UPDATE`; estas pruebas verifican atomicidad, reintentos y relectura, pero no ejecutan peticiones MySQL simultáneas. La protección requiere un motor transaccional con bloqueo de fila.

**Validación posterior C14.1, 2026-10-23:** tres escenarios concurrentes con kernels HTTP separados sobre MariaDB/InnoDB temporal: generación doble sin duplicación, rollback de la primera generación con segunda petición en espera y edición concurrente rechazada con 409 después de generar. Espera comprobada mediante INNODB_LOCK_WAITS e INNODB_TRX, con stock intacto. Sesión y proyección de seguimiento controladas; transacciones, catálogo y escrituras reales. [Evidencia y reproducción](VALIDACION_CONCURRENCIA_APP_MECANICO_C14_1.md). El recorrido conectado con usuario/dispositivo de T14 sigue pendiente.

Siguiente punto: T09, ajuste de inclusión/exclusión de materiales con pertenencia y bloqueo tras solicitar abastecimiento.
