# Roadmap: Notificaciones de Ordenes de Compra

Objetivo: cerrar el ciclo de notificaciones de orden de compra tanto en notificaciones internas de la app como en el agente instalable de escritorio.

## Flujo Objetivo

| Evento | Destinatario | Canal |
| --- | --- | --- |
| Creacion de orden de compra | Autorizador | In-app + agente |
| Orden autorizada | Creador | In-app + agente |
| Orden autorizada/lista para pago | Responsable de programar pagos | In-app + agente |
| Pago programado | Involucrados del ciclo | In-app + agente |

Datos minimos por notificacion:

- Folio o identificador de la orden de compra.
- Proveedor.
- Obra, si aplica.
- Monto.
- Usuario que dispara el evento.
- URL directa a la vista de accion o detalle.

## Fase 1: Auditoria del Flujo Actual

- [x] Identificar modelo principal de orden de compra.
- [x] Identificar controlador(es) de creacion, autorizacion y programacion de pago.
- [x] Identificar rutas usadas por el modulo de ordenes de compra.
- [x] Documentar estados actuales de la orden de compra.
- [x] Revisar si ya existen eventos, observers o servicios asociados al flujo.
- [x] Revisar si ya existen notificaciones internas para ordenes de compra.
- [x] Revisar endpoint que consume el agente instalable.
- [x] Confirmar si el agente consume todas las notificaciones o filtra por tipo/categoria.

Hallazgos:

- Modelo principal: `app/Models/OrdenCompra.php`.
- Modelo de pagos ligados a OC: `app/Models/PagoProveedor.php`.
- Controlador de ordenes de compra: `app/Http/Controllers/OrdenCompraController.php`.
- Controlador de programacion/ejecucion de pagos: `app/Http/Controllers/PagoProveedorController.php`.
- Rutas OC:
  - `GET/POST/PUT ordenes_compra` via resource, excepto `show` y `destroy`.
  - `POST ordenes_compra/{id}/autorizar` con nombre `ordenes_compra.autorizar`.
  - `POST ordenes_compra/{id}/cancelar` con nombre `ordenes_compra.cancelar`.
  - `GET ordenes_compra/{orden_compra}/print` con nombre `ordenes_compra.print`.
- Rutas pagos proveedor:
  - `GET pagos-proveedores` con nombre `pagos-proveedores.index`.
  - `GET pagos-proveedores/create` con nombre `pagos-proveedores.create`.
  - `POST pagos-proveedores` con nombre `pagos-proveedores.store`.
  - `PATCH pagos-proveedores/{pago}/autorizar` con nombre `pagos-proveedores.autorizar`.
  - `PATCH pagos-proveedores/{pago}/pagar` con nombre `pagos-proveedores.pagar`.
  - `PATCH pagos-proveedores/{pago}/cancelar` con nombre `pagos-proveedores.cancelar`.
- Estados de OC:
  - Valor legacy inicial: `BORRADOR`.
  - Normalizado como `programada` por `OrdenCompra::estado_normalizado`.
  - Autorizada: `AUTORIZADA`.
  - Cancelada: `CANCELADA`.
- Estados de pago proveedor:
  - `programado`.
  - `autorizado`.
  - `pagado`.
  - `cancelado`.
- Creacion de OC:
  - Metodo: `OrdenCompraController::store`.
  - Crea OC con `estado = BORRADOR`.
  - Guarda creador en texto: `usuario_registro = usuarioActualNombre()`.
  - No dispara notificacion actualmente.
- Autorizacion de OC:
  - Metodo: `OrdenCompraController::autorizar`.
  - Permiso actual usado: `ordenes_compra.autorizar`.
  - Cambia `estado = AUTORIZADA`.
  - Guarda `fecha_autorizacion` y `usuario_autoriza` como texto.
  - No dispara notificacion actualmente.
- Programacion de pago:
  - Metodo: `PagoProveedorController::store`.
  - Solo permite OC con `estado = AUTORIZADA`.
  - Crea `PagoProveedor` con `estatus = programado`.
  - Guarda usuario real por id en `programado_by`.
  - No dispara notificacion actualmente.
- Autorizacion/ejecucion de pago:
  - `PagoProveedorController::autorizar` cambia `estatus = autorizado`.
  - `PagoProveedorController::pagar` cambia `estatus = pagado`.
  - Estas acciones tampoco disparan notificaciones actualmente.
- Notificacion OC existente:
  - Archivo: `app/Notifications/OrdenCompraCreada.php`.
  - Canal: `database`.
  - Tipo: `orden_compra`.
  - Incluye folio, obra, proveedor, total, usuario_registro y mensaje.
  - No incluye `url`.
  - No se encontro uso activo de esta notificacion en el flujo.
- Sistema in-app:
  - Usa tabla `notifications`, creada en `database/migrations/2026_06_17_173101_create_notifications_table.php`.
  - Controlador web: `app/Http/Controllers/NotificationController.php`.
  - Rutas web: prefijo `notificaciones`.
  - Campana en `resources/views/layouts/admin.blade.php`.
  - Centro de notificaciones en `resources/views/notifications/index.blade.php`.
  - La UI ya reconoce `tipo = orden_compra`.
- Agente instalable:
  - Rutas API en `routes/api.php` bajo prefijo `agent`.
  - `GET agent/notifications/unread` usa `AgentNotificationController::unread`.
  - `POST agent/notifications/{id}/read` usa `AgentNotificationController::markRead`.
  - El agente no filtra por tipo/categoria; consume todas las notificaciones no leidas del usuario.
  - Espera `title`/`titulo`, `message`/`mensaje`, `url`, `icon`, `priority`, `created_at`.

Checkpoint de cierre:

- [x] Tenemos claro donde se crea la OC: `OrdenCompraController::store`.
- [x] Tenemos claro donde se autoriza: `OrdenCompraController::autorizar`.
- [x] Tenemos claro donde se programa el pago: `PagoProveedorController::store`.
- [x] Tenemos claro como se guardan y leen las notificaciones in-app: Laravel database notifications + `NotificationController`.
- [x] Tenemos claro como el agente recibe notificaciones: `AgentNotificationController::unread`, sin filtro por tipo.

Pendientes derivados de la auditoria:

- [ ] Definir destinatarios reales por permiso/rol para autorizadores de OC.
- [ ] Definir destinatarios reales para responsables de programar pagos.
- [ ] Crear notificaciones especificas para cada evento o extender la existente con `evento`, `url`, `title`, `message`, `icon` y `priority`.
- [ ] Insertar disparadores en `OrdenCompraController::store`, `OrdenCompraController::autorizar` y `PagoProveedorController::store`.
- [ ] Evaluar si tambien notificaremos en `PagoProveedorController::autorizar` o solo en `store` y `pagar`, segun el ciclo final deseado.

## Fase 2: Matriz de Roles, Permisos y Destinatarios

- [x] Confirmar que permiso o rol puede autorizar ordenes de compra.
- [x] Confirmar que permiso o rol puede programar pagos.
- [x] Confirmar como detectar al creador de la orden.
- [x] Confirmar como detectar involucrados del ciclo.
- [x] Definir si los destinatarios se buscan por rol, permiso `.access`, usuario asignado o relacion directa.
- [x] Crear permisos faltantes siguiendo el patron `.access`, si aplica.
- [x] Asignar permisos faltantes a roles reales.

Hallazgos de permisos/roles locales:

- Permisos existentes relacionados:
  - `ordenes de compra.access`.
  - `ordenes_compra.autorizar`.
  - `ordenes_compra.imprimir`.
  - `programacion_pagos.access`.
  - `programacion_pagos.revisar.access`.
  - `programacion_pagos.autorizar.access`.
  - `proveedores.access`.
- Roles con permisos de OC/pagos/proveedores:
  - `super-admin`: tiene `ordenes_compra.autorizar`, `ordenes_compra.imprimir`, `ordenes de compra.access`, `programacion_pagos.access`, `programacion_pagos.revisar.access`, `programacion_pagos.autorizar.access`, `proveedores.access`.
  - `admin-rivera`: tiene `ordenes_compra.autorizar`, `ordenes_compra.imprimir`, `ordenes de compra.access`.
  - `secretaria`: tiene `ordenes_compra.imprimir`, `ordenes de compra.access`.
  - Otros roles revisados sin permisos relevantes locales: `Almacen`, `consulta`, `jefe-obra`, `residente`, `supervisor-obra`.
- El patron nuevo deseado es usar permisos con sufijo `.access`, pero OC aun usa permisos legacy sin `.access`:
  - `ordenes_compra.autorizar`.
  - `ordenes_compra.imprimir`.
- `pagos-proveedores` solo esta protegido por `auth`; no hay middleware ni `can()` en controlador/vistas para programar, autorizar, pagar o cancelar.
- En vistas:
  - Boton autorizar OC usa `@can('ordenes_compra.autorizar')`.
  - Boton imprimir OC usa `@can('ordenes_compra.imprimir')`.
  - Boton pagar OC aparece si la OC esta autorizada y sin pago activo, sin validacion de permiso especifico.
  - Acciones de pagos proveedor aparecen por estatus, sin validacion de permiso especifico.

Matriz propuesta de destinatarios:

| Evento | Destinatario recomendado | Regla tecnica propuesta | Estado actual |
| --- | --- | --- | --- |
| OC creada | Autorizadores de OC | Usuarios con `ordenes_compra.authorize.access` cuando exista; compatibilidad temporal con `ordenes_compra.autorizar` | Existe solo permiso legacy `ordenes_compra.autorizar` |
| OC autorizada | Creador de la OC | Usuario guardado por id en nuevo campo `registrado_por`; fallback temporal por `usuario_registro` | Hoy solo existe `usuario_registro` texto |
| OC autorizada/lista para pago | Responsable de programar pagos | Usuarios con `pagos_proveedores.schedule.access` o `programacion_pagos.access` segun se decida | No hay permiso especifico para `pagos-proveedores`; existe `programacion_pagos.access` |
| Pago programado | Involucrados del ciclo | Creador de OC + autorizador + programador del pago | Creador/autorizador estan en texto; programador si esta en `programado_by` |

Permisos recomendados para homologar con `.access`:

- `ordenes_compra.view.access`.
- `ordenes_compra.create.access`.
- `ordenes_compra.edit.access`.
- `ordenes_compra.print.access`.
- `ordenes_compra.authorize.access`.
- `ordenes_compra.cancel.access`.
- `pagos_proveedores.view.access`.
- `pagos_proveedores.schedule.access`.
- `pagos_proveedores.authorize.access`.
- `pagos_proveedores.pay.access`.
- `pagos_proveedores.cancel.access`.

Asignacion inicial recomendada:

- `super-admin`: todos los permisos nuevos.
- `admin-rivera`: autorizar OC, imprimir OC, ver OC, programar pagos y ver pagos proveedor si administracion lo requiere.
- `secretaria`: ver/imprimir OC y programar pagos proveedor si sera el rol operativo de pagos.
- Mantener permisos legacy durante una transicion para no romper botones existentes:
  - `ordenes_compra.autorizar` junto a `ordenes_compra.authorize.access`.
  - `ordenes_compra.imprimir` junto a `ordenes_compra.print.access`.

Recomendacion tecnica antes de implementar notificaciones:

- Agregar campos de usuario reales a `ordenes_compra` para dejar de depender de texto:
  - `registrado_por` nullable FK a `users.id`.
  - `autorizado_por` nullable FK a `users.id`.
- Seguir llenando `usuario_registro` y `usuario_autoriza` por compatibilidad visual/reportes legacy.
- Si no se quiere migracion ahora, usar fallback por `User::where('name', $oc->usuario_registro)->orWhere('email', $oc->usuario_registro)`, pero queda marcado como riesgo.

Checkpoint de cierre:

- [x] Hay una regla clara para encontrar autorizadores: permiso legacy `ordenes_compra.autorizar`; objetivo nuevo `ordenes_compra.authorize.access`.
- [x] Hay una regla clara para encontrar responsables de programar pago: pendiente crear/usar permiso especifico; recomendacion `pagos_proveedores.schedule.access` o decidir reutilizar `programacion_pagos.access`.
- [x] Hay una regla clara para notificar al creador: ideal por nuevo FK `registrado_por`; fallback temporal por `usuario_registro` texto.
- [x] Hay una regla clara para notificar a involucrados al cerrar ciclo: creador + autorizador + programador del pago, con deduplicacion por usuario id.

Implementado en esta fase:

- Migracion: `database/migrations/2026_07_03_090000_add_user_fields_and_access_permissions_to_purchase_flow.php`.
- Campos agregados a `ordenes_compra`:
  - `registrado_por` FK nullable a `users.id`.
  - `autorizado_por` FK nullable a `users.id`.
- `OrdenCompraController` ahora guarda `registrado_por` al crear y `autorizado_por` al autorizar.
- `OrdenCompraController` acepta permisos nuevos `.access` con compatibilidad legacy para autorizar/imprimir.
- `PagoProveedorController` protege index, crear, programar, autorizar, pagar y cancelar con permisos `pagos_proveedores.*.access`.
- Vistas de OC y pagos proveedor ya ocultan acciones segun permisos nuevos.
- Permisos nuevos asignados inicialmente:
  - `super-admin`: todos los permisos nuevos.
  - `admin-rivera`: permisos de OC + ver/programar pagos proveedor.
  - `secretaria`: ver/crear/editar/imprimir OC + ver/programar pagos proveedor.

Pendientes derivados de Fase 2:

- [ ] Decidir si `programacion_pagos.access` tambien gobierna `pagos-proveedores` o si se crean permisos dedicados `pagos_proveedores.*.access`.
- [x] Crear permisos nuevos `.access`.
- [x] Asignar permisos nuevos a roles.
- [x] Migrar OC para guardar `registrado_por` y `autorizado_por` como FK a usuarios.
- [x] Actualizar vistas/controladores para aceptar permisos nuevos y mantener compatibilidad legacy durante transicion.

## Fase 3: Implementacion de Notificaciones In-App

- [x] Crear o reutilizar un servicio helper para generar notificaciones de orden de compra.
- [x] Notificar al autorizador cuando se crea una orden de compra.
- [x] Notificar al creador cuando la orden se autoriza.
- [x] Notificar al responsable de programar pagos cuando la orden queda autorizada.
- [x] Notificar a involucrados cuando el pago queda programado.
- [x] Agregar titulos y mensajes consistentes.
- [x] Agregar URL destino correcta en cada notificacion.
- [x] Evitar duplicados basicos por destinatario en el mismo evento.

Implementado en esta fase:

- Nueva notificacion: `app/Notifications/OrdenCompraFlujoNotification.php`.
- Nuevo servicio: `app/Services/OrdenCompraNotificationService.php`.
- `OrdenCompraController::store` dispara evento `creada` para usuarios con `ordenes_compra.authorize.access`; mantiene fallback legacy a `ordenes_compra.autorizar` si no hay destinatarios.
- `OrdenCompraController::autorizar` dispara:
  - evento `autorizada` al creador de la OC;
  - evento `lista_pago` a usuarios con `pagos_proveedores.schedule.access`.
- `PagoProveedorController::store` dispara evento `pago_programado` al creador, autorizador y programador del pago, deduplicando por usuario.
- Payload homologado para app y agente:
  - `tipo = orden_compra`.
  - `evento = creada|autorizada|lista_pago|pago_programado`.
  - `title/titulo`, `message/mensaje`, `url`, `icon`, `priority`.
  - folio, proveedor, obra, total, usuario_registro, usuario_autoriza, pago_id y datos de pago cuando aplica.
- URLs destino:
  - creacion/autorizacion: `ordenes_compra.edit`.
  - lista para pago: `pagos-proveedores.create?orden_compra_id=...`.
  - pago programado: `pagos-proveedores.index`.
- Se corrigio la autorizacion de `OrdenCompraController::autorizar` para aceptar `ordenes_compra.authorize.access` con compatibilidad `ordenes_compra.autorizar`.
- Se corrigio `OrdenCompraController::print` para aceptar `ordenes_compra.print.access` con compatibilidad `ordenes_compra.imprimir`.

Validaciones ejecutadas:

- `php -l app/Notifications/OrdenCompraFlujoNotification.php`.
- `php -l app/Services/OrdenCompraNotificationService.php`.
- `php -l app/Http/Controllers/OrdenCompraController.php`.
- `php -l app/Http/Controllers/PagoProveedorController.php`.
- `php artisan view:clear`.

Checkpoint de cierre:

- [x] Los disparadores de cada evento quedaron conectados en codigo.
- [ ] Cada evento crea la notificacion esperada en base de datos con usuarios reales.
- [ ] Las notificaciones aparecen en la campana/notificaciones internas.
- [ ] El click abre la vista correcta.
- [ ] No se generan duplicados evidentes en pruebas reales.

## Fase 4: Conexion con el Agente Instalable

- [ ] Auditar endpoint actual del agente.
- [ ] Confirmar estructura esperada por el agente: id, titulo, mensaje, url, fecha, estado leido/no leido.
- [ ] Confirmar si el endpoint entrega las nuevas notificaciones de OC sin cambios.
- [ ] Si el endpoint filtra tipos, agregar el tipo/categoria de orden de compra.
- [ ] Validar polling del agente con usuario real.
- [ ] Validar que al hacer click desde el agente abra la URL correcta.
- [ ] Confirmar que marcar como leida funciona igual que en app.

Checkpoint de cierre:

- [ ] El agente recibe notificaciones de OC creada.
- [ ] El agente recibe notificaciones de OC autorizada.
- [ ] El agente recibe notificaciones de OC lista para pago.
- [ ] El agente recibe notificacion de pago programado.
- [ ] El agente respeta estado leido/no leido.

## Fase 5: Pruebas de Ciclo Completo

- [ ] Crear orden de compra con usuario creador.
- [ ] Confirmar notificacion al autorizador en app.
- [ ] Confirmar notificacion al autorizador en agente.
- [ ] Autorizar orden de compra.
- [ ] Confirmar notificacion al creador en app.
- [ ] Confirmar notificacion al creador en agente.
- [ ] Confirmar notificacion al responsable de programar pago en app.
- [ ] Confirmar notificacion al responsable de programar pago en agente.
- [ ] Programar pago.
- [ ] Confirmar notificacion final a involucrados en app.
- [ ] Confirmar notificacion final a involucrados en agente.
- [ ] Revisar logs de errores.

Checkpoint de cierre:

- [ ] El ciclo completo funciona con usuarios reales.
- [ ] No hay errores 403 inesperados.
- [ ] No hay errores en logs.
- [ ] Los links de todas las notificaciones son correctos.

## Fase 6: Produccion

- [ ] Confirmar migraciones o seeders requeridos.
- [ ] Confirmar permisos nuevos en produccion, si aplica.
- [ ] Limpiar cache de permisos.
- [ ] Limpiar cache de rutas/config/vistas si aplica.
- [ ] Probar con una orden real o controlada.
- [ ] Confirmar que el agente instalado en escritorio recibe el ciclo completo.

Checkpoint de cierre:

- [ ] Produccion tiene permisos y roles correctos.
- [ ] Produccion genera notificaciones in-app.
- [ ] Produccion entrega notificaciones al agente.
- [ ] Ciclo documentado como cerrado.

## Notas de Implementacion

- Mantener el patron de permisos con sufijo `.access`.
- Preferir notificar por permiso cuando el flujo dependa de capacidad funcional y no de un rol fijo.
- Evitar hardcodear usuarios salvo que no exista otra fuente confiable.
- Reutilizar el sistema de notificaciones existente antes de crear tablas nuevas.
- Mantener URLs internas absolutas o rutas generadas por Laravel segun lo que consuma el agente.

