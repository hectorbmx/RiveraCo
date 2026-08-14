# Fase 0: Diagnostico Para Implementar Obra Civil

## Objetivo

Identificar como el sistema actual maneja obras, ordenes de compra, detalles de compra y presupuestos, para decidir donde conectar el nuevo modulo de Obra Civil.

## Hallazgos Principales

- El sistema ya tiene un modelo central de obra en `app/Models/Obra.php`.
- La obra ya maneja el campo `tipo_obra`.
- Los tipos de obra disponibles se resuelven desde configuracion mediante `ObraTipoConfiguracion`.
- Si no hay configuracion, el sistema usa tipos base como `PILAS` y `POZOS`.
- `ObraController` valida `tipo_obra` contra los tipos disponibles.
- `ObraController` asigna `area_id` segun el tipo de obra.
- Las ordenes de compra ya existen en `ordenes_compra`, usando el modelo `app/Models/OrdenCompra.php`.
- Las ordenes de compra ya se relacionan con obra por medio de `obra_id`.
- Los detalles de orden de compra ya existen en `orden_compra_detalles`, usando el modelo `app/Models/OrdenCompraDetalle.php`.
- Los totales de una OC se recalculan desde sus detalles mediante `OrdenCompraTotalesService`.
- La pantalla actual de edicion de OC agrega detalles desde `resources/views/ordencompra/edit.blade.php`.
- Las rutas de detalles de OC ya son anidadas bajo la orden de compra.
- Una OC no permite modificar detalles cuando esta autorizada, verificada o cancelada.
- La autorizacion de OC ya valida presupuesto usando `planeacion_gasto_id` en la cabecera.
- Existe `ObraPlaneacionGasto`, que ya guarda partida, concepto, unidad, cantidad, precio unitario y monto programado.

## Rutas Relevantes Detectadas

```text
Route::resource('ordenes_compra', OrdenCompraController::class)->except(['show','destroy']);
POST ordenes_compra/{id}/autorizar
POST ordenes_compra/{id}/verificar
POST ordenes_compra/{id}/cancelar
POST ordenes_compra/{orden}/detalles
PUT ordenes_compra/{orden}/detalles/{detalle}
DELETE ordenes_compra/{orden}/detalles/{detalle}
GET ordenes-compra/partidas-obra/{obra_id}
```

## Modelos Relevantes

### `Obra`

Archivo:

```text
app/Models/Obra.php
```

Campos relevantes:

- `cliente_id`
- `nombre`
- `clave_obra`
- `descripcion`
- `tipo_obra`
- `area_id`
- `estatus_nuevo`
- `monto_contratado`
- `monto_modificado`
- `responsable_id`

Relaciones relevantes:

- `presupuestos`
- `presupuestos_vinculados`
- `pilas`
- `planeacionGastos`
- `gastosPlaneados`
- `cfdis`

### `OrdenCompra`

Archivo:

```text
app/Models/OrdenCompra.php
```

Campos relevantes:

- `folio`
- `proveedor_id`
- `obra_id`
- `centro_costo_id`
- `area_id`
- `subtotal`
- `iva`
- `otros_impuestos`
- `total`
- `fecha`
- `estado`
- `usuario_registro`
- `autorizado_por`
- `fecha_autorizacion`
- `verificado_por`
- `fecha_verificacion`
- `comentarios`

Relaciones relevantes:

- `proveedor`
- `obra`
- `centroCosto`
- `areaCatalogo`
- `detalles`
- `pagosProveedor`
- `planeacionGasto`

Estados normalizados detectados:

- `programada`
- `autorizada`
- `verificada`
- `cancelada`

### `OrdenCompraDetalle`

Archivo:

```text
app/Models/OrdenCompraDetalle.php
```

Campos actuales relevantes:

- `orden_compra_id`
- `producto_id`
- `legacy_prod_id`
- `descripcion`
- `unidad`
- `cantidad`
- `precio_unitario`
- `descuento_porcentaje`
- `descuento_importe`
- `importe`
- `iva`
- `tipo_retencion_id`
- `retencion_porcentaje`
- `retenciones`
- `otros_impuestos`
- `tipo_cambio`
- `notas`

Este es el punto recomendado para agregar la referencia a conceptos civiles.

### `ObraPlaneacionGasto`

Archivo:

```text
app/Models/ObraPlaneacionGasto.php
```

Campos relevantes:

- `obra_id`
- `presupuesto_id`
- `partida`
- `concepto`
- `unidad`
- `cantidad`
- `precio_unitario`
- `numero_semana`
- `monto_programado`
- `presupuesto_detalle_id`
- `presupuesto_pila_id`

## Interpretacion

El sistema actual ya tiene una base util para Obra Civil:

1. **Obras con tipo configurable**

   Obra Civil debe entrar como un nuevo `tipo_obra`, probablemente `OBRA_CIVIL` o `CIVIL`, usando la configuracion existente.

2. **Ordenes de compra reutilizables**

   No conviene crear otro modulo de compras. Las OC actuales ya tienen estados, autorizacion, proveedor, obra, detalles, totales e impresion.

3. **Detalles de OC como punto de integracion**

   Obra Civil necesita controlar saldo por concepto. Por eso la relacion debe estar en `orden_compra_detalles`, no solo en `ordenes_compra`.

4. **Planeacion actual no alcanza para catalogo civil**

   `planeacion_gasto_id` vive en la cabecera de OC y controla una partida general. Obra Civil requiere multiples conceptos por OC, cada uno con su propio saldo.

## Decision Recomendada

No usar `planeacion_gasto_id` como base principal de Obra Civil.

Crear un catalogo civil propio y conectarlo a los detalles de OC:

```text
orden_compra_detalles.civil_concept_id -> civil_concepts.id
```

Esto permite:

- Una OC con varios conceptos civiles.
- Control de saldo por concepto.
- Trazabilidad al Excel importado.
- No mezclar planeacion semanal con catalogo civil.
- Mantener el flujo actual de compras.

## Modelo Tecnico Inicial Recomendado

Tablas nuevas:

- `civil_catalog_imports`
- `civil_buildings`
- `civil_partidas`
- `civil_concepts`

Extensiones a tablas existentes:

- `orden_compra_detalles.civil_concept_id`
- opcional: `orden_compra_detalles.civil_concept_snapshot`

El snapshot permitiria conservar descripcion, unidad, precio catalogo y estructura original aunque el catalogo se actualice despues.

## Regla De Saldo Recomendada

El saldo civil debe calcularse desde detalles de OC relacionados a cada concepto:

```text
presupuesto del concepto
- detalles de OC autorizadas
- detalles de OC verificadas
= saldo disponible
```

Primera version recomendada:

- `BORRADOR`: no afecta saldo.
- `AUTORIZADA`: compromete saldo.
- `VERIFICADA`: confirma consumo.
- `CANCELADA`: no consume y libera lo comprometido.

## Checkpoint Fase 0

- Obra Civil debe entrar como nuevo `tipo_obra`.
- Las OC actuales se deben reutilizar.
- La relacion con catalogo civil debe estar en `orden_compra_detalles`.
- El importador debe poblar tablas nuevas de catalogo civil.
- El saldo civil debe calcularse por concepto.
- El siguiente paso tecnico es crear migraciones/modelos base.

## Siguiente Paso

Implementar la base estructural:

1. Agregar tipo de obra `OBRA_CIVIL` a configuracion o seed.
2. Crear migraciones para tablas civiles.
3. Crear modelos `CivilCatalogImport`, `CivilBuilding`, `CivilPartida`, `CivilConcept`.
4. Agregar `civil_concept_id` a `orden_compra_detalles`.
5. Agregar relaciones Eloquent.
6. Crear servicio de saldo civil.

