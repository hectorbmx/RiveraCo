# T13 / C13.3. Consultas, filtros y pruebas de vistas

Fecha: 2026-10-23. Implementado en el repositorio móvil `C:\xampp\htdocs\rivera\app`, sobre la navegación y consultas iniciales de C13.2.

## Cambios de esta entrega

- Seguimiento por fecha programada desde/hasta, conservando búsqueda, estado y solo propios. Se pueden usar los extremos por separado; se rechazan fechas inexistentes o invertidas antes de llamar a la API.
- Filtro por un equipo específico: elegir catálogo de máquinas o vehículos, buscar por nombre/código/placas y seleccionar una opción. No requiere capturar un ID técnico ni estar asignado a una obra.
- Selector de equipos paginado, sin descargar el catálogo completo. Cambiar de catálogo limpia la selección previa y solo se envía `maquina_id` o `vehiculo_id`, nunca ambos.
- La selección permanece visible al buscar o paginar otras opciones. Una búsqueda auxiliar fallida no elimina los servicios ya consultados.
- Limpiar filtros restablece búsqueda, estado, fechas, selección y solo propios; cambiar filtros consulta desde la primera página. La paginación de servicios conserva los filtros elegidos.
- Botón Actualizar en cabecera para recargar la consulta vigente. Manejo de resultados vacíos, errores/reintento y descarte de respuestas anteriores a una nueva búsqueda.

El detalle operativo de C13.2 permanece de solo lectura: equipos e historial, servicios programados, orden, materiales incluidos/excluidos y motivos, avance, solicitudes, documentos/compras vinculados, evidencias y costos. Las emergencias del historial no enlazan a un detalle programado fuera de alcance.

## Archivos del cliente

- `src/app/mecanico/mecanico-consulta.page.ts`.
- `src/app/mecanico/mecanico-consulta.page.html`.
- `src/app/mecanico/mecanico-consulta.page.spec.ts` (nuevo).

Las escrituras fuera de la raíz del backend utilizaron revisión de sandbox para el repositorio móvil autorizado. Durante el trabajo la revisión automática no pudo ejecutarse por límite de uso y no aplicó el ajuste pendiente; después del horario indicado de restablecimiento se reintentó la misma acción, con aprobación y validación satisfactorias. No se eludió el control ni se marcaron resultados pendientes como aprobados.

## Validación

- **24 pruebas exitosas en Chrome Headless:** las 11 de navegación/transporte y 13 nuevas de vistas con RouterTestingHarness, componentes Ionic reales y respuestas controladas.
- Casos nuevos: lista paginada y estados, combinación de fechas/propios/estado/equipo, fechas inválidas sin consulta, búsqueda/paginación de equipos, selección persistente, limpieza, renderizado de orden/materiales/avance/documentos/evidencia/costos, vehículo e historial, vacío/error/recuperación, respuestas antiguas, 401, 403 y fallo auxiliar sin perder seguimiento.
- TypeScript de app y specs: `--noEmit --incremental false`, exit 0.
- Build Angular de desarrollo con plantillas estrictas: exit 0. Salida temporal fuera del backend y de `app/www`; sin despliegue. Se mantienen advertencias de componentes existentes ajenos a esta entrega.
- `git diff --check` y UTF-8/mojibake de los archivos modificados: correctos.
- Sin llamadas de escritura a la API, sin credenciales de un usuario real y sin cambios de configuración de hosts/dependencias.

Las pruebas de navegador verifican renderizado e interacción con contratos representativos; no sustituyen el recorrido conectado con un usuario autorizado ni la validación Android/cámara de T14. El cliente sigue sin grafo propio; el grafo del backend se mantiene libre de salidas temporales generadas.

## Pendiente siguiente

C13.4: catálogos y captura de borradores, generación de orden, ajuste de materiales, solicitudes y carga de evidencias con sus contratos T05–T11. Ejecución C13.5 depende de T12 y de la respuesta D05 sobre materiales pendientes. T13 y la entrega integral continúan abiertas; no corresponde commit/push final todavía.
