# Graph Report - rivera-v2  (2026-08-04)

## Corpus Check
- 1002 files · ~973,852 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 3955 nodes · 6811 edges · 754 communities (720 shown, 34 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 466 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `c65c5912`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Seeder
- ObraReposicionGasto
- User
- Comision
- NominaCorrida
- AttendanceUser
- Model
- ResidenteComisionesService
- Migration
- Proveedor
- EquipoComputo
- Maquina
- Obra
- ObraFacturaBorrador
- InventarioDocumento
- SatFactura
- Queueable
- ObraMaquina
- Presupuesto
- .edit
- ObraController
- SatDocumentRequest
- MaquinariaReporteSnapshot
- Producto
- devDependencies
- NominaListaRaya
- Empleado
- ObraSolicitudGasto
- .view
- SatCfdi
- HasFactory
- Area
- Middleware
- ObraEmpleado
- Seguro
- FormRequest
- Command
- MaquinaEstadoCambiado
- Mantenimiento
- MaquinaService
- OrdenCompraDetalleController.php
- PagoProveedor
- web.php
- Controller
- ObraPila
- CsfRequestService
- ServiceProvider
- SatCfdiProgramacion
- require
- UsuarioController.php
- CatalogoRol
- Cliente
- OrdenCompra
- SatFacturaPago
- Vehiculo
- api.php
- VehiculoEmpleado
- ObraFactura
- DatabaseCaptchaResolver.php
- SatDownloadRequest
- OrdenCompra
- SatMassDownloadService
- ImportProductosCsv
- Almacen
- EmpresaConfig
- LoginRequest
- What You Must Do When Invoked
- SatFacturacionController.php
- MaquinaEstadoMail.php
- ResidenteComisionController
- auth.php
- CatalogoPila
- .edit
- SatConcepto
- ObraAsistencia
- NominaCorrida
- SatComplementoPagoController
- .create
- composer.json
- scripts
- SatCfdiPago
- User.php
- EmpleadoNota
- CatalogoRol
- ProveedorController
- MaquinaController.php
- Component
- require-dev
- AuthenticatedSessionController.php
- ZkDeviceClient
- ObraFacturaPago
- .abortarSiObraFueraDeArea
- ObraPresupuesto
- EmpleadoDocumento
- SatCfdi.php
- PreventivoMaquinaService
- edit.blade.php
- EmpresaConfigAreaController
- ProfileController.php
- PasswordResetLinkController.php
- RegisteredUserController.php
- InventarioKardexGerencialController.php
- LoginRequest
- config
- Kernel
- Handler.php
- ComisionController.php
- InventarioSeedInicial.php
- RequireApiKey.php
- InventarioMovimiento
- RouteServiceProvider.php
- UserFactory
- show.blade.php
- ResidenteComisionController
- AgentNotificationController
- ComisionPersonal
- psr-4
- edit.blade.php
- edit.blade.php
- ComisionEtapaPersonal
- VehiculoDocumentoController.php
- edit.blade.php
- Kernel
- Application
- edit.blade.php
- autoload-dev
- keywords
- 2014_10_12_000000_create_users_table.php
- 2019_08_19_000000_create_failed_jobs_table.php
- 2025_11_27_181836_create_empleado_contactos_emergencia_table.php
- AgentNotificationController
- 2025_12_02_185346_create_proveedores_table.php
- VehiculoDocumentoController.php
- EmailVerificationPromptController.php
- 2025_12_11_192236_create_vehiculos_table.php
- 2025_12_18_184201_add_rol_id_to_comision_personal_table.php
- InventarioGerencialController.php
- 2026_01_05_195229_make_numero_pila_nullable_in_obras_pilas_table.php
- 2026_01_08_193757_alter_usuarios_app_add_user_id_and_is_active.php
- 2026_01_16_192603_create_maquinaria_reporte_snapshots_tables.php
- 2026_02_03_171828_create_inventario_stock_table.php
- SatCfdiConcepto.php
- 2026_02_25_160241_create_facturas_table.php
- .handle
- 2026_04_09_173414_create_obra_planeacion_gastos_table.php
- 2026_04_10_190703_create_obra_planeacion_semanal_table.php
- 64d5b5b5e15997d6185a02db44016242.php
- 2026_04_17_152650_alter_sat_cfdis_add_header_fields.php
- 2026_04_23_194501_add_captcha_token_to_sat_document_requests_table.php
- 79c8b59029384a6aa84b536a6daf05e9.php
- SnapshotsController.php
- 2026_05_26_000004_create_centros_costo_table.php
- VehiculoServicioPreventivoNotification
- empleados._form
- layouts.navigation
- obras.comisiones.create-form
- obras.partials.fila_planeacion
- index.blade.php
- cliente.blade.php
- 4f230847dbc256924ade4eb2c2d00cb2.php
- 6a2cd42352527a6acb1a6fd285ce1209.php
- graphify reference: incremental update and cluster-only
- graphify reference: GitHub clone and cross-repo merge
- graphify reference: transcribe video and audio
- AGENTS.md
- extraction-spec.md
- ComisionEtapaPersonal
- SatCfdiEstadisticaController.php
- VerifyEmailController.php
- FacturaBorradorAutorizado
- .edit
- SatCfdiConcepto.php
- InventarioGerencialController.php
- empleados.blade.php
- NotificationController

## God Nodes (most connected - your core abstractions)
1. `Controller` - 168 edges
2. `Obra` - 152 edges
3. `Empleado` - 72 edges
4. `User` - 70 edges
5. `Maquina` - 60 edges
6. `OrdenCompra` - 48 edges
7. `Cliente` - 45 edges
8. `ObraController` - 44 edges
9. `Comision` - 44 edges
10. `SatCfdi` - 44 edges

## Surprising Connections (you probably didn't know these)
- `MaquinaEstadoCambiado` --references--> `Maquina`  [EXTRACTED]
  app/Events/MaquinaEstadoCambiado.php → app/Models/Maquina.php
- `EmpresaConfigAreaController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/EmpresaConfigAreaController.php → app/Http/Controllers/Controller.php
- `EmpresaConfigListaRayaController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/EmpresaConfigListaRayaController.php → app/Http/Controllers/Controller.php
- `EmpresaSecurityController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/EmpresaSecurityController.php → app/Http/Controllers/Controller.php
- `UsuarioController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Admin/UsuarioController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (754 total, 34 thin omitted)

### Community 0 - "Seeder"
Cohesion: 0.15
Nodes (13): EmpresaSecurityController, Request, assignPermissions(), up(), assignPermissions(), up(), up(), down() (+5 more)

### Community 1 - "ObraReposicionGasto"
Cohesion: 0.07
Nodes (9): EmpresaConfigMaquinaController, Request, MaquinaController, Request, ObraMaquinaController, Request, Maquina, MaquinaMovimiento (+1 more)

### Community 2 - "User"
Cohesion: 0.08
Nodes (11): BaseTestCase, CreatesApplication, RefreshDatabase, EmailVerificationTest, PasswordConfirmationTest, PasswordResetTest, PasswordUpdateTest, RegistrationTest (+3 more)

### Community 3 - "Comision"
Cohesion: 0.05
Nodes (42): Arquitectura propuesta, Cambios, Cambios, Cambios, Cambios, Cambios, Cambios, Cambios (+34 more)

### Community 4 - "NominaCorrida"
Cohesion: 0.08
Nodes (25): 1. Confirmar el buzon institucional, 1. Revisar el flujo actual de envio, 2. Crear servicio Microsoft Graph, 2. Entrar al panel correcto, 3. Agregar configuracion, 3. Crear App Registration, 4. Agregar variables de entorno, 4. Guardar datos de la app (+17 more)

### Community 5 - "AttendanceUser"
Cohesion: 0.14
Nodes (12): MaquinaEstadoCambiado, ProcessSatDownloadJob, SendMaquinaEstadoNotification, MaquinaEstadoMail, Content, Dispatchable, Envelope, InteractsWithQueue (+4 more)

### Community 6 - "Model"
Cohesion: 0.07
Nodes (13): CatalogoRolAlias, DocumentoVehiculo, MantenimientoDetalle, MantenimientoFoto, NominaPagoExtra, ProductoProveedorPrecio, SatFacturaConcepto, SeguroMaquina (+5 more)

### Community 7 - "ResidenteComisionesService"
Cohesion: 0.11
Nodes (5): ObraReposicionGastoController, Request, MetodoPagoEmpresa, ObraReposicionGasto, MetodoPagoEmpresaSeeder

### Community 8 - "Migration"
Cohesion: 0.03
Nodes (6): NullableObraIdInObraPlaneacionGastosTable, CreateSatFacturasTable, CreateSatFacturaConceptosTable, CreateSatConceptosTable, AddFiscalFieldsToProveedoresTable, Migration

### Community 9 - "Proveedor"
Cohesion: 0.09
Nodes (3): Request, ProveedorController, Proveedor

### Community 10 - "EquipoComputo"
Cohesion: 0.12
Nodes (5): EquipoComputoController, Request, EquipoComputo, EquipoComputoFoto, EquipoComputoMovimiento

### Community 11 - "Maquina"
Cohesion: 0.08
Nodes (13): PlanoCategoria, CatalogoRolesAliasSeeder, CatalogoRolesSeeder, ComisionesCatalogosSeeder, ComisionTarifarioInicialSeeder, DatabaseSeeder, EmpresaConfigSeeder, MaquinaSeeder (+5 more)

### Community 12 - "Obra"
Cohesion: 0.06
Nodes (5): ObrasGerencialController, Request, ObraPilaController, Request, Obra

### Community 13 - "ObraFacturaBorrador"
Cohesion: 0.18
Nodes (4): ProcessSatCsfRequestJob, BelongsTo, SatDocumentRequest, CsfRequestService

### Community 14 - "InventarioDocumento"
Cohesion: 0.18
Nodes (4): InventarioDocumento, BelongsTo, HasMany, InventarioDocumentoService

### Community 15 - "SatFactura"
Cohesion: 0.15
Nodes (3): Request, SatFacturacionController, SatFacturaBorrador

### Community 16 - "Queueable"
Cohesion: 0.18
Nodes (3): Request, ResidenteReposicionGastoController, ObraReposicionGastoDetalle

### Community 17 - "ObraMaquina"
Cohesion: 0.43
Nodes (3): Request, SatCaptchaController, SatCaptchaSession

### Community 18 - "Presupuesto"
Cohesion: 0.12
Nodes (7): Request, PresupuestoController, PresupuestoController, Presupuesto, PresupuestoDetalle, PresupuestoPila, PresupuestoResumen

### Community 19 - ".edit"
Cohesion: 0.12
Nodes (7): EmpresaConfigController, Request, CuentaBancoEmpresa, EmpresaAlertaDestinatario, Builder, ObraFolio, ObraTipoConfiguracion

### Community 20 - "ObraController"
Cohesion: 0.12
Nodes (3): ObraController, Collection, Request

### Community 21 - "SatDocumentRequest"
Cohesion: 0.14
Nodes (6): Request, SatCfdiEstadisticaController, Request, SatEmpresaController, HasMany, SatEmpresa

### Community 22 - "MaquinariaReporteSnapshot"
Cohesion: 0.14
Nodes (5): MaquinasReporteDiarioController, Request, MaquinaReporteDiario, MaquinariaReporteSnapshot, MaquinariaReporteSnapshotItem

### Community 23 - "Producto"
Cohesion: 0.14
Nodes (3): Request, ProductoController, Producto

### Community 24 - "devDependencies"
Cohesion: 0.09
Nodes (22): alpinejs, autoprefixer, axios, laravel-vite-plugin, devDependencies, alpinejs, autoprefixer, axios (+14 more)

### Community 25 - "NominaListaRaya"
Cohesion: 0.15
Nodes (7): EmpresaConfigAreaController, Request, OrdenCompraController, Request, Area, CentroCosto, TipoIva

### Community 26 - "Empleado"
Cohesion: 0.08
Nodes (5): EmpleadoController, Request, Empleado, EmpleadoKardexService, Collection

### Community 27 - "ObraSolicitudGasto"
Cohesion: 0.13
Nodes (5): ObraSolicitudGastoController, Request, ObraPlaneacionSemanal, ObraSolicitudGasto, ObraSolicitudGastoDetalle

### Community 28 - ".view"
Cohesion: 0.10
Nodes (20): Ajuste Fase B 2026-07-20 - Lotes vacios del agente, Checkpoint 2026-07-21 - Paso 1 Click-to-call por agente: cola de solicitudes, Checkpoint 2026-07-21 - Paso 2 Click-to-call por agente: boton Llamar crea solicitud, Checkpoint 2026-07-21 - Paso 3 Click-to-call por agente: endpoints de consumo, Checkpoint 2026-07-21 - Paso 4 Click-to-call por agente: ejecucion local en UCM, Checkpoint Agente SIRICO Connect 2026-07-20 - Modulo UCM, Checkpoint Fase C 2026-07-20 - Cliente UCM dentro de Sirico.Agent, Checkpoint Fase C 2026-07-20 - Password UCM seguro y configuracion UI (+12 more)

### Community 29 - "SatCfdi"
Cohesion: 0.06
Nodes (31): Bitacora de hallazgos, Checkpoint 0. Preparacion del equipo y seguridad, Checkpoint 10. Interfaz basica, Checkpoint 11. Llamadas perdidas, Checkpoint 12. Dashboard basico, Checkpoint 13. Seguridad, permisos y logs, Checkpoint 14. Pruebas, Checkpoint 1. Barrido de arquitectura SIRICO (+23 more)

### Community 30 - "HasFactory"
Cohesion: 0.10
Nodes (5): Comision, ComisionEtapa, Collection, ResidenteComisionesService, UploadedFile

### Community 31 - "Area"
Cohesion: 0.11
Nodes (17): Ajuste completado: registros moviles por obra, App movil: captura de gasolina, Comando de alertas preventivas, Cuenta de envio de correos, Decision inicial, Estado actual, Fase 1: Configuracion persistente, Fase 2: Destinatarios configurables (+9 more)

### Community 32 - "Middleware"
Cohesion: 0.15
Nodes (10): Authenticate, Request, EncryptCookies, PreventRequestsDuringMaintenance, TrimStrings, TrustHosts, TrustProxies, ValidateSignature (+2 more)

### Community 33 - "ObraEmpleado"
Cohesion: 0.16
Nodes (4): AgentTelephonyController, Request, TelephonyCallRequest, TelephonySyncRun

### Community 34 - "Seguro"
Cohesion: 0.27
Nodes (4): GiraldaController, Request, GiraldaHoraExtra, StreamedResponse

### Community 35 - "FormRequest"
Cohesion: 0.07
Nodes (11): RedirectResponse, Request, View, ProfileController, LoginRequest, ProfileUpdateRequest, StoreOrdenCompraDetalleRequest, StoreOrdenCompraRequest (+3 more)

### Community 36 - "Command"
Cohesion: 0.05
Nodes (42): 1. Servicio generado o registrado, 2. Coordinacion, 3. Confirmacion, 4. Materiales, 5. Ejecucion, 6. Registro digital, 7. Validacion administrativa, 8. Cierre (+34 more)

### Community 37 - "MaquinaEstadoCambiado"
Cohesion: 0.11
Nodes (5): AttendanceDeviceCheckpoint, ComisionEtapaFoto, ComisionEtapaPersonal, PlaneacionGasto, HasFactory

### Community 38 - "Mantenimiento"
Cohesion: 0.17
Nodes (8): CalendarioOperacionalController, Request, View, CalendarioOperacionalService, Carbon, Collection, CarbonInterface, JsonResponse

### Community 39 - "MaquinaService"
Cohesion: 0.20
Nodes (3): CatalogoRolController, Request, CatalogoRol

### Community 40 - "OrdenCompraDetalleController.php"
Cohesion: 0.13
Nodes (3): CajaChicaController, Request, ObraPila

### Community 41 - "PagoProveedor"
Cohesion: 0.16
Nodes (4): PagoProveedorController, Request, PagoProveedor, OrdenCompraFlujoNotification

### Community 42 - "web.php"
Cohesion: 0.10
Nodes (13): AttendanceSync, AttendanceApiController, Request, AttendanceIngestController, Request, AttendanceController, Request, AttendanceWebController (+5 more)

### Community 43 - "Controller"
Cohesion: 0.08
Nodes (4): ObraFacturaBorrador, FacturaBorradorAutorizado, FacturaBorradorCreado, FacturaBorradorRechazado

### Community 44 - "ObraPila"
Cohesion: 0.06
Nodes (31): Ambientes, Aplicaciones móviles, Backend, Base de datos, Cajas chicas, Checadas, Clientes, Controladores (+23 more)

### Community 45 - "CsfRequestService"
Cohesion: 0.09
Nodes (9): ComisionController, Request, ComisionController, Request, CatalogoActividadComision, ComisionDetalle, ComisionPerforacion, ComisionTarifario (+1 more)

### Community 46 - "ServiceProvider"
Cohesion: 0.16
Nodes (5): AppServiceProvider, AuthServiceProvider, BroadcastServiceProvider, EventServiceProvider, ServiceProvider

### Community 47 - "SatCfdiProgramacion"
Cohesion: 0.07
Nodes (28): Campos del modal Crear borrador, Contexto operativo, Decisiones iniciales, Fase 1: Investigacion tecnica puntual, Fase 2: Base de datos y modelo, Fase 3: Crear borrador desde obra, Fase 4: Listado de borradores, Fase 5: Impresion (+20 more)

### Community 48 - "require"
Cohesion: 0.13
Nodes (15): require, barryvdh/laravel-dompdf, facturapi/facturapi-php, guzzlehttp/guzzle, intervention/image, laravel/framework, laravel/sanctum, laravel/tinker (+7 more)

### Community 51 - "Cliente"
Cohesion: 0.28
Nodes (3): MaquinaSeguroController, Request, Seguro

### Community 52 - "OrdenCompra"
Cohesion: 0.24
Nodes (3): EmpresaConfigListaRayaController, Request, NominaListaRaya

### Community 53 - "SatFacturaPago"
Cohesion: 0.10
Nodes (7): InventarioStockController, Request, MantenimientoController, Request, InventarioStock, BelongsTo, Mantenimiento

### Community 54 - "Vehiculo"
Cohesion: 0.20
Nodes (3): Request, ProgramacionPagosController, SatCfdiProgramacion

### Community 55 - "api.php"
Cohesion: 0.27
Nodes (3): SatMassDownloadService, Service, SimpleXMLElement

### Community 56 - "VehiculoEmpleado"
Cohesion: 0.14
Nodes (3): NominaCorridaController, Request, NominaRecibo

### Community 57 - "ObraFactura"
Cohesion: 0.04
Nodes (45): Arquitectura propuesta, Cambios, Cambios, Cambios, Cambios, Cambios, Cambios, Cambios (+37 more)

### Community 58 - "DatabaseCaptchaResolver.php"
Cohesion: 0.11
Nodes (8): MaquinasGerencialController, Request, MaquinaRegistroController, Request, ObraMaquinaHorasController, Request, ObraMaquina, ObraMaquinaRegistro

### Community 59 - "SatDownloadRequest"
Cohesion: 0.27
Nodes (3): Request, SatDownloadController, SatDownloadRequest

### Community 60 - "OrdenCompra"
Cohesion: 0.22
Nodes (3): Request, PlaneacionGastosController, ObraPlaneacionGasto

### Community 61 - "SatMassDownloadService"
Cohesion: 0.07
Nodes (28): Checkpoints de ejecucion, Contexto operativo, Fase 1: Auditoria tecnica, Fase 2: Navegacion y vista index, Fase 3: Pendientes por complementar, Fase 4: Formulario agregar pago, Fase 5: Timbrado Facturapi, Fase 6: Conexion con pagos internos de obra (+20 more)

### Community 62 - "ImportProductosCsv"
Cohesion: 0.39
Nodes (3): EmpleadoContactoEmergenciaController, Request, EmpleadoContactoEmergencia

### Community 63 - "Almacen"
Cohesion: 0.11
Nodes (17): AI Transcription, Decisiones recomendadas, Estado actual confirmado, Fase 1: metadata de grabacion, Fase 2: descarga por agente local, Fase 3: almacenamiento en SIRICO, Fase 4: API del agente, Fase 5: UI (+9 more)

### Community 64 - "EmpresaConfig"
Cohesion: 0.11
Nodes (6): Request, VehiculoController, Request, VehiculoSeguroController, Vehiculo, VehiculoAsignacionFoto

### Community 65 - "LoginRequest"
Cohesion: 0.11
Nodes (4): EmpleadoDocumentoController, Request, EmpleadoDocumento, EmpresaDocumentoTipo

### Community 66 - "What You Must Do When Invoked"
Cohesion: 0.08
Nodes (24): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+16 more)

### Community 68 - "MaquinaEstadoMail.php"
Cohesion: 0.10
Nodes (19): Comandos usados en el barrido, Comisiones, Comunidades mas grandes, Empleados, Estado del grafo, Facturacion SAT y CFDI, Hubs principales, Inventario (+11 more)

### Community 69 - "ResidenteComisionController"
Cohesion: 0.11
Nodes (8): Collection, User, Authenticatable, HasApiTokens, HasRoles, Notifiable, AuthenticationTest, ProfileTest

### Community 70 - "auth.php"
Cohesion: 0.18
Nodes (4): Request, SatFacturaPagoController, SatFacturaMail, SatFacturaPago

### Community 71 - "CatalogoPila"
Cohesion: 0.11
Nodes (18): AQUI UNA NOTA, LAS LISTAS DE RAYA CUANDO SON OBRAS, NO NECESITAN ESTAR EN MI CONFIG YA QUE SON LISTAS "TEMPORALES" O hay que buscar la manera porque con el paso del tiempo esas listas temporales iran creciendo, porque siempre hay obras nuevas, lo que podriamos hacer es dejarlas como referencia, para en un futuro auditar las rayas de la obra ejemplo entrar ala lista de raya de la obra X y ver cuantas corridas tienen, que empleados tiene y cuanto se pago, Auditoria inicial, Checkpoints de implementacion, Fase 1 - Catalogo de listas de raya en configuracion de empresa, Fase 2 - Correccion de base actual, Fase 3 - Guardado parcial/autosave, Fase 4 - Multiples extras por recibo, Fase 5 - Comisiones trazables (+10 more)

### Community 73 - "SatConcepto"
Cohesion: 0.27
Nodes (3): Request, SatCatalogoController, SatConcepto

### Community 74 - "ObraAsistencia"
Cohesion: 0.24
Nodes (3): AsistenciasController, Request, ObraAsistencia

### Community 75 - "NominaCorrida"
Cohesion: 0.21
Nodes (3): ObraEmpleadoController, Request, ObraEmpleado

### Community 76 - "SatComplementoPagoController"
Cohesion: 0.19
Nodes (5): TelephonyMatchCalls, TelephonyTestMatcher, PhoneCall, Collection, TelephonyCallMatcher

### Community 77 - ".create"
Cohesion: 0.17
Nodes (4): Builder, Request, SatCfdiController, SatCfdi

### Community 78 - "composer.json"
Cohesion: 0.20
Nodes (9): description, extra, laravel, dont-discover, license, minimum-stability, name, prefer-stable (+1 more)

### Community 79 - "scripts"
Cohesion: 0.20
Nodes (10): scripts, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan key:generate --ansi, @php artisan package:discover --ansi (+2 more)

### Community 80 - "SatCfdiPago"
Cohesion: 0.14
Nodes (7): FacturaBorradorListoParaFacturar, OrdenCompraCreada, SeguroVehiculoVencimiento, SolicitudGastoCreada, MailMessage, Notification, Queueable

### Community 81 - "User.php"
Cohesion: 0.09
Nodes (12): BackfillObraEmpleadoRolId, CheckInsuranceExpirations, GrandstreamImportCdr, GrandstreamSyncExtensions, GrandstreamTestCall, GrandstreamTestConnection, ImportLegacyCompras, ImportProductosLegacy (+4 more)

### Community 82 - "EmpleadoNota"
Cohesion: 0.12
Nodes (5): OrdenCompraDetalleController, Request, OrdenCompra, OrdenCompraDetalle, OrdenCompraTotalesService

### Community 85 - "CatalogoRol"
Cohesion: 0.18
Nodes (5): GrandstreamAssignExtension, Builder, Request, TelephonyExtensionController, PhoneExtension

### Community 86 - "ProveedorController"
Cohesion: 0.05
Nodes (16): ClienteContactoController, Request, ClienteController, Request, ClienteDocumentoController, Request, ClientePortalController, Request (+8 more)

### Community 87 - "MaquinaController.php"
Cohesion: 0.09
Nodes (22): 1. RH: cumpleanos, 2. Vehiculos: servicios programados, 3. Maquinaria: servicios programados, 4. Ordenes de compra: fecha y autorizacion, 5. Seguros: vigencia y vencimientos, 6. Obras: inicio/fin programado y real, Categorias principales, Checkpoint 1: Servicio agregador (+14 more)

### Community 88 - "Component"
Cohesion: 0.33
Nodes (5): AppLayout, View, GuestLayout, View, Component

### Community 89 - "require-dev"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/breeze, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit (+1 more)

### Community 91 - "ZkDeviceClient"
Cohesion: 0.16
Nodes (6): VehiculosAlertasPreventivoKm, EmpresaConfig, VehiculoAlertaLog, Carbon, Collection, PreventivoVehiculoService

### Community 92 - "ObraFacturaPago"
Cohesion: 0.22
Nodes (3): ObraFacturaController, Request, ObraFactura

### Community 93 - ".abortarSiObraFueraDeArea"
Cohesion: 0.11
Nodes (17): 1. Modelo de Datos, 2. Backend (Controlador), 3. Rutas (`routes/web.php`), 4. Frontend (Vista), Avance 2026-07-13, Avance 2026-07-14, Avance 2026-07-14 - Detalle por empleado, Avance 2026-07-14 - Promedios teorico y real en detalle (+9 more)

### Community 94 - "ObraPresupuesto"
Cohesion: 0.38
Nodes (3): InventarioSeedInicial, InventarioDocumentoDetalle, BelongsTo

### Community 97 - "EmpleadoDocumento"
Cohesion: 0.20
Nodes (9): Fase 1: Auditoria del Flujo Actual, Fase 2: Matriz de Roles, Permisos y Destinatarios, Fase 3: Implementacion de Notificaciones In-App, Fase 4: Conexion con el Agente Instalable, Fase 5: Pruebas de Ciclo Completo, Fase 6: Produccion, Flujo Objetivo, Notas de Implementacion (+1 more)

### Community 98 - "SatCfdi.php"
Cohesion: 0.20
Nodes (9): Calidad de datos de clientes, CFDI SAT descargados, Equipos de computo, Facturacion CFDI: borrador antes de timbrar, Notificaciones del agente instalable, Pendientes para ejecutar manana, Pendientes registrados, Roadmap: Pendientes operativos (+1 more)

### Community 99 - "PreventivoMaquinaService"
Cohesion: 0.22
Nodes (8): graphify reference: extra exports and benchmark, Step 6b - Wiki (only if --wiki flag), Step 7 - Neo4j export (only if --neo4j or --neo4j-push flag), Step 7a - FalkorDB export (only if --falkordb or --falkordb-push flag), Step 7b - SVG export (only if --svg flag), Step 7c - GraphML export (only if --graphml flag), Step 7d - MCP server (only if --mcp flag), Step 8 - Token reduction benchmark (only if total_words > 5000)

### Community 100 - "edit.blade.php"
Cohesion: 0.22
Nodes (8): empleados.partials._contrato, empleados.partials._datos, empleados.partials._documentos, empleados.partials._emergencia, empleados.partials._epp, empleados.partials._kardex, empleados.partials._nomina, empleados.partials._notas

### Community 101 - "EmpresaConfigAreaController"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 103 - "ProfileController.php"
Cohesion: 0.22
Nodes (4): Request, VehiculoKmController, VehiculoEmpleado, VehiculoEmpleadoKmLog

### Community 104 - "PasswordResetLinkController.php"
Cohesion: 0.43
Nodes (4): PasswordResetLinkController, RedirectResponse, Request, View

### Community 105 - "RegisteredUserController.php"
Cohesion: 0.43
Nodes (4): RedirectResponse, Request, View, RegisteredUserController

### Community 106 - "InventarioKardexGerencialController.php"
Cohesion: 0.31
Nodes (3): ObraPlanoController, Request, ObraPlano

### Community 109 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 110 - "Kernel"
Cohesion: 0.40
Nodes (3): Kernel, ConsoleKernel, Schedule

### Community 111 - "Handler.php"
Cohesion: 0.47
Nodes (3): Handler, ExceptionHandler, Throwable

### Community 112 - "ComisionController.php"
Cohesion: 0.25
Nodes (4): CatalogoPilaController, Request, CatalogoPila, CatalogoPilasSeeder

### Community 113 - "InventarioSeedInicial.php"
Cohesion: 0.25
Nodes (6): EmailVerificationNotificationController, RedirectResponse, Request, PasswordController, RedirectResponse, Request

### Community 114 - "RequireApiKey.php"
Cohesion: 0.53
Nodes (4): Closure, Request, Response, RequireApiKey

### Community 115 - "InventarioMovimiento"
Cohesion: 0.33
Nodes (5): For /graphify explain, For /graphify path, graphify reference: query, path, explain, Step 0 — Constrained query expansion (REQUIRED before traversal), Step 1 — Traversal

### Community 116 - "RouteServiceProvider.php"
Cohesion: 0.39
Nodes (4): AuthenticatedSessionController, RedirectResponse, Request, View

### Community 117 - "UserFactory"
Cohesion: 0.47
Nodes (3): UserFactory, Factory, static

### Community 118 - "show.blade.php"
Cohesion: 0.33
Nodes (5): proveedores.partials._facturas, proveedores.partials._general, proveedores.partials._ordenes, proveedores.partials._pagado, proveedores.partials._productos

### Community 121 - "ComisionPersonal"
Cohesion: 0.18
Nodes (3): NominaGeneradorController, Request, NominaCorrida

### Community 122 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 123 - "edit.blade.php"
Cohesion: 0.40
Nodes (4): empresa_config.partials._centros_costo, empresa_config.partials._equipos_computo, empresa_config.partials._tipos_iva, maquinas.partials._preventivo_badge

### Community 124 - "edit.blade.php"
Cohesion: 0.40
Nodes (4): productos.partials._costos, productos.partials._general, productos.partials._kardex, productos.partials._proveedores

### Community 125 - "ComisionEtapaPersonal"
Cohesion: 0.48
Nodes (4): NewPasswordController, RedirectResponse, Request, View

### Community 127 - "edit.blade.php"
Cohesion: 0.50
Nodes (3): profile.partials.delete-user-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 130 - "edit.blade.php"
Cohesion: 0.33
Nodes (5): clientes._form, clientes.partials._contactos, clientes.partials._documentos, clientes.partials._portales, clientes.partials.tab-placeholder

### Community 131 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 132 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 133 - "2014_10_12_000000_create_users_table.php"
Cohesion: 0.43
Nodes (3): Carbon, Collection, PreventivoMaquinaService

### Community 148 - "2025_11_27_181836_create_empleado_contactos_emergencia_table.php"
Cohesion: 0.24
Nodes (4): D32RequestService, CaptchaResolverInterface, SatCaptchaResolverFactory, Client

### Community 168 - "EmailVerificationPromptController.php"
Cohesion: 0.33
Nodes (3): ObraContratoController, Request, ObraContrato

### Community 169 - "2025_12_11_192236_create_vehiculos_table.php"
Cohesion: 0.36
Nodes (3): ObraPresupuestoController, Request, ObraPresupuesto

### Community 181 - "2025_12_18_184201_add_rol_id_to_comision_personal_table.php"
Cohesion: 0.36
Nodes (3): Request, VehiculoDocumentoController, VehiculoDocumento

### Community 185 - "InventarioGerencialController.php"
Cohesion: 0.17
Nodes (5): AgentOpenLinkController, RedirectResponse, AgentNotificationController, Request, AgentOpenLink

### Community 200 - "2026_01_16_192603_create_maquinaria_reporte_snapshots_tables.php"
Cohesion: 0.31
Nodes (3): EmpleadoNotaController, Request, EmpleadoNota

### Community 210 - "2026_02_03_171828_create_inventario_stock_table.php"
Cohesion: 0.33
Nodes (3): InventarioImportStockCsv, Almacen, HasMany

### Community 211 - "SatCfdiConcepto.php"
Cohesion: 0.22
Nodes (7): AgendaController, Request, Model, Request, TelephonyClickToCallController, TelephonyPhoneNumber, PhoneNumberNormalizer

### Community 222 - "2026_02_25_160241_create_facturas_table.php"
Cohesion: 0.35
Nodes (3): AuthController, Request, UsuarioApp

### Community 243 - "2026_04_09_173414_create_obra_planeacion_gastos_table.php"
Cohesion: 0.47
Nodes (3): FacturaController, Request, Factura

### Community 245 - "2026_04_10_190703_create_obra_planeacion_semanal_table.php"
Cohesion: 0.43
Nodes (4): ConfirmablePasswordController, RedirectResponse, Request, View

### Community 246 - "64d5b5b5e15997d6185a02db44016242.php"
Cohesion: 0.22
Nodes (7): DatabaseCaptchaResolver, CaptchaAnswerInterface, CaptchaImageInterface, CaptchaAnswerInterface, CaptchaImageInterface, StoreCaptchaResolver, CaptchaResolverInterface

### Community 261 - "2026_04_23_194501_add_captcha_token_to_sat_document_requests_table.php"
Cohesion: 0.19
Nodes (4): Request, UsuarioController, InventarioMovimiento, BelongsTo

### Community 270 - "79c8b59029384a6aa84b536a6daf05e9.php"
Cohesion: 0.08
Nodes (18): DashboardGerencialController, Request, InventarioGerencialController, Request, InventarioKardexGerencialController, Request, ObraComisionesApiController, Request (+10 more)

### Community 496 - "4f230847dbc256924ade4eb2c2d00cb2.php"
Cohesion: 0.50
Nodes (3): For /graphify add, For --watch, graphify reference: add a URL and watch a folder

### Community 497 - "6a2cd42352527a6acb1a6fd285ce1209.php"
Cohesion: 0.50
Nodes (3): For git commit hook, For native CLAUDE.md integration, graphify reference: commit hook and native CLAUDE.md integration

### Community 502 - "graphify reference: incremental update and cluster-only"
Cohesion: 0.50
Nodes (3): For --cluster-only, For --update (incremental re-extraction), graphify reference: incremental update and cluster-only

### Community 514 - "SatCfdiEstadisticaController.php"
Cohesion: 0.24
Nodes (3): EmpleadoEppEntregaController, Request, EmpleadoEppEntrega

### Community 531 - "VerifyEmailController.php"
Cohesion: 0.21
Nodes (8): EmailVerificationPromptController, RedirectResponse, Request, View, RedirectResponse, VerifyEmailController, RouteServiceProvider, EmailVerificationRequest

### Community 714 - "FacturaBorradorAutorizado"
Cohesion: 0.19
Nodes (7): AgentAuthController, Request, EnsureActiveAgentDevice, Closure, Request, Response, AgentDevice

### Community 732 - ".edit"
Cohesion: 0.20
Nodes (3): Request, SatComplementoPagoController, ObraFacturaPago

### Community 735 - "SatCfdiConcepto.php"
Cohesion: 0.15
Nodes (4): Request, SatCfdiPagoController, HasMany, SatCfdiPago

### Community 739 - "InventarioGerencialController.php"
Cohesion: 0.53
Nodes (4): Closure, Request, Response, RedirectIfAuthenticated

### Community 747 - "empleados.blade.php"
Cohesion: 0.50
Nodes (3): giralda.partials._modal_epp, giralda.partials._modal_epp_historial, giralda.partials._modal_horas_extra

## Knowledge Gaps
- **490 isolated node(s):** `name`, `type`, `description`, `laravel`, `framework` (+485 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **34 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `79c8b59029384a6aa84b536a6daf05e9.php` to `Seeder`, `ObraReposicionGasto`, `SatCfdiEstadisticaController.php`, `ResidenteComisionesService`, `Proveedor`, `EquipoComputo`, `Obra`, `ObraFacturaBorrador`, `SatFactura`, `Queueable`, `ObraMaquina`, `Presupuesto`, `VerifyEmailController.php`, `.edit`, `ObraController`, `MaquinariaReporteSnapshot`, `Producto`, `SatDocumentRequest`, `NominaListaRaya`, `Empleado`, `ObraSolicitudGasto`, `ObraEmpleado`, `Seguro`, `FormRequest`, `Mantenimiento`, `MaquinaService`, `OrdenCompraDetalleController.php`, `PagoProveedor`, `web.php`, `CsfRequestService`, `UsuarioController.php`, `Cliente`, `OrdenCompra`, `SatFacturaPago`, `Vehiculo`, `VehiculoEmpleado`, `DatabaseCaptchaResolver.php`, `SatDownloadRequest`, `OrdenCompra`, `ImportProductosCsv`, `EmpresaConfig`, `LoginRequest`, `SatFacturacionController.php`, `auth.php`, `SatConcepto`, `ObraAsistencia`, `NominaCorrida`, `.create`, `EmpleadoNota`, `CatalogoRol`, `ProveedorController`, `ObraFacturaPago`, `ProfileController.php`, `PasswordResetLinkController.php`, `RegisteredUserController.php`, `InventarioKardexGerencialController.php`, `LoginRequest`, `ComisionController.php`, `InventarioSeedInicial.php`, `RouteServiceProvider.php`, `ComisionPersonal`, `ComisionEtapaPersonal`, `EmailVerificationPromptController.php`, `2025_12_11_192236_create_vehiculos_table.php`, `2025_12_18_184201_add_rol_id_to_comision_personal_table.php`, `InventarioGerencialController.php`, `2026_01_16_192603_create_maquinaria_reporte_snapshots_tables.php`, `FacturaBorradorAutorizado`, `SatCfdiConcepto.php`, `.edit`, `2026_02_25_160241_create_facturas_table.php`, `SatCfdiConcepto.php`, `.handle`, `2026_04_09_173414_create_obra_planeacion_gastos_table.php`, `2026_04_10_190703_create_obra_planeacion_semanal_table.php`, `2026_04_17_152650_alter_sat_cfdis_add_header_fields.php`, `2026_04_23_194501_add_captcha_token_to_sat_document_requests_table.php`, `NotificationController`, `SnapshotsController.php`, `2026_05_26_000004_create_centros_costo_table.php`?**
  _High betweenness centrality (0.109) - this node is a cross-community bridge._
- **Why does `Obra` connect `Obra` to `ObraReposicionGasto`, `SatCfdiEstadisticaController.php`, `Model`, `ResidenteComisionesService`, `Proveedor`, `79c8b59029384a6aa84b536a6daf05e9.php`, `SatFactura`, `Queueable`, `.edit`, `ObraController`, `AgentNotificationController`, `NominaListaRaya`, `ObraSolicitudGasto`, `HasFactory`, `Seguro`, `MaquinaEstadoCambiado`, `Mantenimiento`, `OrdenCompraDetalleController.php`, `EmailVerificationPromptController.php`, `2025_12_11_192236_create_vehiculos_table.php`, `Controller`, `CsfRequestService`, `OrdenCompra`, `DatabaseCaptchaResolver.php`, `SatFacturacionController.php`, `ObraAsistencia`, `NominaCorrida`, `.create`, `ProveedorController`, `ObraFacturaPago`, `2026_02_25_160241_create_facturas_table.php`, `ProfileController.php`, `InventarioKardexGerencialController.php`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Why does `SatFactura` connect `AuthenticatedSessionController.php` to `.handle`, `auth.php`, `Model`, `OrdenCompraDetalleController.php`, `SatFactura`, `ObraController`, `2026_01_05_195229_make_numero_pila_nullable_in_obras_pilas_table.php`, `.edit`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Are the 16 inferred relationships involving `Obra` (e.g. with `.contextoResidente()` and `.index()`) actually correct?**
  _`Obra` has 16 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `type`, `description` to the rest of the system?**
  _490 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.14789915966386555 - nodes in this community are weakly interconnected._
- **Should `ObraReposicionGasto` be split into smaller, more focused modules?**
  _Cohesion score 0.07439613526570048 - nodes in this community are weakly interconnected._