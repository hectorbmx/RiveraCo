# Chat institucional SIRICO — MVP y visión futura

## 1. Objetivo

Implementar un chat institucional interno para los empleados registrados como usuarios de SIRICO. El chat debe estar disponible tanto en la aplicación móvil Ionic como en el sistema web renderizado con Laravel, compartiendo conversaciones, mensajes, grupos, permisos y estados de lectura.

Laravel será la fuente única de verdad. Ionic y la interfaz web no se sincronizarán directamente entre sí: ambos consumirán la misma API, base de datos y servicio de eventos en tiempo real administrados por Laravel.

## 2. Principios del módulo

- Uso exclusivo para usuarios internos activos de SIRICO.
- Toda comunicación debe quedar registrada y ser auditable.
- Los mensajes no se eliminan físicamente de la base de datos.
- En el MVP no se permitirá borrar mensajes desde la interfaz, ni siquiera mediante eliminación lógica visible para el usuario.
- El historial será el mismo en Ionic y en la aplicación web.
- Los permisos se administrarán con los roles y permisos existentes en Laravel.
- HTTPS, autenticación, autorización y almacenamiento privado son obligatorios.
- No se implementará cifrado de extremo a extremo, porque impediría o complicaría la auditoría, recuperación y conservación institucional.
- Las llamadas de voz o video quedan completamente fuera del proyecto.
- Las notas de voz son secundarias y se contemplan únicamente para una fase posterior.

## 3. Alcance del MVP

### 3.1 Usuarios

- Utilizar los usuarios y empleados ya registrados en SIRICO.
- Mostrar únicamente usuarios activos y autorizados para usar el chat.
- Buscar usuarios por nombre, puesto, área o departamento.
- Mostrar nombre, fotografía o iniciales, puesto y estado activo/inactivo.
- Impedir que usuarios dados de baja inicien sesión o envíen mensajes.
- Conservar los mensajes históricos de usuarios inactivos.

### 3.2 Conversaciones privadas

- Crear conversaciones directas entre dos usuarios.
- Evitar conversaciones directas duplicadas entre la misma pareja de usuarios.
- Enviar y recibir mensajes de texto.
- Mostrar fecha, hora y remitente.
- Estados mínimos: enviado, entregado y leído.
- Contador de mensajes no leídos.
- Archivar una conversación sin eliminar su historial.
- Cargar el historial mediante paginación por lotes para evitar peticiones extensas al entrar a una conversación.

### 3.3 Grupos

- Crear grupos de chat institucionales.
- Definir nombre y descripción del grupo.
- Agregar y retirar integrantes de acuerdo con permisos.
- Asignar uno o varios administradores del grupo.
- Permitir que un administrador cambie el nombre y la descripción.
- Registrar altas y bajas de integrantes como eventos del sistema.
- Impedir que un grupo se elimine físicamente; podrá quedar archivado o cerrado.
- Preparar la relación opcional con una obra, área o departamento, aunque la automatización de esos grupos puede quedar para una iteración posterior.

### 3.4 Roles y permisos

Se recomienda integrar los permisos con Spatie y la estructura actual de SIRICO, sin crear un sistema de roles independiente.

Permisos propuestos:

| Permiso | Función |
| --- | --- |
| `chat.access` | Acceder al módulo y consultar las conversaciones propias |
| `chat.direct.create` | Iniciar conversaciones privadas |
| `chat.group.create` | Crear grupos |
| `chat.group.manage.own` | Administrar grupos en los que el usuario es administrador |
| `chat.group.manage.any` | Administrar cualquier grupo institucional |
| `chat.audit.access` | Consultar el panel de auditoría autorizado |
| `chat.export` | Exportar conversaciones cuando exista autorización |

Roles funcionales dentro de cada grupo:

- **Integrante:** consulta y envía mensajes.
- **Administrador del grupo:** administra nombre, descripción e integrantes.
- **Administrador institucional:** puede administrar grupos conforme a su permiso global.

El acceso de auditoría no debe concederse automáticamente a todos los administradores del sistema. Debe ser un permiso explícito y quedar registrado cada vez que se utilice.

### 3.5 Sincronización Ionic–Laravel web

- Laravel guardará todos los mensajes y estados oficiales.
- Ionic y la web consumirán los mismos endpoints.
- Los mensajes nuevos se distribuirán mediante eventos en tiempo real.
- Al reconectarse, cada cliente solicitará los mensajes posteriores al último identificador confirmado.
- Cada mensaje enviado por un cliente incluirá un `client_uuid` para evitar duplicados durante reintentos.
- El servidor asignará el identificador definitivo y la fecha oficial de recepción.
- Ionic podrá mantener una caché local para funcionamiento y apertura rápida, pero nunca será la fuente oficial.
- Los cambios de integrantes, mensajes leídos y conversaciones archivadas también deberán sincronizarse.

Flujo de envío:

1. Ionic o la web genera un `client_uuid`.
2. Envía el mensaje a la API de Laravel.
3. Laravel valida usuario, permisos y pertenencia a la conversación.
4. Laravel persiste el mensaje y registra el evento de auditoría.
5. Laravel devuelve el mensaje con su ID definitivo.
6. Laravel publica el evento en tiempo real para los clientes conectados.
7. Si el destinatario no está activo, Laravel envía una notificación push.
8. Al reconectarse, el cliente recupera cualquier mensaje faltante desde Laravel.

### 3.6 Tiempo real

- Utilizar Laravel Broadcasting con una implementación compatible con WebSockets.
- Recomendación inicial:
  - **Laravel Reverb** si SIRICO se ejecuta en un servidor donde se puedan administrar procesos persistentes, SSL, puertos y supervisor de servicios.
  - **Pusher o Ably** si el hosting actual no permite operar WebSockets propios de forma estable.
- Para el MVP se prefiere una opción administrada como Pusher o Ably cuando exista duda sobre la infraestructura, porque reduce riesgo operativo y acelera la entrega.
- Crear canales privados por usuario y por conversación.
- Autorizar cada suscripción desde Laravel.
- Eventos mínimos:
  - mensaje creado;
  - mensaje entregado;
  - mensaje leído;
  - conversación actualizada;
  - integrante agregado o retirado;
  - grupo archivado o cerrado.
- La falla temporal del canal en tiempo real no debe impedir guardar mensajes por API.
- Al recuperar conexión se debe ejecutar una sincronización incremental.

### 3.7 Notificaciones push

- Enviar push cuando exista un mensaje nuevo y el usuario no tenga activa esa conversación.
- Incluir remitente, nombre del grupo y una vista previa controlada del contenido.
- Abrir la conversación correspondiente al tocar la notificación.
- Registrar múltiples dispositivos por usuario.
- Permitir invalidar tokens vencidos o cerrados.
- Respetar las preferencias futuras de notificación sin impedir que el mensaje quede registrado.
- En la web, mostrar contador global y aviso interno en tiempo real.

### 3.8 Archivos adjuntos del MVP

Se pueden incluir imágenes y PDF en el MVP si el calendario lo permite. Si se requiere reducir el alcance inicial, esta función puede entregarse inmediatamente después del chat de texto.

- Almacenamiento privado.
- Descarga únicamente mediante autorización de Laravel.
- Límite configurable de tamaño.
- Lista blanca de tipos MIME.
- Registro de nombre, tamaño, tipo, propietario y fecha.
- El archivo debe conservarse junto con el historial institucional.
- No se deben exponer rutas públicas permanentes.

### 3.9 Auditoría y conservación

- Conservar mensajes, archivos y eventos institucionales.
- No permitir eliminación física ni eliminación lógica de mensajes desde las interfaces del MVP.
- La eliminación lógica de mensajes queda fuera del MVP y solo deberá evaluarse posteriormente si existe una política institucional explícita.
- Registrar creación, entrega, lectura, descarga de archivo, ingreso o salida de grupo y consulta administrativa.
- Toda consulta administrativa de una conversación debe generar su propio evento de auditoría.
- Definir posteriormente una política formal de retención y exportación con la administración de la empresa.

## 4. Arquitectura propuesta

### Backend Laravel

- API REST autenticada para usuarios, conversaciones, grupos, mensajes, adjuntos y estados de lectura.
- Laravel Policies o Gates para autorización por conversación.
- Spatie para permisos globales.
- Broadcasting para eventos en tiempo real.
- Jobs/colas para notificaciones push y procesos que no deban bloquear el envío.
- Almacenamiento privado para adjuntos.
- Base de datos central y bitácora de auditoría.

### Cliente Ionic

- Lista de conversaciones.
- Buscador de usuarios.
- Creación y administración de grupos según permisos.
- Vista de conversación con paginación.
- Caché local controlada.
- Cola de mensajes pendientes y reintentos con `client_uuid`.
- Suscripción a eventos en tiempo real.
- Registro de tokens para push.
- Sincronización incremental al iniciar, reanudar o recuperar conexión.

### Cliente web Laravel

- Página completa del chat dentro de SIRICO.
- Contador global de no leídos en el encabezado.
- Lista de conversaciones y vista de mensajes.
- Creación y administración de grupos según permisos.
- Eventos en tiempo real usando el mismo backend.
- Diseño responsive para escritorio y tablet.

## 5. Estrategia de implementación Laravel

### 5.1 Módulo aislado

El desarrollo inicial debe hacerse como un módulo interno de Laravel, con bajo acoplamiento respecto al resto de SIRICO. La intención no es crear un paquete externo desde el inicio, sino mantener una frontera clara de código para facilitar pruebas, mantenimiento y futuras extensiones.

Estructura sugerida:

```text
app/Chat/
  Models/
  Policies/
  Services/
  Actions/
  Events/
  Jobs/
  Notifications/
  Support/
```

Las rutas del módulo deben vivir en un archivo separado, por ejemplo:

```text
routes/chat.php
```

Ese archivo se cargará con prefijo y middleware propios del módulo:

```php
Route::middleware(['auth:sanctum'])
    ->prefix('api/chat')
    ->group(base_path('routes/chat.php'));
```

La lógica de negocio no debe concentrarse en los controladores. Los controladores deberán validar entrada, ejecutar acciones del módulo y devolver respuestas. La lógica principal deberá vivir en clases de acción o servicios, por ejemplo:

```text
CreateDirectConversation
CreateGroupConversation
SendMessage
MarkConversationAsRead
ArchiveConversation
```

### 5.2 Orden técnico recomendado

1. Migraciones, modelos y relaciones del módulo.
2. Policies y validaciones de pertenencia a conversaciones.
3. Acciones principales para crear conversaciones, crear grupos, enviar mensajes y marcar lectura.
4. API REST del módulo.
5. Bitácora mínima de auditoría.
6. Vista web ligera que consuma la API.
7. Cliente Ionic consumiendo la misma API.
8. Tiempo real mediante Laravel Broadcasting.
9. Push, adjuntos y endurecimiento operativo.

### 5.3 Carga progresiva de vistas

Laravel no tiene un equivalente directo a `async/await` para renderizar vistas Blade del lado servidor como ocurre en JavaScript. Una petición tradicional de Laravel prepara los datos, renderiza la vista y responde.

Para que la aplicación se sienta más rápida, la estrategia recomendada es renderizar primero una vista ligera y cargar los datos por partes desde el frontend usando JavaScript y la API del módulo.

Ejemplo de flujo para la vista web del chat:

1. Blade renderiza el contenedor visual del chat sin cargar todo el historial.
2. JavaScript solicita `/api/chat/conversations`.
3. Al seleccionar una conversación, JavaScript solicita `/api/chat/conversations/{conversation}/messages?limit=50`.
4. Para historial anterior, el cliente solicita otro lote con `before_id`.
5. Los datos secundarios, como integrantes o adjuntos, se cargan solo cuando la vista los necesite.

Este enfoque permite que web e Ionic usen la misma API, evita respuestas iniciales pesadas y reduce el riesgo de bloquear la interfaz al abrir conversaciones grandes.

### 5.4 Procesos en segundo plano

Para tareas pesadas o no críticas en la respuesta inmediata se usarán Jobs y colas de Laravel. Ejemplos:

- envío de notificaciones push;
- registro o consolidación de eventos secundarios;
- procesamiento posterior de adjuntos;
- limpieza de tokens vencidos.

El envío de un mensaje no debe depender de que estos procesos terminen. Laravel debe persistir el mensaje primero y despachar los trabajos secundarios después.

## 6. Modelo de datos inicial

### `chat_conversations`

- `id`
- `type`: `direct` o `group`
- `name`, nullable para conversaciones directas
- `description`, nullable
- `created_by`
- `related_type`, nullable, para relación futura con obra, área u otro módulo
- `related_id`, nullable
- `status`: `active`, `archived` o `closed`
- `created_at`, `updated_at`

### `chat_conversation_users`

- `id`
- `conversation_id`
- `user_id`
- `role`: `member` o `admin`
- `joined_at`
- `left_at`, nullable
- `last_read_message_id`, nullable
- `last_read_at`, nullable
- `archived_at`, nullable
- restricción única por conversación y usuario

### `chat_messages`

- `id`
- `conversation_id`
- `sender_id`
- `client_uuid`
- `type`: inicialmente `text`, `image`, `file` o `system`
- `body`, nullable cuando sea un adjunto
- `reply_to_message_id`, nullable y opcional para MVP
- `server_received_at`
- `edited_at`, nullable
- `deleted_at`, nullable, reservado para una fase futura si se autoriza eliminación lógica
- `created_at`, `updated_at`
- restricción única por `sender_id` y `client_uuid`

### `chat_message_receipts`

- `id`
- `message_id`
- `user_id`
- `delivered_at`, nullable
- `read_at`, nullable
- restricción única por mensaje y usuario

### `chat_attachments`

- `id`
- `message_id`
- `uploaded_by`
- `disk`
- `path`
- `original_name`
- `mime_type`
- `size`
- `checksum`, nullable
- `created_at`, `updated_at`

### `chat_audit_events`

- `id`
- `conversation_id`, nullable
- `message_id`, nullable
- `actor_user_id`, nullable para eventos automáticos
- `event_type`
- `metadata` JSON
- `ip_address`, nullable
- `user_agent`, nullable
- `created_at`

### `user_push_devices`

- `id`
- `user_id`
- `platform`: `ios`, `android` o `web`
- `device_uuid`
- `push_token`
- `last_used_at`
- `revoked_at`, nullable
- `created_at`, `updated_at`

## 7. API mínima propuesta

### Usuarios y conversaciones

```text
GET    /api/chat/users
GET    /api/chat/conversations
POST   /api/chat/conversations/direct
POST   /api/chat/conversations/groups
GET    /api/chat/conversations/{conversation}
PATCH  /api/chat/conversations/{conversation}
POST   /api/chat/conversations/{conversation}/archive
```

### Integrantes de grupos

```text
GET    /api/chat/conversations/{conversation}/members
POST   /api/chat/conversations/{conversation}/members
PATCH  /api/chat/conversations/{conversation}/members/{user}
DELETE /api/chat/conversations/{conversation}/members/{user}
```

El `DELETE` anterior representa retirar al integrante; no debe borrar su historial ni sus mensajes.

### Mensajes y sincronización

```text
GET    /api/chat/conversations/{conversation}/messages?limit={n}&before_id={message_id}
POST   /api/chat/conversations/{conversation}/messages
POST   /api/chat/conversations/{conversation}/read
GET    /api/chat/sync?after_event_id={id}
```

La carga inicial de mensajes usará un límite configurable por conversación. Valor sugerido para el MVP: entre 30 y 50 mensajes recientes. Para cargar historial anterior, el cliente enviará `before_id` con el ID del mensaje más antiguo que ya tenga en pantalla.

La búsqueda dentro de conversaciones y la búsqueda global de mensajes quedan fuera del MVP. La búsqueda inicial solo cubrirá usuarios activos para iniciar conversaciones o agregar integrantes.

### Dispositivos y push

```text
POST   /api/chat/devices
DELETE /api/chat/devices/{device_uuid}
```

## 8. Reglas funcionales críticas

1. Solo un integrante activo puede consultar una conversación.
2. Solo un integrante activo puede enviar mensajes.
3. Crear o administrar grupos requiere el permiso correspondiente.
4. Los administradores de grupo pueden administrar únicamente sus grupos, salvo permiso global.
5. Retirar a un usuario no elimina sus mensajes anteriores.
6. Un usuario nuevo en un grupo podrá consultar el historial del grupo al que fue asignado, siempre que conserve pertenencia activa y permisos sobre ese grupo.
7. En conversaciones directas uno a uno no existe historial previo para un usuario nuevo, porque la conversación pertenece exclusivamente a esa pareja de usuarios.
8. No debe existir eliminación física ni lógica de mensajes desde el módulo durante el MVP.
9. Laravel define timestamps, IDs definitivos, permisos y estados oficiales.
10. Los reintentos con el mismo `client_uuid` no deben generar mensajes duplicados.
11. Ninguna notificación push sustituye el almacenamiento del mensaje en Laravel.
12. La información sensible no debe incluirse completa en el payload de una notificación.
13. Toda acción administrativa extraordinaria debe quedar auditada.

## 9. Criterios de aceptación del MVP

- Un usuario puede buscar a otro usuario activo e iniciar un chat privado.
- Un usuario autorizado puede crear un grupo y administrar sus integrantes.
- Los roles y permisos controlan quién puede crear y administrar grupos.
- Un mensaje enviado desde Ionic aparece en la web sin recargar manualmente.
- Un mensaje enviado desde la web aparece en Ionic sin recargar manualmente.
- Si un cliente estuvo desconectado, recupera los mensajes faltantes al reconectarse.
- Un reintento de red no duplica el mensaje.
- Los contadores de no leídos coinciden entre web y móvil después de sincronizar.
- El destinatario recibe una notificación push cuando corresponde.
- Un usuario no integrante recibe respuesta `403` al intentar consultar una conversación.
- Un usuario retirado de un grupo deja de recibir mensajes nuevos, pero su actividad anterior permanece registrada.
- Un usuario nuevo en un grupo puede consultar el historial del grupo asignado, pero no conversaciones privadas previas.
- Los mensajes y conversaciones no pueden eliminarse física ni lógicamente desde la interfaz del MVP.
- Las acciones administrativas quedan registradas en la bitácora.

## 10. Fuera del alcance

Quedan fuera del MVP y del proyecto actual:

- Llamadas de voz.
- Videollamadas.
- Compartir pantalla.
- Cifrado de extremo a extremo.
- Comunicación con usuarios externos o invitados.

Quedan fuera del MVP, pero pueden evaluarse posteriormente:

- Notas de voz.
- Reacciones y emojis avanzados.
- Edición de mensajes.
- Eliminación lógica de mensajes visible para usuarios.
- Respuestas encadenadas.
- Menciones `@usuario`.
- Búsqueda dentro de conversaciones y búsqueda global avanzada.
- Exportaciones administrativas.
- Automatización de grupos por obra, departamento o proyecto.

## 11. Visión futura

### Fase 2 — Productividad y contenido

- Notas de voz con duración, compresión, carga, reproducción y almacenamiento privado.
- Búsqueda dentro de conversaciones.
- Respuestas a mensajes y menciones.
- Reacciones.
- Mejoras de administración de grupos.
- Preferencias de notificación por conversación.
- Archivos adicionales según políticas de seguridad.

Las notas de voz deberán tratarse como mensajes institucionales: se almacenarán en el servidor, se conservarán en el historial y estarán sujetas a permisos y auditoría.

### Fase 3 — Integración con procesos SIRICO

- Grupos vinculados con obras.
- Grupos por departamento o área.
- Conversaciones vinculadas con solicitudes de material.
- Conversaciones vinculadas con órdenes de compra, mantenimientos o incidencias.
- Mensajes automáticos de sistema sobre cambios de estado.
- Acceso directo desde un módulo de SIRICO hacia su conversación relacionada.

El chat complementará los módulos formales, pero no sustituirá autorizaciones, firmas, estados ni registros operativos.

### Fase 4 — Gobierno y análisis institucional

- Panel de auditoría con permisos especiales.
- Exportación autorizada y trazable.
- Política configurable de conservación.
- Métricas generales sin exponer contenido innecesariamente.
- Herramientas de moderación institucional.
- Alertas de archivos inseguros o comportamiento anómalo.

## 12. Orden sugerido de implementación

1. Crear la estructura aislada del módulo en Laravel y sus rutas propias.
2. Migraciones, modelos, relaciones y Policies del módulo.
3. Acciones y servicios para conversaciones privadas, grupos, mensajes y lectura.
4. API de usuarios, conversaciones privadas, grupos, mensajes y sincronización básica.
5. Bitácora y reglas de conservación.
6. Interfaz web ligera en Laravel consumiendo la API.
7. Interfaz Ionic y caché local consumiendo la misma API.
8. Broadcasting y sincronización incremental.
9. Notificaciones push y registro de dispositivos.
10. Adjuntos privados.
11. Pruebas de permisos, desconexión, reintentos, duplicados y auditoría.

## 13. Decisiones pendientes antes de desarrollar

- Tecnología de Broadcasting disponible en el hosting actual de SIRICO.
- Confirmar si el servidor permite operar Laravel Reverb; si no, elegir Pusher o Ably para el MVP.
- Servicio y credenciales definitivas para push en iOS y Android.
- Límite máximo y tipos permitidos para archivos adjuntos.
- Usuarios o roles iniciales con permisos para crear grupos.
- Personas autorizadas para auditoría y exportación.
- Política institucional de retención de mensajes y archivos.
- Si imágenes y PDF entran en la primera entrega o en una iteración inmediata posterior.

## 14. Estimación inicial

Para un MVP con chat privado, grupos, permisos, sincronización en tiempo real, web, Ionic, push, auditoría y archivos básicos, la estimación preliminar es de **3 a 5 semanas de desarrollo y pruebas**, dependiendo principalmente de la infraestructura de tiempo real disponible, el estado actual de las notificaciones push y la profundidad de la interfaz web.

Se recomienda dividir el trabajo en entregas internas: primero persistencia y API, después web e Ionic, y finalmente tiempo real, push, adjuntos y endurecimiento de auditoría.
