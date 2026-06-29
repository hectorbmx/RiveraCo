# Roadmap: Complementos de pago SAT / Facturapi

## Objetivo

Crear un modulo formal de complementos de pago para facturas emitidas desde Facturapi, separado del listado general de facturas y conectado con el control interno de pagos de obras.

La meta es que las facturas `PPD` puedan generar, consultar y descargar su CFDI tipo `P` desde una vista dedicada, y que el usuario pueda ver claramente que pagos estan pendientes por complementar.

Vista origen:

- Local: `http://127.0.0.1:8000/sat/facturacion`

Vista propuesta:

- Local: `http://127.0.0.1:8000/sat/complementos-pago`

## Contexto operativo

Regla fiscal base:

- Una factura con metodo de pago `PUE` puede registrarse como cobrada internamente sin complemento de pago.
- Una factura con metodo de pago `PPD` requiere un CFDI tipo `P` con complemento de recepcion de pagos cuando el cliente paga.

Separacion conceptual:

- Pago interno: control operativo de cobranza, banco, referencia, fecha, evidencia fisica y relacion con obra.
- Complemento de pago SAT: CFDI timbrado via Facturapi que acredita fiscalmente el pago de una factura `PPD`.

## Hallazgos iniciales

Fecha de investigacion: 2026-06-29.

Ya existe una base funcional:

- Modelo: `App\Models\SatFacturaPago`
- Tabla: `sat_factura_pagos`
- Controlador: `App\Http\Controllers\Sat\SatFacturaPagoController`
- Rutas existentes:
  - `POST /sat/facturacion/{factura}/pagos`
  - `GET /sat/facturacion/pagos/{pago}`
  - `GET /sat/facturacion/pagos/{pago}/xml`
  - `GET /sat/facturacion/pagos/{pago}/pdf`
  - `POST /sat/facturacion/pagos/{pago}/cancelar`
- En `resources/views/sat/facturacion/show.blade.php` ya existe modal `Registrar pago` para facturas PPD.
- El controlador ya arma payload Facturapi tipo `P` y descarga XML/PDF.
- `obra_factura_pagos` ya tiene:
  - `requiere_complemento_pago`
  - `sat_factura_pago_id`
  - evidencia fisica del pago.

Riesgos detectados para auditar:

- Confirmar si las rutas `show` y `cancelar` de `SatFacturaPagoController` existen realmente como metodos completos.
- Confirmar si `sat_factura_pagos` guarda todos los campos necesarios para listar y auditar complementos.
- Confirmar si el payload actual de Facturapi cubre impuestos, parcialidades, saldos e importes para todos los casos.
- Confirmar como se vincula un pago interno de obra con el complemento timbrado.

## Fuente de verdad

Fuente principal para generar complementos:

- `sat_facturas`

Motivo:

- Son facturas emitidas por nosotros desde Facturapi.
- Guardan datos necesarios para timbrar:
  - `facturapi_invoice_id`
  - `facturapi_customer_id`
  - `uuid`
  - `metodo_pago`
  - `forma_pago`
  - `total`
  - `moneda`
  - `tipo_cambio`

Fuente secundaria / consulta:

- `sat_cfdis`

Uso sugerido:

- Validacion o consulta fiscal desde scraper SAT.
- No debe ser la fuente principal para timbrar complementos de facturas emitidas por Facturapi.

Fuente operativa interna:

- `obra_factura_pagos`

Uso sugerido:

- Registrar evidencia, banco, referencia y cobro operativo.
- Puede alimentar la creacion del complemento si el pago corresponde a factura `PPD`.
- Debe poder vincularse con `sat_factura_pagos`.

## Vista principal: Complementos de pago

Ruta propuesta:

- `GET /sat/complementos-pago`

Nombre de ruta sugerido:

- `sat.complementos-pago.index`

Acceso:

- Boton `Complementos de pago` junto a `Nueva Factura` en `/sat/facturacion`.

Componentes:

- KPIs:
  - Complementos timbrados este mes.
  - Monto complementado este mes.
  - Facturas PPD pendientes por complementar.
  - Monto PPD pendiente por complementar.
- Filtros:
  - Cliente.
  - Fecha desde / hasta.
  - Estado.
  - Factura / UUID.
  - Solo pendientes por complementar.
- Tabla:
  - Fecha pago.
  - Cliente.
  - Factura relacionada.
  - UUID factura.
  - UUID complemento.
  - Parcialidad.
  - Monto pagado.
  - Saldo insoluto.
  - Forma de pago.
  - Estado.
  - PDF.
  - XML.
  - Acciones.

Botones:

- `+ Agregar pago`
- `Ver factura`
- `Ver PDF`
- `XML`
- `Cancelar complemento` en fase posterior.

## Formulario: Agregar pago / generar complemento

Ruta propuesta:

- `GET /sat/complementos-pago/create`
- `POST /sat/complementos-pago`

Flujo:

1. Seleccionar empresa SAT si aplica.
2. Seleccionar cliente.
3. Mostrar facturas `PPD` timbradas y con saldo pendiente.
4. Seleccionar una factura en primera version.
5. Capturar datos del pago.
6. Calcular parcialidad, saldo anterior y saldo insoluto.
7. Timbrar CFDI tipo `P` en Facturapi.
8. Guardar `sat_factura_pagos`.
9. Guardar PDF/XML.
10. Si viene de un pago interno de obra, vincular `obra_factura_pagos.sat_factura_pago_id`.

Campos:

- Cliente.
- Factura PPD.
- Fecha de pago.
- Forma de pago SAT.
- Monto pagado.
- Moneda.
- Tipo de cambio.
- Referencia / numero de operacion.
- Cuenta ordenante, opcional.
- Cuenta beneficiaria, opcional.
- Observaciones internas.

Primera version:

- Una factura por complemento.

Fase posterior:

- Un complemento puede incluir multiples documentos relacionados del mismo cliente.

## Modelo de datos

Tabla existente:

- `sat_factura_pagos`

Checkpoint de auditoria:

- Revisar si la tabla actual ya tiene:
  - `sat_factura_id`
  - `facturapi_invoice_id`
  - `uuid`
  - `fecha_pago`
  - `forma_pago`
  - `moneda`
  - `tipo_cambio`
  - `monto`
  - `saldo_anterior`
  - `saldo_insoluto`
  - `numero_parcialidad`
  - `estado`
  - `xml_path`
  - `pdf_path`
  - `facturapi_response`
  - `error_message`

Si faltan campos, migracion sugerida:

- `referencia`
- `cuenta_ordenante`
- `cuenta_beneficiaria`
- `observaciones`
- `creado_por`
- `cancelado_por`
- `cancelado_at`
- `cancelacion_motivo`
- `obra_factura_pago_id` o mantener relacion desde `obra_factura_pagos.sat_factura_pago_id`.

## Checkpoints de ejecucion

### Fase 1: Auditoria tecnica

- [x] Revisar modelo `SatFacturaPago`.
- [x] Revisar migracion `sat_factura_pagos`.
- [x] Revisar rutas existentes en `routes/web.php`.
- [x] Revisar `SatFacturaPagoController`.
- [x] Confirmar si existen metodos `show` y `cancelar`.
- [x] Revisar vista `sat/facturacion/show.blade.php`.
- [x] Revisar modelo `ObraFacturaPago`.
- [x] Revisar como se calcula saldo pendiente de facturas PPD.
- [ ] Probar con una factura PPD local si el complemento actual timbra correctamente.

Salida esperada:

- Lista de campos existentes.
- Lista de huecos funcionales.
- Decision de reutilizar controlador actual o crear `SatComplementoPagoController`.

#### Hallazgos Fase 1

Fecha de ejecucion: 2026-06-29.

Base existente confirmada:

- Modelo `App\Models\SatFacturaPago`.
- Tabla `sat_factura_pagos`.
- Relacion `SatFactura::pagos()`.
- Controlador `App\Http\Controllers\Sat\SatFacturaPagoController`.
- Modal actual en `resources/views/sat/facturacion/show.blade.php`.
- Tabla actual de complementos dentro del detalle de una factura.
- Descarga XML/PDF ya implementada para `SatFacturaPago`.

Campos existentes en `sat_factura_pagos`:

- `sat_factura_id`
- `facturapi_invoice_id`
- `uuid`
- `fecha_pago`
- `forma_pago`
- `moneda`
- `tipo_cambio`
- `monto`
- `saldo_anterior`
- `saldo_insoluto`
- `numero_parcialidad`
- `estado`
- `xml_path`
- `pdf_path`
- `facturapi_response`
- `error_message`
- timestamps

Flujo actual:

- Desde `sat/facturacion/{factura}` se muestra boton `Registrar pago` solo si:
  - `metodo_pago` es `PPD`.
  - la factura no esta cancelada.
  - tiene saldo pendiente.
- El modal captura:
  - fecha de pago
  - forma de pago
  - monto
- Al guardar, `SatFacturaPagoController@store`:
  - valida factura `timbrada`.
  - valida factura `PPD`.
  - valida `facturapi_invoice_id` y `uuid`.
  - calcula saldo anterior, saldo insoluto y parcialidad.
  - arma payload Facturapi tipo `P`.
  - guarda `SatFacturaPago`.
  - descarga XML y PDF.

Huecos detectados:

- Las rutas `sat.facturacion.pagos.show` y `sat.facturacion.pagos.cancelar` existen, pero `SatFacturaPagoController` no tiene metodos `show` ni `cancelar`.
- `resources/views/sat/facturacion/cliente.blade.php` ya enlaza a `sat.facturacion.pagos.show`, por lo que ese link puede fallar actualmente.
- No existe vista global para listar todos los complementos.
- No existe ruta `/sat/complementos-pago`.
- No hay permisos especificos `.access` para complementos de pago SAT.
- `ObraFacturaPago` tiene columna `sat_factura_pago_id`, pero el modelo no tiene relacion `complementoPago()`.
- El pago interno de obra marca `requiere_complemento_pago` cuando la factura es `PPD`, pero no queda conectado automaticamente con `sat_factura_pagos`.
- La suma de pagos fiscales usa estados `timbrado` y, en algunos calculos internos del controlador, tambien considera `registrado`; se debe homologar el criterio antes del index.
- El formulario actual no captura referencia, cuenta ordenante, cuenta beneficiaria ni observaciones fiscales.
- No se probo timbrado real en esta fase para evitar consumir Facturapi sin un caso confirmado.

Decision tecnica recomendada:

- Reutilizar `SatFacturaPago` como modelo principal.
- Completar `SatFacturaPagoController` para acciones puntuales existentes: `show`, `cancelar`, `xml`, `pdf`.
- Crear un controlador nuevo `SatComplementoPagoController` para el modulo global:
  - `index`
  - `create`
  - `store`
- En una fase posterior, extraer el armado del payload tipo `P` a un servicio para que el detalle de factura y el modulo global compartan la misma logica.

### Fase 2: Navegacion y vista index

- [x] Agregar boton `Complementos de pago` junto a `Nueva Factura` en `/sat/facturacion`.
- [x] Crear ruta index `/sat/complementos-pago`.
- [x] Crear controlador o metodo index.
- [x] Crear vista `resources/views/sat/complementos-pago/index.blade.php`.
- [x] Listar complementos desde `sat_factura_pagos`.
- [x] Agregar descargas PDF/XML.
- [x] Agregar filtros base.
- [x] Agregar KPIs.

Checkpoint:

- El usuario puede ver todos los complementos ya generados sin entrar factura por factura.

#### Hallazgos Fase 2

Fecha de ejecucion: 2026-06-29.

Implementado:

- Controlador:
  - `App\Http\Controllers\Sat\SatComplementoPagoController`
- Ruta:
  - `GET /sat/complementos-pago`
  - Nombre: `sat.complementos-pago.index`
- Vista:
  - `resources/views/sat/complementos-pago/index.blade.php`
- Navegacion:
  - Boton `Complementos de pago` junto a `Nueva Factura` en `/sat/facturacion`.
- KPIs:
  - Complementos timbrados este mes.
  - Monto complementado este mes.
  - Facturas PPD pendientes por complementar.
  - Monto PPD pendiente por complementar.
- Filtros:
  - Busqueda por UUID, factura, cliente o RFC.
  - Cliente.
  - Estado.
  - Fecha desde / hasta.
- Tabla:
  - Fecha pago.
  - Cliente.
  - Factura relacionada.
  - UUID complemento.
  - Parcialidad.
  - Monto.
  - Saldo insoluto.
  - Estado.
  - Acciones XML/PDF.

Notas:

- El boton `+ Agregar pago` queda visible pero deshabilitado hasta Fase 4.
- El modulo global reutiliza `sat_factura_pagos` y las rutas existentes de descarga XML/PDF.

### Fase 3: Pendientes por complementar

- [x] Crear query de facturas `PPD` timbradas.
- [x] Calcular pagado acumulado desde `sat_factura_pagos`.
- [x] Calcular saldo pendiente.
- [x] Mostrar facturas pendientes por complementar.
- [x] Distinguir:
  - Sin pagos.
  - Parcialmente pagada.
  - Complementada total.
  - Cancelada.

Checkpoint:

- La vista muestra cuales facturas `PPD` necesitan complemento y cuanto falta por complementar.

#### Hallazgos Fase 3

Fecha de ejecucion: 2026-06-29.

Implementado:

- Se agrego seccion `Pendientes por complementar` en `/sat/complementos-pago`.
- Se consultan facturas `PPD` con estado `timbrada`.
- Se calcula:
  - total de factura.
  - monto complementado desde `sat_factura_pagos` con estado `timbrado`.
  - saldo por complementar.
  - pagos internos pendientes de ligar desde `obra_factura_pagos`.
- Se muestran pagos internos que:
  - tienen `requiere_complemento_pago = true`.
  - no tienen `sat_factura_pago_id`.
- Se agrego relacion:
  - `ObraFacturaPago::complementoPago()`.
- La tabla muestra:
  - factura.
  - cliente.
  - obra.
  - total.
  - complementado.
  - saldo.
  - pago interno pendiente.
  - estado `Sin complemento` o `Parcial`.
  - accion `Ver factura`.

Nota:

- `Complementada total` no aparece en la tabla de pendientes porque queda fuera del listado al tener saldo fiscal cero.
- La conexion automatica de `obra_factura_pagos.sat_factura_pago_id` queda para Fase 6.

### Fase 4: Formulario agregar pago

- [x] Crear ruta `create`.
- [x] Crear formulario de complemento.
- [ ] Selector de cliente.
- [x] Selector de facturas PPD pendientes.
- [x] Autollenar total, pagado, saldo, parcialidad siguiente.
- [x] Validar monto no mayor al saldo.
- [x] Usar catalogo centralizado `sat_catalogs.formas_pago`.

Checkpoint:

- El usuario puede capturar un pago PPD desde el modulo nuevo antes de timbrar.

#### Hallazgos Fase 4

Fecha de ejecucion: 2026-06-29.

Implementado:

- Ruta:
  - `GET /sat/complementos-pago/create`
  - Nombre: `sat.complementos-pago.create`
- Vista:
  - `resources/views/sat/complementos-pago/create.blade.php`
- Navegacion:
  - El boton `+ Agregar pago` en `/sat/complementos-pago` ya abre el formulario.
  - Cada factura pendiente tiene accion `Agregar pago` con la factura preseleccionada.
- Formulario:
  - Selector de factura PPD pendiente.
  - Fecha de pago.
  - Forma de pago desde `sat_catalogs.formas_pago`.
  - Monto pagado.
  - Resumen de cliente, RFC, UUID, obra, total, complementado y saldo.
  - Sugerencia de monto con base en pago interno pendiente, sin exceder saldo fiscal.

Notas:

- No se agrego selector separado de cliente porque el flujo actual selecciona directamente factura PPD pendiente; el cliente se filtra/visualiza desde la factura.
- El boton `Continuar a timbrado` queda deshabilitado hasta Fase 5.
- La validacion backend y el `POST` real se implementaran con el timbrado Facturapi en Fase 5.

### Fase 5: Timbrado Facturapi

- [x] Reutilizar o extraer logica actual de `SatFacturaPagoController@store`.
- [ ] Crear servicio para payload CFDI tipo `P` si conviene.
- [x] Validar datos obligatorios antes de llamar Facturapi.
- [x] Timbrar CFDI tipo `P`.
- [x] Guardar `sat_factura_pagos`.
- [x] Descargar y guardar XML/PDF.
- [x] Manejar errores de Facturapi con mensajes claros.

Checkpoint:

- Un complemento se timbra desde el modulo nuevo y queda visible en index.

#### Hallazgos Fase 5

Fecha de ejecucion: 2026-06-29.

Implementado:

- Ruta:
  - `POST /sat/complementos-pago`
  - Nombre: `sat.complementos-pago.store`
- Controlador:
  - `SatComplementoPagoController@store`
- Validaciones:
  - factura obligatoria y existente.
  - fecha de pago obligatoria.
  - forma de pago obligatoria.
  - monto mayor a cero.
  - factura debe estar `timbrada`.
  - factura debe ser `PPD`.
  - factura debe tener UUID, cliente Facturapi e ID de Facturapi.
  - monto no puede exceder saldo pendiente.
- Timbrado:
  - Se genera payload CFDI tipo `P`.
  - Se llama a `https://www.facturapi.io/v2/invoices`.
  - Se guarda `SatFacturaPago`.
  - Se descargan XML/PDF si Facturapi devuelve ID.
- UI:
  - El formulario `create` ahora envia POST real.
  - El boton final cambia a `Generar complemento`.

Nota:

- Se reutilizo la logica actual dentro del nuevo controlador para avanzar rapido.
- Queda pendiente extraer un servicio compartido para evitar duplicacion con `SatFacturaPagoController@store`.
- La conexion con pagos internos de obra queda para Fase 6.

### Fase 6: Conexion con pagos internos de obra

- [x] Detectar pagos internos con `requiere_complemento_pago = true`.
- [x] Mostrar acceso para generar complemento desde esos pagos.
- [x] Si se genera complemento desde un pago interno, guardar `sat_factura_pago_id`.
- [x] Evitar doble complemento para el mismo pago interno.
- [x] Mostrar en tab facturacion de obra si el complemento ya fue generado.

Checkpoint:

- Lo cobrado en obra y lo timbrado fiscalmente quedan conectados.

#### Hallazgos Fase 6

Fecha de ejecucion: 2026-06-29.

Implementado:

- Relacion:
  - `SatFacturaPago::pagosInternosObra()`
- Al timbrar un complemento desde `SatComplementoPagoController@store`, se intenta ligar automaticamente con pagos internos en `obra_factura_pagos`.
- Regla de liga automatica:
  - Solo pagos internos con `requiere_complemento_pago = true`.
  - Solo pagos internos sin `sat_factura_pago_id`.
  - Deben tener el mismo `factura_uuid`.
  - Se toman en orden por `fecha_pago` e `id`.
  - La suma debe coincidir exactamente con el monto del complemento.
  - Si no coincide, no se liga automaticamente para evitar una relacion incorrecta.
- En la tabla de complementos registrados se muestra si el complemento tiene pagos internos ligados y el monto ligado.
- En el tab `facturacion` de obra se muestra:
  - `Requiere complemento` cuando hay pagos internos PPD sin liga fiscal.
  - `Complemento timbrado` cuando el pago interno ya tiene `sat_factura_pago_id`.

Pendiente:

- Considerar una accion manual para ligar/desligar cuando el monto no coincida exacto.

### Fase 7: Detalle de complemento

- [x] Implementar `show` si no existe.
- [x] Mostrar payload/respuesta resumida.
- [x] Mostrar factura relacionada.
- [x] Mostrar parcialidad, saldos y estado.
- [x] Botones PDF/XML.
- [x] Link a obra si existe relacion.

Checkpoint:

- Cualquier complemento tiene pantalla propia de auditoria.

#### Hallazgos Fase 7

Fecha de ejecucion: 2026-06-29.

Implementado:

- Metodo:
  - `SatFacturaPagoController@show`
- Vista:
  - `resources/views/sat/facturacion/pagos/show.blade.php`
- La pantalla muestra:
  - UUID del complemento.
  - monto pagado.
  - saldo anterior.
  - saldo insoluto.
  - estado.
  - factura relacionada.
  - cliente.
  - obra relacionada.
  - pagos internos ligados.
  - fecha, forma de pago, parcialidad y Facturapi ID.
  - botones XML/PDF.
  - respuesta Facturapi en JSON para auditoria.
- En `/sat/complementos-pago` se agrego accion `Ver` hacia el detalle.

### Fase 8: Cancelacion

- [x] Auditar flujo Facturapi para cancelar CFDI tipo `P`.
- [x] Implementar ruta de cancelacion.
- [x] Guardar motivo, usuario y fecha.
- [x] Actualizar estado local.
- [x] Recalcular saldo de factura relacionada.

Checkpoint:

- Un complemento cancelado deja de contar como pagado vigente.

#### Hallazgos Fase 8

Fecha de ejecucion: 2026-06-29.

Implementado:

- Migracion:
  - `2026_06_29_120000_add_cancelacion_fields_to_sat_factura_pagos_table.php`
- Campos agregados:
  - `fecha_cancelacion`
  - `cancelado_por`
  - `motivo_cancelacion`
  - `sustitucion_uuid`
- Modelo:
  - `SatFacturaPago::canceladoPor()`
- Metodo:
  - `SatFacturaPagoController@cancelar`
- Flujo:
  - Valida complemento timbrado.
  - Llama `DELETE /v2/invoices/{id}` en Facturapi.
  - Guarda estado `cancelado`.
  - Guarda motivo, usuario y fecha.
  - Desliga pagos internos de obra para que vuelvan a quedar pendientes de complemento.
- UI:
  - Boton `Cancelar complemento` en detalle.
  - Accion `Cancelar` en tabla global de complementos.

Nota:

- Al quedar en estado `cancelado`, el complemento deja de contar en saldos porque los calculos vigentes usan estado `timbrado`.

### Fase 9: Permisos

- [ ] Definir permisos `.access`.
- [ ] Agregar al seeder.
- [ ] Asignar roles.
- [ ] Proteger rutas.
- [ ] Ocultar botones por permiso.

Permisos sugeridos:

- `sat_complementos_pago.view.access`
- `sat_complementos_pago.create.access`
- `sat_complementos_pago.cancel.access`
- `sat_complementos_pago.download.access`

Asignacion inicial sugerida:

- `super-admin`: todos.
- `admin-rivera`: todos.
- Roles operativos de facturacion: view, create, download.

Checkpoint:

- El modulo respeta el mismo patron granular `.access` del sistema.

## Orden recomendado

1. Ejecutar Fase 1 completa.
2. Implementar Fase 2 para dar visibilidad a lo que ya existe.
3. Implementar Fase 3 para detectar pendientes.
4. Migrar el flujo actual de registro/timbrado al modulo nuevo.
5. Conectar pagos internos de obra.

## Preguntas abiertas

- Confirmar si el nombre visible sera `Complementos de pago` o `Pagos clientes`.
- Confirmar si en primera version se permitira solo una factura por complemento.
- Confirmar si el pago interno de obra debe ser obligatorio antes de timbrar complemento.
- Confirmar si se requiere notificacion cuando una factura PPD queda pendiente de complemento.
- Confirmar si los complementos deben vincularse tambien con `sat_cfdis` cuando el scraper detecte el CFDI tipo `P`.
