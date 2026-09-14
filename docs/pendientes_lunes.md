# Pendientes lunes

## Documentos firmables y asignaciones de firma

Objetivo: evitar que se creen firmas sueltas o inventadas. El sistema debe partir de una lista de documentos reales que usan firmas y, desde ahi, permitir asignar usuarios a cada espacio de firma.

### Hallazgo actual

- La tabla `documento_firma_definiciones` debe responder: que se puede firmar.
- La tabla `documento_firmantes` debe responder: quien firma cada espacio.
- Hoy se puede crear una definicion manual como `orden_compra | administracion | vobo`.
- La impresion real de orden de compra usa `orden_compra | general | vobo_1`, `vobo_2`, `enterado`.
- Por eso una asignacion puede quedar guardada, pero no ser usada por el documento impreso.

### Propuesta

- Crear un catalogo cerrado de documentos firmables reales.
- Mostrar cada documento con una URL/ruta ejemplo, por ejemplo `/ordenes_compra/142/print`.
- Cada documento define sus espacios de firma.
- La asignacion de usuarios debe hacerse desde esos espacios, no escribiendo `documento`, `ambito` y `campo` libremente.
- A futuro, `documento_firmantes` deberia apuntar por FK a `documento_firma_definiciones` usando `documento_firma_definicion_id`.

### Documentos iniciales

- Orden de compra
  - Ruta ejemplo: `/ordenes_compra/142/print`
  - Campos: `vobo_1`, `vobo_2`, `enterado`
  - Ambito: `general`
- Reposicion caja chica
  - Ruta ejemplo: `/reposicion-caja-chica/imprimir`
  - Campos: `elaboro`, `vobo`, `autorizo`
  - Ambitos: `reposicion_gastos_almacen`, `giralda`
- Borrador de factura
  - Ruta ejemplo: `/obras/{obra}/factura-borradores/{borrador}/imprimir`
  - Campos por definir: `elaboro`, `reviso`, `autorizo`

### Pasos cortos

1. Revisar todos los documentos que hoy imprimen o deberian imprimir firmas.
2. Confirmar la lista inicial de documentos firmables.
3. Crear/ajustar seeder idempotente para `documento_firma_definiciones`.
4. Agregar ese seeder a `DatabaseSeeder`.
5. Ajustar orden de compra para filtrar `ambito = general`.
6. Crear pantalla de configuracion por documento: documento, ruta ejemplo, campos y usuario asignado.
7. Quitar inputs libres para evitar documentos/campos inventados.
8. Migrar asignaciones legacy como `orden_compra | administracion | vobo` a una definicion valida o marcarlas como historicas.
9. Agregar `documento_firma_definicion_id` a `documento_firmantes`.
10. Cambiar guardado de asignaciones para usar la FK.
11. Cambiar resolucion de firmas en impresiones para usar definiciones oficiales.
12. Agregar vista informativa en usuarios: documentos/campos donde este usuario firma.

### Checkpoints

- [ ] Inventario de documentos firmables confirmado.
- [ ] Definiciones base sembradas automaticamente al preparar una DB nueva.
- [ ] Orden de compra usa solo `orden_compra | general`.
- [ ] UI ya no permite inventar `documento`, `ambito` o `campo` al asignar firmantes.
- [ ] Asignaciones existentes migradas o conciliadas.
- [ ] `documento_firmantes` queda ligado por FK a `documento_firma_definiciones`.
- [ ] Usuarios muestran sus firmas asignadas como consulta, no como fuente principal de configuracion.
- [ ] Documentos impresos muestran el nombre correcto y quedan listos para usar `firma_digital_path` cuando se active.

### Criterio de listo

- Si alguien quiere agregar firmas a un documento nuevo, primero debe registrar el documento firmable y sus campos en el catalogo oficial.
- Ninguna asignacion puede guardarse si no apunta a una definicion existente.
- Al imprimir, el documento solo resuelve firmas desde sus definiciones oficiales.
---

## App movil: selector de panel y selector de obra

Objetivo: permitir que usuarios con acceso gerencial puedan elegir a que panel entrar, y que el panel residente pueda trabajar contra una obra seleccionada de forma explicita. Esto debe cubrir a residentes con varias obras y a Admin Rivera / Super Admin con acceso a todas las obras activas.

### Hallazgo actual

- Ionic redirige automaticamente al panel gerencial si el usuario tiene permiso `app.gerencial.access`.
- Si no tiene permiso gerencial, entra al panel residente.
- Laravel devuelve un solo `contexto` residente en login/me.
- El contexto residente se resuelve con la asignacion activa mas reciente usando `latest(id)`.
- Varios endpoints residentes vuelven a resolver la obra activa desde backend, tambien con una sola obra.
- Si Ionic permitiera cambiar obra sin backend nuevo, algunos endpoints podrian seguir usando otra obra.

### Casos que debe resolver

- Residente con una obra: entra directo al panel residente con esa obra.
- Residente con varias obras: puede elegir y cambiar obra.
- Usuario gerencial: puede entrar al panel gerencial.
- Admin Rivera / Super Admin: puede elegir panel gerencial o panel residente.
- Admin Rivera / Super Admin en panel residente: puede seleccionar cualquier obra activa permitida.

### Propuesta

- Crear un contexto movil seleccionable.
- El backend debe exponer paneles disponibles y obras disponibles para el usuario autenticado.
- El frontend debe mostrar una pantalla intermedia cuando haya mas de una opcion.
- La obra seleccionada debe persistirse en Ionic como `selected_obra_id`.
- Los endpoints residentes deben aceptar una obra seleccionada y validar acceso antes de operar.

### API propuesta

- `GET /api/v1/app/contexto-opciones`
  - Devuelve paneles disponibles.
  - Devuelve obras disponibles para modo residente.
- `GET /api/v1/me?obra_id={id}` o `GET /api/v1/residente/contexto?obra_id={id}`
  - Devuelve contexto residente para la obra seleccionada.
- Endpoints residentes que dependen de obra deben aceptar `obra_id` por query, body o header.

### Reglas de acceso

- Si el usuario tiene rol residente:
  - Solo puede ver obras donde tenga asignacion activa en `obra_empleado`.
- Si el usuario tiene rol `Admin Rivera`, `Admin-rivera`, `Super Admin` o permiso equivalente:
  - Puede ver todas las obras activas/no canceladas para modo residente.
- Si el usuario tiene `app.gerencial.access`:
  - Puede entrar al panel gerencial.
- Si no cumple ningun acceso valido:
  - Responder 403 con mensaje claro.

### Cambios backend

1. Crear servicio central para contexto movil, por ejemplo `AppMobileContextService`.
2. Mover ahi la logica de:
   - paneles disponibles
   - obras disponibles
   - validacion de acceso a obra seleccionada
   - armado de contexto residente
3. Cambiar `AuthController@login` y `AuthController@me` para no depender solo de `latest(id)`.
4. Agregar soporte para `obra_id` seleccionado.
5. Actualizar endpoints residentes que resuelven obra activa internamente:
   - comisiones residente
   - reposicion de gastos residente
   - avance civil residente
   - solicitud de materiales residente
   - asistencia/maquina/vehiculo si dependen del contexto residente
6. Asegurar que Admin Rivera / Super Admin pueda usar modo residente sin tener necesariamente rol `residente`.
7. Mantener compatibilidad para residentes con una sola obra.

### Cambios Ionic

1. Agregar storage para:
   - `selected_panel`
   - `selected_obra_id`
   - `obras_disponibles`
2. Cambiar `appShellGuard` para que no mande directo a gerencial cuando haya mas de un panel disponible.
3. Crear pantalla `selector-panel`.
4. Crear pantalla/modal `selector-obra`.
5. Despues del login:
   - si hay una sola opcion, navegar directo
   - si hay varias, pedir seleccion
6. En panel residente, agregar switch de obra en header/menu.
7. Al cambiar obra:
   - pedir contexto actualizado al backend
   - actualizar `app_contexto`
   - refrescar tabs/resumen
8. Al logout, limpiar panel y obra seleccionados.

### Puntos intermedios

1. Inventariar todos los endpoints residentes que usan obra activa.
2. Definir permiso exacto para acceso operativo global en app movil.
3. Crear payload nuevo de login/me sin romper la app actual.
4. Implementar selector de obra solo para residentes multiobra.
5. Despues extender selector a Admin Rivera / Super Admin.
6. Integrar selector de panel gerencial/residente.
7. Probar que cada modulo residente use la obra seleccionada.

### Checkpoints

- [ ] Backend devuelve `paneles_disponibles`.
- [ ] Backend devuelve `obras_disponibles` para modo residente.
- [ ] Residente con una sola obra entra directo como hoy.
- [ ] Residente con varias obras puede elegir obra.
- [ ] Residente puede cambiar obra sin cerrar sesion.
- [ ] Admin Rivera / Super Admin puede ver todas las obras activas en modo residente.
- [ ] Admin Rivera / Super Admin puede elegir panel gerencial o panel residente.
- [ ] Panel gerencial sigue funcionando con `app.gerencial.access`.
- [ ] Endpoints residentes validan `obra_id` seleccionado.
- [ ] Ningun endpoint residente usa `latest(id)` como decision final si hay `obra_id` seleccionado.
- [ ] Ionic persiste `selected_panel` y `selected_obra_id`.
- [ ] Logout limpia seleccion de panel y obra.
- [ ] Errores de acceso muestran mensaje claro en app movil.

### Criterio de listo

- El usuario nunca queda atrapado en un panel solo por tener permiso gerencial.
- El residente no queda amarrado a la ultima obra asignada si tiene mas de una.
- Admin Rivera / Super Admin puede inspeccionar cualquier obra activa desde el flujo residente.
- Todas las capturas moviles quedan asociadas a la obra seleccionada y validada por backend.
