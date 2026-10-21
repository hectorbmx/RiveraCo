# Plan de servicios de maquinaria y materiales

## Estado del documento

- Estado: definicion funcional del MVP.
- Ultima actualizacion: 2026-10-20.
- Permisos finos: fuera del MVP por ahora.
- Almacen inicial: Giralda.
- Rangos iniciales de maquinaria: 250, 500 y 1000 horas.

## Objetivo

Conectar el servicio de una maquina con los materiales necesarios para ejecutarlo,
consultar existencias, solicitar el surtido desde almacen y enviar unicamente los
faltantes al proceso de compras.

El flujo debe dejar trazabilidad desde el servicio hasta el material consumido:

`Servicio -> Solicitud de materiales -> Salida de almacen`

Cuando existan faltantes:

`Solicitud de materiales -> Requisicion de compra -> Orden de compra -> Entrada -> Salida -> Servicio`

## Alcance funcional del MVP

1. Configurar materiales requeridos por tipo de servicio.
2. Mostrar esos materiales al crear un mantenimiento de maquina.
3. Consultar existencias del almacen Giralda.
4. Crear una solicitud de materiales desde el servicio.
5. Surtir materiales mediante una salida de inventario vinculada.
6. Detectar faltantes y generar una requisicion para compras.
7. Cargar la requisicion en la creacion de una orden de compra.
8. Importar los detalles de la requisicion a la orden de compra.
9. Permitir que compras aumente cantidades para dejar inventario.
10. Permitir al mecanico ejecutar el servicio y adjuntar evidencias.

## Estados del servicio

| Estado | Significado |
|---|---|
| `borrador` | El servicio aun no se envia a surtido. |
| `material_pendiente` | El servicio existe, pero aun no se solicito material. |
| `solicitud_enviada` | Ya existe una solicitud de materiales. |
| `esperando_compra` | Hay faltantes enviados al proceso de compras. |
| `listo_para_ejecutar` | Todo el material requerido esta surtido. |
| `ejecutado` | El mecanico termino y registro evidencias. |
| `cancelado` | El servicio ya no se realizara. |

### Decision formal del paso 1

El estado general del servicio y el avance de sus materiales son conceptos
distintos. El servicio no cambiara a ejecutado solo porque se haya solicitado
material; primero debe completarse el abastecimiento correspondiente.

Estados operativos del servicio:

| Estado | Uso |
|---|---|
| `borrador` | Cabecera creada, aun sin decision de abastecimiento. |
| `pendiente` | El servicio ya tiene una ruta de abastecimiento definida y espera materiales. |
| `en_proceso` | Los materiales estan completos y el mecanico puede trabajar. |
| `completado` | El servicio fue ejecutado con notas y evidencias. |
| `cancelado` | El servicio dejo de ser valido. |

Ruta de abastecimiento (`abastecimiento_tipo`):

| Valor | Significado |
|---|---|
| `surtido_directo` | Todo lo requerido se solicita al almacen Giralda. |
| `compra` | Todo lo requerido se envia al proceso de compras. |
| `parcial` | Lo disponible se solicita al almacen y el faltante se envia a compras. |

Estado del abastecimiento (`abastecimiento_estado`):

| Valor | Significado |
|---|---|
| `pendiente` | Aun no se ha generado una solicitud operativa. |
| `solicitud_almacen` | Existe una solicitud de surtido directo. |
| `solicitud_compra` | Existe una solicitud de compra por atender. |
| `parcial` | El servicio tiene atencion dividida entre almacen y compras. |
| `listo` | Todas las cantidades requeridas ya fueron atendidas. |

No se usaran marcas ambiguas como X, Y o Z. La interfaz mostrara etiquetas
descriptivas y los valores internos conservaran nombres estables para reportes,
validaciones y futuras integraciones.

## Estados de la solicitud de materiales

| Estado | Significado |
|---|---|
| `pendiente` | Creada y aun no atendida. |
| `parcial` | Algunas partidas fueron surtidas. |
| `surtida` | Todas las partidas requeridas fueron surtidas. |
| `en_compra` | Hay partidas faltantes en proceso de compra. |
| `cancelada` | La solicitud dejo de ser valida. |

Cada partida debe conservar:

- Cantidad requerida.
- Cantidad disponible al consultar.
- Cantidad surtida.
- Cantidad faltante.
- Fuente de atencion: almacen, compra o ambas.
- Producto de inventario relacionado.

## Reglas de negocio acordadas

1. El servicio siempre pertenece a una maquina.
2. La obra es opcional; una maquina puede estar en patio o taller.
3. El material proviene del catalogo de inventario de Giralda.
4. El tipo de servicio define los materiales sugeridos.
5. Crear una solicitud no descuenta inventario.
6. Aplicar una salida si descuenta inventario.
7. La salida debe volver a validar existencias al momento de aplicarse.
8. No se puede surtir mas de la cantidad pendiente.
9. Una solicitud completamente surtida no puede generar otra salida.
10. Los faltantes se envian a compras sin duplicar lo ya surtido.
11. Una orden de compra puede comprar mas que el faltante para aumentar stock.
12. El servicio no puede pasar a `completado` si tiene material pendiente.
13. Un documento con movimientos de inventario no se elimina; se cancela.
14. Por ahora no se implementaran permisos finos adicionales para este flujo.

## Decisiones de arquitectura

- La solicitud de materiales debe ser una entidad propia vinculada al servicio.
- La requisicion de compra debe poder relacionarse con el servicio y su solicitud.
- La salida de inventario debe guardar la referencia a la solicitud de servicio.
- La orden de compra debe guardar la referencia a la requisicion que la origino.
- No se debe depender solo del texto o del folio para mantener las relaciones.
- La existencia mostrada al mecanico es informativa; la validacion final ocurre al aplicar la salida.
- La reserva de inventario queda fuera del primer MVP.
- Las horas de trabajo por servicio quedan preparadas para una etapa posterior.

## Checkpoints

### Checkpoint 1: catalogo de servicios

- [x] Los tipos de servicio activos se pueden listar.
- [x] La estructura para asociar materiales a cada tipo de servicio existe.
- [x] Cada material se relacionara con un producto real del inventario.
- [x] La estructura contempla cantidad y unidad por material.
- [x] Crear el modelo y las relaciones Eloquent.
- [x] Preparar metodos de consulta, alta, edicion y activacion de materiales.
- [ ] Agregar la interfaz para administrar materiales.

### Checkpoint 2: captura del servicio

- [ ] El mecanico selecciona el tipo de servicio.
- [ ] Se muestran los materiales requeridos.
- [x] Se muestra la existencia actual de Giralda.
- [x] Se distinguen partidas disponibles, parciales y faltantes.

### Checkpoint 2A: estados y decision de abastecimiento

- [x] Separar el estado general del servicio del estado de materiales.
- [x] Definir las rutas `surtido_directo`, `compra` y `parcial`.
- [x] Definir estados descriptivos para el abastecimiento.
- [x] Mantener el servicio en `borrador` hasta que se elija una ruta.
- [ ] Generar las solicitudes operativas de almacen y compras.

### Checkpoint 2B: estructura de solicitudes

- [x] Crear cabecera de solicitud vinculada al mantenimiento.
- [x] Separar solicitudes de almacen y compras por tipo.
- [x] Crear partidas con cantidades requerida, solicitada y atendida.
- [x] Guardar snapshot del producto para conservar la trazabilidad.
- [x] Generar folio propio `SMA-YY-######`.
- [ ] Conectar la creacion desde los botones del mantenimiento.

### Checkpoint 2C: calculo de partidas

- [x] Resolver la receta especifica de la maquina con fallback general.
- [x] Calcular existencia disponible descontando reservas.
- [x] Calcular cantidad surtible y cantidad faltante.
- [x] Exponer el calculo en la edicion del servicio.
- [ ] Persistir las partidas en una solicitud de abastecimiento.

### Checkpoint 3: solicitud de almacen

- [x] El boton crea una solicitud con folio propio.
- [x] La solicitud conserva el servicio y la maquina.
- [x] El almacenista puede abrirla mediante su folio desde el mantenimiento.
- [x] La salida carga sus partidas pendientes automaticamente.

### Decision del paso 5

- El mecanico solo puede elegir `almacen` o `compra`.
- `parcial` no es una decision del mecanico; es un resultado del surtido de almacen.
- La solicitud conserva la cantidad completa requerida.
- La solicitud de almacen termina cuando se surte completa o se cancela; puede permanecer parcial mientras exista saldo pendiente.
- La solicitud de compra no termina al comprar ni al registrar la entrada. Termina cuando almacen entrega el material al servicio.
- Cada solicitud tiene un folio imprimible y recuperable.
- La impresion es un snapshot de la solicitud y no modifica inventario.

### Checkpoint 4: surtido

- [x] La salida no permite exceder el pendiente.
- [ ] La salida vuelve a validar existencia al aplicar.
- [x] Se actualizan cantidades surtidas y faltantes.
- [x] El estado de la solicitud cambia a `parcial` o `surtida`.
- [x] Al cancelar una salida se revierten sus cantidades atendidas.
- [ ] El servicio cambia a listo solo cuando corresponde.

### Checkpoint 4A: salida vinculada al almacen

- [x] `inventario_documentos.mantenimiento_abastecimiento_id` conserva el origen operativo.
- [x] Solo se pueden cargar productos pertenecientes a la solicitud.
- [x] La cantidad inicial es el pendiente, no la cantidad original completa.
- [x] Una salida parcial puede continuar con otra salida posterior.
- [x] Cancelar una salida reabre la solicitud sin duplicar movimientos.

### Checkpoint 5: faltantes y compras

- [ ] Los faltantes generan una requisicion de mantenimiento.
- [x] Compras puede localizar la solicitud de mantenimiento por folio `SMA-YY-######`.
- [x] La OC carga automaticamente sus detalles pendientes.
- [ ] Compras puede aumentar cantidades dejando registro del ajuste.
- [x] La solicitud pasa a `en_compra` cuando se genera la OC.
- [x] Una entrada parcial o completa actualiza la solicitud como `recibida_parcial` o `material_en_almacen`.
- [ ] La entrada debe quedar visible con el folio SMA en la pantalla de documentos.

### Mapa de estados de abastecimiento

| Estado | Significado | Siguiente acción |
|---|---|---|
| `pendiente` | Solicitud creada y todavía sin entrega | Almacén surte o compras genera OC |
| `parcial` | Almacén entregó una parte | Almacén puede generar otra salida |
| `en_compra` | Compras ya tomó la solicitud | Esperar entrada de la OC |
| `recibida_parcial` | Compras recibió una parte en almacén | Almacén puede surtir lo recibido |
| `material_en_almacen` | La compra ya ingresó al almacén | Almacén genera la salida final |
| `surtida` | Todo lo solicitado fue entregado al servicio | Ejecutar/completar mantenimiento |
| `cancelada` | Solicitud cerrada sin surtir | No permite nuevas salidas |

### Checkpoint 6: trazabilidad completa

- [x] La salida conserva el folio de solicitud de mantenimiento.
- [x] La OC conserva el folio de solicitud de mantenimiento.
- [x] La entrada de la OC puede actualizar el estado de compra.
- [x] La salida final actualiza lo atendido y el estado de la solicitud.
- [x] La cancelación de una salida revierte la atención.
- [ ] Probar el flujo completo con datos reales: directo, parcial y compra.

### Checkpoint 6: ejecucion del servicio

- [ ] El mecanico ve que el material ya fue surtido.
- [ ] Puede completar el servicio.
- [ ] Puede agregar fotos y notas.
- [ ] El servicio queda ligado a todo su historial de materiales.

## Fuera del MVP

- Permisos finos por cada accion.
- Reserva formal de inventario.
- Horas de mano de obra por tipo de servicio.
- Sustituciones automaticas de productos.
- Flujo equivalente para vehiculos.
- Integracion movil.

## Decisiones pendientes

1. Definir el formato final del folio de solicitud de mantenimiento.
2. Confirmar si una solicitud puede mezclar materiales de almacen y compras.
3. Definir quien puede cancelar un servicio o una solicitud.
4. Definir si compras puede modificar cantidades sin autorizacion adicional.
5. Confirmar si la entrada de compra debe quedar ligada automaticamente a la requisicion.

## Configuracion por maquina

- Cada maquina podra tener una receta distinta para cada rango: 250, 500 y 1000 horas.
- La combinacion funcional sera `servicio_preventivo_tipo_id + maquina_id`.
- Los rangos 500 y 1000 se configuraran desde la pantalla actual; sus meses y alertas no se fijan por migracion hasta definirlos.
- `maquina_id` queda nullable para conservar materiales generales como fallback.
- La unicidad de una receta especifica se controlara por servicio, maquina y producto.

### Checkpoint de configuracion por maquina

- [x] Agregar `maquina_id` a los materiales preventivos.
- [x] Agregar la relacion con maquinaria.
- [x] Ajustar el indice unico para contemplar la maquina.
- [x] Actualizar controlador y validaciones para recibir la maquina.
- [x] Agregar rutas generales y rutas anidadas por servicio y maquina.
- [x] Crear la interfaz de detalle por servicio y maquina.
- [x] Administrar materiales desde un modal por maquina.
- [x] Resolver materiales especificos de la maquina con fallback a la receta general.
- [x] Mostrar la receta efectiva al capturar un mantenimiento.

