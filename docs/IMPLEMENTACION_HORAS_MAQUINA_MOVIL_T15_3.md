# T15.3: captura móvil de horas de máquina

- Fecha: 2026-10-29.
- Cliente: `C:\xampp\htdocs\rivera\app` (Ionic/Angular).
- Contrato: [operaciones T15](CONTRATO_OPERACIONES_MAQUINAS_MECANICO_T15.md).
- Alcance: modal de horas/ajuste e integración en listado y detalle. No se implementan aquí operaciones de ubicación, permisos nuevos ni cambios del backend.

## Comportamiento

- Las tarjetas muestran «Registrar horas» o «Ajustar horómetro» cuando la sesión contiene `maquinas.horas.create.access`; no se exige empleado vinculado para esta operación. La API vuelve a autorizar al abrir y al guardar.
- Al abrir se consulta `opciones-operacion`. El modo del formulario y su habilitación se toman del contexto/capacidades actuales del servidor, no solo de la etiqueta o asignación que se veía en el listado.
- Se presentan máquina, obra, referencia de lectura y última lectura. Con asignación se capturan inicio/fin; sin asignación, fecha civil y obra relacionada opcional. Las notas admiten 500 caracteres.
- El usuario captura el nuevo horómetro, no la diferencia. Validación cliente de lectura mayor a la referencia, rango de `DECIMAL(10,2)`, hasta dos decimales, fechas reales y fin no anterior al inicio. El servidor conserva la autoridad sobre secuencia histórica, permisos y contexto.
- Una referencia cero se trata como válida; referencia nula permanece nula en el payload y se interpreta aritméticamente como cero al validar incremento, conforme al backend. No se reemplaza por `horometro_base`.
- Las horas civiles se convierten a ISO con offset de Ciudad de México independientemente de la zona del teléfono; se consideran offsets históricos mediante `Intl`. El ajuste conserva `YYYY-MM-DD` sin conversión de zona.
- La búsqueda de obras usa `uso=ajuste`, texto y paginación. Conserva la selección fuera de la página y no reemplaza el contexto de una captura en curso con el contexto de las búsquedas auxiliares. No asigna la máquina a la obra seleccionada.
- Tras éxito se aplican lectura, última fecha, preventivos y asignación devueltos a la tarjeta/detalle. No se recarga el catálogo completo ni se pierden búsqueda/página; se informa si fue un reintento recuperado.

## Errores, recuperación y sesiones

- El envío queda bloqueado mientras guarda; también se impide cerrar el modal durante ese envío. Los errores 422 se muestran por campo y permiten corregir.
- Un 409 bloquea una nueva captura hasta consultar los datos actuales y revisarlos; esa consulta no reenvía automáticamente ni conserva como válido un contexto viejo.
- Antes de enviar se conserva el payload por usuario y máquina. Desconexión, timeout o resultado incierto mantienen la copia original, bloquean edición y ofrecen «Reintentar misma captura». No se regeneran fechas, notas, obra ni referencia al reintentar.
- La copia se guarda en `sessionStorage`, con respaldo en memoria si el almacenamiento está restringido. Permite cerrar/reabrir o recargar en la misma sesión de navegador. No se promete persistencia tras desinstalar la app, borrar sus datos o perder el almacenamiento de la sesión.
- El reintento usa la huella/idempotencia de T15.2. El POST nunca se reemplaza por un GET que suponga éxito solo porque la lectura parece coincidir.
- Un 401 inicia logout; un 403 bloquea los envíos y conserva la captura pendiente de confirmación para recuperarla con la sesión autorizada. Los pendientes de otro usuario/máquina no se restauran.
- Se descartan respuestas de otra máquina, otra sesión o un componente destruido. Las consultas se cancelan al destruirlo; un POST cancelado conserva su payload porque el servidor pudo haberlo procesado.
- Las operaciones de consulta/envío tienen timeout de 30 segundos. No hay reintentos de escritura automáticos.

## Archivos

- `src/app/services/mecanico-maquina-horas.service.ts` y `.spec.ts`: DTO, transporte, fechas y recuperación.
- `src/app/mecanico/mecanico-maquina-horas.component.ts`, `.html`, `.scss`, `.spec.ts`: modal real de Ionic y formulario.
- `src/app/mecanico/mecanico-maquina-card.component.*`: acción compacta de captura por permiso y máquina.
- `src/app/mecanico/mecanico-consulta.page.*`: integración y actualización de lista/detalle.

## Validación

- **112 pruebas exitosas en Chrome Headless** del módulo mecánico y contrato de horas, incluidas 22 pruebas nuevas: modal/formulario, validaciones, transporte, recuperación, permisos, errores, respuestas antiguas, selección de obra y aplicación del resultado sin perder filtros.
- Pruebas renderizadas con componentes Ionic reales, incluido el contenido del modal montado en el documento. No se sustituyeron sus controles por mocks de HTML.
- Compilación completa de desarrollo con plantillas estrictas y verificación UTF-8/mojibake de los archivos tocados. Permanecen advertencias previas en componentes ajenos al cambio.
- Respuestas controladas y HTTP interceptado en pruebas: no se registraron horas ni ajustes en la base operativa y no se usaron credenciales de una cuenta real.
- Sin despliegue ni modificación de `app/www`. La compilación se genera en un directorio temporal y se retira al cerrar la entrega.

T15.3 queda completado con pruebas controladas. El recorrido conectado con un usuario autorizado y el teléfono real sigue en T15.6/T14. T15.4 (asignar/retirar) requiere la confirmación del usuario antes de comenzar; D09 permanece abierto para cambios directos de ubicación.
