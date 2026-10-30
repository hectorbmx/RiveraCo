# D09: formulario móvil de cambio de ubicación

Fecha: 2026-10-29. Código implementado. Pruebas funcionales a cargo del usuario; grafo diferido al cierre.

## Uso

Desde el listado y el detalle de Máquinas aparece «Cambiar ubicación» cuando la sesión tiene `maquinas.ubicacion.update.access`. Al abrir consulta opciones actuales: máquina, asignación, contexto de ubicación, destinos y capacidad efectiva. La autorización final corresponde al backend; si el permiso o el contexto ya no permiten el cambio, el formulario no habilita el envío.

La pantalla muestra código/nombre de máquina, obra vinculada y ubicación actual. Permite elegir únicamente destinos entregados por el servidor, capturar motivo obligatorio (190 caracteres) y notas opcionales (2000). No selecciona automáticamente un destino.

Antes de enviar muestra una confirmación con el destino, conservación de la asignación si existe y conservación del estado operativo. El nombre de la obra aparece en el resumen y el aviso del formulario. Sin asignación, aclara que el cambio no crea una. Con asignación, indica que el retiro explícito es necesario para enviarla al patio y cerrar la obra.

La fecha y el responsable son registrados por el servidor. La interfaz no permite editar estado operativo, lecturas, obra destino ni fecha del movimiento. Reutiliza el modal y los estilos azules de captura de horas, con campos adaptables al ancho móvil.

## Contrato

- GET `/api/v1/mecanico/maquinas/{id}/opciones-operacion`, usando `contexto_ubicacion` y `ubicaciones_permitidas` separados del contrato anterior de horas/asignación.
- PATCH `/api/v1/mecanico/maquinas/{id}/ubicacion`, con copia del contexto original, destino, motivo y notas. Conserva referencias nulas y la lectura decimal tal como se recibió.
- Respuesta actualiza la tarjeta y el detalle sin perder búsqueda/página. No se cambia el contrato de los formularios anteriores.
- Errores 422 por campo. Un 409 bloquea nuevos envíos hasta consultar datos actuales y revisar el destino; conserva motivo/notas para corregir. Si el destino ya no es permitido, obliga a elegirlo de nuevo.
- 401 cierra sesión y 403 bloquea el envío. Las respuestas de otra máquina, captura o sesión no se aplican.
- Si el backend aún no entrega el contrato D09, se informa que el cambio no está disponible; no se inventan destinos de respaldo.

## Recuperación y operaciones pendientes

Se bloquean doble envío y cierre durante confirmación/envío. Cancelar la confirmación no envía ni guarda un pendiente.

Antes del PATCH se conserva una copia del payload, más el nombre de la obra como metadato solo de presentación, en memoria y sessionStorage por usuario/máquina. Si el resultado queda incierto, se congelan los campos y solo se permite reintentar el mismo payload. El botón de la tarjeta cambia a «Confirmar ubicación pendiente» para recuperarlo después de cerrar/reabrir, incluso si la ubicación cambió por el envío anterior. El reintento no requiere que el destino original siga entre los destinos de una operación nueva: el backend identifica el movimiento original y mantiene la autorización por permiso.

No se inicia un cambio de ubicación si hay una captura de horas o asignación sin confirmar de esa máquina. Mientras exista un cambio de ubicación pendiente, tampoco se inician horas ni asignación/retiro desde esta pantalla. Se informa qué operación debe confirmarse primero. El aislamiento por usuario evita recuperar capturas de otra sesión.

La recuperación local depende de conservar la ejecución o sessionStorage. Al perder ambos no se conserva ese payload local; la huella del servidor no se elimina. No se crea un mecanismo de recuperación entre dispositivos en este paso.

## Archivos

- `C:/xampp/htdocs/rivera/app/src/app/services/mecanico-maquina-ubicacion.service.ts`: tipos, opciones, PATCH y persistencia del pendiente.
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-maquina-ubicacion.component.ts` y `.html`: captura, confirmación, errores y recuperación.
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-maquina-card.component.ts` y `.html`: acción compacta y recuperación.
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-consulta.page.ts` y `.html`: integración de listado/detalle, coordinación de modales y actualización de tarjeta.

## Comprobaciones y pendientes

- TypeScript con `--noEmit --incremental false` y compilación Angular de desarrollo completos. Advertencias existentes de otras pantallas/Stencil, sin errores de compilación. Salida temporal retirada al cerrar.
- UTF-8/mojibake y formato de fuentes/documentos revisados. Sin ejecución ni creación de pruebas funcionales; sin capturas operativas, cambios de esquema, permisos o usuarios.
- No se desplegó ni se reemplazó `www`; este paso modifica el código fuente móvil. El backend D09 y su migración previa T15.4 deben estar disponibles en el entorno utilizado.
- El permiso requiere registro y asignación manual; después hay que actualizar la sesión móvil para obtenerlo. Su ausencia oculta la acción y el servidor sigue rechazando escrituras sin autorización.
- Quedan pruebas conectadas, dispositivo y concurrencia del checkpoint T15.6 a cargo del usuario. No se marca paridad funcional validada.
- Grafo pendiente para el cierre; no actualizado en este paso. Siguiente paso sujeto a confirmación del usuario.
