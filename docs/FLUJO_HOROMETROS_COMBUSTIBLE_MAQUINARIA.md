# Flujo de horómetros y combustible de maquinaria

## Objetivo

Consolidar las horas trabajadas y el consumo de diesel de cada máquina para mostrar un resumen mensual confiable y auditable.

La información debe conservar siempre su fuente de origen y no debe permitir que una nueva captura reduzca o replique el horómetro existente.

## Fuentes de información

### 1. Mecánico desde el módulo de máquinas

El mecánico puede registrar o ajustar el horómetro desde:

```text
/maquinas
```

Fuente actual:

```text
maquina_horometro_ajustes
```

Datos relevantes:

- `maquina_id`
- `obra_id` opcional
- `obra_maquina_id` opcional
- `horometro_anterior`
- `horometro_nuevo`
- `diferencia`
- `origen`
- usuario que capturó
- fecha de creación

### 2. Residente desde Laravel

El residente registra horas desde el contexto de la obra.

Fuente actual:

```text
obra_maquina_registros
```

Datos relevantes:

- `maquina_id`
- `obra_id`
- `obra_maquina_id`
- `inicio`
- `fin`
- `horometro_inicio`
- `horometro_fin`
- `horas`
- `origen`
- usuario que capturó

### 3. Residente desde la aplicación móvil

La aplicación móvil utiliza el flujo de registro de maquinaria y debe conservar las mismas reglas que Laravel.

La fuente debe conservar el origen para diferenciar:

```text
web
app
web_maquinas
```

## Fuente de verdad

El horómetro válido es global para la máquina, sin importar:

- Quién lo capturó.
- Si fue capturado desde app o web.
- Si fue capturado por un mecánico o residente.
- Si tiene obra relacionada.
- Si tiene asignación activa.

Las lecturas deben consolidarse en una secuencia única por `maquina_id`.

## Reglas de negocio

### Regla 1: el horómetro debe crecer

El nuevo horómetro debe ser estrictamente mayor al máximo horómetro válido existente.

No se permite:

- Un valor menor.
- Un valor igual.
- Un valor que repita una lectura anterior.

La comparación debe usar todas las fuentes de la máquina.

### Regla 2: la fecha debe respetar la secuencia

No se permite registrar un horómetro mayor con una fecha anterior a la última lectura válida.

La fecha debe ser igual o posterior a la última lectura válida, considerando también la hora cuando exista.

Las correcciones históricas deben hacerse mediante un flujo autorizado de corrección, no creando una captura nueva con fecha anterior.

### Regla 3: validación en dos capas

La interfaz debe validar en tiempo real para orientar al usuario.

El backend debe validar nuevamente porque no puede confiar en el navegador.

### Regla 4: concurrencia

La validación y el guardado deben ejecutarse dentro de una transacción.

Debe evitarse que dos usuarios registren simultáneamente el mismo horómetro o dos valores basados en el mismo máximo anterior.

### Regla 5: trazabilidad

Cada lectura debe conservar:

```text
maquina_id
obra_id opcional
obra_maquina_id opcional
horometro_anterior
horometro_nuevo
diferencia
fecha_operativa
origen
usuario
```

## Fecha operativa

Los registros de residente ya tienen fechas `inicio` y `fin`.

Los ajustes del mecánico actualmente dependen de `created_at`. Para comparar correctamente ambas fuentes se requiere agregar una fecha operativa explícita, sugerencia:

```text
fecha_registro
```

El campo debe:

- Mostrarse en el modal del mecánico.
- Tener por defecto la fecha actual.
- Permitir selección manual.
- Validarse contra la última lectura válida.

## Cálculo de horas

No se deben sumar directamente todas las diferencias de las fuentes porque el residente y el mecánico podrían reportar el mismo avance.

Las lecturas deben normalizarse a una colección común:

```text
maquina_id
fecha
horometro_anterior
horometro_nuevo
diferencia
origen
registro_id
obra_id
usuario
```

Después se debe:

1. Ordenar por fecha.
2. Mantener una secuencia creciente.
3. Ignorar lecturas repetidas.
4. Ignorar lecturas menores.
5. Detectar conflictos entre fuentes.
6. Contabilizar únicamente avances válidos.
7. Agrupar el avance por mes.

Para el MVP, el avance se asignará al mes de la fecha de la lectura. Más adelante puede dividirse un intervalo que cruce dos meses.

## Fuente de diesel

Inicialmente el diesel se tomará solamente de las salidas de inventario aplicadas.

Condiciones:

- Documento tipo `salida`.
- Documento en estado `aplicado`.
- Detalle relacionado con `maquina_id`.
- Producto identificado como combustible diesel.
- Fecha del documento.
- Cantidad expresada en litros.

No deben considerarse:

- Entradas.
- Borradores.
- Documentos cancelados.
- Salidas sin máquina relacionada para esta gráfica.

## Identificación del diesel

Actualmente la salida de combustible se consulta por máquina, pero el catálogo de productos no tiene una clasificación formal visible para distinguir diesel de otros productos.

No se recomienda depender permanentemente de buscar la palabra `DIESEL` en el nombre.

Alternativas:

1. Agregar una clasificación de combustible al producto.
2. Crear un catálogo de tipos de combustible.
3. Relacionar el producto con el tipo `diesel`, `gasolina` u otro.

## Resumen mensual

Para cada mes del año seleccionado se debe mostrar:

- Horas válidas trabajadas.
- Litros de diesel salidos.
- Cantidad de lecturas.
- Fuentes involucradas.
- Conflictos, si existen.

Resumen anual:

- Horas acumuladas.
- Litros de diesel acumulados.
- Litros por hora, cuando los datos sean suficientes.

## Gráfica

La gráfica debe conservar el patrón usado para vehículos:

- Botón con icono de gráfica.
- Modal compacto.
- Enero a diciembre.
- Barras mensuales.
- Valores exactos visibles.
- Totales anuales.

Las horas y los litros deben tener escalas diferenciadas o etiquetas claras porque representan unidades distintas.

## Hallazgos actuales

1. `MaquinaHorometroService` calcula el horómetro actual usando el máximo de varias fuentes.
2. El ajuste del mecánico permitía un valor igual porque comparaba con `<`; quedó corregido en el paso 4 con comparación estricta `<=`.
3. `maquina_horometro_ajustes` no tiene fecha operativa propia; actualmente depende de `created_at`.
4. `obra_maquina_registros` ya guarda inicio, fin, horómetro inicial, horómetro final y horas.
5. Las salidas de inventario ya tienen `maquina_id` en el detalle.
6. La vista de combustible de máquina consulta salidas aplicadas asignadas a la máquina.
7. La consulta actual de salidas todavía requiere una regla formal para identificar únicamente diesel.
8. Las dos fuentes de horómetro pueden registrar el mismo avance y provocar doble conteo si se suman directamente.
9. La fecha de una lectura debe validarse junto con el horómetro; no basta con revisar solamente el valor numérico.
10. Los datos existentes pueden contener lecturas repetidas, menores o fuera de secuencia y requieren revisión antes de usarse como base estadística.

## Barrido técnico general

### Entradas de captura identificadas

| Origen | Ruta o punto de entrada | Persistencia | Servicio compartido | Situación |
|---|---|---|---|---|
| Mecánico | `/maquinas` | `maquina_horometro_ajustes` | `MaquinaHorometroService::crearAjuste` | Es un ajuste global de la máquina; no tiene fecha operativa propia. |
| Residente web | Tab de maquinaria de una obra | `obra_maquina_registros` | `MaquinaHorometroService::crearRegistro` | Tiene fecha y contexto de obra/asignación. |
| Aplicación móvil | `api/v1/maquinas/{obraMaquina}/registros` | `obra_maquina_registros` | `MaquinaHorometroService::crearRegistro` | Reutiliza la validación del servicio, con control adicional de acceso a la obra. |

### Reglas que hoy sí están centralizadas

- Los registros web del residente y de la app móvil validan que el horómetro final sea estrictamente mayor al inicial de la asignación.
- El servicio calcula un horómetro actual global usando registros de obra, asignaciones, mantenimientos, ajustes y el horómetro base de la máquina.
- Las salidas de inventario conservan `maquina_id` en el detalle y se pueden consultar desde el tab de combustible.
- El inventario solo se considera para combustible cuando la salida está aplicada; los borradores y cancelados quedan fuera de la consulta actual.

### Brechas encontradas

1. `crearAjuste` permite igualdad con el máximo actual porque usa una comparación equivalente a `nuevo < anterior`; debe cambiarse a una regla estricta `nuevo <= anterior`.
2. La fecha enviada por el residente web se convierte en `inicio` y `fin`, pero no existe una validación global que impida capturar un horómetro mayor con una fecha anterior a otra lectura válida.
3. Los ajustes del mecánico no reciben ni guardan fecha operativa; dependen de `created_at`, por lo que no pueden ordenarse correctamente contra una lectura histórica del residente.
4. `horometroActual` mezcla lecturas operativas con valores base de asignación y mantenimientos. Antes de calcular horas mensuales se debe definir cuáles son lecturas estadísticas y cuáles solo sirven como referencia inicial.
5. El endpoint de consulta de la app prioriza el último registro de la asignación, mientras que el servicio usa el máximo global de la máquina. Esto puede producir valores distintos entre app, web y módulo de máquinas.
6. La validación está en el servicio, pero no existe una protección equivalente a nivel de base de datos ni un bloqueo explícito de la secuencia completa para dos capturas concurrentes.
7. La vista de combustible de maquinaria actualmente lista salidas aplicadas relacionadas con la máquina, pero no filtra formalmente diesel mediante una clasificación del producto.
8. En el checkout actual no se encontró todavía un dataset mensual ni una gráfica de maquinaria equivalente a la de vehículos; el tab recibe la colección de salidas, pero falta construir la consolidación mensual de horas y diesel.
9. El detalle de inventario identifica el equipo, pero no guarda un tipo formal de combustible; para el MVP se requiere una regla controlada para distinguir diesel de otros productos sin depender permanentemente del texto del nombre.
10. La consulta de combustible conserva un buen punto de auditoría porque enlaza cada salida con su documento, fecha, obra opcional y estado aplicado.

### Decisiones que deben cerrarse antes de programar

- Agregar `fecha_registro` a `maquina_horometro_ajustes` y usarla como fecha operativa del mecánico.
- Definir si los valores de mantenimiento y de inicio de asignación serán solo referencias o también lecturas estadísticas.
- Hacer que la lectura válida sea una secuencia global por máquina, no solo por asignación.
- Crear una clasificación formal de combustible o una relación equivalente para identificar diesel.
- Resolver concurrencia dentro de una transacción antes de permitir capturas simultáneas.

### Checkpoint del barrido

- [x] Rutas web, API y puntos de entrada identificados.
- [x] Modelos, migraciones y tablas de las dos fuentes revisados.
- [x] Servicio común de validación localizado.
- [x] Fuente actual de diesel localizada en salidas aplicadas.
- [x] Brechas de fecha, igualdad, secuencia, doble conteo y clasificación de diesel documentadas.
- [x] Confirmado que este paso no modifica comportamiento de la aplicación.
- [x] Agregar fecha operativa al ajuste del mecánico.
- [x] Definir y aplicar el validador global de secuencia.

### Checkpoint del paso 3

- [x] Se agregó `fecha_registro` nullable a `maquina_horometro_ajustes` para no romper datos existentes.
- [x] Se agregó el campo al modelo y a sus casts.
- [x] El modal de `/maquinas` muestra la fecha cuando se ajusta una máquina sin asignación activa.
- [x] La fecha se establece por defecto al día actual.
- [x] Los nuevos ajustes sin asignación activa requieren fecha y la guardan como fecha operativa.
- [x] Los registros históricos conservan `created_at` como respaldo mientras `fecha_registro` sea null.
- [x] Validar la fecha contra la secuencia global del horómetro en el paso 4.
- [ ] Definir backfill opcional de `fecha_registro` a partir de `created_at`.

### Checkpoint del paso 4

- [x] La validación global quedó centralizada en `MaquinaHorometroService`.
- [x] Se rechaza un horómetro menor o igual al máximo válido de la máquina.
- [x] Se valida la fecha contra la última lectura comparable.
- [x] Los ajustes antiguos usan `fecha_registro` y, si es null, `created_at` como respaldo.
- [x] Se consideran registros de obra/app, ajustes del mecánico y mantenimientos con horómetro.
- [x] Se agregó bloqueo de la fila de máquina dentro de la transacción para reducir capturas concurrentes sobre el mismo máximo.
- [x] Las entradas web de obra, módulo de máquinas y API móvil pasan por la misma regla del servicio.
- [ ] Agregar validación en tiempo real con badge en los formularios.
- [ ] Revisar datos históricos fuera de secuencia antes de construir estadísticas mensuales.

### Checkpoint del paso 5

- [x] La captura del mecánico usa la nueva fecha operativa.
- [x] Se valida contra el horómetro global de todas las fuentes.
- [x] Se guardan horómetro anterior, nuevo, diferencia, origen y usuario.
- [x] Se agregó alerta visual al perder el foco del horómetro o de la fecha.
- [x] El botón de guardado se bloquea cuando la captura es inválida.
- [x] El backend mantiene la validación definitiva aunque el navegador sea manipulado.

### Checkpoint del paso 6

- [x] La captura web del residente conserva la obra y la asignación de maquinaria.
- [x] El modal recibe el último horómetro y la última fecha comparable de la máquina.
- [x] Se agregó validación visual para horómetro menor o igual.
- [x] Se agregó validación visual para fecha anterior.
- [x] El botón de guardado se bloquea cuando la captura es inválida.
- [x] El controlador conserva la fecha seleccionada y el servicio aplica la regla global.
- [ ] Validar manualmente una captura válida y dos intentos inválidos desde la vista de obra.

### Checkpoint del paso 8

- [x] La edición web solo apunta al último registro de la asignación.
- [x] La edición valida que el nuevo horómetro sea mayor al máximo histórico anterior.
- [x] Se agregó ruta y método para eliminar registros de horas.
- [x] La eliminación requiere `borrar_horas_maquina.access`.
- [x] La eliminación solo permite el último registro de la asignación.
- [x] La eliminación usa transacción y bloqueo del registro.
- [x] La vista muestra iconos separados para editar y eliminar, con confirmación para borrar.
- [ ] Crear/asignar `borrar_horas_maquina.access` en cada ambiente.
- [ ] Probar edición, eliminación y recalculo del horómetro sugerido después de eliminar.

### Checkpoint del paso 9

- [x] Se creó `MaquinaHorometroConsolidacionService`.
- [x] Se unifican registros de residente web/app y ajustes del mecánico.
- [x] Cada lectura conserva fuente, fecha, obra, registro de origen y valores de horómetro.
- [x] Las lecturas se ordenan por fecha operativa y creación.
- [x] Se marcan lecturas válidas, duplicadas y menores.
- [x] Se calcula únicamente el avance válido acumulado.
- [x] La consolidación se muestra como resumen en el tab de combustible de la máquina.
- [ ] Revisar y clasificar manualmente los conflictos históricos antes de usar el resumen mensual.

## Plan de trabajo

### Paso 1. Documentar el flujo

- Confirmar fuentes.
- Confirmar campos.
- Confirmar reglas de fecha y horómetro.

**Checkpoint:** reglas aprobadas.

### Paso 2. Revisar estructura actual

- Revisar modelos y migraciones.
- Revisar controladores web.
- Revisar endpoints móviles.
- Detectar inconsistencias existentes.

**Checkpoint:** mapa técnico documentado. Completado en el barrido general; quedan pendientes las decisiones de diseño indicadas arriba.

### Paso 3. Agregar fecha operativa al mecánico

- Crear migración.
- Actualizar modelo.
- Actualizar modal.
- Guardar la fecha seleccionada.

**Checkpoint:** ambas fuentes tienen fecha comparable.

### Paso 4. Crear servicio único de validación

- Obtener máximo histórico.
- Obtener fecha de última lectura.
- Rechazar menor o igual.
- Rechazar fecha anterior.
- Retornar mensajes consistentes.

**Checkpoint:** una sola regla para todos los orígenes.

### Paso 5. Corregir captura del mecánico

- Aplicar comparación estricta.
- Validar fecha.
- Guardar origen y usuario.
- Guardar diferencia.

### Paso 6. Corregir captura web del residente

- Aplicar validación global.
- Validar fecha.
- Mantener contexto de obra opcional según el flujo.

### Paso 7. Corregir captura móvil

- Aplicar la misma regla.
- Devolver errores JSON controlados.
- Evitar duplicados simultáneos.

### Paso 8. Revisar edición y eliminación

- Definir si se puede editar una lectura.
- Evitar romper la secuencia.
- Recalcular cuando se elimine la última.
- Registrar correcciones.

### Paso 9. Consolidar lecturas

- Unificar las dos fuentes.
- Ordenar por fecha.
- Ignorar duplicados.
- Detectar conflictos.
- Calcular avances válidos.

### Paso 10. Consolidar diesel

- Filtrar salidas aplicadas.
- Identificar diesel.
- Agrupar litros por mes.
- Excluir documentos no aplicados.

### Paso 11. Construir resumen mensual

- Horas válidas.
- Litros de diesel.
- Totales anuales.
- Rendimiento cuando sea posible.

### Paso 12. Construir gráfica

- Botón con icono.
- Modal compacto.
- Barras mensuales.
- Valores y totales.
- Selector de año.

### Paso 13. Pruebas funcionales

- Lectura válida del mecánico.
- Lectura válida del residente web.
- Lectura válida desde app.
- Horómetro igual al máximo.
- Horómetro menor al máximo.
- Fecha anterior.
- Capturas simultáneas.
- Lecturas duplicadas entre fuentes.
- Diesel aplicado.
- Diesel borrador.
- Diesel cancelado.
- Resumen mensual.
- Gráfica anual.

## Checkpoints globales

- [ ] Flujo y fuentes documentados.
- [ ] Fecha operativa disponible para todas las fuentes.
- [ ] Regla estricta de horómetro aplicada.
- [ ] Regla de fecha aplicada.
- [ ] Concurrencia protegida.
- [ ] Doble conteo controlado.
- [ ] Diesel identificado formalmente.
- [ ] Resumen mensual validado.
- [ ] Gráfica validada.
- [ ] Datos existentes revisados.

## Estado actual

- Documento creado.
- La fecha operativa y la validación global de secuencia ya están implementadas.
- Los datos históricos sin `fecha_registro` conservan `created_at` como respaldo; el backfill sigue pendiente.
- La gráfica final queda pendiente hasta cerrar la consolidación de horas y la identificación formal del diesel.
