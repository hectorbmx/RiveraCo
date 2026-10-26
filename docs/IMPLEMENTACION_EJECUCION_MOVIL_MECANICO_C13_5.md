# C13.5: inicio y cierre en el cliente móvil

Fecha: 2026-10-26, America/Mexico_City. Alcance solicitado: solo C13.5, sin ejecutar T14 ni commit/push.

Se respetó la pausa desde el cierre T12 (09:35:41.792505-06:00): cambios iniciados después de 09:45:41.792505-06:00.

## Comportamiento implementado

- Acciones iniciar/completar en el detalle, habilitadas exclusivamente por las banderas del servidor. No hay cancelación móvil ni precios editables.
- Inicio con fecha/hora y confirmación; permitido aunque falte surtido. Cierre con fin posterior al inicio, horómetro final o kilómetros enteros según equipo, notas hasta 5000 caracteres y confirmación. Se conserva la lectura mínima del servicio; Laravel revalida consolidado, propiedad, estado y surtido.
- D05: cierre solo cuando estén surtidos todos los materiales incluidos; sin incluidos no hay bloqueo. Mensaje de faltantes y actualización explícita del estado para habilitar el cierre tras surtido.
- Hora de Ciudad de México en captura y consulta, independiente de la zona del teléfono. datetime-local se envía como hora local del proyecto, sin convertirlo mediante la zona del navegador. Fechas inexistentes y fin no posterior se rechazan antes del POST.
- Solo se envían los campos del contrato T12. El inicio no envía lectura, notas ni costos. El cierre envía exactamente una lectura y notas; no actor, equipo, obra ni estado.
- Estado y costos de la respuesta se muestran después de guardar; fechas/notas de ejecución quedan disponibles en consulta.
- Bloqueo de doble envío durante confirmación/escritura, compartido con las operaciones y evidencias existentes. Validaciones 422 por campo conservan captura. 401 cierra sesión; 403 revalida acceso; 409 recarga acciones y explica el conflicto.
- Ante red/5xx, no se repite automáticamente: se exige actualizar estado y revisar antes de continuar. Si la consulta falla, se conserva el bloqueo. Se descartan respuestas tardías de ejecución pertenecientes a otro servicio y se revalida permiso/servicio después de confirmar.

## Archivos del cliente (repositorio separado)

En `C:\xampp\htdocs\rivera\app\src\app`:

- `services/mecanico-operacion.service.ts`: contratos POST y fechas de ejecución.
- `services/mecanico-consulta.service.ts`: DTO de fechas, notas y equipo.
- `mecanico/mecanico-operaciones.component.ts/html`: formulario, confirmaciones, errores, estado incierto y recarga.
- `mecanico/mecanico-consulta.page.ts/html`: consulta de ejecución registrada con zona horaria correcta.
- `mecanico/mecanico-ejecucion.spec.ts`: dieciséis pruebas nuevas.

No se modificaron hosts, dependencias, configuración de permisos ni datos operativos. Se conservaron los cambios anteriores del cliente y backend.

## Validación

- 63 pruebas exitosas en Chrome Headless 153: 47 anteriores y 16 nuevas. Incluyen recorrido controlado inicio con faltantes → actualización tras surtido → cierre, servicio sin incluidos, vehículo, campos HTTP, fechas, lectura inválida, errores 422/409/401/403, doble envío, evidencia bloqueada durante POST, confirmación descartada, resultado incierto, cambio de servicio y destrucción de vista.
- TypeScript de app y specs: `--noEmit --incremental false`, ambos exit 0.
- Build Angular de desarrollo completo con plantillas: exit 0. Salida temporal única fuera de `www`, sin despliegue; advertencias de componentes ajenos y Stencil conservadas.
- Los siete archivos móviles pasaron decodificación UTF-8 estricta, búsqueda de mojibake y espacios finales; diff comprobado. El comprobador PHP se aplica a los documentos, pues no cubre extensiones TS/HTML externas a la raíz backend.
- Graphify se consultó para el contrato backend y se actualiza al cerrar. El cliente sigue sin grafo propio; el grafo backend no indexa sus símbolos.

Las pruebas usan respuestas HTTP controladas y conservan la cobertura anterior de cámara/galería simuladas. No constituyen recorrido conectado con usuario real, Android o cámara física. Esas verificaciones, carreras de ejecución/surtido y configuración operativa siguen en T14/T01; no se marcan completas en esta entrega. No se ejecutó commit ni push.
