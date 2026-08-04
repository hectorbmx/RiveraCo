# Referencia general del sistema desde Graphify

Fecha de barrido: 2026-07-13  
Fuente principal: `graphify-out/graph.json`, `graphify-out/GRAPH_REPORT.md` y consultas con `graphify.exe`.

## Estado del grafo

- Archivo visual: `graphify-out/graph.html`
- Archivo base: `graphify-out/graph.json`
- Reporte base: `graphify-out/GRAPH_REPORT.md`
- Grafo reportado como construido desde commit: `396b0d1f`
- Commit actual consultado durante este barrido: `dbeb0a6fdea3c8c6689dfa1c952ddbb54f137e00`
- Nota: el grafo puede estar parcialmente desfasado respecto a los cambios locales posteriores. Conviene ejecutar `C:\Users\hecto\.local\bin\graphify.exe update .` despues de cerrar cambios importantes.

## Resumen numerico

- Nodos: 2776
- Aristas: 5101
- Comunidades: 501
- Tipo de archivo dominante: `code` con 2761 nodos.
- Origen de nodos: `ast` en 2776 nodos.
- Extraccion reportada: 93% `EXTRACTED`, 7% `INFERRED`, 0% `AMBIGUOUS`.
- Ciclos de importacion detectados: ninguno en el reporte.

## Hubs principales

Nodos con mayor conectividad calculada desde `graph.json`:

- `Model`: 218 conexiones.
- `Migration`: 201 conexiones.
- `Controller`: 152 conexiones.
- `Obra`: 144 conexiones.
- `.view()`: 115 conexiones.
- `HasFactory`: 78 conexiones.
- `web.php`: 59 conexiones.
- `Maquina`: 58 conexiones.
- `User`: 58 conexiones.
- `Empleado`: 57 conexiones.
- `OrdenCompra`: 46 conexiones.
- `Comision`: 44 conexiones.
- `SatCfdi`: 44 conexiones.
- `Proveedor`: 40 conexiones.
- `ObraController`: 40 conexiones.
- `ObraFacturaBorrador`: 36 conexiones.
- `SatFactura`: 36 conexiones.
- `ObraMaquina`: 35 conexiones.
- `ResidenteComisionesService`: 33 conexiones.
- `Producto`: 32 conexiones.
- `ObraEmpleado`: 32 conexiones.

## Comunidades mas grandes

- `ObraReposicionGasto`: 57 nodos.
- `Seeder`: 57 nodos.
- `User`: 56 nodos.
- `Comision`: 52 nodos.
- `NominaCorrida`: 47 nodos.
- `AttendanceUser`: 41 nodos.
- `Model`: 39 nodos.
- `.edit`: 37 nodos.
- `ResidenteComisionesService`: 37 nodos.
- `Migration`: 36 nodos.
- `Proveedor`: 34 nodos.
- `EquipoComputo`: 33 nodos.
- `Obra`: 29 nodos.
- `Maquina`: 29 nodos.
- `ObraFacturaBorrador`: 28 nodos.
- `InventarioDocumento`: 28 nodos.
- `Empleado`: 28 nodos.
- `SatFactura`: 27 nodos.
- `Queueable`: 27 nodos.
- `ObraController`: 26 nodos.
- `Presupuesto`: 26 nodos.
- `ObraMaquina`: 26 nodos.
- `SatDocumentRequest`: 25 nodos.
- `Producto`: 24 nodos.
- `NominaListaRaya`: 23 nodos.

## Lectura arquitectonica

El sistema es una aplicacion Laravel centrada en flujos operativos de construccion/administracion: obras, empleados, nomina, compras, proveedores, facturacion SAT, inventario, maquinaria, vehiculos, mantenimiento, reportes y configuracion de empresa.

La arquitectura real se organiza alrededor de modelos Eloquent, controladores web, vistas Blade y servicios puntuales. Los hubs del grafo muestran que `Obra`, `Empleado`, `User`, `OrdenCompra`, `Comision`, `SatCfdi`, `Proveedor`, `SatFactura` y `NominaCorrida` son entidades de alto impacto. Cambios en esos modelos o controladores asociados deben revisarse con mas cuidado porque atraviesan multiples pantallas y flujos.

## Modulos y hallazgos

### Obras

Nodo clave: `ObraController`  
Archivo principal: `app/Http/Controllers/ObraController.php`

El grafo muestra a `ObraController` como uno de los centros del sistema. Maneja listado, creacion/edicion, validaciones por area, KPIs, asistencia, facturas de obra, pagos, borradores de factura y autorizacion/rechazo de borradores.

Hallazgos:

- `Obra` es el modelo de mayor conectividad funcional despues de abstracciones base.
- `ObraController` concentra muchos subflujos; conviene evitar que siga creciendo sin separar servicios.
- Los borradores de factura de obra ya forman una comunidad propia (`ObraFacturaBorrador`) conectada a notificaciones y facturacion.
- Hay relacion fuerte entre obra, empleados, maquinaria, facturacion, gastos y reposiciones.

Rutas/archivos de referencia:

- `app/Models/Obra.php`
- `app/Http/Controllers/ObraController.php`
- `resources/views/obras/edit.blade.php`
- `app/Models/ObraFacturaBorrador.php`

### Nomina

Nodo clave: `NominaCorrida`  
Archivos principales:

- `app/Models/NominaCorrida.php`
- `app/Models/NominaRecibo.php`
- `app/Http/Controllers/Nomina/NominaCorridaController.php`
- `app/Services/Nomina/ListaRayaResolver.php`

El modulo de nomina aparece como comunidad propia. `NominaCorrida` conecta con generacion de recibos, guardado, autosave, cierre, pago, reapertura, listas de raya y trazabilidad de comisiones.

Hallazgos:

- `NominaCorrida` es el eje de corridas y recibos.
- `ListaRayaResolver` es la pieza que decide lista de raya por empleado u obra.
- `NominaRecibo` es la base para kardex, promedios futuros y pagos.
- Ya existe una base de estados (`abierta`, `cerrada`, `pagada`) y auditoria (`closed_by`, `closed_at`, `paid_by`, `paid_at`).
- Para promedios, el camino mas confiable es partir de corridas `pagadas` y recibos `pagado`.

Pendientes naturales:

- Completar pantalla de promedios de nomina.
- Definir si promedios solo toman corridas pagadas o tambien cerradas.
- Mejorar historial de cambios criticos si se requiere auditoria fina.

### Facturacion SAT y CFDI

Nodos clave:

- `SatFacturacionController`
- `SatFactura`
- `SatCfdi`
- `SatFacturaPago`
- `SatComplementoPagoController`

`SatFacturacionController` expone metodos para crear, previsualizar, timbrar, descargar PDF/XML, enviar, cancelar, relacionar facturas y manejar borradores. `SatCfdi` y `SatFactura` son modelos muy conectados.

Hallazgos:

- La facturacion SAT es un flujo critico con alto acoplamiento a clientes, obras, borradores, complementos de pago y Facturapi.
- `SatFacturacionController` tiene muchas responsabilidades y es candidato natural a extraer servicios para payloads, validaciones y sincronizacion.
- Complementos de pago ya tienen comunidad propia y relacion con facturas PPD/pagos.
- El flujo de borradores de factura de obra conecta obra con SAT y notificaciones.

Archivos de referencia:

- `app/Http/Controllers/Sat/SatFacturacionController.php`
- `app/Http/Controllers/Sat/SatComplementoPagoController.php`
- `app/Models/SatFactura.php`
- `app/Models/SatCfdi.php`
- `app/Models/SatFacturaPago.php`

### Ordenes de compra y pagos a proveedores

Nodos clave:

- `OrdenCompra`
- `OrdenCompraController`
- `OrdenCompraNotificationService`
- `PagoProveedor`
- `PagoProveedorController`

`OrdenCompra` tiene alta conectividad y se relaciona con autorizacion, detalle de compra, proveedores, pagos, notificaciones y Facturapi/CFDI cuando aplica.

Hallazgos:

- El flujo de OC y pagos proveedor ya usa permisos especificos (`ordenes_compra.*.access`, `pagos_proveedores.*.access`).
- `OrdenCompraNotificationService` concentra el envio de notificaciones del flujo.
- Es un buen patron para permisos y notificaciones en otros modulos.

Archivos de referencia:

- `app/Models/OrdenCompra.php`
- `app/Http/Controllers/OrdenCompraController.php`
- `app/Http/Controllers/PagoProveedorController.php`
- `app/Services/OrdenCompraNotificationService.php`

### Empleados

Nodo clave: `Empleado`

`Empleado` es uno de los modelos mas conectados. Alimenta nomina, listas de raya, asistencia, documentos, contacto de emergencia, kardex, comisiones, obras y vehiculos.

Hallazgos:

- Cambios en `Empleado` pueden impactar nomina, documentos, obra-empleado y reportes.
- El kardex de empleado ya consume recibos de nomina y distingue estados como `pendiente` y `pagado`.
- La integracion con listas de raya depende de `lista_raya_principal_id` y relaciones con obra activa.

Archivos de referencia:

- `app/Models/Empleado.php`
- `app/Http/Controllers/EmpleadoController.php`
- `resources/views/empleados/partials/_nomina.blade.php`
- `resources/views/empleados/partials/_kardex.blade.php`

### Inventario

Nodo clave: `InventarioDocumento`

Inventario se organiza alrededor de documentos, movimientos, stock, almacenes y servicio de aplicacion/cancelacion.

Hallazgos:

- `InventarioDocumento` conecta con controlador, servicio, detalles, movimientos, origenes y derivados.
- Existe separacion razonable entre controlador y `InventarioDocumentoService`.
- El flujo debe cuidarse con transacciones al aplicar/cancelar documentos porque afecta stock y movimientos.

Archivos de referencia:

- `app/Models/InventarioDocumento.php`
- `app/Http/Controllers/Inventario/InventarioDocumentoController.php`
- `app/Services/Inventario/InventarioDocumentoService.php`
- `app/Models/InventarioMovimiento.php`
- `app/Models/InventarioStock.php`

### Comisiones

Nodos clave:

- `Comision`
- `ComisionPersonal`
- `ResidenteComisionesService`
- `ComisionEtapa`

El grafo muestra comisiones como comunidad grande y conectada a empleados, obras, roles y nomina.

Hallazgos:

- `ResidenteComisionesService` es una pieza de dominio importante para calculos/flujo de comisiones.
- Nomina ya traza comisiones usadas por recibo con `NominaReciboComision`.
- Cambios en comisiones pueden afectar pagos de nomina y reportes de obra.

### Maquinaria, vehiculos y mantenimiento

Nodos clave:

- `Maquina`
- `Vehiculo`
- `Mantenimiento`
- `MaquinaEstadoCambiado`
- `PreventivoMaquinaService`

Hallazgos:

- `Maquina` es un hub importante por reportes, movimientos, seguros, preventivos y obra-maquina.
- Hay eventos/notificaciones de cambio de estado (`MaquinaEstadoCambiado`).
- Vehiculos y mantenimiento comparten patron de documentos, seguros y estados.

### Notificaciones

Comunidades relacionadas:

- `Queueable`
- `NotificationController`
- `AgentNotificationController`
- `OrdenCompraNotificationService`
- notificaciones de borradores de factura

Hallazgos:

- El sistema usa Laravel Notifications con canal `database`.
- El agente consume notificaciones por endpoint propio.
- Ya hay notificaciones para flujo de OC y borradores de factura.
- Pendientes operativos documentados: notificaciones por timbrado/cancelacion de factura y validacion del agente instalable.

## Riesgos y puntos de atencion

- `ObraController` y `SatFacturacionController` concentran muchas responsabilidades; al crecer conviene extraer servicios.
- `Obra`, `Empleado`, `User`, `OrdenCompra`, `Comision`, `SatCfdi`, `Proveedor`, `SatFactura` y `NominaCorrida` son modelos de alto impacto.
- Los cambios de permisos deben mantenerse en migraciones y seeders para no depender de configuracion manual.
- Los flujos de pago/facturacion/inventario deben conservar transacciones y auditoria.
- El grafo actual fue construido desde un commit anterior al actual, por lo que debe actualizarse despues de cerrar cambios.

## Uso recomendado de este documento

- Para preguntas de arquitectura, usar primero este documento y luego `graphify query`.
- Para impacto puntual, usar `graphify explain "Nodo"` o `graphify path "A" "B"`.
- Para cambios en modulos criticos, revisar siempre controlador, modelo, vistas y servicios conectados.
- Despues de cambios importantes, ejecutar:

```powershell
C:\Users\hecto\.local\bin\graphify.exe update .
```

## Comandos usados en el barrido

```powershell
C:\Users\hecto\.local\bin\graphify.exe query "Resumen general de arquitectura del sistema Rivera V2: modulos principales, controladores, modelos, vistas y flujos de negocio" --budget 6000
C:\Users\hecto\.local\bin\graphify.exe query "Cuales son los flujos criticos del sistema: nomina, obras, facturacion SAT, ordenes de compra, pagos, inventario, empleados y notificaciones" --budget 6000
C:\Users\hecto\.local\bin\graphify.exe explain "NominaCorrida"
C:\Users\hecto\.local\bin\graphify.exe explain "ObraController"
C:\Users\hecto\.local\bin\graphify.exe explain "SatFacturacionController"
C:\Users\hecto\.local\bin\graphify.exe explain "OrdenCompra"
C:\Users\hecto\.local\bin\graphify.exe explain "InventarioDocumento"
```

