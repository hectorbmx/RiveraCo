# Roadmap: notificaciones del agente como links web

## Objetivo

Permitir que las notificaciones recibidas por el agente local instalado en la PC abran directamente la pagina correspondiente en el navegador, sin pedir login repetidamente al usuario y sin dejar sesiones/tokens sueltos.

El usuario podra tener un solo equipo/agente activo a la vez. Cuando autorice otro equipo, el anterior quedara revocado.

## Decisiones base

- El agente local conserva un token API de Sanctum para comunicarse con SIRICO.
- El navegador no reutiliza el token API del agente.
- Para abrir una notificacion se genera un link temporal, de un solo uso, que crea/inicia la sesion web y redirige al destino real.
- No se guardan cookies web permanentes en el agente.
- No se manda usuario/password en URLs.
- Los links temporales expiran rapido, idealmente en 60 a 120 segundos.

## Arquitectura propuesta

1. El agente consulta `/api/agent/notifications/unread`.
2. El usuario da click en una notificacion del agente.
3. El agente llama a `/api/agent/notifications/{id}/open-link` con su Bearer token.
4. SIRICO valida que el agente/equipo sea el activo del usuario.
5. SIRICO crea un token temporal en `agent_open_links`.
6. El agente abre en el navegador `/agent/open/{token}`.
7. SIRICO valida el token, inicia sesion web, marca la notificacion como leida y redirige al `target_url`.

## Checkpoint 1: modelo de equipo del agente

### Objetivo

Tener una entidad persistente para identificar el equipo autorizado del usuario.

### Cambios

- [x] Crear migracion `create_agent_devices_table`.
- [x] Crear modelo `AgentDevice`.
- [x] Campos sugeridos:
  - `id`
  - `user_id`
  - `device_uuid`
  - `computer_name`
  - `token_id`
  - `is_default`
  - `last_seen_at`
  - `revoked_at`
  - `created_at`
  - `updated_at`
- [x] Indices:
  - `user_id`
  - `device_uuid`
  - `token_id`
  - `revoked_at`
- [x] Unicidad sugerida:
  - `unique(user_id, device_uuid)`

### Validacion

- [x] `php artisan migrate` crea la tabla.
- [x] Se puede crear/actualizar un `AgentDevice` desde tinker o prueba.

## Checkpoint 2: login del agente con un solo equipo activo

### Objetivo

Al iniciar sesion desde el agente, registrar el equipo actual y revocar cualquier agente anterior del mismo usuario.

### Cambios

- [x] Ajustar `app/Http/Controllers/Api/Agent/AgentAuthController.php`.
- [x] Validar payload adicional:
  - `device_uuid`
  - `computer_name`
- [x] Antes de crear el token nuevo, revocar tokens previos del agente:

```php
$user->tokens()
    ->where('name', 'sirico-agent')
    ->delete();
```

- [x] Marcar otros `AgentDevice` del usuario como revocados:

```php
AgentDevice::where('user_id', $user->id)
    ->whereNull('revoked_at')
    ->update([
        'is_default' => false,
        'revoked_at' => now(),
    ]);
```

- [x] Crear token Sanctum nuevo.
- [x] Crear/actualizar `AgentDevice` del equipo actual:
  - `is_default = true`
  - `revoked_at = null`
  - `last_seen_at = now()`
  - `token_id = id del token`
- [x] Devolver datos del equipo en la respuesta de login.

### Validacion

- [ ] Login del agente devuelve token.
- [ ] Segundo login del mismo usuario revoca el token anterior.
- [ ] Solo queda un `AgentDevice` activo por usuario.
- [ ] Endpoints protegidos del agente siguen funcionando con el token nuevo.
- [ ] Endpoints protegidos fallan con el token viejo.

## Checkpoint 2.5: preferencias del equipo del agente

### Objetivo

Guardar preferencias por computadora para controlar la comodidad del usuario al abrir notificaciones desde el agente.

### Cambios

- [x] Crear migracion para agregar preferencias a `agent_devices`.
- [x] Agregar campos:
  - `remember_web_session`
  - `open_notifications_in_browser`
  - `notification_click_behavior`
  - `trusted_until`
- [x] Actualizar `AgentDevice` con `fillable` y `casts`.
- [x] Ajustar `AgentAuthController@login` para aceptar preferencias planas o dentro de `preferences`.
- [x] Usar defaults comodos:
  - `remember_web_session = true`
  - `open_notifications_in_browser = true`
  - `notification_click_behavior = open_detail`
- [x] Devolver preferencias dentro del bloque `device` en el login.

### Validacion

- [x] `php artisan migrate` agrega columnas a `agent_devices`.
- [x] `Schema::hasColumn` confirma columnas principales.
- [ ] Login real del agente guarda preferencias enviadas por la app Windows.
## Checkpoint 3: middleware/validacion de equipo activo

### Objetivo

Evitar que un token viejo o un equipo revocado siga usando endpoints del agente.

### Cambios

- [x] Crear middleware `EnsureActiveAgentDevice`.
- [x] El middleware debe:
  - [x] leer el token actual de Sanctum
  - [x] buscar `AgentDevice` por `token_id`
  - [x] validar `revoked_at === null`
  - [x] actualizar `last_seen_at`
- [x] Aplicarlo a rutas `/api/agent/*` protegidas, excepto `login`.

### Validacion

- [ ] Token activo permite consultar notificaciones.
- [ ] Token de equipo revocado recibe `401` o `403`.
- [ ] `last_seen_at` se actualiza con actividad real.

## Checkpoint 4: links temporales de apertura

### Objetivo

Crear una tabla para generar links web de un solo uso desde el agente.

### Cambios

- [x] Crear migracion `create_agent_open_links_table`.
- [x] Crear modelo `AgentOpenLink`.
- [x] Campos sugeridos:
  - `id`
  - `user_id`
  - `agent_device_id`
  - `notification_id`
  - `token_hash`
  - `target_url`
  - `expires_at`
  - `used_at`
  - `created_at`
  - `updated_at`
- [x] Guardar solo hash del token, no el token plano.
- [x] Indices:
  - `user_id`
  - `agent_device_id`
  - `notification_id`
  - `expires_at`
  - `used_at`

### Validacion

- [x] Se puede crear un link con expiracion.
- [x] El token plano solo existe en la respuesta al agente.
- [x] La BD guarda `token_hash`.

## Checkpoint 5: endpoint API para pedir link de apertura

### Objetivo

Permitir que el agente pida una URL temporal para abrir una notificacion especifica.

### Cambios

- [x] Agregar ruta:

```php
Route::post('notifications/{id}/open-link', [AgentNotificationController::class, 'openLink']);
```

- [x] Implementar `openLink` en `AgentNotificationController`.
- [ ] Validar:
  - la notificacion pertenece al usuario autenticado
  - la notificacion tiene `data.url`
  - el equipo del agente esta activo
- [ ] Crear token aleatorio:

```php
$plainToken = Str::random(64);
```

- [ ] Guardar hash:

```php
hash('sha256', $plainToken)
```

- [ ] Responder:

```json
{
  "ok": true,
  "open_url": "https://sirico.test/agent/open/{token}",
  "expires_at": "2026-07-23T10:00:00-06:00"
}
```

### Validacion

- [ ] API devuelve `open_url` para notificacion valida. Pendiente prueba con token real.
- [x] API rechaza notificacion ajena.
- [x] API rechaza notificacion sin URL.
- [x] API rechaza equipo revocado.

## Checkpoint 6: ruta web publica para consumir el link

### Objetivo

Abrir el navegador, iniciar sesion web y redirigir al destino de la notificacion.

### Cambios

- [x] Crear controlador web `AgentOpenLinkController`.
- [x] Agregar ruta web:

```php
Route::get('/agent/open/{token}', [AgentOpenLinkController::class, 'show'])
    ->name('agent.open');
```

- [ ] En el controlador:
  - calcular hash del token recibido
  - buscar `AgentOpenLink`
  - validar que no este usado
  - validar `expires_at >= now()`
  - validar que el equipo no este revocado
  - marcar `used_at = now()`
  - iniciar sesion web con el usuario
  - marcar la notificacion como leida
  - redirigir a `target_url`

### Codigo base

```php
Auth::loginUsingId($openLink->user_id, true);

$notification = User::findOrFail($openLink->user_id)
    ->notifications()
    ->find($openLink->notification_id);

$notification?->markAsRead();

return redirect()->to($openLink->target_url);
```

### Validacion

- [ ] Link valido abre el destino correcto. Pendiente prueba con token real.
- [ ] Link valido marca la notificacion como leida. Pendiente prueba con token real.
- [x] Link usado no vuelve a abrir.
- [x] Link expirado no abre.
- [x] Link de equipo revocado no abre.

## Checkpoint 7: ajuste del agente Windows

### Objetivo

Al hacer click en una notificacion del agente, pedir `open_url` y abrirla en el navegador predeterminado.

### Cambios en `Sirico.Agent`

- [x] Asegurar que el agente guarda un `device_uuid` persistente.
- [x] En login, enviar:
  - `device_uuid`
  - `computer_name`
- [x] Guardar token API en Windows Credential Manager / DPAPI.
- [x] Al mostrar notificacion local, conservar `notification.id`.
- [x] En el handler de click:
  - llamar `POST /api/agent/notifications/{id}/open-link`
  - leer `open_url`
  - abrir navegador:

```csharp
Process.Start(new ProcessStartInfo(openUrl)
{
    UseShellExecute = true
});
```

- [x] Si falla `open-link`, mostrar mensaje de error y no abrir URL insegura.
- [x] Actualizar version del agente a `1.0.7`.

### Validacion

- [x] El agente compila correctamente con `dotnet build`.
- [ ] Click en notificacion abre navegador. Pendiente prueba con notificacion real.
- [ ] Si el navegador no tenia sesion, queda autenticado. Pendiente prueba con notificacion real.
- [ ] La notificacion queda leida. Pendiente prueba con notificacion real.
- [ ] El mismo link no funciona dos veces. Pendiente prueba con link real.
- [ ] Si se autoriza otro equipo, el anterior deja de abrir links. Pendiente prueba con segundo equipo.
- [ ] Equipos que conservan un token anterior al registro de `agent_devices` pueden requerir iniciar sesion una vez en el agente para registrar el equipo.

## Checkpoint 8: limpieza y mantenimiento

### Objetivo

Evitar acumulacion de links vencidos y mantener auditoria basica.

### Cambios

- [ ] Crear comando `agent:cleanup-open-links`.
- [ ] Eliminar o archivar links expirados con mas de X dias.
- [ ] Opcional: revocar links pendientes al hacer logout del agente.
- [ ] Opcional: vista administrativa de equipos activos por usuario.

### Validacion

- [ ] El comando elimina links vencidos antiguos.
- [ ] Logout del agente revoca el equipo actual y sus links pendientes.

## Checkpoint 9: pruebas end-to-end

### Escenarios

- [ ] Usuario inicia sesion en agente desde Equipo A.
- [ ] Llega notificacion con URL.
- [ ] Click abre detalle en navegador.
- [ ] Notificacion queda leida.
- [ ] Usuario inicia sesion en agente desde Equipo B.
- [ ] Equipo A queda revocado.
- [ ] Equipo A ya no puede pedir `open-link`.
- [ ] Equipo B si puede abrir notificaciones.
- [ ] Link expirado muestra error controlado.
- [ ] Link usado muestra error controlado.

## Riesgos y notas

- Si se usa `Auth::loginUsingId(..., true)`, Laravel creara cookie de remember/session en el navegador. Esto mejora comodidad, pero hay que aceptar que el navegador quedara autenticado como ese usuario.
- Si la PC es compartida, conviene mostrar una opcion en el agente: "Recordar sesion web en este equipo".
- Si el usuario cambia password, se recomienda revocar tokens `sirico-agent` y equipos activos.
- Si se desactiva el usuario, el middleware debe bloquear el agente aunque el token exista.

## Estado

- [ ] Pendiente de implementacion.









