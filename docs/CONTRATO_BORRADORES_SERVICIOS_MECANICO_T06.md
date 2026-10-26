# T06: creación y edición de borradores programados

- Fecha: 2026-10-23.
- Estado: alta y edición de borradores implementadas y verificadas en backend.
- Plan: [PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md](PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md).
- Los ejemplos usan identificadores ficticios; los IDs de tipo preventivo deben obtenerse del catálogo T05.

## Acceso y autoría

Se requiere Bearer de Sanctum, `Accept: application/json`, usuario app activo y permiso efectivo `app.mecanico.access`. No se exige una obra asignada.

Para crear o editar, `usuarios_app.empleado_id` debe apuntar a un empleado existente. Si falta el vínculo o el empleado no existe se devuelve 422 en `mecanico_id`. No se deduce el empleado por nombre, puesto ni rol.

Al crear, el servidor asigna `mecanico_id` a ese empleado. El cliente no puede elegir otro mecánico. Al editar, el servicio debe ser programado y estar asignado a ese mismo empleado; un borrador de otro mecánico devuelve 403 y un servicio de emergencia devuelve 404.

## POST /api/v1/mecanico/servicios

Crea una cabecera en `borrador`, con `tipo = programado` y `abastecimiento_estado = pendiente`. Devuelve HTTP 201 con `ok` y `data`.

```json
{
  "maquina_id": 17,
  "categoria_mantenimiento": "Preventivo",
  "servicio_preventivo_tipo_id": 1,
  "descripcion": "Servicio preventivo de maquinaria",
  "horometro": 250,
  "fecha_programada": "2026-10-25"
}
```

Para vehículo, enviar `vehiculo_id` en lugar de `maquina_id` y un tipo preventivo activo de ámbito vehículo. Solo puede elegirse un equipo. La falta de configuración de tipos de vehículo encontrada en T05 no se resuelve creando tipos ficticios durante el alta.

### Campos de captura

| Campo | Creación | Edición |
|---|---|---|
| `maquina_id` / `vehiculo_id` | Exactamente uno, ID entero positivo y existente. | Se conservan si se omiten. Para cambiar de ámbito se debe enviar el ID anterior como `null` y el nuevo equipo/tipo/lectura correspondientes. |
| `obra_id` | Opcional, ID existente o `null`. | Opcional; `null` elimina la referencia. No requiere asignación del mecánico a esa obra. |
| `categoria_mantenimiento` | Texto obligatorio, máximo 100 caracteres. | Conserva la regla web de edición: texto de hasta 100 o `null`. |
| `servicio_preventivo_tipo_id` | Obligatorio para programado; activo y del ámbito correcto. | Se valida el valor final, incluyendo el que se conserva del borrador. |
| `descripcion` | Opcional, texto de al menos tres caracteres cuando se envía no vacío. | Texto opcional, según regla web de edición. |
| `horometro` | Obligatorio para máquina; no negativo ni inferior al consolidado actual. | Si existe un valor final, debe respetar el consolidado. Conserva la nulabilidad de la edición web; no equivale a registrar un ajuste de horómetro. |
| `km_actuales`, `km_proximo_servicio` | Enteros no negativos, opcionales. | Mismas reglas; no se calcula ni cambia automáticamente el odómetro del vehículo. |
| `fecha_programada` | Fecha obligatoria. | Si se omite se conserva; admite `null`, como la edición web. |
| `tipo` | Opcional; si se envía solo se admite `programado`. El servidor lo fija igualmente. | No permite convertir un servicio en emergencia. |

No se crea orden, snapshot de materiales, solicitud, salida de inventario ni evidencia al dar de alta el borrador. Esas operaciones pertenecen a las siguientes tareas.

## PATCH /api/v1/mecanico/servicios/{servicio}

Actualiza únicamente los campos de captura enviados y conserva el resto. Devuelve HTTP 200 con `ok` y `data`.

```json
{
  "descripcion": "Se ajustó la programación del servicio",
  "fecha_programada": "2026-10-26",
  "horometro": 260
}
```

El servicio relee y bloquea la fila del mantenimiento dentro de una transacción. No confía solo en el estado del modelo resuelto desde la ruta. Solo admite:

- `estatus = borrador`.
- Sin folio de orden de servicio.
- Sin snapshot de materiales de orden.
- Sin solicitudes de abastecimiento.

Si ya cambió de etapa o tiene esos documentos devuelve 409 con un mensaje explícito. Se revalidan las reglas del equipo y tipo preventivo sobre los valores finales; editar solo la descripción no omite esa validación. Si el consolidado de la máquina avanzó, puede ser necesario actualizar también la lectura del borrador.

## Campos administrados por servidor

El cliente no puede introducir ni editar:

- `mecanico_id`.
- `estatus`, `abastecimiento_tipo`, `abastecimiento_estado`.
- Folio, fecha y usuario de generación de orden.
- `costo_mano_obra`, `costo_refacciones`, `costo_total`.
- `detalles` con conceptos/precios.
- `fecha_inicio`, `fecha_fin`, `fotos`, `foto_descripciones`.

Los valores no vacíos en esos campos se rechazan con 422. Los valores vacíos que admite la regla `prohibited` y los campos ajenos al contrato no se trasladan a persistencia: se usa una lista explícita de campos de captura. Los costos se consultan en la respuesta, pero no se editan desde este módulo.

## Respuesta

`data` contiene los campos de captura, `id`, `tipo`, `mecanico_id`, `estatus`, `abastecimiento_estado`, `orden_servicio_folio`, costos de consulta y `created_at`/`updated_at`.

- IDs y kilómetros: enteros o `null`.
- Horómetro y costos: números o `null`.
- Fechas: serialización de los casts de fecha del modelo, en formato ISO de Laravel.
- Una respuesta de alta identifica el recurso para continuar el flujo; T06 no implementa aún el GET de seguimiento de servicios. Ese contrato corresponde a T07.

## Errores

| HTTP | Caso |
|---|---|
| 401 | Sin sesión/token válido. |
| 403 | Sin acceso mecánico, usuario app no habilitado/activo o edición de un servicio asignado a otro empleado. |
| 404 | ID de mantenimiento inexistente o intento de editar una emergencia por este contrato. |
| 409 | El mantenimiento dejó de ser un borrador editable o ya tiene orden/materiales/solicitudes. |
| 422 | Datos inválidos, equipo/tipo de alta inexistente, falta de vínculo de empleado o intento de editar campos protegidos. |

Las validaciones conservan el formato de Laravel con `message` y `errors`. Los conflictos de etapa devuelven `ok: false` y `message`.

## Reutilización con la web

`MantenimientoBorradorService` concentra las reglas de captura, validación de equipo/horómetro/tipo preventivo y persistencia transaccional de borradores.

- La creación web delega al servicio. Conserva altas programadas y de emergencia, selección administrativa de mecánico y conceptos manuales permitidos en web.
- La edición web que mantiene el estado borrador, sin nueva evidencia ni ruta de abastecimiento, delega al mismo guardado. La API solo usa este flujo de borrador.
- Las otras etapas web y la carga de evidencias conservan su flujo existente. La validación de cabecera/tipo y las reglas base se comparten también con la edición web, sin publicar sus operaciones avanzadas en la API.
- La web conserva sus redirecciones y mensajes de éxito. La API devuelve JSON.
- Las validaciones base se reforzaron con IDs enteros positivos y kilómetros no negativos en ambos clientes.
- Los conceptos web se normalizan y sus costos se recalculan en servidor. Creación de cabecera, conceptos y costos se hace en una transacción; al fallar un concepto no queda una cabecera parcial.
- La edición compartida relee el mantenimiento con bloqueo. En validaciones de lectura dentro de las transacciones se bloquea también la fila de máquina. Las pruebas SQLite verifican atomicidad y relectura, no una carrera real de procesos MySQL.

## Validación y checkpoint

- 48 pruebas/398 comprobaciones con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobile --no-progress`.
- Catorce pruebas nuevas de T06 sobre SQLite en memoria: alta de máquina/vehículo, referencia opcional de obra, asignación automática, falta de empleado, validaciones de activos/tipos/lecturas, edición parcial, permisos, propiedad, emergencia, estados/documentos, protección de precios y campos internos.
- Comparación de cabeceras persistidas por web/API para el mismo borrador programado. Verificación de creación web de emergencia y sus costos, además de edición web de borrador.
- Fallos SQL controlados verifican rollback de alta y rollback de edición con reemplazo de conceptos. Relectura de un modelo desactualizado verifica rechazo tras cambio de estado en la base.
- Ninguna prueba de escritura se ejecutó contra la base local de trabajo; las tablas/datos de prueba se crean únicamente en memoria. No se agregaron migraciones ni permisos.
- Sintaxis, UTF-8, rutas registradas y estilo de archivos nuevos verificados. La app móvil aún necesita sus pantallas e integración en T13.
- Siguiente tarea: T07, listado y detalle de seguimiento con acciones permitidas; generar orden continúa en T08.
