# T11. Evidencias fotográficas del servicio

Fecha: 2026-10-23. Backend implementado; integración de cámara/galería en el cliente pendiente de T13.

## Contrato multipart

`POST /api/v1/mecanico/servicios/{servicio}/evidencias`

Cabeceras: `Authorization: Bearer <token>` y `Accept: application/json`. Enviar como `multipart/form-data`; el cliente debe permitir que su librería genere el boundary.

| Campo | Tipo / regla |
|---|---|
| `fotos[0]`, `fotos[1]`, etc. | Archivos nuevos; entre 1 y 8 por petición, sujetos al límite acumulado. |
| `foto_descripciones[0]`, etc. | Texto opcional por el mismo índice, máximo 200 caracteres; null/vacío admite foto sin descripción. |

Ejemplo conceptual de los campos: `fotos[0] = foto.jpg`, `foto_descripciones[0] = Inspección del motor`. No se reciben rutas, URLs, IDs de fotos existentes, mecánico, estado, lecturas, costos ni otros campos. Una descripción cuyo índice no tenga archivo se rechaza.

- Entradas admitidas: JPG/JPEG, PNG y WebP. Se valida que sean imágenes, formato permitido y máximo 10 MB (10 240 KiB) por archivo. PDF/GIF y archivos inválidos se rechazan.
- Máximo **ocho fotografías acumuladas por servicio**, contando las guardadas desde web y API. La cantidad se relee bajo bloqueo del mantenimiento antes de escribir.
- Conversión real mediante `ImageOptimizerInterface::optimize`: WebP, calidad 80, máximo 1920 × 1920, manteniendo proporción, sin ampliar imágenes pequeñas.
- Almacenamiento en el disco `public`, carpeta `mantenimientos/evidencias`, nombre UUID generado en servidor y extensión `.webp`. No se utiliza el nombre original como ruta.
- Es una operación de agregar fotos. No modifica, elimina ni sustituye evidencias existentes y no implementa idempotencia del lote: reenviar un lote exitoso agrega nuevas fotos mientras haya capacidad.

Respuesta `201`: `{ok: true, data: ...}` con el detalle completo de seguimiento T07. `data.evidencias` contiene todas las fotos del servicio, ordenadas por ID:

```json
{
  "id": 75,
  "descripcion": "Inspección del motor",
  "url": "https://servidor.example/storage/mantenimientos/evidencias/uuid.webp"
}
```

El ejemplo ilustra el objeto de evidencia. La URL real es absoluta, usa la configuración del disco público y el host de la aplicación. No se expone la ruta interna como campo adicional. Se conserva el esquema público utilizado por las fotos web; se requiere el enlace/configuración de storage habitual del proyecto.

## Permisos y etapas

Sanctum, usuario app activo y `app.mecanico.access`. Debe existir un empleado vinculado; el servicio debe ser programado y pertenecer a ese mecánico, sin necesidad de obra asignada.

Regla móvil actualizada el 2026-10-28: requiere orden con folio, inicio registrado, estado `en_proceso` o `completado` y surtido completo de los materiales incluidos. Las partidas excluidas no bloquean. Una compra o recepción no sustituye la atención de las cantidades requeridas por el servicio. Si no hay materiales incluidos, no se exige una compra artificial. Ajuste web del 2026-10-29: los servicios completados o cancelados quedan en modo consulta, incluida la carga de fotografías desde su formulario de edición.

`acciones_permitidas.agregar_evidencias` se calcula en listado y detalle como servicio propio, con menos de ocho fotos y cumplimiento de la etapa y surtido anteriores. Se usa `withCount` para no traer imágenes por fila; la operación vuelve a comprobar propiedad, etapa, surtido y capacidad bajo bloqueo del mantenimiento, antes de procesar archivos. Un incumplimiento de etapa o surtido devuelve HTTP 409. Subir fotos no modifica la ejecución.

## Guardado compartido y limpieza

`MantenimientoEvidenciaService` es el único guardado de fotos nuevas para esta API y la edición web:

1. Valida archivos/descripciones y campos permitidos.
2. Inicia transacción y bloquea/relee el mantenimiento; valida tipo/propiedad en API y cantidad acumulada para ambos clientes.
3. En la edición web, guarda también cabecera/conceptos/costos del formulario dentro de esa transacción cuando viene un lote de fotos. La API solo agrega las evidencias.
4. Optimiza, registra la ruta prevista antes de escribir el archivo, verifica el resultado de `put` y crea el registro de foto.
5. Ante una excepción de optimización, escritura o SQL, revierte el lote de registros y elimina únicamente las rutas nuevas del intento. Se conservan archivos/fotos anteriores. Una escritura parcial también queda identificada para limpieza.

No se reintenta automáticamente esta transacción para evitar repetir efectos de archivos. Si el almacenamiento impide también el borrado de compensación, se registra el error y las rutas para su revisión, conservando la excepción original. La compensación cubre fallos controlados por la aplicación; no constituye una transacción distribuida ante una terminación abrupta del proceso.

La web conserva su formulario, permisos de edición, redirect y presentación de evidencias. El lote se publica en las mismas filas `mantenimiento_fotos`, por lo que fotos web/API aparecen en ambas consultas.

## Errores

| HTTP | Caso |
|---|---|
| 401 | Sin autenticación. |
| 403 | Sin acceso mecánico/usuario activo o servicio ajeno. |
| 404 | Servicio inexistente o emergencia fuera del alcance móvil. |
| 409 | Servicio sin orden/inicio, cancelado, en una etapa previa o con materiales incluidos pendientes de surtir. |
| 422 | Sin empleado vinculado, archivos/descripciones inválidos, campos adicionales o límite acumulado excedido. |
| 500 | Fallo inesperado de procesamiento, almacenamiento o persistencia; lote revertido y limpieza compensatoria intentada. |

Los límites HTTP/PHP de tamaño total de petición continúan dependiendo del despliegue; el contrato por archivo no modifica php.ini.

## Validación

Nueve pruebas nuevas sobre SQLite efímero y disco de pruebas aislado:

- Conversión real de PNG/JPG a WebP, límite de dimensiones, no ampliar imágenes pequeñas y URLs consistentes con GET.
- Límite acumulado, rechazo previo a escribir y acción deshabilitada al llegar a ocho.
- Formatos/tamaño/descripciones/campos protegidos y cantidad por lote.
- Propiedad, tipo, empleado vinculado, permisos e inexistencia sin escritura.
- Conservación de etapas al cargar; en móvil solo se admite ejecución iniciada y surtida, también completada; los cancelados se rechazan. En web se bloquea la edición y carga al cerrar el servicio.
- Fallo del optimizador en la segunda foto: rollback y conservación de evidencia anterior.
- Trigger SQL en la segunda foto: rollback del lote y limpieza de todos sus archivos.
- Edición web compartida y rollback de cabecera al fallar la foto.
- Escritura parcial del disco que devuelve false: detección, limpieza y ausencia de filas huérfanas.

Suite móvil completa: **89 pruebas / 877 comprobaciones**, `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobile --no-progress`. Sin escribir en la base local ni en las fotos reales; Storage fake y SQLite en memoria. Sintaxis/rutas/UTF-8 verificados y graphify actualizado al cierre. Prueba de cargas simultáneas sobre InnoDB pendiente para T14.

Siguiente: T12. Resolver D05 antes de habilitar inicio/cierre si no existe una decisión suficiente sobre materiales; no inferir esa regla de esta carga de fotos.

## Checkpoint del 2026-10-28

- Cabecera del servicio al inicio; después acciones, ajustes/materiales y solicitudes, ejecución y evidencias. Las cabeceras de acciones, ejecución y carga de evidencias usan azul de la app con texto blanco.
- El card de carga permanece visible y explica el bloqueo. Archivo, cámara y envío respetan la acción autorizada por la API. Las fotos existentes siguen disponibles para consulta.
- La carga móvil exige inicio y surtido completo; no se exige pasar por Compras si almacén puede surtir directamente. Inicio con materiales pendientes conserva su comportamiento anterior.
- Validación local: 68 pruebas del backend, 716 comprobaciones; 29 pruebas de captura móvil y compilación Angular de desarrollo correctas. Sintaxis PHP y UTF-8 verificados. No requiere migración ni cambios a registros reales.
