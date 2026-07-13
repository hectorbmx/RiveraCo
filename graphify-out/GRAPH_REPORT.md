# Graph Report - .  (2026-07-10)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 2776 nodes · 5101 edges · 501 communities (470 shown, 31 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 347 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `396b0d1f`
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
- SatCfdiPago
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
- UsuarioApp
- EmpresaDocumentoTipo
- SatFacturacionController.php
- MaquinaEstadoMail.php
- ResidenteComisionController
- auth.php
- CatalogoPila
- .edit
- SatConcepto
- ObraAsistencia
- ContpaqiFacturaImportController.php
- SatComplementoPagoController
- .create
- composer.json
- scripts
- InventarioSeedInicial.php
- EmpleadoContactoEmergencia
- EmpleadoNota
- InventarioStockController.php
- ObraContrato
- ObraPlano
- SatEmpresa
- ComisionPersonal
- Component
- require-dev
- AuthenticatedSessionController.php
- RouteServiceProvider.php
- EmpresaConfigController.php
- ObraPresupuesto
- ProfileController.php
- VehiculoDocumentoController.php
- EmpleadoDocumento
- SatCfdi.php
- PreventivoMaquinaService
- edit.blade.php
- EmpresaConfigAreaController
- ConfirmablePasswordController.php
- NewPasswordController.php
- PasswordResetLinkController.php
- RegisteredUserController.php
- NotificationController
- LoginRequest
- StoreCaptchaResolver.php
- config
- Kernel
- Handler.php
- EmailVerificationPromptController.php
- RedirectIfAuthenticated.php
- RequireApiKey.php
- InventarioMovimiento
- OrdenCompraNotificationService
- UserFactory
- show.blade.php
- AgentAuthController.php
- AgentNotificationController
- InventarioKardexController.php
- psr-4
- edit.blade.php
- edit.blade.php
- PlaneacionGastosController.php
- SnapshotsController.php
- edit.blade.php
- Kernel
- Application
- edit.blade.php
- autoload-dev
- keywords
- empleados._form
- layouts.navigation
- obras.comisiones.create-form
- obras.partials.fila_planeacion
- index.blade.php
- cliente.blade.php

## God Nodes (most connected - your core abstractions)
1. `Controller` - 152 edges
2. `Obra` - 144 edges
3. `Maquina` - 58 edges
4. `User` - 58 edges
5. `Empleado` - 57 edges
6. `OrdenCompra` - 46 edges
7. `Comision` - 44 edges
8. `SatCfdi` - 44 edges
9. `ObraController` - 40 edges
10. `Proveedor` - 40 edges

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

## Communities (501 total, 31 thin omitted)

### Community 0 - "Seeder"
Cohesion: 0.07
Nodes (19): EmpresaSecurityController, Request, PlanoCategoria, assignPermissions(), up(), CatalogoRolesAliasSeeder, CatalogoRolesSeeder, ComisionesCatalogosSeeder (+11 more)

### Community 1 - "ObraReposicionGasto"
Cohesion: 0.07
Nodes (9): Request, ResidenteReposicionGastoController, ObraReposicionGastoController, Request, MetodoPagoEmpresa, ObraPlaneacionGasto, ObraReposicionGasto, ObraReposicionGastoDetalle (+1 more)

### Community 2 - "User"
Cohesion: 0.07
Nodes (18): User, Authenticatable, BaseTestCase, CreatesApplication, HasApiTokens, HasRoles, Notifiable, RefreshDatabase (+10 more)

### Community 3 - "Comision"
Cohesion: 0.06
Nodes (11): ComisionController, Request, ComisionController, Request, CatalogoActividadComision, Comision, ComisionDetalle, ComisionPerforacion (+3 more)

### Community 4 - "NominaCorrida"
Cohesion: 0.07
Nodes (8): NominaCorridaController, Carbon, Request, NominaGeneradorController, Request, NominaCorrida, NominaRecibo, NominaReciboComision

### Community 5 - "AttendanceUser"
Cohesion: 0.10
Nodes (13): AttendanceSync, AttendanceApiController, Request, AttendanceIngestController, Request, AttendanceController, Request, AttendanceWebController (+5 more)

### Community 6 - "Model"
Cohesion: 0.07
Nodes (12): CatalogoRolAlias, DocumentoVehiculo, MantenimientoDetalle, MantenimientoFoto, NominaPagoExtra, ProductoProveedorPrecio, SeguroMaquina, SeguroVehiculo (+4 more)

### Community 7 - "ResidenteComisionesService"
Cohesion: 0.14
Nodes (3): ComisionEtapa, Collection, ResidenteComisionesService

### Community 8 - "Migration"
Cohesion: 0.06
Nodes (6): NullableObraIdInObraPlaneacionGastosTable, CreateSatFacturasTable, CreateSatFacturaConceptosTable, CreateSatConceptosTable, AddFiscalFieldsToProveedoresTable, Migration

### Community 9 - "Proveedor"
Cohesion: 0.09
Nodes (3): Request, ProveedorController, Proveedor

### Community 10 - "EquipoComputo"
Cohesion: 0.12
Nodes (5): EquipoComputoController, Request, EquipoComputo, EquipoComputoFoto, EquipoComputoMovimiento

### Community 11 - "Maquina"
Cohesion: 0.11
Nodes (5): EmpresaConfigMaquinaController, Request, MaquinaController, Request, Maquina

### Community 13 - "ObraFacturaBorrador"
Cohesion: 0.09
Nodes (4): ObraFacturaBorrador, FacturaBorradorAutorizado, FacturaBorradorCreado, FacturaBorradorRechazado

### Community 14 - "InventarioDocumento"
Cohesion: 0.13
Nodes (6): InventarioDocumentoController, Request, InventarioDocumento, BelongsTo, HasMany, InventarioDocumentoService

### Community 15 - "SatFactura"
Cohesion: 0.15
Nodes (3): Request, SatFacturacionController, SatFactura

### Community 16 - "Queueable"
Cohesion: 0.12
Nodes (7): FacturaBorradorListoParaFacturar, OrdenCompraCreada, OrdenCompraFlujoNotification, SeguroVehiculoVencimiento, SolicitudGastoCreada, Notification, Queueable

### Community 17 - "ObraMaquina"
Cohesion: 0.12
Nodes (6): MaquinasGerencialController, Request, ObraMaquinaHorasController, Request, ObraMaquina, ObraMaquinaRegistro

### Community 18 - "Presupuesto"
Cohesion: 0.11
Nodes (7): Request, PresupuestoController, PresupuestoController, Presupuesto, PresupuestoDetalle, PresupuestoPila, PresupuestoResumen

### Community 19 - ".edit"
Cohesion: 0.16
Nodes (6): EmpresaConfigController, Request, CuentaBancoEmpresa, ObraFolio, ObraTipoConfiguracion, TipoIva

### Community 21 - "SatDocumentRequest"
Cohesion: 0.14
Nodes (4): Request, SatEmpresaController, BelongsTo, SatDocumentRequest

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
Nodes (5): NominaRecalcularCorrida, EmpresaConfigListaRayaController, Request, NominaListaRaya, ListaRayaResolver

### Community 26 - "Empleado"
Cohesion: 0.08
Nodes (5): EmpleadoDocumentoController, Request, Empleado, EmpleadoKardexService, Collection

### Community 27 - "ObraSolicitudGasto"
Cohesion: 0.13
Nodes (5): ObraSolicitudGastoController, Request, ObraPlaneacionSemanal, ObraSolicitudGasto, ObraSolicitudGastoDetalle

### Community 28 - ".view"
Cohesion: 0.14
Nodes (5): DashboardController, ReportesController, Request, VehiculoController, VehiculoAsignacionFoto

### Community 29 - "SatCfdi"
Cohesion: 0.17
Nodes (4): Request, SatCfdiController, SatCfdi, Builder

### Community 30 - "HasFactory"
Cohesion: 0.11
Nodes (5): AttendanceDeviceCheckpoint, ComisionEtapaFoto, ComisionEtapaPersonal, PlaneacionGasto, HasFactory

### Community 31 - "Area"
Cohesion: 0.22
Nodes (4): OrdenCompraController, Request, Area, CentroCosto

### Community 32 - "Middleware"
Cohesion: 0.15
Nodes (10): Authenticate, Request, EncryptCookies, PreventRequestsDuringMaintenance, TrimStrings, TrustHosts, TrustProxies, ValidateSignature (+2 more)

### Community 33 - "ObraEmpleado"
Cohesion: 0.17
Nodes (5): MaquinaRegistroController, Request, ObraEmpleadoController, Request, ObraEmpleado

### Community 34 - "Seguro"
Cohesion: 0.22
Nodes (3): MaquinaSeguroController, Request, Seguro

### Community 35 - "FormRequest"
Cohesion: 0.13
Nodes (5): ProfileUpdateRequest, StoreOrdenCompraRequest, UpdateOrdenCompraDetalleRequest, UpdateOrdenCompraRequest, FormRequest

### Community 36 - "Command"
Cohesion: 0.18
Nodes (5): BackfillObraEmpleadoRolId, CheckInsuranceExpirations, ImportLegacyCompras, ImportProductosLegacy, Command

### Community 37 - "MaquinaEstadoCambiado"
Cohesion: 0.24
Nodes (9): MaquinaEstadoCambiado, ProcessSatCsfRequestJob, ProcessSatDownloadJob, SendMaquinaEstadoNotification, Dispatchable, InteractsWithQueue, InteractsWithSockets, SerializesModels (+1 more)

### Community 38 - "Mantenimiento"
Cohesion: 0.16
Nodes (3): MantenimientoController, Request, Mantenimiento

### Community 39 - "MaquinaService"
Cohesion: 0.16
Nodes (4): ObraMaquinaController, Request, MaquinaMovimiento, MaquinaService

### Community 40 - "OrdenCompraDetalleController.php"
Cohesion: 0.18
Nodes (5): OrdenCompraDetalleController, Request, StoreOrdenCompraDetalleRequest, OrdenCompraDetalle, OrdenCompraTotalesService

### Community 41 - "PagoProveedor"
Cohesion: 0.22
Nodes (3): PagoProveedorController, Request, PagoProveedor

### Community 43 - "Controller"
Cohesion: 0.18
Nodes (9): DashboardGerencialController, Request, InventarioKardexGerencialController, Request, Controller, MaquinaSeguroController, AuthorizesRequests, BaseController (+1 more)

### Community 44 - "ObraPila"
Cohesion: 0.17
Nodes (5): ObrasGerencialController, Request, ObraPilaController, Request, ObraPila

### Community 45 - "CsfRequestService"
Cohesion: 0.18
Nodes (5): CsfRequestService, D32RequestService, CaptchaResolverInterface, SatCaptchaResolverFactory, Client

### Community 46 - "ServiceProvider"
Cohesion: 0.16
Nodes (5): AppServiceProvider, AuthServiceProvider, BroadcastServiceProvider, EventServiceProvider, ServiceProvider

### Community 47 - "SatCfdiProgramacion"
Cohesion: 0.20
Nodes (3): Request, ProgramacionPagosController, SatCfdiProgramacion

### Community 48 - "require"
Cohesion: 0.13
Nodes (15): require, barryvdh/laravel-dompdf, facturapi/facturapi-php, guzzlehttp/guzzle, intervention/image, laravel/framework, laravel/sanctum, laravel/tinker (+7 more)

### Community 49 - "UsuarioController.php"
Cohesion: 0.19
Nodes (4): Request, UsuarioController, ObraComisionesApiController, Request

### Community 50 - "CatalogoRol"
Cohesion: 0.20
Nodes (3): CatalogoRolController, Request, CatalogoRol

### Community 51 - "Cliente"
Cohesion: 0.26
Nodes (3): ClienteController, Request, Cliente

### Community 52 - "SatCfdiPago"
Cohesion: 0.16
Nodes (3): Request, SatCfdiPagoController, SatCfdiPago

### Community 53 - "SatFacturaPago"
Cohesion: 0.24
Nodes (3): Request, SatFacturaPagoController, SatFacturaPago

### Community 54 - "Vehiculo"
Cohesion: 0.22
Nodes (3): Request, VehiculoSeguroController, Vehiculo

### Community 55 - "api.php"
Cohesion: 0.21
Nodes (4): InventarioGerencialController, Request, PersonalGerencialController, Request

### Community 56 - "VehiculoEmpleado"
Cohesion: 0.23
Nodes (4): Request, VehiculoKmController, VehiculoEmpleado, VehiculoEmpleadoKmLog

### Community 57 - "ObraFactura"
Cohesion: 0.22
Nodes (3): ObraFacturaController, Request, ObraFactura

### Community 58 - "DatabaseCaptchaResolver.php"
Cohesion: 0.23
Nodes (6): Request, SatCaptchaController, SatCaptchaSession, DatabaseCaptchaResolver, CaptchaAnswerInterface, CaptchaImageInterface

### Community 59 - "SatDownloadRequest"
Cohesion: 0.22
Nodes (3): Request, SatDownloadController, SatDownloadRequest

### Community 61 - "SatMassDownloadService"
Cohesion: 0.27
Nodes (3): SatMassDownloadService, Service, SimpleXMLElement

### Community 63 - "Almacen"
Cohesion: 0.24
Nodes (4): InventarioImportStockCsv, InventarioSeedCatalogoStock, Almacen, HasMany

### Community 64 - "UsuarioApp"
Cohesion: 0.35
Nodes (3): AuthController, Request, UsuarioApp

### Community 65 - "EmpresaDocumentoTipo"
Cohesion: 0.20
Nodes (3): EmpleadoController, Request, EmpresaDocumentoTipo

### Community 67 - "SatFacturacionController.php"
Cohesion: 0.20
Nodes (3): SatFacturaConcepto, FacturapiService, Facturapi

### Community 68 - "MaquinaEstadoMail.php"
Cohesion: 0.23
Nodes (5): MaquinaEstadoMail, SatFacturaMail, Content, Envelope, Mailable

### Community 70 - "auth.php"
Cohesion: 0.25
Nodes (6): EmailVerificationNotificationController, RedirectResponse, Request, PasswordController, RedirectResponse, Request

### Community 71 - "CatalogoPila"
Cohesion: 0.25
Nodes (4): CatalogoPilaController, Request, CatalogoPila, CatalogoPilasSeeder

### Community 73 - "SatConcepto"
Cohesion: 0.27
Nodes (3): Request, SatCatalogoController, SatConcepto

### Community 74 - "ObraAsistencia"
Cohesion: 0.27
Nodes (3): AsistenciasController, Request, ObraAsistencia

### Community 75 - "ContpaqiFacturaImportController.php"
Cohesion: 0.29
Nodes (5): FacturaController, Request, ContpaqiFacturaImportController, Request, Factura

### Community 78 - "composer.json"
Cohesion: 0.20
Nodes (9): description, extra, laravel, dont-discover, license, minimum-stability, name, prefer-stable (+1 more)

### Community 79 - "scripts"
Cohesion: 0.20
Nodes (10): scripts, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan key:generate --ansi, @php artisan package:discover --ansi (+2 more)

### Community 80 - "InventarioSeedInicial.php"
Cohesion: 0.36
Nodes (3): InventarioSeedInicial, InventarioDocumentoDetalle, BelongsTo

### Community 81 - "EmpleadoContactoEmergencia"
Cohesion: 0.39
Nodes (3): EmpleadoContactoEmergenciaController, Request, EmpleadoContactoEmergencia

### Community 82 - "EmpleadoNota"
Cohesion: 0.31
Nodes (3): EmpleadoNotaController, Request, EmpleadoNota

### Community 83 - "InventarioStockController.php"
Cohesion: 0.31
Nodes (4): InventarioStockController, Request, InventarioStock, BelongsTo

### Community 84 - "ObraContrato"
Cohesion: 0.33
Nodes (3): ObraContratoController, Request, ObraContrato

### Community 85 - "ObraPlano"
Cohesion: 0.31
Nodes (3): ObraPlanoController, Request, ObraPlano

### Community 86 - "SatEmpresa"
Cohesion: 0.33
Nodes (4): Request, SatCfdiEstadisticaController, HasMany, SatEmpresa

### Community 88 - "Component"
Cohesion: 0.33
Nodes (5): AppLayout, View, GuestLayout, View, Component

### Community 89 - "require-dev"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/breeze, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit (+1 more)

### Community 90 - "AuthenticatedSessionController.php"
Cohesion: 0.39
Nodes (4): AuthenticatedSessionController, RedirectResponse, Request, View

### Community 91 - "RouteServiceProvider.php"
Cohesion: 0.36
Nodes (4): RedirectResponse, VerifyEmailController, RouteServiceProvider, EmailVerificationRequest

### Community 94 - "ObraPresupuesto"
Cohesion: 0.36
Nodes (3): ObraPresupuestoController, Request, ObraPresupuesto

### Community 95 - "ProfileController.php"
Cohesion: 0.43
Nodes (4): RedirectResponse, Request, View, ProfileController

### Community 96 - "VehiculoDocumentoController.php"
Cohesion: 0.36
Nodes (3): Request, VehiculoDocumentoController, VehiculoDocumento

### Community 98 - "SatCfdi.php"
Cohesion: 0.29
Nodes (3): HasMany, BelongsTo, SatCfdiConcepto

### Community 99 - "PreventivoMaquinaService"
Cohesion: 0.43
Nodes (3): Carbon, Collection, PreventivoMaquinaService

### Community 100 - "edit.blade.php"
Cohesion: 0.25
Nodes (7): empleados.partials._contrato, empleados.partials._datos, empleados.partials._documentos, empleados.partials._emergencia, empleados.partials._kardex, empleados.partials._nomina, empleados.partials._notas

### Community 102 - "ConfirmablePasswordController.php"
Cohesion: 0.43
Nodes (4): ConfirmablePasswordController, RedirectResponse, Request, View

### Community 103 - "NewPasswordController.php"
Cohesion: 0.48
Nodes (4): NewPasswordController, RedirectResponse, Request, View

### Community 104 - "PasswordResetLinkController.php"
Cohesion: 0.43
Nodes (4): PasswordResetLinkController, RedirectResponse, Request, View

### Community 105 - "RegisteredUserController.php"
Cohesion: 0.43
Nodes (4): RedirectResponse, Request, View, RegisteredUserController

### Community 108 - "StoreCaptchaResolver.php"
Cohesion: 0.43
Nodes (4): CaptchaAnswerInterface, CaptchaImageInterface, StoreCaptchaResolver, CaptchaResolverInterface

### Community 109 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 110 - "Kernel"
Cohesion: 0.40
Nodes (3): Kernel, ConsoleKernel, Schedule

### Community 111 - "Handler.php"
Cohesion: 0.47
Nodes (3): Handler, ExceptionHandler, Throwable

### Community 112 - "EmailVerificationPromptController.php"
Cohesion: 0.53
Nodes (4): EmailVerificationPromptController, RedirectResponse, Request, View

### Community 113 - "RedirectIfAuthenticated.php"
Cohesion: 0.53
Nodes (4): Closure, Request, Response, RedirectIfAuthenticated

### Community 114 - "RequireApiKey.php"
Cohesion: 0.53
Nodes (4): Closure, Request, Response, RequireApiKey

### Community 117 - "UserFactory"
Cohesion: 0.47
Nodes (3): UserFactory, Factory, static

### Community 118 - "show.blade.php"
Cohesion: 0.33
Nodes (5): proveedores.partials._facturas, proveedores.partials._general, proveedores.partials._ordenes, proveedores.partials._pagado, proveedores.partials._productos

### Community 122 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 123 - "edit.blade.php"
Cohesion: 0.40
Nodes (4): empresa_config.partials._centros_costo, empresa_config.partials._equipos_computo, empresa_config.partials._tipos_iva, maquinas.partials._preventivo_badge

### Community 124 - "edit.blade.php"
Cohesion: 0.40
Nodes (4): productos.partials._costos, productos.partials._general, productos.partials._kardex, productos.partials._proveedores

### Community 127 - "edit.blade.php"
Cohesion: 0.50
Nodes (3): profile.partials.delete-user-form, profile.partials.update-password-form, profile.partials.update-profile-information-form

### Community 131 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 132 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

## Knowledge Gaps
- **88 isolated node(s):** `name`, `type`, `description`, `laravel`, `framework` (+83 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **31 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `Controller` to `Seeder`, `ObraReposicionGasto`, `Comision`, `NominaCorrida`, `AttendanceUser`, `Proveedor`, `EquipoComputo`, `Maquina`, `InventarioDocumento`, `SatFactura`, `ObraMaquina`, `Presupuesto`, `.edit`, `ObraController`, `SatDocumentRequest`, `MaquinariaReporteSnapshot`, `Producto`, `NominaListaRaya`, `Empleado`, `ObraSolicitudGasto`, `.view`, `SatCfdi`, `Area`, `ObraEmpleado`, `Seguro`, `Mantenimiento`, `MaquinaService`, `OrdenCompraDetalleController.php`, `PagoProveedor`, `web.php`, `ObraPila`, `SatCfdiProgramacion`, `UsuarioController.php`, `CatalogoRol`, `Cliente`, `SatCfdiPago`, `SatFacturaPago`, `Vehiculo`, `api.php`, `VehiculoEmpleado`, `ObraFactura`, `DatabaseCaptchaResolver.php`, `SatDownloadRequest`, `UsuarioApp`, `EmpresaDocumentoTipo`, `SatFacturacionController.php`, `ResidenteComisionController`, `auth.php`, `CatalogoPila`, `SatConcepto`, `ObraAsistencia`, `ContpaqiFacturaImportController.php`, `SatComplementoPagoController`, `EmpleadoContactoEmergencia`, `EmpleadoNota`, `InventarioStockController.php`, `ObraContrato`, `ObraPlano`, `SatEmpresa`, `AuthenticatedSessionController.php`, `RouteServiceProvider.php`, `ObraPresupuesto`, `ProfileController.php`, `VehiculoDocumentoController.php`, `EmpresaConfigAreaController`, `ConfirmablePasswordController.php`, `NewPasswordController.php`, `PasswordResetLinkController.php`, `RegisteredUserController.php`, `NotificationController`, `EmailVerificationPromptController.php`, `AgentAuthController.php`, `AgentNotificationController`, `InventarioKardexController.php`, `PlaneacionGastosController.php`, `SnapshotsController.php`?**
  _High betweenness centrality (0.182) - this node is a cross-community bridge._
- **Why does `Obra` connect `Obra` to `ObraReposicionGasto`, `Comision`, `Model`, `ResidenteComisionesService`, `Proveedor`, `ObraFacturaBorrador`, `InventarioDocumento`, `SatFactura`, `ObraMaquina`, `Presupuesto`, `.edit`, `ObraController`, `NominaListaRaya`, `ObraSolicitudGasto`, `.view`, `SatCfdi`, `HasFactory`, `Area`, `ObraEmpleado`, `MaquinaService`, `web.php`, `ObraPila`, `UsuarioController.php`, `api.php`, `ObraFactura`, `UsuarioApp`, `.edit`, `ObraAsistencia`, `.create`, `ObraContrato`, `ObraPlano`, `.abortarSiObraFueraDeArea`, `ObraPresupuesto`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `Maquina` connect `Maquina` to `Seeder`, `Seguro`, `PreventivoMaquinaService`, `MaquinaEstadoMail.php`, `MaquinaEstadoCambiado`, `Mantenimiento`, `MaquinaService`, `.edit`, `Model`, `ObraMaquina`, `.edit`, `HasFactory`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **Are the 13 inferred relationships involving `Obra` (e.g. with `.contextoResidente()` and `.index()`) actually correct?**
  _`Obra` has 13 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `type`, `description` to the rest of the system?**
  _88 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.06766917293233082 - nodes in this community are weakly interconnected._
- **Should `ObraReposicionGasto` be split into smaller, more focused modules?**
  _Cohesion score 0.06578947368421052 - nodes in this community are weakly interconnected._