# T02: contrato de sesión y autorización del panel mecánico

- Fecha: 2026-10-23.
- Estado: especificación terminada; código de sesión y protección de familias implementado/validado en T03. Activación del permiso y navegación móvil pendientes.
- Plan principal: [PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md](PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md).
- Los ejemplos usan identificadores ficticios. Describen el contrato de sesión; no son capturas de una app móvil ya integrada.

## 1. Contrato existente que se conserva

| Endpoint | Entrada | Respuesta actual que debe conservarse |
|---|---|---|
| `POST /api/v1/login` | JSON `email`, `password`; cabecera opcional `X-Device-Name`. | `ok`, `user`, `app`, `authz`, `contexto`, `paneles_disponibles`, `obras_residente`, `defaults`, `token`; bloque `gerencial` cuando corresponda según comportamiento existente. |
| `GET /api/v1/me` | Bearer token; `obra_id` entero opcional; `with_context` booleano opcional, por defecto `true`. | Mismos datos de sesión, sin emitir un token nuevo. |
| `GET /api/v1/app/contexto-opciones` | Bearer token; `obra_id` entero opcional. | `ok`, `paneles`, `obras_residente`, `defaults`. No renombrar `paneles` a `paneles_disponibles` en este endpoint. |

No agregar un parámetro obligatorio `panel` al login ni a `me`. No renombrar `residente` a `regular`. La ampliación consiste en admitir una tercera clave, `mecanico`, y una sesión autorizada sin contexto de obra.

`me` consulta la sesión; no la renueva ni reemplaza el token. La selección de panel es navegación del cliente, no una concesión de permisos en servidor.

## 2. Reglas de acceso a paneles

| Panel | Condición de autorización | Obra |
|---|---|---|
| `residente` | Rol `residente`, roles administrativos reconocidos actualmente o permiso efectivo `app.regular.access`. | Conservar la regla actual de contexto de obra para operar como residente. |
| `gerencial` | Permiso efectivo `app.gerencial.access`. | No exige asignación a obra del usuario. |
| `mecanico` | Permiso efectivo `app.mecanico.access`, guard `web`. | No exige asignación a obra del usuario. |

En los tres casos, login, `me` y opciones requieren un registro vigente de `usuarios_app` con `is_active = true`. El rol llamado `mecanico` por sí solo no concede el nuevo acceso; se comprueba el permiso efectivo mediante `can()` para respetar las denegaciones existentes.

Mantener `app.regular.access` con su significado actual. No quitarlo ni reasignarlo automáticamente a usuarios existentes. Un usuario con permiso regular y mecánico tendrá ambos accesos; para que vea exclusivamente mecánico se configura su autorización efectiva, sin eliminar los controles del servidor.

No exigir una obra para emitir sesión si tiene acceso gerencial o mecánico. Si solo puede entrar al panel residente y se solicita contexto pero no tiene obra disponible, conservar el error de obra actual.

El vínculo `app.empleado_id` se conserva en la respuesta. T02 no agrega un requisito de empleado al login. Las mutaciones posteriores que asignen un servicio al mecánico deberán exigir un empleado vinculado y resolverlo en servidor; tener sesión no permite inventar un `mecanico_id` desde el cliente.

## 3. Selección de panel y contexto

- Orden estable: `residente`, `gerencial`, `mecanico`, incluyendo únicamente paneles autorizados. Los pares actuales conservan su orden.
- Un panel autorizado: `defaults.panel` contiene su clave.
- Dos o tres paneles autorizados: `defaults.panel = null`; el cliente muestra selector. No dar prioridad implícita a gerencial ni al nuevo panel.
- Ningún panel autorizado: login/`me`/opciones devuelven prohibición de acceso, no una sesión exitosa vacía.
- `defaults.obra_id` sigue siendo la obra residente seleccionada/autorizada o `null`. No crear una obra ficticia para el mecánico.
- `contexto` sigue significando contexto de residente. Para mecánico exclusivo es `null`; eso no es un error de autenticación.
- En acceso múltiple puede existir contexto residente aun si el usuario abre Mecánico. La pantalla de mecánico no debe tomarlo como obra obligatoria del servicio ni mostrar información de residente por ese motivo.
- `obras_residente` conserva su lista; para mecánico exclusivo es `[]`.
- En `me?with_context=false` conservar la omisión del cálculo de contexto existente: `contexto = null`. No usar esa opción para autorizar operaciones de obra.
- `obra_id` solo selecciona entre obras disponibles para el usuario. No amplía acceso ni transforma al mecánico en residente.
- Mantener el bloque opcional `gerencial` con la condición actual; no agregarlo a mecánico exclusivo ni exigir un bloque equivalente para que el cliente abra Mecánico.

No ocultar un panel autorizado solo por no disponer de obra en ese momento: se mantiene la semántica de opciones actual. Al abrir Residente, el cliente debe comprobar su contexto; en el servidor siguen vigentes las validaciones de cada operación de obra.

## 4. Ejemplos de login y `me`

### 4.1 Solicitud de login

```json
{
  "email": "mecanico@example.test",
  "password": "clave-de-ejemplo"
}
```

### 4.2 Mecánico exclusivo, activo y sin obra

Respuesta objetivo de login, HTTP 200:

```json
{
  "ok": true,
  "user": {"id": 80, "email": "mecanico@example.test", "name": "Mecánico de ejemplo"},
  "app": {"user_app_id": 20, "empleado_id": 140, "is_active": true},
  "authz": {"roles": ["mecanico"], "permissions": ["app.mecanico.access"]},
  "contexto": null,
  "paneles_disponibles": [{"key": "mecanico", "label": "Mecánico"}],
  "obras_residente": [],
  "defaults": {"panel": "mecanico", "obra_id": null},
  "token": "TOKEN_DE_EJEMPLO"
}
```

`GET /api/v1/me` con ese Bearer devuelve la misma estructura sin la propiedad `token`. No debe emitir el error «No tienes una obra activa asignada».

`GET /api/v1/app/contexto-opciones` devuelve:

```json
{
  "ok": true,
  "paneles": [{"key": "mecanico", "label": "Mecánico"}],
  "obras_residente": [],
  "defaults": {"panel": "mecanico", "obra_id": null}
}
```

### 4.3 Residente exclusivo con obra

Fragmento de respuesta compartido por login y `me`. Los bloques existentes de `user`, `app` y `authz` se conservan; `contexto` mantiene además los campos actuales de obra, empleados, máquina, pilas y vehículo.

```json
{
  "ok": true,
  "paneles_disponibles": [{"key": "residente", "label": "Residente"}],
  "defaults": {"panel": "residente", "obra_id": 17}
}
```

- Login: agrega `token` y carga el contexto completo de la obra autorizada.
- `me`: sin `token`; `obra_id=17` selecciona esa obra si está autorizada.
- Si no tiene obra y no cuenta con otro panel autorizado, login y `me` por defecto conservan HTTP 403 por obra no asignada.
- `me?with_context=false` conserva el comportamiento de metadatos existente; las operaciones de obra continúan validando acceso por sí mismas.

### 4.4 Gerencial exclusivo, sin obra

Fragmento compartido por login y `me`:

```json
{
  "ok": true,
  "contexto": null,
  "paneles_disponibles": [{"key": "gerencial", "label": "Gerencial"}],
  "obras_residente": [],
  "defaults": {"panel": "gerencial", "obra_id": null},
  "gerencial": {"kpis": null, "obras_preview": []}
}
```

Conservar `user`, `app` y `authz`. Solo login emite `token`. No agregar Mecánico por disponer de permiso gerencial.

### 4.5 Residente y mecánico, sin obra

Fragmento compartido por login y `me`:

```json
{
  "ok": true,
  "contexto": null,
  "paneles_disponibles": [
    {"key": "residente", "label": "Residente"},
    {"key": "mecanico", "label": "Mecánico"}
  ],
  "obras_residente": [],
  "defaults": {"panel": null, "obra_id": null}
}
```

Login agrega `token`; `me` no. La sesión es válida por acceso mecánico. El selector permite entrar a Mecánico; Residente debe informar que no hay obra disponible, sin habilitar sus operaciones por esa ausencia.

### 4.6 Gerencial y mecánico, sin obra

Fragmento compartido por login y `me`:

```json
{
  "ok": true,
  "contexto": null,
  "paneles_disponibles": [
    {"key": "gerencial", "label": "Gerencial"},
    {"key": "mecanico", "label": "Mecánico"}
  ],
  "obras_residente": [],
  "defaults": {"panel": null, "obra_id": null},
  "gerencial": {"kpis": null, "obras_preview": []}
}
```

Conservar datos de identidad/autorización; token solo en login. Con los tres accesos, devolver los tres paneles en el orden estable y mantener `defaults.panel = null`. Si tiene obra residente, conservar el contexto y la selección de obra existentes.

## 5. Errores y cambios de acceso

| Caso | Contrato objetivo |
|---|---|
| Email/contraseña inválidos o entrada mal formada | Conservar HTTP 422 y el formato de validación actual de Laravel. No cambiarlo a 401 durante T03. |
| Bearer ausente/inválido en rutas protegidas | HTTP 401, comportamiento de Sanctum con `Accept: application/json`. |
| Sin registro de usuario app o registro eliminado lógicamente | HTTP 403 con el mensaje actual de usuario no habilitado. |
| Usuario app inactivo | HTTP 403 con el mensaje actual de acceso desactivado. |
| Sin ningún panel autorizado | HTTP 403, `ok: false`, mensaje de acceso denegado. |
| Solo residente sin obra al solicitar contexto | HTTP 403 y mensaje actual de obra no asignada. |
| Mecánico sin obra | HTTP 200 en sesión/opciones, contexto residente nulo. |
| Mecánico exclusivo llama una ruta residente o gerencial | HTTP 403 antes de ejecutar la operación. Usar `Accept: application/json`; no redirigir al login web. |
| Permiso de mecánico revocado con un token existente | Una petición nueva a mecánico vuelve a evaluar el permiso y devuelve 403. `me` recalcula opciones; conserva otros paneles si siguen autorizados. No afirmar que el token se revoca automáticamente. |

Los ejemplos y la app deben usar `Accept: application/json`. Tener un token previo no sustituye el registro activo de usuario app ni el permiso exigido para cada nueva operación.

## 6. Barrido de rutas y matriz de autorización

Inventario verificado en `routes/api.php` y con `php artisan route:list --path=api/v1 --json`. Esta matriz define lo que debe aplicar T03; el registro de validación al final documenta los controles implementados.

| Familia | Protección encontrada | Objetivo de T03 |
|---|---|---|
| `POST /v1/login` | Credenciales; validación interna de usuario app y paneles. | Agregar permiso mecánico a la admisión y la excepción de obra. |
| `GET /v1/me`, `GET /v1/app/contexto-opciones` | Sanctum; validaciones internas de usuario app y paneles. | Sesión compartida con los tres paneles; mantener activo y permisos efectivos. |
| `/v1/gerencial/*` | Sanctum + `app.gerencial.access`. | Conservar restricción. Mecánico exclusivo no accede. |
| `/v1/maquinas/{obraMaquina}/registros` GET/POST; `/reportar-falla`, `/actualizar-estado` POST | Sanctum en ruta; el controlador revisa usuario app/asignación o rol administrativo. | Agregar límite de familia: mecánico exclusivo no entra por disponer del nuevo permiso. Conservar comprobaciones de obra actuales. |
| `/v1/obras/{obra}/asistencias` GET/POST/DELETE y asistencia de empleado GET | Sanctum en ruta. Se observó validación del empleado de destino en la obra; eso no equivale a autorizar al usuario que llama. | Bloquear mecánico exclusivo antes del controlador. Conservar validaciones de empleados/asistencia y no suponer que una obra enviada por ID autoriza acceso. |
| `/v1/obras/{obra}/comisiones` GET/POST | Sanctum en ruta; consulta y creación mediante controladores diferentes. | Bloquear mecánico exclusivo sin cambiar contratos o cálculos existentes. |
| `/v1/residente/comisiones*` | Sanctum en ruta y lógica de comisiones residente. | Aplicar límite de familia y conservar autorización por registro/obra. |
| `/v1/residente/obra-civil/avance/*` y `/v1/residente/obra-civil/materiales*` | Sanctum en ruta; reglas propias del flujo. | Mecánico exclusivo prohibido; conservar permisos operativos actuales. |
| `/v1/residente/reposicion-gastos*` | Sanctum en ruta; validación interna de usuario app/obra. | Mecánico exclusivo prohibido; no sustituir comprobaciones de registro/obra. |
| `/v1/vehiculos/km-log` GET/POST | Sanctum en ruta y lógica dependiente de usuario/obra. | Conservar como operación existente de residente. El módulo de mecánico no obtiene este acceso automáticamente. |
| `/v1/attendance/logs`, `/attendance/employees/search`, `/attendance/employees/{employee}/summary` | Sanctum en ruta; las consultas inspeccionadas no contienen una restricción por panel mecánico. | Bloquear mecánico exclusivo. No ampliar ni redefinir acceso de usuarios existentes como parte de este módulo. |
| Futuro `/v1/mecanico/*` | No existe todavía. | Sanctum + usuario app activo + `app.mecanico.access`; autorización adicional del servicio según alcance. |

### Estrategia para conservar acceso existente

Para las familias existentes sin permiso de panel en la ruta, agregar una comprobación común que exija usuario app activo y al menos un acceso previo reconocido (`puedeVerPanelResidente()` o `puedeVerGerencial()`). Un permiso exclusivamente mecánico no satisface ese límite. Las autorizaciones internas más específicas permanecen y pueden seguir denegando acceso.

Esto evita convertir T03 en una redefinición de permisos de residentes, gerenciales o administrativos, y evita una regla negativa basada solo en tener rol de mecánico: un usuario con varios accesos conserva la unión de los accesos autorizados.

Proteger también el grupo gerencial frente a usuario app inactivo con token existente. No usar un encabezado o panel elegido por el cliente como sustituto de estas validaciones.

Los endpoints de ingesta de dispositivos, sincronización externa y `/api/agent` pertenecen a otros contratos. No forman parte de la navegación móvil del mecánico ni se modificarán al agrupar estas rutas. El alcance no constituye una auditoría completa de todas las integraciones.

### Cierre de sesión: hallazgo adicional

Existe `AuthController::logout()`, pero no apareció una ruta de logout bajo `/api/v1` en el inventario actual. Las rutas `/api/agent/logout` son de otro cliente. T03 no inventará ni cambiará un endpoint de logout dentro de esta especificación; registrar su publicación como mejora separada si el cliente móvil la necesita.

## 7. Checklist verificable de T03

- [x] Login de mecánico exclusivo sin obra devuelve 200, token y un solo panel.
- [x] `me` mantiene esa sesión sin emitir token nuevo; opciones usa `paneles`.
- [x] Permiso mecánico permite panel aun si el rol tiene otro nombre; rol mecánico sin permiso no basta.
- [x] Residente y gerencial conservan sus claves y comportamiento de sesión en los escenarios probados.
- [x] Accesos múltiples devuelven selector, sin bloqueo de toda la sesión por falta de obra residente.
- [x] Mecánico no recibe panel gerencial ni acceso a sus rutas por el nuevo permiso.
- [x] Usuario app inactivo/sin registro se rechaza, también en peticiones ya autenticadas.
- [x] Revocar permiso se aplica en la siguiente petición protegida, respetando denegaciones y otros accesos válidos.
- [x] Mecánico exclusivo recibe 403 en cada ruta registrada de las familias de la matriz, antes de resolver modelos.
- [x] Accesos existentes superan el nuevo control común cuando corresponden; los controles internos de obra/registro se conservan sin modificación.
- [x] No crear/asignar permisos ni quitar `app.regular.access` automáticamente.
- [x] Verificar UTF-8 y actualizar este documento/bitácora con resultados reales.

## 8. Checkpoint T02

Completado como especificación documental: contratos de entrada/salida revisados, casos de sesión definidos y familias de rutas inventariadas. Se validó la sintaxis de los ejemplos JSON y la codificación del documento.

Al cerrar T02 no se habían ejecutado pruebas de endpoints futuros ni habilitado acceso mecánico. La validación posterior de T03 se registra a continuación; la navegación con la app requiere inspeccionar el proyecto móvil en T13.

## 9. Resultado de T03, 2026-10-23

- Implementados el panel `mecanico`, la excepción de obra para ese acceso y la selección de panel único/múltiple.
- La validación común de usuario app activo se comparte entre sesión y middleware. `newQuery()` conserva el scope de eliminación lógica de `UsuarioApp`.
- `EnsureMobileAppAccess` aplica el límite `legacy` a familias existentes y `gerencial` al grupo correspondiente, además de su permiso previo. Soporta `mecanico` para los futuros contratos; no se publicó una ruta productiva ficticia para probarlo.
- Se preservó el orden de middleware de Laravel agregando el control móvil antes de `SubstituteBindings`: mecánico exclusivo recibe 403 antes de consultar modelos de rutas ajenas.
- Inventario verificado: 46 rutas existentes con el nuevo control de familia.
- `AppMobileMechanicAccessTest`: 9 pruebas HTTP nuevas. Con `AppMobileContextAccessTest` y las regresiones de fotos: 27 pruebas, 217 comprobaciones.
- Se probaron login, `me`, opciones, paneles múltiples, residente sin obra, credenciales erróneas, usuario app inactivo/ausente, todas las rutas restringidas y revocación/denegación efectiva de permiso.
- Las consultas/modelos de usuario y la creación de token se simularon en las pruebas HTTP para no escribir en la base local. El middleware mecánico se ejercitó en una ruta temporal del entorno de pruebas. No se afirma haber probado emisión/persistencia real de tokens ni pantallas móviles.
- Se verificaron sintaxis de los archivos PHP tocados, UTF-8 y ausencia de errores de whitespace.
- Una consulta de solo lectura confirmó que `app.mecanico.access` con guard `web` todavía no existe en la base local. Crear y asignar el permiso desde la UI antes de usarlo con el usuario real; no hubo cambios automáticos de permisos/roles ni migraciones en T03.
- Siguiente entrega: T04, consultas de máquinas y vehículos para el nuevo panel. Productos corresponde a T05 y las pantallas a T13.

### Actualización T04

El grupo productivo `/api/v1/mecanico` ya publica las cuatro consultas de máquinas y vehículos, protegidas por `EnsureMobileAppAccess:mecanico` y Sanctum. Ver [contrato T04](CONTRATO_CONSULTA_EQUIPOS_MECANICO_T04.md). La ruta mecánica temporal de T03 permanece únicamente en pruebas; no se convirtió en endpoint de producto. Las otras operaciones propuestas de servicios/productos siguen pendientes.
