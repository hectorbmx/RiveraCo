# Roadmap: Borradores de factura por obra

## Objetivo

Crear un flujo interno previo al timbrado de facturas para reemplazar el Excel que hoy genera el gerente de area.

El nuevo flujo debe permitir que, desde el tab de facturacion de una obra, se cree un borrador de factura, se imprima para revision, se notifique al gerente administrativo, se autorice y posteriormente se use como base para facturar y relacionar el CFDI real con la obra.

Vista principal:

- Local: `http://127.0.0.1:8000/obras/{obra}/edit?tab=facturacion`
- Produccion: `https://sirico.riveraco.com.mx/v2/public/obras/{obra}/edit?tab=facturacion`

## Contexto operativo

Flujo actual:

1. Gerente de area arma el borrador de factura en Excel.
2. Lo imprime.
3. Lo pasa a revision con gerente administrativo.
4. Gerente administrativo revisa y autoriza.
5. Se pasa a facturacion.
6. Al timbrar, la factura se relaciona con la obra y aparece en el tab de facturacion.

Flujo objetivo:

1. Gerente de area crea borrador desde la obra.
2. El sistema guarda datos fiscales, concepto e importes.
3. El sistema calcula el total.
4. El sistema genera notificacion de borrador creado.
5. Gerente administrativo revisa.
6. Gerente administrativo autoriza o rechaza.
7. El borrador autorizado queda listo para facturarse.
8. Cuando se timbre, el CFDI real se relaciona con la obra como ya ocurre.

## Decisiones iniciales

- El boton actual `Registro manual` se cambiara por `Crear borrador`.
- La primera version manejara un solo concepto por borrador.
- El regimen fiscal se tomara automaticamente del cliente de la obra.
- El total se calculara en frontend para experiencia de usuario y tambien en backend para seguridad.
- El borrador no sera CFDI ni factura timbrada.
- La conexion directa con Facturapi queda para una fase posterior.
- La impresion del borrador debe parecerse al formato operativo actual, no necesariamente al PDF fiscal timbrado.

## Modelo de datos propuesto

Tabla: `obra_factura_borradores`

Campos:

- `id`
- `obra_id`
- `cliente_id`
- `fecha`
- `forma_pago`
- `metodo_pago`
- `uso_cfdi`
- `regimen_fiscal`
- `sat_concepto_id`
- `concepto_descripcion`
- `cantidad`
- `subtotal`
- `iva`
- `retenciones`
- `descuentos`
- `total`
- `estatus`
- `creado_por`
- `autorizado_por`
- `autorizado_at`
- `rechazado_por`
- `rechazado_at`
- `observaciones_revision`
- `sat_factura_id`
- `sat_cfdi_id`
- `created_at`
- `updated_at`

Estatus sugeridos:

- `pendiente_revision`: creado y enviado a revision.
- `autorizado`: aprobado por gerente administrativo.
- `rechazado`: requiere correccion.
- `facturado`: ya se genero CFDI real y se relaciono.
- `cancelado`: anulado internamente.

## Relaciones esperadas

Modelo nuevo: `ObraFacturaBorrador`

Relaciones:

- `obra()` belongsTo `Obra`
- `cliente()` belongsTo `Cliente`
- `conceptoSat()` belongsTo `SatConcepto`
- `creador()` belongsTo `User`
- `autorizador()` belongsTo `User`
- `rechazador()` belongsTo `User`
- `satFactura()` belongsTo `SatFactura`, nullable
- `satCfdi()` belongsTo `SatCfdi`, nullable

## Campos del modal Crear borrador

Datos fiscales:

- Fecha
- Forma de pago, select con catalogo SAT.
- Metodo de pago, select con catalogo SAT.
- Uso CFDI, select con catalogo SAT.
- Regimen fiscal, tomado del cliente y mostrado como solo lectura.

Concepto:

- Concepto SAT, select desde `/sat/catalogos/conceptos`.
- Concepto editado, campo libre de texto.
- Cantidad, numerico, default `1`.
- Subtotal, numerico.
- IVA, numerico.
- Retenciones, numerico.
- Descuentos, numerico.
- Total, automatico.

Formula base:

`total = subtotal + iva - retenciones - descuentos`

Pendiente a validar:

- Si el IVA debe calcularse automaticamente a 16% desde subtotal o capturarse manualmente.
- Si retenciones y descuentos se manejaran como montos en primera version.

## Vista en tab Facturacion de obra

Agregar seccion `Borradores de factura`.

Columnas sugeridas:

- Fecha
- Concepto
- Subtotal
- IVA
- Retenciones
- Descuentos
- Total
- Estatus
- Creado por
- Autorizado por
- Acciones

Acciones:

- `Imprimir`
- `Editar`, si esta en `pendiente_revision` o `rechazado` y el usuario puede editar.
- `Autorizar`, solo usuarios con permiso/rol administrativo.
- `Rechazar`, solo usuarios con permiso/rol administrativo.
- `Facturar`, fase posterior, solo si esta `autorizado`.

## Permisos propuestos

Puede crear borrador:

- Gerente de area.
- Admin.
- SuperAdmin.

Puede autorizar/rechazar:

- Gerente administrativo: usuarios con rol Spatie `admin-rivera`.
- SuperAdmin.

Puede imprimir:

- Creador.
- Gerente administrativo.
- Admin.
- SuperAdmin.

Decision confirmada:

- `admin-rivera` representa al gerente administrativo para este flujo.
- Se usara el sistema granular de permisos de Spatie para mostrar/validar acciones.

## Notificaciones

Evento al crear borrador:

- Mensaje: `Borrador de factura creado para obra {clave/nombre}`
- Destinatarios: usuarios con rol Spatie `admin-rivera`.

Evento al autorizar:

- Mensaje: `Borrador de factura autorizado para obra {clave/nombre}`
- Destinatario: creador del borrador.

Evento al rechazar:

- Mensaje: `Borrador de factura rechazado para obra {clave/nombre}`
- Destinatario: creador del borrador.

Pendiente tecnico:

- Revisar el sistema actual de notificaciones antes de implementar.

## Fases de ejecucion

### Fase 1: Investigacion tecnica puntual

- [x] Ubicar boton actual `Registro manual`.
- [x] Revisar estructura del tab `facturacion` en `resources/views/obras/edit.blade.php`.
- [x] Revisar rutas de facturacion de obra existentes.
- [x] Revisar catalogo `SatConcepto`.
- [x] Revisar de donde salen forma de pago, metodo de pago y uso CFDI.
- [x] Revisar roles/permisos actuales.
- [x] Revisar sistema actual de notificaciones.

#### Hallazgos Fase 1

Fecha de investigacion: 2026-06-25.

Boton actual:

- Archivo: `resources/views/obras/edit.blade.php`.
- Linea aproximada: bloque del tab `facturacion`, cerca de `id="facturacion-tab"`.
- Texto actual: `Registro manual`.
- Comportamiento actual: activa/oculta un formulario viejo dentro de la misma vista, identificado como `FORM NUEVA FACTURA`.
- Tambien existe JS que vuelve a poner el texto `Registro manual`.
- Recomendacion: no reutilizar el flujo viejo como base funcional del borrador; crear flujo nuevo y solo reemplazar el boton visual por `Crear borrador`.

Tab facturacion de obra:

- Archivo principal: `resources/views/obras/edit.blade.php`.
- Ya muestra:
  - Facturas ligadas a obra desde `sat_facturas` y `sat_cfdis`.
  - Boton `+ Relacionar facturas`.
  - Control interno de pagos.
  - KPIs: total facturado, total pagado, pendiente de pago.
- Controlador principal: `app/Http/Controllers/ObraController.php`.
- Metodos relevantes existentes:
  - `facturasSatDeObra`.
  - `facturasDisponiblesParaRelacionar`.
  - `storeFacturaPago`.
  - `relacionarCfdis`.
- Rutas relevantes existentes:
  - `POST /obras/{obra}/relacionar-cfdis` -> `obras.relacionarCfdis`.
  - `POST /obras/{obra}/facturas-sat/pagos` -> `obras.facturas-sat.pagos.store`.

Facturacion SAT/Facturapi:

- Controlador: `app/Http/Controllers/Sat/SatFacturacionController.php`.
- Rutas relevantes:
  - `GET /sat/facturacion/create` -> `sat.facturacion.create`.
  - `POST /sat/facturacion/preview` -> `sat.facturacion.preview`.
  - `POST /sat/facturacion` -> `sat.facturacion.store`.
- La pantalla de facturacion ya carga:
  - `SatEmpresa::where('activo', true)`.
  - `Cliente::where('activo', true)`.
  - `Obra::orderBy('nombre')`.
  - `SatConcepto::where('activo', true)->orderBy('descripcion')`.

Catalogo de conceptos:

- Modelo: `app/Models/SatConcepto.php`.
- Tabla: `sat_conceptos`.
- Ruta de administracion: `/sat/catalogos/conceptos`.
- Campos utiles para borrador:
  - `id`
  - `codigo`
  - `clave_producto_servicio`
  - `clave_unidad`
  - `descripcion`
  - `unidad`
  - `objeto_impuesto`
  - `iva_tasa`
  - `incluye_iva`
  - `precio_unitario`
  - `activo`
- Recomendacion: cargar solo conceptos activos para el select.

Catalogos fiscales:

- `config/sat_catalogs.php` actualmente contiene:
  - `regimenes_fiscales`
  - `usos_cfdi`
- Forma de pago y metodo de pago no estan centralizados en config.
- La vista `resources/views/sat/facturacion/create.blade.php` tiene opciones hardcodeadas:
  - Uso CFDI: lista completa en el Blade, aunque tambien existe en `config/sat_catalogs.php`.
  - Metodo de pago:
    - `PUE - Pago en una sola exhibicion`
    - `PPD - Pago en parcialidades`
  - Forma de pago:
    - `03 - Transferencia electronica`
    - `01 - Efectivo`
    - `02 - Cheque nominativo`
    - `04 - Tarjeta de credito`
    - `28 - Tarjeta de debito`
    - `99 - Por definir`
- Recomendacion tecnica antes de Fase 3:
  - Extender `config/sat_catalogs.php` con `metodos_pago` y `formas_pago`.
  - Usar `config('sat_catalogs.usos_cfdi')`, `config('sat_catalogs.metodos_pago')` y `config('sat_catalogs.formas_pago')` en el modal de borrador.

Obra y cliente:

- `Obra` ya tiene relacion `cliente()`.
- `Cliente` ya tiene campos fiscales:
  - `regimen_fiscal`
  - `uso_cfdi_default`
  - `facturapi_customer_id`
  - `codigo_postal`
- Regimen fiscal del borrador debe salir de `$obra->cliente->regimen_fiscal`.
- Uso CFDI puede defaultar a `$obra->cliente->uso_cfdi_default` si existe.

Notificaciones:

- Se usa el sistema nativo de Laravel Notifications con canal `database`.
- Tabla: `notifications`.
- Controlador: `app/Http/Controllers/NotificationController.php`.
- Rutas:
  - `GET /notificaciones` -> `notifications.index`.
  - `GET /notificaciones/{id}/read` -> `notifications.read`.
  - `POST /notificaciones/mark-all-read` -> `notifications.markAllRead`.
- Layout principal muestra campana con `auth()->user()->unreadNotifications`.
- La UI espera que `data` tenga:
  - `tipo`
  - `mensaje`
  - opcional `url`
- Clases existentes de ejemplo:
  - `SolicitudGastoCreada`
  - `OrdenCompraCreada`
  - `SeguroVehiculoVencimiento`
- Recomendacion: crear `App\Notifications\ObraFacturaBorradorCreado`, `ObraFacturaBorradorAutorizado` y `ObraFacturaBorradorRechazado`, todas via `database`.

Roles y permisos:

- El proyecto usa Spatie Permission.
- `User` usa `HasRoles`.
- Middlewares registrados:
  - `role`
  - `permission`
  - `role_or_permission`
- Roles actuales semilla:
  - `super-admin`
  - `admin-rivera`
  - `jefe-obra`
  - `supervisor-obra`
  - `consulta`
- Permisos actuales base incluyen usuarios, roles, clientes, obras y detalles de obra.
- Hay ejemplos de permisos funcionales existentes:
  - `ordenes_compra.autorizar`
  - `ordenes_compra.imprimir`
  - `reposicion_gastos.autorizar.access`
- Recomendacion: crear permisos especificos para borradores en lugar de amarrar logica solo a roles:
  - `obra_factura_borradores.view`
  - `obra_factura_borradores.create`
  - `obra_factura_borradores.edit`
  - `obra_factura_borradores.print`
  - `obra_factura_borradores.authorize`
  - `obra_factura_borradores.reject`
  - `obra_factura_borradores.invoice`
- Asignacion inicial definida:
  - `super-admin`: todos.
  - `admin-rivera`: ver, imprimir, autorizar, rechazar, facturar.
  - `jefe-obra` o rol equivalente gerente de area: ver, crear, editar propios, imprimir.
  - El creador puede editar el borrador despues de enviarlo a revision.

Decisiones cerradas despues de Fase 1:

- `admin-rivera` representa al gerente administrativo.
- El creador si puede editar despues de enviarlo a revision.
- La notificacion de borrador creado se enviara a usuarios con rol `admin-rivera`.

### Fase 2: Base de datos y modelo

- [x] Crear migracion `obra_factura_borradores`.
- [x] Crear modelo `ObraFacturaBorrador`.
- [x] Agregar relaciones en `Obra`, `Cliente`, `SatConcepto` si hacen falta.
- [x] Validar casts de importes y fechas.

#### Hallazgos Fase 2

Fecha de ejecucion: 2026-06-25.

Archivos creados:

- `database/migrations/2026_06_25_090000_create_obra_factura_borradores_table.php`
- `app/Models/ObraFacturaBorrador.php`

Archivos modificados:

- `app/Models/Obra.php`
- `app/Models/Cliente.php`
- `app/Models/SatConcepto.php`
- `database/seeders/RolesAndPermissionsSeeder.php`

Base de datos:

- Migracion ejecutada con `php artisan migrate`.
- Tabla creada: `obra_factura_borradores`.

Permisos:

- Seeder ejecutado con `php artisan db:seed --class=RolesAndPermissionsSeeder`.
- Permisos agregados:
  - `obra_factura_borradores.view`
  - `obra_factura_borradores.create`
  - `obra_factura_borradores.edit`
  - `obra_factura_borradores.print`
  - `obra_factura_borradores.authorize`
  - `obra_factura_borradores.reject`
  - `obra_factura_borradores.invoice`

Asignacion de permisos:

- `super-admin`: todos los permisos.
- `admin-rivera`: ver, imprimir, autorizar, rechazar, facturar.
- `jefe-obra`: ver, crear, editar, imprimir.

### Fase 3: Crear borrador desde obra

- [x] Cambiar texto de boton `Registro manual` a `Crear borrador`.
- [x] Crear modal de captura.
- [x] Cargar selects fiscales.
- [x] Cargar select de conceptos SAT.
- [x] Mostrar regimen fiscal del cliente como solo lectura.
- [x] Calcular total en frontend.
- [x] Crear ruta `store` para guardar borrador.
- [x] Validar y recalcular total en backend.

#### Hallazgos Fase 3

Fecha de ejecucion: 2026-06-25.

Archivos modificados:

- `config/sat_catalogs.php`
- `routes/web.php`
- `app/Http/Controllers/ObraController.php`
- `resources/views/obras/edit.blade.php`

Implementado:

- Se agregaron catalogos centralizados:
  - `sat_catalogs.metodos_pago`
  - `sat_catalogs.formas_pago`
- Se agrego ruta:
  - `POST /obras/{obra}/factura-borradores`
  - Nombre: `obras.factura-borradores.store`
- En el tab `facturacion` se cambio el boton visual por `Crear borrador`.
- El boton se muestra con permiso `obra_factura_borradores.create`.
- Se agrego modal de captura con:
  - Fecha
  - Forma de pago
  - Metodo de pago
  - Uso CFDI
  - Regimen fiscal del cliente solo lectura
  - Concepto SAT
  - Concepto editado
  - Cantidad
  - Subtotal
  - IVA
  - Retenciones
  - Descuentos
  - Total automatico
- Al elegir concepto SAT se llena la descripcion y, si el catalogo tiene precio/IVA, sugiere subtotal e IVA.
- El backend recalcula el total con:
  - `total = subtotal + iva - retenciones - descuentos`
- El borrador se guarda en estatus `pendiente_revision`.
- Se agrego tabla de `Borradores de factura` dentro del tab facturacion.
- Se agrego boton `Imprimir` junto al estatus del borrador.
- Se agrego ruta imprimible:
  - `GET /obras/{obra}/factura-borradores/{borrador}/imprimir`
  - Nombre: `obras.factura-borradores.print`
- Se agrego vista imprimible:
  - `resources/views/obras/factura-borradores/print.blade.php`

Validaciones ejecutadas:

- `php -l app/Http/Controllers/ObraController.php`
- `php artisan route:list --name=obras.factura-borradores.store`
- `php artisan route:list --name=obras.factura-borradores.print`
- `php artisan view:cache`

### Fase 4: Listado de borradores

- [ ] Agregar tabla/listado de borradores en tab facturacion.
- [ ] Mostrar badges de estatus.
- [ ] Mostrar auditoria basica: creado por, autorizado por.
- [ ] Agregar accion `Imprimir`.

### Fase 5: Impresion

- [ ] Crear ruta de impresion.
- [ ] Crear vista imprimible.
- [ ] Incluir datos de obra, cliente, regimen fiscal, forma/metodo/uso CFDI.
- [ ] Incluir concepto e importes.
- [ ] Incluir estatus y firmas/datos de autorizacion si existen.

### Fase 6: Autorizacion y rechazo

- [ ] Crear rutas `autorizar` y `rechazar`.
- [ ] Validar permisos.
- [ ] Guardar `autorizado_por` y `autorizado_at`.
- [ ] Guardar `rechazado_por`, `rechazado_at` y `observaciones_revision`.
- [ ] Bloquear autorizacion doble.
- [ ] Bloquear cambios indebidos si ya esta facturado.

### Fase 7: Notificaciones

- [ ] Crear notificacion al guardar borrador.
- [ ] Crear notificacion al autorizar.
- [ ] Crear notificacion al rechazar.
- [ ] Confirmar destinatarios reales por rol/permiso.

### Fase 8: Conexion con facturacion real

- [ ] Agregar boton `Facturar` para borradores autorizados.
- [ ] Prellenar pantalla de Facturapi con datos del borrador.
- [ ] Al timbrar, vincular CFDI real con el borrador.
- [ ] Cambiar estatus a `facturado`.
- [ ] Mantener relacion con obra como ya ocurre.

## Primer paso recomendado al retomar

Ejecutar Fase 1 completa y documentar hallazgos directamente en este archivo antes de crear migraciones.

Orden sugerido:

1. Buscar `Registro manual`.
2. Revisar rutas existentes de obras/facturacion.
3. Revisar modelos/catalogos SAT disponibles.
4. Revisar notificaciones.
5. Confirmar permisos.
6. Ejecutar Fase 2.

## Notas abiertas

- Confirmar si el borrador debe nacer como `pendiente_revision` o como `borrador` editable.
- Confirmar si habra multiples conceptos por borrador en una segunda etapa.
- Confirmar si IVA, retenciones y descuentos seran capturados como monto o porcentaje.
- Confirmar quienes son exactamente los usuarios/roles de gerente administrativo.
- Confirmar si se requiere historial de cambios del borrador.
