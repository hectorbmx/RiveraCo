# Ajuste de notificaciones

Fecha de barrido: 2026-09-04

## Objetivo

Definir una estrategia clara para decidir que rol o usuario recibe cada notificacion del sistema, evitando reglas dispersas en controladores y servicios.

## Estado actual

El sistema usa notificaciones internas de Laravel por canal `database`. Las notificaciones se muestran en la campana del layout y en el centro de notificaciones.

Archivos principales:

- `resources/views/layouts/admin.blade.php`
- `resources/views/notifications/index.blade.php`
- `app/Http/Controllers/NotificationController.php`
- `app/Http/Controllers/Api/Agent/AgentNotificationController.php`

Rutas principales:

- `/notificaciones`
- `/notificaciones/{id}/read`
- `/notificaciones/unread-json`
- API agente: `notifications/unread`, `notifications/{id}/open-link`, `notifications/{id}/read`

## Clases de notificacion encontradas

- `app/Notifications/OrdenCompraFlujoNotification.php`
- `app/Notifications/OrdenCompraCreada.php`
- `app/Notifications/FacturaBorradorCreado.php`
- `app/Notifications/FacturaBorradorAutorizado.php`
- `app/Notifications/FacturaBorradorListoParaFacturar.php`
- `app/Notifications/FacturaBorradorRechazado.php`
- `app/Notifications/SolicitudGastoCreada.php`
- `app/Notifications/SeguroVehiculoVencimiento.php`
- `app/Notifications/VehiculoServicioPreventivoNotification.php`

Nota: `OrdenCompraCreada.php` existe, pero el flujo nuevo usa principalmente `OrdenCompraFlujoNotification` mediante `OrdenCompraNotificationService`.

## Matriz actual de destinatarios

| Evento | Archivo emisor | Destinatarios actuales | Observacion |
| --- | --- | --- | --- |
| Orden de compra creada | `app/Services/OrdenCompraNotificationService.php` | Usuarios con permiso `ordenes_compra.authorize.access`; fallback a `ordenes_compra.autorizar` | En BD local: `admin-rivera`, `super-admin` |
| Orden de compra autorizada | `app/Services/OrdenCompraNotificationService.php` | Creador de la OC y usuarios con permiso `pagos_proveedores.schedule.access` | En BD local el permiso lo tienen `admin-rivera`, `secretaria`, `super-admin` |
| Pago de proveedor programado | `app/Services/OrdenCompraNotificationService.php` | Creador de la OC, autorizador de la OC y usuario que programa el pago | Depende de participantes directos, no de rol |
| Borrador de factura creado | `app/Http/Controllers/ObraController.php` | Usuarios con permiso `obra_factura_borradores.authorize.access` | En BD local: `admin-rivera`, `super-admin` |
| Borrador de factura autorizado | `app/Http/Controllers/ObraController.php` | Creador del borrador | Correcto como notificacion de seguimiento |
| Borrador listo para facturar | `app/Http/Controllers/ObraController.php` | Usuarios con permiso `obra_factura_borradores.invoice.access`, excluyendo al creador | En BD local: `admin-rivera`, `residente`, `secretaria`, `super-admin`. Revisar si `residente` debe recibir avisos para facturar |
| Borrador rechazado | `app/Http/Controllers/ObraController.php` | Creador del borrador | Correcto como notificacion de accion tomada |
| Solicitud de gasto creada | `app/Http/Controllers/ObraSolicitudGastoController.php` | `User::role('administrador')` | Riesgo: en BD local no existe rol `administrador`, por lo que podria no enviarse |
| Seguro de vehiculo por vencer | `app/Console/Commands/CheckInsuranceExpirations.php` | `User::role('administrador')` | Mismo riesgo: rol no encontrado en BD local |
| Servicio preventivo por KM | `app/Console/Commands/VehiculosAlertasPreventivoKm.php` | Configuracion `empresa_alerta_destinatarios` modulo `vehiculos` | En BD local no hay destinatarios configurados |

## Roles encontrados en BD local

| Rol | Usuarios |
| --- | ---: |
| `admin-rivera` | 4 |
| `Almacen` | 0 |
| `consulta` | 0 |
| `jefe-obra` | 0 |
| `residente` | 3 |
| `secretaria` | 0 |
| `super-admin` | 1 |
| `supervisor-obra` | 0 |

## Permisos relevantes en BD local

| Permiso | Roles con permiso |
| --- | --- |
| `ordenes_compra.authorize.access` | `admin-rivera`, `super-admin` |
| `ordenes_compra.autorizar` | `admin-rivera`, `super-admin` |
| `pagos_proveedores.schedule.access` | `admin-rivera`, `secretaria`, `super-admin` |
| `obra_factura_borradores.authorize.access` | `admin-rivera`, `super-admin` |
| `obra_factura_borradores.invoice.access` | `admin-rivera`, `residente`, `secretaria`, `super-admin` |

## Hallazgos importantes

1. Las reglas de destinatarios estan dispersas.

   Algunas viven en servicios, otras en controladores y otras en comandos programados. Esto hace dificil responder con seguridad que rol recibe cada aviso.

2. Hay usos de rol literal `administrador`.

   Se encontro en:

   - `app/Http/Controllers/ObraSolicitudGastoController.php`
   - `app/Console/Commands/CheckInsuranceExpirations.php`

   En la BD local no existe el rol `administrador`. Si produccion tiene la misma estructura, esas notificaciones quedan sin destinatarios.

3. Algunos flujos ya usan permisos, que es mejor que roles directos.

   Ordenes de compra y borradores de factura usan permisos como `ordenes_compra.authorize.access` u `obra_factura_borradores.invoice.access`. Esto es mas flexible porque el rol puede cambiar sin tocar codigo.

4. Vehiculos tiene un modelo configurable aparte.

   El comando `vehiculos:alertas-preventivo-km` usa `empresa_alerta_destinatarios` para decidir correo y notificacion interna. Es un patron util para alertas recurrentes/configurables.

5. La UI de notificaciones no decide destinatarios.

   El centro de notificaciones solo lista y marca como leidas. La decision de quien recibe cada notificacion ocurre antes, cuando se emite el evento.

## Propuesta de arquitectura

Crear una capa central para resolver destinatarios, por ejemplo:

- `app/Services/Notifications/NotificationAudienceResolver.php`
- `config/notification_audiences.php`

La idea seria que cada flujo pida destinatarios por clave de evento:

```php
$audienceResolver->for('orden_compra.creada', context: ['orden' => $orden]);
$audienceResolver->for('factura_borrador.creado', context: ['borrador' => $borrador]);
$audienceResolver->for('seguro.vehiculo_por_vencer', context: ['seguro' => $seguro]);
```

El resolver podria soportar:

- Destinatarios por permiso.
- Destinatarios por rol.
- Destinatarios por usuario participante.
- Destinatarios configurables por modulo.
- Exclusiones, por ejemplo excluir al usuario creador.
- Deduplicacion por usuario.

## Propuesta inicial de matriz objetivo

| Evento | Destinatarios propuestos | Criterio |
| --- | --- | --- |
| `orden_compra.creada` | Usuarios con `ordenes_compra.authorize.access` | Quien puede autorizar debe recibir aviso |
| `orden_compra.autorizada` | Creador de la OC y usuarios con `pagos_proveedores.schedule.access` | Seguimiento al creador y siguiente paso a pagos |
| `orden_compra.pago_programado` | Creador, autorizador y programador | Aviso a participantes directos |
| `factura_borrador.creado` | Usuarios con `obra_factura_borradores.authorize.access` | Quien revisa/autorizan borradores |
| `factura_borrador.autorizado` | Creador del borrador | Seguimiento al solicitante |
| `factura_borrador.listo_para_facturar` | Usuarios con `obra_factura_borradores.invoice.access` | Quien puede emitir factura |
| `factura_borrador.rechazado` | Creador del borrador | Correccion por parte del solicitante |
| `solicitud_gasto.creada` | Usuarios con permiso a definir, por ejemplo `obra_solicitudes_gasto.review.access` | Evitar rol hardcodeado `administrador` |
| `seguro.vencimiento` | Destinatarios configurables modulo `vehiculos` o permiso a definir | Evitar rol hardcodeado `administrador` |
| `vehiculo.preventivo_km` | Destinatarios configurables modulo `vehiculos` | Mantener patron existente configurable |

## Checkpoints sugeridos

- [ ] Definir nombres canonicos de eventos de notificacion.
- [ ] Crear `NotificationAudienceResolver`.
- [ ] Crear `config/notification_audiences.php` con reglas por evento.
- [ ] Migrar `OrdenCompraNotificationService` para usar el resolver.
- [ ] Migrar notificaciones de borradores de factura.
- [ ] Reemplazar `User::role('administrador')` en solicitudes de gasto y seguros.
- [ ] Definir si `residente` debe conservar `obra_factura_borradores.invoice.access` o si necesita otro permiso separado.
- [ ] Definir destinatarios configurables para modulo `vehiculos` en produccion.
- [ ] Agregar pruebas unitarias del resolver para deduplicacion, permisos, roles y participantes.
- [ ] Agregar una vista administrativa futura para editar audiencia por evento si se requiere.

## Primer ajuste recomendado

Antes de construir toda la capa central, el ajuste urgente es corregir las notificaciones que usan `User::role('administrador')`, porque actualmente dependen de un rol que no existe en la BD local.

Opciones:

1. Cambiar temporalmente a roles reales: `admin-rivera` y `super-admin`.
2. Mejor: crear permisos especificos y enviar por permiso.
3. Mejor aun: implementar el resolver y que esas reglas ya salgan desde configuracion.

Recomendacion: avanzar con la opcion 3 si vamos a hacer ajuste fino completo; opcion 2 si necesitamos corregir rapido sin abrir todo el refactor.
