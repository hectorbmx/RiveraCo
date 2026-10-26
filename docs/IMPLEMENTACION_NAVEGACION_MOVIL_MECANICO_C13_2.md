# T13 / C13.2. Navegación y consulta inicial del panel mecánico

Fecha: 2026-10-23. Implementación en `C:\xampp\htdocs\rivera\app`, repositorio móvil separado del backend. No se realizó commit/push; la publicación final sigue condicionada a completar el alcance autorizado.

## Cambios

- Nueva familia `/tabs-mecanico` con shell Ionic y tabs de máquinas, vehículos y servicios.
- Guard de hijos revalida la sesión mediante `me`, comprueba panel/permiso mecánico, selecciona ese panel y limpia obra antes/después de hidratar la respuesta. No confía únicamente en permisos almacenados.
- Una ruta directa sin token vuelve a login; 401/403 de la validación de sesión limpian sesión; permiso revocado con otra sesión válida vuelve a home para resolver los accesos restantes. Fallos de red avisan y permiten regresar al selector sin asumir acceso.
- Shell principal y selector reconocen `mecanico`; si es el panel único se abre directamente y si hay varios accesos sin selección se conserva el selector. No necesita obra asignada.
- Guards del árbol residente y de captura de máquina/vehículo evitan enviar al mecánico exclusivo a sus pantallas. Se mantiene el acceso legacy gerencial; destinos gerenciales superiores de obras/máquinas/vehículos/inventario siguen bajo su permiso propio donde se modificaron rutas. La autorización efectiva de todas las operaciones continúa en backend.
- ApiService conserva `Error.message` y añade `status` y cuerpo `error` de Laravel, para manejar 401/403/409/422 sin romper consumidores existentes que leen el mensaje.

## Consultas iniciales incluidas

Para que cada destino sea funcional desde esta entrega se agregó un cliente tipado con rutas relativas `/mecanico/*`, sin reutilizar el paginador o endpoints gerenciales:

- Máquinas/vehículos: búsqueda, paginación, detalle operativo e historial reciente. Los programados del historial abren el detalle de seguimiento; las emergencias se muestran como resumen sin solicitar un detalle fuera del alcance de la API mecánica.
- Servicios: listado paginado, búsqueda, estado y solo propios; detalle con equipo, mecánico, lecturas, costo de consulta, orden, inclusión/motivos de materiales, avance, solicitudes, documentos/compras y fotos.
- Controles de estado vacío, carga, error/reintento, regreso, cambio de acceso y cierre de sesión con confirmación.
- Un contador de carga descarta respuestas antiguas después de otra búsqueda, cambio de ruta o destrucción del componente.
- No se inyecta una obra residente en consultas mecánicas. No se agregan botones para acciones aún no conectadas en el cliente.

Son consultas iniciales de C13.3; no se da por completa la captura/operación de C13.4 ni la ejecución C13.5/T12. Todavía faltan filtros de fechas/equipo, pruebas de vistas con datos representativos y recorrido conectado con usuario real.

## Archivos principales del cliente

- `src/app/guards/app-shell.guard.ts`, `mecanico.guard.ts`, `operativo-panel.guard.ts`, `gerencial-guard.ts`.
- `src/app/app.routes.ts` y `pages/selector-panel/selector-panel.page.ts`.
- `src/app/mecanico/mecanico.routes.ts`, `mecanico-shell.page.ts` y `mecanico-consulta.page.*`.
- `src/app/services/mecanico-consulta.service.ts` y ajuste compatible de errores en `services/api.ts`.
- `src/app/mecanico/mecanico-navigation.spec.ts`.

Las escrituras al cliente se realizaron mediante la aprobación de sandbox para ese repositorio, ya que está fuera de la raíz editable del chat. Se comprobó árbol limpio antes de aplicar y se prepararon los archivos/contextos antes de modificar. No se cambiaron hosts, dependencias ni archivos Android.

## Validación

- `node node_modules/typescript/bin/tsc -p tsconfig.app.json --noEmit --incremental false`: exit 0.
- El mismo comando sobre `tsconfig.spec.json`: exit 0.
- Build Angular `--configuration development`: exit 0, incluidas plantillas estrictas. Se usó salida temporal en `rivera-v2/storage/app/codex-mobile-build`, retirada al cierre sin reemplazar `app/www`. Se observaron advertencias de imports/optional chaining y Stencil en componentes existentes; no se hizo una limpieza ajena al módulo.
- Karma/Chrome Headless: **11 pruebas exitosas**, filtradas por `src/app/mecanico/mecanico-navigation.spec.ts`.
- Cobertura: panel mecánico único sin obra, selección mecánica entre varios, selector sin elección, compatibilidad residente, revalidación por ruta directa, permiso revocado, 401, aislamiento del árbol residente, selector mecánico, familia `/mecanico` sin obra automática y preservación de errores 422.
- `git diff --check` y revisión UTF-8/mojibake de archivos tocados.

No se utilizaron credenciales reales, no se escribieron datos de API, no se ejecutó cámara Android ni se instaló un APK. Estas comprobaciones no sustituyen la validación integral T14. El cliente no tiene grafo graphify y el grafo del backend no indexa este repositorio; los archivos se localizaron mediante la inspección documentada C13.1.

## Siguiente entrega

Continuar C13.3 (filtros y pruebas de vistas) y C13.4 (catálogos/formulario/operaciones/evidencias) con los contratos ya disponibles T05–T11. La ejecución permanece pendiente de la respuesta D05 y de T12, sin asumir una decisión por el paso del tiempo.
