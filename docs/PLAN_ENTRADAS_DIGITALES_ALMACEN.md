# Entradas digitales al almacén

## Objetivo

Permitir que una entrada de compra distribuya sus partidas entre una solicitud de servicio y el inventario disponible, evitando capturas duplicadas cuando el material se compra para uso inmediato y para stock.

El flujo debe conservar la trazabilidad entre:

- Orden de compra.
- Detalle de orden de compra.
- Entrada de inventario.
- Solicitud de mantenimiento.
- Salida de almacén.
- Existencia final.

## Contexto del negocio

Un mecánico puede crear una solicitud de servicio con materiales faltantes. Compras puede adquirir exactamente lo solicitado o comprar una cantidad mayor para dejar material en stock.

Ejemplo:

```text
Necesidad del servicio: 1 unidad
Compra realizada: 2 unidades
Destino de 1 unidad: servicio de mantenimiento
Destino de 1 unidad: inventario de almacén
```

No todos los materiales deben recorrer manualmente el flujo completo de entrada y salida. La entrada debe permitir registrar la distribución y, cuando corresponda, generar automáticamente la salida al servicio.

## Reglas de negocio confirmadas

1. Una entrada puede tener partidas para servicio, stock o ambas.
2. El destino se define por partida.
3. La cantidad para servicio más la cantidad para stock debe ser igual a la cantidad recibida.
4. Una partida directa a servicio debe estar vinculada a una solicitud SMA.
5. Una partida destinada solamente a stock no genera salida.
6. Una partida mixta genera salida únicamente por la cantidad destinada al servicio y conserva en inventario la cantidad destinada a stock.
7. La solicitud SMA debe tener saldo pendiente para recibir una asignación.
8. No se puede asignar al servicio una cantidad mayor que la pendiente de la solicitud.
9. La aplicación debe ser transaccional: si falla la entrada, la salida automática tampoco debe quedar aplicada.
10. Los documentos históricos no deben modificarse por la nueva funcionalidad.

## Estado actual del sistema

### Entrada de inventario

La entrada utiliza:

- `inventario_documentos`.
- `inventario_documento_detalles`.
- `orden_compra_id`.
- `orden_compra_detalle_id`.
- `cantidad`.
- `costo_unitario`.

Al aplicar una entrada actualmente:

- Incrementa el inventario.
- Actualiza `cantidad_recibida` del detalle de la OC.
- Actualiza el estado de recepción de la partida.
- Actualiza el estado general de la OC.

### Orden de compra

La OC cuenta con:

- `estado_recepcion` en la cabecera.
- `cantidad_recibida` por detalle.
- `estado_recepcion` por detalle.
- `mantenimiento_abastecimiento_id` en la cabecera.
- `mantenimiento_abastecimiento_detalle_id` en cada detalle.

Estados de recepción utilizados:

- `pendiente`.
- `parcial`.
- `recibida`.

Las OCs completamente recibidas ya no deben aparecer en el buscador de entradas.

### Solicitud de mantenimiento

La solicitud SMA cuenta con:

- Folio.
- Tipo de abastecimiento: almacén o compra.
- Estado.
- Detalles por producto.
- Cantidad requerida.
- Cantidad solicitada.
- Cantidad atendida.
- Estado por partida.
- Relación con el mantenimiento.

La solicitud ya soporta surtidos parciales.

### Salida de almacén

La salida puede relacionarse con una solicitud mediante:

```text
inventario_documentos.mantenimiento_abastecimiento_id
```

Al aplicar una salida relacionada:

- Descuenta inventario.
- Incrementa `cantidad_atendida` de la solicitud.
- Marca la solicitud como parcial o surtida.
- Actualiza el mantenimiento.

## Hallazgo principal

Actualmente la entrada no distingue el destino de cada partida. Toda la cantidad recibida se trata como inventario físico disponible.

Todavía no se puede representar de forma nativa:

```text
Cantidad recibida: 2
Cantidad para servicio: 1
Cantidad para stock: 1
```

Los campos existentes `vehiculo_id`, `maquina_id` y `equipo_tipo` pertenecen a la trazabilidad de salidas y no deben reutilizarse para dividir una entrada.

## Diseño propuesto

Agregar a `inventario_documento_detalles`:

- `destino_tipo`: `stock`, `servicio` o `mixto`.
- `cantidad_para_servicio`.
- `cantidad_para_stock`.
- `mantenimiento_abastecimiento_id` nullable.
- `mantenimiento_abastecimiento_detalle_id` nullable.

La relación por detalle es importante porque una misma entrada puede tener partidas con destinos diferentes.

La relación de cabecera puede conservarse cuando todo el documento corresponde a una sola solicitud, pero la fuente principal para la distribución debe ser el detalle.

## Flujo esperado

### Caso A: todo para stock

```text
Recibido: 2
Servicio: 0
Stock: 2
```

Resultado:

- Entrada normal por 2.
- Existencia aumenta en 2.
- No se genera salida.

### Caso B: todo para servicio

```text
Recibido: 2
Servicio: 2
Stock: 0
```

Resultado:

- Se registra la recepción de la OC.
- Se genera salida automática por 2.
- La solicitud SMA se actualiza como atendida.
- El inventario neto queda en cero.

### Caso C: entrada mixta

```text
Recibido: 2
Servicio: 1
Stock: 1
```

Resultado:

- Se registra la recepción total de 2.
- Se genera salida automática por 1.
- Se incrementa el stock por 1.
- La solicitud SMA recibe 1 unidad atendida.

## Plan de trabajo

### Paso 1. Confirmar reglas de negocio

- Reglas confirmadas por el usuario.
- Definir los tres destinos: stock, servicio y mixto.
- Confirmar que la asignación se realiza por partida.

**Checkpoint:** no existen cantidades sin destino y toda partida de servicio tiene una SMA.

### Paso 2. Revisar la estructura actual

- Revisar modelos de inventario.
- Revisar migraciones de documentos y detalles.
- Revisar aplicación y cancelación de entradas.
- Revisar actualización de OCs.
- Revisar actualización de solicitudes SMA.

**Checkpoint:** completado. La estructura actual soporta las relaciones base, pero no la distribución servicio/stock por partida.

### Paso 3. Diseñar y aprobar los campos

- Confirmar nombres de columnas.
- Confirmar estados permitidos.
- Definir si las cantidades se guardan como decimales.
- Definir si la relación SMA estará en cabecera, detalle o ambas.

**Checkpoint:** el modelo representa entradas normales, directas y mixtas sin romper documentos anteriores.

### Paso 4. Crear migración

- Agregar campos a `inventario_documento_detalles`.
- Agregar índices para las relaciones SMA.
- Usar valores compatibles con documentos existentes.
- No alterar datos históricos.

**Checkpoint:** la migración es reversible y no requiere borrar información.

### Paso 5. Actualizar modelos y relaciones

- Agregar campos a `$fillable`.
- Agregar casts numéricos.
- Crear relaciones con solicitud y detalle de solicitud.
- Agregar accesores útiles para cantidades asignadas.

**Checkpoint:** las partidas pueden consultar su OC, solicitud SMA, destino y cantidades.

### Paso 6. Preparar datos de la pantalla de entrada

- Cargar solicitudes SMA con saldo pendiente.
- Mostrar solo solicitudes activas.
- Mantener el buscador de OC.
- Excluir solicitudes completamente atendidas.

**Checkpoint:** el almacenista puede seleccionar una solicitud válida desde la entrada.

### Paso 7. Ajustar la interfaz por partida

- Agregar selector de destino.
- Mostrar solicitud SMA cuando el destino sea servicio o mixto.
- Mostrar cantidades para servicio y stock.
- Calcular diferencias en tiempo real.
- Mostrar mensajes cuando la suma no coincida.

**Checkpoint:** el usuario puede construir los tres escenarios sin ambigüedad.

### Paso 8. Validar en backend

- Validar destino permitido.
- Validar cantidades no negativas.
- Validar suma contra cantidad recibida.
- Validar SMA existente.
- Validar producto perteneciente a la SMA.
- Validar saldo pendiente.
- Evitar cantidades superiores a lo solicitado.

**Checkpoint:** ninguna regla depende únicamente del navegador.

### Paso 9. Aplicar entrada normal

- Incrementar stock solo por `cantidad_para_stock`.
- Actualizar recepción de la OC con la cantidad total recibida.
- Actualizar estado de OC y detalle.
- Registrar movimiento con referencia de origen.

**Checkpoint:** el inventario refleja únicamente lo destinado a stock.

### Paso 10. Generar salida automática de servicio

- Crear salida relacionada con la SMA.
- Usar la cantidad destinada a servicio.
- Vincular entrada, OC, detalle de OC y SMA.
- Actualizar `cantidad_atendida`.
- Actualizar estado de la solicitud.

**Checkpoint:** no se requiere una segunda captura manual de salida.

### Paso 11. Procesar entradas mixtas

- Registrar entrada total.
- Separar cantidades de stock y servicio.
- Generar salida solo por servicio.
- Dejar disponible solo la parte de stock.

**Checkpoint:** la suma de movimientos coincide con la cantidad recibida.

### Paso 12. Cancelación y reversión

- Revertir existencia de stock.
- Cancelar o revertir la salida automática.
- Disminuir la cantidad atendida de la SMA.
- Disminuir la cantidad recibida de la OC.
- Recalcular estados.

**Checkpoint:** cancelar la entrada no deja cantidades atendidas o existencias falsas.

### Paso 13. Reportes y trazabilidad

- Mostrar destino por partida.
- Mostrar cantidad recibida.
- Mostrar cantidad para servicio.
- Mostrar cantidad para stock.
- Mostrar folio de salida automática.
- Mostrar OC y SMA relacionadas.

**Checkpoint:** una persona puede reconstruir el recorrido completo desde cualquier documento.

### Paso 14. Pruebas funcionales

- Entrada completa a stock.
- Entrada completa a servicio.
- Entrada mixta.
- OC recibida parcialmente.
- Varias OCs para una misma SMA.
- SMA ya atendida.
- Cantidad de servicio mayor al pendiente.
- Suma de cantidades incorrecta.
- Reintento de aplicar la misma entrada.
- Cancelación de entrada mixta.

**Checkpoint:** los estados, existencias y relaciones son consistentes en todos los escenarios.

### Paso 15. Despliegue

- Crear commit de checkpoint.
- Revisar migraciones nuevas.
- Respaldar producción.
- Ejecutar migraciones.
- Probar primero una entrada normal.
- Probar después una entrada directa y una mixta.
- Revisar inventario, OC, SMA y reportes.

**Checkpoint final:** el flujo de entradas digitales funciona sin duplicar capturas ni inflar existencias.

## Decisión pendiente

Definir si la salida automática se crea:

1. Al guardar la entrada como borrador.
2. Al guardar la entrada aplicada.

Recomendación: crearla únicamente al **aplicar** la entrada y dentro de la misma transacción. Así no existen salidas de servicio asociadas a entradas que todavía no fueron confirmadas.
