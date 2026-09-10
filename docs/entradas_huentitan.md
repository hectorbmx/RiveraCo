# Entradas HUENTITAN

## Objetivo

Crear el flujo de entradas para el almacen HUENTITAN, tomando como origen principal las ordenes de compra autorizadas.

La entrada sera el evento que confirma lo realmente recibido y el unico punto que afecta positivamente el inventario. La orden de compra no debe mover inventario por si sola.

## Regla base del flujo

1. Se genera una orden de compra para HUENTITAN.
2. La orden de compra se autoriza.
3. La orden autorizada queda disponible como origen para una entrada.
4. El usuario crea una entrada desde HUENTITAN.
5. Selecciona una orden de compra autorizada con productos pendientes de recibir.
6. El sistema carga los productos pendientes de esa orden.
7. El usuario confirma o ajusta cantidades recibidas.
8. Al guardar/aplicar la entrada, el inventario aumenta.
9. Si la recepcion fue parcial, la orden de compra sigue disponible con saldo pendiente.
10. Si se recibio todo, la orden queda completa para recepcion.

## Alcance inicial

El primer alcance es registrar entradas de material comprado para HUENTITAN.

No se incluye todavia:

- Entrada por devolucion de obra.
- Entrada por ajuste manual.
- Entrada de producto terminado desde orden de fabricacion.
- Recepcion fisica confirmada por obra.

Estos casos se dejan preparados como tipos de origen para crecer despues.

## Decisiones operativas

### Orden de compra contra entrada

La orden de compra representa lo solicitado/comprado.

La entrada representa lo recibido.

Por eso, una orden autorizada puede generar una o varias entradas.

### Recepcion parcial

Si no llega todo el material, el usuario ajusta la cantidad recibida antes de guardar.

El sistema debe conservar pendiente lo no recibido para permitir una entrada posterior.

### Compra para stock, fabricacion u obra

Aunque fisicamente algunos productos puedan ir directo a obra, administrativamente deben generar registro de entrada en HUENTITAN para conservar trazabilidad.

Despues de la entrada, el material podra:

- Quedar en stock del almacen.
- Consumirse en una orden de fabricacion.
- Salir hacia obra.

### Impacto en inventario

La entrada aplicada debe:

- Crear movimiento positivo de inventario en `inventario_movimientos`.
- Actualizar stock actual del producto en HUENTITAN (`inventario_stock`).
- Recalcular el costo promedio ponderado y el valor total en `inventario_stock` con base en el costo de la entrada:
  $$\text{Costo Promedio} = \frac{\text{Valor Total Anterior} + (\text{Cantidad Recibida} \times \text{Costo Unitario})}{\text{Stock Actual Anterior} + \text{Cantidad Recibida}}$$
- Alimentar el kardex del producto.
- Conservar relación con la orden de compra y su detalle.


## Modelo de datos propuesto

### Tabla `huentitan_entradas`

Campos sugeridos:

- `id`
- `folio`
- `almacen_id`
- `orden_compra_id`
- `tipo_origen`
- `fecha`
- `estado`
- `usuario_id`
- `observaciones`
- `created_at`
- `updated_at`

Estados iniciales sugeridos:

- `borrador`
- `aplicada`
- `cancelada`

Tipos de origen preparados:

- `orden_compra`
- `ajuste`
- `devolucion_obra`
- `produccion`
- `otro`

### Tabla `huentitan_entrada_detalles`

Campos sugeridos:

- `id`
- `huentitan_entrada_id`
- `orden_compra_detalle_id`
- `producto_id`
- `descripcion`
- `unidad`
- `cantidad_ordenada`
- `cantidad_recibida`
- `costo_unitario`
- `importe`
- `observaciones`
- `created_at`
- `updated_at`

### Control y Estatus en Órdenes de Compra (No invasivo)

Para evitar sobrecargar el sistema con consultas pesadas y mantener la compatibilidad absoluta con el flujo actual de órdenes de compra (donde el campo `estado` gestiona la autorización: BORRADOR, AUTORIZADA, CANCELADA):

1. **Tabla `ordenes_compra`:**
   - Campo nuevo: `estado_recepcion` enum(`'pendiente'`, `'parcial'`, `'recibida'`) con valor por defecto `'pendiente'` e indexado.
   - Permite filtrar al instante las OC disponibles para recibir sin alterar el estatus de autorización.
2. **Tabla `orden_compra_detalles`:**
   - Campo nuevo: `cantidad_recibida` `decimal(14,3)` con valor por defecto `0.000`.
   - Permite conocer de forma directa el saldo pendiente por partida (`cantidad - cantidad_recibida`).

## Calculos necesarios

### Cantidad comprada

Viene de `orden_compra_detalles.cantidad`.

### Cantidad recibida

Suma acumulada de recepciones registradas en `orden_compra_detalles.cantidad_recibida` (alimentada por cada entrada aplicada).

### Cantidad pendiente

`cantidad_comprada - cantidad_recibida`

La cantidad pendiente no debe ser negativa. Si llega mas material del comprado, debe requerir decision operativa antes de permitirlo.

## Pantallas propuestas

### 1. Index de entradas

Ruta sugerida:

`/huentitan/entradas`

Debe mostrar:

- Folio de entrada.
- Fecha.
- Orden de compra origen.
- Proveedor.
- Estado.
- Total de partidas.
- Total recibido.
- Usuario creador.
- Acciones.

Filtros sugeridos:

- Busqueda por folio, OC o proveedor.
- Estado.
- Fecha desde/hasta.

### 2. Nueva entrada

Ruta sugerida:

`/huentitan/entradas/create`

Debe mostrar:

- Fecha.
- Selector de orden de compra autorizada pendiente.
- Observaciones.
- Tabla de productos pendientes.

Al seleccionar una orden de compra:

- Cargar sus detalles pendientes.
- Mostrar cantidad comprada.
- Mostrar cantidad ya recibida.
- Mostrar cantidad pendiente.
- Precargar cantidad a recibir con el pendiente.
- Permitir ajustar cantidad recibida.

### 3. Detalle de entrada

Ruta sugerida:

`/huentitan/entradas/{entrada}`

Debe mostrar:

- Cabecera.
- Productos recibidos.
- Relacion con orden de compra.
- Movimiento generado en inventario.
- Estado.

## Reglas de validacion

- Solo se pueden usar ordenes de compra autorizadas.
- Solo se deben mostrar ordenes de compra del area HUENTITAN.
- Solo se deben mostrar ordenes con saldo pendiente de recibir (`estado_recepcion IN ('pendiente', 'parcial')`).
- La cantidad recibida debe ser mayor a cero.
- La cantidad recibida no debe exceder el pendiente sin autorizacion/regla adicional.
- Una entrada aplicada no debe editar cantidades.
- **Regla de Cancelación / Reversión:**
  - Una entrada cancelada debe revertir el movimiento de inventario en `inventario_movimientos`, restar el stock correspondiente y descontar las cantidades recibidas en la OC.
  - **Validación de stock mínimo:** No se permitirá cancelar una entrada si el stock actual disponible en Huentitán para alguno de sus productos es menor a la cantidad recibida en dicha entrada (`stock_actual < cantidad_recibida_en_entrada`), ya que implicaría que el material ya fue consumido en fabricación o enviado a obra.


## Alcance congelado: entrada por una sola OC

**Fecha de ajuste:** 09 de septiembre de 2026  
**Decision:** la entrada multidocumento queda fuera del alcance actual.

Aunque operativamente puede pasar que llegue material de varias ordenes de compra al mismo tiempo, por ahora HUENTITAN mantendra la regla de una entrada ligada a una sola OC.

Motivos:

- El modelo actual ya resuelve recepcion parcial por OC con `orden_compra_detalles.cantidad_recibida`.
- Permite cerrar primero el flujo de fabricacion sin introducir una cabecera de recepcion agrupada.
- Evita crear una red dificil de auditar entre multiples OC, multiples entradas y multiples origenes de compra.

Queda como mejora futura:

- Analizar una recepcion fisica agrupada que pueda contener varias OC.
- Definir si se requiere una tabla/cabecera nueva de recepcion multidocumento o si basta con una pantalla que genere N entradas en lote.
- Mantener compatibilidad con trazabilidad hacia orden de fabricacion, salida a obra y stock.
## Plan complementario: salidas HUENTITAN

El flujo de HUENTITAN necesita manejar dos tipos principales de salidas: salida por consumo interno de fabricacion y salida directa hacia obra. Ambas deben afectar negativamente el inventario, alimentar kardex y conservar trazabilidad del destino.

### Salida por orden de fabricacion

Una orden de fabricacion consume materia prima o insumos internos de HUENTITAN. Operativamente, esto debe registrarse como una salida de almacen relacionada con la orden de fabricacion.

Regla propuesta:

1. El usuario crea una orden de fabricacion.
2. El sistema calcula materiales requeridos con base en la formula vigente congelada.
3. Al avanzar la orden al paso de consumo/apartado, el sistema valida existencia disponible.
4. Si hay existencia suficiente, se genera la salida de materiales internos.
5. Esa salida afecta inventario negativamente.
6. Cuando termina la fabricacion, se genera una entrada de producto terminado.
7. La entrada de producto terminado queda relacionada con la misma orden de fabricacion.

La orden de fabricacion no debe confundirse con una salida a obra. Su destino es produccion interna.

### Entrada de producto terminado desde fabricacion

El producto terminado que nace de una orden de fabricacion debe entrar nuevamente al inventario de HUENTITAN. Esta entrada debe tener origen `produccion`, no `orden_compra`.

Regla propuesta:

1. Produccion termina piezas.
2. Se captura cantidad terminada aceptada.
3. Se capturan rechazos, merma o sobrantes si aplica.
4. Se genera entrada de producto terminado.
5. La entrada aumenta stock del producto terminado.
6. El costo unitario puede calcularse con base en materiales consumidos, costo promedio de insumos y tiempo de fabricacion.

### Salida directa hacia obra

HUENTITAN tambien puede enviar materia prima o producto terminado directamente a una obra.

Regla propuesta:

1. El usuario crea una salida desde HUENTITAN.
2. Selecciona destino `obra`.
3. Selecciona la obra correspondiente.
4. Agrega productos internos del almacen HUENTITAN.
5. El sistema valida existencia disponible.
6. Al aplicar la salida, el inventario disminuye.
7. El kardex registra el movimiento con referencia a la obra.
8. La salida conserva responsable, fecha y observaciones.

### Tipos de documentos sugeridos

| Documento | Direccion inventario | Origen/destino | Uso |
| --- | --- | --- | --- |
| Entrada por OC | Entrada | Orden de compra | Material comprado |
| Salida a fabricacion | Salida | Orden de fabricacion | Consumo de materia prima |
| Entrada de produccion | Entrada | Orden de fabricacion | Producto terminado fabricado |
| Salida a obra | Salida | Obra | Material enviado a obra |
| Ajuste | Entrada/Salida | Ajuste interno | Correccion controlada |

### Checkpoints futuros para salidas

#### Fase S1: Definir salida base

- [ ] Crear documento especifico `salidas_huentitan.md` si el flujo crece.
- [ ] Crear migraciones `huentitan_salidas` y `huentitan_salida_detalles`.
- [ ] Crear modelos `HuentitanSalida` y `HuentitanSalidaDetalle`.
- [ ] Agregar relaciones con almacen, producto, obra y orden de fabricacion.

#### Fase S2: Salida a obra

- [ ] Crear index de salidas.
- [ ] Crear pantalla nueva salida.
- [ ] Permitir destino `obra`.
- [ ] Cargar obras activas.
- [ ] Buscar productos HUENTITAN con stock disponible.
- [ ] Validar stock antes de guardar/aplicar.
- [ ] Aplicar salida contra `inventario_stock` e `inventario_movimientos`.
- [ ] Mostrar salida en kardex del producto con referencia a obra.

#### Fase S3: Salida por fabricacion

- [ ] Generar salida desde una orden de fabricacion calculada.
- [ ] Consumir materiales segun formula congelada.
- [ ] Validar stock disponible de todos los insumos.
- [ ] Registrar faltantes antes de permitir aplicar.
- [ ] Aplicar salida a inventario.
- [ ] Relacionar salida con orden de fabricacion.

#### Fase S4: Entrada de producto terminado

- [ ] Crear entrada de origen `produccion` desde orden de fabricacion.
- [ ] Capturar cantidad terminada aceptada.
- [ ] Capturar merma/rechazo/sobrante.
- [ ] Calcular costo unitario estimado del producto terminado.
- [ ] Aplicar entrada positiva al inventario.
- [ ] Relacionar entrada con salida de materiales y orden de fabricacion.

## Ajuste operativo: fabricacion sin salida manual

Decision: para fabricacion no se debe pedir al usuario crear una salida manual separada. El consumo de insumos debe ejecutarse como una salida automatica interna ligada a la orden de fabricacion.

### Regla de usuario

Para el usuario, el flujo debe sentirse asi:

1. Crear orden de fabricacion.
2. Ver materiales requeridos y faltantes calculados automaticamente.
3. Cuando exista stock suficiente, presionar `Enviar a produccion`.
4. El sistema descuenta insumos del inventario y cambia la orden a `en_produccion`.
5. Al terminar, registrar producto terminado.
6. El sistema genera la entrada del producto terminado y cierra la orden.

### Regla tecnica

Aunque no exista una salida manual visible, el sistema debe conservar trazabilidad creando movimientos formales en kardex.

Al presionar `Enviar a produccion`, el sistema debe:

- Validar stock disponible de cada insumo.
- Descontar inventario en `inventario_stock`.
- Crear movimientos `out` en `inventario_movimientos`.
- Ligar cada movimiento con la orden de fabricacion.
- Cambiar estado de la orden a `en_produccion`.
- Guardar usuario y fecha del consumo/envio a produccion.

### Ajuste sobre calcular materiales

El boton `Calcular materiales` fue util para fase de prueba, pero en operacion normal puede generar friccion.

Decision recomendada:

- Al crear una orden de fabricacion con producto, cantidad y formula valida, el sistema debe calcular materiales automaticamente.
- La orden nueva puede quedar directamente en estado `calculada`.
- El estado `borrador` se conservara solo si despues se permite guardar ordenes incompletas o pendientes de definir.
- Si el usuario edita producto o cantidad antes de enviar a produccion, el sistema debe recalcular los materiales al guardar.

### Estados operativos simplificados

Estados visibles recomendados:

- `calculada`: orden creada con materiales calculados; no afecta inventario.
- `en_produccion`: insumos ya descontados del inventario.
- `cerrada`: producto terminado ya entro al inventario.
- `cancelada`: orden anulada antes de concluir.

Estado tecnico opcional:

- `borrador`: solo para orden incompleta o guardada temporalmente.

### Checkpoints de ajuste en orden de fabricacion

- [ ] Convertir el calculo de materiales en proceso automatico al crear la orden.
- [ ] Revisar si `borrador` debe ocultarse del flujo visible.
- [x] Agregar accion `Enviar a produccion`.
- [x] Validar stock disponible al enviar a produccion.
- [x] Crear salida automatica interna de insumos.
- [x] Registrar movimientos `out` ligados a la orden de fabricacion.
- [x] Cambiar estado a `en_produccion`.
- [ ] Preparar registro de producto terminado como entrada de origen `produccion`.
- [ ] Evitar doble consumo si una orden ya esta `en_produccion` o `cerrada`.

## Registro de Ejecución y Avances

### Decisiones de Arquitectura Tomadas
1. **Opción 1 implementada:** Tablas dedicadas `huentitan_entradas` y `huentitan_entrada_detalles`, manteniendo desacoplado el flujo de Huentitán mientras que la aplicación impacta de manera unificada en `inventario_movimientos` e `inventario_stock` (costo promedio ponderado).
2. **Control en Órdenes de Compra:** Se agregaron campos aislados (`estado_recepcion` en `ordenes_compra` y `cantidad_recibida` en `orden_compra_detalles`) sin alterar el campo existente `estado`, asegurando cero conflictos con el resto del sistema y filtrado instantáneo.
3. **Regla de Cancelación Segura:** No se permite cancelar una entrada si el stock actual en Huentitán es inferior a la cantidad recibida en dicha entrada (`stock_actual < cantidad_recibida`).

### Componentes Creados / Modificados

#### Fase 1: Base de datos y modelos (Completada)
- **Migraciones ejecutadas:**
  - `database/migrations/2026_09_07_000005_add_recepcion_fields_to_ordenes_compra_tables.php` (`estado_recepcion` e índice en `ordenes_compra`, `cantidad_recibida` en `orden_compra_detalles`).
  - `database/migrations/2026_09_07_000006_create_huentitan_entradas_table.php` (Cabecera de entradas: folio único, almacén, OC, fechas, estados, usuarios).
  - `database/migrations/2026_09_07_000007_create_huentitan_entrada_detalles_table.php` (Detalle de partidas recibidas: cantidades ordenadas, recibidas, costos, importes).
- **Modelos:**
  - `app/Models/HuentitanEntrada.php` (Relaciones con `Almacen`, `OrdenCompra`, `User`, `detalles` y helpers de estado e importes).
  - `app/Models/HuentitanEntradaDetalle.php` (Relaciones con `HuentitanEntrada`, `OrdenCompraDetalle` y `Producto`).
  - `app/Models/OrdenCompra.php` (Agregado `estado_recepcion` y relación `huentitanEntradas()`).
  - `app/Models/OrdenCompraDetalle.php` (Agregado `cantidad_recibida`, relación `huentitanEntradaDetalles()` y accessor `cantidad_pendiente`).
  - `app/Models/Producto.php` (Agregada relación `huentitanEntradaDetalles()`).
  - `app/Models/Almacen.php` (Agregada relación `huentitanEntradas()`).

#### Fase 2: Rutas y permisos (Completada)
- **Migración de permisos:**
  - `database/migrations/2026_09_07_000008_create_huentitan_entradas_permissions.php` (Crea `huentitan.entradas.view`, `huentitan.entradas.create`, `huentitan.entradas.apply`, `huentitan.entradas.cancel` y los asigna al rol `super-admin`, `admin-rivera` y roles de almacén).
  - `database/seeders/RolesAndPermissionsSeeder.php` (Actualizado con los permisos para roles de Huentitán).
- **Controlador:**
  - `app/Http/Controllers/Inventario/HuentitanEntradaController.php` (Métodos `index`, `create`, `store`, `show`, `aplicar`, `cancelar`, `ordenCompraDetalles`).
- **Rutas (`routes/web.php`):**
  - `huentitan.entradas.index`, `create`, `store`, `show`, `aplicar`, `cancelar` y `oc-detalles`.
- **Navegación:**
  - Enlace *Entradas* añadido al submenú HUENTITAN en `resources/views/layouts/admin.blade.php`.
  - Tarjeta *Entradas* añadida al panel operativo en `resources/views/huentitan/index.blade.php`.

#### Fase 3: Index de entradas (Completada)
- **Controlador (`HuentitanEntradaController@index`):**
  - Carga paginada de entradas con relaciones (`almacen`, `ordenCompra.proveedor`, `usuario`, `detalles.producto`).
  - Filtros dinámicos por texto (`q`: folio, OC, proveedor), estado (`estado`: borrador, aplicada, cancelada) y rango de fechas (`fecha_desde`, `fecha_hasta`).
  - Tarjetas de conteo de resumen operativo: total registradas, aplicadas, borrador y canceladas.
- **Vista (`resources/views/huentitan/entradas/index.blade.php`):**
  - Barra de navegación con migas de pan y botón destacado "+ Nueva entrada".
  - Filtros y botón para limpiar búsqueda.
  - Tabla completa con folio, fecha, OC, proveedor, cantidad de partidas, total recibido, usuario, badges de estado y enlace de acción "Ver detalle".
  - Estado vacío descriptivo con acceso directo a creación de primera entrada.

#### Fase 4: Nueva entrada desde OC (Completada)
- **Controlador (`HuentitanEntradaController@create` y `@ordenCompraDetalles`):**
  - Carga de órdenes de compra autorizadas exclusivamente del área HUENTITAN que contengan saldo pendiente de recibir (`whereHas('detalles', cantidad > cantidad_recibida)`).
  - Soporte para preselección de OC vía query param (`orden_compra_id`).
  - Endpoint JSON (`/huentitan/entradas-oc-detalles/{ordenCompra}`) que devuelve las partidas pendientes con `cantidad_ordenada`, `cantidad_recibida_previa`, `cantidad_pendiente`, `costo_unitario` e `importe`.
- **Vista (`resources/views/huentitan/entradas/create.blade.php`):**
  - Componente interactivo Alpine.js (`nuevaEntrada()`).
  - Selector dinámico de OC con ficha de datos del proveedor y fecha de la orden.
  - Carga asíncrona de partidas con indicador de carga.
  - Checkboxes individuales y masivos ("Seleccionar todas" / "Deseleccionar todas").
  - Precarga automática de la cantidad a recibir con el saldo pendiente exacto.
  - Permite ajuste manual para recepciones parciales.
  - Validación visual preventiva si la cantidad introducida excede el saldo pendiente o es $\le 0$.
  - Recálculo en tiempo real de totales (partidas seleccionadas, suma de cantidades recibidas e importe total estimado).
  - Botón de guardado con validación de cliente.

#### Fase 5: Guardado de entrada (Completada)

Alcance cerrado: la Fase 5 guarda la entrada como borrador consistente. No actualiza inventario ni cantidades recibidas de la OC; eso se ejecutara en Fase 6 al aplicar la entrada.

- **Controlador (`HuentitanEntradaController@store`, `ordenCompraDetalles` y `generarFolioEntrada`):**
  - Validación estricta en servidor:
    - OC existente y autorizada.
    - OC perteneciente al area HUENTITAN.
    - OC con `estado_recepcion` pendiente o parcial.
    - OC con al menos una partida con saldo pendiente.
    - Presencia de al menos una partida seleccionada con cantidad $> 0$.
    - Comparación contra base de datos: ninguna partida puede recibir más de su saldo pendiente real (`cantidad - cantidad_recibida`).
    - Revalidación transaccional con bloqueo de la OC antes de persistir el borrador.
  - Generación de folio único consecutivo con bloqueo pesimista (`lockForUpdate`): `ENT-HUE-YYYYMM-####`.
  - Persistencia transaccional (`DB::transaction`) en `huentitan_entradas` (estado `borrador`, almacén, usuario creador) y `huentitan_entrada_detalles` (partidas recibidas, costo unitario, importe, observaciones).
  - Redirección a `huentitan.entradas.show` con mensaje de éxito.
- **Vista Detalle (`resources/views/huentitan/entradas/show.blade.php`):**
  - Pantalla completa con cabecera de auditoría, ficha de OC y proveedor, desglose de partidas recibidas con totales y controles para fases posteriores.
- **Migración de compatibilidad:**
  - `database/migrations/2026_09_07_000009_make_producto_id_nullable_in_huentitan_entrada_detalles.php` (para admitir conceptos sin catálogo previo sin romper integridad).

---

## Checkpoints de desarrollo

### Fase 1: Base de datos y modelos

- [x] Crear migracion `add_recepcion_fields_to_ordenes_compra_tables`.
- [x] Crear migracion `huentitan_entradas`.
- [x] Crear migracion `huentitan_entrada_detalles`.
- [x] Crear modelo `HuentitanEntrada`.
- [x] Crear modelo `HuentitanEntradaDetalle`.
- [x] Definir relaciones con `OrdenCompra`, `OrdenCompraDetalle`, `Producto`, `Almacen` y `User`.
- [x] Validar que las migraciones corran sin afectar datos existentes.

### Fase 2: Rutas y permisos

- [x] Agregar rutas HUENTITAN para entradas.
- [x] Agregar permisos `huentitan.entradas.view`.
- [x] Agregar permisos `huentitan.entradas.create`.
- [x] Agregar permisos `huentitan.entradas.apply`.
- [x] Asignar permisos al rol super-admin.
- [x] Agregar opcion Entradas al menu HUENTITAN.

### Fase 3: Index de entradas

- [x] Crear vista de listado.
- [x] Mostrar entradas existentes.
- [x] Agregar boton Nueva entrada.
- [x] Agregar filtros basicos.
- [x] Mostrar estado de cada entrada.

### Fase 4: Nueva entrada desde OC

- [x] Crear pantalla de nueva entrada.
- [x] Agregar selector de OC autorizada pendiente de recibir.
- [x] Filtrar selector solo por area HUENTITAN.
- [x] Cargar productos pendientes al seleccionar OC.
- [x] Mostrar comprado, recibido y pendiente.
- [x] Precargar cantidad a recibir con el pendiente.
- [x] Permitir ajuste manual de cantidad recibida.

### Fase 5: Guardado de entrada

- [x] Guardar cabecera de entrada.
- [x] Guardar detalles recibidos.
- [x] Relacionar cada detalle con `orden_compra_detalle_id`.
- [x] Calcular importes con el costo de la OC.
- [x] Evitar guardar entrada sin productos.
- [x] Evitar guardar cantidades mayores al pendiente.


### Fase 6: Aplicacion a inventario

- [x] Definir si la entrada se guarda ya aplicada o si tendra boton Aplicar.
- [x] Crear movimiento positivo de inventario por cada detalle.
- [x] Actualizar stock HUENTITAN.
- [x] Reflejar movimiento en kardex del producto.
- [x] Marcar entrada como aplicada.
- [x] Recalcular pendientes de recepcion por OC.
- [x] Registrar proveedor-producto desde la OC origen al aplicar la entrada.
- [x] Registrar historial de costo por producto/proveedor/OC para alimentar el tab Costos.

Nota: la cancelacion/reversion de una entrada aplicada queda como fase posterior, porque debe validar que el material no haya sido consumido o enviado a obra antes de restar stock.

### Fase 7: Parcialidades de OC

- [ ] Mostrar OC como pendiente mientras existan cantidades por recibir.
- [ ] Ocultar OC del selector cuando todos sus detalles esten completos.
- [ ] Mostrar historial de entradas relacionadas en la OC.
- [ ] Mostrar pendiente de recepcion por detalle en la OC.

### Fase 8: Integracion con fabricacion

- [ ] Identificar materiales comprados para una orden de fabricacion.
- [ ] Al recibir esos materiales, mantener liga con la orden de fabricacion origen.
- [ ] Mostrar en la orden de fabricacion que el faltante ya fue comprado/recibido.
- [ ] Permitir continuar el flujo de apartado/produccion cuando ya exista stock suficiente.

### Fase 9: Pruebas operativas

- [ ] Crear OC HUENTITAN autorizada con una partida.
- [ ] Crear entrada completa desde esa OC.
- [ ] Confirmar aumento de stock.
- [ ] Confirmar movimiento en kardex.
- [ ] Crear OC HUENTITAN con dos partidas.
- [ ] Crear entrada parcial.
- [ ] Confirmar que la OC sigue disponible con saldo pendiente.
- [ ] Crear segunda entrada para cerrar pendiente.
- [ ] Confirmar que la OC ya no aparece como pendiente.


## Estado actual del modulo HUENTITAN

Este es el estado funcional despues de conectar productos, compras, entradas e inventario vivo.

### Tramo operativo ya funcional

El siguiente tramo ya esta implementado como base operativa:

```text
Producto HUENTITAN
-> Orden de compra HUENTITAN
-> OC autorizada
-> Entrada desde OC
-> Aplicar entrada
-> Stock positivo
-> Kardex
-> Proveedor/costo del producto
```

### Componentes ya construidos

- Modulo HUENTITAN como panel principal en `/huentitan`.
- Menu y submenu HUENTITAN.
- Permisos base del modulo.
- Empleados HUENTITAN filtrados por area.
- Productos HUENTITAN con ficha, tabs y stock vivo.
- Clasificacion de producto, stock minimo y formula.
- Formulas de fabricacion con materiales internos.
- Ordenes de fabricacion para crear orden, congelar formula, calcular materiales, detectar faltantes y marcar materiales para compra.
- Ordenes de compra HUENTITAN con index filtrado, create preseleccionado, origen stock/obra/orden de fabricacion, materiales marcados desde fabricacion y buscador orientado a HUENTITAN.
- Entradas HUENTITAN con index, entrada desde OC autorizada, recepcion parcial, aplicar al inventario, stock, kardex, recepcion de OC y liga proveedor/costo al producto.

### Pendientes principales

1. Salidas HUENTITAN hacia obra.
2. Enviar orden de fabricacion a produccion con consumo automatico de insumos.
3. Entrada de producto terminado desde orden de fabricacion.
4. Parcialidades y trazabilidad fina entre OC, entrada, fabricacion y movimientos.
5. Limpieza de rutas temporales para carga inicial antes de subir a produccion.
6. [x] Ajuste aplicado: la OC de materiales de fabricacion ya permite modificar cantidad en `/ordenes_compra/{id}/edit` para pedir excedente y dejar stock.

## Desglose propuesto: salidas HUENTITAN hacia obra

Objetivo: permitir que HUENTITAN entregue materia prima o producto terminado a una obra, afectando inventario negativamente y dejando trazabilidad completa en kardex.

### Regla base

Una salida a obra representa material que deja HUENTITAN y queda relacionado con una obra especifica.

Puede incluir:

- Materia prima comprada.
- Insumos internos.
- Producto terminado comprado.
- Producto terminado fabricado en HUENTITAN.

La salida a obra no debe mezclarse con salida a fabricacion. Son destinos distintos.

### Fase SO-1: Modelo base de salidas

- [x] Crear migracion `huentitan_salidas`.
- [x] Crear migracion `huentitan_salida_detalles`.
- [x] Crear modelo `HuentitanSalida`.
- [x] Crear modelo `HuentitanSalidaDetalle`.
- [x] Relacionar salida con almacen HUENTITAN.
- [x] Relacionar salida con obra cuando `tipo_destino = obra`.
- [x] Relacionar detalles con producto.
- [x] Preparar campos de auditoria: usuario, aplicada_por, fechas y cancelacion.

### Fase SO-2: Permisos y rutas

- [x] Crear permiso `huentitan.salidas.view`.
- [x] Crear permiso `huentitan.salidas.create`.
- [x] Crear permiso `huentitan.salidas.apply`.
- [x] Crear permiso `huentitan.salidas.cancel`.
- [x] Asignar permisos base al rol super-admin.
- [x] Agregar rutas `huentitan.salidas.index`, `create`, `store`, `show`, `aplicar` y `cancelar`.
- [x] Agregar opcion `Salidas` al menu HUENTITAN.
- [x] Agregar tarjeta `Salidas` al panel `/huentitan`.

### Fase SO-3: Index de salidas

- [x] Crear vista de listado de salidas.
- [x] Mostrar folio, fecha, destino, obra, estado, usuario y total de partidas.
- [x] Agregar filtros por busqueda, estado y rango de fechas.
- [x] Agregar boton `Nueva salida`.
- [x] Mostrar estado vacio cuando no existan salidas.

### Fase SO-4: Crear salida a obra

- [x] Crear formulario `Nueva salida`.
- [x] Seleccionar tipo de destino `obra`.
- [x] Cargar selector de obras activas.
- [x] Capturar fecha de salida.
- [x] Capturar observaciones generales.
- [x] Agregar buscador de productos HUENTITAN.
- [x] Mostrar stock actual, reservado y disponible del producto seleccionado.
- [x] Permitir agregar varias partidas a la salida.
- [x] Capturar cantidad a enviar por partida.
- [x] Validar en pantalla que la cantidad no exceda stock disponible.

### Fase SO-5: Guardar salida en borrador

- [x] Validar en servidor que el destino sea obra.
- [x] Validar que la obra exista.
- [x] Validar que cada producto pertenezca al catalogo HUENTITAN.
- [x] Validar que cada cantidad sea mayor a cero.
- [x] Revalidar stock disponible en backend.
- [x] Guardar cabecera en estado `borrador`.
- [x] Guardar detalles con cantidad solicitada/salida, unidad, costo promedio e importe estimado.
- [x] No afectar inventario todavia al guardar borrador.

### Regla de implementacion para HUENTITAN

Antes de crear campos, tablas puente o controladores nuevos, se debe revisar el flujo existente completo: modelos, migraciones, controladores, servicios, rutas y vistas. La prioridad es reutilizar conexiones ya creadas y mantener un camino de trazabilidad legible.

Para faltantes de salida a obra, el nodo central sera `orden_compra_detalles`:

`huentitan_salida_detalles -> orden_compra_detalles -> huentitan_entrada_detalles -> huentitan_entradas -> inventario_stock / inventario_movimientos`

No se debe crear una tabla puente adicional mientras este camino resuelva la consulta de forma clara.
### Fase SO-5.5: Faltantes antes de aplicar salida

Objetivo: cuando una salida a obra pide mas material del disponible, el sistema no debe aplicar inventario ni dejar al usuario sin camino. Debe convertir el faltante en una necesidad visible para compra.

Regla operativa:
- La salida puede armarse aunque existan faltantes, pero no puede aplicarse al inventario hasta que el stock sea suficiente.
- El usuario debe ver claramente que productos faltan, cuanto falta y que debe generar una orden de compra y despues una entrada.
- Los productos faltantes deben poder marcarse como `requiere_compra`, similar a lo que ya hicimos en ordenes de fabricacion.
- Esos faltantes deben aparecer como seleccionables al crear una orden de compra HUENTITAN, separados de los faltantes por orden de fabricacion.
- Despues de autorizar la OC y aplicar la entrada, la salida puede volver a intentarse y deberia pasar si ya hay stock suficiente.

- El estado real de la salida no cambia automaticamente al aplicar una entrada; sigue como `borrador` hasta aplicar la salida.
- Al abrir el detalle de la salida se recalcula un estado operativo visual: `con_faltantes` o `listo_para_aplicar`, usando el stock actual.
- Este estado operativo no se guarda como estado fijo porque el stock puede cambiar antes de aplicar la salida.

Tareas pequenas:
- [x] Ajustar borrador de salida para permitir guardar partidas con faltante sin afectar inventario.
- [x] Guardar snapshot de stock disponible al momento de crear la salida.
- [x] Guardar cantidad faltante por partida o calcularla en consulta.
- [x] Agregar bandera `requiere_compra` / `comprar` en los detalles de salida.
- [x] Mostrar alerta clara en la salida cuando existan faltantes.
- [x] Bloquear boton `Aplicar al inventario` si existen faltantes.
- [x] Calcular en el detalle el estado operativo con_faltantes / listo_para_aplicar con stock actual.
- [x] Exponer faltantes de salidas a obra en el flujo de orden de compra HUENTITAN.
- [x] Al crear OC desde HUENTITAN, permitir seleccionar faltantes por origen: stock, orden de fabricacion o salida a obra.
- [x] Ligar el detalle de OC con el detalle de salida cuando la compra venga de una salida faltante.
- [x] Mostrar en el detalle de salida el avance del faltante usando el camino existente: salida -> detalle OC -> entrada.
- [x] Mostrar en el detalle de entrada si cada partida viene de stock, orden de fabricacion o salida a obra.
- [x] Distinguir en el detalle de salida si la compra/recepcion ligada esta parcial o completa.
- [x] Al aplicar entrada de esa OC, dejar trazabilidad para saber que el material entro para surtir una salida a obra.

Implementacion:
- Se agrego huentitan_entrada_detalles.huentitan_salida_detalle_id.
- Al guardar/aplicar una entrada desde una OC originada por faltante de salida a obra, el detalle de entrada conserva liga directa con el detalle de salida.
- La vista de entrada usa esa relacion directa para mostrar el origen Salida a obra.
- El detalle de salida puede sumar entradas aplicadas desde la relacion directa, conservando tambien compatibilidad con el camino por OC.

### Fase SO-6: Aplicar salida a obra

- [x] Validar que la salida este en `borrador`.
- [x] Validar que tenga detalles.
- [x] Bloquear stock por producto con `lockForUpdate`.
- [x] Validar stock disponible nuevamente.
- [x] Restar `stock_actual` del almacen HUENTITAN.
- [x] Recalcular `valor_total`.
- [x] Mantener o recalcular `costo_promedio` segun valor restante.
- [x] Crear movimiento `out` en `inventario_movimientos` por cada detalle.
- [x] Guardar `obra_id` en el movimiento para trazabilidad.
- [x] Marcar salida como `aplicada`.
- [x] Guardar usuario y fecha de aplicacion.

### Fase SO-7: Detalle y kardex

- [x] Crear vista detalle de salida.
- [x] Mostrar cabecera, obra destino, usuario, estado y partidas.
- [x] Linkear salida desde kardex del producto.
- [x] Mostrar salida a obra en el tab kardex del producto.
- [x] Agregar referencia a obra en el movimiento.
- [x] Preparar folio imprimible o comprobante de entrega.

Implementacion SO-7:
- El kardex del producto HUENTITAN resuelve movimientos in contra entradas HUENTITAN y movimientos out contra salidas HUENTITAN.
- Las salidas a obra aparecen con folio enlazado al detalle de salida y referencia visual a la obra destino.
- El ultimo movimiento del producto tambien enlaza a entrada o salida HUENTITAN segun corresponda.
- Se agrego ruta/vista imprimible `huentitan.salidas.print` para comprobante de entrega.

### Fase SO-8: Cancelacion futura

- [ ] Definir regla para cancelar salida aplicada.
- [ ] Validar si la obra ya uso o devolvio el material.
- [ ] Decidir si cancelacion genera movimiento inverso o documento de devolucion.
- [ ] Guardar motivo de cancelacion.
- [ ] Evitar reversos sin trazabilidad.

## Cierre esperado

Este proceso queda cerrado cuando una orden de compra autorizada de HUENTITAN pueda convertirse en una o varias entradas, y cada entrada aplicada actualice correctamente el inventario y el kardex.































