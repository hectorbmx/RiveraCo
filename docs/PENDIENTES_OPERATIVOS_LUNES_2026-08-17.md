# Pendientes operativos: lunes 17 de agosto de 2026

**Fecha de registro:** 2026-08-14  
**Fecha objetivo:** lunes 17 de agosto de 2026  
**Estado:** Pendiente  
**Relacionado con:** ordenes de compra, Giralda, impresion de OC y mantenimiento de maquinaria.

## Regla de trabajo

Antes de implementar estos pendientes se debe revisar el flujo actual de ordenes de compra con el grafo y con la documentacion existente. No reemplazar el flujo actual si se puede extender con campos, filtros y vistas nuevas.

Fuentes a revisar antes de tocar codigo:

- `docs/ROADMAP_INSUMOS_ORDENES_COMPRA.md`
- `docs/MODULO_GIRALDA.md`
- Modelo `app/Models/OrdenCompra.php`
- Controlador `app/Http/Controllers/OrdenCompraController.php`
- Requests `StoreOrdenCompraRequest` y `UpdateOrdenCompraRequest`
- Vista/formulario de ordenes de compra.
- Vista de impresion de ordenes de compra.
- Modelos de maquinaria y mantenimiento: `Maquina`, `Mantenimiento`.

## Pendiente 1: ordenes de compra de Giralda con codigo GL

### Objetivo

Agregar a las ordenes de compra una clasificacion o entrada operativa para Giralda usando codigo `GL`.

### Checkpoints

- [ ] Revisar si Giralda debe ser un centro de costo, tipo de OC, area, almacen o prefijo de folio.
- [ ] Definir si el codigo `GL` vive en la cabecera de la orden o se deriva de un catalogo.
- [ ] Permitir crear ordenes de compra de Giralda.
- [ ] Filtrar o identificar las ordenes de compra de Giralda en listado y busqueda.
- [ ] Validar si el folio/impresion debe mostrar el codigo `GL`.
- [ ] Revisar permisos de usuarios Giralda para crear, ver, autorizar y dar seguimiento.

## Pendiente 2: bandera "gastos sin factura"

### Objetivo

Agregar un check nuevo en ordenes de compra llamado `Gastos sin factura`.

### Comportamiento esperado

- [ ] Guardar la bandera en la orden de compra.
- [ ] Usar la bandera para generar una separacion adicional de ordenes.
- [ ] Generar una impresion adicional para las ordenes marcadas como `Gastos sin factura`.
- [ ] Revisar si debe convivir con `es_caja_chica` o si son clasificaciones independientes.
- [ ] Mostrar la bandera en listado, formulario y vista de impresion cuando aplique.

## Pendiente 3: caratula de resumen de ordenes generadas

### Objetivo

Generar una caratula con resumen de las ordenes de compra generadas.

### Contenido esperado

- [ ] Mostrar una lista con las N ordenes de compra generadas por cada tipo.
- [ ] Separar tipos relevantes: normales, Giralda/GL, caja chica, gastos sin factura y mantenimiento si aplica.
- [ ] Incluir folio, proveedor, obra/centro de costo, fecha, total y estado.
- [ ] Mostrar totales por tipo y total general.
- [ ] Definir si la caratula se imprime junto con el paquete de ordenes o como impresion independiente.

## Pendiente 4: check "es mantenimiento" y selector de maquina

### Objetivo

Agregar un check `Es mantenimiento` en ordenes de compra. Al activarlo debe habilitar un select para elegir la maquina a la que va dirigido el mantenimiento.

### Checkpoints

- [ ] Agregar bandera `es_mantenimiento` a la orden de compra.
- [ ] Agregar `maquina_id` nullable y relacion con `Maquina`.
- [ ] En el formulario, mostrar el select de maquina solo cuando `Es mantenimiento` este activo.
- [ ] Validar en backend que `maquina_id` sea requerido cuando `es_mantenimiento` este activo.
- [ ] Mostrar maquina en listado/detalle/impresion cuando aplique.
- [ ] Revisar si debe crear o enlazar un registro en el modulo `Mantenimiento`.

## Preguntas pendientes de definicion

- [ ] Confirmar si `GL` debe ser prefijo de folio, centro de costo, area o tipo de orden.
- [ ] Confirmar si `Gastos sin factura` requiere flujo de autorizacion diferente.
- [ ] Confirmar si la caratula debe generarse por rango de fechas, por seleccion manual o despues de crear varias ordenes.
- [ ] Confirmar si una orden marcada como mantenimiento debe crear automaticamente un mantenimiento o solo quedar ligada a una maquina.

