# Revisión del formulario móvil de servicios

Fecha: 2026-10-28. Cliente: `C:\xampp\htdocs\rivera\app`.

## Hallazgos

- Un servicio corresponde a un solo equipo, máquina o vehículo. El selector y el payload ya eran individuales; la paginación solo recorre el catálogo.
- El formulario compartía estilos de consulta y su fieldset conservaba el ancho mínimo intrínseco. Las etiquetas y nombres largos provocaban desbordamiento en móvil.
- El tipo preventivo venía del catálogo de empresa, pero inicialmente solo mostraba nombre. Se incorporó el intervalo de horas/km.
- El contrato de borrador conserva mecánico automático, tipo programado y ejecución mediante acciones posteriores. Este cambio no amplía esos permisos ni cambia el contrato de escritura.

## Ajustes

- Cabecera principal con nombre, código, ámbito del equipo y estado de borrador. Mensaje explícito: un servicio, un equipo.
- Búsqueda plegada al editar o entrar desde un equipo. Se abre mediante Cambiar equipo; paginar no reemplaza la selección.
- Etiquetas apiladas, estilos exclusivos del formulario, anchos mínimos corregidos y botones de catálogo separados en dos columnas.
- Categoría vacía en altas nuevas; la edición conserva su valor existente.
- Referencia de horómetro destacada. Los avisos de vehículo se muestran únicamente al trabajar con vehículos.
- Materiales sugeridos con requerido, disponible y faltante, usando datos ya entregados por la API. Los valores ausentes se muestran como desconocidos, no como cero.

## Archivos del cliente

- `src/app/mecanico/mecanico-servicio-form.page.ts`.
- `src/app/mecanico/mecanico-servicio-form.page.html`.
- `src/app/mecanico/mecanico-servicio-form.page.scss` (estilos propios).
- `src/app/services/mecanico-operacion.service.ts` (datos de catálogo/materiales).
- `src/app/mecanico/mecanico-captura.spec.ts`.

## Validación

- TypeScript de app y pruebas: sin errores.
- Chrome Headless: 25 pruebas exitosas, incluidas selección única, conservación del equipo al paginar, reapertura del selector sin perder programación y geometría a 320 píxeles con nombres largos.
- Revisión visual del componente real a 390 y 320 píxeles, usando una compilación temporal con datos ficticios y servicios simulados. Sin iniciar sesión ni escribir registros reales.
- A 320 píxeles se comprobó que el ancho del contenido coincide con el del formulario y los botones de paginación permanecen dentro de sus límites.
- Compilación Angular completa y revisión UTF-8. Las advertencias preexistentes de imports/optional chaining y Stencil no se corrigen dentro de este alcance.
- Los archivos fuente y salidas temporales de vista previa se retiran al terminar; no se reemplaza `app/www` ni se publica en producción.

La validación visual y de componentes no cierra por sí sola T14: sigue siendo necesario el recorrido funcional conectado y en dispositivo del plan principal.

## Seguimiento del servicio: cards desplegables (2026-10-29)

- Los cards del detalle tienen cabecera con flecha para mostrar/ocultar su contenido: resumen, acciones, ajuste de materiales, orden, carga de evidencias, galería, ejecución registrada y ejecución.
- Ejecución aparece al final, después de las evidencias y del registro previo. Iniciar/completar y actualizar estado comparten una fila de botones compactos.
- El resumen empieza abierto. La captura de materiales y los formularios/avisos activos pueden abrir su card; ocultarlos conserva los datos y las fotos seleccionadas.
- El texto para iniciar distingue materiales pendientes de materiales listos. Se conservan las validaciones y permisos existentes.
- Verificación: 77 pruebas del módulo móvil y compilación Angular correctas. Incluye apertura/cierre sin enviar ni perder captura, orden de los cards, conservación de la galería y geometría sin desbordamiento a 320 y 390 px. UTF-8 comprobado. Sin migraciones ni cambios en registros reales.

## Consulta web de servicios cerrados (2026-10-29)

- La edición web pasa a consulta al estar completado o cancelado: aviso visible, campos deshabilitados y estilo gris, carga de fotos bloqueada, materiales/solicitudes sin edición y acciones de cierre ocultas. Se conservan navegación, fotos e impresión.
- El controlador rechaza actualizaciones de servicios cerrados antes de validar la captura; el servicio de guardado web vuelve a comprobar el estado bajo bloqueo para impedir cambios desde un formulario abierto antes del cierre. Cubre programados y emergencias.
- Verificación: 71 pruebas PHP y 747 comprobaciones correctas; vistas Blade compiladas, sintaxis y UTF-8 verificados. Renderizado del servicio 16: completado, 12 campos principales bajo fieldset deshabilitado, cero casillas de materiales habilitadas, sin botón de cierre e impresión disponible. Sin modificar datos reales ni requerir migración.

## Listado y filtro de tipo de equipo (2026-10-29)

- En el listado de servicios, la máquina/vehículo es el título principal; la descripción o categoría se muestra debajo en gris y tamaño menor. Estado y fecha programada comparten una columna lateral, con la fecha debajo del badge.
- El filtro desplegable de fechas/equipo usa cabecera azul con texto blanco y flecha. El selector se llama Tipo de equipo y permite ver todas las máquinas o todos los vehículos antes de elegir una unidad.
- Hallazgo: el selector anterior solo cambiaba el catálogo de búsqueda; sin un ID concreto no filtraba los servicios. Ahora el cliente envía `equipo_tipo` y la API lo aplica, rechazando IDs del tipo contrario. El filtro se conserva al paginar y se elimina al limpiar.
- Verificación: 79 pruebas del módulo móvil; tres pruebas de filtros PHP con 53 comprobaciones; compilación Angular, sintaxis PHP y UTF-8 correctos. No requiere migración ni cambios a registros reales.

## Tipo de servicio en la segunda línea del listado (2026-10-29)

- Se reemplazó la descripción manual por nombre e intervalo del tipo preventivo asignado, tomados de configuración: por ejemplo, `General · cada 500 h` o `Vehicular · cada 10,000 km`.
- La API precarga y devuelve `intervalo_valor` y `unidad` junto al nombre ya disponible. No se codifican los nombres de los tipos registrados ni se modifica el catálogo.
- Si el tipo falta o fue eliminado, se indica esa situación; la descripción manual permanece en el detalle y en sus registros originales. El intervalo puede ocupar más de una línea para mostrarse completo.
- Validación: 73 pruebas PHP (775 comprobaciones), 80 pruebas del módulo móvil y compilación Angular correctas. Sintaxis y UTF-8 verificados; sin migración ni cambios a datos reales.
