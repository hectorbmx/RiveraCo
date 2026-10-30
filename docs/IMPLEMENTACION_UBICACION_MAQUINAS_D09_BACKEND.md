# D09: backend compartido de ubicación de máquinas

Fecha: 2026-10-29. Código entregado; pruebas funcionales a cargo del usuario. UI móvil entregada posteriormente: [detalle del formulario D09](IMPLEMENTACION_UBICACION_MOVIL_D09.md).

## Comportamiento implementado

- Camino y reparación conservan la asignación existente. Regresar a obra desde camino/reparación conserva esa misma asignación y requiere que siga activa.
- Sin asignación se permiten camino, reparación y patio. No se crea una asignación por modificar ubicación.
- Patio con asignación exige usar el retiro explícito; no se permite eludirlo mediante cambio directo. Cambiar obra sigue requiriendo retiro y nueva asignación.
- Ubicación y estado operativo independientes; este flujo no modifica estado, lecturas, fechas ni notas de la asignación. No certifica el término de una reparación.
- Se exige motivo no vacío, máximo 190 caracteres; notas opcionales hasta 2000. Auditoría en `maquina_movimientos`: usuario autenticado, fecha del servidor, ubicaciones anterior/nueva y vínculo a la misma obra/asignación cuando existe.
- Las asignaciones abiertas a obras canceladas/terminadas o múltiples asignaciones activas bloquean este flujo hasta regularizarse; no se convierten silenciosamente en «sin obra».
- El destino actual se excluye: un envío nuevo que no cambia ubicación se rechaza; un reintento exacto recupera el movimiento original.

## Permiso y rutas

Permiso aprobado: `maquinas.ubicacion.update.access`. Se comprueba permiso efectivo y denegación explícita antes de consultar la máquina. Web y API usan el mismo servicio `MaquinaUbicacionService`. La API además conserva autenticación Sanctum, usuario móvil activo y acceso al panel mecánico.

| Canal | Método y URL | Respuesta |
|---|---|---|
| API | GET `/api/v1/mecanico/maquinas/{id}/opciones-operacion` | Consulta existente ampliada con `contexto_ubicacion`, `ubicaciones_permitidas` y capacidad real. |
| API | PATCH `/api/v1/mecanico/maquinas/{id}/ubicacion` | Movimiento original/nuevo y ficha operativa actualizada. HTTP 200. |
| Web | GET `/maquinas/maquinas/{id}/opciones-ubicacion` | Datos mínimos, obra asignada, contexto y destinos. Exige permiso específico. |
| Web | POST `/maquinas/maquinas/{id}/cambiar-ubicacion` | Controlador antes ausente (H26) implementado. JSON para `Accept: application/json`; redirección/mensajes para formulario. |

No se crea ni asigna el permiso automáticamente: su registro y otorgamiento son manuales en administración. Sin permiso efectivo, los destinos no se habilitan y la escritura responde 403. No se cambian roles ni usuarios.

## Captura y contexto

Al abrir el futuro formulario, consultar opciones actuales y conservar `contexto_ubicacion` completo. Este objeto es independiente del `contexto` anterior de horas/asignación, porque esos clientes ya lo expanden en sus payloads y rechazan campos adicionales. No se cambia ese contrato previo.

Ejemplo ilustrativo; los valores deben proceder de opciones, no copiarse del documento:

```json
{
  "asignacion_esperada_id": 123,
  "horometro_esperado": "6211.00",
  "ubicacion_esperada": "en_obra",
  "movimiento_esperado_id": 456,
  "ubicacion": "en_camino",
  "motivo": "Traslado temporal al taller",
  "notas": null
}
```

- Referencias de asignación, lectura, ubicación y movimiento pueden ser `null`; conservar el valor exacto de opciones. La lectura de `contexto_ubicacion` se entrega como decimal normalizado de dos posiciones o `null`; acepta número o decimal al validar la entrada.
- Destino obligatorio: `en_obra`, `en_camino`, `en_reparacion` o `en_patio`, condicionado por la matriz D09. Enviar uno de los valores de `ubicaciones_permitidas`.
- Campos ajenos al contrato se rechazan. La web únicamente excluye los campos de transporte `_token` y `_method` antes de usar el servicio.
- No se admite autor, obra destino, estado operativo, fecha del movimiento ni huella del cliente.

## Concurrencia y recuperación

Bloqueo de máquina antes de asignaciones, validación/relectura del contexto y transacción compartida con el registro de ubicación y movimiento. Se compara también el último ID del historial para distinguir ciclos repetidos de ubicación: volver posteriormente a la misma ubicación no convierte una nueva operación en reintento antiguo.

Se reutiliza `maquina_movimientos.solicitud_hash` (migración previa T15.4). La huella incluye origen web/app, autor, máquina, tipo y payload completo normalizado. Se guarda dentro de la transacción. Un reintento exacto autorizado recupera el movimiento original antes de revisar el contexto actual; no lo vuelve a aplicar. La respuesta adjunta el contexto actual y `operacion.reutilizada`.

409 con `code: contexto_maquina_cambio` requiere revisar opciones antes de otra captura. 422 devuelve errores por campo. 401/403/404 conservan sus significados. Ante un resultado incierto, la futura UI deberá conservar y reenviar exactamente el payload original.

## Catálogo Laravel

H25 corregido en `resources/views/maquinas/index.blade.php`: se muestra la ubicación guardada con su color y la obra asociada cuando existe. Se conservan las acciones de asignación/retiro existentes. Este paso no agrega un formulario web nuevo ni el modal móvil de cambio directo.

## Comprobaciones y entrega

- Sintaxis PHP de servicio, controladores y rutas; registro de las tres rutas de ubicación; compilación/sintaxis de la vista tocada; formato y UTF-8.
- Sin pruebas funcionales ni capturas operativas; no se declara paridad funcional validada. Pruebas de permisos, transiciones, historial, reintentos y concurrencia quedan pendientes del usuario.
- Sin migración nueva: reutiliza los campos y huella ya disponibles. Para desplegar exige tener aplicada la migración T15.4 `2026_10_29_140000_add_solicitud_hash_to_maquina_movimientos.php`.
- Sin cambios de datos/permisos, despliegue, commit ni actualización del grafo. Grafo diferido al cierre por instrucción del usuario.
- Siguiente paso, sujeto a confirmación: contrato TypeScript y formulario móvil de ubicación, usando destinos/capacidades actuales y confirmación de que la obra se conserva.
