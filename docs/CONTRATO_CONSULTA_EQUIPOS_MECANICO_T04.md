# T04: consulta de equipos desde el panel mecánico

- Fecha: 2026-10-23.
- Estado: cuatro rutas implementadas y verificadas en backend; integración de pantallas pendiente en T13.
- Plan: [PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md](PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md).

## Acceso

Todas las rutas requieren Bearer de Sanctum, `Accept: application/json`, usuario activo en `usuarios_app` y permiso efectivo `app.mecanico.access`. No se requiere obra asignada. El permiso gerencial, el permiso regular o el nombre del rol por sí solos no conceden este acceso.

La autorización se ejecuta antes de resolver el equipo de la URL. Un usuario sin permiso recibe 403; un usuario autorizado que consulta un ID inexistente recibe 404. Un invitado recibe 401. Estas rutas son de lectura: no crean ni modifican equipos o servicios.

## Rutas y filtros

| Ruta GET | Parámetros |
|---|---|
| `/api/v1/mecanico/maquinas` | `q`, `estatus`, `asignada`, `en_uso`, `page`, `per_page`. |
| `/api/v1/mecanico/maquinas/{maquina}` | ID del equipo. |
| `/api/v1/mecanico/vehiculos` | `q`, `estatus`, `asignado`, `page`, `per_page`. |
| `/api/v1/mecanico/vehiculos/{vehiculo}` | ID del equipo. |

- Máquinas: búsqueda por nombre, código, modelo o placas; `estatus` filtra el campo `estado`.
- Vehículos: búsqueda por marca, modelo, placas, serie, tipo o nombre/apellidos del empleado asignado; `estatus` filtra el campo del mismo nombre.
- `asignada`/`asignado`: `1` con asignación, `0` sin asignación. `en_uso=1` exige asignación de maquinaria.
- Paginación predeterminada de 20; `per_page` se limita a 1–50, siguiendo la consulta gerencial existente.
- Orden de máquinas por nombre; vehículos por marca, modelo y placas, sin cambiar la consulta gerencial.
- Consultan todos los equipos que devuelve el catálogo existente, sin filtrar por obra del mecánico.

## Formato de listado

El listado mecánico devuelve un arreglo directo en `data`; la paginación queda en `meta`. Esto no cambia la estructura histórica del listado gerencial, que conserva su paginador anidado.

Ejemplo de estructura, con datos ficticios:

```json
{
  "ok": true,
  "data": [],
  "meta": {"current_page": 1, "last_page": 1, "per_page": 20, "total": 0}
}
```

### Campos de máquinas

`id`, `codigo`, `nombre`, `tipo`, `marca`, `modelo`, `placas`, `horometro_base`, `estado`, `asignada`, `obra_activa`.

`obra_activa` es nula o contiene `id`, `nombre`, `clave_obra`, `estatus_nuevo`. Describe la ubicación/asignación del equipo y no representa una obra asignada al mecánico.

### Campos de vehículos

`id`, `marca`, `modelo`, `anio`, `color`, `placas`, `serie`, `tipo`, `estatus`, `fecha_registro`, `foto_url`, `asignado`, `asignacion_actual`.

`asignacion_actual` es nula o contiene identificadores, fechas, kilómetros inicial/final y empleado con `id` y `nombre`. No incluye teléfono, notas administrativas ni fotos de la asignación.

## Formato de detalle

Máquina: `ok`, `data.maquina`, `data.asignacion_activa`, `data.mantenimientos_recientes`, `data.mantenimientos_resumen`.

Vehículo: `ok`, `data.vehiculo`, `data.asignacion_actual`, `data.mantenimientos_recientes`, `data.mantenimientos_resumen`.

La ficha de máquina incluye identidad, marca/modelo/serie/placas/color, horómetro base, estado, ubicación y notas del equipo. La asignación activa contiene fechas, lectura inicial, estado y obra. La ficha de vehículo usa los mismos campos operativos de su listado.

### Historial de servicios

Se incluyen como máximo diez mantenimientos recientes, ordenados por fecha programada descendente y luego ID descendente. Se conserva el historial existente del equipo, incluyendo el campo `tipo` para distinguir programado/emergencia. Esta consulta no reemplaza el futuro listado paginado de servicios programados de T07.

Cada mantenimiento conserva: ID, tipo, categoría, descripción, kilómetros actual/próximo, horómetro, fechas programada/inicio/fin, estado, costo total, notas y mecánico con ID/nombre. Los costos son de consulta, según la decisión de T01.

`mantenimientos_resumen` conserva los contadores actuales: `total`, `pendiente`, `en_proceso`, `completado`, `cancelado`. `total` contempla también otros estados existentes como borrador u orden generada; no se agregó un contador por cada estado nuevo en esta tarea.

### Datos excluidos

Las respuestas mecánicas no incluyen pólizas, seguros recientes, comprobantes, documentos administrativos, sus resúmenes ni historial de asignaciones. La proyección de salida usa una lista explícita de campos; no basta ocultarlos en la pantalla.

## Implementación compartida

- `app/Services/Mobile/Equipos/MaquinasConsultaService.php`: consultas y transformación existentes de máquinas.
- `app/Services/Mobile/Equipos/VehiculosConsultaService.php`: consultas y transformación existentes de vehículos.
- `app/Services/Mobile/Equipos/EquipoMecanicoPresenter.php`: proyección de datos operativos y formato mecánico.
- Los controladores gerenciales delegan `index`/`show` a los servicios sin cambiar sus respuestas. Los endpoints gerenciales de registros/horómetro permanecen en su controlador y no se publicaron como rutas mecánicas nuevas en T04.
- Los servicios conservan por ahora la consulta completa gerencial y el presenter limita lo enviado al mecánico. Reducir las consultas de seguros/documentos para este perfil puede evaluarse como optimización posterior; no se han expuesto esos datos en el contrato mecánico.

## Validación y límites

- 24 pruebas de acceso, sesión, proyección y compatibilidad de equipos; 214 comprobaciones.
- 14 escenarios gerenciales comparados antes/después por hash de la respuesta completa: resultados idénticos. Se usó lectura local sin modificar registros; el archivo temporal de hashes se elimina al cerrar T04.
- 20 escenarios mecánicos con consultas locales de solo lectura: límites de paginación, segunda página, búsqueda/estado sin coincidencia, asignado/no asignado y detalle de ambos tipos de equipo.
- Pruebas HTTP con datos controlados para autorización, usuario inactivo, revocación/denegación, 404, proyección y ausencia de rutas de escritura.
- Sintaxis PHP, estilo de archivos nuevos, UTF-8 y rutas registradas verificados.
- No se crearon permisos ni se alteraron usuarios. La creación/asignación manual de `app.mecanico.access` sigue siendo necesaria para activar el acceso de un usuario real si aún no la realizó el administrador.
- No se implementaron productos de Giralda (T05), creación de servicios (T06) ni pantallas móviles (T13).
