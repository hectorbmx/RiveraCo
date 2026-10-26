# Ajustes de productos del almacén Giralda

Fecha de apertura: 2026-10-26.

Estado: propuesta de trabajo acordada; implementación de segmentación pendiente.

## Objetivo

Organizar el catálogo de productos de Giralda por familia, subfamilia, marca y aplicación a máquinas, con un flujo completo de alta, edición y consulta. Permitir completar gradualmente los productos existentes sin exigir su clasificación para seguir operando.

Este documento concentra decisiones, hallazgos y checkpoints para ejecutar el cambio en pasos pequeños, evitando ajustes aislados sin continuidad.

## Alcance y límites

- Pantalla de alta: `/productos/create`.
- Edición: expediente del producto, pestaña Información general.
- Catálogo de Giralda: clasificación, búsqueda y filtros.
- Expediente de máquinas: consulta de productos compatibles.
- Administración de familias y subfamilias.
- Revisión y transición de los segmentos históricos.
- Compatibilidad indica dónde puede utilizarse un producto; las salidas de almacén y los servicios conservan el registro de consumo real.
- La tabla `productos` es compartida con otros flujos, incluido Huentitán. Revisar esos consumidores antes de cambiar contratos o reglas globales; el catálogo de Giralda conserva su alcance por almacén.
- Este plan no autoriza ni ejecuta un despliegue a producción.

## Hallazgos

### Tipo de producto

- La migración original `database/migrations/2025_12_02_185510_create_productos_table.php` define `tipo` como texto de hasta 20 caracteres, con valor predeterminado `PRODUCTO`.
- El comentario original lo plantea para distinguir `PRODUCTO / SERVICIO`.
- Alta y edición lo muestran como texto libre y el controlador lo valida.
- `app/Models/Producto.php` no incluye `tipo` en `$fillable`: el flujo actual mediante `create()` y `update()` descarta ese atributo. Se comprobó en memoria que llenar el modelo con `tipo = FILTROS` no conserva el dato.
- En la base local consultada había 3,720 productos, todos con `tipo = PRODUCTO`. Este resultado no constituye una revisión de producción.
- Decisión: retirar Tipo del formulario de Giralda y fijar `PRODUCTO` internamente para las altas desde esta pantalla. No reutilizarlo como familia ni eliminar globalmente la columna sin revisar dependencias.

### Marca: ajuste ya realizado

- La tabla local no tenía la columna `marca`.
- Se agregó `marca`, texto de hasta 100 caracteres, nullable, mediante `database/migrations/2026_10_26_000001_add_marca_to_productos_table.php`.
- Se incorporó al modelo, a la validación del controlador y a los formularios de alta y edición.
- La migración quedó aplicada localmente. Su aplicación en producción no se ha confirmado.
- Se verificó alta con y sin marca, captura posterior, borrado de marca y rechazo de valores de más de 100 caracteres. Las pruebas de persistencia se revirtieron sin dejar productos de prueba.
- Pasaron validaciones de sintaxis PHP, compilación de vistas y revisión de codificación.

### Segmentación histórica y máquinas

- Ya existen `catalogo_segmentos` y los campos `uso_type`, `uso_id`, `uso_label` y `segmento_legacy` en productos.
- El esquema actual permite un destino de uso: segmento o máquina. No representa compatibilidad con varias máquinas simultáneamente.
- La importación `app/Console/Commands/ImportProductosCsv.php` crea segmentos a partir de los datos históricos.
- En la base local se observaron 3,493 productos con `uso_type = segmento` y 227 sin destino de uso. No se observaron productos con `uso_type = maquina` en esa consulta.
- Los segmentos mezclan conceptos: Aceites y Consumible aparecen junto con Bauer 12H, Cortadora y otros equipos o agrupaciones.
- No convertir automáticamente todos los segmentos en familias ni asociarlos a máquinas por coincidencia de texto.

### Otros datos disponibles

- Ya existen SKU, unidad, marca y descripción.
- Existe `tipo_inventario`, inicialmente destinado a consumible/herramienta; es un concepto distinto de familia y de PRODUCTO/SERVICIO.
- Existe `especificaciones_tecnicas` como JSON nullable. Revisar su utilización antes de agregar estructuras equivalentes.
- Las máquinas tienen código, nombre, marca, modelo y número de serie, útiles para presentar el selector de compatibilidad.

## Diseño propuesto

| Dato | Propósito | Regla propuesta |
| --- | --- | --- |
| Familia | Agrupación principal, por ejemplo Filtros | Catálogo administrable; asignación opcional |
| Subfamilia | Detalle, por ejemplo Filtros de aceite | Opcional; debe pertenecer a la familia seleccionada |
| Marca | Fabricante del producto | Campo opcional ya implementado |
| Número de parte | Referencia del fabricante | Opcional; separado del SKU interno |
| SKU | Código interno | Conservar las reglas actuales |
| Aplicación | Pendiente, uso general o máquinas específicas | Los registros sin revisar quedan pendientes |
| Máquinas compatibles | Equipos donde puede utilizarse | Relación de muchos a muchos, opcional |
| Especificaciones técnicas | Medidas, capacidad y otras características | Aprovechar el campo existente cuando corresponda |

La compatibilidad se propone mediante una tabla intermedia `maquina_producto`, con una sola relación por pareja producto/máquina. No tener máquinas asignadas se representa con ausencia de relaciones, sin un `maquina_id` nullable en productos.

Reglas de aplicación:

- **Pendiente:** todavía no se ha determinado la aplicación.
- **Uso general:** el producto se utiliza sin asignación a máquinas específicas; no equivale a certificar compatibilidad técnica con todas las máquinas.
- **Máquinas específicas:** requiere al menos una máquina seleccionada; admite varias.
- Cambiar a pendiente o uso general debe manejar explícitamente las relaciones anteriores para no dejar compatibilidades ocultas.
- No duplicar el producto ni sus existencias por asignarlo a varias máquinas.
- Desactivar una familia o subfamilia no debe borrar productos ni sus clasificaciones históricas.

## Metas

- Retirar la captura manual de Tipo en Giralda.
- Separar clasificación comercial, identificación del fabricante y compatibilidad con equipos.
- Usar familias y subfamilias controladas para evitar variantes de texto que fragmenten los filtros.
- Permitir clasificar productos nuevos y existentes sin interrumpir la operación actual.
- Consultar productos compatibles tanto desde el catálogo como desde una máquina.
- Mantener separadas compatibilidad y consumo.
- Conservar trazabilidad de los segmentos históricos durante su revisión.

## Plan de ejecución y checkpoints

Cada paso debe registrar cambios, validaciones y pendientes antes de marcarse completo. Este documento registra el plan; no da por implementados sus pasos.

### Paso 1. Definir la clasificación inicial

- [ ] Acordar familias y subfamilias iniciales con ejemplos reales del catálogo.
- [ ] Confirmar los estados de aplicación y sus transiciones.
- [ ] Definir reglas de nombres y duplicados de los catálogos.
- [ ] Revisar si número de parte requiere reglas adicionales; no imponer unicidad global sin validar el negocio.

Checkpoint: listado inicial y reglas documentadas; cada concepto tiene una función clara y no se confunde con marca, tipo de inventario o máquina.

### Paso 2. Crear la estructura de datos

- [ ] Agregar catálogos y relaciones de familia/subfamilia.
- [ ] Agregar número de parte y estado de aplicación.
- [ ] Crear la relación de muchos a muchos producto/máquina, evitando parejas duplicadas.
- [ ] Permitir productos existentes sin familia, subfamilia ni número de parte, y con aplicación pendiente.
- [ ] Revisar impacto en Huentitán, importaciones y demás consumidores de productos.
- [ ] Definir migraciones y comportamiento de reversión.

Checkpoint: migraciones verificadas; registros existentes conservan sus datos, existencias y funcionamiento.

### Paso 3. Administrar familias y subfamilias

- [ ] Permitir crear, editar y desactivar familias y subfamilias.
- [ ] Validar pertenencia de subfamilias y evitar duplicados según las reglas acordadas.
- [ ] Conservar las clasificaciones utilizadas al desactivar elementos.
- [ ] Integrar permisos siguiendo el patrón existente del proyecto.

Checkpoint: catálogos administrables con restricciones coherentes y sin pérdida de asociaciones históricas.

### Paso 4. Actualizar el alta de productos

- [ ] Quitar Tipo del formulario y asegurar `PRODUCTO` internamente al guardar desde este flujo.
- [ ] Incorporar familia, subfamilia y número de parte, manteniendo Marca.
- [ ] Incorporar aplicación y selector múltiple de máquinas por código y nombre.
- [ ] Agrupar visualmente identificación, clasificación y aplicación.
- [ ] Validar datos relacionados en el servidor y guardar producto/relaciones de forma consistente.

Checkpoint: se puede crear un producto sin clasificación, de uso general o compatible con una o varias máquinas; los errores conservan la captura del formulario.

### Paso 5. Actualizar la edición de productos

- [ ] Mostrar y permitir corregir los mismos datos del alta.
- [ ] Permitir agregar y quitar máquinas compatibles sin duplicar relaciones.
- [ ] Resolver cambio de familia cuando la subfamilia anterior deje de corresponder.
- [ ] Manejar explícitamente los cambios de estado de aplicación.

Checkpoint: un producto existente puede clasificarse y modificar su compatibilidad sin alterar movimientos ni existencias.

### Paso 6. Agregar filtros al catálogo

- [ ] Filtrar por familia, subfamilia, marca y máquina compatible.
- [ ] Permitir búsqueda por número de parte junto con los identificadores actuales.
- [ ] Identificar pendientes de clasificación y de aplicación, diferenciando ambos casos.
- [ ] Mantener filtros al paginar y conservar el alcance del almacén Giralda.

Checkpoint: filtros combinables, resultados sin duplicados y sin inclusión accidental del catálogo de otro almacén.

### Paso 7. Consultar compatibilidad desde máquinas

- [ ] Incorporar productos compatibles al expediente de la máquina.
- [ ] Mostrar identificación y clasificación útiles para localizar el producto.
- [ ] Distinguir claramente esta consulta del historial de consumo.
- [ ] Definir el alcance por almacén cuando se presenten existencias.

Checkpoint: una máquina muestra sus productos compatibles; un mismo producto puede aparecer en varias máquinas sin duplicarse en inventario.

### Paso 8. Revisar y clasificar datos históricos

- [ ] Preparar inventario de segmentos y cantidades de productos asociados.
- [ ] Proponer equivalencias hacia familias, subfamilias o máquinas.
- [ ] Revisar ambigüedades: una etiqueta puede referirse a una máquina, un modelo o una agrupación.
- [ ] Revisar las equivalencias antes de ejecutar su aplicación masiva.
- [ ] Conservar valores históricos y registrar qué productos quedan pendientes.
- [ ] Evitar que importaciones posteriores sobrescriban la clasificación revisada sin una regla explícita.

Checkpoint: clasificación verificable y trazable, sin inferencias automáticas de compatibilidad por nombre.

### Paso 9. Validar y preparar producción

- [ ] Probar alta, edición, campos opcionales y límites de captura.
- [ ] Probar pertenencia de subfamilias y sincronización de múltiples máquinas.
- [ ] Probar estados de aplicación, filtros y productos existentes sin clasificar.
- [ ] Revisar consumidores compartidos y permisos.
- [ ] Ejecutar validaciones PHP, vistas, codificación UTF-8 y actualización del grafo cuando se modifique código.
- [ ] Preparar instrucciones de migración, despliegue y recuperación.
- [ ] Registrar por separado lo aplicado localmente y lo confirmado en producción.

Checkpoint: cambio completo y verificable, con pasos de despliegue claros y sin dar por aplicada ninguna migración remota.

## Registro de avance

| Fecha | Hito | Estado / evidencia |
| --- | --- | --- |
| 2026-10-26 | Análisis de Marca | Campo ausente en tabla local; agregado nullable en alta y edición; validaciones locales correctas |
| 2026-10-26 | Análisis de Tipo | Se confirmó intención PRODUCTO/SERVICIO y descarte del valor capturado por `$fillable` |
| 2026-10-26 | Revisión de segmentos | Clasificación histórica mezcla familias y equipos; relación actual no admite múltiples máquinas |
| 2026-10-26 | Plan integral | Nueve pasos documentados; segmentación y compatibilidad pendientes de implementación |

Al completar cada paso, agregar fecha, archivos afectados, pruebas realizadas, decisiones y pendientes. No marcar un checkpoint como completo solo por haber creado su estructura.
