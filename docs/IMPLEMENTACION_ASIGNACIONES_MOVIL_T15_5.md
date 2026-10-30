# T15.5: asignación y retiro de máquinas desde la app

Fecha: 2026-10-29. Código implementado; checkpoint funcional pendiente del usuario.

## Interfaz y contrato

- Las tarjetas de listado y detalle muestran Asignar a obra o Retirar al patio según permiso y contexto disponible. Al abrir se obtiene contexto actual de la API; sus capacidades habilitan el envío y el servidor autoriza la operación definitiva.
- Asignación: buscador de obras elegibles paginado, selección que permanece al cambiar página, fecha, horómetro inicial y notas. Se valida formato, precisión, lectura mínima y longitud de notas; el servidor verifica elegibilidad y cronología completas.
- Retiro: muestra obra/asignación actual, fecha de cierre y confirmación explícita antes de enviar. Finaliza asignación y envía al patio sin inventar lectura final ni cambiar estado operativo.
- Fecha inicial de captura según México. Los errores 422 aparecen por campo; un 409 obliga a consultar/revisar contexto actual.
- Se bloquean envíos repetidos y cierre durante envío. Una respuesta confirmada actualiza listado/detalle sin perder búsqueda ni página.

## Recuperación

Payload pendiente aislado por usuario/máquina, conservado en memoria y sessionStorage. Tras desconexión, timeout o resultado incierto solo se reenvía ese payload. El botón Confirmar operación pendiente permite reabrirlo incluso si la operación original sí se aplicó y ahora la máquina muestra otro contexto. Su recuperación usa la huella ya implementada en T15.4 y exige conservar el permiso original.

401 cierra sesión; 403 bloquea la operación. 409/422 permiten corregir después de revisar los datos. Cambiar usuario o máquina invalida respuestas antiguas. No se envían al servidor los metadatos de obra usados para mostrar la captura pendiente. Si se pierde almacenamiento de sesión y memoria, se pierde esa recuperación local; la autorización y huella del servidor permanecen.

## Archivos móviles

- `C:/xampp/htdocs/rivera/app/src/app/services/mecanico-maquina-asignacion.service.ts`
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-maquina-asignacion.component.ts`
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-maquina-asignacion.component.html`
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-maquina-card.component.ts`
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-maquina-card.component.html`
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-consulta.page.ts`
- `C:/xampp/htdocs/rivera/app/src/app/mecanico/mecanico-consulta.page.html`

El modal reutiliza los estilos del formulario de horas. No se cambian los endpoints ni el esquema en este paso.

## Comprobaciones y pendientes

- Comprobación de tipos TypeScript y compilación Angular de desarrollo, sin ejecutar pruebas funcionales; salida temporal de build retirada al cerrar.
- Formato y UTF-8 de fuentes/documentos revisados. Las advertencias de compilación de otras pantallas no se corrigen en este alcance.
- Pruebas conectadas, permisos, reintentos y concurrencia quedan pendientes del usuario/T15.6. No se crearon movimientos operativos para verificar el formulario.
- Grafo diferido al final, según instrucción del usuario. Sin commit, despliegue ni cambios en producción.
- D09 (camino/reparación/cambio directo) sigue pendiente; no habilitado mediante estos formularios.
