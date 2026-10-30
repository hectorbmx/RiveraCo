# App móvil: panel de mecánico y servicios programados

## Control del documento

- Fecha de apertura: 2026-10-23.
- Estado: T15.1-T15.3 de horas completados; T15.4-T15.5 de asignación/retiro y D09 backend/formulario móvil implementados, pendientes de pruebas del usuario. Validación integral pendiente. Grafo diferido al cierre.
- Alcance: Laravel, API y cliente Ionic/Angular inspeccionado en C:\xampp\htdocs\rivera\app (avances C13.1-C13.4 registrados).
- Propósito: mantener tareas, hallazgos, decisiones, pruebas y checkpoints en un solo documento.
- Regla de seguimiento: marcar una tarea como completada solo después de registrar su validación. Si se implementó pero no se validó, conservarla pendiente y explicar el avance en la bitácora.
- Revisión previa al cierre, 2026-10-29: [AMK1, estado/ubicación y acciones del residente](REVISION_ESTADO_UBICACION_MAQUINAS_AMK1.md). Cierre y actualización final del grafo pospuestos mientras se revisan autorización por obra, asignaciones abiertas a obras terminadas y las reglas del cambio de estado.

## Objetivo y alcance acordado

Crear una tercera vista móvil, `mecanico`, con tres entradas: Máquinas, Vehículos y Servicios programados.

El mecánico podrá consultar equipos y crear y dar seguimiento a servicios programados con el comportamiento que hoy existe en Laravel: borrador, orden de servicio, materiales, abastecimiento y registro de ejecución con evidencias.

**Ampliación acordada, 2026-10-29:** desde Máquinas también deberá registrar horas o ajustar el horómetro y administrar ubicación/asignación con paridad respecto de `/maquinas`. El bloque T15 se ejecutará antes de continuar con indicadores/filtros nuevos del catálogo y antes del cierre integral T14. La consulta y las tarjetas ya construidas no completan estas operaciones de escritura.

El reporte de fallas del residente es un flujo independiente. No se convertirá en la base de este módulo. La obra del servicio seguirá siendo opcional; entrar al panel de mecánico no dependerá de una asignación a obra.

## Decisiones de arquitectura

1. Crear `App\Http\Controllers\Api\V1\Mecanico\ServicioProgramadoController` para las operaciones móviles de servicios.
2. Usar un grupo propuesto `/api/v1/mecanico`, protegido con `auth:sanctum` y `app.mecanico.access`.
3. Conservar el controlador web y compartir reglas de negocio mediante servicios. No copiar métodos web con sus redirecciones a la API.
4. Fijar `tipo = programado` en servidor para este módulo y verificar ese tipo al consultar o modificar un servicio por ID.
5. Reutilizar las consultas de equipos mediante una capa compartida y respuestas apropiadas para mecánico, sin otorgar acceso al grupo gerencial completo.
6. Conservar contratos existentes de residente y gerencial. Agregar el panel de mecánico sin renombrarlos.
7. Los permisos nuevos se podrán crear manualmente en la UI. Avisar y ofrecer esa opción antes de introducir migraciones o cambios de datos para crearlos. No asignar permisos automáticamente.
8. Implementar el flujo vigente; el roadmap de confirmación, revisión y cierre administrativo requiere un alcance posterior o una decisión expresa.

## Hallazgos confirmados

| ID | Hallazgo | Evidencia / impacto |
|---|---|---|
| H01 | Solo existen paneles residente y gerencial en el servicio de contexto. | `app/Services/Mobile/AppMobileContextService.php`: opciones y panel predeterminado requieren ampliación. |
| H02 | `app.regular.access` actualmente permite el panel residente; no identifica al mecánico. | `puedeVerPanelResidente()`. Evitar usarlo para distinguir la tercera vista. |
| H03 | Login y `me` rechazan sesión regular sin contexto de obra cuando no hay acceso gerencial. | `app/Http/Controllers/Api/V1/AuthController.php`. El permiso regular por sí solo no resuelve el caso del mecánico. |
| H04 | Máquinas y vehículos tienen consultas API bajo `/gerencial`. | `routes/api.php` y controladores `Api/V1/Gerencial`. Están protegidos con `app.gerencial.access`. |
| H05 | Los detalles de equipos incluyen mantenimientos recientes y resumen, pero no el contrato completo para operar servicios. | `MaquinasGerencialController` y `VehiculosGerencialController`. Hay datos reutilizables, no un módulo de servicios móvil terminado. |
| H06 | La creación web admite máquina o vehículo, obra opcional y crea un borrador. | `MantenimientoController::store()`. El servicio programado de maquinaria exige horómetro y valida contra la lectura consolidada. |
| H07 | Generar orden crea folio OSM, fecha, autor y snapshot de materiales. | `generarOrdenServicio()` y modelo `MantenimientoMaterial`. |
| H08 | Los materiales pueden incluirse/excluirse con motivo antes de solicitar abastecimiento. | `actualizarMaterialesOrden()`. Exige `orden_generada` y ausencia de solicitudes. |
| H09 | Abastecimiento ya tiene un servicio reutilizable. | `MantenimientoAbastecimientoService::calcular()` y `crearSolicitud()`: cantidades, stock, surtido, faltante, solicitudes SMA de almacén o compra. |
| H10 | La evidencia web permite hasta ocho fotografías por servicio, de hasta 10 MB cada una. | `MantenimientoController::update()`: descripción de 200 caracteres y conversión WebP, calidad 80, máximo 1920 × 1920. No confundir con las fotos generales de máquina. |
| H11 | Estados aceptados actualmente: borrador, orden_generada, pendiente, en_proceso, completado y cancelado. | Validación de `update()`. No asumir que todos los estados del roadmap existen. |
| H12 | No se observó un bloqueo explícito por material pendiente al completar dentro de `update()`. | Requiere revisar el flujo completo y decidir la regla antes de habilitar esa operación en API. |
| H13 | Las plantillas usadas al generar materiales se resuelven con una función enfocada en maquinaria. | `materialesPreventivosParaMaquina()`. Confirmar el comportamiento de materiales para vehículos. |
| H14 | Varias reglas siguen dentro del controlador web. | Catálogos, validación del preventivo, generación de orden, costos y actualización. Deben extraerse gradualmente para compartirlas. |
| H15 | Las consultas gerenciales exponen datos adicionales, como costos, seguros o asignaciones. | Revisar los campos permitidos para mecánico antes de reutilizar respuestas completas. |
| H16 | Existe `GET /api/v1/gerencial/inventario/stock`, con búsqueda por nombre/SKU, filtro `almacen_id`, existencia, mínimos y paginación. | `InventarioGerencialController::stock()`. Exige acceso gerencial; no abrir todo ese grupo al mecánico. |
| H17 | Ese listado parte de `inventario_stock` y no incluye productos sin registro de stock; tampoco devuelve unidad ni disponibilidad neta calculada. | Para seleccionar materiales destinados a compra se necesitan también productos válidos sin existencias. No basta activar `con_existencia=0`: solo elimina el filtro de cantidad positiva. |
| H18 | La respuesta gerencial incluye costo promedio y valor de inventario; su resumen incluye indicadores de valor. | Reutilizar la consulta necesaria y adaptar campos para mecánico. Consultar productos no autoriza modificar catálogo, precios ni movimientos de inventario. |
| H19 | En el barrido de T05, hay tres tipos de maquinaria y ningún tipo de vehículo configurado localmente. | El catálogo móvil informa configuración ausente; no se crean tipos automáticamente. |
| H20 | El abastecimiento existente devuelve vacío para mantenimientos sin máquina; las plantillas tienen `maquina_id`, sin vínculo específico a vehículo. | D06 queda diagnosticado: se publica lectura/tipos de vehículo, pero se declara que sus plantillas automáticas no están implementadas. Extender ese flujo requiere alcance posterior. |
| H21 | El selector web de materiales usa el catálogo global de productos activos. | La API incluye productos sin fila de stock y limita las cantidades estrictamente a Giralda, sin revelar existencias de otros almacenes. |
| H22 | El alcance original de T04 es solo lectura; las rutas mecánicas de equipos aún no publican escrituras de horas o ubicación. | Las tarjetas operativas de los pasos 1 y 2 no habilitan esas funciones. Se agrega T15. |
| H23 | `guardarHoras()` diferencia máquina con asignación activa y máquina sin asignación. | Con obra crea `ObraMaquinaRegistro`; sin obra crea `MaquinaHorometroAjuste`. El servicio compartido exige lectura creciente y fecha no anterior a la última válida; no corrige hacia abajo ni edita lecturas históricas. |
| H24 | Administrar ubicación desde el listado web significa asignar a obra o finalizar la asignación y volver a patio. | `MaquinaService` crea/finaliza `ObraMaquina` y registra movimientos. Asignar exige estado operativa, ausencia de asignación activa y pilas activas en la obra. |
| H25 | El listado web representa ubicación como obra activa o patio, aunque el campo `maquinas.ubicacion` admite también camino y reparación. | Definir una presentación consistente; cambiar el campo no equivale a asignar o retirar una máquina. |
| H26 | La ruta web `cambiar-ubicacion` apunta a `MaquinaController::cambiarUbicacion`, que no está definido; el método sí existe en `MaquinaService`. | Revisar/completar ese flujo antes de anunciar cambios directos de ubicación; no copiar la ruta como si estuviera operativa. |
| H27 | Existen tablas y servicios para lecturas, ajustes, asignaciones y movimientos. | De entrada no se requiere una nueva tabla. Las API móviles deben reutilizar reglas y auditoría, con autorización por operación y protección frente a solicitudes simultáneas. |

## Decisiones pendientes

Resolver cada punto antes de implementar la tarea que depende de él. Se puede avanzar en tareas independientes. El registro de decisiones al final conserva los acuerdos del 2026-10-23; las preguntas se mantienen como referencia de origen.

| ID | Decisión | Afecta |
|---|---|---|
| D01 | ¿Ve todos los servicios programados o solo los asignados a su empleado? ¿Puede tomar servicios sin asignar? | Listado, detalle y autorización por registro. |
| D02 | ¿El mecánico se asigna automáticamente o puede elegir otro empleado? | Creación y edición. |
| D03 | ¿Puede ver/capturar costos y mano de obra? | Respuestas de equipos, detalle y ejecución. |
| D04 | ¿Puede completar y cancelar o esas acciones requieren otro permiso? | Acciones y transiciones. |
| D05 | ¿Qué condición de materiales permite iniciar/completar? ¿Cómo se tratan servicios sin materiales? | Ejecución y paridad web/API. |
| D06 | ¿Cómo se obtienen los materiales preventivos de vehículos? | Catálogos y generación de orden. |
| D07 | ¿Se conserva, sustituye o retira `app.regular.access` a este usuario al habilitar mecánico? | Accesos múltiples y panel predeterminado. No quitar permisos durante la implementación sin una decisión. |
| D08 | ¿Qué panel abre cuando tiene varios accesos? | Login, `me` y navegación móvil. |
| D09 | Aprobado 2026-10-29: camino/reparación conservan obra; regreso a la misma obra con asignación activa; sin obra se permiten camino/reparación/patio; con obra, patio exige retiro. Estado operativo independiente, motivo obligatorio, auditoría y permiso `maquinas.ubicacion.update.access` compartido web/app, otorgado manualmente. | Decisión resuelta; backend compartido/API y formulario móvil implementados. Pruebas funcionales a cargo del usuario. |

## Contratos propuestos

Base: `/api/v1/mecanico`. Son propuestas de diseño, no rutas implementadas.

| Método | Ruta | Uso |
|---|---|---|
| GET | `/maquinas` | Listado y búsqueda paginados. |
| GET | `/maquinas/{maquina}` | Detalle e historial autorizado. |
| GET | `/maquinas/{maquina}/opciones-operacion` | Propuesta T15: acciones autorizadas, lectura de referencia y búsqueda paginada de obras elegibles. Contrato final pendiente de T15.1. |
| POST | `/maquinas/{maquina}/horas` | Propuesta T15: registrar lectura con obra o crear ajuste sin asignación, decidido en servidor. |
| POST | `/maquinas/{maquina}/asignar-obra` | Propuesta T15: asignación con fecha, lectura inicial y notas; ubicación y movimiento coordinados. |
| POST | `/maquinas/{maquina}/baja-obra` | Propuesta T15: finalizar asignación y devolver a patio, conservando historial. |
| PATCH | `/maquinas/{maquina}/ubicacion` | Propuesta T15 sujeta a D09: cambio directo de ubicación con motivo/auditoría, sin romper la asignación. |
| GET | `/vehiculos` | Listado y búsqueda paginados. |
| GET | `/vehiculos/{vehiculo}` | Detalle e historial autorizado. |
| GET | `/inventario/productos` | Buscar productos por nombre/SKU y consultar unidad, existencia y disponibilidad de Giralda; incluir productos válidos sin stock para solicitar compra. Contrato por definir para delimitar el catálogo aplicable. |
| GET | `/servicios/catalogos` | Tipos preventivos por ámbito, categorías, estados y opciones de captura. |
| GET | `/servicios` | Seguimiento con filtros por equipo, estado, fechas y mecánico, según D01. |
| GET | `/servicios/{servicio}` | Cabecera, equipo, orden, materiales, abastecimientos, evidencias y acciones permitidas. |
| POST | `/servicios` | Crear un servicio programado en borrador. |
| PATCH | `/servicios/{servicio}` | Editar los campos permitidos en la etapa actual. |
| POST | `/servicios/{servicio}/generar-orden` | Crear folio y snapshot de materiales. |
| PATCH | `/servicios/{servicio}/materiales` | Ajustar inclusión y motivos de materiales de esa orden. |
| POST | `/servicios/{servicio}/abastecimientos` | Solicitar almacén o compra. |
| POST | `/servicios/{servicio}/evidencias` | Carga multipart de fotografías y descripciones. |
| POST | `/servicios/{servicio}/iniciar` | Acción explícita, sujeta a la regla acordada de ejecución. |
| POST | `/servicios/{servicio}/completar` | Capturar y validar ejecución final. |
| POST | `/servicios/{servicio}/cancelar` | Cancelación según autorización y efectos sobre documentos relacionados. |

Antes de programar cada contrato, documentar: parámetros, campos de entrada, respuesta de ejemplo, permisos, pertenencia/visibilidad del registro y errores. Usar formatos consistentes para paginación, fechas, cantidades y costos; definir códigos HTTP de validación, prohibición, inexistencia y conflicto.

## Tareas pequeñas y checkpoints

### T00. Barrido y documento de trabajo

- [x] Identificar consultas móviles de equipos y acciones web de servicios.
- [x] Separar el flujo residente del módulo de servicios programados.
- [x] Registrar hallazgos, decisiones y tareas en este documento.

**Checkpoint:** barrido de backend documentado. La inspección del proyecto móvil sigue pendiente.

### T01. Cerrar las reglas del primer alcance

- [ ] Registrar las decisiones D01 a D08 con fecha.
- [ ] Definir matriz de lectura, creación, edición, abastecimiento y ejecución.
- [ ] Confirmar la disponibilidad del permiso `app.mecanico.access`, guard `web`, y quién lo creará/asignará.

**Checkpoint:** reglas necesarias para los contratos acordadas; sin permisos asignados automáticamente.

### T02. Especificar sesión y selección de panel

- [x] Documentar ejemplos de login y `me` para mecánico sin obra, residente, gerencial y usuario con accesos múltiples.
- [x] Definir `paneles_disponibles`, `defaults.panel` y el significado de `contexto: null` para mecánico.
- [x] Revisar endpoints regulares que hoy solo exigen autenticación y detectar cuáles necesitan restringirse para cumplir «solo cosas de mecánico».

**Checkpoint documental completado, 2026-10-23:** [contrato de sesión T02](CONTRATO_SESION_APP_MECANICO_T02.md), con ejemplos y matriz de acceso. Se conservan campos y claves actuales; un panel abre directamente y varios requieren selector. La compatibilidad en ejecución se probará en T03 y la navegación móvil en T13. No se modificó código.

### T03. Implementar acceso al panel mecánico

- [x] Agregar `puedeVerPanelMecanico()` y la opción `mecanico` en el servicio de contexto.
- [x] Ajustar login, `me` y opciones para admitir sesión de mecánico activo sin obra.
- [x] Aplicar la matriz de acceso de T02 a las rutas involucradas.

**Checkpoint de código validado, 2026-10-23:** 15 pruebas de acceso/contexto y regresión de fotos dentro de una ejecución de 27 pruebas, 217 comprobaciones. Se protegieron 46 rutas existentes de las familias regular y gerencial, antes de resolver modelos. Se conserva la unión de accesos válidos y las verificaciones internas de obra. Ver detalle de validación en el contrato T02. **Activación operativa pendiente:** la consulta local confirmó que `app.mecanico.access` con guard `web` todavía no existe; debe crearse/asignarse manualmente. No se crearon permisos, no se modificaron usuarios y no se implementó todavía la navegación móvil.

### T04. Compartir las consultas de equipos

- [x] Extraer consultas/transformaciones reutilizables de máquinas y vehículos sin cambiar los contratos gerenciales existentes.
- [x] Agregar rutas de lectura bajo `/mecanico` con los campos autorizados.
- [x] Verificar búsqueda, filtros, paginación e historial.

**Checkpoint validado, 2026-10-23:** cuatro consultas protegidas con acceso mecánico, usuario activo y Sanctum. 24 pruebas/214 comprobaciones, 14 escenarios gerenciales idénticos por hash antes/después y 20 escenarios mecánicos de solo lectura local. Contratos y límites documentados en [consulta de equipos T04](CONTRATO_CONSULTA_EQUIPOS_MECANICO_T04.md). Se excluyen documentos/seguros y contactos administrativos de las respuestas mecánicas. Sin cambios de datos ni pantallas móviles.

### T05. Publicar catálogos de captura

- [x] Extraer las consultas de tipos preventivos activos por ámbito. La validación de un tipo elegido al guardar se compartirá con la web en T06.
- [x] Exponer lecturas sugeridas y las opciones que necesita el formulario.
- [x] Diagnosticar D06: informar que plantillas/abastecimiento automático de vehículos no están implementados; no publicar materiales sugeridos ficticios.
- [x] Compartir la consulta de inventario con una ruta de lectura para mecánico; delimitar Giralda en servidor y validar cualquier selección de almacén.
- [x] Incluir identificador, nombre, SKU, unidad, stock actual, reservado y disponible neto; contemplar productos válidos sin stock para compra y mantener precios sin edición.

**Checkpoint de lectura validado, 2026-10-23:** publicados catálogos y productos con 34 pruebas/295 comprobaciones, incluidas diez pruebas nuevas sobre SQLite en memoria. Se preservan las consultas web y gerenciales existentes. Contratos en [T05](CONTRATO_CATALOGOS_INVENTARIO_MECANICO_T05.md). Disponible de Giralda informativo, sin reserva/descuento ni escritura sobre productos. **Límite real:** los servicios programados de vehículo necesitan tipos configurados y su abastecimiento automático requiere ampliación; no se considera implementado por publicar el catálogo.

### T06. Compartir la creación y edición de borradores

- [x] Extraer validaciones y persistencia a una capa compartida con la web.
- [x] Implementar `store` y edición del controlador API; fijar `programado` en servidor.
- [x] Conservar un solo equipo por servicio, obra opcional y validaciones de lectura/preventivo.

**Checkpoint validado, 2026-10-23:** POST y PATCH mecánicos de borradores con asignación desde el empleado vinculado, edición solo del propio programado y estado/documentos releídos en transacción. Web/API comparten reglas y guardado; se comprobaron cabeceras equivalentes y rollback ante fallos de conceptos. 48 pruebas/398 comprobaciones, catorce pruebas nuevas de T06. [Contrato T06](CONTRATO_BORRADORES_SERVICIOS_MECANICO_T06.md). La API no modifica precios, ejecución ni abastecimiento; no se crearon datos en la base local.

### T07. Publicar listado y detalle de seguimiento

- [x] Aplicar el alcance de registros de D01 y filtros paginados.
- [x] Crear una respuesta de detalle con orden, materiales, solicitudes, documentos vinculados y evidencias.
- [x] Calcular `acciones_permitidas` en servidor según permisos y estado.

**Checkpoint validado, 2026-10-23:** GET de listado/detalle sobre los mismos mantenimientos web/API, exclusivamente programados. D01 permite consultar los ajenos; su modificación sigue prohibida. Filtros, paginación, estado, documentos y evidencias por servicio, avance compartido con la web y edición de borrador calculada en servidor. Operaciones futuras deshabilitadas hasta T08–T12. 56 pruebas/511 comprobaciones con SQLite efímero, sin cambios en la base local. [Contrato T07](CONTRATO_SEGUIMIENTO_SERVICIOS_MECANICO_T07.md). Se conserva el límite de abastecimiento automático de vehículos documentado en T05.

### T08. Compartir la generación de orden

- [x] Extraer generación de folio, autor, fecha y snapshot a un servicio compartido.
- [x] Agregar la acción API y conservar el comportamiento web.
- [x] Proteger reintentos y solicitudes simultáneas contra duplicación de folio/materiales.

**Checkpoint validado, 2026-10-23:** servicio compartido con web y POST de generación mecánica, bloqueo/relectura del mantenimiento, transacción única para metadata/snapshot y reintentos sin duplicación ni sustitución del snapshot. Acción de generación habilitada según propiedad/equipo/tipo/estado; web conserva redirección y exige permiso de edición. 64 pruebas/606 comprobaciones; rollback real al fallar el segundo material y reintentos web/API comprobados. [Contrato T08](CONTRATO_ORDEN_SERVICIO_MECANICO_T08.md). **Límite de prueba:** SQLite valida atomicidad/relectura, no bloqueo de fila; prueba simultánea en base de pruebas InnoDB pendiente para T14. Vehículos conservan generación sin plantilla, sin afirmar abastecimiento implementado.

### T09. Publicar el ajuste de materiales

- [x] Exponer inclusión/exclusión y motivos de los materiales de la orden.
- [x] Verificar pertenencia de cada material al servicio.
- [x] Bloquear modificaciones después de solicitar abastecimiento.

**Checkpoint validado, 2026-10-23:** PATCH mecánico parcial y guardado compartido con selección completa web. Solo inclusión/motivo; pertenencia de todos los IDs antes de escribir, propiedad/estado releídos y transacción con rollback probado. Cualquier solicitud existente bloquea; permiso web explícito. 71 pruebas/692 comprobaciones. [Contrato T09](CONTRATO_MATERIALES_SERVICIO_MECANICO_T09.md). T10 debe coordinar también la creación de solicitudes con el bloqueo del mantenimiento; no se da por implementada anticipadamente esa protección concurrente.

### T10. Publicar abastecimiento y avance

- [x] Reutilizar `MantenimientoAbastecimientoService` para solicitar almacén o compra.
- [x] Exponer requerido, disponible, surtido, faltante, folios y estado.
- [x] Conservar la prevención de solicitudes duplicadas y de cantidades ya surtidas.
- [x] Bloquear/releer el mismo mantenimiento durante la creación de solicitudes para coordinarla con los ajustes de T09 y evitar que se capture una selección simultáneamente obsoleta.

**Checkpoint validado, 2026-10-23:** POST de solicitudes compartidas con web, propiedad/estado y cantidades resueltas bajo bloqueo del mantenimiento. Recupera solicitud activa del mismo tipo, posteriores solo por faltante, stock intacto y folios/documentos en seguimiento. Coordinado con T09; rollback real probado y servicios cancelados/completados bloqueados. Una orden emitida vacía ya no cae a una plantilla posterior. 80 pruebas/783 comprobaciones. [Contrato T10](CONTRATO_ABASTECIMIENTO_SERVICIO_MECANICO_T10.md). Se conserva coexistencia de solicitudes por tipo y el límite de vehículos; prueba simultánea InnoDB pendiente en T14.

### T11. Publicar evidencias

- [x] Definir y publicar el contrato multipart, descripciones y URLs.
- [x] Reutilizar WebP y límites del servicio; asegurar limpieza ante fallos y límite acumulado.
- [x] Comprobar permisos y pertenencia del servicio.

**Checkpoint backend validado, 2026-10-23:** POST multipart compartido con web, WebP real 1920/calidad 80, límite acumulado de ocho, 10 MB por archivo y descripción 200. Permisos/propiedad y conteo bajo bloqueo; rollback y limpieza compensatoria de archivos probados ante fallo de optimizador, SQL y escritura parcial. Web revierte cabecera cuando falla su lote. 89 pruebas/877 comprobaciones en SQLite/disco aislados. [Contrato T11](CONTRATO_EVIDENCIAS_SERVICIO_MECANICO_T11.md). Conserva etapas de carga permitidas por web; cámara/galería real del teléfono pendiente de T13/T14. Cargas simultáneas InnoDB y límites HTTP del despliegue requieren validación integral.

### T12. Publicar ejecución y conservar cancelación web (D04)

- [x] Implementar inicio y cierre explícitos según D04/D05, compartiendo reglas con la web.
- [x] Validar fechas, mecánico, lectura final, datos de ejecución y efectos sobre costos/preventivos.
- [x] Conservar cancelación exclusivamente web; no publicar cancelación móvil ni introducir cierre administrativo. Rechazar reapertura y retroceso de etapas de ejecución.

**Checkpoint backend validado, 2026-10-26:** D05 confirmada: inicio permitido con faltantes, cierre solo con todo surtido; sin materiales incluidos no hay bloqueo. Transacciones, pertenencia, folio, fechas con zona, lectura final y cálculo automático de mano de obra compartidos con web. Cantidades repetidas por producto sin doble conteo; reintento idéntico no recalcula costos. 99 pruebas/959 comprobaciones; calculador preventivo existente verificado y medidores sin cambios. [Contrato y validación T12](CONTRATO_EJECUCION_SERVICIO_MECANICO_T12.md). C13.5 y recorrido integral T14 siguen pendientes.

### T13. Inspeccionar e implementar el cliente móvil

- [x] Localizar el proyecto móvil e inspeccionar sesión, selección de panel y componentes de máquinas/vehículos. Esta inspección puede adelantarse a T03.
- [x] Crear navegación `mecanico` sin requerir contexto de obra y reutilizar componentes de equipos cuando sus datos lo permitan.
- [x] Implementar lista/detalle, creación, orden, materiales, abastecimiento y ejecución por entregas.
- [x] Manejar expiración de sesión, errores de permisos, carga de evidencias y prevención de dobles envíos.

**Checkpoint:** recorrido funcional desde un usuario mecánico sin obra, sin mostrar pantallas de residente/gerencial que no tenga autorizadas.

**Avance parcial C13.1, 2026-10-23:** cliente Ionic/Angular localizado en `C:\xampp\htdocs\rivera\app`, repositorio separado en main y sin cambios locales. Inspeccionados auth/selector/guards/equipos/transporte/cámara; TypeScript baseline sin emisión pasó. [Inspección y checkpoints C13.1–C13.5](INSPECCION_CLIENTE_MOVIL_MECANICO_T13.md). T13 no está implementada: navegación/captura pueden adelantarse por entregas mientras T12 espera D05; la ejecución y recorrido completo no pueden darse por listos sin ese contrato.

**Avance C13.2 validado, 2026-10-23:** implementados shell/selector/guard mecánicos sin obra y consultas iniciales de equipos/servicios para sus destinos. Cliente tipado sobre `/mecanico`, preservación de errores Laravel y aislamiento de destinos residentes/gerenciales cubiertos por las rutas modificadas. TypeScript app/spec, build completo de desarrollo y 11 pruebas Chrome Headless exitosos. [Entrega C13.2](IMPLEMENTACION_NAVEGACION_MOVIL_MECANICO_C13_2.md). T13 sigue abierta: filtros/pruebas de vistas C13.3, captura/operaciones C13.4 y ejecución dependiente de T12 no están completos.

**Avance C13.3 validado, 2026-10-23:** filtros de fechas y equipo por búsqueda/selección paginada, persistencia de selección, limpieza/actualización y trece pruebas nuevas de vistas con contratos representativos. 24 pruebas Chrome Headless y TypeScript/build completos exitosos. [Entrega C13.3](IMPLEMENTACION_CONSULTAS_MOVIL_MECANICO_C13_3.md). Restan captura/operaciones C13.4, ejecución T12/C13.5 y validación integral T14 con usuario/dispositivo; no se realizó publicación final.

**Avance C13.4 validado, 2026-10-23:** formularios de borrador con equipo/catálogos/lecturas, orden, ajuste de materiales, solicitudes, productos de consulta y evidencias multipart con selección/cámara. Acciones del servidor, bloqueo de doble envío y manejo de resultados inciertos; vistas Ionic se recargan al reingresar. 47 pruebas Chrome Headless y TypeScript/build completos exitosos. [Entrega C13.4](IMPLEMENTACION_CAPTURA_MOVIL_MECANICO_C13_4.md). T13 sigue abierta por ejecución T12/C13.5 y recorrido conectado T14; D05 y límites de vehículos no se asumen resueltos.

**Avance C13.5 validado, 2026-10-26:** inicio/cierre con fecha y hora de México, lectura final por equipo, notas, confirmación, errores por campo y acciones del servidor. Inicio con faltantes; cierre solo habilitado tras surtido (o sin incluidos), conforme a T12/D05. Bloqueo de doble envío/respuesta incierta y descarte de respuestas de otro servicio. 63 pruebas Chrome Headless (16 nuevas), TypeScript app/spec y build completo exitosos. [Entrega C13.5](IMPLEMENTACION_EJECUCION_MOVIL_MECANICO_C13_5.md). Código de T13 completado con pruebas controladas; recorrido conectado y dispositivo siguen en T14. El usuario pidió solo C13.5: no se inició T14 ni publicación final.

### T14. Validación integral y entrega

- [ ] Probar máquina y vehículo, con y sin materiales, surtido parcial, compra, evidencias y ejecución.
- [ ] Probar compatibilidad de los accesos existentes y autorización por endpoint/registro.
- [ ] Verificar consistencia entre web y móvil; registrar versiones, resultados y pendientes reales.
- [ ] Ejecutar verificaciones de sintaxis, pruebas pertinentes y `php tools/check_mojibake.php` sobre archivos tocados; actualizar graphify después de cambios de código.
- [x] Ejecutar prueba concurrente de generación en una base de pruebas InnoDB, con dos peticiones y verificación de un solo folio/snapshot; no usar datos operativos. La cobertura SQLite de T08 no sustituye esa prueba. Validado en C14.1 con instancia MariaDB temporal y espera de bloqueo observada.

**Checkpoint:** evidencias de validación registradas y pendientes explícitos; ninguna tarea marcada lista solo porque existe el código.

**Avance C14.1 validado, 2026-10-23:** tres escenarios concurrentes y seis peticiones HTTP sobre InnoDB aislado: generación doble sin duplicar snapshot, rollback de la primera generación con reintento concurrente y edición concurrente rechazada tras emitir orden. Espera observada en INNODB_LOCK_WAITS, stock intacto y ninguna solicitud creada. [Prueba e informe C14.1](VALIDACION_CONCURRENCIA_APP_MECANICO_C14_1.md). T14 sigue abierta por el recorrido conectado/dispositivo y ejecución dependiente de T12/D05.

### T15. Operaciones de máquinas: horas y ubicación — prioridad actual

Ampliación del 2026-10-29. T15.1-T15.3 de horas completados y T15.4-T15.5 de asignación/retiro implementados. Sus pruebas funcionales quedan a cargo del usuario; D09 y T15.6 permanecen pendientes. Se conserva la consulta operativa validada: paso 1 API (40 pruebas/350 comprobaciones) y paso 2 contrato/tarjetas (30 pruebas Chrome Headless y build completo). Ver [T04](CONTRATO_CONSULTA_EQUIPOS_MECANICO_T04.md) y [tarjetas móviles](IMPLEMENTACION_CONSULTAS_MOVIL_MECANICO_C13_3.md#tarjetas-operativas-de-máquinas--2026-10-29). El recorrido con sesión real sigue pendiente.

#### T15.1. Contrato, permisos y reglas por operación

- [x] Documentar entradas, respuestas, acciones permitidas y errores de horas y asignación/retiro, incluyendo fechas con zona, contexto obsoleto y resultado incierto/reintento; reservar el cambio directo pendiente de D09.
- [x] Especificar usuario móvil activo y `app.mecanico.access`, además del permiso de la operación: `maquinas.horas.create.access`, `maquinas.asignar_obra.access` o `baja_maquinas.access`. Es contrato, no implementación nueva de autorización.
- [x] Definir capacidades por máquina y la misma autorización en cada escritura; ocultar un botón no sustituye la validación del servidor. Los endpoints futuros no anuncian capacidades habilitadas.
- [x] Delimitar ubicación frente a estado operativo y el alcance pendiente de D09; no publicar el cambio directo ni crear/asignar permisos por esta especificación.
- [x] Resolver D09 con el usuario antes de implementar cambios directos: transiciones, asignación activa y permiso aplicable. Aprobado 2026-10-29; implementación pendiente.
- [x] Especificar acceso mecánico independiente de obra y de los paneles residente/gerencial; operar una máquina asignada no requiere asignar al mecánico como residente.

**Checkpoint:** contrato revisable y matriz de permisos/estados definidos. D09 puede quedar pendiente para el cambio directo, sin bloquear las demás operaciones.

**Cierre documental T15.1, 2026-10-29:** [contrato de operaciones T15](CONTRATO_OPERACIONES_MAQUINAS_MECANICO_T15.md) creado y contrastado con la web actual. Entradas, capacidades, permisos, errores, fechas y recuperación definidos para horas/asignación/retiro; D09 sigue abierto y cambio directo deshabilitado en el diseño. Ejemplos JSON, formato y UTF-8 verificados. No se implementaron rutas, formularios o migraciones ni se modificaron permisos/datos. Por instrucción del usuario, detenerse al cerrar este paso y solicitar confirmación para T15.2.

#### T15.2. API compartida para registrar horas y ajustar horómetro

- [x] Extraer la coordinación de `guardarHoras()` para compartirla con web/API y reutilizar `MaquinaHorometroService`.
- [x] Resolver la asignación en servidor: con obra activa guardar registro de horas; sin asignación guardar ajuste con fecha, notas y obra relacionada opcional.
- [x] Conservar validación de lectura creciente y secuencia de fechas. No editar históricos ni permitir reducir el horómetro como parte de esta entrega.
- [x] Releer máquina/asignación bajo transacción y conservar autor, origen móvil y trazabilidad; rechazar contexto móvil obsoleto y recuperar dobles envíos exactos mediante huella persistida. Bloqueos concurrentes reales pendientes de InnoDB en T15.6.
- [x] Retornar datos actualizados para refrescar lectura, última fecha y preventivos.

**Checkpoint:** pruebas de paridad web/API, con y sin obra, permisos, cero de referencia, lectura igual/inferior, fechas inválidas, rollback y reintento sin duplicados.

**Cierre T15.2, 2026-10-29:** API de opciones/horas y servicio compartido implementados; **132 pruebas / 1,332 comprobaciones** de regresión, incluidas 16 pruebas nuevas de horas con SQLite en memoria. Migración nullable/única de `solicitud_hash` en las dos tablas existentes, aplicada solo en local (lote 151), sin poblar históricos ni crear capturas operativas. Rutas, sintaxis, UTF-8 y formato verificados; [contrato T15](CONTRATO_OPERACIONES_MAQUINAS_MECANICO_T15.md#cierre-t152--2026-10-29) actualizado. Concurrencia InnoDB y sesión real pendientes de T15.6. Detenerse y pedir confirmación para T15.3; no se implementó todavía su modal.

#### T15.3. Modal móvil de horas

- [x] Agregar acción compacta «Registrar horas» o «Ajustar horómetro» por permiso de sesión; consultar capacidades/asignación actuales del servidor antes de habilitar el formulario.
- [x] Mostrar máquina, obra y lectura actual; capturar nueva lectura, fechas y notas. Ofrecer obra relacionada opcional solo cuando corresponda.
- [x] Mostrar errores por campo, bloquear doble envío y recuperar el estado tras una respuesta incierta mediante reintento del payload original conservado por usuario/máquina.
- [x] Refrescar tarjeta y detalle después de guardar, sin perder búsqueda/página.

**Checkpoint:** pruebas de formulario, capacidades y recarga; compilación móvil y verificación UTF-8.

**Cierre T15.3, 2026-10-29:** modal Ionic, DTO/transporte de horas, selección paginada de obra relacionada y acciones en tarjetas implementados. Contexto del servidor, fecha/hora de México con offset, errores 409/422, bloqueo de doble envío, recuperación por sesión y descarte de respuestas de otra máquina/usuario. **112 pruebas Chrome Headless**, incluidas 22 nuevas, y build completo de desarrollo exitosos; UTF-8 verificado. [Implementación y límites T15.3](IMPLEMENTACION_HORAS_MAQUINA_MOVIL_T15_3.md). Sin escrituras operativas ni despliegue; recorrido conectado/dispositivo pendiente de T15.6. Detenerse y pedir confirmación antes de T15.4.

#### T15.4. API compartida de ubicación y asignaciones

- [x] Implementar API reutilizando `MaquinaService` para asignar y finalizar, sin `update` aislado de ubicación.
- [x] Publicar búsqueda de obras vigentes con pilas activas y capacidades según permiso/estado/asignación.
- [x] Implementar validaciones compartidas de fechas y lectura contra historial, conservando el retiro sin inventar una lectura final.
- [x] Implementar bloqueo/relectura de máquina antes de asignación, contexto esperado y reintentos exactos con huella persistida.
- [x] Coordinar ubicación, asignación, movimiento, autor y huella bajo transacción. Verificación funcional/concurrente pendiente del usuario.
- [x] Implementar backend de cambio directo con D09 aprobado, controlador H26 y reglas compartidas web/API; corregir presentación de ubicación H25. Pruebas funcionales pendientes del usuario.

**Checkpoint funcional pendiente del usuario:** código de asignar/retirar entregado; no se declara paridad probada ni se marca todo el bloque validado mientras falten sus pruebas y D09.

**Entrega de código T15.4, 2026-10-29:** dos POST y `uso=asignacion` implementados, con permisos existentes y reglas compartidas con web. Migración nullable/única en movimientos aplicada solo en local, lote 152, sin llenar históricos ni crear movimientos operativos. [Detalles T15.4](IMPLEMENTACION_ASIGNACIONES_MAQUINAS_T15_4.md). Solo sintaxis, rutas, formato y UTF-8 comprobados; sin pruebas funcionales ni actualización del grafo por instrucción del usuario. D09 continúa pendiente. Detenerse y pedir confirmación para T15.5 de asignar/retirar.

#### T15.5. Formularios móviles de ubicación

- [x] Agregar «Asignar a obra» o «Retirar de obra» según capacidades; buscador de obra y datos de asignación, con confirmación de retiro.
- [x] Agregar opciones de cambio directo con contrato D09 aprobado y backend disponible; formulario móvil implementado. Pruebas funcionales pendientes del usuario.
- [x] Mostrar destino y efecto de asignar/retirar; conservar historial mediante la API existente y distinguir ubicación de estado operativo.
- [x] Implementar bloqueo de doble envío, errores y actualización de tarjeta/detalle tras guardar. Verificación funcional pendiente del usuario.

**Checkpoint funcional pendiente del usuario:** código de formularios de asignación/retiro/cambio directo entregado; faltan recorridos con máquina asignada, en patio, no operativa y usuario sin permiso. No se declara paridad probada. D09 aprobado e implementado en backend y móvil.

**Entrega de código T15.5, 2026-10-29:** formularios desde listado/detalle, búsqueda paginada de obras elegibles, fecha/lectura/notas de asignación, confirmación de retiro y recuperación de operación incierta por usuario/máquina. Compilación TypeScript y plantillas Angular verificada; sin pruebas funcionales ni actualización del grafo. [Detalles T15.5](IMPLEMENTACION_ASIGNACIONES_MOVIL_T15_5.md). Detenerse para informar y pedir confirmación antes de continuar.

#### T15.6. Validación integral de estas operaciones

- [ ] Recorrer con sesión real web/app: registrar horas con obra, ajustar sin obra, asignar, retirar y los cambios directos aprobados.
- [ ] Verificar autor/origen, registros anteriores intactos, recálculo preventivo y ausencia de asignaciones o movimientos duplicados.
- [ ] Probar carreras pertinentes sobre InnoDB aislado, incluyendo captura de horas frente a retiro y dos intentos de asignación; no usar datos operativos.
- [ ] Registrar resultados, límites y pendientes; actualizar contratos y checkpoints. Mantener T14 abierto hasta completar el recorrido global y de dispositivo.

**Checkpoint:** paridad funcional comprobada. Continuar después con indicadores/filtros nuevos del catálogo, seguros si se acuerdan y cierre global T14.

## Orden de trabajo y dependencias

1. T01 y T02: definir reglas y contratos; adelantar la inspección móvil de T13.
2. T03: acceso sin obra y tercera vista en el contrato de sesión.
3. T04 a T07: consultas y primera entrega de creación/seguimiento.
4. T08 a T10: orden, materiales y abastecimiento.
5. T11 y T12: evidencias y ejecución.
6. T13: completar pantallas conforme se validen los contratos.
7. T14: cerrar la entrega con el recorrido completo.

**Prioridad vigente desde 2026-10-29:** conservar los pasos 1 y 2 del catálogo como completados en pruebas controladas; ejecutar T15.1 → T15.2 → T15.3 → T15.4 → T15.5 → T15.6. Después retomar indicadores/filtros y terminar T14. D09 no bloquea horas ni asignación/retiro. Reportar cada paso y pedir confirmación antes del siguiente. T15.1-T15.3 completados y código de asignación/retiro T15.4-T15.5 entregado; T15.6 y D09 siguen pendientes. **Instrucción vigente del usuario:** dejar las pruebas funcionales a su cargo y actualizar el grafo hasta el final; no ejecutarlas ni actualizarlo automáticamente en los siguientes pasos.

## Fuentes de referencia

- `app/Http/Controllers/MantenimientoController.php`
- `app/Services/Mantenimiento/MantenimientoAbastecimientoService.php`
- `app/Models/Mantenimiento.php`
- `app/Models/MantenimientoMaterial.php`
- `app/Models/MantenimientoAbastecimiento.php`
- `app/Http/Controllers/Api/V1/Gerencial/MaquinasGerencialController.php`
- `app/Http/Controllers/Api/V1/Gerencial/VehiculosGerencialController.php`
- `app/Services/Mobile/AppMobileContextService.php`
- `app/Http/Controllers/MaquinaController.php`: registro/ajuste de horas, asignación y retiro de obra.
- `app/Services/Maquinas/MaquinaHorometroService.php`: lecturas, ajustes y secuencia histórica.
- `app/Services/Maquinas/MaquinaService.php`: asignación, ubicación y movimientos.
- `resources/views/maquinas/index.blade.php`: acciones y permisos del listado web.
- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Http/Controllers/Api/V1/AppContextController.php`
- `routes/api.php`
- `docs/PLAN_ORDEN_SERVICIO_MANTENIMIENTO.md`: referencia del flujo web implementado; verificar contra código al extraer reglas.
- `docs/ROADMAP_SEGUIMIENTO_SERVICIOS_MANTENIMIENTO.md`: propuesta de evolución; no constituye evidencia de implementación.

## Bitácora de trabajo

| Fecha | Tarea / hallazgo | Acción y resultado | Validación | Pendiente siguiente |
|---|---|---|---|---|
| 2026-10-23 | T00 / H01-H15 | Barrido inicial de backend y creación de este plan. No se implementaron endpoints ni pantallas del módulo. | Lectura de código, rutas y documentos; las reglas pendientes se registraron como D01-D08. | T01: acordar alcance y permisos; T02: sesión y selección de panel. |
| 2026-10-23 | T01 / T05 / H16-H18 | El usuario acepta las reglas iniciales propuestas y agrega consulta de productos del almacén. Se confirmó el endpoint de stock existente y sus límites; se amplió el plan, sin implementar rutas. | Inspección de `routes/api.php`, `InventarioGerencialController::stock()` y modelo `Producto`. | Resolver materiales/ejecución, vehículos, permisos múltiples y alcance exacto del catálogo; preparar T02. |
| 2026-10-23 | T02 | Contrato de sesión documentado con ejemplos de login, `me`, opciones y accesos múltiples; matriz de familias de endpoints y protección requerida. Hallazgo: logout del controlador sin ruta móvil `/v1` registrada. | Inspección de controladores, servicio de contexto y rutas registradas; revisión de JSON y UTF-8. Sin cambios de código ni endpoints futuros probados. | T03: implementar acceso y comprobar la matriz; permiso mecánico pendiente de creación/asignación manual. |
| 2026-10-23 | T03 | Agregado panel mecánico y sesión sin obra. `AppMobileAccessService` comparte validación de usuario activo; `EnsureMobileAppAccess` protege familias existentes y prepara el control del futuro grupo mecánico. Se ordenó antes del binding para prohibir acceso antes de resolver IDs. | 27 pruebas/217 comprobaciones, sintaxis PHP, UTF-8 y rutas registradas. Pruebas de acceso con consultas/modelos controlados, sin escribir en la base local. Consulta de solo lectura: permiso mecánico aún no creado. | Crear/asignar permiso en UI; T04: consultas compartidas de equipos. Pantallas pendientes en T13. |
| 2026-10-23 | T04 | Extraídas consultas compartidas de equipos; publicados listados y detalles para mecánico con proyección operativa. Controladores gerenciales conservan sus contratos y los endpoints de registros existentes. | 24 pruebas/214 comprobaciones; 14 respuestas gerenciales idénticas por hash; 20 escenarios mecánicos con lecturas locales; sintaxis, UTF-8 y cuatro rutas registradas. | T05: catálogos de captura y productos de Giralda. Activación de permisos y pantallas móviles siguen siendo tareas separadas. |
| 2026-10-23 | T05 / H19-H21 | Catálogos compartidos con web, lecturas sugeridas y consulta de productos activos con stock opcional de Giralda. Vehículos informan la configuración y capacidades reales. | 34 pruebas/295 comprobaciones, SQLite efímero para consultas reales; paridad gerencial y catálogos web; consultas locales de solo lectura; sintaxis y UTF-8. | T06: creación/edición de borradores y validación compartida del tipo elegido. Configurar tipos de vehículo y decidir extensión de su abastecimiento antes de anunciar flujo completo de vehículo. |
| 2026-10-23 | T06 | Creación/edición API mediante controlador mecánico y servicio de borradores compartido con web. Guardado transaccional, asignación por empleado, restricciones de propiedad/tipo/estado y protección de campos internos. | 48 pruebas/398 comprobaciones; paridad web/API, emergencias web preservadas, rollback real en SQLite, relectura de estado; sintaxis y UTF-8. | T07: listado/detalle y acciones permitidas. Configuración y abastecimiento de vehículos siguen con los límites registrados en T05. |
| 2026-10-23 | T07 | Publicados listado/detalle de programados con alcance D01, filtros, paginación, documentos, evidencias y avance compartido. Acciones reflejan propiedad/estado y endpoints disponibles; consultas ajenas permitidas, edición ajena bloqueada. | 56 pruebas/511 comprobaciones; consultas reales sobre SQLite en memoria, aislamiento entre servicios, stock intacto, máquina/vehículo, sintaxis, rutas y UTF-8. | T08: generación de orden compartida con web y protección de concurrencia/reintentos. |
| 2026-10-23 | T08 | Generación web/API compartida, bloqueo del mantenimiento, folio y snapshot atómicos e idempotentes; acción y permiso web actualizados. Cierre: 2026-10-23T14:20:48.8399194-06:00 (America/Mexico_City). | 64 pruebas/606 comprobaciones, rollback real, reintentos web/API, sintaxis, rutas, UTF-8 y graphify actualizado. Prueba concurrente InnoDB pendiente en T14. | T09; iniciar no antes de 2026-10-23T14:30:48.8399194-06:00 (pausa mínima de 10 minutos). |
| 2026-10-23 | T09 | Ajuste parcial API y selección completa web compartidos; inclusión/motivos, pertenencia y bloqueo por etapa/solicitudes, con permiso web explícito. Cierre: 2026-10-23T14:39:42.7010818-06:00 (America/Mexico_City). | 71 pruebas/692 comprobaciones; rollback entre filas, aislamiento, permisos, sintaxis, rutas, UTF-8 y graphify actualizado. | T10; iniciar no antes de 2026-10-23T14:49:42.7010818-06:00. Coordinar creación de solicitudes con el bloqueo del mantenimiento. |
| 2026-10-23 | T10 | Solicitudes web/API compartidas bajo bloqueo del mantenimiento, faltantes calculados en servidor y reintentos por tipo sin duplicación. Selección vacía congelada, servicios cancelados bloqueados y ejecución preservada. Cierre: 2026-10-23T15:01:15.8814252-06:00 (America/Mexico_City). | 80 pruebas/783 comprobaciones; rollback, stock intacto, folios/autor, paridad web/API, sintaxis, rutas, UTF-8 y graphify actualizado. | T11; iniciar no antes de 2026-10-23T15:11:15.8814252-06:00. Prueba concurrente InnoDB y límites de vehículos siguen registrados. |
| 2026-10-23 | T11 | Evidencias multipart compartidas con web, WebP real y límite acumulado bajo bloqueo. Limpieza compensatoria y rollback de lote/cabecera web probados. Cierre: 2026-10-23T15:21:28.4855723-06:00 (America/Mexico_City). | 89 pruebas/877 comprobaciones; GD/WebP real, fallos SQL/optimizador/disco parcial, permisos, sintaxis, rutas, UTF-8 y graphify actualizado. | Siguiente punto no antes de 2026-10-23T15:31:28.4855723-06:00. T12 requiere respuesta a D05: consulta enviada sobre iniciar/completar con materiales pendientes; no asumir opción por falta de respuesta. T13/T14 conservan sus dependencias. |
| 2026-10-23 | T13 / C13.1 parcial | Localizado cliente Ionic/Angular en C:\xampp\htdocs\rivera\app, main, HEAD dddabff y árbol limpio. Inspeccionados sesión/selector/guards/equipos/API/cámara y documentados checkpoints de integración. Cierre de inspección: 2026-10-23T15:38:32.2551244-06:00 (America/Mexico_City). | TypeScript baseline --noEmit --incremental false, exit 0; Git limpio antes/después. Solo documentación del backend modificada y UTF-8 validado. No se implementaron pantallas ni se ejecutó recorrido móvil. | Continuar C13.2 de T13 (sesión/navegación), independiente de D05, no antes de 2026-10-23T15:48:32.2551244-06:00. T12 y ejecución final siguen pendientes de la respuesta del usuario. |
| 2026-10-23 | T13 / C13.2 | Navegación mecánica implementada en repositorio app, guard con revalidación de sesión, selector sin obra, aislamiento de destinos cubiertos y consulta inicial de máquinas/vehículos/servicios con detalle. Transporte conserva status/errores. Cierre: 2026-10-23T16:07:45.8307837-06:00 (America/Mexico_City). | TypeScript app/spec, build Angular de desarrollo y 11 pruebas Chrome Headless exitosos; diff/UTF-8 validados. Temporales retirados y grafo backend actualizado sin paquetes generados. | Continuar C13.3/C13.4, no antes de 2026-10-23T16:17:45.8307837-06:00. T13 sigue parcial y T12 espera D05; sin commit/push hasta completar todo el alcance. |
| 2026-10-23 | T13 / C13.3 | Filtros de fechas y equipo con selección paginada persistente, limpieza y actualización; pruebas de vistas representativas, respuestas antiguas y errores de sesión. Cierre: 2026-10-23T16:53:02.8015192-06:00 (America/Mexico_City). | 24 pruebas Chrome Headless exitosas (13 nuevas), TypeScript app/spec, build de desarrollo, diff/UTF-8 y grafo backend actualizado. La revisión de sandbox tuvo límite de uso temporal y se completó tras su restablecimiento; sin eludir el control. | C13.4, no antes de 2026-10-23T17:03:02.8015192-06:00. T12/C13.5 esperan D05 y T14 conserva pruebas conectadas/dispositivo; sin commit/push final aún. |
| 2026-10-23 | T13 / C13.4 | Captura de borradores, orden, materiales, solicitudes, productos y evidencias integrados al cliente; acciones del servidor, prevención de duplicados y recarga al reingresar en Ionic. Cierre: 2026-10-23T17:22:40.1244914-06:00 (America/Mexico_City). | 47 pruebas Chrome Headless, TypeScript app/spec y build de desarrollo exitosos; diff, UTF-8 estricto de 16 archivos móviles, mojibake de documentos y grafo backend actualizado (7703 nodos/13344 relaciones). Temporales retirados. | Siguiente punto no antes de 2026-10-23T17:32:40.1244914-06:00. T12/C13.5 esperan D05; T14 conserva recorrido conectado, dispositivo/cámara y concurrencia InnoDB. Sin commit/push final hasta completar y validar el alcance. |
| 2026-10-23 | T14 / C14.1 | Prueba concurrente InnoDB aislada con tres escenarios y seis peticiones HTTP: orden idempotente, rollback/reintento y edición concurrente rechazada. Cierre: 2026-10-23T19:23:34.2342304-06:00 (America/Mexico_City). | MariaDB 10.4.32, espera real entre conexiones en los tres escenarios, stock intacto, sin solicitudes. Instancia apagada y datos/temporales retirados; informe JSON conservado. Sintaxis PHP/PowerShell, guard de aislamiento, UTF-8/diff y grafo (7725 nodos/13380 relaciones) validados. Reintento elevado del grafo resuelve WinError 5. | Siguiente punto no antes de 2026-10-23T19:33:34.2342304-06:00. T14 sigue abierta por usuario/dispositivo y recorrido completo; T12/C13.5 esperan D05. No se realizó commit/push final. |
| 2026-10-26 | T12 | D05 confirmada e implementada: iniciar con faltantes, completar solo surtido; cancelación solo web. Ejecución bajo bloqueo y mano de obra compartida; ajuste de cantidades repetidas, fechas y lecturas. Cierre: 2026-10-26T09:35:41.792505-06:00 (America/Mexico_City). | 99 pruebas/959 comprobaciones; paridad HTTP web/API, rollback SQL, reintentos, permisos, preventivo real, rutas, sintaxis y UTF-8. Grafo actualizado: 7760 nodos/13474 relaciones. | C13.5, no antes de 2026-10-26T09:45:41.792505-06:00. T14 conserva recorrido conectado/dispositivo y carreras de ejecución/surtido. Sin commit/push hasta terminar el alcance. |
| 2026-10-26 | T13 / C13.5 | Inicio y cierre móvil según T12/D05: fecha México, lectura final, notas, acciones de servidor, bloqueo de doble envío y recuperación ante resultado incierto. Cierre: 2026-10-26T10:08:10.881930-06:00 (America/Mexico_City). | 63 pruebas Chrome Headless (16 nuevas), TypeScript app/spec y build completo exitosos; UTF-8/diff verificados. Graphify actualizado: 7765 nodos/13480 relaciones/1254 comunidades. Salida temporal de build retirada. | T14 sigue pendiente; siguiente punto no antes de 2026-10-26T10:18:10.881930-06:00 (America/Mexico_City). Pedido humano limitado a C13.5: no se inició T14 ni commit/push. |

### 2026-10-29: ampliación de consulta y nueva prioridad T15

- Pasos 1 y 2 del catálogo ya validados: datos operativos en API y contrato/tarjetas móviles; 40 pruebas/350 comprobaciones backend y 30 pruebas Chrome Headless más build móvil. Sin recorrido conectado con cuenta real.
- El usuario solicita paridad de horas y ubicación respecto de `/maquinas`; se contrasta con el alcance original de consulta T04 y se registran H22-H27.
- Se agrega T15.1-T15.6 como prioridad previa a indicadores/filtros y al cierre T14. No se implementaron las escrituras ni se modificaron permisos, usuarios o datos en esta actualización documental.
- D09 identifica lo que falta definir para cambios directos de ubicación; asignar a obra y retirar al patio sí pertenecen al alcance solicitado.
- Validación de esta actualización: revisión contra controladores, rutas, vista y servicios web existentes; comprobación de formato y UTF-8 del documento.

### 2026-10-29: cierre documental T15.1

- Creado `CONTRATO_OPERACIONES_MAQUINAS_MECANICO_T15.md` con permisos por operación, propuestas de rutas, contexto esperado, campos de captura, ejemplos, errores y recuperación de reintentos.
- Se conserva la decisión del modo de horas en servidor y las reglas de lectura/fecha del servicio existente. La API exige fechas explícitas en registros de obra para recuperación reproducible; la web mantiene su normalización opcional.
- D09 queda pendiente con alcance explícito: no publicar cambios directos de ubicación ni inventar sus permisos. Asignar/retirar y horas pueden continuar con los permisos actuales.
- Validación documental: contraste con fuente web, ejemplos JSON, `git diff --check` y `php tools/check_mojibake.php`. Sin código ni datos modificados y sin pruebas de endpoints futuros.
- Siguiente: solicitar confirmación del usuario para T15.2; no iniciarlo en el mismo paso.

### 2026-10-29: cierre T15.2

- Implementadas opciones de captura y API de horas, servicio de coordinación compartido con web y validación del contexto esperado. Los reintentos exactos se recuperan por huella persistida con autor/origen, sin repetir ni recalcular la lectura original.
- El esquema anterior no conservaba todo el contexto para demostrar identidad: ajuste documentado antes de implementar y migración nullable/única creada para ambas tablas. Aplicada solo en local, lote 151; cero huellas no nulas en datos operativos tras la migración. No se crearon permisos ni se modificó producción.
- 132 pruebas / 1,332 comprobaciones, con HTTP sobre SQLite aislado para permisos, captura, contexto, reintentos, rollback, paridad web/API y migración reversible. Sintaxis, rutas, formato y UTF-8 verificados. Las pruebas concurrentes de bloqueo en InnoDB permanecen en T15.6.
- Siguiente: pedir confirmación para T15.3 (modal móvil). No avanzar automáticamente a pantallas ni ubicación.

### 2026-10-29: cierre T15.3

- Modal de registro/ajuste integrado en las tarjetas del listado y detalle. La operación consulta contexto/capacidades frescas, conserva referencias nulas/cero y maneja fechas de México con offset explícito.
- Captura con obra relacionada opcional paginada, validación, errores por campo, bloqueo de doble envío y revisión de contexto tras 409. El resultado actualiza la fila/detalle sin recargar ni perder búsqueda/página.
- Un envío incierto conserva el payload exacto por usuario/máquina en memoria y sesión; solo permite reintentar ese contenido y descarta respuestas ajenas. Se documenta el límite de persistencia de sesión.
- 112 pruebas exitosas de módulo/contrato en Chrome Headless (22 nuevas), build completo de desarrollo y UTF-8 verificados. HTTP interceptado/datos controlados, sin capturas operativas ni cambios del backend. Salida temporal de compilación retirada al cerrar.
- Siguiente: pedir confirmación para T15.4, API de asignación/retiro y revisión de D09 antes de cualquier cambio directo. No iniciar ese paso automáticamente.

### 2026-10-29: entrega de código T15.4

- Publicados POST de asignación/retiro y catálogo de obras elegibles con permisos específicos, estado/asignación y contexto esperado. Se reutilizan reglas, transacciones y auditoría de `MaquinaService`, ahora compartidas y reforzadas para web/API.
- Huella nullable/única en `maquina_movimientos` para reintentos exactos por actor/acción/contexto. Migración aplicada solo en local, lote 152; no se crearon movimientos ni se poblaron históricos.
- Se comprobaron sintaxis PHP, registro de rutas, formato y UTF-8. No se ejecutaron pruebas funcionales ni se actualizó el grafo, conforme a la instrucción del usuario. El checkpoint funcional no se marca validado.
- D09 y la ruta web de cambio directo permanecen pendientes; esta entrega no habilita camino/reparación ni altera estado operativo.
- Siguiente: solicitar confirmación para T15.5 (formularios móviles de asignar/retirar). Las pruebas quedan para el usuario y el grafo para el cierre.

### 2026-10-29: entrega de código T15.5

- Integrados formularios de asignación y retiro al patio desde tarjetas del listado y detalle. Se consultan capacidades/contexto actuales; obra destino paginada, fecha, horómetro inicial y notas de asignación; retiro con confirmación explícita y fecha de cierre.
- Resultado actualiza tarjeta/detalle conservando búsqueda y página. Un resultado incierto congela el payload por usuario/máquina y permite reabrir «Confirmar operación pendiente», incluso si cambió la asignación. Se usa la recuperación exacta del servidor.
- TypeScript y compilación Angular de desarrollo verificados; formato y UTF-8 revisados. No se ejecutaron pruebas funcionales ni se actualizó el grafo, por instrucción del usuario. Sin movimientos operativos ni cambios de base de datos en este paso.
- D09 sigue abierto; T15.6 y el recorrido del usuario permanecen pendientes. Informar la entrega y pedir confirmación antes de cualquier siguiente paso.

### 2026-10-29: decisión parcial D09

- El usuario confirma conservar la asignación a obra cuando la máquina pase a camino o reparación. El retiro explícito sigue finalizando la asignación; un cambio de ubicación sin asignación no crea una nueva.
- Actualizado el contrato T15. No se interpretó esta respuesta como aprobación de permisos nuevos ni de todas las transiciones propuestas. D09 permanece parcialmente definida.
- Paso documental: sin cambios de código, datos ni permisos. Pruebas a cargo del usuario y grafo diferido al cierre.
- Siguiente: concretar el permiso y las transiciones restantes antes de implementar el cambio directo; pedir confirmación para continuar.

### 2026-10-29: aprobación completa D09 y cierre documental

- El usuario aprueba la definición propuesta: conservar obra en camino/reparación, regreso a la misma obra con asignación activa, camino/reparación/patio sin obra y retiro explícito para cerrar una asignación. Cambio de obra mediante retiro y nueva asignación.
- Estado operativo independiente de ubicación. Motivo obligatorio y registro de autor, fecha y ubicaciones anterior/nueva.
- Aprobado permiso específico `maquinas.ubicacion.update.access`, compartido por web/app y otorgado manualmente. No se creó ni asignó en este paso.
- D09 resuelta como decisión. Contrato actualizado con matriz de transiciones y condiciones; formato y UTF-8 revisados. Sin código, datos, pruebas funcionales ni actualización del grafo.
- Siguiente paso: implementar backend compartido/API y corregir H26; después formularios móviles. Pedir confirmación antes del backend y reportar al cerrar cada paso.

### 2026-10-29: entrega backend D09

- Implementado servicio compartido de ubicación con permiso efectivo, motivo obligatorio, destinos según asignación y ubicación actual, estado operativo independiente e historial ligado a la misma obra. No permite enviar al patio una máquina asignada sin el retiro explícito.
- PATCH móvil y GET de opciones/POST web implementados, corrigiendo H26. El catálogo muestra ubicación guardada y obra vinculada (H25); no se agrega un formulario web nuevo en este paso.
- Contexto de ubicación separado del contexto previo; incorpora último movimiento para distinguir cambios repetidos. Transacción, bloqueo máquina/asignación y huella por actor/origen/payload para recuperar reintentos exactos.
- Sin migración nueva ni datos/permisos modificados. Registro y otorgamiento manual de `maquinas.ubicacion.update.access` pendientes de administración. Requiere migración previa T15.4 de huella en movimientos al desplegar.
- Sintaxis PHP, rutas, vista, formato y UTF-8 revisados. Sin pruebas funcionales ni actualización del grafo por instrucción del usuario; no se marca paridad probada.
- [Detalle técnico D09](IMPLEMENTACION_UBICACION_MAQUINAS_D09_BACKEND.md). Siguiente: contrato/formulario móvil de cambio directo; informar y pedir confirmación antes de comenzar.

### 2026-10-29: entrega formulario móvil D09

- Acción Cambiar ubicación integrada en listado/detalle, con permiso de sesión y consulta fresca de destinos/contexto/capacidad al abrir. Máquina y obra como referencia; destino sin selección automática, motivo obligatorio y notas opcionales.
- Confirmación del cambio y conservación de obra/estado operativo. Contexto separado del contrato de horas/asignación; tratamiento de nulo/cero y lectura decimal sin normalizar en cliente.
- Errores por campo, recarga/revisión tras 409, bloqueo de doble envío y recuperación exacta del payload incierto por usuario/máquina. Botón de recuperación en tarjeta y coordinación con capturas pendientes de horas/asignación.
- TypeScript y compilación Angular de desarrollo completos; UTF-8/formato revisados. Sin pruebas funcionales, escrituras operativas, cambios de permisos ni actualización del grafo. Temporales retirados.
- [Detalle móvil D09](IMPLEMENTACION_UBICACION_MOVIL_D09.md). T15.6/recorrido conectado quedan a cargo del usuario; permiso nuevo requiere registro/asignación manual y actualización de sesión. Informar entrega y pedir confirmación antes del siguiente paso; grafo sigue para el cierre.

### 2026-10-29: revisión AMK1 solicitada antes del cierre

- Inspección de solo lectura: AMK1 fuera de servicio/en obra por un cambio de estado histórico (movimiento 48), anterior a T15/D09. Autor registrado; origen web/app no conservado. No atribuir el movimiento al residente sin evidencia histórica.
- Asignación 4 sigue abierta a obra 4, ya terminada. El listado y el detalle no aplican la misma definición de asignación vigente. Sin corrección automática de datos.
- Confirmado que la pantalla residente usa actualizar-estado para falla/inicio/fin de reparación. Esa ruta no replica la autorización por obra de reportar-falla ni valida vigencia de asignación; middleware legacy no resuelve ese alcance por registro.
- Registrar hallazgos de auditoría, concurrencia, errores web y reglas de horas en [revisión AMK1](REVISION_ESTADO_UBICACION_MAQUINAS_AMK1.md). Cambios de código/datos pendientes de autorización y definición; sin pruebas funcionales.
- El cierre documental y el grafo quedan pospuestos por esta prioridad del usuario. No marcar T15.6/T14 validados.

### 2026-10-29: revisión de liberación automática al cerrar obra

- Confirmado bloque existente en ObraController::update: al pasar de estatus no cerrado a terminada/cancelada libera asignaciones y envía máquinas a patio mediante MaquinaService. Conserva estado operativo; suspensión no libera.
- El bloque se incorporó en julio y desde 2026-09-25 usa el servicio en vez de una actualización masiva de la asignación. Obra de AMK1 sin actualización desde junio: apunta a pendiente anterior a esa automatización, sin evidencia de fecha exacta de cierre.
- Consulta local de solo lectura: tres asignaciones abiertas a obras terminadas (AMK1, BG-12 y S3). Sin corrección de datos en esta revisión.
- Hallazgos: no hay transacción global de cierre; un fallo puede dejar obra cerrada y liberación parcial. Guardar otra vez la misma obra cerrada no reejecuta el proceso. Registrar evidencia y recomendaciones en [revisión AMK1](REVISION_ESTADO_UBICACION_MAQUINAS_AMK1.md).
- Pendiente definir/autorizar refuerzo del cierre y regularización histórica; cierre documental/grafo continúan pospuestos. Sin pruebas funcionales ni escrituras operativas.

### 2026-10-29: ajuste de retiro pendiente desde ubicación

- Aprobado para AMK1: única acción de ubicación Pasar al patio; cierre explícito de la asignación pendiente, conservación de fuera de servicio, sin nueva obra.
- Implementado selector compartido de una única asignación abierta para retiro, aunque la obra esté terminada/cancelada. Opciones/detalle exponen asignacion_retiro y contexto_retiro separado de horas. Web/API conservan validación de fecha y movimiento transaccional.
- Etiqueta de ubicación interactiva en listado/detalle y menú de acciones con capacidades frescas, explicación de permisos y apertura del formulario existente. Sin retiro automático ni modificación del registro de AMK1.
- El usuario configurará manualmente baja_maquinas.access. No se registraron ni concedieron permisos; requiere actualizar sesión móvil después del cambio manual.
- [Detalle del ajuste](AJUSTE_RETIRO_ASIGNACIONES_PENDIENTES_MOVIL.md). Sintaxis, TypeScript, compilación Angular, formato y UTF-8 comprobados; sin pruebas funcionales ni actualización del grafo. Cierre global continúa pospuesto durante la revisión de reglas/hallazgos.

### 2026-10-29: tarjetas de máquinas plegables

- Cabecera azul de cada tarjeta convertida en botón con flecha para mostrar/ocultar datos de forma independiente, en listado y detalle. Inician abiertas y conservan el contenido al plegarse.
- Badge de estado permanece visible. Acceso por teclado, aria-expanded y vínculo al contenido; flecha independiente del desplegable de preventivos.
- Compilación Angular y UTF-8 verificados; sin pruebas funcionales ni cambios de backend/datos. Actualización final del grafo sigue pendiente del cierre de los ajustes.

### Plantilla para nuevas entradas

- Fecha:
- Tarea / hallazgo:
- Archivos o contratos afectados:
- Decisión o cambio:
- Validación ejecutada y resultado:
- Checkpoint: pendiente / en curso / validado / bloqueado por decisión concreta.
- Pendiente siguiente:

### Registro de decisiones

Al resolver D01-D08, agregar aquí la fecha, la decisión del usuario y las tareas afectadas. No convertir recomendaciones en decisiones aprobadas sin confirmación.

### 2026-10-23: reglas iniciales aceptadas y consulta de productos

- Acceso mediante `app.mecanico.access`, sin obra asignada. Creación/asignación efectiva del permiso pendiente.
- Consultar todas las máquinas y vehículos.
- D01: ver todos los servicios programados y modificar únicamente los asignados al propio mecánico. Tomar un servicio sin asignar sigue pendiente de definición.
- D02: los servicios creados en la app se asignan automáticamente al mecánico que los crea. Resolver por `usuarios_app.empleado_id`; no confiar en un identificador de mecánico enviado por el cliente.
- D03: consultar costos sin modificar precios desde la app. El detalle de la captura de mano de obra sigue pendiente.
- D04: iniciar y completar sus servicios. Cancelación disponible solo desde la web durante el primer alcance; retirar la acción móvil de la entrega inicial aunque permanezca en el inventario de contratos posibles.
- Ajustar materiales antes de solicitar abastecimiento y poder solicitar almacén o compra.
- El usuario agrega acceso de consulta a productos del almacén. El endpoint existente de stock es la referencia; la ruta mecánica y su respuesta se definirán en T05.
- D05, D06, D07 y D08 siguen abiertos. T01 permanece parcialmente completada, hasta resolver las reglas necesarias.

### 2026-10-23: T02, selección de panel y compatibilidad

- Al autorizar T02 se adopta la propuesta de un panel directo si es el único y selector cuando hay varios: `defaults.panel = null`.
- D08 queda definida para la primera versión; no se persiste una preferencia nueva en servidor.
- D07 sigue sin resolver: T03 no quitará permisos ni roles existentes. El administrador podrá retirar `app.regular.access` manualmente si el usuario debe tener acceso exclusivamente mecánico.
- Mecánico sin obra tendrá sesión válida con contexto residente nulo; esto no habilita funciones de residente ni gerenciales.
- Se conserva la diferencia actual entre `paneles_disponibles` en login/`me` y `paneles` en opciones.

### 2026-10-23: T05, diagnóstico D06 y alcance de productos

- D06 tiene respuesta técnica: las plantillas y el servicio de abastecimiento actuales son de maquinaria. T05 no extiende ese comportamiento a vehículos; publica `plantilla_materiales_vehiculo: false` y materiales vacíos de forma explícita. Decidir/configurar ese flujo queda pendiente para la entrega operativa de vehículos.
- La selección de productos replica el catálogo activo global de la web; el almacén limita las cantidades consultadas. No confundir ausencia de stock en Giralda con producto inexistente o inactivo.
- No se crearon registros de configuración automáticamente. El inventario mecánico es de solo lectura y no requiere otorgar permiso gerencial.

### 2026-10-26: D05 confirmada por el usuario

- Permitir iniciar con materiales pendientes; completar solo con todo surtido. Sin materiales incluidos no hay bloqueo por abastecimiento.
- T12 comparte esta regla con la web. C13.5 integrará las acciones móviles después del cierre y pausa de T12. Cancelación sigue solo web (D04).
