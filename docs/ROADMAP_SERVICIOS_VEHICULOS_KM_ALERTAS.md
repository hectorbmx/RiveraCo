# Roadmap: servicios preventivos de vehiculos por KM y alertas

Fecha: 2026-07-28

## Estado actual

Backend/web cerrado para ligar capturas moviles de vehiculo a obra.

Regla confirmada:

- El vehiculo no se asigna directamente a una obra.
- El vehiculo se asigna a un usuario/empleado mediante `vehiculo_empleado`.
- El usuario/empleado se liga a una obra mediante `obra_empleado`.
- Cada captura movil debe guardar la `obra_id` vigente en ese momento para poder medir consumo por obra.

## Ajuste completado: registros moviles por obra

Se agregaron campos a `vehiculo_empleado_km_logs`:

- `obra_id`
- `foto_ticket_gasolina`
- `monto_gasolina`

La API movil de kilometraje queda como fuente principal:

- `GET api/v1/vehiculos/km-log`
- `POST api/v1/vehiculos/km-log`

Contrato actualizado para `POST api/v1/vehiculos/km-log`:

- `km`: requerido, entero.
- `foto`: requerido, imagen del odometro.
- `notas`: opcional.
- `foto_ticket_gasolina`: opcional, imagen del ticket de gasolina.
- `monto_gasolina`: opcional, monto cargado en dinero.

Comportamiento esperado:

- La API resuelve la obra activa desde `usuario_app -> empleado -> obra_empleado -> obra`.
- Si no hay vehiculo activo asignado al empleado, responde error.
- Si no hay obra activa asignada al empleado, no permite registrar kilometraje.
- En `GET`, si no hay obra activa, devuelve `data: []` para evitar mostrar historial viejo de otra obra.
- El tab web de obra `?tab=vehiculos` consulta por `vehiculo_empleado_km_logs.obra_id`, no por `vehiculo_obra`.

Validacion local realizada:

- `php artisan migrate`
- `php -l` en controlador API, controlador de obra y modelo.
- `php artisan view:cache`
- Registro local `17990 km` quedo ligado a `obra_id = 5 / POZOS TEST`.

## App movil: captura de gasolina

Completado en `C:\xampp\htdocs\rivera\app\src\app\pages\vehiculo-registro`:

- Foto del ticket de gasolina en el modal de nuevo registro.
- Monto cargado en dinero como numero decimal opcional.
- Envio al endpoint actual `POST api/v1/vehiculos/km-log` usando multipart:
  - `foto_ticket_gasolina` como archivo.
  - `monto_gasolina` como decimal.
- Historial muestra monto y enlace al ticket cuando la API lo devuelve.

La app movil sigue mostrando solo los registros de la obra activa que devuelve la API.

## Objetivo funcional

Detectar cuando un vehiculo este proximo o vencido para servicio preventivo segun su kilometraje actual, programar el siguiente mantenimiento y notificar por correo a N destinatarios configurables.

## Decision inicial

Empezar por servicios por KM.

Motivo: el dato de kilometraje ya se captura en campo desde la app movil, trae evidencia fotografica y ahora queda ligado a obra. La regla por tiempo puede quedar como segunda etapa o como respaldo cuando un vehiculo no tenga capturas recientes.

## Fase 1: Configuracion persistente

Estado: completada en backend/web.

Agregar campos reales a `empresa_config`:

- `vehiculo_servicio_km`: intervalo de servicio, por ejemplo 5000 km.
- `vehiculo_servicio_meses`: respaldo por tiempo, por ejemplo 6 meses.
- `vehiculo_alerta_km`: margen de alerta antes del servicio, por ejemplo 500 km.
- `vehiculo_alerta_dias`: margen por tiempo, por ejemplo 10 dias.
- `vehiculo_alertas_activas`: boolean para encender/apagar alertas.

Actualizado:

- `EmpresaConfig::$fillable` y casts.
- `EmpresaConfigController@update`, seccion `vehiculos`.
- `resources/views/empresa_config/edit.blade.php`, tab `vehiculos`, usando `old(..., $config->...)`.
- Migracion `2026_07_28_130000_add_vehiculo_preventivo_fields_to_empresa_config.php`.

## Fase 2: Destinatarios configurables

Estado: completada para vehiculos.

Se creo tabla reutilizable:

`empresa_alerta_destinatarios`

Campos implementados:

- `empresa_config_id`
- `modulo`: `vehiculos`, `maquinaria`, etc.
- `user_id` nullable para notificacion interna.
- `email` nullable para correo interno/externo.
- `nombre` nullable.
- `notificar_correo` boolean.
- `notificar_sistema` boolean.
- `activo` boolean.

En la pestana `Vehiculos` de `configuracion-empresa` ya se puede seleccionar N usuarios o capturar emails externos. La misma lista servira para correo y campana/notificacion interna.

## Fase 3: Servicio de calculo preventivo por KM

Estado: completada en backend.

Servicio creado siguiendo el patron de `PreventivoMaquinaService`:

`App\Services\Vehiculos\PreventivoVehiculoService`

Entrada:

- coleccion de vehiculos
- config de empresa

Datos base:

- km actual: ultimo `VehiculoEmpleadoKmLog` por vehiculo, cruzando `vehiculo_empleado`.
- ultimo servicio completado: ultimo `Mantenimiento` con `vehiculo_id`, `tipo = programado`, `estatus = completado`, y `km_actuales` no null.
- fallback: `km_final` o `km_inicial` de ultima asignacion `vehiculo_empleado`.

Salida por vehiculo:

- `estado`: `sin_datos`, `ok`, `proximo`, `vencido`
- `km_actual`
- `km_ultimo_servicio`
- `km_usados`
- `km_restantes`
- `km_proximo_servicio`
- `porcentaje`
- `ultimo_servicio_fecha`
- `proximo_fecha` si se usa regla de meses


Validacion de PreventivoVehiculoService:

- `php -l app/Services/Vehiculos/PreventivoVehiculoService.php`
- `php -l app/Http/Controllers/VehiculoController.php`
- `php artisan view:cache`
- Prueba local vehiculo 14: estado `ok`, km actual `17990`, proximo servicio `22500`, restan `4510 km`.
## Fase 4: Integracion en UI de vehiculos

Estado: iniciada. Tarjeta preventiva agregada al tab de mantenimientos del vehiculo.

En `VehiculoController@edit`, tab `mantenimientos`:

- mostrar tarjeta de estado preventivo. Completado en `VehiculoController@edit`, tab `mantenimientos`.
- mostrar km actual obtenido desde capturas moviles. Completado en tarjeta preventiva.
- mostrar proximo km de servicio. Completado en tarjeta preventiva.
- boton para programar mantenimiento con `km_actuales` y `km_proximo_servicio` prellenados. Completado.

En `vehiculos.index`:

- agregar badge de estado preventivo para escanear la flotilla. Completado en `vehiculos.index`.

En `obras.edit?tab=vehiculos`:

- mantener resumen por obra.
- sumar `monto_gasolina` por vehiculo/asignacion.
- mostrar evidencia de odometro y ticket.


Validacion de badges preventivos:

- `php -l app/Http/Controllers/VehiculoController.php`
- `php artisan view:cache`
- Prueba local de coleccion con vehiculo 14: estado `ok`, label `Restan 4,510 km`.
## Fase 5: Generacion de alertas

Crear comando programado:

`php artisan vehiculos:alertas-preventivo-km`

Responsabilidades:

- calcular estado de todos los vehiculos activos.
- detectar `proximo` o `vencido`.
- evitar spam con una tabla de bitacora, por ejemplo `vehiculo_alerta_logs`.
- enviar correo a los destinatarios activos configurados.\n- enviar notificacion interna a los destinatarios con `user_id` y `notificar_sistema` activo.

Tabla sugerida `vehiculo_alerta_logs`:

- `vehiculo_id`
- `tipo_alerta`
- `estado`
- `km_actual`
- `km_proximo_servicio`
- `sent_at`
- `hash_contexto` para no repetir la misma alerta


## Cuenta de envio de correos

Para alertas de vehiculos se usara Microsoft Graph separado de facturacion:

- Facturacion: conservar la cuenta configurada para CFDI/complementos.
- Alertas preventivas: enviar desde `administracion@riveraco.com.mx`.

Config lista en `services.alertas_mail`:

- `ALERTAS_MAIL_PROVIDER=graph`
- `ALERTAS_MAIL_FROM_ADDRESS=administracion@riveraco.com.mx`
- `ALERTAS_GRAPH_USER=administracion@riveraco.com.mx`

El servicio `MicrosoftGraphMailService` ahora tiene `sendHtml(...)` para correos no SAT y permite pasar el usuario Graph emisor sin afectar `sendSatFactura(...)`.
## Fase 6: Correos

Crear notification/mail:

`VehiculoServicioPreventivoNotification`\n\nEstado: clase creada con canales `mail` y `database`.

Contenido minimo:

- vehiculo: marca/modelo/placas.
- km actual.
- proximo km de servicio.
- estado: proximo o vencido.
- ultima captura con fecha y liga a imagen si existe.
- liga al vehiculo en SIRICO.

## Fase 7: Reglas de programacion

Primera version:

- Si no hay servicio previo completado: usar km actual como base y sugerir configurar primer servicio.
- Si hay servicio previo: `km_proximo_servicio = ultimo_servicio.km_actuales + vehiculo_servicio_km`.
- Alerta proxima: `km_restantes <= vehiculo_alerta_km`.
- Vencido: `km_actual >= km_proximo_servicio`.

Segunda version:

- permitir intervalo por vehiculo o por tipo de vehiculo.
- permitir combinar KM + tiempo: vence por el criterio que ocurra primero.

## Riesgos / detalles a cuidar

- Validar que las capturas moviles no bajen el kilometraje respecto al ultimo registro; la API ya tiene esta proteccion por asignacion activa.
- Si cambia el empleado asignado al vehiculo, el calculo preventivo debe usar logs del vehiculo completo, no solo de la asignacion actual.
- Las imagenes estan en disco `public`; confirmar que `storage:link` este vigente en produccion.
- Las alertas deben tener deduplicacion para no mandar correos repetidos todos los dias por el mismo evento.
- Si un empleado llegara a tener mas de una obra activa, la API hoy toma la asignacion activa mas reciente. Si el negocio permite multiples obras simultaneas, la app movil debera enviar `obra_id` de forma explicita.

## Primer corte recomendado

1. Conectar los dos nuevos inputs en la app movil. Completado.
2. Persistir configuracion de vehiculos en `empresa_config`. Completado.
3. Crear `PreventivoVehiculoService` por KM. Completado.
4. Mostrar estado preventivo en vehiculo y listado.
5. Crear destinatarios configurables en tab `Vehiculos`. Completado.
6. Crear comando de alertas por correo + notificacion interna con deduplicacion. Completado.

## Comando de alertas preventivas

Completado:

- php artisan vehiculos:alertas-preventivo-km evalua vehiculos activos.
- --dry-run calcula sin enviar ni registrar bitacora.
- --force reenvia una alerta ya registrada y actualiza la bitacora.
- Deduplicacion en ehiculo_alerta_logs por ehiculo_id, 	ipo_alerta y hash_contexto.
- Correos por Graph usando services.alertas_mail / dministracion@riveraco.com.mx.
- Notificacion interna por Laravel Notifications database.
- Scheduler diario registrado a las 08:15.
