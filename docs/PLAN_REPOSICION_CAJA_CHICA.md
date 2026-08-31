# Plan tecnico y funcional: Reposicion de caja chica

Fecha: 2026-08-28

> Documento de planeacion y bitacora de avance del feature.

## 1. Objetivo

Crear el MVP de **Reposicion de caja chica** para registrar gastos hechos desde almacen o por obra, revisarlos individualmente en oficina y generar una relacion semanal/caratula con importes registrados, autorizados, rechazados y pendientes. El monto autorizado sera la base futura para programacion de pagos como reposicion interna.

## 2. Diagnostico del proyecto actual

El proyecto es una app Laravel con Blade, rutas web, API v1 para residente, modelos Eloquent, migraciones, PDFs y permisos/roles tipo Spatie.

Modulos reutilizables detectados:

- `app/Models/Obra.php`: relacion con obra, cliente, area, responsable, presupuestos y CFDI.
- `app/Models/Almacen.php`: catalogo `almacenes` con `nombre`, `tipo`, `obra_id` futuro y `activo`.
- `app/Models/ObraReposicionGasto.php`: encabezado existente en `obra_reposicion_gastos`.
- `app/Models/ObraReposicionGastoDetalle.php`: detalle existente con proveedor, RFC, UUID, fecha, monto, comprobante, nota y evidencia.
- `app/Http/Controllers/ObraReposicionGastoController.php`: flujo web para reposiciones de obra, PDF, programar, aprovisionar, autorizar y rechazar.
- `app/Http/Controllers/Api/V1/ResidenteReposicionGastoController.php`: API residente para listar, crear y consultar reposiciones de obra.
- `resources/views/obras/reposicion-gastos/*`: vistas y PDFs existentes para caja chica, gastos varios, viaticos y mixta.
- `app/Models/SatCfdi.php` y `app/Models/SatCfdiConcepto.php`: base CFDI con UUID, RFC, emisor/receptor, fechas, conceptos y totales.
- `app/Services/Sat/SatMassDownloadService.php`: parser XML actual con `SimpleXMLElement`, persistencia de CFDI/conceptos y deduplicacion por UUID.
- `app/Models/Proveedor.php`: proveedores legacy, busqueda natural por `rfc` y relacion `facturasSat()`.
- `app/Models/Producto.php` y `producto_proveedor`: base para equivalencias proveedor-producto y precios.
- `app/Models/SatCfdiProgramacion.php` y `ProgramacionPagosController`: programacion de pagos de CFDI con revision/aprobacion.
- `CuentaBancoEmpresa` y `MetodoPagoEmpresa`: catalogos reutilizables para pago/aprovisionamiento.
- `RolesAndPermissionsSeeder`: roles `super-admin`, `admin-rivera`, `jefe-obra`, `supervisor-obra`, `consulta`; API valida tambien rol `residente`.

Limitaciones del modulo existente:

- `obra_reposicion_gastos.obra_id` es obligatorio; no soporta destino almacen ni relacion mixta obra/almacen.
- La autorizacion actual vive en el encabezado; el nuevo flujo exige autorizacion por gasto.
- `tipo_reposicion = caja_chica` exige CFDI/UUID en el controlador actual, pero el MVP inicia con gastos sin factura.
- Tipos como `caja_chica`, `viaticos`, `gastos_varios`, `cfdi`, `nota` estan hardcodeados; el nuevo flujo necesita catalogos.
- No separa `importe_registrado` de `importe_autorizado`.
- No hay genero/subgenero, motivo sin factura, destino obra/almacen ni historial completo por gasto.
- La evidencia actual es un solo path por detalle; el MVP deberia soportar varios archivos y metadatos.

Recomendacion: crear un modulo nuevo `CajaChica` y reutilizar patrones/componentes del modulo `ObraReposicionGasto`, sin modificar su semantica actual.

## 3. Alcance del MVP

Incluye:

- Catalogos de generos y subgeneros.
- Catalogo configurable de tipos de comprobacion.
- Captura manual de gastos sin factura.
- Destino obra o almacen; obra obligatoria si destino es obra.
- Forma de pago, motivo sin factura, evidencia y observaciones.
- Envio a oficina.
- Revision individual: autorizar, rechazar, devolver; autorizacion parcial si se confirma.
- Relacion semanal con folio `RCC-YYYY-SSS`.
- Totales registrado, autorizado, rechazado y pendiente.
- Caratula PDF.

Excluye del MVP:

- Carga/lectura XML.
- Resolver automatico de proveedor.
- Resolver de productos.
- Division de conceptos de XML entre obras.
- Integracion final con programacion de pagos.
- Creacion automatica de proveedor u orden de compra.
- Historico definitivo de precios.

## 4. Flujo funcional

1. **Selección inicial de destino y Obra/Almacén**: El usuario selecciona primero el destino (`obra` o `almacen`) y la Obra o Almacén específica sobre la que va a trabajar.
2. **Captura por lote (Hoja tipo Excel / Lector XML)**:
   - Todo el lote activo se asigna a esa Obra/Almacén.
   - El usuario carga/arrastra la N cantidad de gastos de esa obra (vía archivos XML scrapeados automáticamente o mediante filas manuales sin factura).
   - Para cada gasto se indica/confirma tipo de comprobación, categoría/subcategoría, concepto, proveedor, forma de pago e importe (y motivo sin factura si aplica).
3. **Guardado o Envío del lote**:
   - Guarda el lote en `borrador` o lo envía directo a oficina (`pendiente`).
4. **Avance a la siguiente obra/almacén**: Una vez guardado o terminado el paquete, el usuario avanza a la siguiente obra o almacén para continuar capturando su respectivo paquete de gastos.
5. **Revisión individual**: Oficina revisa individualmente cada gasto (autoriza, autoriza parcialmente, rechaza o devuelve a corrección).
6. **Relación semanal**: Los gastos se agrupan por semana operativa en una relación con folio `RCC-YYYY-SSS`.
7. **Carátula PDF**: Se genera la carátula y reporte PDF para la programación de reposición interna.

## 5. Estados y transiciones

Estados del gasto:

- `borrador`
- `pendiente`
- `en_correccion`
- `autorizado`
- `autorizado_parcial`
- `rechazado`
- `programado`
- `pagado`
- `cancelado`

Transiciones:

```text
borrador -> pendiente
pendiente -> autorizado
pendiente -> autorizado_parcial
pendiente -> rechazado
pendiente -> en_correccion
en_correccion -> pendiente
autorizado/autorizado_parcial -> programado
programado -> pagado
borrador -> cancelado
```

Estados de relacion:

- `borrador`
- `enviada`
- `en_revision`
- `revisada`
- `autorizada`
- `autorizada_parcial`
- `rechazada`
- `programada`
- `pagada`

Regla: la relacion no autoriza gastos en bloque; calcula su estado desde las decisiones individuales.

## 6. Modelo de datos propuesto

### `caja_chica_tipos_comprobacion`

`id`, `codigo`, `nombre`, `descripcion`, `requiere_xml`, `requiere_evidencia`, `activo`, `orden`, timestamps.

Sembrar inicialmente `requiere_factura` y `sin_factura`. Las dos categorias pendientes se agregan aqui, no en codigo.

### `caja_chica_generos`

`id`, `codigo`, `nombre`, `descripcion`, `activo`, `orden`, timestamps.

### `caja_chica_subgeneros`

`id`, `caja_chica_genero_id`, `codigo`, `nombre`, `descripcion`, `activo`, `orden`, timestamps.

### `caja_chica_relaciones`

`id`, `folio`, `semana_anio`, `semana_numero`, `fecha_inicio`, `fecha_fin`, `responsable_user_id`, `responsable_empleado_id`, `area_id`/`area_codigo`, `almacen_id`, `estado`, `fecha_generacion`, `total_registrado`, `total_autorizado`, `total_rechazado`, `total_pendiente`, `monto_reposicion`, `programacion_pago_id` futuro, `pagado_at`, `referencia_pago`, `created_by`, `updated_by`, timestamps.

### `caja_chica_gastos`

`id`, `caja_chica_relacion_id`, `tipo_comprobacion_id`, `destino`, `obra_id`, `almacen_id`, `genero_id`, `subgenero_id`, `fecha_gasto`, `proveedor_nombre`, `proveedor_id` futuro, `proveedor_rfc`, `concepto`, `forma_pago`, `importe_registrado`, `importe_autorizado`, `estado_autorizacion`, `motivo_sin_factura`, `observaciones`, `resuelto_por`, `resuelto_at`, `motivo_rechazo`, `observaciones_autorizacion`, `solicitado_por`, `solicitado_at`, `created_by`, `updated_by`, timestamps.

Indices: estado, fecha, destino, obra, almacen, relacion, tipo comprobacion, genero/subgenero.

### `caja_chica_gasto_archivos`

`id`, `caja_chica_gasto_id`, `tipo`, `disk`, `path`, `nombre_original`, `mime_type`, `size_bytes`, `hash_sha256`, `uploaded_by`, timestamps.

### `caja_chica_gasto_historial`

`id`, `caja_chica_gasto_id`, `evento`, `estado_anterior`, `estado_nuevo`, `payload_anterior_json`, `payload_nuevo_json`, `comentario`, `user_id`, `created_at`.

### Fase 2 XML

`caja_chica_gasto_cfdis`: `gasto_id`, `sat_cfdi_id`, `uuid`, `xml_path`, `subtotal`, `impuestos`, `total`, `meta_json`.

`caja_chica_gasto_cfdi_conceptos`: `cfdi_id`, `sat_cfdi_concepto_id`, `no_identificacion`, `clave_prod_serv`, `clave_unidad`, `descripcion`, `cantidad`, `unidad`, `valor_unitario`, `importe`, `producto_id`, `resolver_estado`.

### Fase 3 resolver

`proveedor_producto_equivalencias`: proveedor, producto, codigo proveedor, descripcion normalizada, clave SAT, unidad SAT, confianza, origen, confirmado por/fecha, activo.

`proveedor_producto_precios_historial`: proveedor, producto, concepto origen, fecha, cantidad, unidad, precio, moneda, estatus fuente.

## 7. Relaciones

- Relacion semanal tiene muchos gastos.
- Gasto pertenece a tipo de comprobacion, genero y subgenero.
- Subgenero pertenece a genero.
- Gasto pertenece a obra cuando `destino = obra`.
- Gasto pertenece a almacen cuando `destino = almacen`.
- Gasto tiene muchos archivos.
- Gasto tiene historial.
- En fase 2, gasto puede tener CFDI y conceptos.
- CFDI puede vincularse con `sat_cfdis` por UUID.
- Proveedor se resuelve contra `proveedores.rfc`.
- En fase 3, conceptos se vinculan a `productos` por equivalencias.
- En fase futura, relacion autorizada crea programacion de pago con origen `caja_chica`.

## 8. Permisos

Permisos sugeridos:

- `caja_chica.view`
- `caja_chica.create`
- `caja_chica.edit_own_draft`
- `caja_chica.submit`
- `caja_chica.review`
- `caja_chica.authorize`
- `caja_chica.reject`
- `caja_chica.return`
- `caja_chica.relations.view`
- `caja_chica.relations.generate`
- `caja_chica.relations.pdf`
- `caja_chica.program`
- `caja_chica.pay`
- `caja_chica.catalogs.manage`

Asignacion inicial:

- `residente`: crear, editar borrador propio, enviar, ver propios.
- `jefe-obra`/`supervisor-obra`: ver gastos de sus obras; pre-revision solo si se define.
- `admin-rivera`: revisar, autorizar, rechazar, devolver, generar relaciones y PDF.
- `super-admin`: todo.
- `consulta`: solo lectura.

## 9. Rutas y endpoints sugeridos

Web:

```text
GET    /caja-chica
GET    /caja-chica/create
POST   /caja-chica
GET    /caja-chica/{gasto}
GET    /caja-chica/{gasto}/edit
PUT    /caja-chica/{gasto}
POST   /caja-chica/{gasto}/enviar
PATCH  /caja-chica/{gasto}/autorizar
PATCH  /caja-chica/{gasto}/rechazar
PATCH  /caja-chica/{gasto}/devolver
GET    /caja-chica/relaciones
POST   /caja-chica/relaciones/generar
GET    /caja-chica/relaciones/{relacion}
GET    /caja-chica/relaciones/{relacion}/pdf
PATCH  /caja-chica/relaciones/{relacion}/programar
```

Catalogos:

```text
/configuracion/caja-chica/generos
/configuracion/caja-chica/subgeneros
/configuracion/caja-chica/tipos-comprobacion
```

API residente:

```text
GET    /api/v1/residente/caja-chica
GET    /api/v1/residente/caja-chica/catalogos
POST   /api/v1/residente/caja-chica
POST   /api/v1/residente/caja-chica/{gasto}/evidencias
POST   /api/v1/residente/caja-chica/{gasto}/enviar
GET    /api/v1/residente/caja-chica/{gasto}
```

Fase 2 XML:

```text
POST   /api/v1/residente/caja-chica/xml/preview
POST   /api/v1/residente/caja-chica/xml/importar
GET    /api/v1/residente/caja-chica/proveedores/resolver
POST   /api/v1/residente/caja-chica/proveedores/proponer
```

## 10. Pantallas y componentes

Pantallas MVP:

- Bandeja de gastos con filtros por semana, estado, destino, obra, almacen, genero y usuario.
- Captura manual sin factura.
- Detalle de gasto con snapshot, evidencias, historial y resolucion.
- Bandeja de revision de oficina.
- Modal/formulario de autorizacion.
- Modal/formulario de rechazo/devolucion.
- Relacion semanal con totales, detalle y PDF.
- Catalogos de generos, subgeneros y tipos de comprobacion.

Reutilizar/inspirarse en:

- Selects de obras.
- Selects de almacenes activos.
- `ImageOptimizerInterface` y `Storage` para evidencias.
- Tabla de detalles del modulo de reposiciones existente.
- Plantillas PDF de `resources/views/obras/reposicion-gastos/pdf`.
- Catalogos de cuenta/metodo de pago para fase posterior.

## 11. Validaciones

Captura:

- Fecha obligatoria y no futura salvo permiso especial.
- Proveedor/beneficiario obligatorio.
- Concepto obligatorio.
- Importe registrado mayor a 0.
- Forma de pago obligatoria.
- Tipo de comprobacion obligatorio.
- Destino obligatorio: `obra` o `almacen`.
- Obra obligatoria si destino es obra (únicamente obras activas con estatus 1 'Planeación' o 2 'En ejecución').
- Almacen obligatorio si negocio confirma almacen especifico.
- Genero obligatorio.
- Subgenero obligatorio y perteneciente al genero.
- Motivo sin factura obligatorio para tipo sin factura.
- Evidencia obligatoria si se confirma o si `requiere_evidencia = true`.
- Archivos permitidos: `pdf`, `jpg`, `jpeg`, `png`, `webp`; tamano sugerido 10 MB.

Revision:

- Solo `pendiente` puede autorizarse/rechazarse/devolverse.
- `importe_autorizado` no negativo.
- Autorizacion total: autorizado = registrado.
- Autorizacion parcial: autorizado > 0 y menor que registrado.
- Rechazo: motivo obligatorio y autorizado = 0.
- Nunca sobrescribir `importe_registrado`.

Relacion semanal:

- Un gasto no puede estar en dos relaciones activas.
- Semana operativa debe venir de configuracion, no de `startOfWeek()` implicito.
- Totales se recalculan desde gastos, no desde valores escritos manualmente.

## 12. Archivos y evidencia

- Guardar archivos en disco controlado; ruta sugerida `caja-chica/evidencias/{YYYY}/{semana}/{gasto_id}/...`.
- Guardar metadatos en `caja_chica_gasto_archivos`.
- Optimizar imagenes con `ImageOptimizerInterface`.
- Guardar PDF/XML originales sin optimizar.
- Calcular hash SHA-256 para deduplicacion/auditoria.
- Validar permisos antes de servir/descargar archivos.
- Conservar evidencias aunque el gasto sea rechazado.

## 13. Reporte semanal PDF

Folio recomendado: `RCC-{anio}-{semana_3_digitos}`, ejemplo `RCC-2026-035`.

Totales:

- Registrado: suma de `importe_registrado`.
- Autorizado: suma de `importe_autorizado` autorizado/parcial.
- Rechazado: suma registrada de rechazados.
- Pendiente: suma registrada de pendientes/en revision.
- Monto final de reposicion: total autorizado.

Detalle minimo:

- Renglon, fecha, tipo comprobante, proveedor/beneficiario, concepto, destino, obra, genero/subgenero, forma de pago, importe registrado, importe autorizado, estado y observaciones.

Piezas tecnicas:

- `CajaChicaRelacionService`.
- `CajaChicaFolioService`.
- `CajaChicaReportePdfService`.
- `CajaChicaRelacionController`.
- Blade `resources/views/caja-chica/relaciones/show.blade.php`.
- PDF `resources/views/caja-chica/relaciones/pdf.blade.php`.

## 14. Estrategia para XML fase 2

Extraer la logica de `SatMassDownloadService` a servicios reutilizables:

- `SatCfdiXmlParserService`: parsea XML y devuelve datos normalizados sin persistir.
- `SatCfdiImportService`: reutiliza `sat_cfdis` existente o crea snapshot.
- `CajaChicaXmlPreviewService`: valida, extrae UUID, RFC, razon social, fecha, conceptos, subtotal, impuestos, total, forma/metodo de pago.

Reglas:

- Validar CFDI legible.
- Extraer UUID desde Timbre Fiscal Digital.
- Normalizar RFC en mayusculas sin espacios.
- Detectar duplicado por UUID.
- Mostrar conceptos en tabla.
- MVP XML: factura como un solo gasto; conceptos como detalle informativo.
- No crear proveedor automaticamente.

## 15. Resolver futuro

Proveedor:

- Buscar primero por RFC normalizado en `proveedores.rfc`.
- Resultados: encontrado, no encontrado, ambiguo/inconsistente.
- Si no existe, proponer proveedor preliminar con confirmacion.
- No crear proveedor operativo completo sin confirmacion.

Productos:

1. Equivalencia previa proveedor + codigo.
2. `NoIdentificacion` del XML.
3. Descripcion normalizada.
4. Alias/palabras clave del catalogo interno.
5. Clave SAT y unidad como auxiliares.
6. Seleccion manual.

Reglas:

- Permitir marcar conceptos como servicio/gasto operativo.
- Guardar equivalencias solo con confirmacion del usuario.
- No actualizar catalogo/historico desde gastos rechazados, duplicados o no autorizados.
- Historico de precios solo desde conceptos autorizados.

## 16. Integracion posterior con programacion de pagos

El flujo actual crea `SatCfdiProgramacion` con `origen = cfdi`. Para caja chica:

- Agregar origen `caja_chica`.
- Permitir programacion sin `sat_cfdi_id` cuando no hay factura.
- Enlazar `caja_chica_relacion_id` o usar tabla polimorfica de origen.
- `monto_programado = monto_reposicion`.
- `requiere_factura` debe derivarse del tipo de comprobacion.
- Guardar beneficiario/responsable de reposicion, no proveedor externo, si es reposicion interna.
- Mantener revision/aprobacion de pagos si administracion lo decide.

Decision abierta: definir si se generara OC de caja chica, programacion directa o documento independiente.

## 17. Migraciones, modelos, controladores, servicios y pruebas

Migraciones MVP:

- Tipos de comprobacion.
- Generos.
- Subgeneros.
- Relaciones semanales.
- Gastos.
- Archivos.
- Historial.
- Seeders base.

Modelos:

- `CajaChicaTipoComprobacion`
- `CajaChicaGenero`
- `CajaChicaSubgenero`
- `CajaChicaRelacion`
- `CajaChicaGasto`
- `CajaChicaGastoArchivo`
- `CajaChicaGastoHistorial`

Controladores:

- `CajaChicaGastoController`
- `CajaChicaRevisionController`
- `CajaChicaRelacionController`
- `CajaChicaCatalogoController`
- `Api/V1/ResidenteCajaChicaController`

Requests:

- `StoreCajaChicaGastoRequest`
- `UpdateCajaChicaGastoRequest`
- `SubmitCajaChicaGastoRequest`
- `AutorizarCajaChicaGastoRequest`
- `RechazarCajaChicaGastoRequest`
- `GenerarCajaChicaRelacionRequest`

Servicios:

- `CajaChicaGastoService`
- `CajaChicaEstadoService`
- `CajaChicaRelacionService`
- `CajaChicaFolioService`
- `CajaChicaArchivoService`
- `CajaChicaReportePdfService`
- Fase 2: `SatCfdiXmlParserService`, `CajaChicaProveedorResolverService`.
- Fase 3: `CajaChicaProductoResolverService`.

Pruebas:

- Captura sin factura.
- Validacion destino obra/almacen.
- Validacion genero/subgenero.
- Upload de evidencia.
- Envio bloquea edicion.
- Autorizacion individual conserva registrado.
- Rechazo exige motivo.
- Autorizacion parcial recalcula totales.
- Relacion semanal y folio.
- PDF responde.
- Permisos por rol.
- Fase 2: XML invalido, valido, UUID duplicado y conceptos.

## 18. Fases de implementacion

1. Decisiones funcionales pendientes.
2. Migraciones, modelos, seeders y permisos.
3. Captura sin factura web/API.
4. Envio a oficina y bloqueo de edicion.
5. Revision individual y auditoria.
6. Relacion semanal y caratula PDF.
7. Lector XML.
8. Resolver proveedor.
9. Resolver productos e historico de precios.
10. Integracion con programacion de pagos.

## 19. Riesgos

- Duplicar o confundir este modulo con `ObraReposicionGasto`.
- Romper flujo actual si se fuerza el modelo existente.
- Mezclar caja chica sin factura con OCs GL/caja chica ya documentadas.
- Estados/tipos hardcodeados dificultarian nuevas categorias.
- Evidencia publica sin autorizacion podria exponer archivos.
- Autorizacion parcial sin reglas claras complica pagos.
- Semana operativa distinta a la semana calendario puede romper folios.
- Resolver de productos puede contaminar catalogos si aprende desde gastos no autorizados.
- Programacion de pagos podria confundir proveedor externo con reposicion interna.

## 20. Decisiones pendientes

- Nombre y reglas de las otras dos categorias de comprobacion.
- Si una relacion semanal puede mezclar varias obras y gastos del almacen.
- Si la evidencia es obligatoria para gastos sin factura.
- Que dia inicia y termina la semana operativa.
- Si la relacion corresponde a un responsable o consolida a varios ingenieros.
- Quienes pueden autorizar y si existe mas de un nivel de revision.
- Si se permitira autorizacion parcial desde el MVP.
- Como se integrara con programacion de pagos.
- Si debe generarse OC de caja chica o documento independiente.
- Si almacen sera generico o requerira `almacen_id`.
- Si residente solo puede capturar para su obra activa.

## 21. Criterios de aceptacion MVP

- El usuario registra gasto sin factura en borrador.
- Puede elegir destino obra/almacen.
- Obra es obligatoria cuando destino es obra.
- Subgenero depende del genero.
- Motivo sin factura y evidencia se validan segun catalogo/regla.
- Al enviar, el gasto queda bloqueado para edicion directa.
- Oficina autoriza/rechaza individualmente.
- El importe registrado nunca se pierde.
- Rechazo conserva motivo, usuario y fecha.
- Relacion semanal genera folio `RCC-YYYY-SSS`.
- Caratula muestra detalle y totales completos.
- Monto final de reposicion coincide con total autorizado.
- PDF no afecta PDFs existentes.
- No se rompe OCs, reposiciones por obra, viaticos, CFDI ni programacion de pagos.
## 22. Avance implementado al 2026-08-29

### Modulo base

- Se creo el modulo nuevo `reposicion-caja-chica` sin modificar el flujo legacy de `ObraReposicionGasto`.
- Se agregaron rutas web para bandeja, captura, detalle, revision, relaciones legacy/cascaron, impresion legacy de relacion, reporte imprimible semanal y exportacion Excel.
- Se agrego opcion en el menu lateral para acceder al modulo.
- Se crearon modelos Eloquent para categorias, subcategorias, gastos, archivos y relaciones.
- Se crearon migraciones base para categorias, subcategorias, relaciones, gastos y archivos.
- Se sembraron las cuatro categorias tecnicas iniciales:
  - `efectivo_factura`: Con efectivo y factura.
  - `tarjeta_factura`: Con tarjeta y factura.
  - `sin_factura_viaticos`: Sin factura (viaticos).
  - `sin_factura_reembolso`: Sin factura (reembolso).
- Se agrego catalogo inicial configurable de subcategorias visibles como `Categoria` en captura:
  - Servicio.
  - Consumibles.
  - Refacciones.
  - Mantenimientos.

### Captura

- La pantalla `/reposicion-caja-chica/create` funciona como hoja tipo Excel.
- La captura inicia seleccionando una Obra o Almacen objetivo para todo el lote.
- Se quitaron los selects visibles de Obra/Almacen por renglon para evitar errores de asignacion.
- El backend fuerza que todos los gastos del lote se guarden contra el destino global seleccionado, ignorando cualquier valor por renglon manipulado.
- Se separaron `Proveedor` y `RFC` en columnas independientes.
- El RFC queda opcional en captura manual.
- Se oculto/comento temporalmente el campo `motivo_sin_factura` para mantener la tabla alineada, sin eliminar la columna ni la capacidad futura de reactivarlo.
- Se relajo temporalmente la validacion de `motivo_sin_factura` en backend mientras el input esta oculto.
- Se amplio la columna `Concepto` para capturar ahi el detalle operativo.
- Se compactaron acciones de evidencia/eliminar en una sola columna.
- El nombre del XML ya no ocupa espacio visual en la tabla; queda disponible como `title`/dato interno.

### Lector XML

- Se agrego endpoint `POST /reposicion-caja-chica/parse-xml`.
- Se agrego `CfdiXmlParserService` para leer CFDI 3.3/4.0 desde XML.
- El XML extrae proveedor, RFC, fecha, concepto, total y forma de pago SAT.
- La forma de pago SAT se mapea asi:
  - `01` => `efectivo` y categoria `efectivo_factura`.
  - `04`, `28`, `05`, `06` => `tarjeta` y categoria `tarjeta_factura`.
  - `03` => `transferencia`, rechazada para este modulo.
  - desconocida => rechazada, ya no cae por defecto en efectivo.
- Los XML con transferencia se bloquean en la captura porque corresponden a Orden de Compra / pago a proveedores, no a caja chica.
- Se corrigio la asignacion del archivo XML original usando `file_index` para evitar adjuntar el archivo equivocado cuando una carga parcial falla.

### Bandeja semanal

- La pantalla `/reposicion-caja-chica` tiene navegador semanal.
- La semana operativa ahora usa `created_at`, no `fecha_gasto`, para que facturas con fecha fiscal antigua aparezcan en la semana en que se capturaron/reportaron.
- La columna `Fecha` sigue mostrando `fecha_gasto` para conservar la fecha real del comprobante o gasto.
- El listado semanal conserva filtros por estado, tipo de comprobacion y destino.
- Las tarjetas de totales se calculan con la misma consulta filtrada del reporte semanal.
- Se confirmo con datos reales que la semana visible incluye gastos con factura y sin factura capturados en la misma semana.

### Autorizacion individual

- Cada gasto conserva autorizacion individual mediante `estado_autorizacion`, `importe_autorizado`, `resuelto_por`, `resuelto_at`, `motivo_rechazo` y `observaciones_autorizacion`.
- Se agregaron rutas POST para:
  - Autorizar completo.
  - Autorizar parcial.
  - Rechazar.
- Se implementaron metodos protegidos por permisos en `ReposicionCajaChicaController`.
- Solo se pueden resolver gastos en estado `pendiente`.
- Autorizar completo copia `importe_registrado` a `importe_autorizado`.
- Autorizar parcial exige importe mayor a 0 y menor que el registrado.
- Rechazar exige motivo y deja `importe_autorizado = 0`.
- La pantalla `/reposicion-caja-chica/revision` se mantiene como revision completa con acciones de autorizar, parcial y rechazar.
- En `/reposicion-caja-chica` se agrego check rapido por renglon para autorizar completo, visible solo si el usuario tiene `caja_chica.authorize` y el gasto esta `pendiente`.
- Se confirmo/creo el permiso `caja_chica.authorize` en BD y se asigno al rol `super-admin`.
- No se asignaron permisos adicionales como `caja_chica.reject` o `caja_chica.review` sin aprobacion explicita.

### Reporte operativo de relacion

Decision actual: para este flujo, una relacion no necesita ser una entidad persistida ni ligada por `relacion_id`. La relacion operativa es el reporte/impresion semanal de las partidas visibles, agrupadas por tipo de comprobacion.

- La pantalla `/reposicion-caja-chica/relaciones` queda por ahora como cascaron/legacy y no se borra todavia.
- En `/reposicion-caja-chica` se agregaron botones:
  - `Imprimir`.
  - `Exportar Excel`.
- Ambos respetan los filtros actuales de semana, estado, tipo de comprobacion y destino.
- Se agrego vista imprimible `resources/views/reposicion-caja-chica/reporte-imprimir.blade.php`.
- El reporte imprimible agrupa por tipo de comprobacion y muestra una tabla por grupo.
- El export Excel genera archivo `.xls` HTML compatible con Excel, agrupado por tipo de comprobacion.
- Ambos reportes incluyen totales por grupo y totales generales.

### Validaciones realizadas

Durante los cortes se validaron los cambios con:

- `php -l` en controladores/servicios tocados.
- `php artisan route:list --name=reposicion-caja-chica`.
- `php artisan view:cache`.
- Revision puntual de mojibake en archivos tocados con `rg` usando patrones de caracteres corruptos comunes.
- `graphify update .` despues de cambios de codigo.

Nota: el checker global de mojibake falla por archivos historicos no relacionados; los archivos tocados en este modulo no reportaron mojibake.

## 23. Decisiones actualizadas

- La semana operativa de la bandeja y reportes es por `created_at`.
- `fecha_gasto` se conserva como fecha del comprobante/gasto, pero no define la semana operativa del reporte.
- La relacion semanal persistida queda en pausa; por ahora se resuelve con impresion/exportacion desde la bandeja semanal.
- La pantalla `/reposicion-caja-chica/relaciones` no se elimina aun, pero ya no es el camino principal del reporte operativo.
- Los pagos por transferencia detectados en XML no pertenecen a este modulo; deben ir por Orden de Compra / pago a proveedores.
- El motivo sin factura queda temporalmente oculto y no obligatorio, reversible cuando operacion lo pida.
- El rechazo y la autorizacion parcial existen en revision completa, pero el index semanal solo muestra autorizacion completa rapida.

## 24. Pendientes inmediatos

- Definir proceso final cuando una partida no debe autorizarse desde el index semanal: enviar a revision completa, mostrar rechazo rapido o usar detalle.
- Decidir si se asignan `caja_chica.reject` y `caja_chica.review` al rol autorizador/super-admin en BD actual.
- Revisar si el boton `Relaciones` debe ocultarse del menu superior del modulo o mantenerse hasta retirar el cascaron.
- Definir si el export debe migrar de `.xls` HTML a `.xlsx` real con libreria dedicada.
- Agregar pruebas automatizadas del flujo: captura, XML, semana por `created_at`, autorizacion y reportes.
- Definir permisos finales por rol operativo.


