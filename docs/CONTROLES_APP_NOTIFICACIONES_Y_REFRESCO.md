# Cabeceras, notificaciones, salida y refresco de consultas

Fecha: 2026-11-02. Implementación local; prueba funcional y visual del usuario pendiente.

## Cambios visibles

- Los indicadores de estado de máquinas/vehículos comparten el color del texto de
  su badge: operativo/activo verde, reparación/taller naranja y fuera de servicio/baja rojo.
  Se corrige la prioridad CSS: el selector base adquiría más especificidad con los
  atributos de encapsulación de Angular y dejaba todos los puntos grises.
- Se retiran las recargas convencionales de las cabeceras y el botón «Actualizar
  información» de los detalles. También se retiran los iconos de recarga del
  selector de panel y reposición de gastos.
- Salida del mecánico en la barra inferior, inmediatamente después de Servicios,
  con `log-out-outline`, texto Salir y confirmación. Se incorpora la salida al
  final de la barra gerencial; el residente conserva su salida inferior existente.
- La campana sustituye la salida superior en consultas del mecánico y su detalle
  compartido de vehículos. Se mantiene Acceso cuando el usuario tiene varios paneles.
- No se alteran los iconos ni el contenido de los menús de perfil gerenciales.
- La verificación de una operación cuyo resultado es incierto se conserva como
  «Verificar operación», visible únicamente en ese caso. No es una recarga general:
  permite resolver el estado antes de volver a enviar una operación.

## Pull to refresh

Se conserva el gesto con logo corporativo en consultas mecánicas y detalle
compartido de vehículos. Se completa en estas consultas adicionales:

- Gerencial: dashboard; índices de máquinas, vehículos, obras, empleados e inventario.
- Gerencial: detalle de máquina, empleado, obra, pilas de obra, registros de máquina
  por obra y producto/kardex.
- Gerencial: reporte de checadas y resumen del empleado.
- Residente: detalle de asistencia del empleado, registros de máquina y vehículo,
  lista de comisiones y reposición de gastos.
- Selector de panel.

Los refrescos de consulta comparten `RefrescoConsultaService`, que vincula la
finalización del indicador y del loader con el callback/finalización de la consulta
real. Se liberan también al navegar o destruir la vista. No se disparan GET duplicados
para simular una recarga, ni se usa un temporizador como señal de finalización.

Las listas acumuladas reinician su consulta a la primera página con los filtros
vigentes. Los historiales del detalle de vehículos mantienen su gestión independiente.
Se bloquea el gesto al abrir formularios/modales, durante guardados y cuando hay
capturas pendientes. Login, formularios de edición y vistas estáticas no reciben un
gesto que descarte lo escrito; las pantallas civiles que ya tenían refresco lo conservan.

## Notificaciones personales

Rutas nuevas bajo Sanctum:

- `GET /api/v1/app/notificaciones?page=1&per_page=20`.
- `POST /api/v1/app/notificaciones/{id}/leer`.

El controlador valida usuario móvil activo y acceso a al menos uno de los paneles
existentes. Las consultas y marcas de lectura siempre se realizan mediante la
relación `notifications()` del usuario autenticado. No se acepta un ID de usuario
del cliente. No se crea ninguna notificación ni se envían mensajes.

La respuesta incluye título, mensaje, fechas, paginación y conteo de no leídas.
Se reutilizan las claves `title/titulo` y `message/mensaje` ya usadas por Laravel.
La UI muestra texto escapado, sin HTML ni aperturas automáticas de enlaces web.

La campana abre un modal con historial paginado, pull to refresh, errores con
reintento y acción explícita «Marcar como leída». El contador refleja la última
consulta de esa campana; no es push en tiempo real. No se ejecuta polling al abrir
cada cabecera ni se marcan avisos al solo abrir el modal.

## Archivos y verificaciones

Backend: `AppNotificationController.php` y rutas en `routes/api.php`.
App: componente `app-notificaciones`, servicio `refresco-consulta`, barra mecánica,
barra gerencial, vistas compartidas y consultas enumeradas arriba.

- [x] Compilación Angular development de los tres paneles.
- [x] Sintaxis PHP y registro de las dos rutas nuevas bajo Sanctum.
- [x] Revisión de mojibake y formato de archivos.
- [x] Barrido de plantillas: sin botones convencionales de Actualizar ni iconos de recarga.
- [ ] Usuario: comprobar colores, gesto y logo, salida inferior y confirmación.
- [ ] Usuario: consultar notificaciones reales y marcar una como leída con una cuenta móvil.
- [ ] Usuario: comprobar que formularios/capturas pendientes bloquean el refresco.

No se ejecutaron pruebas funcionales, no se modificaron notificaciones reales ni
se hicieron migraciones, asignaciones de permisos o despliegues.
