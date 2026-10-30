# Almacén fijo por catálogo

Fecha: 2026-10-29.

## Hallazgo

ENT-000031 recibió el producto AG-1915 en Giralda; SAL-000030 estaba en borrador para Huentitán. Las existencias se consultan por producto y almacén, por lo que esa salida no podía usar las 60 unidades de Giralda.

## Regla y alcance

- `/productos` corresponde a Giralda. El formulario general de entradas/salidas muestra el almacén fijo sin selector y el servidor determina su ID al guardar, ignorando el valor enviado por el cliente. Si no hay Giralda activo, se rechaza la captura sin usar otro almacén.
- `/huentitan/productos` usa las entradas/salidas propias de Huentitán, cuyos controladores ya determinan el almacén sin tomarlo del formulario. Se agregó acceso directo a nueva salida para quien tenga el permiso existente.
- La tabla del catálogo Huentitán usa la cabecera azul de la app con texto blanco.
- Otros tipos de documentos generales conservan su flujo. Los documentos ya guardados no cambian de almacén automáticamente; SAL-000030 necesita corregirse o sustituirse con una salida del almacén correcto.

## Checkpoint

Tres pruebas en SQLite temporal, 13 comprobaciones: formularios limitados a Giralda, guardado de entradas/salidas con ID manipulado, ausente o inexistente, y rechazo si Giralda está inactivo. Sintaxis PHP y UTF-8 verificados. No requiere migración ni ajuste de existencias reales.

## Entradas relacionadas en el reporte de mantenimiento

El reporte del servicio 16 omitía ENT-000031 porque la entrada tenía la OC en sus partidas y no en la cabecera. La corrección del almacén de SAL-000030 con Tinker no causó esta omisión.

La consulta del reporte ahora contempla documentos ligados directamente al abastecimiento, a la OC en cabecera, a la OC en sus partidas o a una partida de esa OC. Usa consultas de existencia para no repetir un documento con varias partidas. Conserva el filtro del servicio y la exclusión de documentos eliminados.

Verificación: prueba de regresión SQLite correcta (cuatro comprobaciones) y renderizado del reporte real, confirmando que contiene ENT-000031 y SAL-000030. No se modificaron documentos, existencias ni costos.
