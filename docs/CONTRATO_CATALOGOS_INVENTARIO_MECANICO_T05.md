# T05: catálogos de captura y productos para mecánico

- Fecha: 2026-10-23.
- Estado: contratos de lectura implementados y validados. No se crean servicios, productos, tipos preventivos ni movimientos de inventario en esta tarea.
- Plan: [PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md](PLAN_APP_MECANICO_SERVICIOS_PROGRAMADOS.md).

## Autorización común

Bearer de Sanctum, usuario app activo y permiso efectivo `app.mecanico.access`. No requiere obra. Usar `Accept: application/json`.

Las dos rutas están en el grupo mecánico existente. Tener acceso únicamente a residente o gerencial no concede acceso mecánico. Respuestas de invitado: 401; sin permiso/inactivo: 403. No hay métodos de escritura para estos contratos.

## GET /api/v1/mecanico/servicios/catalogos

Parámetros opcionales: `maquina_id` o `vehiculo_id`, enteros positivos y mutuamente excluyentes. Sin ID devuelve los catálogos generales. ID de equipo inexistente: 404; ambos IDs, valores inválidos o negativos: 422.

Respuesta: `ok: true` y `data` con:

| Campo | Contenido |
|---|---|
| `tipo` | Siempre `programado`. |
| `servicios_preventivos.maquinaria` | Tipos activos del ámbito maquinaria, ordenados como en web. |
| `servicios_preventivos.vehiculo` | Tipos activos del ámbito vehículo; puede ser una lista vacía. |
| `categoria_mantenimiento` | `captura: texto_libre`, `obligatoria: true`, `max_caracteres: 100`. La web no tiene un catálogo cerrado de categorías. |
| `estados_servicio` | Estados actuales del modelo/flujo, informativos: borrador, orden_generada, pendiente, en_proceso, completado, cancelado. No conceden autorización para cambiar a cualquier estado. |
| `equipo` | `null`, o `ambito` e `id` del equipo seleccionado. |
| `lectura_sugerida` | `null`, o `campo`, `valor`, `unidad` para el equipo seleccionado. |
| `almacen` | Identidad del Giralda activo resuelto en servidor, o `null` si falta configuración. |
| `materiales_por_servicio` | Objeto indexado por ID de tipo preventivo; vacío si no se seleccionó máquina o no hay plantilla. |
| `capacidades` | Banderas de inventario y configuración real; ver límites de vehículos. |
| `avisos` | Mensajes informativos de configuración faltante o funcionalidad no implementada. |

Cada tipo preventivo contiene: `id`, `ambito`, `nombre`, `codigo`, `unidad`, `intervalo_valor`, `intervalo_meses`, `alerta_valor`, `alerta_dias` y `orden`.

### Lecturas sugeridas

- Máquina: `campo: horometro`, unidad `horas`. Reutiliza `MaquinaHorometroService::horometroSugeridoParaAsignacion()`, como el formulario web; integra las fuentes del servicio existente y su respaldo a cero si no hay lectura.
- Vehículo: `campo: km_actuales`, unidad `km`. Reutiliza `PreventivoVehiculoService::calcularParaVehiculo()['km_actual']`, con las reglas existentes de logs/asignaciones. Puede ser `null` si no hay lectura.
- Se consulta solo el equipo seleccionado; no se calculan lecturas de toda la flota en cada petición.
- Son sugerencias de captura, no actualizaciones del equipo ni permisos para omitir las validaciones que implementará T06.

### Materiales de máquina

Se reutiliza la selección web: material activo y tipo activo; si existen materiales específicos para esa máquina y tipo, sustituyen a la plantilla general completa de ese tipo. Si no existen, se usa la plantilla general. No se mezclan automáticamente ambas listas.

Cada material conserva los campos web: `id`, `producto_id`, `producto`, `sku`, `cantidad`, `unidad`, `especifico`, `disponible`, `faltante`, `estado`, `almacen`. La disponibilidad descuenta reservado y no puede ser negativa. Esta consulta no genera el snapshot de la orden.

### Hallazgo D06: vehículos

En la configuración local revisada había tres tipos de maquinaria y ningún tipo de vehículo. El contrato devuelve `vehiculo: []` y `tipos_vehiculo_configurados: false`; no crea un tipo ficticio. Si se configura un tipo activo de vehículo, se devuelve automáticamente en ese ámbito, como demuestra la prueba con configuración aislada.

La tabla de materiales tiene `maquina_id`, sin vínculo específico a vehículo, y `MantenimientoAbastecimientoService::calcular()` devuelve una colección vacía si no hay máquina. La generación web usa plantillas de maquinaria. Por ello:

- `plantilla_materiales_maquinaria: true` describe soporte, no asegura que cada máquina tenga una plantilla.
- `plantilla_materiales_vehiculo: false` es explícito y los materiales de vehículo se devuelven vacíos.
- Se puede consultar kilometraje y tipos de vehículo ya configurados, pero no anunciar abastecimiento automático para vehículos como una función terminada.
- Para crear servicios programados de vehículo se necesita configuración real de tipos. Extender sus plantillas/abastecimiento requiere una tarea y decisión posteriores; no se hicieron migraciones ni se alteró la web para simular ese soporte.

## GET /api/v1/mecanico/inventario/productos

| Parámetro | Regla |
|---|---|
| `q` | Texto opcional, máximo 120 caracteres; busca nombre o SKU. |
| `almacen_id` | Opcional. Si se envía debe ser el Giralda resuelto por servidor. Otro ID devuelve 422. |
| `con_existencia` | Booleano opcional, usar `0`/`1`. Con `1` devuelve solo disponible neto positivo. |
| `per_page` | Opcional, entero 1–50, predeterminado 25. Vacío/nulo usa 25; fuera de rango devuelve 422. |
| `page` | Opcional, entero positivo. |

Se resuelve un almacén activo con código `GL` o nombre que contenga Giralda, priorizando código `GL`, igual que el flujo web. Si no existe un Giralda activo: 503 con mensaje explícito; no se sustituye por otro almacén ni se presentan existencias ficticias.

### Alcance del catálogo y existencias

El catálogo de productos es global, como el selector web de materiales: se incluyen los productos activos, aun sin fila en `inventario_stock`. No existe en esta consulta una propiedad que haga al producto dueño de un solo almacén. **Solo las existencias pertenecen a Giralda.** Un producto con stock únicamente en otro almacén aparece con existencia cero en Giralda; nunca se suman ni se revelan las cantidades del otro almacén.

Se usa un LEFT JOIN con el almacén restringido dentro del JOIN, para no perder productos sin existencia. Se excluyen productos inactivos. Orden estable: nombre e ID.

Respuesta: `ok`, `almacen`, `data` como arreglo directo y `meta` (`current_page`, `last_page`, `per_page`, `total`). Cada producto contiene:

- `id`, `nombre`, `sku`, `unidad`.
- `unidad_stock`: unidad base configurada, con respaldo a `unidad`; corresponde a las cantidades de existencia.
- `tiene_registro_stock`: distingue ausencia de registro de una fila con cantidad cero.
- `stock.stock_actual`, `stock.stock_reservado`, `stock.disponible`.

`disponible = max(0, stock_actual - stock_reservado)`; valores ausentes se presentan como cero. No se exponen costo promedio ni valor total del inventario. Consultar no reserva, descuenta, crea productos ni permite editar precios.

El filtro mecánico `con_existencia=1` usa disponible neto, mientras la consulta gerencial conserva su filtro histórico de stock actual positivo. No se cambió el contrato gerencial para introducir esta diferencia operativa.

## Código compartido y compatibilidad

- `MantenimientoCatalogoService`: extrae los tipos, plantillas y resolución de Giralda usados por la web. `MantenimientoController` delega los métodos existentes.
- `InventarioConsultaService`: aloja las consultas gerenciales existentes y la nueva proyección de productos activos con stock opcional.
- `ServicioProgramadoCatalogoService`: arma el catálogo móvil y compone los servicios de lectura existentes.
- `CatalogoController` e `InventarioController` mecánicos: validan entradas y devuelven JSON. Las rutas siguen protegidas por el middleware común de T03.

## Validación registrada

- 34 pruebas, 295 comprobaciones con `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter=AppMobile --no-progress`.
- Diez pruebas nuevas de T05 usan tablas y datos reales de SQLite en memoria. Los datos de productos/tipos/materiales se insertan únicamente en esa base efímera; autorización y cálculo de lectura se controlan como dependencias ya probadas.
- Se cubren productos inactivos, sin registro, con cero, con reservas superiores al stock y con cantidades en otro almacén; búsqueda, paginación, filtros inválidos, almacén incorrecto, falta de Giralda, selección de equipo, prioridad específica/general, tipos activos/inactivos y configuración de vehículo presente/ausente.
- Sin modificar `php.ini`, la prueba nueva se omite si no está disponible PDO SQLite y muestra el comando para ejecutarla; la validación de esta entrega sí habilitó la extensión por proceso y ejecutó todas sus pruebas.
- Consultas locales de solo lectura: paridad de stock/resumen gerenciales contra el controlador original; dos catálogos web idénticos por hash; catálogos generales, lecturas de máquina/vehículo y cuatro consultas de productos verificadas.
- Sintaxis PHP, estilo de archivos nuevos y UTF-8 verificados. Los archivos temporales de referencia se eliminan al cerrar T05.
- No se cambiaron roles, permisos, tipos preventivos ni existencias de la base local. La navegación móvil sigue en T13 y la creación de borradores en T06.
