# T15: contrato de operaciones de máquinas del mecánico

- Fecha: 2026-10-29.
- Estado: T15.1-T15.3 de horas completados; T15.4-T15.5 de asignación/retiro y D09 backend/formulario móvil implementados con pruebas funcionales a cargo del usuario. Validación integral pendiente. Grafo diferido al cierre.
- Plan: [PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md](PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md).
- Consulta existente: [T04](CONTRATO_CONSULTA_EQUIPOS_MECANICO_T04.md).
- Los endpoints de opciones/horas se probaron previamente con HTTP en entorno aislado. Asignación/retiro y ubicación directa ya están implementados en backend y cliente móvil; no se ejecutaron pruebas funcionales nuevas por instrucción del usuario. El checkpoint integral sigue pendiente.

## Acceso y permisos

Todas las operaciones requieren Sanctum, usuario activo en `usuarios_app` y permiso efectivo `app.mecanico.access`. Se aplica además el permiso específico de la tabla. Los permisos denegados/revocados se comprueban en cada petición, antes de resolver IDs de equipos. No se requiere obra asignada al usuario ni acceso de residente/gerencial. El autor se resuelve con el usuario autenticado; no se acepta `user_id` ni `created_by` del cliente.

Estas operaciones de equipo no asignan un servicio a un empleado: no agregan un requisito de `empleado_id` que el flujo web de horas/asignación no tiene. Los requisitos de empleado para crear servicios programados permanecen independientes.

| Acción | Permiso adicional | Condición del equipo |
|---|---|---|
| Consultar opciones | Ninguno | Máquina existente y visible en el catálogo mecánico. Cada opción se filtra por sus propios permisos. |
| Registrar horas | `maquinas.horas.create.access` | Asignación activa a obra vigente. |
| Ajustar horómetro | `maquinas.horas.create.access` | Sin asignación activa; obra relacionada opcional. |
| Asignar a obra | `maquinas.asignar_obra.access` | Operativa y sin otra asignación activa; obra vigente con pilas activas. |
| Retirar de obra | `baja_maquinas.access` | Una única asignación abierta, incluso a obra terminada/cancelada; el retiro la finaliza y devuelve la máquina al patio conservando su estado operativo. |
| Cambiar directamente ubicación | `maquinas.ubicacion.update.access` | Implementado según D09; destinos/capacidad según contexto y permiso efectivo. Permiso compartido por web y app, con registro/otorgamiento manual. |

No se crean ni asignan permisos automáticamente. Si falta un permiso para un usuario real, su asignación manual es una acción separada. Tener acceso al panel no concede los permisos de escritura.

## Rutas y estado de implementación

Base `/api/v1/mecanico`. Las rutas de consulta actuales se conservan.

| Método | Ruta | Entrega |
|---|---|---|
| GET | `/maquinas/{maquina}/opciones-operacion` | Implementado T15.2: contexto y capacidades de horas; las acciones futuras permanecen en `false`. |
| POST | `/maquinas/{maquina}/horas` | Implementado T15.2: servidor decide entre registro y ajuste. |
| POST | `/maquinas/{maquina}/asignar-obra` | Implementado T15.4, pendiente de pruebas del usuario. |
| POST | `/maquinas/{maquina}/baja-obra` | Implementado T15.4, pendiente de pruebas del usuario. |
| PATCH | `/maquinas/{maquina}/ubicacion` | Implementado D09 backend y UI móvil; pendiente de pruebas del usuario. |

`opciones-operacion` acepta `q`, `page`, `per_page` para buscar obras según `uso=asignacion|ajuste`. Límite 1–50, predeterminado 20; paginar en servidor, sin descargar todas las obras. `asignacion` solo devuelve obras vigentes con pilas activas y requiere permiso de asignación; `ajuste` devuelve obras no canceladas y requiere permiso de horas, siguiendo la referencia web. Sin `uso`, devolver contexto/capacidades y omitir la consulta de obras. No exponer presupuesto, personal, documentos o información del panel residente.

**Etapa actual T15.4/D09 backend:** se acepta `uso=ajuste|asignacion`. La consulta base requiere acceso mecánico; buscar obras relacionadas exige permiso de horas y buscar obras de asignación exige `maquinas.asignar_obra.access`. El parámetro de ruta se llama `maquinaId` para autorizar antes de buscar el modelo; su URL externa conserva el mismo identificador de máquina. Las capacidades de asignación/retiro/cambio directo se habilitan según permiso y contexto.

**Ampliación compatible D09:** `contexto_ubicacion` y `ubicaciones_permitidas` se entregan separados del `contexto` anterior. Incluyen ubicación y último movimiento esperados además de asignación/lectura; permiten detectar cambios concurrentes y ciclos repetidos sin alterar los payloads anteriores de horas/asignación. Ver [contrato concreto del backend D09](IMPLEMENTACION_UBICACION_MAQUINAS_D09_BACKEND.md).

**Ampliación de retiro pendiente, 2026-10-29:** `asignacion_retiro` identifica una única asignación abierta, aunque ya no sea vigente por cierre de obra. `contexto_retiro` entrega su ID esperado y lectura; el formulario de retiro usa este contexto en vez del contexto de horas. El detalle también conserva esta asignación para presentación. No transforma la obra cerrada en vigente para horas/nuevas asignaciones. El retiro sigue sujeto a permiso, fecha y recuperación exacta. [Implementación y menú desde ubicación](AJUSTE_RETIRO_ASIGNACIONES_PENDIENTES_MOVIL.md).

### Contexto y capacidades

La respuesta usa `ok`, `data` y, al buscar obras, `meta`. `data` contiene:

- `maquina`: identidad, estado, ubicación, horómetro actual, última lectura y preventivos, con los formatos de T04.
- `asignacion_activa`: identificador de asignación, obra, fecha y lectura inicial, o `null`.
- `contexto`: `asignacion_esperada_id` y `horometro_esperado`, reflejando exactamente los valores observados. No sustituir `horometro_actual: null` por una lectura real inventada. La referencia aritmética de un equipo sin ninguna lectura es cero, como en el servicio existente.
- `acciones_permitidas`: `registrar_horas`, `ajustar_horometro`, `asignar_obra`, `retirar_obra`, `cambiar_ubicacion`. Solo devolver `true` cuando exista el endpoint, el permiso y la condición del equipo. Las acciones no implementadas permanecen en `false` aunque el usuario tenga permiso.
- `obras`: solo cuando se solicita `uso`, con `id`, `nombre`, `clave_obra`; no es una asignación del usuario.

Estos flags orientan la UI. Cada POST debe volver a comprobar permisos y contexto bajo transacción; los flags no autorizan por sí mismos. El estado operativo no añade restricciones de horas o retiro que la web actual no tenga; sí condiciona la asignación.

## POST de horas: dos modos determinados por servidor

Campos comunes:

| Campo | Regla |
|---|---|
| `horometro_fin` | Obligatorio, número entre 0 y 99,999,999.99 con hasta dos decimales, compatible con `DECIMAL(10,2)`, sin redondeo silencioso. La lectura debe superar la referencia actual consolidada; no es la cantidad de horas a sumar. |
| `asignacion_esperada_id` | Clave presente, entero positivo o `null`; copia del contexto consultado. |
| `horometro_esperado` | Clave presente, número no negativo o `null`; copia del contexto consultado. |
| `notas` | Opcional/nulo, texto hasta 500 caracteres. Normalizar vacío a nulo. |

Con asignación activa:

- `inicio` y `fin`: obligatorios en API, ISO 8601 con offset explícito; fin mayor o igual a inicio. La API móvil exige ambas fechas para que el resultado y su reintento sean reproducibles; la web conserva sus valores opcionales y normalización existente.
- `obra_id` y `fecha_registro` no se aceptan para elegir destino: la obra se deriva de la asignación validada en servidor.
- Crear `ObraMaquinaRegistro` mediante `MaquinaHorometroService`, usando la lectura consolidada como referencia y calculando la diferencia en servidor.

Sin asignación activa:

- `fecha_registro`: obligatoria, fecha civil `YYYY-MM-DD` de México. Mantener el significado de día que tiene el modelo actual.
- `obra_id`: opcional/nulo, obra relacionada existente no cancelada. No crea asignación ni cambia ubicación.
- No aceptar `inicio`/`fin` como datos de un registro de obra en este modo.
- Crear `MaquinaHorometroAjuste` con lectura anterior, nueva y diferencia; tipo `ajuste` y origen `app_mecanico`.

En ambos modos, usar el usuario autenticado y origen `app_mecanico`; no modificar lecturas anteriores ni `horometro_base`. Cero es una referencia válida, pero una nueva lectura igual a la referencia no incrementa el contador y se rechaza salvo recuperación de un reintento idéntico ya guardado.

### Fechas, contexto y resultados inciertos

1. Validar formato de entrada, autorización y modelo; normalizar fechas con la zona `America/Mexico_City` antes de compararlas.
2. Bajo transacción bloquear/releer la máquina y su asignación antes de escribir. No convertir silenciosamente una captura abierta como registro en un ajuste porque otro usuario retiró la máquina, ni ligarla a una nueva obra.
3. Comparar los campos esperados con el contexto real. Si cambiaron, responder 409 sin escritura; el móvil debe recargar y pedir que se revise la captura.
4. Conservar la regla de secuencia de `MaquinaHorometroService`: nueva lectura estrictamente mayor y fecha operativa no anterior a la última lectura válida. En registros se toma fin; en ajustes se compara el día civil.
5. Antes de rechazar el contexto de un reintento, comprobar si ya existe la operación exacta de ese usuario y origen, misma asignación/modo, lecturas anterior/nueva, fechas, obra relacionada y notas normalizadas. Si coincide, devolverla sin insertar otra fila. Coincidir solo en horómetro o en autor no basta. La recuperación no concede acceso a registros de otro usuario.
6. La UI conserva el payload original cuando no sabe si se guardó y lo reenvía sin cambiar fechas ni lectura de referencia. Una captura distinta con contexto viejo se rechaza y no recalcula el pasado.

El reconocimiento del reintento debe basarse en datos persistidos, dentro de la misma coordinación transaccional, sin depender de memoria del proceso o caché local. La estrategia y su atomicidad se verificarán en T15.2; no se declara garantía de ejecución por este contrato. Si el esquema real no permite identificarlo sin ambigüedad, se documentará el ajuste necesario antes de implementarlo, en vez de usar una coincidencia incompleta.

**Implementación T15.2:** la huella persistida resuelve esa identificación, conservando la diferencia entre referencia nula y cero. Se normalizan enteros/decimales, fechas equivalentes con offset y notas vacías antes de calcularla. Las fechas de registro aceptan segundos completos y offset explícito; no se aceptan fracciones de segundo que las columnas actuales no conservan. Un reintento recuperado vuelve a exigir permisos y devuelve la fila original junto con el estado actual del equipo. La prueba de exclusión concurrente real en InnoDB sigue en T15.6.

### Ejemplo de captura y respuesta previstos

Captura con obra activa:

```json
{
  "asignacion_esperada_id": 81,
  "horometro_esperado": 1480,
  "horometro_fin": 1485,
  "inicio": "2026-10-29T08:00:00-06:00",
  "fin": "2026-10-29T10:00:00-06:00",
  "notas": "Lectura verificada en máquina"
}
```

Respuesta de creación: HTTP 201. Reintento idéntico recuperado: HTTP 200. Ambos entregan el registro persistido y una consulta actualizada; el estado actual puede haber avanzado desde la operación recuperada.

```json
{
  "ok": true,
  "message": "Horas registradas correctamente.",
  "data": {
    "operacion": {
      "tipo": "registro_horas",
      "id": 123,
      "asignacion_id": 81,
      "horometro_anterior": 1480,
      "horometro_nuevo": 1485,
      "diferencia": 5,
      "reutilizada": false
    },
    "maquina": {
      "id": 25,
      "horometro_actual": 1485,
      "fecha_ultima_lectura": "2026-10-29T10:00:00-06:00"
    }
  }
}
```

El objeto `maquina` de la implementación incluirá los campos operativos completos de T04, incluidos preventivos; el ejemplo abrevia esos campos. En ajuste, `tipo` será `ajuste_horometro` y `asignacion_id` será nulo. La operación indica la diferencia de su propia fila, sin recalcularla con lecturas posteriores.

## Asignación y retiro: contrato para T15.4

| Operación | Entrada | Efecto |
|---|---|---|
| Asignar | `obra_id`, `fecha_inicio` civil obligatoria, `horometro_inicio` no negativo obligatorio, notas hasta 1000; contexto esperado con asignación nula y lectura consultada. | Crear asignación activa, establecer `en_obra` y registrar movimiento con actor/fecha. |
| Retirar | `asignacion_esperada_id` obligatorio no nulo y lectura esperada; `fecha_fin` civil obligatoria. | Finalizar esa asignación, conservar historial, establecer `en_patio` y registrar movimiento. |

- Asignar exige operativa, ninguna asignación activa y obra no cancelada/terminada con pilas activas. La lectura inicial no debe retroceder; puede ser igual a la actual. Alinear cualquier validación nueva con la web compartida y probarla, sin introducir una discrepancia silenciosa.
- La fecha inicial no debe anteceder la última lectura válida. La fecha de retiro no debe ser anterior al inicio ni a registros de horas existentes de esa asignación. Documentar cualquier ajuste necesario de la validación web al implementar T15.4.
- Retirar no implica baja definitiva del catálogo ni cambio de estado operativo. No inventar una lectura final; la web actual puede finalizar con `horometro_fin` nulo. Si se necesita una nueva lectura, capturarla por el flujo de horas antes del retiro.
- No trasladar entre obras cambiando solo `ubicacion`: retiro y nueva asignación son operaciones explícitas.
- Usar transacción y relectura/bloqueo de máquina/asignación para coordinar asignación, retiro y captura de horas. Reintentos exactos se recuperan por datos persistidos del mismo actor y operación; otra operación/contexto responde 409. T15.4 debe probar la identificación y el rollback de asignación y movimiento.
- Respuestas: HTTP 201 al crear asignación, 200 al retirar o recuperar una operación exacta; `data.operacion`, `data.maquina`, `data.asignacion_activa` y capacidades actualizadas. No enviar un `true` de capacidad futura si aún no existe su endpoint.

## Ubicación directa: D09 aprobada — 2026-10-29

Se conserva la distinción entre estado (`operativa`, `fuera_servicio`, `en_reparacion`, `baja_definitiva`) y ubicación (`en_obra`, `en_camino`, `en_reparacion`, `en_patio`). No se cambia el estado operativo al modificar ubicación salvo regla acordada posteriormente.

**Decisión confirmada por el usuario, 2026-10-29:** cambiar a `en_camino` o `en_reparacion` conserva la asignación a obra, si existe. No cerrar ni sustituir esa asignación como efecto del cambio de ubicación; el retiro explícito sigue siendo la acción que la finaliza. Sin asignación previa, cambiar ubicación no crea una.

**Aprobación posterior del usuario, 2026-10-29:** se aceptan las transiciones, separación de estado operativo, motivo obligatorio, auditoría y permiso específico de la propuesta. D09 queda resuelta como decisión; su implementación permanece pendiente.

| Contexto | Destino permitido | Efecto en la asignación |
|---|---|---|
| Con obra asignada | `en_camino` o `en_reparacion` | Conserva la misma asignación. |
| En camino o reparación con obra asignada | `en_obra` | Regresa a la misma obra; requiere asignación activa. |
| Sin obra asignada | `en_camino`, `en_reparacion` o `en_patio` | Continúa sin asignación; no crea una. |
| Con obra asignada y destino patio | Flujo explícito de retiro, con confirmación | Finaliza la asignación; no usar el PATCH directo para eludir el retiro. |
| Cambio entre obras | Retiro y nueva asignación | Conserva ambos eventos en el historial; no trasladar mediante ubicación. |

- Ubicación y estado operativo permanecen independientes. Ir a reparación o salir de ella no cambia automáticamente el estado operativo ni certifica una reparación terminada.
- Permiso aprobado: `maquinas.ubicacion.update.access`, compartido por Laravel y app, además de los requisitos móviles de sesión y panel. Crear/otorgar manualmente al usuario autorizado; este paso documental no crea ni asigna permisos.
- Motivo obligatorio. Registrar autor autenticado, fecha del movimiento, ubicación anterior y nueva. Si conserva una obra, mostrarla junto a la ubicación y avisar antes de confirmar que seguirá asignada a ella.
- El backend deberá comprobar permiso, transición y contexto vigente bajo transacción y conservar recuperación de reintentos exactos, sin duplicar movimientos. El cambio directo no modifica lecturas ni fechas de la asignación.
- Backend y formulario móvil entregados: capacidad y destinos según permiso/contexto, reglas compartidas web/API, ruta H26 implementada y ubicación real presentada en catálogo (H25). Pruebas funcionales pendientes del usuario.
- Orden de ejecución: backend compartido/API, después UI móvil. Reportar y pedir confirmación al cerrar cada paso. Las pruebas funcionales siguen a cargo del usuario y el grafo se actualiza al final.

**Entrega backend D09, 2026-10-29:** `MaquinaUbicacionService`, PATCH móvil, opciones/POST web, auditoría, contexto versionado y recuperación exacta. Reutiliza huella existente; sin migración nueva ni registro/asignación de permisos. [Archivos, payloads, límites y comprobaciones](IMPLEMENTACION_UBICACION_MAQUINAS_D09_BACKEND.md). Solo comprobaciones estáticas; no pruebas funcionales ni grafo.

**Entrega móvil D09, 2026-10-29:** contrato TypeScript y formulario desde listado/detalle, destinos actuales de API, motivo/notas, confirmación y aviso de conservación de obra. Preserva el payload incierto y permite recuperar el mismo cambio desde la tarjeta; coordina pendientes con horas/asignación. TypeScript y compilación Angular completos; sin pruebas funcionales ni grafo. [Implementación y límites](IMPLEMENTACION_UBICACION_MOVIL_D09.md).

## Errores y recuperación del cliente

| HTTP | Uso |
|---|---|
| 401 | Sesión inexistente/vencida; volver al flujo de autenticación. |
| 403 | Usuario móvil inactivo, acceso mecánico ausente o permiso específico denegado/revocado. |
| 404 | Máquina inexistente para usuario ya autorizado. |
| 422 | Campos/fechas inválidos, lectura no creciente sin reintento exacto, obra no elegible o validación de negocio de la captura. Entregar `errors` por campo cuando corresponda. |
| 409 | Contexto cambió: lectura/asignación distintas de las esperadas o estado concurrente incompatible. No escribir. Entregar `code: contexto_maquina_cambio` y mensaje para recargar. |
| 5xx o desconexión | Resultado incierto: conservar payload, bloquear otro envío distinto y permitir recuperación/reintento exacto o consulta del estado. No anunciar éxito ni fabricar otro registro. |

Formato de error: `ok: false`, `message`; `errors` cuando aplica y `code` estable en conflictos. Mantener los códigos y formatos existentes de autenticación/middleware; no cambiar contratos de residente o gerencial para uniformarlos en esta tarea.

## Checkpoints y validación requerida

- T15.1 documental: contrastado contra rutas, vista `/maquinas`, `MaquinaController`, `MaquinaHorometroService`, `MaquinaService` y modelos. Formato/UTF-8 y ejemplos JSON verificados; no se ejecutaron escrituras ni se anunciaron rutas nuevas disponibles.
- T15.2: acceso, modo determinado por servidor, nulo/cero de referencia, crecimiento, fechas/zona, obra relacionada, contexto obsoleto, reintento idéntico, intento distinto, actor/origen, rollback, paridad web/API y lectura/preventivo actualizados.
- T15.3: formulario por capacidades, payload preservado, errores, doble envío, reintento y recarga sin perder filtros/página.
- T15.4-T15.5: obras elegibles, asignar/retirar, historial, autor, permisos, reintentos y ubicación coherente. Cambio directo queda pendiente de D09.
- T15.6: recorridos reales web/app y concurrencia InnoDB aislada; SQLite por sí solo no prueba exclusión de filas.

No se modifican tablas ni permisos en T15.1. La actualización del plan no autoriza saltar checkpoints: por instrucción del usuario, reportar el cierre de cada paso y pedir confirmación antes del siguiente.

## Ajuste técnico previo a T15.2 — 2026-10-29

La lectura anterior guardada no distingue una referencia originalmente nula de cero, ni conserva todos los campos originales de contexto. Comparar solo esas columnas no demuestra un reintento exacto. T15.2 incorporará `solicitud_hash`, SHA-256 nullable y único, en las tablas existentes `obra_maquina_registros` y `maquina_horometro_ajustes`. La huella incluye usuario, máquina, origen, modo y payload normalizado completo; se guarda en la misma transacción que la lectura. No se crean tablas nuevas ni se llenan registros históricos: permanecen con huella nula y no se reconocen como reintentos móviles. La migración debe aplicarse antes de usar los endpoints nuevos.

## Cierre T15.2 — 2026-10-29

- Implementado `MaquinaHorasService`, compartido por web y API, que delega lecturas/ajustes al servicio de horómetro existente. La web conserva fechas opcionales y origen `web_maquinas`; la API exige contexto y fechas explícitas y usa `app_mecanico`.
- Publicados GET de opciones y POST de horas. Autorización de escritura anterior a la consulta del modelo, capacidades por asignación/permiso, búsqueda paginada de obras relacionadas y errores de conflicto/validación estructurados.
- Migración `2026_10_29_120000_add_solicitud_hash_to_maquina_lecturas` aplicada únicamente en la base local `127.0.0.1:4200/rivera_v2`, lote 151. Verificado que ambas tablas siguen con cero huellas no nulas: no se crearon lecturas operativas ni se llenaron históricos. Producción no modificada.
- Las huellas internas se ocultan en la serialización de los dos modelos. No se admiten autor/origen/huella enviados por cliente.
- **132 pruebas / 1,332 comprobaciones** en la regresión de acceso, consultas, catálogos, servicios y horas. Incluye 16 pruebas nuevas de horas: permisos/revocación/401/403/404, ambos modos, contexto obsoleto, zona, precisión/fechas, nulo frente a cero, actor distinto, recuperación tras nuevas lecturas o retiro, paridad web/API, migración reversible y rollback de lectura al fallar la huella.
- Pruebas de escritura sobre SQLite en memoria, sin usar registros operativos. Sintaxis, rutas, UTF-8 y formato verificados. SQLite no demuestra bloqueos de fila: concurrencia InnoDB y recorrido con sesión real permanecen en T15.6. No se implementó el modal móvil ni se habilitaron escrituras de ubicación.
- Para desplegar este paso, ejecutar primero la migración nueva con el flujo habitual de despliegue: `php artisan migrate --path=database/migrations/2026_10_29_120000_add_solicitud_hash_to_maquina_lecturas.php --force`. No requiere poblar históricos.
- Próximo paso: T15.3, sujeto a confirmación del usuario; no ejecutarlo automáticamente.

## Cierre T15.3 — 2026-10-29

El cliente ya implementa el formulario de horas/ajuste sobre este contrato, con consulta de capacidades actuales, fechas de México con offset, errores, contexto obsoleto y reintento exacto de payload conservado por usuario/máquina. Listado y detalle se actualizan con la respuesta sin perder filtros ni página. **112 pruebas Chrome Headless y build completo exitosos**; ver [implementación T15.3](IMPLEMENTACION_HORAS_MAQUINA_MOVIL_T15_3.md) para archivos, escenarios y límites. Sin cambios del backend ni escrituras operativas en este paso. El recorrido conectado sigue pendiente; T15.4 requiere confirmación antes de comenzar.

## Entrega de código T15.4 — 2026-10-29

Asignación/retiro y catálogo de obras elegibles implementados mediante `MaquinaAsignacionService` y reglas compartidas con web en `MaquinaService`. Autor, contexto, cronología, transacción y huella de reintento coordinados. Se agregó la migración nullable/única de huella en movimientos y se aplicó solo en local, lote 152. Ver [detalles, payloads y pendientes T15.4](IMPLEMENTACION_ASIGNACIONES_MAQUINAS_T15_4.md).

No se ejecutaron pruebas funcionales ni se actualizó el grafo: el usuario asumió las pruebas y difirió el grafo al cierre. Solo se comprobaron sintaxis, rutas, formato y UTF-8. D09 permanece abierto; los formularios de asignación/retiro corresponden a T15.5 y requieren confirmación antes de comenzar.

## Entrega de código T15.5 — 2026-10-29

El cliente implementa asignar a obra y retirar al patio desde listado/detalle, usando contexto y capacidades frescas, obras elegibles paginadas y confirmación de retiro. Conserva payloads inciertos por usuario/máquina en memoria y sessionStorage para reintentar la misma operación; una acción visible permite recuperarlos aunque la asignación ya haya cambiado. La autorización final permanece en servidor.

TypeScript y compilación Angular de desarrollo verificados; no se ejecutaron pruebas funcionales ni se actualizó el grafo, conforme a la instrucción del usuario. Sin cambios de base de datos en este paso. D09 y el checkpoint funcional siguen pendientes. Ver [implementación y límites T15.5](IMPLEMENTACION_ASIGNACIONES_MOVIL_T15_5.md).
