# Plan de orden de servicio para mantenimientos

## Objetivo

Separar la captura inicial del mantenimiento de la orden de servicio, permitir ajustar los materiales realmente necesarios y habilitar el abastecimiento solo después de generar la orden.

## Regla base

Al crear la cabecera, el mantenimiento queda en `borrador`. En la edición, el botón debe decir `Crear orden de servicio`. Al ejecutarlo, se genera un folio, cambia el estado y se habilita el siguiente paso del flujo.

## Flujo objetivo

```text
Borrador
  -> Orden de servicio generada
  -> Materiales ajustados
  -> Solicitud de almacén o compras
  -> Material surtido
  -> Servicio ejecutado
  -> Reporte final
```

## Tareas y checkpoints

### Paso 1. Definir y preparar estados

- Confirmar estados de mantenimiento y abastecimiento.
- Agregar el estado de orden generada sin romper los estados existentes.
- Definir transiciones permitidas.

**Checkpoint:** completado. Se agregó `orden_generada` al catálogo de estados de mantenimiento y se habilitó su validación en edición.

### Paso 2. Generar folio de orden de servicio

- Crear folio único y recuperable.
- Asociarlo al mantenimiento.
- Evitar duplicar la orden si ya fue generada.

**Checkpoint:** completado. Se agregaron folio, fecha y usuario de generación, además de la ruta transaccional que cambia un borrador a `orden_generada`.

### Paso 3. Preparar datos de la orden

- Máquina o vehículo y código.
- Horómetro o kilometraje actual.
- Ubicación.
- Tipo de servicio.
- Mecánico.
- Fecha del último servicio equivalente.
- Descripción inicial.

**Checkpoint:** completado. La edición ahora prepara equipo, código, ubicación, lectura actual y último servicio comparable para reutilizarlos en la orden.

### Paso 4. Crear impresión de la orden

- Vista tamaño carta.
- Encabezado institucional.
- Folio, equipo, ubicación y datos operativos.
- Botones imprimir y regresar.

**Checkpoint:** completado. Se agregó endpoint y vista tamaño carta para imprimir la orden inicial con folio, equipo, lectura, ubicación, historial y materiales sugeridos.

### Paso 5. Ajustar el botón principal de edición

- Borrador: `Crear orden de servicio`.
- Orden generada: `Ajustar materiales`.
- Completado: `Imprimir reporte`.

**Checkpoint:** completado. El borrador muestra `Crear orden de servicio`, la orden muestra impresión y las solicitudes quedan bloqueadas hasta generar la orden.

### Paso 6. Crear snapshot de materiales

- Copiar la plantilla al servicio.
- Guardar producto, SKU, unidad y cantidad.
- Mantener congelada la versión usada por la orden.

**Checkpoint:** completado. La generación de la orden copia la plantilla a `mantenimiento_materiales`; abastecimiento e impresión usan ese snapshot cuando existe.

### Paso 7. Permitir quitar materiales

- Quitar partidas únicamente de la orden actual.
- No modificar la plantilla general.
- Registrar qué partidas fueron excluidas.

**Checkpoint:** pendiente.

### Paso 8. Recalcular abastecimientos

- Usar solo partidas activas.
- Mantener surtido directo, compras y parcial.
- Bloquear solicitudes antes de crear la orden.

**Checkpoint:** pendiente.

### Paso 9. Revisar flujo de emergencias

- Mantener captura manual.
- Permitir conceptos manuales.
- Permitir vincular productos reales cuando requieran abastecimiento.

**Checkpoint:** pendiente.

### Paso 10. Mostrar historial operativo

- Último servicio preventivo del mismo tipo.
- Última emergencia relevante.
- Horómetro o kilometraje actual.
- Ubicación actual.

**Checkpoint:** pendiente.

### Paso 11. Aplicar candados

- No solicitar materiales antes de generar la orden.
- No generar dos órdenes para el mismo mantenimiento.
- No cambiar materiales después de generar abastecimientos sin una acción controlada.

**Checkpoint:** pendiente.

### Paso 12. Integrar navegación e impresión

- Acceso desde la edición.
- Acceso desde la lista de servicios.
- Regreso a la máquina o vehículo de origen.
- Recuperación por folio.

**Checkpoint:** pendiente.

### Paso 13. Ejecutar pruebas del flujo

- Programado completo.
- Programado con partidas removidas.
- Material faltante.
- Surtido parcial.
- Emergencia.
- Servicio terminado y reporte final.

**Checkpoint:** pendiente.

## Hallazgos iniciales

- Ya existen estados de mantenimiento y solicitudes de abastecimiento.
- Ya existen plantillas de materiales por tipo y máquina.
- Ya existe cálculo de existencias y soporte de abastecimiento parcial.
- Ya existe impresión del reporte final.
- Todavía no existe una orden de servicio independiente ni un snapshot de materiales por mantenimiento.
- La captura de emergencia permite detalles manuales, pero debe revisarse su integración con inventario.

## Registro de ejecución

| Paso | Fecha | Resultado | Hallazgos |
|---|---|---|---|
| 1 | 2026-10-21 | Completado | Se agregó el estado `orden_generada` y se aplicó la migración localmente. |
| 2 | 2026-10-21 | Completado | Folio `OSM-aa-######` persistido y generación idempotente desde borrador. |
| 3 | 2026-10-21 | Completado | Se consulta el último preventivo del mismo tipo o la última emergencia del mismo activo. |
| 4 | 2026-10-21 | Completado | Vista `orden-servicio-print` y ruta recuperable por mantenimiento. |
| 5 | 2026-10-21 | Completado | UI conectada al estado y al folio de la orden. |
| 6 | 2026-10-21 | Completado | Snapshot persistido por servicio sin alterar la plantilla maestra; abastecimiento e impresión usan el snapshot. Migración, caché y sintaxis validadas. |
| 7 | 2026-10-21 | Completado | Se puede incluir/excluir material del snapshot de la orden con motivo; el cambio se bloquea al existir abastecimientos. También se corrigió la inyección del servicio de horómetro en edición. |
| 8 | 2026-10-21 | Completado | Solicitudes de almacén/compra bloqueadas hasta contar con orden generada; también se bloquean en servicios completados, incluyendo llamadas directas al backend. |
| 9 | 2026-10-21 | Completado | Emergencias mantienen captura manual sin tipo preventivo; sus detalles ahora se muestran y pueden actualizarse desde edición, sin generar snapshot preventivo. |
| 10 | 2026-10-21 | Completado | La edición ahora muestra máquina/vehículo, código, ubicación, lectura actual y último servicio equivalente. |
| 11 | 2026-10-21 | Completado | Se bloquearon cambios estructurales y regreso a borrador cuando existen abastecimientos; los servicios completados no pueden reabrirse desde edición. |
| 12 | 2026-10-21 | Completado | La orden imprimible queda accesible desde edición y desde el tab Servicios de la máquina, abriendo en nueva pestaña; el reporte final conserva su acceso independiente. |
| 13 | 2026-10-21 | Completado con hallazgo | Sintaxis, rutas, migración y caché validadas. `php artisan test` pasó las pruebas disponibles salvo `MaquinaRegistroAccessTest`, que falla porque la base de testing no tiene el rol `super-admin` para el guard `web`. |
