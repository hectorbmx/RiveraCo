# Recepción directa de compras en obra

## Ejecución acordada

Ejecutar los 21 puntos en orden. Verificar cada punto y esperar 10 minutos antes de comenzar el siguiente. Las pausas son solicitadas por el usuario; no ejecutar trabajo del siguiente punto durante ellas. Trabajar en local; el último punto prepara el despliegue, sin publicar ni cambiar producción.

## Reglas confirmadas por el usuario

- Compras confirma y aplica la recepción después de verificar la entrega con las personas correspondientes.
- Costo de la partida de la orden de compra, proporcional a lo recibido.
- Obra, fecha, receptor físico y remisión o fotografía obligatorios. No exigir factura para recibir.
- Destino fijado en la OC; no cambiarlo al recibir.
- Una recepción tiene un único destino y no reparte cantidades entre almacén y obras.
- Recepciones parciales permitidas, conservando el mismo destino.
- Correcciones con permiso especial y trazabilidad; no editar movimientos aplicados silenciosamente.
- Una captura aplica conjuntamente recepción, entrada documental y salida a obra.
- El material directo no queda disponible para otras solicitudes.
- Compras normales pueden funcionar sin requisición. La vinculación con requisiciones debe respetar su autorización y saldo cuando ese flujo esté disponible.
- Conservar separados los gastos de caja chica capturados desde obra y almacén.

## Puntos y avance

1. [x] Revisar recepción actual de OC en Giralda y Huentitán.
2. [x] Definir y documentar la regla de costo para entrega directa.
3. [x] Definir permisos de confirmación y corrección.
4. [x] Definir datos obligatorios y validación de evidencias.
5. [x] Diseñar e implementar relaciones persistentes entre documentos y partidas.
6. [x] Incorporar lugar de entrega previsto y obra fija en la OC.
7. [x] Incorporar captura de recepción directa en obra.
8. [x] Cargar partidas con saldo pendiente de recepción.
9. [x] Validar cantidades, origen autorizado y destino inmutable.
10. [x] Implementar aplicación conjunta y atómica.
11. [x] Evitar disponibilidad temporal y procesamiento duplicado.
12. [x] Actualizar avance de recepción de la OC.
13. [x] Validar recepciones parciales; no permitir destinos mixtos según decisión posterior del usuario.
14. [x] Registrar cantidad y costo entregados a obra.
15. [x] Conectar saldo autorizado de requisición cuando corresponda, sin dar por existente su flujo aún no implementado.
16. [x] Mostrar trazabilidad en consultas de OC, recepción y obra.
17. [x] Crear comprobante de recepción directa y evidencias.
18. [x] Implementar corrección/reversión conjunta con permiso especial.
19. [ ] Conectar devoluciones de sobrantes con la entrega original.
20. [ ] Ejecutar pruebas completas de integración y regresión.
21. [ ] Preparar guía, migraciones y procedimiento de despliegue.

La lista original proponía entregas mixtas en el punto 13. La decisión posterior del usuario las excluye; este punto verifica el destino único y las entregas parciales.

## Punto 1: revisión del comportamiento actual

### Giralda

- Controlador: `app/Http/Controllers/Inventario/InventarioDocumentoController.php`.
- Servicio: `app/Services/Inventario/InventarioDocumentoService.php`.
- Documentos y partidas: `InventarioDocumento`, `InventarioDocumentoDetalle`.
- El almacén Giralda se determina en servidor para entradas y salidas.
- El servicio aplica cada documento en una transacción. Bloquea las OCs y sus partidas relacionadas, valida saldo pendiente y actualiza `cantidad_recibida` y `estado_recepcion`.
- Entrada normal incrementa stock y valor; salida normal toma costo promedio del almacén.
- Reutilizable: trazabilidad de partidas de compra, saldo de recepción, kardex y seguimiento de abastecimiento de servicios.
- No basta con aplicar entrada y salida normales consecutivas: la salida tomaría el promedio modificado, sin garantizar el costo de OC ni conservar el valor previo de las existencias físicas.

### Huentitán

- Controlador: `app/Http/Controllers/Inventario/HuentitanEntradaController.php`.
- Documentos específicos: `HuentitanEntrada`, `HuentitanEntradaDetalle`; salidas mediante `HuentitanSalidaController`.
- La captura exige OC autorizada del área correspondiente y saldo pendiente. Guarda borrador con costo obtenido de `OrdenCompraDetalle::precio_unitario`.
- La aplicación bloquea entrada, stock y partidas de OC; registra movimiento de entrada y actualiza cantidades recibidas y promedio ponderado.
- Las salidas a obra son documentos separados y usan costo promedio.
- `cancelar` de entradas Huentitán es actualmente un marcador sin reversión. No apoyarse en ese método como si ya corrigiera una recepción aplicada.
- Las rutas existentes usan permisos específicos de entradas/salidas Huentitán. Es necesario integrar permisos de Compras y corrección para el nuevo flujo.

### Compra y datos faltantes

- `OrdenCompra` ya contiene `obra_id`, estado, moneda, tipo de cambio, importes y relaciones con partidas.
- No se encontró una modalidad persistida de recepción directa a obra en los modelos de OC y recepción revisados.
- Faltan vínculo de la pareja documental, identificación del receptor físico, remisión/evidencia y operación única protegida contra repetición.
- La recepción directa debe atender el destino de la OC y conservar las reglas de compra autorizada; no depender únicamente de valores enviados por el formulario.
- La documentación de Huentitán ya contempla entrada/salida documental para productos enviados directamente a obra; aún requiere el mecanismo operativo único.

### Verificación del punto 1

- Revisión de controladores, servicio, modelos, validaciones y rutas existentes, sin cambiar su comportamiento.
- Prueba de base: `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --do-not-cache-result tests/Feature/InventarioAlmacenGiraldaTest.php`.
- Resultado: 3 pruebas, 13 comprobaciones, todas correctas; SQLite en memoria, sin modificar la base de desarrollo.
- El trabajo funcional del punto 2 comienza después de completar la pausa de 10 minutos.

## Punto 2: regla de costo definida

- Fuente de costo: partida de la OC autorizada. No aceptar un costo capturado libremente en la recepción directa.
- Usar el importe neto de la partida dividido entre la cantidad ordenada, conservando los descuentos ya registrados en la OC. Si una partida legacy no tiene importe informado, resolver su precio y descuentos desde los campos de OC; no usar el precio promedio del almacén como sustituto.
- Conservar fotografía de cantidad ordenada, precio unitario, descuentos, importe neto, moneda y tipo de cambio de la OC al confirmar la recepción. Una variación posterior en la OC no debe reescribir el costo histórico de una entrega aplicada.
- Conservar el importe de recepción en moneda de OC. Para el kardex y costo de obra en moneda base MXN, aplicar el tipo de cambio de la partida, o el de cabecera si la partida no tiene uno. Rechazar la aplicación en moneda extranjera sin tipo de cambio positivo; no asumir 1.
- IVA, retenciones y otros cargos de cabecera se conservan como información de compra. No agregarlos de nuevo al costo de material ni inventar un reparto automático; usar el importe neto de la partida para este flujo, consistente con el control existente de inventario basado en precio de material.
- La entrada y salida documentales deben tener la misma cantidad y el mismo costo base de compra. El efecto conjunto sobre cantidad, valor total, costo promedio y reserva preexistentes del almacén debe ser cero.
- El movimiento de salida es el cargo de entrega a obra. No sumar también la entrada o el total de la OC al mismo indicador de material entregado.
- Guardar precisión suficiente durante el cálculo; redondear solo al persistir según las columnas. Conciliar el residuo de centavos al completar la última recepción de una partida para que la suma de recepciones no exceda el importe neto autorizado.
- La devolución debe recuperar el costo histórico de la entrega directa de origen, no el promedio vigente del almacén.

### Ejemplos de comprobación

1. Existencia física: 10 piezas, valor $1,000, promedio $100. Compra directa: 5 piezas a $150. Cargo a obra: $750. Después de entrada/salida documental, almacén conserva 10 piezas, $1,000 y promedio $100.
2. OC de 10 piezas, precio $100, descuento $100, importe neto $900: costo unitario neto $90. Recibir 6 carga $540; quedan 4 por recibir y $360 netos pendientes.
3. OC en USD con importe neto de 100 USD para 10 piezas y tipo de cambio 20 MXN/USD: recibir 4 corresponde a 40 USD y cargo base de 800 MXN.

### Verificación del punto 2

Regla contrastada con `OrdenCompraDetalle`, `OrdenCompraTotalesService`, la valuación actual de Giralda y los costos usados por Huentitán. Comprobaciones numéricas ejecutadas separadamente; no se modificaron inventario, costos ni documentos existentes. La implementación de cálculos y sus pruebas de regresión corresponde a los puntos 10 y 14.

## Punto 3: permisos definidos

Permisos dedicados, con guard `web`, siguiendo el catálogo actual de permisos por acción:

| Permiso | Alcance |
| --- | --- |
| `ordenes_compra.direct_receipts.view.access` | Consultar recepción directa, partidas, evidencias, movimientos y comprobante. |
| `ordenes_compra.direct_receipts.confirm.access` | Compras captura y confirma la recepción física; aplica la operación completa. |
| `ordenes_compra.direct_receipts.reverse.access` | Permiso especial para corregir mediante reversión conjunta con motivo obligatorio. |

- La responsabilidad de Compras se representa mediante asignación explícita de permisos; no inferirla por el nombre del usuario ni por el permiso de crear/autorización de OC.
- No habilitar confirmación directa solo por tener `inventario.documentos.aplicar.access`, `huentitan.entradas.apply` o `ordenes_compra.authorize.access`.
- Confirmar no concede permiso de revertir. El permiso especial de reversión se asigna por separado.
- Respetar permisos heredados, asignaciones individuales y denegaciones de `user_permission_overrides` usando `can`/Gate; no comprobar exclusivamente `hasPermissionTo` ni el rol.
- Validar permiso en backend y visibilidad de botones en la vista. La ausencia de botón no sustituye el control en servidor.
- Registrar usuario de confirmación y usuario/motivo/fecha de reversión.
- Preparar un seeder específico e idempotente para crear solo estos permisos. No ejecutar el seeder global de roles: contiene `syncPermissions` y podría reemplazar configuraciones existentes.
- No asignar automáticamente los permisos nuevos a personas o roles. La asignación al personal de Compras y al responsable de correcciones se hará desde Usuarios/Permisos en cada ambiente.

### Verificación del punto 3

Se revisaron permisos locales relacionados con compras e inventario, `AuthServiceProvider`, `User::hasDeniedPermission`, el patrón `authorizeAny` de compras y el seeder global. Los tres permisos propuestos no coinciden con claves existentes y separan consulta, confirmación y reversión. Este punto define el contrato; el alta idempotente y las comprobaciones de las acciones se incorporan al implementar el nuevo módulo.

## Punto 4: contrato de captura y evidencia

Implementado `StoreCompraRecepcionDirectaRequest`, todavía sin conectar a las rutas existentes:

- UUID de operación obligatorio para permitir protección posterior contra repetición.
- Fecha real de recepción obligatoria, sin fecha futura.
- Nombre del receptor físico obligatorio; el usuario que confirma será Compras y se tomará de la sesión.
- Número/referencia de remisión opcional y observaciones opcionales.
- Al menos un archivo de remisión o fotografía obligatorio, hasta cinco archivos PDF/JPG/PNG/WebP de hasta 10 MB cada uno. No exigir XML o factura.
- Una o más partidas; identificadores positivos y sin duplicados; cantidad recibida positiva con hasta tres decimales.
- Obra, almacén, destino y precios del cliente prohibidos: se resolverán desde la OC en backend, no desde campos ocultos modificables.
- La autorización del request requiere específicamente `ordenes_compra.direct_receipts.confirm.access` mediante `can`.
- La comprobación de pertenencia de la partida a la OC, OC autorizada y saldo disponible se implementa en los puntos 8 y 9 bajo bloqueo transaccional en el punto 10.
- Los archivos se almacenarán asociados a la recepción y consultables con permiso del módulo; no enlazar archivos sensibles mediante una URL pública independiente del control de acceso.

### Verificación del punto 4

`CompraRecepcionDirectaRequestTest`: 6 pruebas y 16 comprobaciones correctas. Cubren recepción sin factura con evidencia, evidencia/receptor obligatorios, destino/costo no alterables, partidas duplicadas, cantidad cero, fecha futura y permiso explícito de confirmación. Validación de UTF-8 correcta. Ninguna recepción real ni asignación de permisos fue creada.

## Punto 5: estructura persistente y relaciones

Implementadas y aplicadas únicamente en local las migraciones `2026_11_05_205300` y `2026_11_05_205301`:

- `compra_recepciones_directas`: UUID único, folio único, OC, obra y almacén fijos, fecha, receptor, remisión, observaciones, moneda/tipo de cambio/totales, documentos de entrada y salida, confirmación y reversión con responsables.
- `compra_recepcion_directa_detalles`: partida de OC, producto, vínculo opcional con partida de requisición, cantidad y costo históricos. Una partida de OC no puede repetirse dentro de una recepción.
- `compra_recepcion_directa_archivos`: rutas privadas, nombre original, MIME, tamaño, hash y usuario que adjuntó.
- Entrada y salida vinculadas mediante `inventario_documentos.compra_recepcion_directa_id`; cada documento puede ser referencia principal de una sola recepción.
- Movimientos identificados expresamente por `inventario_movimientos.compra_recepcion_directa_id`, para evitar confundir los identificadores de documentos genéricos con entradas/salidas específicas de Huentitán al consultar kardex.
- Modelos y relaciones de OC, recepción, partidas, evidencias, documentos y movimientos. La estructura común permite operar con Giralda o Huentitán sin obligar al usuario a capturar ambos documentos.
- Seeder específico `CompraRecepcionDirectaPermisosSeeder`: crea tres permisos de forma idempotente, sin asignarlos ni reemplazar roles o permisos existentes. Ejecutado en local.
- La recepción permanece en borrador hasta su aplicación atómica del punto 10. Ninguna entrada/salida ni recepción real fue creada en este punto.

### Verificación del punto 5

- Pruebas de esquema en SQLite temporal: relaciones completas, UUID único en base de datos, costos decimales y alta idempotente de permisos sin modificar asignaciones.
- Junto con las validaciones del punto 4: 9 pruebas, 28 comprobaciones correctas.
- Regresión conjunta con inventario Giralda: 12 pruebas, 41 comprobaciones correctas.
- Las dos migraciones aditivas se ejecutaron correctamente contra MySQL local, lo que valida también los nombres de índices y claves foráneas del motor real.
- Producción no modificada. La modalidad de entrega y el almacén fijo en OC se incorporan en el punto 6.

## Punto 6: destino de la orden de compra

- Migración `2026_11_05_210800`: `ordenes_compra.modalidad_entrega` (`almacen` por defecto, `obra_directa` para el nuevo flujo) y `almacen_destino_id` opcional para compras normales y obligatorio para entrega directa.
- Las 203 OC históricas locales conservaron la modalidad normal de almacén; no se reinterpretaron entregas antiguas ni se generaron movimientos.
- Crear/editar OC presenta lugar de entrega y almacén responsable mediante un partial compartido. La obra sigue siendo la obra única de la OC.
- `OrdenCompraEntregaService` valida obra activa y almacén activo, y compatibilidad con área cuando el almacén está ligado a una. La captura legacy sin nuevos campos mantiene su comportamiento normal.
- El modelo bloquea cambios de modalidad, almacén, obra o área si existen recepciones directas o cantidades recibidas en partidas. El bloqueo no depende de botones ni del formulario.
- Se conserva el bloqueo existente para OC autorizadas/verificadas/canceladas y se verifica nuevamente bajo bloqueo de la OC durante actualización de encabezado.
- El formulario de Huentitán muestra origen Obra cuando se elige entrega directa; no oculta ni limpia accidentalmente la obra como sucede con compras para stock/fabricación.
- No se agregó el flujo de aplicación de recepción en este punto; se incorpora a partir del punto 7.

### Verificación del punto 6

- `OrdenCompraEntregaTest`: valida compra normal, destino directo válido, obra terminada, almacén ausente/inactivo/de otra área y destino inmutable después de recepción directa o normal.
- Pruebas conjuntas con esquema, request y compras de mantenimiento: 19 pruebas, 55 comprobaciones correctas.
- Migración aditiva aplicada correctamente en MySQL local. Compilación Blade de crear, editar y partial correcta; comprobación UTF-8 y diff correctos.

## Punto 7: pantalla de captura

- Ruta GET `ordenes_compra/{ordenCompra}/recepciones-directas/create`, con sesión y permiso de confirmación; el controlador vuelve a comprobar el permiso.
- Solo admite OC autorizada marcada `obra_directa`. El enlace aparece en la consulta/edición de esa OC para usuarios autorizados.
- Muestra OC, proveedor, obra, almacén responsable y moneda como información fija.
- Captura UUID, fecha, receptor físico, referencia de remisión, cantidades por partida, archivos y observaciones. No envía obra, almacén o costos editables.
- Pantalla preparada sin POST ni confirmación habilitada hasta implementar la aplicación conjunta en el punto 10. Esto evita exponer una operación incompleta durante el desarrollo por puntos.
- El punto 8 limita las partidas cargadas al saldo pendiente y permite seleccionar solo lo recibido; el punto 9 consolida validaciones en backend.

### Verificación del punto 7

Pruebas de acceso y contexto junto con validación del request: 9 pruebas, 24 comprobaciones correctas. La ruta aparece correctamente en `route:list`; PHP, compilación Blade y UTF-8 correctos. No se confirmó ninguna recepción.

## Punto 8: partidas pendientes y selección parcial

- `CompraRecepcionDirectaPartidasService` calcula cantidad ordenada menos recibida a tres decimales; excluye partidas completas o históricamente excedidas sin modificar la OC.
- La pantalla muestra ordenado, recibido y pendiente por partida.
- Compras selecciona expresamente lo recibido; únicamente las partidas marcadas envían identificador y cantidad. La cantidad inicial sugerida es el saldo, editable a una cantidad menor positiva.
- Se restaura la selección y cantidad después de errores de captura.
- Las partidas pendientes sin producto ligado se muestran con aviso y sin selección habilitada; no se ocultan como si ya estuvieran recibidas.
- Los máximos HTML ayudan a capturar; la verificación definitiva de saldo se implementa en el punto 9 y se repite bajo bloqueo en el punto 10.

### Verificación del punto 8

11 pruebas y 30 comprobaciones correctas entre cálculo de partidas, acceso a captura y request. Incluyen recepción parcial, completa, saldo histórico negativo y saldo mínimo de 0.001. Codificación UTF-8 correcta; las cantidades originales de la OC permanecen intactas.

## Punto 9: validación de negocio en servidor

- `CompraRecepcionDirectaValidacionService` exige OC autorizada y modalidad directa, obra activa y almacén activo compatible con área.
- Rechaza partidas ajenas a la OC, repetidas, sin producto activo o con cantidades no positivas/no representables o superiores al saldo pendiente.
- Respeta separación de catálogo: SKU `HUE-` para Huentitán y catálogo general para el resto del flujo normal.
- Calcula cantidad de inventario en unidad base usando únicamente la conversión de compra configurada en producto. Si OC y unidad base difieren sin conversión válida, rechaza la aplicación en lugar de mezclar cajas con piezas o litros con envases.
- El resultado contiene la partida original, cantidad de compra, factor y cantidad/unidad de inventario; no modifica cantidades recibidas ni existencias.
- Se integra dentro de la operación transaccional del punto 10 y se ejecuta después de bloquear OC y partidas para comprobar el saldo vigente.

### Verificación del punto 9

15 pruebas y 43 comprobaciones correctas junto con captura, partidas y request. Cubren partida de otra OC, exceso de saldo, duplicados, OC no autorizada, obra cerrada, almacén inactivo, catálogo incorrecto, producto inactivo y conversión de 2 cajas a 24 piezas. Codificación UTF-8 correcta.

## Punto 10: confirmación conjunta

- Implementado `CompraRecepcionDirectaService::confirmar`, con comprobación explícita del permiso de Compras y validación de captura.
- Una transacción bloquea OC y partidas, comprueba saldo vigente, registra recepción/evidencias y genera pareja de documentos aplicados con sus partidas y movimientos.
- Costos desde OC mediante `CompraRecepcionDirectaCostoService`, con importe neto, moneda/tipo de cambio y conversión a unidad base.
- Migración `2026_11_05_220000`: fotografía de conversión/cantidad/unidad base y `inventario_movimientos.importe_costo` para conservar el importe exacto frente al redondeo del unitario a cuatro decimales.
- El stock físico, reservado, valor y promedio existentes no se modifican. Si no había saldo materializado se crea una fila en cero; los movimientos documentales se identifican con recepción directa y no generan disponibilidad para otras órdenes.
- La recepción actualiza cantidades recibidas dentro de la misma transacción; se guarda como aplicada solo al completar ambos documentos. El punto 12 consolida estados y casos parciales.
- Archivos guardados en disco privado. Si falla la operación, rollback de registros y eliminación de los archivos creados por ese intento.
- Se habilitó POST de captura con permiso de confirmación y vínculo a la OC. Todavía no se asignaron permisos a usuarios reales.
- Se impide aplicar o cancelar individualmente documentos de la pareja desde el servicio normal de inventario.
- Se impide recibir una OC directa como entrada física normal, tanto en Giralda como en Huentitán. El flujo de compras sin modalidad directa conserva su funcionamiento.
- El UUID ya es único y un reintento se rechaza sin duplicar movimientos. El punto 11 incorpora recuperación idempotente del mismo resultado y comprobación de solicitud repetida.

### Verificación del punto 10

35 pruebas y 115 comprobaciones correctas entre aplicación, captura, request, esquema, destino, partidas, validación, inventario Giralda y compras de mantenimiento. Se simuló fallo al insertar la salida: recepción, documentos, movimientos, cantidades y archivos nuevos se revirtieron; saldo físico y reserva quedaron intactos. Migración aplicada en MySQL local, rutas/sintaxis/UTF-8/diff correctos. Sin recepciones reales ni cambios en producción.

## Punto 11: reintentos y disponibilidad

- Migración `2026_11_05_221800` aplicada en local: hash de la solicitud original.
- El mismo UUID, usuario de Compras, OC, partidas/cantidades, fecha/receptor/remisión/observaciones y contenido de evidencias devuelve la recepción aplicada existente; no inserta archivos, documentos o movimientos ni incrementa cantidades otra vez.
- Una solicitud diferente con el mismo UUID genera conflicto 409. Una recepción revertida no se reactiva mediante reintento.
- Hash normalizado por identificador de partida, cantidad a tres decimales y contenido/tamaño/MIME de archivos, sin depender del orden de selección o del nombre local del archivo.
- Bloqueo de OC antes de comprobar UUID, unicidad en base y recuperación de colisión después de rollback. No usar un simple botón deshabilitado como única protección.
- El formulario deshabilita confirmación mientras envía y exige al menos una partida seleccionada; al volver con el navegador restaura el estado de captura.
- La entrega directa conserva el stock físico/reserva/valor/promedio previos. Cada entrega nueva necesita UUID propio y solo puede usar el saldo aún pendiente de la OC.

### Verificación del punto 11

8 pruebas y 34 comprobaciones correctas entre aplicación e idempotencia. Cubren reintento idéntico sin duplicación, cambio de cantidad/OC rechazado, recepción revertida no reactivable, entregas distintas con saldo, rollback y bloqueo de entrada normal. La unicidad se comprobó además en las pruebas de esquema; la aplicación usa bloqueos de filas en MySQL. Migración local y UTF-8 correctos.

## Punto 12: estados de recepción

- `OrdenCompraRecepcionEstadoService` centraliza estados de partidas y cabecera en una transacción con bloqueo de OC y partidas.
- Sin cantidades recibidas: pendiente. Con saldo en alguna partida: parcial. Solo cuando todas las partidas están cubiertas: recibida.
- Se determina cobertura por partida, sin compensar un exceso histórico de un producto con el faltante de otro ni sumar unidades distintas como si fueran equivalentes.
- No modifica la autorización, cantidades compradas o recibidas; únicamente sincroniza los estados correspondientes.
- La recepción directa y los flujos normales de Giralda/Huentitán reutilizan el mismo cálculo.
- Recalcular después de una reversión permite volver a parcial o pendiente según los saldos reales; la reversión se incorpora en el punto 18.

### Verificación del punto 12

19 pruebas y 73 comprobaciones correctas junto con aplicación, reintentos, inventario Giralda y compras de servicios. Casos nuevos: recepción 4+6 de 10 pasa de parcial a recibida, autorización permanece intacta, exceso histórico de una partida no oculta faltantes en otra y saldo restablecido a cero vuelve a pendiente. UTF-8 correcto.

## Punto 13: entregas parciales con destino único

- La captura y el servicio admiten varias recepciones por OC y varias partidas seleccionadas por entrega, sin exigir recibir todo lo pendiente.
- El destino procede exclusivamente de la OC. Se prohíben también destinos alternativos por partida y listas de destinos, además de los campos generales ya protegidos.
- Una partida completa no admite recepciones nuevas con otro UUID; una partida parcialmente recibida conserva únicamente su saldo.
- No se implementan entregas mixtas entre almacén y obras ni se reparte una recepción. Esta restricción sustituye expresamente la propuesta inicial de entregas mixtas.

### Verificación del punto 13

12 pruebas y 43 comprobaciones correctas con estados y request. Una OC de dos materiales se recibió en tres entregas, conservó una obra/almacén, acumuló exactamente las cantidades 10 y 5 y costo de $1,900; quedó recibida solo al cubrir ambas partidas. Intentos de dividir destinos o recibir de más rechazados sin nuevos documentos. UTF-8 correcto.

## Punto 14: cantidades y costo neto entregado a obra

- La pestaña de órdenes de compra de la obra incluye un resumen independiente de material recibido directamente, protegido por el permiso de consulta de recepciones directas. No agrega el total de OC ni la entrada al cargo por entrega.
- `CompraRecepcionDirectaObraService` contabiliza la salida aplicada vinculada y deja preparado el descuento de entradas por devolución. Excluye recepciones revertidas, documentos no aplicados/eliminados y movimientos de otras obras.
- Migración `2026_11_06_000100`: vínculo explícito de cada movimiento y detalle documental con su partida de recepción. Evita multiplicar importes cuando un producto aparece en dos partidas diferentes.
- Costos por recepción calculados como diferencia del importe acumulado redondeado menos lo ya registrado; la suma de entregas parciales concilia con el importe neto de OC y su conversión a MXN.
- Se comprueba que cantidades recibidas y recepciones aplicadas coincidan. Si cambia el costo neto, cantidad ordenada, tipo de cambio o conversión después de recibir, se detiene la entrega nueva para revisión; no se revalúa silenciosamente una entrega histórica.
- El monto mostrado representa material entregado a cargo de obra, no consumo final confirmado. Las devoluciones del punto 19 ajustarán ese saldo.
- No se modifican compras históricas, caja chica ni existencias físicas. Antes de aplicar la migración local se confirmó que no había recepciones directas reales que requirieran vinculación retroactiva.

### Verificación del punto 14

16 pruebas y 64 comprobaciones correctas con aplicación, entregas parciales y reintentos. Cubren cargo único de $600, mismo producto en dos partidas sin duplicación, costo $100 repartido en tres entregas con conciliación de centavos, cambio de costo bloqueado, USD convertido al tipo de cambio de compra y valor físico de almacén intacto. Render del resumen correcto; migración aplicada en MySQL local, PHP/UTF-8/diff correctos. Producción sin cambios.

## Punto 15: saldo autorizado de requisición

- Migración `2026_11_06_010000`: vínculo opcional de la partida de OC con la partida de requisición. Se conserva también en la fotografía de cada recepción directa.
- Al agregar material a una OC de entrega directa puede seleccionarse una partida autorizada de la misma obra; el servidor exige mismo producto y unidad base compatible con la conversión de compra. Las compras sin requisición siguen permitidas.
- Se exige autorización registrada por un usuario diferente del solicitante, cabecera en estado surtible y cantidad autorizada positiva. Una requisición pendiente, cancelada o rechazada no habilita entregas. No se crea ni simula el mecanismo de autorización pendiente.
- `CompraRequisicionSaldoService` bloquea cabeceras y partidas en orden estable dentro de la transacción de recepción. Agrupa todas las líneas que apuntan a la misma partida, comprueba el saldo autorizado menos lo ya surtido e incrementa `cantidad_surtida` en unidades base. Actualiza el estado de la partida; no declara recibida la cabecera ni altera su autorización.
- El saldo es común entre OCs; no se puede gastar dos veces desde otra orden o desde dos partidas de la misma entrega. Un reintento idempotente no lo descuenta otra vez y un fallo posterior revierte también este avance.
- El vínculo procede de la OC; no puede escogerse otra requisición al recibir. Una partida vinculada con recepciones no puede editarse. La creación/edición bloquea y vuelve a comprobar la OC para evitar cambios concurrentes con su autorización.
- Esta conexión cubre entregas directas a obra. No crea reservas de compra ni de inventario, ni conecta todavía el surtido físico normal, devoluciones o reversión de recepciones. Los puntos 16 a 21 permanecen pendientes por instrucción de ejecutar únicamente el 15.

### Verificación del punto 15

49 pruebas y 171 comprobaciones correctas entre recepción directa, costos, parciales, idempotencia, validación, esquema, destino y estados de OC. Siete pruebas nuevas cubren saldo compartido, autorización por otro usuario, incompatibilidades de origen, conversión, rollback y exceso al vincular. Migración aplicada en MySQL local; compilación Blade y revisión UTF-8 correctas. Producción sin cambios ni permisos asignados.

## Punto 16: consultas y trazabilidad

- La OC de entrega directa muestra avance por partida (ordenado, recibido y pendiente, en unidad de compra), origen de requisición cuando existe y sus cantidades actuales autorizadas, surtidas y pendientes en unidad propia.
- Historial paginado de recepciones en OC y pestaña de compras de obra, con enlaces al detalle de entrega y OC, fecha, estado, receptor, usuario que confirmó, folios de entrada/salida e importe registrado MXN. La paginación conserva la pestaña seleccionada.
- Nueva consulta GET `ordenes_compra/{ordenCompra}/recepciones-directas/{recepcion}` protegida por el permiso existente de consulta. Comprueba la relación con la OC y devuelve 404 ante una recepción de otra orden.
- El detalle conserva cantidades, unidades, conversión, costos e importe históricos de cada partida entregada. Identifica su partida de compra y requisición y distingue ese registro del avance actual de la OC y del saldo actual de requisición.
- Se muestran obra, almacén responsable, confirmación, remisión, observaciones y documentos de la pareja. Las compras sin requisición indican expresamente que no hay vínculo; no se deduce un origen por coincidencia de producto.
- El resumen neto de obra enlaza cada entrega a su consulta. El historial incluye estados revertidos para mantener trazabilidad, etiquetados como importes registrados que no integran el neto vigente. Se conserva el cálculo del punto 14 sin sumar entradas, compras ni recepciones revertidas.
- Consultas limitadas por obra/OC, con relaciones precargadas y diez recepciones por página. Las pantallas que integran el historial también exigen el permiso de consulta. No se crean permisos ni se asignan a usuarios.
- No requiere migración ni cambia saldos, costos o existencias. El punto 17 (comprobante imprimible y consulta de evidencias) sigue pendiente.

### Verificación del punto 16

56 pruebas y 208 comprobaciones correctas en la suite de recepción directa, destino y estados de OC. Siete pruebas de consulta cubren permiso separado, OC ajena, rutas, alcance por obra/OC, historial revertido, paginación con pestaña y detalle con origen/costo histórico frente a saldos actuales. Render completo de la nueva pantalla y parciales correcto; rutas, sintaxis PHP, compilación Blade y UTF-8 verificados. Sin cambios en producción.

## Punto 17: comprobante y evidencias

- La consulta de recepción permite imprimir el comprobante con destino, receptor, documentos, partidas y costos históricos. Identifica las recepciones no aplicadas.
- Las evidencias se descargan desde almacenamiento privado con el permiso de consulta y se comprueba su pertenencia a la recepción y a la OC.
- Verificación: 8 pruebas de consulta y 41 comprobaciones, incluidas impresión y rechazo de evidencia ajena. Compilación Blade y UTF-8 correctos. Producción sin cambios.

## Punto 18: reversión conjunta

- Una recepción aplicada puede revertirse completa con el permiso especial y motivo obligatorio. Se conserva usuario, fecha, motivo, evidencia y documentos originales.
- Una transacción bloquea OC, recepción, partidas y requisiciones; valida los movimientos originales y rechaza documentos posteriores dependientes o saldos inconsistentes. Actualiza el saldo de OC y requisición y recalcula los estados de recepción.
- Se generan documentos de cancelación y movimientos compensatorios por la salida y la entrada, con cantidades e importes históricos. No se modifica el stock físico ni su valor; el resumen de obra excluye la recepción revertida. Una segunda reversión se rechaza.
- La prueba local persistente se identifica como OC `PRUEBA-REV-05DA8F1A2E` (206), recepción `RDO-00000002` (2). Se conservan los cuatro movimientos, sus documentos y la evidencia. Es simulada; no corresponde a entrega real.
- 63 pruebas, 240 comprobaciones correctas. Cubren permiso, reversión repetida, parciales, requisición, saldos inconsistentes y fallo durante la escritura de movimientos con rollback. No se modificó producción.

## Paso 19: checkpoints de devolución y finiquito

La ampliación del paso 19 cubre entregas directas y salidas de almacén a obra, con o sin OC o requisición. Las requisiciones y OCs son antecedentes; las entregas efectivamente aplicadas determinan el material en obra. Cada punto se marca al terminarlo y verificarlo; sus hallazgos se registran en este documento.

- [x] **19.1** Revisar salidas, entregas directas, devoluciones, vínculos y costos existentes.
- [x] **19.2** Definir estados, transiciones y reglas iniciales de devolución.
- [x] **19.3** Definir permisos de solicitud y confirmación; el usuario informó que ya los creó.
- [x] **19.4** Diseñar registros y vínculos de solicitud, partidas y origen.
- [x] **19.5** Crear estructura de datos y migraciones.
- [x] **19.6** Consultar material efectivamente entregado por las distintas rutas.
- [x] **19.7** Mostrar trazabilidad de requisición, OC y salida sin duplicar cantidades.
- [x] **19.8** Calcular saldos entregados, devueltos y disponibles para solicitud.
- [x] **19.9** Mostrar materiales y trazabilidad en la obra.
- [x] **19.10** Capturar solicitudes de devolución durante la obra.
- [x] **19.11** Impedir solicitudes duplicadas o que excedan el saldo.
- [x] **19.12** Capturar cantidad, condición y observaciones por partida.
- [x] **19.13** Generar folio y documento imprimible de solicitud.
- [x] **19.14** Consultar solicitudes pendientes en almacén.
- [x] **19.15** Confirmar en almacén cantidad y condición recibidas.
- [x] **19.16** Registrar entrada de material recibido en buen estado con su origen.
- [x] **19.17** Ajustar el cargo de obra con el costo histórico confirmado.
- [x] **19.18** Registrar herramientas dañadas o desgastadas recibidas pendientes de evaluación.
- [x] **19.19** Mostrar diferencias y cantidades pendientes; su resolución queda para una etapa posterior.
- [x] **19.20** Proteger contra confirmaciones duplicadas y aplicar cambios de forma conjunta.
- [x] **19.21** Incorporar saldos y solicitudes al finiquito de materiales.
- [x] **19.22** Crear resumen imprimible del finiquito.
- [x] **19.23** Probar orígenes, parciales, devoluciones y herramientas pendientes de evaluación.
- [x] **19.24** Documentar flujo, permisos y temas de evaluación pendientes.

## Punto 19.1: revisión de salidas y devoluciones

Revisión de código, sin modificar datos ni comportamiento. El paso 19 continúa pendiente; no se implementaron todavía la solicitud ni el finiquito.

- **Giralda:** `InventarioDocumentoController` crea documentos generales y sus partidas. Una salida puede apuntar a una obra sin OC ni requisición; al crearla para obra, esta debe estar en ejecución. `InventarioDocumentoService::aplicar` bloquea stock, descuenta cantidad y valor, y guarda un movimiento `out` con obra y costo promedio vigente al salir. La entrada relacionada con OC puede guardar la partida de compra, pero una salida posterior no queda automáticamente vinculada a esa compra.
- **Precisión y origen en Giralda:** el movimiento general registra producto y documento, pero no identifica explícitamente la partida del documento. Si un documento repite producto, no debe suponerse una correspondencia única por producto. El costo histórico de salida se encuentra en el movimiento; no debe reemplazarse por el promedio actual al devolver.
- **Huentitán:** `HuentitanSalidaController` maneja `huentitan_salidas` y `huentitan_salida_detalles` por separado. Al aplicar descuenta stock disponible, registra `out` en `inventario_movimientos` y guarda costo unitario e importe en la partida propia de Huentitán. Su método de cancelación actual únicamente informa que está pendiente; no asumir que revierte inventario.
- **Identificadores de documentos:** el movimiento de Huentitán guarda el id de `huentitan_salidas` en `documento_id`, mientras que Giralda utiliza `inventario_documentos`. Esos identificadores pertenecen a tablas distintas y pueden coincidir; una consulta única no debe asociarlos solo por id. La nueva trazabilidad requiere identificar explícitamente el tipo de origen y su partida.
- **Entrega directa:** la recepción aplicada guarda OC, obra, almacén, evidencia, documentos de entrada/salida y partidas con cantidades, unidad base y costo histórico en moneda de OC y MXN. La partida de requisición es opcional. La entrada/salida documental no cambia el stock físico; la salida constituye la entrega a obra. El resumen de obra cuenta solo recepciones aplicadas y su salida, sin duplicar la entrada.
- **Requisiciones:** sus partidas tienen cantidades autorizadas y surtidas y campos de origen, pero el flujo general de revisión/autorización aún no está implementado. La conexión existente con OC y recepción directa es opcional; no asumir que toda compra o salida deriva de una requisición.
- **Devolución general existente:** el servicio admite documentos `devolucion` como entradas y aumenta stock/valor; si el detalle no informa costo, usa el costo promedio vigente. La captura revisada exige obra, pero no establece automáticamente el vínculo por partida con una salida ni valida un saldo devolvible de la entrega. No reutilizar ese comportamiento sin asegurar origen, saldo y costo histórico.
- **Consecuencia para el diseño:** construir saldos a partir de entregas aplicadas identificadas por origen y partida; presentar OC y requisición como antecedentes, no como entregas adicionales. Excluir borradores, recepciones revertidas y compensaciones de saldos activos. Conservar el historial y establecer cómo tratar registros legacy ambiguos sin adjudicarles un origen o costo que no se pueda demostrar.
- **Herramientas dañadas:** el estado recibido pendiente de evaluación y su tratamiento de inventario/costo aún no existen en este flujo. La solicitud y la recepción física deben quedar separadas; el cargo definitivo no debe ajustarse por un prorrateo todavía no definido.

## Punto 19.2: estados y reglas iniciales

Definición funcional para desarrollar los puntos siguientes; todavía no se crearon tablas ni se cambiaron procesos.

### Estado de la solicitud

| Estado | Significado y transición |
| --- | --- |
| **Borrador** | El residente prepara cantidades, condiciones y observaciones. Puede corregirlas antes de presentar la solicitud. No genera entrada ni ajuste de costo. |
| **Solicitada** | El residente presenta la solicitud; queda disponible para almacén. Las cantidades solicitadas pendientes se comprometen contra su entrega de origen para impedir otra solicitud por el mismo saldo. No se registra aún una entrada física ni se reduce el cargo de obra. |
| **Recepción parcial** | Almacén confirmó parte de lo solicitado; el resto queda pendiente de recibir o aclarar. Pueden existir varias confirmaciones, cada una conservada en el historial. |
| **Recibida** | Almacén confirmó toda la cantidad solicitada. Puede haber partidas recibidas pendientes de evaluación; este estado acredita cantidad física, no que la valuación esté cerrada. |
| **Cancelada** | Solicitud cancelada antes de cualquier recepción confirmada; libera sus cantidades comprometidas sin generar movimiento de inventario. La autorización y la forma de corrección de recepciones ya aplicadas se definirán en los puntos 19.3 y 19.20. |

### Estado y condición por partida

- Cada partida conserva la entrega de origen, la cantidad solicitada, la cantidad confirmada por almacén y el saldo pendiente. La condición informada por el residente y la confirmada por almacén se guardan por separado, con observaciones; la solicitud original no se sobrescribe.
- **Buen estado:** al confirmar recepción física, registrar la entrada correspondiente y ajustar el cargo de obra con el costo histórico de la cantidad confirmada, sujeto a las validaciones y transacción de los puntos 19.16 a 19.20.
- **Herramienta dañada o desgastada:** registrar la cantidad como recibida y **pendiente de evaluación**. Conservar condición y observaciones; no fijar desgaste, prorrateo ni ajuste definitivo de costo hasta definir la evaluación posterior. Su tratamiento como existencia disponible o segregada se definirá en el diseño de datos y operación antes de implementarlo; no asumir disponibilidad automática.
- **Pendiente de recibir o aclarar:** si se recibe menos de lo solicitado, conservar la diferencia pendiente; no crear entrada ni descontar cargo por esa diferencia. La resolución y autorización de discrepancias es una etapa posterior.
- Si almacén recibe más de lo solicitado, bloquear el excedente en esta primera etapa; no incrementar el saldo devuelto de forma implícita. Si la condición difiere, conservar ambas declaraciones y dejar la diferencia pendiente de aclaración. No cerrar automáticamente su valuación.

### Saldos y cierre

- **Entregado:** únicamente cantidades efectivamente aplicadas a la obra. Una requisición, OC y su salida vinculadas describen una misma cadena y no se suman entre sí.
- **Disponible para solicitar:** cantidad entregada menos devoluciones confirmadas y cantidades todavía comprometidas en solicitudes presentadas no canceladas. Evitar descontar dos veces la parte de una solicitud que ya fue confirmada: del compromiso se resta solo su parte aún pendiente.
- **Costo de devolución:** conservar el costo histórico de la partida de entrega; la solicitud no cambia el costo. La devolución confirmada en buen estado ajusta el cargo. Una herramienta pendiente de evaluación mantiene su costo definitivo sin resolver.
- Se permiten solicitudes durante la obra y un finiquito al cierre. El finiquito debe mostrar por separado saldo en obra, solicitudes pendientes, diferencias y herramientas pendientes de evaluación; no declarar que el costo está completamente cerrado mientras queden valuaciones o diferencias sin resolver.
- Una recepción confirmada no se edita ni borra silenciosamente. Corrección, permisos, concurrencia, evidencia y transiciones exactas ante discrepancias se detallarán en los puntos correspondientes; no se considera implementado el mecanismo de evaluación o prorrateo.

## Punto 19.3: permisos acordados

El usuario informó que creó los siguientes permisos. En este punto se documentan los nombres; aún no se han conectado al código del nuevo flujo ni se ha verificado su asignación a roles o usuarios.

- `obra.devoluciones.view.access`: consultar solicitudes e historial; recomendado para residente y almacén.
- `obra.devoluciones.create.access`: preparar, presentar y cancelar solicitudes sin recepción confirmada; recomendado para residente o responsable de obra. Las acciones también exigen consulta y acceso a la obra.
- `obra.devoluciones.receive.access`: confirmar cantidades y condiciones recibidas; recomendado para almacén.

Los permisos de corrección de recepciones aplicadas y de evaluación de herramientas quedan pendientes de definir con esos procesos. El permiso de consulta debe acompañar al de crear o recibir para acceder a las pantallas correspondientes.

## Punto 19.4: diseño de registros y vínculos

Diseño funcional y técnico propuesto para el 19.5. No crea tablas, no modifica registros y no presupone que una OC provenga de una requisición. Los nombres de tablas y columnas se validarán al implementar la migración.

### 1. Solicitud de devolución

Cabecera propuesta `obra_devoluciones`: identificador y folio único; obra; estado (`borrador`, `solicitada`, `recepcion_parcial`, `recibida`, `cancelada`); persona que creó y presentó; fechas de creación, presentación y cancelación; observaciones y motivo de cancelación cuando aplique. Los estados y restricciones siguen el 19.2. El folio y las fechas de presentación deben permanecer como historial.

Una solicitud pertenece a una obra y contiene una o más partidas. Puede generar varias recepciones físicas parciales. Su estado se deriva de sus partidas y confirmaciones; `recibida` no significa que estén resueltas todas las evaluaciones de herramienta.

### 2. Partidas solicitadas y origen de entrega

Tabla propuesta `obra_devolucion_detalles`: solicitud, producto, cantidad solicitada, unidad de inventario, condición declarada por obra, observaciones y fotografía de descripción, cantidad y costo de la entrega de origen. Cada partida apunta a **una entrega original concreta**. Si el mismo producto llegó en dos entregas con diferente costo, se manejan dos partidas de devolución, aunque se muestren agrupadas visualmente.

Usar tres referencias opcionales con claves foráneas y una regla de exclusividad: exactamente una debe estar informada. El servidor comprueba que el origen esté aplicado, corresponda a la misma obra y producto y no sea una compensación ni una recepción revertida.

| Origen | Referencia propuesta | Consideraciones |
| --- | --- | --- |
| Entrega directa | `compra_recepcion_directa_detalle_id` | La partida conserva cantidad base, OC, requisición opcional y costo histórico en MXN. Su documento de salida es parte de la misma entrega, no otra partida disponible. |
| Salida general Giralda | `inventario_movimiento_id` | Usar el movimiento de salida concreto como unidad de trazabilidad: contiene cantidad, obra y costo aplicado. Las partidas generales no tienen vínculo explícito al movimiento y pueden repetir producto; no inferir un cruce único por producto. Excluir movimientos de recepción directa y cancelaciones/compensaciones. |
| Salida Huentitán | `huentitan_salida_detalle_id` | Partida propia de salida aplicada; conserva cantidad y costo histórico. Su id y el `documento_id` del movimiento no deben confundirse con los de documentos generales. |

La tabla conserva instantáneas de cantidad, unidad y costo para auditoría, con el identificador original como fuente verificable. Los antecedentes de OC y requisición se consultan desde el origen cuando existan; no crean saldo adicional. Una entrega legacy cuyo origen o costo no pueda identificarse sin ambigüedad se presenta para revisión y no se habilita para devolución automática.

### 3. Confirmaciones físicas

Cabecera propuesta `obra_devolucion_recepciones`: solicitud, folio, almacén receptor, estado aplicado, fecha física, usuario que confirma y observaciones. Una confirmación puede contener varias partidas; una solicitud admite confirmaciones parciales. La misma operación debe tener identificador único para reintentos y conservar fecha y responsable.

Partidas propuestas `obra_devolucion_recepcion_detalles`: confirmación, partida solicitada, cantidad recibida, condición confirmada por almacén, observaciones, estado de evaluación y costo histórico utilizado. Mantener la condición declarada originalmente y la confirmada como datos separados. Para una herramienta dañada o desgastada, guardar `pendiente_evaluacion` y no registrar un ajuste definitivo de costo por desgaste.

Cada cantidad físicamente aceptada debe enlazarse al documento y movimiento de entrada que corresponda. Para material en buen estado, también debe existir un vínculo verificable al ajuste del cargo de obra, usando el costo de la entrega original. Si se reciben unidades de una partida en condiciones distintas, registrarlas en partidas de confirmación separadas; no mezclar unidades disponibles y pendientes de evaluación bajo un solo estado.

### 4. Saldos, integridad y concurrencia

- **Pendiente por solicitud:** solicitado menos cantidades confirmadas; una cancelación previa a toda confirmación libera el compromiso. La suma confirmada no puede exceder lo solicitado.
- **Disponible de una entrega:** cantidad entregada aplicada menos devoluciones confirmadas y compromisos pendientes de solicitudes presentadas. La parte ya confirmada se descuenta una sola vez. Los borradores no comprometen saldo, por lo que al presentar se debe validar nuevamente.
- Al presentar o recibir, bloquear la entrega de origen y sus compromisos en una transacción con orden de bloqueo estable; recalcular el saldo dentro de esa transacción. La llave de operación única y las restricciones de base complementan la validación, no la sustituyen.
- Las confirmaciones, movimientos y ajustes aplicados se conservan como historial; no se editan ni eliminan silenciosamente. La corrección de una recepción confirmada y la resolución de discrepancias se definirán antes de habilitar esas acciones.
- Usar precisión suficiente para cantidades, costos e importes, preservar unidad base y moneda de costo, y conciliar redondeos en devoluciones parciales. No convertir usando el precio o promedio actual del almacén.

### 5. Finiquito y evaluación pendientes

El finiquito consulta entregas, solicitudes, confirmaciones, diferencias y evaluaciones pendientes por obra. Su implementación y si requiere una cabecera propia y versiones quedan para los puntos 19.21 y 19.22; no se necesita inventar desde ahora una tabla definitiva de cierre.

Quedan por definir antes de implementar la recepción de herramienta dañada: segregación física/lógica, disponibilidad y tratamiento contable provisional. La tabla de prorrateo, valuación final y autorización de diferencias son una etapa posterior. La migración 19.5 debe proteger la trazabilidad sin presentar esas decisiones como ya tomadas.

## Punto 19.5: estructura de datos

- Migración `2026_11_06_020000_create_obra_devoluciones_tables`: cabeceras de solicitud, partidas con fotografías de origen/costo, recepciones físicas y partidas de recepción con condiciones separadas, evaluación pendiente y vínculos opcionales a entrada/movimiento.
- Tres claves foráneas distinguen los orígenes de entrega directa, movimiento general y salida Huentitán. La base exige exactamente uno; en SQLite se verifica mediante triggers y en MySQL/MariaDB mediante restricción CHECK. Las claves de origen y los vínculos de historial restringen la eliminación.
- UUID único por recepción para que el flujo posterior pueda implementar reintentos seguros. Los modelos Eloquent y sus relaciones permiten consultar las cuatro entidades y sus orígenes.
- Migración aplicada en base local; se verificó la existencia de `odd_origen_unico`. Tres pruebas de esquema y siete comprobaciones correctas sobre exclusividad de origen, claves foráneas, condiciones separadas y UUID único.
- Este punto solo agrega la estructura. El estado de origen aplicado, pertenencia a obra/producto, saldo, concurrencia, idempotencia funcional, permisos, entradas y costos se implementarán en puntos posteriores. No se crearon solicitudes ni se modificaron datos existentes o producción.

## Punto 19.6: consulta de material entregado

- `MaterialEntregadoConsultaService::porObra` consulta partidas de recepción directa aplicada, movimientos de salida general aplicada y partidas de salida Huentitán aplicada. Cada resultado conserva tipo e identificador de origen, obra implícita en el filtro, producto, cantidad, fecha, almacén y costo histórico disponible.
- La entrega directa se identifica por su partida y salida aplicada; no se agrega la entrada documental. Se excluyen recepciones revertidas, borradores, cancelaciones y movimientos compensatorios. No se suman OC ni requisición como entregas adicionales.
- Las salidas generales se identifican por movimiento. Si un id de documento también coincide con una salida Huentitán del mismo almacén, se excluye de las salidas generales por ambiguo y se expone por separado mediante `ambiguasPorObra`, para revisión; no se atribuye automáticamente a otra partida. La unidad de movimiento general se toma del catálogo actual y se marca como tal, pues el movimiento no conserva unidad histórica.
- La consulta devuelve cantidades brutas entregadas, no saldos disponibles para devolución; no resta devoluciones ni compromisos. La resolución de ambigüedades y la presentación al usuario se atenderán en puntos posteriores.
- Verificada en local con la obra 18 y en siete pruebas (23 comprobaciones) junto con el resumen de costos: tres fuentes, exclusión de duplicados y reversión, documentos no aplicados, aislamiento por obra y detección de colisiones. No se alteraron datos ni se creó pantalla.

## Punto 19.7: trazabilidad de antecedentes

- La consulta de entregas añade referencias de salida, OC, requisición y recepción directa cuando existe un vínculo explícito, sin agregar filas ni cantidades. Las referencias se filtran por obra y, para requisiciones y partidas Huentitán, por producto.
- En salidas generales la OC de cabecera se etiqueta como vínculo del documento, no como vínculo probado de la partida. Las salidas sin OC permanecen sin referencia inventada. Una partida Huentitán puede conservar varias partidas de compra relacionadas sin multiplicar la cantidad entregada.
- En la tabla de órdenes de obra, cada material de una recepción directa muestra su requisición vinculada cuando existe. La pantalla integral de materiales de todos los orígenes corresponde al 19.9.
- Pruebas: 19 pruebas y 104 comprobaciones correctas, incluidos varios antecedentes de OC para una sola entrega, requisición vinculada, ausencia de duplicación y recepción revertida. Consulta verificada en local para la obra 18; sin modificaciones de datos ni producción.

## Punto 19.8: saldos por entrega

- `MaterialDevolucionSaldoService::porObra` parte de las entregas aplicadas y calcula por origen cantidad recibida en devolución, cantidad comprometida pendiente y cantidad disponible para solicitar. Los borradores y canceladas sin recepciones no comprometen saldo; la parte confirmada de una solicitud parcial no se descuenta dos veces.
- Las herramientas físicamente recibidas pendientes de evaluación se cuentan por separado dentro de lo recibido. Esta consulta no calcula ajustes de costo ni considera resuelta su valuación.
- Se informa la revisión necesaria y no se ofrece saldo automático ante sobreasignación, producto u origen inconsistente, recepción aplicada incompatible, o devolución histórica de ese producto sin vínculo inequívoco. Se devuelven aparte los orígenes ambiguos y las devoluciones antiguas sin origen.
- La consulta es informativa; no reserva cantidades ni sustituye la validación transaccional y el bloqueo de origen que se implementarán al presentar solicitudes y confirmar recepciones. Las solicitudes y devoluciones existentes no se modificaron.
- Ocho pruebas y 36 comprobaciones correctas con la consulta de entregas: recepciones parciales, borradores, cancelaciones, evaluación pendiente, exceso, origen revertido y devolución legacy sin vínculo. Consulta verificada con los datos locales de la obra 18.

## Punto 19.9: consulta de materiales en obra

- Nueva pestaña `materiales-obra`, visible y accesible con `obra.devoluciones.view.access`, protegida también en el controlador. Muestra por entrega aplicada el origen, antecedentes verificables, cantidad, devoluciones confirmadas, compromisos, saldo disponible, evaluación pendiente y costo original.
- Los saldos inconsistentes se identifican como `Revisar`. Las colisiones de origen, devoluciones legacy sin vínculo y otras inconsistencias se presentan con referencias para revisión. Las cantidades y costos son informativos: aún no existe captura de solicitudes ni costo definitivo de herramienta pendiente de evaluación.
- Nueve pruebas y 42 comprobaciones correctas con consultas y render de casos vacíos/observados. Vista completa de obra 18 verificada con permiso simulado, sin modificar roles ni datos. El usuario local Héctor todavía no tiene asignado el permiso de consulta; deberá asignarse para ver la pestaña.

## Puntos 19.10 y 19.11: captura y protección del saldo

- Desde `Materiales en obra` el usuario con permisos de consulta y creación puede seleccionar varias entregas y cantidades, guardar un borrador o presentar una solicitud. Se genera el folio `DEV-OB-...` y se muestran las solicitudes y sus partidas con sus folios de origen.
- El borrador no compromete saldo; permite corregir cantidades y observaciones. Al presentarlo se comprueban otra vez la entrega, el saldo y la fotografía de cantidad, unidad y costo. Las condiciones por partida se incorporarán en 19.12; entretanto quedan como `por_definir`.
- Una solicitud presentada compromete el saldo pendiente. La operación bloquea la obra y los orígenes, comprueba el estado vigente bajo bloqueo y vuelve a calcular el saldo dentro de la transacción. Se rechazan cantidades excesivas, partidas repetidas, otros orígenes/obras, datos inconsistentes y entregas sin costo o unidad confiable.
- Se añadió una clave UUID e identificación de contenido mediante la migración `2026_11_06_030000_add_obra_devolucion_idempotency.php`. El reintento idéntico devuelve la misma solicitud; reutilizar la clave con contenido, usuario u obra distintos produce conflicto.
- El permiso de creación permite cancelar, con motivo e historial, un borrador o solicitud sin recepción aplicada. La cancelación libera el compromiso sin borrar registros; una solicitud con recepción confirmada no puede cancelarse así.
- Una recepción directa o salida general con una solicitud presentada no puede revertirse/cancelarse silenciosamente. La corrección de recepciones de devolución aplicadas y la coordinación de su confirmación son posteriores (19.20).
- Prueba local persistente aislada: obra `PRUEBA LOCAL DEVOLUCION 91B7A72A` (43), OC `TEST-DEV-91B7A72A`, recepción `RDO-00000004` y solicitud `DEV-OB-00000002`. Dos procesos intentaron solicitar 3 de 4 unidades: uno se aceptó y el otro se rechazó; permaneció 1 disponible. Otra prueba aislada equivalente quedó en obra 42. Ambas están identificadas como simulación y conservan sus registros.
- Se probó también una solicitud concurrente contra la reversión de su origen: obra de prueba 44, OC `TEST-DEV-EB43D77B`, recepción `RDO-00000005`. Se aceptó la reversión y la solicitud se rechazó al comprobar bajo bloqueo que la entrega ya no estaba aplicada; no quedó una solicitud huérfana. Se conservan los registros de prueba.
- La migración se aplicó en local. Se verificaron borradores, saldos, duplicados, permisos, origen ajeno, cancelación, reintento, fuente revertida y cambio de costo, además de la vista con permisos simulados. No se asignaron permisos ni se modificó producción.

## Punto 19.12: condición y observaciones por partida

- Al crear una solicitud o borrador se captura por partida la condición declarada: `bueno`, `danado` o `desgastado`, más observaciones opcionales. Las cantidades siguen vinculadas a cada entrega original; la información de la solicitud general se conserva separada de la nota de cada partida.
- En borrador se pueden corregir cantidad, condición y observaciones por partida. Para presentar un borrador se exige una condición válida en cada partida. Las solicitudes ya presentadas siguen sin edición silenciosa.
- La condición declarada por obra no representa confirmación de almacén ni dispara entrada, ajuste de costo o evaluación. Las condiciones confirmadas se registrarán en el flujo posterior; herramientas dañadas o desgastadas permanecerán pendientes de evaluación conforme al 19.18.
- Los registros anteriores `por_definir` se conservan y muestran como `Sin definir`; un borrador antiguo debe completarse antes de presentarse. Los reintentos idénticos de operaciones anteriores conservan su idempotencia; para solicitudes nuevas el hash incluye condición y observaciones de cada partida.
- Una misma entrega se incluye una vez por solicitud; si un mismo origen contiene material en condiciones distintas, de momento se capturan solicitudes separadas y cada una respeta el saldo disponible.
- Prueba persistente en la obra de simulación 43: borrador `DEV-OB-00000003`, con condición `desgastado` y nota identificada como prueba. La vista se verificó con permisos simulados. No se modificaron solicitudes anteriores ni producción.

## Punto 19.24: guía operativa y pendientes

- Se documentaron el flujo de solicitud, recepción física, aplicación por partida, herramientas pendientes de evaluación y consulta/impresión preliminar; las fórmulas de saldos y su solapamiento; y los permisos y restricciones vigentes en `docs/GUIA_DEVOLUCIONES_FINIQUITO_OBRA.md`.
- La guía distingue lo implementado de las decisiones pendientes sobre consumo definitivo, valuación de herramientas, aclaración de diferencias, correcciones y revisión operativa. También identifica las pruebas locales y el límite de cobertura en MySQL. La documentación no asigna permisos, no despliega cambios y no modifica registros.

## Punto 19.23: pruebas integrales

- Se ejecutaron 117 pruebas automatizadas con 547 comprobaciones en SQLite aislado, incluyendo compra y recepción directa, los tres orígenes de entrega, solicitudes, permisos, parciales, idempotencia, rollback, costos históricos, condiciones distintas, herramientas pendientes de evaluación y finiquito e impresión.
- Se agregó un caso integral con una solicitud de tres orígenes y dos recepciones mixtas: las partidas en buen estado se aplicaron una sola vez, las herramientas desgastadas quedaron pendientes de evaluación sin aumentar el inventario y el finiquito conservó las cantidades, importes y folios correspondientes.
- En MySQL local se probó además una devolución directa parcial, otra recepción con condición diferente y el resultado en el finiquito y su impresión. Se conservaron en la obra 42 la solicitud `DEV-OB-00000005`, recepción aplicada `REC-OB-00000002`, recepción pendiente `REC-OB-00000003` y movimiento 67; etiqueta `PRUEBA LOCAL FINIQUITO 19.23 UPLAPPJK`. La prueba usa permisos simulados para el usuario local y no acredita las asignaciones reales de roles.
- La prueba de concurrencia de MySQL del 19.20 también permanece registrada. Los tres orígenes y la recepción mixta se cubrieron en SQLite; la prueba persistente de MySQL cubrió el origen de entrega directa, no sustituye la revisión operativa del usuario ni prueba en producción. No se modificó producción.

## Punto 19.22: resumen imprimible preliminar

- La consulta de finiquito incluye un botón de impresión. El formato reutiliza la cabecera de impresión compartida, identifica la obra y fecha de generación y presenta los mismos saldos y referencias por entrega, sin sumar OC o requisición nuevamente.
- Incluye solicitudes, recepciones, cantidades, condiciones, diferencias, evaluaciones y observaciones, así como avisos de orígenes o saldos que requieren revisión. La impresión y la consulta muestran `Revisar` cuando una fila no tiene saldos confiables.
- El documento se identifica como preliminar: no confirma consumo ni cierra la obra, y puede cambiar después de nuevos movimientos. No se asigna folio de cierre ni se registra un estado definitivo. La distribución de consumo y la resolución de diferencias siguen pendientes; no se agregaron firmantes ni permisos nuevos.
- La ruta exige el permiso de consulta y el acceso a la obra. Las pruebas aisladas verifican saldos parciales, condición diferente, evaluación, obra ajena, cantidades inconsistentes, estado vacío y permiso. No se modificaron registros ni producción.

## Punto 19.21: consulta preliminar de finiquito de materiales

- Desde materiales de la obra se puede consultar una vista preliminar que reúne las entregas aplicadas de los tres orígenes y sus referencias a OC, requisición y solicitudes, sin sumar dos veces los documentos de una misma cadena.
- Por entrega se muestran cantidad entregada, devolución aplicada, recepción física pendiente de aplicación, cantidad pendiente de recibir, saldo sin devolución física registrada, herramienta pendiente de evaluación y costo neto provisional. Las cantidades se mantienen en su propia unidad; no se suman unidades incompatibles. El pendiente de recibir está incluido en el saldo sin devolución; la evaluación se muestra como subconjunto de lo recibido.
- Se muestran solicitudes e historial de recepciones, diferencias y observaciones. Orígenes ambiguos, devoluciones antiguas sin referencia, orígenes faltantes o cantidades inconsistentes se señalan para revisión en vez de presentar saldos concluyentes. Se usa el permiso existente de consulta y la misma restricción de acceso por obra.
- La consulta no guarda un cierre, no declara consumo ni cambia el estado de la obra. El saldo sin devolución puede contener material consumido; para un finiquito definitivo queda pendiente definir y registrar esa distribución y resolver las diferencias y evaluaciones. El formato imprimible es el punto 19.22.
- Pruebas aisladas verifican parciales aplicadas y sin aplicar, evaluación, saldos, discrepancias, datos inconsistentes, separación de obras y permiso de consulta. No hubo migración ni cambios en producción.

## Punto 19.20: aplicación por partida y concurrencia

- Una recepción mixta permite aplicar sus partidas aptas en buen estado, generando la entrada, el movimiento y su importe histórico en una transacción. Las partidas dañadas, desgastadas o con condición diferente permanecen pendientes de evaluación o aclaración; no se suman al inventario disponible ni se les ajusta el costo.
- Cada partida aplicada conserva vínculos al detalle del documento, movimiento e importe. La recepción permanece pendiente mientras alguna partida no pueda aplicarse. Los saldos y el seguimiento reconocen las partidas aplicadas de una recepción mixta y no vuelven a contarlas como pendientes; un reintento no genera una segunda entrada. Se bloquean vínculos de aplicación incompletos o inconsistentes.
- Las pruebas aisladas incluyen recepción mixta, reintento, aplicación posterior de otra cantidad del mismo origen, saldos, costos y fallo con rollback. En MySQL local dos procesos intentaron aplicar simultáneamente la misma recepción: ambos terminaron y se creó un solo movimiento. Se conservan los registros identificados como prueba: obra 42, solicitud `DEV-OB-00000004`, recepción `REC-OB-00000001`, movimiento 66, cantidad 0.001. La herramienta de prueba solo opera en MySQL local y sobre obras identificadas como simulación.
- La aclaración de diferencias y la evaluación/valuación de herramientas no se implementaron en este punto. No se modificó producción.

## Punto 19.19: diferencias y cantidades pendientes

- La consulta de almacén distingue por partida lo solicitado, lo recibido físicamente, lo pendiente de recibir y lo ya aplicado a inventario y costo; muestra por separado la cantidad pendiente de evaluación y advierte las condiciones distintas o datos inconsistentes.
- En materiales de obra, el seguimiento de cada solicitud muestra las mismas cantidades y el historial de recepciones con folio, fecha, almacén, condición y observaciones. Las recepciones en borrador o canceladas no se cuentan. Una condición original sin definir se identifica como tal, sin inventar una diferencia.
- Las diferencias de condición siguen pendientes de aclaración y una cantidad pendiente de recibir no se clasifica automáticamente como pérdida. No se agrega resolución, autorización, ajuste de costo ni movimiento. Las consultas solo usan datos de la obra autorizada o los permisos existentes de almacén.
- Pruebas aisladas cubren recepciones parciales, aplicación, evaluación, diferencia de condición, condición sin referencia y cantidades inconsistentes. No se modificaron registros ni producción.

## Punto 19.18: herramientas recibidas pendientes de evaluación

- Desde una recepción física pendiente, almacén puede registrar las partidas dañadas o desgastadas cuyo producto esté clasificado como `herramienta`. Se conserva la clasificación al registrar, el usuario, la fecha, la condición, la cantidad y el vínculo con la recepción y entrega de origen. El reintento no duplica el registro.
- La custodia queda registrada en el detalle de recepción con estado `pendiente_evaluacion`, separada del inventario disponible. No se crea todavía movimiento de kardex para esas partidas ni se modifica la existencia, el valor del almacén o el cargo de obra. El detalle es el registro de recepción física; la entrada o disposición posterior depende de definir la evaluación y su operación de inventario.
- La ficha de materiales muestra la cantidad física pendiente de evaluación; esa cantidad continúa comprometida mientras no exista una devolución aplicada. Si la condición de almacén difiere de la declarada, también se conserva la advertencia de aclaración. Consumibles y otras clasificaciones no se convierten automáticamente en herramientas.
- En este punto la recepción mixta aún no aplicaba partidas por separado; esa limitación se atendió en 19.20. La migración `2026_11_09_010000_add_evaluacion_custodia_a_devoluciones` se aplicó en local. MySQL rechazó inicialmente el nombre largo de la llave foránea; se acortó y la migración reanudó sin borrar columnas ni registros. No se modificó producción.

## Puntos 19.16 y 19.17: entrada y costo histórico aplicado

- Para una recepción cuyas partidas estén todas en buen estado y coincidan con la condición declarada, almacén puede aplicar la entrada. En una transacción se crean documento y movimiento de devolución, se incrementan existencias y valor del almacén receptor, y se vinculan las partidas con el origen y el movimiento. Se impide cancelar el documento desde inventario de manera independiente.
- El importe del movimiento y el ajuste por entrega se calculan proporcionalmente sobre el importe histórico original. Los centavos se distribuyen acumulativamente entre devoluciones parciales para que la suma de una devolución completa coincida con el importe original. La ficha de materiales muestra costo original, devolución aplicada y costo neto; el resumen de entrega directa resta el movimiento vinculado. El total histórico de las órdenes de compra permanece sin cambios y no es un indicador de consumo neto.
- Inicialmente las recepciones mixtas no se aplicaban; 19.20 incorporó la aplicación de sus partidas aptas. Las dañadas o con condición diferente continúan pendientes de evaluación o aclaración. Una recepción aplicada no puede aplicarse dos veces.
- En esta etapa se probaron los tres orígenes, devolución parcial, idempotencia, distribución de centavos y rollback ante fallo de movimiento usando SQLite aislado. La validación posterior de concurrencia en MySQL y casos mixtos se documenta en 19.20. No se modificó producción.

## Punto 19.16: preparación inicial de entrada

- Se prepara la entrada para partidas físicamente recibidas en buen estado y cuya condición coincide con la declarada. Se conservan la referencia al detalle de recepción, producto, unidad, cantidad, costo histórico y el identificador de la entrega de origen. Las diferencias de condición quedan pendientes de aclaración y las partidas dañadas o desgastadas, de evaluación.
- La consulta de almacén muestra cuántas partidas pueden prepararse y señala datos que requieren revisión. La preparación no crea documentos ni movimientos, ni modifica existencias o el cargo de obra.
- Hallazgo: el servicio general registra el costo unitario con cuatro decimales, mientras el detalle de devolución conserva ocho. La aplicación conjunta posterior conserva el costo histórico en el detalle de recepción y el importe exacto en el movimiento; no usa el servicio general de aplicación independiente.
- Verificado con pruebas aisladas: origen y costo preservados, exclusión de diferencias, detección de costo alterado y ausencia de movimientos o documentos nuevos. No se modificó producción ni registros locales existentes.

## Punto 19.15: confirmación de recepción física

- Almacén registra fecha, almacén receptor, cantidades, condición y observaciones por partida. Se permiten recepciones parciales; las cantidades pendientes y la condición declarada por obra se conservan. Cuando las condiciones difieren, se muestran para aclaración.
- La recepción queda como `pendiente_aplicacion`: registra que el material llegó físicamente, pero no genera movimiento de inventario, no aumenta existencias ni ajusta el costo de obra. Las herramientas dañadas o desgastadas no se consideran disponibles; su tratamiento corresponde a 19.18. La aplicación conjunta sigue pendiente de 19.16 a 19.20.
- Se exigen los permisos de consulta y recepción, se valida el almacén activo y se bloquean cantidades excedentes, solicitudes ajenas o inconsistentes, cancelaciones posteriores y reintentos con datos diferentes. Las recepciones físicas registradas se descuentan del pendiente físico, no del saldo aplicado de obra.
- Pruebas aisladas verifican recepción parcial y completa, conservación de condiciones, discrepancias, reintentos, bloqueo de excedentes y cancelación, y ausencia de cambios en inventario. No se modificó producción ni se cambió ningún registro local existente.

## Punto 19.14: consulta de solicitudes pendientes en almacén

- Se agregó una consulta de solicitudes presentadas y parcialmente recibidas, accesible desde documentos de inventario y entradas de Huentitán. Incluye todas las obras; exige los permisos `obra.devoluciones.view.access` y `obra.devoluciones.receive.access`.
- Cada partida muestra su origen, almacén de origen, condición declarada, cantidades solicitada y recibida, y saldo pendiente. Las recepciones no aplicadas no reducen el pendiente; los datos inconsistentes se señalan para revisión. El almacén de origen es una referencia y no determina el destino de la devolución.
- Esta consulta no confirma recepciones ni registra movimientos; la recepción física corresponde al punto 19.15. Se verificaron los permisos, búsqueda, estados, cantidades parciales, inconsistencias y compilación de vistas, sin modificar registros existentes ni producción.

## Punto 19.13: folio y solicitud imprimible

- Cada solicitud conserva el folio único `DEV-OB-...` generado al crearla. Desde la pestaña de materiales se puede abrir su versión imprimible con la cabecera compartida de los formatos de obra.
- El documento muestra obra, folio, estado, fechas y responsables, así como origen, cantidad solicitada, condición declarada y observaciones de cada partida. Incluye espacios físicos para entrega y recepción; aclara que la firma no sustituye la confirmación en el sistema.
- Los borradores se identifican expresamente como no presentados y las solicitudes canceladas muestran su estado y motivo. El documento no registra recepción ni cambia saldos.
- El acceso requiere el permiso `obra.devoluciones.view.access`, acceso a la obra y correspondencia entre obra y solicitud. Se verificó el render del borrador local `DEV-OB-00000003` con permiso simulado y se preservaron los registros de prueba.
- Veinticuatro pruebas de saldos y solicitudes, 78 comprobaciones correctas, incluyen impresión, estados, enlace y rechazo por falta de permiso u obra ajena. Sin migración adicional ni cambios en producción.

## Registro de pausas

Las marcas de inicio y fin de cada pausa se registran en UTC para que las continuaciones puedan comprobar el tiempo transcurrido sin depender de la zona horaria del proceso.

- Punto 1 completado. Pausa iniciada el 2026-11-05 a las 20:08:32 UTC; siguiente punto a partir de las 20:18:32 UTC (14:18:32, Ciudad de México).
- Pausa del punto 1 cumplida. Punto 2 iniciado después de las 20:18:32 UTC.
- Punto 2 completado. Pausa iniciada el 2026-11-05 a las 20:19:21 UTC; siguiente punto a partir de las 20:29:21 UTC (14:29:21, Ciudad de México).
- Pausa del punto 2 cumplida. Punto 3 iniciado después de las 20:29:21 UTC.
- Punto 3 completado. Pausa iniciada el 2026-11-05 a las 20:30:15 UTC; siguiente punto a partir de las 20:40:15 UTC (14:40:15, Ciudad de México).
- Pausa del punto 3 cumplida. Punto 4 iniciado después de las 20:40:15 UTC.
- Punto 4 completado. Pausa iniciada el 2026-11-05 a las 20:42:46 UTC; siguiente punto a partir de las 20:52:46 UTC (14:52:46, Ciudad de México).
- Pausa del punto 4 cumplida. Punto 5 iniciado después de las 20:52:46 UTC.
- Punto 5 completado. Pausa iniciada el 2026-11-05 a las 20:57:45 UTC; siguiente punto a partir de las 21:07:45 UTC (15:07:45, Ciudad de México).
- Pausa del punto 5 cumplida. Punto 6 iniciado después de las 21:07:45 UTC.
- Punto 6 completado. Pausa iniciada el 2026-11-05 a las 21:12:38 UTC; siguiente punto a partir de las 21:22:38 UTC (15:22:38, Ciudad de México).
- Pausa del punto 6 cumplida. Punto 7 iniciado después de las 21:22:38 UTC.
- Punto 7 completado. Pausa iniciada el 2026-11-05 a las 21:25:35 UTC; siguiente punto a partir de las 21:35:35 UTC (15:35:35, Ciudad de México).
- Pausa del punto 7 cumplida. Punto 8 iniciado después de las 21:35:35 UTC.
- Punto 8 completado. Pausa iniciada el 2026-11-05 a las 21:37:16 UTC; siguiente punto a partir de las 21:47:16 UTC (15:47:16, Ciudad de México).
- Pausa del punto 8 cumplida. Punto 9 iniciado después de las 21:47:16 UTC.
- Punto 9 completado. Pausa iniciada el 2026-11-05 a las 21:50:09 UTC; siguiente punto a partir de las 22:00:09 UTC (16:00:09, Ciudad de México).
- Pausa del punto 9 cumplida. Punto 10 iniciado después de las 22:00:09 UTC.
- Punto 10 completado. Pausa iniciada el 2026-11-05 a las 22:07:39 UTC; siguiente punto a partir de las 22:17:39 UTC (16:17:39, Ciudad de México).
- Pausa del punto 10 cumplida. Punto 11 iniciado después de las 22:17:39 UTC.
- Punto 11 completado. Pausa iniciada el 2026-11-05 a las 22:20:49 UTC; siguiente punto a partir de las 22:30:49 UTC (16:30:49, Ciudad de México).
- Pausa del punto 11 cumplida. Punto 12 iniciado después de las 22:30:49 UTC.
- Punto 12 completado. Pausa iniciada el 2026-11-05 a las 22:33:18 UTC; siguiente punto a partir de las 22:43:18 UTC (16:43:18, Ciudad de México).
- Pausa del punto 12 cumplida. Punto 13 iniciado después de las 22:43:18 UTC.
- Punto 13 completado. Pausa iniciada el 2026-11-05 a las 22:44:58 UTC; siguiente punto a partir de las 22:54:58 UTC (16:54:58, Ciudad de México).
- Pausa del punto 13 cumplida. Punto 14 reanudado el 2026-11-06 a solicitud del usuario.
- Punto 14 completado. Pausa iniciada el 2026-11-06 a las 15:34:52 UTC; siguiente punto a partir de las 15:44:52 UTC (09:44:52, Ciudad de México).
