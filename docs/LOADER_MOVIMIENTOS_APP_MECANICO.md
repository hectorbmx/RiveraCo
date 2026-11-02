# Loader corporativo en movimientos del mecánico

Fecha: 2026-10-30. Código móvil implementado; pruebas funcionales a cargo del usuario.

Se reutiliza el `LoadingController` de Ionic con `cssClass: company-loading`, spinner crescent, logo Rivera y fondo difuminado ya definidos en `src/global.scss`, como en el registro de asistencia de la app. No se crean assets ni estilos nuevos.

Un interceptor limitado a POST/PUT/PATCH/DELETE de `/api/v1/mecanico/` muestra el loader al comenzar la petición y lo finaliza al completarse, fallar o cancelarse la suscripción. Las confirmaciones ocurren antes de enviar y no quedan tapadas por el loader. GET de búsqueda y paginación conservan sus indicadores actuales. Las actualizaciones manuales incorporadas el 2026-11-02 usan el mismo servicio de forma explícita, como se detalla abajo.

Mensajes según operación: guardar borrador/cambios de servicio, generar orden, ajustar materiales, solicitar abastecimiento, iniciar/completar servicio, guardar evidencias, horas, asignación, retiro al patio y ubicación. El loader no altera payloads, autenticación, permisos, validaciones ni recuperación de resultados inciertos.

El servicio conserva referencias de solicitudes concurrentes y serializa presentación/cierre del overlay: no cierra hasta que finalice la última petición activa. Solo descarta su propio overlay, sin cerrar loaders ajenos de otras pantallas. Si falla la presentación visual, la operación sigue su curso sin repetirse ni bloquearse por ese error visual.

## Archivos móviles

- `C:/xampp/htdocs/rivera/app/src/app/services/mecanico-loading.service.ts`
- `C:/xampp/htdocs/rivera/app/src/app/interceptors/mecanico-loading.interceptor.ts`
- `C:/xampp/htdocs/rivera/app/src/main.ts`

Se conserva `withInterceptorsFromDi()` al registrar el interceptor funcional nuevo.

## Comprobaciones

TypeScript, compilación Angular de desarrollo, UTF-8/mojibake y formato revisados. Advertencias existentes de otras pantallas/Stencil. Sin pruebas funcionales ni movimientos operativos, cambios de backend, migraciones, permisos, despliegue ni reemplazo de `www`. Salida temporal de compilación retirada.

## Ampliación: pull to refresh y Actualizar — 2026-11-02

- Los listados/detalles del mecánico, productos y detalle compartido de vehículos
  usan el loader corporativo al actualizar mediante el gesto o los botones de
  actualización disponibles en esas pantallas.
- La llamada explícita a `MecanicoLoadingService.iniciar` envuelve toda la carga,
  no cada GET individual; conserva el contador de solicitudes del servicio.
- En vehículos, el botón superior «Actualizar», «Actualizar información» y el
  gesto comparten la misma función. El logo permanece hasta terminar la ficha y
  los historiales abiertos, conservando filtros, páginas y desplegables.
- Finalización en `finally` y al abandonar/destruir la pantalla, sin cerrar
  overlays ajenos. Los intentos bloqueados por captura pendiente no abren loader.
- Las búsquedas, cambios de filtro y paginación no activan este overlay.
- No se modifica el interceptor de escrituras ni el CSS/asset del logo.

Compilación Angular development y mojibake correctos. Prueba del loader y gesto
en dispositivo pendiente del usuario; sin pruebas funcionales automáticas.

## Navegación entre vistas — 2026-11-02

La app también reutiliza este servicio para cambiar de pantalla o pestaña. La
coordinación global de Angular, Ionic y consultas iniciales está documentada en
[LOADER_NAVEGACION_APP_MOVIL.md](LOADER_NAVEGACION_APP_MOVIL.md). El interceptor de
movimientos y su alcance de escrituras se conservan.
