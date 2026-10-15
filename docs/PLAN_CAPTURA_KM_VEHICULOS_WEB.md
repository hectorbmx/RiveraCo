# Plan: Captura hibrida de KM de vehiculos

## Contexto

El flujo actual de kilometraje de vehiculos ya existe desde la app movil. Los residentes deberian registrar los KM desde la app, pero durante el periodo de integracion no todos tienen la app o no la estan usando de forma constante.

La propuesta es permitir que una persona autorizada capture KM desde la PC en `/mantenimiento/vehiculos`, usando el mismo historial que usa la app movil. La captura web debe ser temporal o controlada por permiso, para que en el futuro baste con revocar el permiso si los residentes ya actualizan desde la app.

## Hallazgos antes de comenzar

- La app movil ya guarda KM en `vehiculo_empleado_km_logs` mediante `App\Http\Controllers\Api\V1\VehiculoKmController`.
- El log actual ya guarda:
  - `vehiculo_empleado_id`
  - `obra_id`
  - `fecha`
  - `km`
  - `foto`
  - `foto_ticket_gasolina`
  - `monto_gasolina`
  - `notas`
  - timestamps
- El log actual no guarda quien capturo ni desde donde se capturo.
- El flujo movil exige foto de odometro.
- El flujo movil valida que el KM capturado no sea menor al mayor entre:
  - `km_inicial` de la asignacion
  - ultimo KM registrado en logs de la misma asignacion
- El flujo movil actualiza `km_final` de la asignacion con el KM capturado.
- El index de vehiculos muestra KM desde `PreventivoVehiculoService`.
- Ya se corrigio el calculo del KM actual para que no tome un log viejo menor al KM de asignacion.
- Existen permisos generales `mantenimiento.access` y `vehiculos.access`, pero no existe un permiso especifico para capturar KM desde web.
- Hay vehiculos sin asignacion activa; con la estructura actual no pueden recibir log de KM porque `vehiculo_empleado_km_logs` depende de `vehiculo_empleado_id`.

## Decisiones acordadas

- La captura web debe crear registros en `vehiculo_empleado_km_logs`, igual que la app movil.
- No se debe crear una tabla paralela para KM web.
- Se agregara trazabilidad al log.
- Campos nuevos acordados:
  - `capturado_por_user_id`
  - `origen`
- Valores de `origen`:
  - `app`
  - `web`
- Desde app:
  - `capturado_por_user_id = auth()->id()`
  - `origen = app`
- Desde web:
  - `capturado_por_user_id = auth()->id()`
  - `origen = web`
- Desde app, el usuario deberia corresponder al empleado de la asignacion por medio de `usuarioApp.empleado_id`.
- Desde web, el usuario puede ser distinto al empleado asignado porque puede ser mecanico/admin capturando por soporte operativo.
- El permiso propuesto para mostrar/usar la captura web es:
  - `vehiculos.km_logs.create.access`

## Checkpoints de implementacion

### 1. Migracion de trazabilidad

Agregar a `vehiculo_empleado_km_logs`:

- `capturado_por_user_id` nullable, relacionado con `users.id`.
- `origen` nullable o con default controlado.

Checkpoint:

- La migracion corre sin romper logs anteriores.
- Los logs existentes quedan con campos nuevos en `null`.
- No se modifica el significado de `vehiculo_empleado_id`.

### 2. Modelo `VehiculoEmpleadoKmLog`

Actualizar:

- `$fillable`
- relacion `capturadoPor()` hacia `User`
- casts si aplica

Checkpoint:

- Se puede crear un log con `capturado_por_user_id` y `origen`.
- Se puede consultar `$log->capturadoPor`.

### 3. Alinear flujo movil

Actualizar `Api\V1\VehiculoKmController@store` para guardar:

- `capturado_por_user_id = $request->user()->id`
- `origen = app`

Checkpoint:

- La app conserva validaciones actuales.
- La foto sigue siendo requerida.
- Sigue actualizando `km_final`.
- La respuesta API no cambia de forma incompatible.

### 4. Crear permiso web

Agregar o crear manualmente:

- `vehiculos.km_logs.create.access`

Checkpoint:

- Un usuario sin permiso ve el KM como texto.
- Un usuario con permiso ve accion para capturar KM.

### 5. Ruta web

Agregar ruta dentro de `mantenimiento`:

- `POST /mantenimiento/vehiculos/{vehiculo}/km-log`

Nombre sugerido:

- `mantenimiento.vehiculos.km-log.store`

Checkpoint:

- La ruta aparece en `php artisan route:list`.
- Esta protegida por autenticacion y permiso.

### 6. Metodo web para guardar KM

Crear metodo web que:

- Busca asignacion activa del vehiculo.
- Bloquea si no hay asignacion activa.
- Valida KM requerido.
- Valida que KM no sea menor al minimo permitido.
- Guarda fecha.
- Guarda foto/evidencia.
- Guarda notas.
- Crea log en `vehiculo_empleado_km_logs`.
- Actualiza `km_final` de la asignacion activa.
- Guarda `capturado_por_user_id`.
- Guarda `origen = web`.

Checkpoint:

- Si el vehiculo no tiene asignacion activa, no guarda.
- Si el KM es menor al minimo permitido, no guarda.
- Si guarda correctamente, el index recalcula el KM.

### 7. Modal en index de vehiculos

En `/mantenimiento/vehiculos`:

- Convertir KM en boton solo para usuarios con permiso.
- Abrir modal con:
  - vehiculo
  - placas
  - asignado actual
  - KM actual conocido
  - nuevo KM
  - fecha de captura
  - foto/evidencia
  - notas

Checkpoint:

- Sin permiso no hay accion visible.
- Con permiso el modal abre con datos correctos.
- El formulario apunta a la ruta correcta.

### 8. Validacion visual y UX

Mensajes esperados:

- Exito: KM registrado correctamente.
- Error sin asignacion: El vehiculo no tiene asignacion activa.
- Error por KM menor: El kilometraje no puede ser menor a X.

Checkpoint:

- El usuario entiende por que no puede guardar.
- El KM mostrado se actualiza al volver al index.

### 9. Verificaciones tecnicas

Ejecutar:

- `php -l` en archivos modificados.
- `php artisan route:list` filtrando vehiculos.
- Prueba con tinker o guardado manual para confirmar:
  - log creado
  - `capturado_por_user_id` correcto
  - `origen` correcto
  - `km_final` actualizado

Checkpoint:

- No hay errores de sintaxis.
- La ruta existe.
- El log queda trazable.

## Riesgos y reglas pendientes

- Vehiculos sin asignacion activa: por ahora no deben aceptar captura porque no hay `vehiculo_empleado_id`.
- Foto web: falta decidir si sera requerida igual que app o si sera opcional para el mecanico.
- Obra en captura web: la app exige obra activa; en web puede no haber una obra activa del usuario mecanico. Se debe decidir si:
  - se usa la obra asociada al empleado/asignacion si se puede resolver, o
  - se permite `obra_id = null`, o
  - se agrega selector de obra.
- Origen de fecha: la app usa `now()`. En web se propuso permitir fecha de captura; debe validarse si se guarda la fecha seleccionada o siempre `now()`.

## Orden recomendado

1. Migracion de trazabilidad.
2. Modelo.
3. Ajuste del API movil para llenar trazabilidad.
4. Permiso.
5. Ruta y metodo web.
6. Modal en index.
7. Verificacion completa.
---

# Extension: captura de gasolina del residente en Laravel

## Objetivo

Replicar en Laravel la captura de kilometraje y gasolina que actualmente existe en la app movil, permitiendo que el residente registre sus cargas desde el tab de vehiculos de una obra.

La captura debe reutilizar la bitacora `vehiculo_empleado_km_logs` y conservar la trazabilidad de quien capturo, desde donde capturo, en que obra y con que asignacion.

## Hallazgos del paso 1

- La app usa `GET /api/v1/vehiculos/km-log` para consultar registros.
- La app usa `POST /api/v1/vehiculos/km-log` para crear registros.
- Las rutas estan protegidas por `auth:sanctum`.
- La app obtiene la obra mediante `AppMobileContextService`; no confia solamente en el `obra_id` enviado.
- La app obtiene el vehiculo mediante el contexto del residente y su asignacion activa.
- El registro se vincula mediante `vehiculo_empleado_id`.
- La app valida que el kilometraje no sea menor al maximo entre el kilometraje inicial y el mayor kilometraje registrado de la asignacion.
- La app actualiza `km_final` en la asignacion activa.
- La foto del odometro sigue siendo obligatoria en la app movil.
- La foto del ticket es opcional.
- El monto de gasolina es opcional.
- La tabla ya contiene `obra_id`, `foto_ticket_gasolina`, `monto_gasolina`, `capturado_por_user_id` y `origen`.
- El tab de la obra ya consulta los logs filtrados por `obra_id`.
- El detalle del vehiculo ya consulta la bitacora, pero se debe distinguir el origen de cada registro.

## Decisiones tomadas

### Una sola bitacora

No se creara una tabla paralela para la captura web del residente. Todas las lecturas y cargas seguiran en `vehiculo_empleado_km_logs`.

### Origen del registro

Se conservaran los valores existentes y se agregara un origen especifico para Laravel:

- `app`: captura desde la app movil.
- `web`: captura del mecanico desde el modal web.
- `web_residente`: captura del residente desde Laravel.

El modelo `VehiculoEmpleadoKmLog` ya contiene estas constantes:

- `ORIGEN_APP`
- `ORIGEN_WEB_MECANICO`
- `ORIGEN_WEB_RESIDENTE`

### Contexto de obra

La captura Laravel se iniciara desde una obra y la ruta debe mantener la `obra_id` en el contexto. El backend validara que la asignacion del vehiculo corresponda a esa obra.

### Fuente del kilometraje

Los registros historicos seran la fuente de auditoria. El campo `km_final` de la asignacion sera solamente un resumen operativo.

La lectura vigente debera calcularse por asignacion, considerando la mayor lectura valida y conservando tambien la fecha de la ultima lectura.

## Tareas pequeñas y checkpoints

### 1. Levantar el flujo actual de la app

Estado: **completado**.

Checkpoint:

- Payload, validaciones, contexto, archivos y persistencia documentados.

### 2. Formalizar el origen del registro

Estado: **completado**.

Checkpoint:

- El modelo contiene los origenes.
- Los controladores existentes usan constantes.
- No se alteraron los valores historicos `app` y `web`.

### 3. Definir la ruta Laravel para el residente

Estado: **completado parcialmente**.

Se agrego la ruta:

```text
POST /obras/{obra}/vehiculos/{asignacion}/combustible
```

Nombre:

```text
obras.vehiculos.combustible.store
```

La ruta apunta a `ObraController@storeVehiculoCombustible` y conserva la obra y la asignacion en el contexto de la solicitud.

Pendiente para el paso 4:

- Agregar el permiso especifico.
- Conectar el middleware de permiso.
- Implementar el metodo de guardado.

Checkpoint:

- La ruta aparece en `php artisan route:list`.
- La ruta contiene la obra y la asignacion.
- Todavia no procesa datos hasta implementar el metodo del siguiente paso.

### 4. Definir el permiso del residente

Estado: **completado**.

Se creo el permiso:

```text
obras.vehiculos.combustible.create.access
```

El permiso se creo mediante la migracion `2026_10_15_000003_add_resident_vehicle_fuel_capture_permission.php` y quedo separado del permiso del mecanico `vehiculos.km_logs.create.access`.

La ruta `obras.vehiculos.combustible.store` ya utiliza este middleware.

El permiso no se asigno automaticamente a usuarios o roles; debe asignarse de forma explicita al residente correspondiente.

Checkpoint:

- El permiso existe en la base de datos.
- La ruta esta protegida por el permiso especifico.
- Un usuario sin permiso no puede invocar la ruta.

### 5. Validar obra y asignacion

Estado: **completado**.

Se preparo `ObraController::validarContextoCapturaCombustible()` para reutilizarlo desde el guardado. La validacion:

- Requiere usuario autenticado.
- Reutiliza `AppMobileContextService` para confirmar que el usuario tenga acceso a la obra; los administradores conservan acceso global.
- Confirma que la asignacion exista, corresponda al vehiculo recibido y este vigente por fechas.
- Confirma que el empleado de la asignacion este activo dentro de la obra.
- Rechaza una asignacion cerrada o perteneciente a otra obra.
- Devuelve la asignacion cargada con vehiculo y empleado para usarla al guardar.

Checkpoint:

- No se puede capturar gasolina para un vehiculo fuera de la obra.
- La obra y la asignacion no dependen solamente de valores enviados por el navegador.
- El metodo de guardado aun debe invocar esta validacion.

### 6. Crear formulario en el tab de vehiculos de la obra

Estado: **completado parcialmente**.

Se agrego al tab `vehiculos` un formulario protegido por `obras.vehiculos.combustible.create.access` con:

- Selector de vehiculo con asignacion vigente en la obra.
- Kilometraje.
- Fecha y hora, precargada con la fecha actual.
- Monto de gasolina opcional.
- Foto del odometro obligatoria.
- Foto del ticket opcional.
- Notas.

El formulario cambia su action segun la asignacion elegida y conserva la `obra_id` en la ruta. El backend todavia debe implementar el metodo de guardado para procesar estos campos.

Checkpoint:

- El residente puede ver el formulario cuando tiene el permiso.
- Solo puede seleccionar vehiculos activos de la obra.
- El boton permanece deshabilitado hasta seleccionar una asignacion.

### 7. Replicar las validaciones de la app

Estado: **completado parcialmente**.

Se preparo `ObraController::validarDatosCapturaCombustible()` para que el guardado reutilice las reglas del flujo movil:

- Kilometraje obligatorio, entero y mayor o igual a cero.
- No aceptar lecturas menores al mayor valor entre `km_inicial` y el ultimo log de la asignacion.
- Fecha obligatoria y valida.
- Foto del odometro obligatoria, en JPG/JPEG/PNG y hasta 5 MB.
- Ticket opcional con las mismas restricciones de imagen.
- Monto opcional, numerico y no negativo.
- Notas opcionales hasta 500 caracteres.

Checkpoint:

- Las reglas web coinciden con las reglas moviles.
- Un kilometraje menor genera un error de validacion en el campo `km`.
- El metodo de guardado aun debe invocar este validador y persistir los archivos.

### 8. Guardar archivos y registro

Estado: **completado**.

`ObraController::storeVehiculoCombustible()` ahora:

- Invoca la validacion de contexto y datos antes de guardar.
- Guarda la foto del odometro en `vehiculos/km-logs`.
- Guarda el ticket opcional en `vehiculos/gasolina-tickets`.
- Crea el registro con `obra_id`, `vehiculo_empleado_id`, fecha, kilometraje, monto, notas y usuario capturador.
- Guarda `origen = web_residente`.
- Actualiza `km_final` de la asignacion.
- Ejecuta persistencia dentro de una transaccion.
- Regresa al tab `vehiculos` con mensaje de confirmacion.

Checkpoint:

- El registro queda consultable desde obra y vehiculo.
- La captura conserva la trazabilidad de obra, asignacion, usuario y origen.

### 9. Mostrar captura en la obra

Agregar a la tabla del tab de vehiculos:

- Fecha.
- Vehiculo.
- Usuario.
- Kilometraje.
- Kilometros recorridos.
- Monto de gasolina.
- Evidencias.
- Origen.

Checkpoint:

- El registro aparece inmediatamente despues de guardarlo.

### 10. Separar origenes en el detalle del vehiculo

Mostrar distintivamente:

- App movil.
- Residente web.
- Mecanico web.

Checkpoint:

- La oficina puede saber quien genero cada lectura.

### 11. Agregar control de duplicados

Detectar registros sospechosos por:

- Misma asignacion.
- Misma fecha.
- Mismo kilometraje.
- Mismo monto.

Primero se recomienda advertir antes de bloquear.

Checkpoint:

- Se reducen duplicados sin impedir correcciones operativas.

### 12. Preparar litros de combustible

El MVP actual conserva `monto_gasolina`, pero para calcular rendimiento se debera agregar posteriormente:

- `litros_gasolina`.
- Tipo de combustible.
- Precio por litro.
- Relacion con salida de inventario cuando aplique.

Checkpoint:

- Queda claro que el MVP puede calcular costo por kilometro, pero no `km/litro` sin litros.

### 13. Preparar resumen de rendimiento

- Definir lectura inicial y final por periodo.
- Sumar litros o monto por periodo.
- Relacionar cargas con kilometros recorridos.
- Marcar inconsistencias entre app y web.

Checkpoint:

- El resumen no modifica los registros originales.

### 14. Pruebas funcionales

- Captura valida desde una obra.
- Vehiculo asignado correctamente.
- Vehiculo fuera de la obra.
- Usuario sin permiso.
- Kilometraje menor al anterior.
- Sin ticket.
- Sin foto del odometro.
- Registro duplicado.
- Consulta desde obra.
- Consulta desde vehiculo.

## Cambios realizados

- Se agregaron constantes de origen en `VehiculoEmpleadoKmLog`.
- El API movil usa `ORIGEN_APP`.
- El modal web del mecanico usa `ORIGEN_WEB_MECANICO`.
- Se definio `ORIGEN_WEB_RESIDENTE` para la futura captura Laravel.
- Se documento que la foto del odometro es obligatoria para la app y para el futuro flujo residente, aunque el flujo del mecanico web actualmente permite omitirla.

## Notas de diseño

- No usar el valor mayor como sustituto de la bitacora; usarlo solo para calcular el kilometraje vigente.
- No eliminar ni corregir silenciosamente lecturas anteriores.
- Cada lectura debe conservar origen, usuario, fecha, obra y asignacion.
- La grafica de rendimiento debe construirse sobre datos derivados, no sobre valores sobrescritos.