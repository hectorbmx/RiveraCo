# T15.4: API de asignación y retiro de máquinas

- Fecha: 2026-10-29.
- Estado: código de asignación/retiro implementado; pruebas funcionales a cargo del usuario. D09 sigue pendiente para cambios directos de ubicación.
- Se respetó la instrucción de no ejecutar pruebas ni actualizar el grafo en este paso. Solo se revisaron sintaxis PHP, rutas registradas, formato y UTF-8.

## Operaciones publicadas

Base: `/api/v1/mecanico`. Se mantienen Sanctum, usuario móvil activo y acceso mecánico independiente de obra del usuario.

| Operación | Ruta | Permiso adicional |
|---|---|---|
| Asignar | `POST /maquinas/{maquinaId}/asignar-obra` | `maquinas.asignar_obra.access` |
| Retirar | `POST /maquinas/{maquinaId}/baja-obra` | `baja_maquinas.access` |
| Consultar obras elegibles | `GET /maquinas/{maquinaId}/opciones-operacion?uso=asignacion` | `maquinas.asignar_obra.access` |

El permiso específico se comprueba antes de buscar la máquina, incluso en reintentos. No se crearon permisos ni se asignaron a usuarios.

`opciones-operacion` ya acepta `uso=ajuste|asignacion`. Para asignación devuelve obras no terminadas/canceladas con pilas activas, búsqueda por nombre/clave y paginación 1–50. Las capacidades `asignar_obra` y `retirar_obra` ahora reflejan sus endpoints, permisos y estado actual. `cambiar_ubicacion` permanece en `false`.

## Entradas

Tomar `asignacion_esperada_id` y `horometro_esperado` de la consulta actual. No enviar actor, estado, ubicación ni huella.

Ejemplo de asignación (IDs ficticios):

```json
{
  "asignacion_esperada_id": null,
  "horometro_esperado": 1485,
  "obra_id": 9,
  "fecha_inicio": "2026-10-29",
  "horometro_inicio": 1485,
  "notas": "Asignación programada"
}
```

Ejemplo de retiro de la asignación observada:

```json
{
  "asignacion_esperada_id": 81,
  "horometro_esperado": 1485,
  "fecha_fin": "2026-10-29"
}
```

- Fechas civiles `YYYY-MM-DD`. Lecturas no negativas, hasta dos decimales y máximo 99,999,999.99. Notas de asignación opcionales, hasta 1000 caracteres.
- Asignar exige máquina operativa, ninguna asignación activa sin finalizar y obra vigente con pilas activas. La lectura inicial no puede bajar del consolidado. La fecha no puede anteceder la última lectura ni el retiro anterior.
- Retirar finaliza exactamente la asignación esperada y cambia a `en_patio`; no es baja definitiva del catálogo ni modifica el estado operativo. La fecha no puede anteceder su inicio ni las lecturas registradas.
- El retiro móvil no captura un nuevo horómetro final: si hace falta actualizarlo, registrar horas antes de retirar. Se conserva una lectura final ya existente; no se inventa una nueva.
- Una asignación antigua sin finalizar sigue bloqueando otra asignación aunque su obra esté terminada/cancelada. Ese historial debe regularizarse, no ignorarse para crear una segunda asignación activa. La consulta operativa vigente conserva el criterio de obra activa de T04.

## Respuestas, historial y reintentos

- Alta de asignación nueva: 201. Retiro o recuperación de una operación idéntica: 200.
- `data` contiene `maquina`, `asignacion_activa`, `contexto`, `acciones_permitidas` y `operacion`.
- `operacion` contiene `id` y `movimiento_id` (ID del evento), `tipo` (`asignacion_obra` o `retiro_obra`), `asignacion_id`, `obra_id`, `ubicacion_anterior`, `ubicacion_nueva` y `reutilizada`.
- La operación recuperada describe el evento original; la ficha refleja el estado actual, que puede haber cambiado después.
- Contexto obsoleto/incompatible: 409, `code: contexto_maquina_cambio`. Campos, fechas u obra no elegible: 422 con `errors`. Sesión y permisos conservan 401/403; máquina inexistente autorizada, 404.
- `MaquinaAsignacionService` normaliza y compara el payload completo por máquina, usuario, acción y origen móvil. Una huella persistida permite recuperar reintentos exactos sin ejecutar otra asignación/retiro. Otro actor o contenido no recupera ese evento.
- Máquina, asignación, ubicación, movimiento y huella se coordinan bajo transacción. El orden de bloqueos compartido comienza por máquina y después asignación. Si falla el evento o su huella, la transacción debe revertir la operación completa; la comprobación funcional/concurrente queda para el usuario.

## Ajustes compartidos con Laravel

`MaquinaService` conserva sus métodos públicos de asignar/finalizar; ahora relee/bloquea registros y aplica también a la web las validaciones de vigencia, lectura y fechas. Acepta un usuario opcional explícito para conservar al autor del API sin cambiar los llamados web existentes. La web conserva fechas por defecto cuando sus controladores las omiten.

Los eventos de asignación/desasignación se registran aunque el texto de ubicación no cambie, porque cambia el vínculo con la obra. Los cambios genéricos que no cambian ubicación continúan sin generar ruido.

## Migración y despliegue

- Nueva migración: `2026_10_29_140000_add_solicitud_hash_to_maquina_movimientos`.
- Agrega `solicitud_hash`, nullable/única e interna, a `maquina_movimientos`. No agrega tablas ni llena históricos. El modelo oculta la huella al serializar.
- Aplicada únicamente en local (`127.0.0.1:4200/rivera_v2`), lote 152. No se crearon asignaciones, retiros o movimientos operativos ni se modificó producción.
- Antes de usar estos POST en otro ambiente, aplicar la migración:

```sh
php artisan migrate --path=database/migrations/2026_10_29_140000_add_solicitud_hash_to_maquina_movimientos.php --force
```

El flujo de horas sigue necesitando su migración T15.2 previa. No se ejecutaron las demás migraciones pendientes.

## Pendientes y siguiente paso

- Pruebas funcionales y concurrencia: a cargo del usuario. Las comprobaciones estáticas no equivalen a probar asignación/retiro en ejecución. La antigua expectativa de 422 para `uso=asignacion` de T15.2 ya no aplica: ahora requiere permiso y devuelve catálogo o 403 según acceso.
- Actualización del grafo: diferida hasta el cierre, por instrucción del usuario.
- D09: camino/reparación/cambio directo siguen sin endpoint nuevo ni permiso inventado. No se corrigió/habilitó la ruta web de cambio directo en este alcance.
- T15.5: formularios móviles para asignar/retirar, solo después de confirmación. No se cambiaron pantallas en T15.4.
