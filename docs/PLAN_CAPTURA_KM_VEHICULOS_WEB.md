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