# Un complemento de pago para varias facturas

Fecha: 2026-10-30. Implementación local del flujo SAT/FacturAPI; módulo de máquinas en pausa por instrucción del usuario.

## Necesidad y fuente oficial

Un cliente realiza un depósito que cubre dos, tres o más facturas y solicita un solo recibo. Se implementa un CFDI tipo P con un nodo de pago y varios documentos relacionados, cada uno con su importe aplicado, saldo y parcialidad. No se generan complementos independientes por factura.

Referencias consultadas:

- [FacturAPI: un pago que cubre varias facturas](https://docs.facturapi.io/docs/guides/invoices/pago/#un-pago-que-cubre-varias-facturas).
- [Referencia API: resumen de pago por factura](https://docs.facturapi.io/api/). La referencia documenta GET `/v2/invoices/{invoice_id}/payment-summary?amount=...`; sus resúmenes se incorporan a `related_documents`.
- [Referencia API general](https://docs.facturapi.io/api/): creación de facturas con `idempotency_key`, estado de emisión y estado de cancelación.

## Captura

En `/sat/complementos-pago?tab=pendientes` se pueden seleccionar facturas mediante casillas y abrir «Complementar facturas seleccionadas». También se conserva Agregar pago desde una factura y la captura anterior en el detalle de facturación.

El formulario muestra todas las facturas elegibles, permite seleccionar de 1 a 20 del mismo emisor, cliente local, RFC receptor, cliente FacturAPI y moneda, e indicar un importe por factura. Incluye fecha/forma de pago comunes y total automático del reparto. Selecciones iniciales incompatibles se excluyen con aviso para revisar antes de confirmar.

No se permite PUE, facturas canceladas/no timbradas, documentos distintos de ingreso, importes superiores al saldo disponible, facturas repetidas ni forma de pago 99. En divisa distinta de MXN exige el tipo de cambio del pago a pesos. No se mezclan monedas ni se implementan conversiones entre monedas de facturas distintas en este alcance.

Los impuestos no se eligen manualmente: antes de emitir se solicita el resumen de cada factura a FacturAPI y se valida UUID, monto, saldo y parcialidad. Si el saldo fiscal remoto no coincide con el local se detiene la emisión para revisar/sincronizar; no se inventan impuestos ni se continúa con saldos divergentes.

## Persistencia y saldos

- `sat_factura_pagos` es la cabecera del único recibo: UUID, ID remoto, fecha/forma/moneda, monto total, archivos y estado.
- `sat_factura_pago_documentos` contiene una fila por factura: importe aplicado, saldo anterior/insoluto, parcialidad y resumen fiscal remoto.
- `SatFactura::pagos()` relaciona ahora cabeceras mediante el desglose. Las sumas y el historial de una factura usan `importe_aplicado` de su documento, nunca el total del depósito.
- `sat_factura_id` de la cabecera se conserva como primera factura de referencia para compatibilidad y correo al mismo cliente. Listado, búsqueda, detalle, correo y resumen del cliente muestran los documentos relacionados, con un solo total/archivo/complemento.
- Los pagos internos se vinculan a la cabecera únicamente cuando los no ligados de la misma factura y fecha suman exactamente el importe aplicado. No se dividen automáticamente pagos internos ni se modifica su monto.

## Emisión, concurrencia y recuperación

Solicitud UUID por formulario, hash por actor/entradas normalizadas y bloqueo de solicitud; referencias e importes se comprueban bajo bloqueo de facturas en orden de ID. Cabecera/desglose se guardan antes de emitir, reservando los importes para impedir pagos simultáneos sobre el mismo saldo. La transacción local no permanece abierta durante llamadas al proveedor.

Estados que ocupan saldo: timbrado, registrado, en_proceso, pendiente_confirmacion y cancelacion_pendiente. Un error definitivo antes de emisión libera la reserva; un timeout/5xx/respuesta incierta conserva la reserva y el payload exacto.

Se envía una sola creación con todos los documentos y `idempotency_key` estable. Si existe ID remoto se consulta; si el resultado quedó incierto sin ID, se reenvía exactamente el payload con la misma clave. Un 202 o respuesta sin estado valid/UUID no se presenta como timbrado. El detalle permite «Consultar / reintentar el mismo complemento». La recuperación requiere el mismo usuario emisor de la solicitud, por el hash de actor; no se reutiliza la solicitud para otro reparto.

Cuando el timbrado está confirmado se guardan PDF/XML una sola vez por cabecera. Fallos de descarga no revierten ni convierten la emisión fiscal en error; las rutas de descarga intentan recuperarlos si faltan.

## Cancelación completa

Cancelar el REP afecta a todas sus facturas. Se consulta el estado remoto antes de cancelar para recuperar respuestas anteriores no guardadas. Solo un estado fiscal cancelado/aceptado libera saldos y desvincula pagos internos. Una cancelación pendiente/verificando conserva los saldos aplicados; rechazo/expiración mantiene el recibo vigente. El detalle permite consultar nuevamente la cancelación.

No se cancela una sola línea del REP. Para cambiar el reparto de un recibo ya emitido se requiere el procedimiento fiscal de cancelación/sustitución correspondiente, que no se ejecutó en esta implementación.

## Migración y despliegue

Nueva migración: `database/migrations/2026_10_30_120000_add_documentos_to_sat_factura_pagos.php`.

Aplicada únicamente en local, lote 153. Migró 15 complementos existentes a 15 documentos sin cambiar sus importes, estados, UUID ni archivos. Reconciliación de solo lectura: cero repartos duplicados y cero diferencias de importe entre cabecera y detalle en los registros anteriores. No se crearon nuevas solicitudes de emisión.

En producción, desplegar código y ejecutar la migración antes de habilitar la nueva captura:

```bash
php artisan migrate --path=database/migrations/2026_10_30_120000_add_documentos_to_sat_factura_pagos.php --force
```

No requiere comando de llenado adicional: la migración crea el desglose de los complementos anteriores. La reversión se bloquea si existen recibos con varias facturas para evitar pérdida de ese desglose. No se modificó producción ni se llamó a FacturAPI durante el desarrollo.

## Comprobaciones y límites

- Sintaxis PHP, registro de rutas y compilación/sintaxis de seis vistas Blade.
- Consultas locales de lectura para verificar relación, saldos y correspondencia de los 15 registros migrados.
- Sintaxis JavaScript, formato y UTF-8 de los archivos modificados.
- Sin pruebas de emisión/cancelación, sin correos y sin CFDI nuevos en Test o Live. El recorrido con selección de facturas y timbrado real queda pendiente del usuario.
- Bloqueos concurrentes y recuperación de cortes de red implementados pero pendientes de prueba funcional; no se declaran validados con una revisión estática.
- La regularización de saldos divergentes con FacturAPI, operaciones entre monedas distintas y recuperación por otro usuario/dispositivo quedan fuera de este alcance.
