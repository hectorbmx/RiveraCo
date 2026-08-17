# Plan: bandera "Gastos sin factura" en ordenes de compra

Fecha: 2026-08-17

## Objetivo

Agregar una bandera en las ordenes de compra para identificar gastos sin factura.

La bandera debe permitir:

- Capturar el dato desde alta y edicion de ordenes de compra.
- Guardarlo de forma persistente en la cabecera de la orden.
- Mostrarlo claramente en listado, detalle/edicion e impresion individual.
- Generar una separacion adicional para impresion semanal de Giralda.

## Alcance sugerido para primer corte

Este primer corte debe cubrir la bandera completa y usable:

1. Base de datos.
2. Modelo.
3. Validacion.
4. Guardado en crear/editar.
5. Visualizacion en listado y edicion.
6. Marca en PDF individual.
7. Exportacion semanal separada para gastos sin factura.

La caratula resumen de N ordenes por tipo queda como siguiente paso, porque depende de consolidar varios reportes en un solo paquete.

## Regla de negocio confirmada

`gastos_sin_factura` es una bandera independiente de `es_caja_chica`.

Checkpoint de decision (Confirmados 2026-08-17):

- [x] Confirmar si una orden puede ser al mismo tiempo `Caja chica` y `Gastos sin factura`: **SI**.
- [x] Confirmar si `Gastos sin factura` tambien permite omitir proveedor: **SI** (el proveedor es opcional cuando `es_caja_chica` o `gastos_sin_factura` es verdadero).

## Archivos detectados

Modelo:

- `app/Models/OrdenCompra.php`

Requests:

- `app/Http/Requests/StoreOrdenCompraRequest.php`
- `app/Http/Requests/UpdateOrdenCompraRequest.php`

Controlador:

- `app/Http/Controllers/OrdenCompraController.php`

Vistas:

- `resources/views/ordencompra/create.blade.php`
- `resources/views/ordencompra/edit.blade.php`
- `resources/views/ordencompra/index.blade.php`

Rutas:

- `routes/web.php`

Migracion relacionada existente:

- `database/migrations/2026_08_10_100000_add_es_caja_chica_to_ordenes_compra.php`

## Paso 1: Base de datos

Crear una migracion nueva para agregar el campo:

- Tabla: `ordenes_compra`
- Campo: `gastos_sin_factura`
- Tipo: boolean
- Default: false
- Index: si
- Ubicacion sugerida: despues de `es_caja_chica`

Checkpoint:

- [ ] Existe migracion nueva.
- [ ] La migracion valida `Schema::hasColumn` antes de agregar.
- [ ] El rollback elimina el campo si existe.
- [ ] `php artisan migrate` corre sin errores en local.

## Paso 2: Modelo

Actualizar `app/Models/OrdenCompra.php`.

Cambios:

- Agregar `gastos_sin_factura` a `$fillable`.
- Agregar cast booleano en `$casts`.

Checkpoint:

- [ ] `$fillable` incluye `gastos_sin_factura`.
- [ ] `$casts['gastos_sin_factura'] = 'boolean'`.
- [ ] No se altera comportamiento existente de `es_caja_chica`.

## Paso 3: Validacion

Actualizar:

- `StoreOrdenCompraRequest`
- `UpdateOrdenCompraRequest`

Regla sugerida:

```php
'gastos_sin_factura' => ['nullable', 'boolean'],
```

Checkpoint:

- [ ] Crear OC acepta checkbox marcado.
- [ ] Crear OC acepta checkbox desmarcado.
- [ ] Editar OC acepta checkbox marcado.
- [ ] Editar OC acepta checkbox desmarcado.
- [ ] La regla de proveedor sigue funcionando como antes.

## Paso 4: Guardado en controller

Actualizar `OrdenCompraController`.

En `store`:

```php
$gastosSinFactura = $request->boolean('gastos_sin_factura');
$oc->gastos_sin_factura = $gastosSinFactura;
```

En `update`:

```php
$gastosSinFactura = $request->boolean('gastos_sin_factura');
$oc->gastos_sin_factura = $gastosSinFactura;
```

Checkpoint:

- [ ] Una OC nueva queda con `gastos_sin_factura = 1` cuando se marca.
- [ ] Una OC nueva queda con `gastos_sin_factura = 0` cuando no se marca.
- [ ] Al editar se puede activar.
- [ ] Al editar se puede desactivar.

## Paso 5: Formulario de alta

Actualizar `resources/views/ordencompra/create.blade.php`.

Agregar un checkbox junto al de caja chica:

- Label: `Gastos sin factura`
- Name: `gastos_sin_factura`
- Value: `1`
- Mantener estado con `old('gastos_sin_factura')`

Checkpoint:

- [ ] El checkbox aparece en nueva orden.
- [ ] El estilo no rompe el layout.
- [ ] Al fallar validacion, conserva el valor marcado.

## Paso 6: Vista de edicion

Actualizar `resources/views/ordencompra/edit.blade.php`.

Cambios:

- Mostrar badge en el titulo si `gastos_sin_factura` es true.
- Agregar checkbox editable dentro del formulario de encabezado.
- Si la orden esta bloqueada por estado, no debe permitir editar la bandera.

Checkpoint:

- [ ] El badge aparece en ordenes marcadas.
- [ ] El checkbox aparece en ordenes editables.
- [ ] El checkbox conserva el valor actual.
- [ ] Ordenes autorizadas/verificadas/canceladas siguen bloqueadas como antes.

## Paso 7: Listado

Actualizar `resources/views/ordencompra/index.blade.php`.

Cambios:

- En la celda de folio, mostrar badge `Gasto sin factura`.
- Mantener badge `Caja chica`.
- Si una orden tiene ambas banderas, mostrar ambas.

Checkpoint:

- [ ] El listado muestra visualmente las ordenes sin factura.
- [ ] Caja chica sigue mostrandose igual.
- [ ] No se rompe la tabla en resoluciones chicas.

## Paso 8: PDF individual de orden de compra

Actualizar `OrdenCompraController@print`.

Cambios:

- Agregar una marca visible si `$oc->gastos_sin_factura` es true.
- Texto sugerido: `GASTO SIN FACTURA`.
- Ubicacion sugerida: cerca del bloque de folio/obra o debajo del encabezado.

Checkpoint:

- [ ] PDF de OC normal no muestra la marca.
- [ ] PDF de OC marcada muestra `GASTO SIN FACTURA`.
- [ ] La marca no tapa folio, proveedor, obra ni totales.

## Paso 9: Separacion semanal para Giralda

Actualmente `exportarListaPagos()` genera reportes semanales para GL por forma de pago y caja chica.

Crear una separacion adicional:

- Ruta nueva sugerida:
  - `ordenes-compra/exportar-gastos-sin-factura`
- Metodo sugerido:
  - `exportarGastosSinFactura(Request $request)`
- Filtros:
  - area codigo `GL`
  - semana seleccionada
  - `estado` en `AUTORIZADA`, `VERIFICADA`
  - `gastos_sin_factura = true`
  - folio `OC-GL-%`

Checkpoint:

- [ ] La ruta existe y requiere permisos de vista/impresion equivalentes a los reportes actuales.
- [ ] El PDF incluye solo ordenes marcadas como gastos sin factura.
- [ ] Respeta semana seleccionada.
- [ ] No incluye semanas futuras.
- [ ] El nombre del archivo identifica el periodo.

## Paso 10: Boton de impresion adicional

Actualizar `resources/views/ordencompra/index.blade.php`.

En el bloque especial de `area_codigo=GL`, agregar boton:

- Texto: `Exportar gastos sin factura`
- Abre en nueva pestana.
- Envia `area_codigo` y `semana`.

Checkpoint:

- [ ] El boton aparece solo en vista GL.
- [ ] Respeta la semana seleccionada.
- [ ] Abre el PDF correcto.
- [ ] Los botones de efectivo y tarjeta siguen funcionando.

## Paso 11: Validaciones tecnicas

Ejecutar:

```bash
php artisan migrate
php -l app/Models/OrdenCompra.php
php -l app/Http/Requests/StoreOrdenCompraRequest.php
php -l app/Http/Requests/UpdateOrdenCompraRequest.php
php -l app/Http/Controllers/OrdenCompraController.php
php artisan view:cache
php tools/check_mojibake.php app/Models/OrdenCompra.php app/Http/Controllers/OrdenCompraController.php resources/views/ordencompra/create.blade.php resources/views/ordencompra/edit.blade.php resources/views/ordencompra/index.blade.php
git diff --check
graphify update .
```

Checkpoint:

- [ ] Migracion aplicada.
- [ ] PHP lint sin errores.
- [ ] Vistas compilan.
- [ ] Sin mojibake nuevo.
- [ ] Sin whitespace errors.
- [ ] Grafo actualizado.

## Pruebas manuales

Crear orden:

- [ ] Crear OC normal.
- [ ] Crear OC con `Caja chica`.
- [ ] Crear OC con `Gastos sin factura`.
- [ ] Crear OC con ambas banderas, si negocio lo permite.

Editar orden:

- [ ] Activar `Gastos sin factura`.
- [ ] Desactivar `Gastos sin factura`.
- [ ] Confirmar que orden autorizada/verificada/cancelada no permita editar encabezado.

Listado:

- [ ] Confirmar badges correctos.
- [ ] Confirmar total y acciones sin cambios.

PDF individual:

- [ ] Imprimir OC normal.
- [ ] Imprimir OC con gasto sin factura.

Reporte GL:

- [ ] Entrar a `/ordenes_compra?area_codigo=GL`.
- [ ] Seleccionar semana actual.
- [ ] Exportar gastos sin factura.
- [ ] Navegar a semana anterior.
- [ ] Exportar gastos sin factura.
- [ ] Confirmar que efectivo y tarjeta siguen exportando caja chica.

## Pendiente posterior

Caratula resumen:

- Lista de N ordenes por tipo.
- Totales por tipo.
- Posibles tipos:
  - Ordenes normales.
  - Caja chica efectivo.
  - Caja chica tarjeta.
  - Gastos sin factura.
- Definir si la caratula debe ser un PDF independiente o primera pagina de un paquete de impresion.
