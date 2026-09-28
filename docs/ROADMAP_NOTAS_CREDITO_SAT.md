# Roadmap - Modulo SAT: Notas de Credito

## Objetivo

Agregar una nueva opcion dentro del modulo SAT llamada **Notas de Credito**, para crear, administrar, timbrar, consultar, descargar y cancelar CFDI de egreso relacionados con facturas existentes.

El modulo debe reutilizar la integracion actual con FacturAPI, respetar los permisos finos del sistema y mantener separada la informacion fiscal de los saldos comerciales.

## Reglas base

- No crear una integracion paralela con FacturAPI.
- No modificar destructivamente el flujo actual de facturacion.
- No cambiar la logica de complementos de pago timbrados.
- No marcar una nota como cancelada hasta tener confirmacion fiscal.
- No considerar una nota de credito como cancelacion de la factura original.
- Separar siempre estado fiscal del CFDI y saldo comercial aplicado a la factura.
- Antes de crear migraciones, confirmar nombres reales de tablas, columnas, modelos y relaciones existentes.

## Permisos propuestos

- `sat.notas_credito.access`: permite ver el modulo.
- `sat.notas_credito.create`: permite crear borradores.
- `sat.notas_credito.edit`: permite editar borradores no timbrados.
- `sat.notas_credito.timbrar`: permite timbrar la nota.
- `sat.notas_credito.cancelar`: permite cancelar una nota timbrada.
- `sat.notas_credito.download`: permite descargar PDF/XML/acuse.

Checkpoint:

- [ ] Permisos validados contra el patron actual del proyecto.
- [ ] Confirmado si los permisos se crean manualmente o por migracion.
- [ ] Menu SAT envuelto con `sat.notas_credito.access`.

## Fase 1 - Barrido tecnico

Revisar la implementacion actual del modulo SAT antes de tocar codigo.

Archivos/zonas a revisar:

- Rutas SAT.
- Menu lateral SAT.
- Controladores de facturacion.
- Controladores de complementos de pago.
- Modelos SAT existentes.
- Migraciones de `sat_facturas`, complementos y CFDI descargados.
- Servicio actual de FacturAPI.
- Vistas de facturacion y complementos.
- Logica de saldos en obras/facturas/pagos.

Preguntas a resolver:

- Donde se debe considerar una factura como vigente?
- Que columna representa el estado fiscal real?
- Que tabla es la fuente principal para facturas emitidas?
- Como se guardan actualmente PDF/XML?
- Como se manejan errores de FacturAPI?
- Como se calcula hoy el saldo de una factura?

Checkpoint:

- [ ] Mapa de archivos relevante documentado.
- [ ] Fuente de verdad para facturas confirmada.
- [ ] Fuente de verdad para saldo comercial confirmada.
- [ ] Riesgos detectados antes de disenar tablas.

## Fase 2 - Diseno de almacenamiento

Propuesta inicial de tablas, sujeta al barrido real:

### `sat_notas_credito`

Campos sugeridos:

- `id`
- `sat_empresa_id`
- `cliente_id`
- `obra_id` nullable
- `facturapi_invoice_id`
- `facturapi_customer_id`
- `uuid`
- `serie`
- `folio`
- `tipo_comprobante` esperado: `E`
- `cfdi_version`
- `receptor_rfc`
- `receptor_nombre`
- `uso_cfdi`
- `forma_pago`
- `metodo_pago`
- `moneda`
- `subtotal`
- `descuento`
- `iva`
- `total`
- `estado`
- `estado_cancelacion`
- `motivo_cancelacion`
- `pdf_path`
- `xml_path`
- `acuse_cancelacion_path`
- `facturapi_response`
- `cancelacion_response`
- `created_by`
- `updated_by`
- timestamps

### `sat_nota_credito_facturas`

Relaciona una nota con una o varias facturas origen.

Campos sugeridos:

- `id`
- `sat_nota_credito_id`
- `sat_factura_id`
- `uuid_factura`
- `serie_factura`
- `folio_factura`
- `total_factura`
- `saldo_antes`
- `monto_aplicado`
- `saldo_despues`
- timestamps

### `sat_nota_credito_conceptos`

Guarda los conceptos incluidos en la nota.

Campos sugeridos:

- `id`
- `sat_nota_credito_id`
- `sat_factura_id` nullable
- `descripcion`
- `producto_servicio_key`
- `unidad_key`
- `cantidad`
- `precio_unitario`
- `descuento`
- `subtotal`
- `iva`
- `total`
- timestamps

Checkpoint:

- [ ] Nombres confirmados con convenciones existentes.
- [ ] Indices definidos para busquedas por cliente, factura, uuid y estado.
- [ ] Relaciones reversibles definidas.
- [ ] Sin duplicar informacion innecesaria salvo snapshots fiscales/comerciales.

## Fase 3 - Modelos y relaciones

Crear modelos solo despues de confirmar tablas.

Relaciones esperadas:

- `SatNotaCredito` pertenece a empresa SAT.
- `SatNotaCredito` pertenece a cliente.
- `SatNotaCredito` puede pertenecer a obra.
- `SatNotaCredito` tiene muchas facturas relacionadas.
- `SatNotaCredito` tiene muchos conceptos.
- `SatNotaCreditoFactura` pertenece a `SatFactura`.
- `SatFactura` puede tener muchas notas aplicadas.

Checkpoint:

- [ ] Relaciones cargan sin N+1 en listado.
- [ ] Casts JSON definidos para respuestas FacturAPI.
- [ ] Estados encapsulados en constantes o helpers.

## Fase 4 - Rutas, menu y controlador base

Agregar rutas bajo:

```txt
/sat/notas-credito
```

Acciones MVP:

- index
- create
- store
- show
- edit
- update
- timbrar
- pdf
- xml
- cancelar
- consultar cancelacion

Checkpoint:

- [ ] Link visible solo con `sat.notas_credito.access`.
- [ ] Rutas protegidas con permisos correspondientes.
- [ ] Controlador creado sin timbrado todavia.
- [ ] Vistas base cargan correctamente.

## Fase 5 - Listado

Crear pantalla principal con:

- filtros por cliente;
- filtros por estado;
- filtros por fecha;
- busqueda por folio, UUID o receptor;
- tabla con fecha, folio, cliente, total, estado fiscal, estado cancelacion y acciones.

Acciones por fila:

- ver detalle;
- editar si es borrador;
- timbrar si tiene permiso;
- descargar PDF/XML si existe;
- cancelar si esta timbrada y tiene permiso.

Checkpoint:

- [ ] Listado paginado.
- [ ] Acciones respetan permisos.
- [ ] No aparecen acciones invalidas para estados cerrados.

## Fase 6 - Formulario de borrador

Formulario para crear nota:

- empresa SAT;
- cliente/receptor;
- moneda;
- uso CFDI;
- forma de pago;
- motivo interno;
- facturas relacionadas;
- conceptos;
- subtotal, IVA y total calculados.

Checkpoint:

- [ ] Se puede guardar borrador sin timbrar.
- [ ] Se puede editar borrador.
- [ ] No se puede editar nota timbrada desde el formulario normal.

## Fase 7 - Buscador de facturas disponibles

Crear endpoint/pantalla auxiliar para buscar facturas timbradas disponibles.

Filtros minimos:

- cliente;
- empresa SAT;
- folio;
- UUID;
- fecha;
- saldo disponible.

Reglas:

- Solo facturas tipo ingreso.
- Solo facturas vigentes/timbradas.
- Excluir canceladas.
- Mostrar saldo considerando pagos y notas ya aplicadas.

Checkpoint:

- [ ] No lista facturas canceladas.
- [ ] No lista facturas sin saldo aplicable, salvo que se decida permitir consulta.
- [ ] El saldo mostrado coincide con la vista de facturacion/obra.

## Fase 8 - Validaciones de negocio

Validar antes de guardar/timbrar:

- La nota debe tener al menos una factura relacionada.
- La nota debe tener al menos un concepto.
- El total de la nota no debe exceder saldo disponible permitido.
- Facturas relacionadas deben pertenecer al mismo cliente.
- Facturas relacionadas deben pertenecer a la misma empresa emisora.
- No permitir timbrar dos veces la misma nota.

Checkpoint:

- [ ] Validaciones en backend.
- [ ] Mensajes claros en UI.
- [ ] Logs utiles para errores de timbrado.

## Fase 9 - Servicio de Notas de Credito

Crear servicio que arme el payload para FacturAPI usando la estructura actual del proyecto.

Responsabilidades:

- resolver datos de empresa;
- resolver cliente FacturAPI;
- armar CFDI tipo `E`;
- relacionar UUID de factura origen;
- armar conceptos;
- timbrar;
- guardar respuesta;
- descargar/guardar PDF y XML;
- registrar errores.

Checkpoint:

- [ ] No duplica credenciales.
- [ ] Reutiliza cliente/configuracion FacturAPI existente.
- [ ] Payload revisado contra facturas reales.
- [ ] Errores quedan en log y en estado de la nota.

## Fase 10 - Timbrado

Flujo:

1. Validar borrador.
2. Construir payload.
3. Enviar a FacturAPI.
4. Guardar `facturapi_invoice_id`, UUID, serie, folio y respuesta completa.
5. Descargar/guardar PDF y XML.
6. Cambiar estado a timbrada.
7. Aplicar saldo comercial.

Checkpoint:

- [ ] Nota timbrada aparece en listado.
- [ ] PDF abre correctamente.
- [ ] XML descarga correctamente.
- [ ] Factura origen refleja nota aplicada en saldo comercial.

## Fase 11 - Detalle

Pantalla de detalle:

- datos fiscales;
- datos del receptor;
- facturas relacionadas;
- conceptos;
- importes;
- PDF/XML;
- estado de cancelacion;
- historial de eventos.

Checkpoint:

- [ ] Folio/UUID visible.
- [ ] Facturas origen enlazadas.
- [ ] Documentos disponibles si existen.
- [ ] Acciones restringidas por permiso y estado.

## Fase 12 - Cancelacion

Permitir cancelacion de nota timbrada.

Reglas:

- Solicitar motivo.
- Enviar cancelacion a FacturAPI/SAT.
- No marcar cancelada hasta confirmacion.
- Guardar respuesta.
- Si se confirma cancelacion, revertir saldo comercial aplicado por la nota.

Checkpoint:

- [ ] Cancelacion solo visible con permiso.
- [ ] Estado pendiente de cancelacion soportado.
- [ ] Consulta posterior de cancelacion soportada.
- [ ] Acuse guardado si aplica.

## Fase 13 - Impacto en saldos

Definir helper/servicio para calcular saldo de factura considerando:

- total factura;
- pagos aplicados;
- notas de credito timbradas;
- notas canceladas excluidas;
- complementos de pago sin modificarlos.

Checkpoint:

- [ ] Saldo comercial consistente en obra/facturacion.
- [ ] Nota cancelada no reduce saldo.
- [ ] No se altera estado fiscal de factura origen.

## Fase 14 - Pruebas y verificacion

Validaciones manuales minimas:

- Crear borrador.
- Editar borrador.
- Relacionar factura vigente.
- Timbrar nota parcial.
- Timbrar nota total.
- Descargar PDF/XML.
- Cancelar nota.
- Consultar estado de cancelacion.
- Ver saldo de factura antes y despues.

Validaciones tecnicas:

- `php -l` en archivos modificados.
- `php artisan view:cache`.
- `php artisan view:clear`.
- `php tools/check_mojibake.php` en vistas/controladores tocados.
- `git diff --check`.
- `graphify update .`.

Checkpoint:

- [ ] Sin errores de sintaxis.
- [ ] Sin problemas de encoding.
- [ ] Sin espacios conflictivos en diff.
- [ ] Grafo actualizado.

## Orden recomendado de ejecucion

1. Barrido tecnico completo.
2. Confirmar permisos y nombres.
3. Disenar migraciones.
4. Crear modelos.
5. Agregar rutas/menu/index vacio.
6. Agregar formulario de borrador.
7. Agregar buscador de facturas.
8. Guardar borrador.
9. Timbrar.
10. PDF/XML.
11. Detalle.
12. Cancelacion.
13. Saldos comerciales.
14. Pruebas.

## Decisiones pendientes

- Serie que se usara para notas de credito.
- Si una nota puede aplicar a multiples facturas desde el MVP.
- Si se permitira nota sobre factura pagada para generar saldo a favor.
- Si el permiso de timbrado sera separado de creacion.
- Si los permisos se crearan manualmente o por migracion.
- Donde se mostrara el impacto de notas dentro de obras/facturacion.

## Resultado Fase 1 - Barrido tecnico ejecutado

Fecha de barrido: 2026-09-25.

### Mapa real encontrado

Rutas principales:

- `routes/web.php` contiene el grupo SAT bajo `prefix('sat')` y `name('sat.')`.
- Submodulos actuales con permiso fino:
  - `sat.empresas.access` para `/sat/empresas`.
  - `sat.solicitudes.access` para `/sat/descargas`.
  - `sat.documentos.access` para `/sat/cfdis`.
  - `sat.borradores.access` para `/sat/borradores`.
  - `sat.facturacion.access` para `/sat/facturacion`.
- Dentro de facturacion, el timbrado usa permiso adicional `sat.cfdi_autoriza.access`.
- `complementos-pago` existe como submodulo bajo SAT, pero hoy no tiene middleware de permiso fino propio en rutas.
- `catalogos` existe bajo SAT, pero hoy no tiene middleware de permiso fino propio en rutas.

Controladores SAT relevantes:

- `SatFacturacionController`: listado, creacion, borradores, timbrado, relacionables, detalle, PDF/XML/ZIP, envio por correo/WhatsApp, cancelacion y sincronizacion de cancelacion.
- `SatFacturaPagoController`: complementos de pago desde detalle de factura.
- `SatComplementoPagoController`: modulo/listado independiente de complementos de pago.
- `SatFacturaBorradorController`: listado simple de borradores CFDI.
- `SatCfdiController`: CFDI descargados del SAT.
- `SatEmpresaController`: empresas SAT y solicitudes de documentos.
- `SatDownloadController`: solicitudes de descarga masiva.
- `SatCatalogoController`: conceptos SAT y busqueda de catalogos.

Modelos relevantes:

- `SatFactura`: fuente principal de CFDI emitidos desde FacturAPI.
- `SatFacturaConcepto`: conceptos de facturas emitidas.
- `SatFacturaPago`: complementos de pago emitidos para `sat_facturas`.
- `SatFacturaBorrador`: borradores de CFDI de facturacion.
- `SatCfdi`: CFDI descargados del SAT.
- `SatCfdiConcepto`: conceptos de CFDI descargados.
- `SatCfdiPago`: pagos capturados sobre CFDI descargados.
- `ObraFacturaPago`: pagos administrativos de facturas ligadas a obra.
- `ObraFacturaBorrador`: borradores operativos de facturacion desde obras.

Vistas relevantes:

- `resources/views/layouts/admin.blade.php`: menu SAT.
- `resources/views/sat/facturacion/index.blade.php`: listado de facturacion.
- `resources/views/sat/facturacion/create.blade.php`: formulario de CFDI.
- `resources/views/sat/facturacion/show.blade.php`: detalle, pagos, archivos, cancelacion.
- `resources/views/sat/complementos-pago/index.blade.php`: listado de complementos.
- `resources/views/sat/complementos-pago/create.blade.php`: alta de complemento.
- `resources/views/sat/borradores/index.blade.php`: listado de borradores SAT.
- `resources/views/obras/edit.blade.php`: tabla de facturacion de obra y saldos.

### Fuente de verdad detectada

Para notas de credito emitidas desde el sistema, la fuente operativa debe partir de `sat_facturas`, no de `sat_cfdis`.

- `sat_facturas` almacena CFDI emitidos por FacturAPI, con `tipo_comprobante`, UUID, serie, folio, estado, PDF/XML y respuesta FacturAPI.
- `sat_cfdis` almacena CFDI descargados del SAT; puede contener tambien documentos emitidos, pero funciona como visor/relacionador de descargas, no como flujo principal de emision.
- `sat_factura_pagos` guarda complementos de pago ligados a una `SatFactura`. No se guardan como `SatFactura`, aunque FacturAPI los devuelve como invoices tipo `P`.

### Tablas/campos utiles ya existentes

`sat_facturas` ya tiene campos suficientes para guardar CFDI tipo `E`:

- `tipo_comprobante` con default `I`, comentario `I, E, P, T, N`.
- `facturapi_invoice_id`, `facturapi_customer_id`, `uuid`, `serie`, `folio`.
- datos fiscales del receptor.
- `metodo_pago`, `forma_pago`, `moneda`, `tipo_cambio`.
- `subtotal`, `descuento`, `iva`, `retenciones`, `total`.
- `estado`, `fecha_emision`, `fecha_timbrado`, `fecha_cancelacion`.
- `xml_path`, `pdf_path`, `facturapi_response`, `error_message`.

`sat_factura_conceptos` ya cubre conceptos con:

- descripcion, cantidad, unidad.
- claves SAT.
- precio, descuento, subtotal, IVA, retenciones, total.
- `taxes` y `facturapi_payload`.

Aun asi, para notas de credito falta almacenar la relacion comercial/fiscal contra factura(s) origen y el monto aplicado por factura.

### Saldos actuales

En `ObraController::facturasSatObra` el saldo de facturas de obra se calcula asi:

- Reune `SatFactura` y `SatCfdi` ligadas a la obra.
- Agrupa `ObraFacturaPago` por UUID.
- Si la factura es PPD, el pagado mostrado usa complementos fiscales (`SatFacturaPago`) en estados `timbrado` o `registrado`.
- Si la factura no es PPD, el pagado mostrado usa pagos administrativos (`ObraFacturaPago`).
- El saldo actual es `total - pagado`.
- No existe aun resta por notas de credito.

Impacto para notas:

- Una nota de credito debe reducir saldo comercial, pero no debe tratarse como pago.
- Para PPD, una nota timbrada debe reducir el saldo antes o junto al calculo de complementos, sin modificar complementos ya timbrados.
- Para PUE, una nota debe reducir saldo comercial igual que en PPD, pero separada de pagos administrativos.

### Cancelacion actual

Facturas emitidas:

- `SatFacturacionController::cancelar` cambia a `cancelacion_solicitada` antes de llamar a FacturAPI.
- Si FacturAPI responde error, vuelve a `timbrada` y guarda `error_message`.
- `sincronizarCancelacion` consulta estatus con FacturAPI.
- `actualizarEstadoLocalDesdeFacturapi` cambia a `cancelada` solo cuando `status === canceled`; si la cancelacion esta pending/verifying, conserva `cancelacion_solicitada`.

Complementos de pago:

- `SatFacturaPagoController::cancelar` marca `cancelado` al recibir respuesta exitosa del DELETE.
- Al cancelar complemento, desliga pagos internos de obra poniendo `sat_factura_pago_id = null`.

Recomendacion para notas:

- Seguir el patron prudente de `SatFactura`, no el de complemento, porque la nota tambien es CFDI y el requerimiento pide no marcar cancelada hasta confirmacion.

### Integracion FacturAPI actual

Hay dos estilos mezclados:

- Facturacion usa `App\Services\Facturacion\FacturapiService`, que envuelve el SDK oficial `Facturapi\Facturapi`.
- Complementos de pago usan `Http::withBasicAuth(config('services.facturapi.secret_key'), '')` directo contra `https://www.facturapi.io/v2/invoices`.

Recomendacion para notas:

- No crear tercera forma.
- Preferible extraer/reutilizar una capa comun para crear invoices, descargar PDF/XML y cancelar, o al menos usar una de las dos rutas de forma consistente.
- Como el SDK ya funciona para facturas, Notas de Credito deberia intentar reutilizar ese camino salvo que el payload tipo `E` requiera llamada HTTP directa.

### Permisos

Hallazgo:

- Los permisos SAT finos usados por rutas/menu no estan centralizados en `RolesAndPermissionsSeeder`.
- Existe una migracion puntual para `sat.borradores.access`.
- El usuario ha indicado previamente que algunos permisos los creara manualmente si son simples.

Decision pendiente:

- Para `sat.notas_credito.*`, confirmar si se crean manualmente o con migracion.

### Riesgos detectados

- Riesgo de duplicar logica de timbrado si se copia `SatFacturacionController::store` completo.
- Riesgo de mezclar `sat_cfdis` descargados con `sat_facturas` emitidas como fuente operativa.
- Riesgo de calcular saldos incorrectos si la nota se resta como pago.
- Riesgo de no reflejar notas en `ObraController::facturasSatObra`, dejando saldos inflados en obras.
- Riesgo de permisos incompletos si solo se agrega acceso al menu sin proteger rutas/acciones.
- Riesgo de cancelacion fiscal incorrecta si se marca cancelada antes de confirmar estatus SAT/FacturAPI.

### Checkpoints Fase 1

- [x] Mapa de archivos relevante documentado.
- [x] Fuente de verdad para facturas confirmada: `sat_facturas`.
- [x] Fuente de verdad para saldo comercial identificada: calculo actual en `ObraController::facturasSatObra` + `ObraFacturaPago` + `SatFacturaPago`.
- [x] Riesgos detectados antes de disenar tablas.

### Siguiente paso recomendado

Fase 2 debe disenar almacenamiento considerando dos opciones:

1. Guardar las notas directamente en `sat_facturas` como `tipo_comprobante = E` y agregar tablas puente para aplicar notas a facturas origen.
2. Crear tabla propia `sat_notas_credito` y relacionarla con `sat_facturas`, duplicando snapshots fiscales necesarios.

Por consistencia y menor duplicidad, la opcion inicial recomendada es usar `sat_facturas` para el CFDI emitido y crear una tabla puente tipo `sat_factura_notas_credito` o `sat_nota_credito_facturas` para aplicacion contra facturas origen. Esta decision debe confirmarse en Fase 2.
