# Módulo: Pendientes de programación

## Objetivo

Crear una bandeja semanal para que el administrador consulte todo lo que está registrado para pago, sin importar si proviene de una orden de compra o de una reposición de caja chica.

El módulo debe permitir filtrar, imprimir y exportar la información conservando la trazabilidad del documento original.

## Fuentes de información

### Órdenes de compra

El módulo debe consultar órdenes de compra que puedan generar un pago:

- Proveedor.
- Folio de la orden.
- Obra o almacén relacionado.
- Importe total.
- Saldo pendiente.
- Estado de autorización.
- Estado de programación.
- Estado de pago.
- Fecha programada de pago.
- Fecha de ejecución del pago.

### Reposición de caja chica

Debe consultar gastos provenientes de:

- Almacén.
- Residentes de obra.

Datos principales:

- Folio del gasto.
- Proveedor.
- Concepto.
- Importe registrado.
- Importe autorizado.
- Estado de autorización.
- Obra o almacén destino.
- Fecha de registro.
- Fecha programada de pago.
- Fecha de ejecución del pago.

## Ámbitos operativos

El administrador debe poder consultar todos los registros o filtrar por ámbito.

### Almacén general

Obligaciones relacionadas directamente con un almacén general.

### Almacén con cargo a obra

Gastos capturados por almacén, pero asignados financieramente a una obra.

Regla de identificación esperada:

```text
origen_captura = almacen
destino = obra
```

### Residentes / obra

Gastos capturados directamente por un residente dentro del contexto de una obra.

Regla de identificación esperada:

```text
origen_captura = obra_residente
destino = obra
```

La diferencia debe conservarse aunque ambos gastos terminen cargados a la misma obra, porque tienen distinto responsable y flujo operativo.

### Todas

Vista consolidada de todas las fuentes y ámbitos.

## Campos comunes del consolidado

La bandeja debe normalizar la información de ambas fuentes con una estructura común:

```text
fuente
fuente_id
folio
proveedor
obra
almacen
ambito
importe_registrado
importe_autorizado
saldo_pendiente
estado
fecha_registro
fecha_programada_pago
fecha_pago
```

## Filtros

- Semana o periodo.
- Fuente: orden de compra o caja chica.
- Ámbito.
- Obra.
- Almacén.
- Proveedor.
- Estado.
- Tipo de pago.
- Búsqueda por folio, proveedor o concepto.

## Estados esperados

- Pendiente de autorización.
- Autorizado.
- Pendiente de programación.
- Programado.
- Pagado.
- Rechazado.
- Cancelado.

## Regla de fechas

La bandeja semanal debe priorizar la fecha programada de pago.

Propuesta:

1. Si existe `fecha_programada_pago`, el registro aparece en la semana correspondiente.
2. Si no existe, aparece en una sección de pendientes de programación.
3. La fecha del gasto o de la orden sirve como referencia, pero no debe sustituir a la fecha programada.

Esto evita que un gasto antiguo desaparezca de la operación solo porque fue registrado en una semana anterior.

## Impresión y exportación

El módulo debe permitir:

- Imprimir la semana seleccionada.
- Imprimir por ámbito.
- Imprimir por obra o almacén.
- Exportar el resultado filtrado a Excel.
- Mostrar totales por fuente y ámbito.
- Mostrar el estado de cada obligación.

Cada fila debe incluir un enlace al documento original:

- Orden de compra.
- Gasto de caja chica.

## Recomendación técnica

No fusionar físicamente las órdenes de compra y los gastos en una nueva tabla.

La primera versión debe usar una consulta consolidada o un DTO con campos comunes. De esta manera:

- Se conserva la lógica actual de cada módulo.
- No se duplican documentos.
- Se mantiene la trazabilidad hacia el origen.
- Se pueden agregar nuevas fuentes posteriormente.

Una tabla propia de programación de pagos podría evaluarse después si se necesita guardar una autorización global, lote de pago o historial de cambios de programación.

## Plan de trabajo MVP

### 1. Barrido técnico

- Revisar estados actuales de órdenes de compra.
- Revisar estados actuales de caja chica.
- Identificar campos existentes de programación y ejecución de pago.
- Confirmar relaciones con proveedor, obra y almacén.

**Checkpoint:** documentar qué datos existen realmente y qué datos faltan.

### 2. Definir el contrato del consolidado

- Definir nombres de los campos comunes.
- Definir cómo se determina la fuente.
- Definir cómo se determina el ámbito.
- Definir qué importe se muestra según el estado.

**Checkpoint:** una fila de OC y una fila de caja chica pueden representarse con la misma estructura.

### 3. Crear consulta consolidada

- Consultar órdenes de compra.
- Consultar reposiciones de caja chica.
- Normalizar los resultados.
- Unir ambas colecciones.
- Ordenar por fecha programada y prioridad.

**Checkpoint:** obtener una lista consolidada sin modificar datos.

### 4. Crear controlador propio

Crear un controlador dedicado, sugerencia:

```text
PendientesProgramacionController
```

Responsabilidades iniciales:

- Index semanal.
- Filtros.
- Impresión.
- Exportación.

### 5. Crear vista index

- Filtros superiores.
- Resumen de totales.
- Tabla consolidada.
- Indicador de fuente.
- Indicador de ámbito.
- Enlace al documento original.

### 6. Agregar impresión

- Imprimir resultado filtrado.
- Mostrar semana y filtros utilizados.
- Mostrar totales.
- Incluir espacio para autorización administrativa si se requiere.

### 7. Agregar exportación

- Exportar exactamente el resultado filtrado.
- Incluir fuente, ámbito, folio, proveedor, importe y estado.

### 8. Permisos

Definir permisos independientes para:

- Consultar pendientes.
- Imprimir pendientes.
- Exportar pendientes.
- Programar pagos.
- Autorizar pagos.
- Ejecutar pagos.

### 9. Pruebas

- OC pendiente de pago.
- OC parcialmente pagada.
- Gasto de almacén.
- Gasto de almacén cargado a obra.
- Gasto de residente.
- Registro sin fecha programada.
- Filtros por semana.
- Filtros por ámbito.
- Impresión.
- Exportación.

## Decisiones pendientes

- Definir el nombre final visible: `Pendientes de programación` o `Programación de pagos`.
- Confirmar si las OCs sin fecha programada deben aparecer en la semana actual o solo en pendientes.
- Definir quién puede cambiar la fecha programada.
- Definir si la autorización administrativa se hará por fila o por lote semanal.
- Definir si la impresión requiere firmas.
- Confirmar los ámbitos finales para Giralda, Huentitán y obras.

## Estado actual

- Documento creado.
- No se han creado rutas ni controladores.
- No se han modificado tablas existentes.
- La recomendación vigente es iniciar con un consolidado de lectura, impresión y exportación.
