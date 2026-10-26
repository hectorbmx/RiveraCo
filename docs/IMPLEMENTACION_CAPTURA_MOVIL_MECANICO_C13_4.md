# T13 / C13.4. Captura y operaciones de servicios programados

Fecha: 2026-10-23. Cliente: `C:\xampp\htdocs\rivera\app`, repositorio separado del backend.

## Implementado

- Acceso a Programar servicio desde seguimiento y desde el detalle de máquina/vehículo, cuando la sesión tiene empleado. Formulario propio para crear y editar borradores mediante T06, con búsqueda y paginación de un solo equipo, tipos preventivos activos, lectura sugerida, categoría, fecha/hora, descripción y lecturas del equipo.
- Tipos/materiales/avisos provienen de T05. Los materiales sugeridos son informativos y el snapshot se fija en servidor al generar la orden. Los vehículos muestran las capacidades reales y no pueden guardarse sin tipo preventivo disponible.
- El servidor asigna el empleado del usuario. No se envían mecánico, estado, precios ni costos editables. La fecha se presenta en America/Mexico_City y se envía como hora local del formulario, de acuerdo con Laravel. La obra opcional se omite en altas y se conserva en ediciones; no se agrega un selector con un catálogo inexistente en estos contratos.
- Detalle con acciones condicionadas por `acciones_permitidas`: editar borrador, generar orden con confirmación, ajustar inclusión/motivo de materiales y solicitar almacén o compra con confirmación. No se modifican cantidades, productos o precios del snapshot desde la app.
- Consulta paginada de productos activos de Giralda por nombre/SKU, unidades, stock actual/reservado/disponible y filtro de disponibilidad. Incluye productos sin registro de stock; no permite editar catálogo/inventario ni añadir productos arbitrarios a la orden.
- Evidencias mediante selección de archivos o cámara bajo acción explícita, vista previa, descripciones y retiro de la selección. Comprueba formato JPG/PNG/WebP, 10 MB por archivo y ocho fotos acumuladas. Multipart con índices `fotos[n]`/`foto_descripciones[n]`, sin Content-Type JSON ni obra automática. La optimización WebP continúa en el servidor T11.
- Bloqueo durante confirmaciones/escrituras, conservación de captura ante validación 422, relectura tras conflicto 409 y manejo de sesión/permisos. Un alta o carga de fotos con respuesta incierta no se repite automáticamente; exige revisar lo que pudo haberse guardado.
- Reingreso en vistas conservadas por Ionic: el detalle se actualiza al volver de edición, los borradores vuelven a validar sus acciones y una creación exitosa no deja bloqueada la siguiente programación. Una creación incierta conserva su bloqueo.

No se publicaron acciones de iniciar/completar ni cancelación móvil. T12/C13.5 continúan sujetos a D05; cancelación sigue solo web según D04. No se amplió el abastecimiento automático de vehículos.

## Archivos del cliente

- Nuevos: `services/mecanico-operacion.service.ts`, `services/mecanico-foto.service.ts`.
- Nuevos en `src/app/mecanico/`: `mecanico-servicio-form.page.ts/html`, `mecanico-operaciones.component.ts/html`, `mecanico-productos.page.ts`, `mecanico-captura.spec.ts`.
- Integración: `mecanico.routes.ts`, `mecanico-consulta.page.ts/html`, `mecanico-consulta.page.spec.ts` y DTO en `services/mecanico-consulta.service.ts`.

Las rutas específicas de alta/edición se registran antes del detalle genérico. Las escrituras al repositorio móvil utilizaron revisión del sandbox y comprobación previa de contextos; no se reemplazaron archivos ajenos. El instalador inicial se detuvo por un contexto HTML ambiguo antes de cualquier escritura; se acotó ese contexto y se aplicó correctamente.

## Validación y límites

- **47 pruebas exitosas en Chrome Headless**: 23 adicionales sobre las 24 de C13.3. Cubren formularios renderizados, selección/catálogos, permisos revocados, campos enviados, zona horaria, doble envío, errores, confirmación cancelada, materiales, solicitudes, fotos/previews, transporte multipart, productos y reingreso en vistas Ionic.
- TypeScript de app y specs con `--noEmit --incremental false`: exit 0.
- Build Angular de desarrollo con plantillas: exit 0; salida temporal fuera de `app/www`, sin despliegue. Advertencias existentes ajenas al módulo conservadas.
- Diff comprobado al cierre. `php tools/check_mojibake.php` pasó en los documentos; los 16 archivos móviles del módulo/servicios pasaron además decodificación UTF-8 estricta y búsqueda de los patrones de mojibake, porque el comprobador PHP no incluye extensiones `.ts`/`.html`. Grafo backend actualizado después de retirar temporales. El cliente no tiene un grafo propio.
- No se hicieron llamadas de escritura a la API real ni cambios de hosts o dependencias. Las respuestas HTTP y la cámara en las pruebas están controladas. Cámara nativa, permisos del dispositivo, usuario real y recorrido conectado siguen pendientes de T14.

T13 permanece parcial hasta C13.5 y el recorrido integral. No corresponde commit/push final mientras T12/T14 y las decisiones indispensables sigan pendientes. Al publicar, revisar backend y cliente como repositorios separados.

## Cierre del checkpoint

Cierre: 2026-10-23T17:22:40.1244914-06:00 (America/Mexico_City). No iniciar el siguiente punto antes de 2026-10-23T17:32:40.1244914-06:00. Grafo backend actualizado: 7703 nodos y 13344 relaciones; HTML omitido por el límite de tamaño. Continúan avisos de fuentes de configuración sin nodos y parser SQL ausente; no impidieron actualizar el grafo de código.
