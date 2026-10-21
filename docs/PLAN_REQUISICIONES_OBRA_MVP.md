# Plan MVP - Requisiciones de obra

## Objetivo

Crear un nuevo tab de **Requisiciones** dentro de la obra para que el residente pueda solicitar recursos necesarios para preparar o ejecutar una obra.

El MVP debe permitir registrar, autorizar, imprimir y dar seguimiento a requisiciones separadas por tipo, sin mover inventario, asignar maquinaria o asignar empleados de forma automatica en la primera etapa. El flujo debe vivir en controlador propio y quedar separado de planeacion.

## Contexto

Una vez registrada una obra en el sistema, el residente puede necesitar levantar diferentes requisiciones:

- Requisicion de maquinaria.
- Requisicion de personal.
- Requisicion de herramientas.
- Requisicion de materiales o insumos.
- Requisicion de otros conceptos.

Cada requisicion debe tener su propio formato, folio, detalle y firmas, aunque solo tenga una linea.

El area correspondiente revisa, autoriza y surte lo solicitado. En fases posteriores, el surtido podra conectarse con salidas de almacen, asignacion de maquinaria, asignacion de empleados o control de herramientas.

## Hallazgos iniciales

- El modulo de obras ya soporta tabs y partials separados.
- Giralda ya concentra informacion relacionada con empleados.
- Huentitan ya concentra inventario, entradas, salidas y almacen.
- Ya existe un modelo/modulo de herramientas (`Herramienta`), aunque hay que validar si cubre prestamo, existencia, salida y devolucion.
- Maquinaria ya tiene modulo propio y relacion con obras.
- El sistema ya maneja patrones de firmas en documentos, por lo que el formato de requisicion deberia reutilizar esa logica.

## Decision de diseno

La requisicion sera primero un documento de solicitud/autorizacion.

La implementacion debe vivir en controlador propio, por ejemplo ObraRequisicionController, con rutas anidadas a obra. ObraController::edit solo debe cargar el resumen/listado necesario para pintar el tab.

Este flujo queda separado de planeacion, solicitudes de gasto y reposicion de gastos. No debe requerir partida, semana, presupuesto ni planeacion_gasto_id.

En el MVP, crear o autorizar una requisicion no debe:

- Descontar inventario.
- Crear salidas de almacen.
- Asignar maquinaria a la obra.
- Dar de alta empleados en la obra.
- Mover herramientas.

El surtido real quedara como accion posterior, inicialmente manual o de seguimiento. Las integraciones automaticas se dejan para una fase siguiente.

## Alcance MVP

### Incluido

- Nuevo tab `Requisiciones` dentro de la vista de obra.
- Listado de requisiciones de la obra.
- Creacion de requisicion desde una obra con `obra_id` fijo.
- Tipos iniciales de requisicion:
  - Maquinaria.
  - Personal.
  - Herramienta.
  - Material/insumo.
  - Otro.
- Captura de encabezado:
  - Obra.
  - Tipo de requisicion.
  - Folio.
  - Fecha requerida.
  - Prioridad.
  - Solicitante.
  - Observaciones.
  - Estado.
- Captura de detalle:
  - Tipo de item.
  - Item relacionado, cuando exista.
  - Descripcion snapshot.
  - Unidad.
  - Cantidad solicitada.
  - Cantidad autorizada.
  - Cantidad surtida.
  - Observaciones.
- Estados basicos:
  - Pendiente de revision.
  - Autorizada.
  - Rechazada.
  - En surtido.
  - Surtida parcial.
  - Surtida.
  - Recibida/Cerrada.
  - Cancelada.
- Impresion de formato.
- Firmas impresas:
  - Solicita.
  - Autoriza.
  - Surte.
  - Recibe.
- Permisos basicos por accion.

### No incluido en MVP

- Descuento automatico de inventario.
- Generacion automatica de salida de almacen.
- Asignacion automatica de maquinaria.
- Alta automatica de empleados en obra.
- Prestamo/devolucion automatica de herramientas.
- Notificaciones.
- Flujo avanzado de compras.
- Integracion directa con Huentitan en el MVP.
- Validacion estricta de stock disponible.

## Modelo de datos propuesto

### Decision de arquitectura de datos

Para que el modulo pueda crecer sin mezclar responsabilidades, se definieron 4 piezas logicas:

1. `empresa_requisicion_tipos`: catalogo/configuracion de tipos de requisicion. Ya existe y alimenta el select de captura.
2. `obra_requisicion_folios`: control de consecutivos por obra, anio y tipo/catalogo.
3. `obra_requisiciones`: cabecera del documento de requisicion.
4. `obra_requisicion_detalles`: partidas o renglones solicitados.

No son 4 migraciones nuevas en este punto: la migracion de `empresa_requisicion_tipos` ya fue creada antes. Para completar la base del MVP se agregan 3 migraciones nuevas: folios, cabecera y detalles.

Motivos de la decision:

- Evita que el catalogo de tipos cargue tambien la responsabilidad de consecutivos.
- Permite folios independientes por obra, anio y tipo sin choques de concurrencia.
- Permite que el folio visible `REQ-YY-CAT-CONSECUTIVO` se repita en otra obra si es necesario, porque el ambito real es la obra.
- Mantiene la cabecera limpia como documento operativo.
- Permite multiples partidas por requisicion.
- Permite partidas manuales o ligadas a catalogos existentes.
- Deja preparada la integracion futura con Huentitan, maquinaria, empleados o herramientas sin activar automatizacion en el MVP.
- Facilita futuras pantallas de Empresa Config para tipos y folios sin tocar documentos historicos.

Regla de unicidad inicial:

- `obra_requisicion_folios`: unico por `obra_id + empresa_requisicion_tipo_id + anio`.
- `obra_requisiciones`: folio unico dentro de la obra, no global.

Esta decision se debe mantener salvo que mas adelante se decida que el folio visible debe ser global en toda la empresa.

### Tabla `obra_requisiciones`

Campos sugeridos:

- `id`
- `obra_id`
- `tipo_id`
- `folio`
- `estado`
- `prioridad`
- `fecha_solicitud`
- `fecha_requerida`
- `solicitado_por`
- `autorizado_por`
- `surtido_por`
- `recibido_por`
- `observaciones`
- `motivo_rechazo`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`

### Tabla `obra_requisicion_detalles`

Campos sugeridos:

- `id`
- `obra_requisicion_id`
- `item_type`
- `item_id`
- `descripcion_snapshot`
- `unidad`
- `cantidad_solicitada`
- `cantidad_autorizada`
- `cantidad_surtida`
- `estado`
- `observaciones`
- `created_at`
- `updated_at`

### Preparacion para integraciones futuras

Aunque Huentitan queda fuera del MVP, el modelo debe considerar referencias opcionales para enlazar surtidos futuros sin migraciones invasivas. Opciones a evaluar al implementar:

- Campo en encabezado para documento/salida generada posteriormente.
- Campo en detalle para origen de surtido por partida.
- Tabla pivote de surtidos si una requisicion puede surtirse con varios documentos.

La decision final debe priorizar no acoplar el MVP a Huentitan, pero si evitar cerrar la puerta a esa integracion.


### Decision de origen de catalogo

Aunque el MVP empieza apuntando operativamente a Giralda, el modulo no debe quemar Giralda en codigo ni en rutas especificas.

La resolucion del catalogo debe depender de `empresa_requisicion_tipos`:

- `origen_catalogo`: indica de donde se obtiene el catalogo operativo (`giralda`, `huentitan`, `maquinaria`, `manual`, etc.).
- `item_type`: indica que tipo de item se va a seleccionar o capturar (`empleado`, `maquina`, `herramienta`, `producto`, `manual`).
- `almacen_id`: opcional, permite amarrar un tipo a un almacen especifico cuando aplique.
- `requiere_catalogo`: indica si el tipo debe cargar opciones desde catalogo.
- `permite_manual`: permite capturar partidas libres cuando el catalogo no alcanza.

Defaults actuales del MVP:

- Personal: origen `giralda`, item `empleado`.
- Maquinaria: origen `maquinaria`, item `maquina`.
- Herramienta: origen `giralda`, item `herramienta`, almacen Giralda si existe.
- Material: origen `giralda`, item `producto`, almacen Giralda si existe.
- Otro: origen `manual`, item `manual`.

Cuando se habiliten requisiciones de Huentitan, se podran crear tipos nuevos o reconfigurar tipos existentes con `origen_catalogo = huentitan` y el `almacen_id` correspondiente, sin cambiar la estructura de requisiciones.
### Catalogo de tipos

Puede iniciar como seeder/configuracion simple y despues moverse a Empresa Config.

Campos sugeridos:

- `id`
- `codigo`
- `nombre`
- `item_source`
- `activo`
- `orden`

Ejemplos:

- `maquinaria`
- `personal`
- `herramienta`
- `material`
- `otro`

## Folios

El control de folios debe contemplar:

- Configuracion desde Empresa Config.
- Creacion y manipulacion de tipos/catalogos de requisicion desde Empresa Config.
- Prefijo general por documento, inicialmente `REQ`.
- Consecutivo editable con permisos.
- Folio por anio, obra y tipo/catalogo de requisicion, por ejemplo `REQ-26-01-0001`.
- El consecutivo queda amarrado a obra para evitar choques operativos y mantener cada requisicion en su propio ambito.
- Vista previa del siguiente folio antes de guardar, calculada en contexto de obra, anio y catalogo/tipo.
- Bloqueo para evitar duplicados al crear requisiciones simultaneas dentro de la misma obra/anio/catalogo.
- Auditoria cuando alguien modifique serie, prefijo o consecutivo.
- Regla clara para folios cancelados: no reutilizarlos salvo decision explicita.

En el MVP se recomienda usar folio por obra, anio y tipo/catalogo de requisicion, configurable desde Empresa Config, y no permitir que el residente edite manualmente el folio.



### Decision actual de folios

- Se usara formato `REQ-YY-CAT-CONSECUTIVO`.
- Ejemplo: `REQ-26-01-0001`.
- `YY` corresponde al anio corto.
- `CAT` corresponde al numero de catalogo/tipo de requisicion configurado en Empresa Config.
- `CONSECUTIVO` se calcula por obra, anio y catalogo/tipo.
- El residente no captura ni edita folio.
- No se contempla estado borrador para el MVP; al guardar nace como pendiente de revision/autorizacion.
- El folio se genera dentro de transaccion con bloqueo del registro de folio correspondiente.
### Propuesta de formato de folio para requisiciones

Idea inicial para trabajar:

- Formato base: `REQ-{yy}-{catalogo}-{consecutivo}`.
- Ejemplo: `REQ-26-01-0001`.
- `REQ` es el prefijo general del documento requisicion.
- El primer set numerico identifica el anio corto.
- El segundo set numerico identifica el catalogo/tipo de requisicion. El tercer set numerico es el consecutivo dentro de ese anio, obra y catalogo.

Ejemplo inicial:

- `REQ-26-01-0001`: requisicion de empleados/personal para anio 2026.
- `REQ-26-02-0001`: requisicion de maquinaria para anio 2026.
- `REQ-26-03-0001`: requisicion de herramientas para anio 2026.
- `REQ-26-04-0001`: requisicion de materiales/insumos para anio 2026.
- `REQ-26-05-0001`: requisicion de otros conceptos para anio 2026.

Esta propuesta implica que cada obra, anio y tipo de requisicion tiene su propio consecutivo. Por ejemplo, en una misma obra empleados puede ir en `REQ-26-01-0045` mientras maquinaria va en `REQ-26-02-0012`. Otra obra puede tener sus propios consecutivos sin chocar.

Puntos por decidir antes de implementar:

- Si el numero de catalogo debe ser editable desde Empresa Config o quedar fijo una vez creado.
- El consecutivo se reinicia por anio, obra y catalogo/tipo.
- El folio incluye anio corto como segundo bloque visual: `REQ-26-01-0001`.
- Si se permite cambiar el prefijo `REQ` desde Empresa Config.
- El folio se genera al crear/enviar la requisicion; no se contempla estado borrador en el MVP.
- Si una requisicion cancelada conserva folio y no se reutiliza.

Recomendacion preliminar:

- Administrar desde Empresa Config los tipos de requisicion, su numero de catalogo, prefijo y reglas de consecutivo.
- Mantener `REQ-{yy}-{catalogo}-{consecutivo}` para MVP por ser claro, con anio y tipo visibles.
- No permitir editar folio desde captura de obra.
- Generar folio al crear/enviar, siempre dentro de transaccion con bloqueo por obra, anio y catalogo/tipo.
## Permisos propuestos

- `obras.requisiciones.view`
- `obras.requisiciones.create`
- `obras.requisiciones.edit`
- `obras.requisiciones.submit`
- `obras.requisiciones.authorize`
- `obras.requisiciones.reject`
- `obras.requisiciones.supply`
- `obras.requisiciones.receive`
- `obras.requisiciones.cancel`
- `obras.requisiciones.print`
- `empresa.requisiciones-tipos.manage`
- `empresa.requisiciones-folios.manage`

## Flujo MVP

1. El residente entra a la obra.
2. Abre el tab `Requisiciones`.
3. Crea una requisicion.
4. Selecciona tipo.
5. Captura una o varias lineas.
6. Guarda la requisicion y queda pendiente de revision/autorizacion.
7. Un usuario autorizado revisa.
8. Autoriza o rechaza.
9. Almacen/responsable marca surtido parcial o surtido.
10. Residente marca recibido/cerrado.
11. Se puede imprimir el formato con firmas.

## Flujo operativo definido

La requisicion inicia como un documento de solicitud. El residente puede crearla desde el contexto fijo de una obra, pero no debe generar movimientos de inventario ni asignaciones automaticas.

### Estados

1. `pendiente_revision`
   - La requisicion fue creada y tiene folio.
   - Es la representacion operativa de una requisicion creada.
   - Todavia no puede ser surtida por almacen.

2. `autorizada`
   - Un usuario con permiso de autorizacion reviso y aprobo la requisicion.
   - Ya puede pasar al proceso de surtido.

3. `en_surtido`
   - Almacen o el responsable de surtido comenzo a atenderla.

4. `surtida_parcial`
   - Una o mas partidas fueron atendidas, pero existe saldo pendiente.

5. `surtida`
   - Todas las partidas fueron atendidas completamente.

6. `rechazada`
   - La requisicion fue revisada y no autorizada.
   - Debe conservar el motivo del rechazo.

7. `cancelada`
   - La requisicion se cancelo y deja de estar disponible para surtido.
   - El folio no se reutiliza.

`creada` no se manejara como estado separado en el MVP. Al guardar, la requisicion nace directamente como `pendiente_revision`.

### Transiciones permitidas

```text
pendiente_revision -> autorizada
pendiente_revision -> rechazada
pendiente_revision -> cancelada
autorizada -> en_surtido
autorizada -> cancelada
en_surtido -> surtida_parcial
en_surtido -> surtida
surtida_parcial -> surtida
```

Regla central: una requisicion solo puede entrar a surtido cuando esta `autorizada`. Autorizar no descuenta inventario; el movimiento real ocurrira durante el surtido.

### Datos de autorizacion y trazabilidad

La autorizacion debe guardar:

- Usuario que autoriza.
- Fecha y hora de autorizacion.
- Comentarios del autorizador.
- Motivo cuando sea rechazada.
- Firma correspondiente cuando el formato lo requiera.

Tambien se recomienda conservar una bitacora de transiciones para conocer quien cambio el estado, desde cual estado y hacia cual estado.

### Permisos del flujo

Permisos funcionales confirmados para la siguiente etapa:

- `obras.requisiciones.view`
- `obras.requisiciones.create`
- `obras.requisiciones.edit`
- `obras.requisiciones.authorize`
- `obras.requisiciones.reject`
- `obras.requisiciones.supply`
- `obras.requisiciones.receive`
- `obras.requisiciones.cancel`
- `obras.requisiciones.print`

El permiso `obras.requisiciones.authorize` debe limitar la transicion a `autorizada`. El permiso de surtido debe pertenecer al personal de almacen o al rol operativo definido posteriormente.

## Replica en la app movil

La app movil debe permitir al residente crear requisiciones dentro de la obra a la que tiene acceso. No debe permitir cambiar libremente la obra ni consultar catalogos fuera de su contexto.

### Alcance movil

- Listar las obras accesibles al residente.
- Seleccionar una obra activa como contexto.
- Consultar tipos de requisicion activos.
- Cargar el catalogo correspondiente.
- Seleccionar una o varias partidas.
- Permitir varias lineas del mismo material con destinos diferentes.
- Capturar cantidad, equipo destino y observaciones.
- Crear la requisicion con estado `pendiente_revision`.
- Consultar folio, estado, partidas y observaciones.
- Mostrar rechazo, autorizacion y avance de surtido.

### Reglas de seguridad movil

- El backend debe validar nuevamente la obra y el acceso del residente.
- La app no debe generar folios localmente.
- La app no debe enviar ni modificar el estado final.
- La app no debe descontar inventario.
- Los catalogos deben resolverse en el servidor según el tipo y almacen configurados.
- La misma validacion de equipo destino usada en web debe aplicarse en la API.

### Contrato API futuro

El contrato movil debe reutilizar la misma estructura de cabecera y lineas que web:

```json
{
  "obra_id": 18,
  "tipo_requisicion_id": 4,
  "fecha_requerida": "2026-10-20",
  "observaciones": "Material requerido para iniciar trabajos",
  "partidas": [
    {
      "item_id": 19,
      "cantidad_solicitada": 100,
      "equipo_tipo": "maquina",
      "maquina_id": 1
    }
  ]
}
```

La API debe devolver el folio generado, el estado inicial y el detalle normalizado con snapshots.

## Checkpoints del flujo de autorizacion

- [ ] Confirmar los estados definitivos en base de datos.
- [ ] Confirmar roles responsables de revisar y autorizar.
- [ ] Crear o reutilizar campos de autorizacion, rechazo y cancelacion.
- [ ] Agregar acciones protegidas por permisos.
- [ ] Crear bandeja de autorizacion agrupada por obra.
- [ ] Bloquear surtido cuando la requisicion no este autorizada.
- [ ] Definir surtido parcial y calculo de saldos.
- [ ] Registrar bitacora de transiciones.
- [ ] Definir endpoints y permisos de la app movil.
- [ ] Replicar la validacion de obra, catalogo y equipos en la API.


### Pantalla de autorizacion

- Debe existir una vista/bandeja de autorizacion para revisar requisiciones pendientes.
- La bandeja debe mostrar la informacion agrupada por obra.
- Dentro de cada obra se listan sus requisiciones pendientes, autorizadas recientes o en surtido, segun filtros.
- Esta vista permite que el autorizador entienda el contexto completo de una obra antes de aprobar recursos.
- Filtros sugeridos: obra, tipo/catalogo, estado, fecha requerida y solicitante.
## Reglas iniciales

- Una requisicion siempre pertenece a una obra.
- Desde el tab de obra no se debe permitir cambiar `obra_id`.
- Cada requisicion tiene un solo tipo principal.
- El formato impreso debe respetar el tipo de requisicion.
- Los items relacionados deben guardar snapshot descriptivo para que el documento no cambie si despues se edita el catalogo.
- El MVP puede permitir lineas manuales para no bloquear la operacion si falta catalogo.
- El estado `Autorizada` no debe surtir automaticamente.
- El estado `Surtida` no debe descontar inventario en MVP.

## Integraciones futuras

- Materiales/insumos: conectar con salidas de Huentitan cuando se habilite fase de surtido real.
- Herramientas: conectar con prestamo, salida y devolucion.
- Maquinaria: conectar con asignacion de maquina a obra.
- Personal: conectar con alta/asignacion de empleado a obra.
- Compras: generar solicitud de compra si almacen no tiene existencia.
- Notificaciones: avisar a almacen, autorizadores y residente.
- Dashboard: requisiciones pendientes por surtir, vencidas o parcialmente surtidas.

## Checkpoints de implementacion

### Checkpoint 1 - Analisis tecnico

- Revisar rutas, controladores y permisos existentes de obras.
- Revisar patron de tabs/partials en `obras/edit`.
- Revisar patron de formatos impresos con firmas.
- Revisar catalogos disponibles: inventario, maquinas, empleados y herramientas.

### Checkpoint 2 - Documento y decisiones

- Confirmar flujo de estados.
- Confirmar quien autoriza.
- Confirmar si los residentes pueden solicitar por item exacto o por descripcion libre.
- Confirmar si herramientas se manejan como inventario, prestamo o catalogo independiente.

### Checkpoint 3 - Base de datos

- Crear migraciones para requisiciones.
- Crear migraciones para detalles.
- Crear catalogo inicial de tipos.
- Crear modelos y relaciones.

### Checkpoint 4 - Rutas y permisos

- Crear rutas para listar, crear, guardar, ver, imprimir y cambiar estado.
- Crear controlador propio ObraRequisicionController.
- Crear permisos base.
- Agregar permisos a roles necesarios.

### Checkpoint 5 - Tab en obra

- Agregar tab `Requisiciones`.
- Crear partial separado.
- Mostrar listado filtrado por `obra_id`.
- Agregar boton para nueva requisicion con obra fija.

### Checkpoint 6 - Captura

- Crear pantalla/formulario de requisicion.
- Fijar `obra_id`.
- Permitir seleccionar tipo.
- Permitir agregar detalles.
- Crear requisicion en estado pendiente de revision.
- La requisicion queda automaticamente en revision al crear; no hay paso de borrador en el MVP.

### Checkpoint 7 - Autorizacion

- Crear acciones de autorizar y rechazar.
- Registrar usuario y fecha.
- Bloquear edicion cuando ya este pendiente de revision/autorizada, salvo permisos especiales.

### Checkpoint 8 - Surtido y recepcion

- Permitir marcar surtido parcial.
- Permitir marcar surtido.
- Permitir marcar recibido/cerrado.
- Registrar usuario y fecha.

### Checkpoint 9 - Impresion y firmas

- Crear formato imprimible.
- Agregar bloque de firmas.
- Validar que cada tipo se distinga visualmente.

### Checkpoint 10 - Pruebas

- Probar creacion desde obra.
- Probar que no se pueda cambiar obra desde el flujo de obra.
- Probar estados.
- Probar permisos.
- Probar impresion.
- Probar que no se descuente inventario ni se asignen recursos automaticamente.

## Preguntas abiertas

- Quien debe autorizar cada tipo de requisicion?
- La autorizacion cambia segun tipo, monto, cantidad o prioridad?
- El residente solicita empleados por nombre exacto, por puesto/rol o por cantidad?
- La maquinaria se solicita por maquina exacta o por tipo/capacidad?
- Las herramientas se manejan como catalogo simple, inventario o prestamo con devolucion?
- Almacen puede surtir parcialmente?
- Una requisicion puede mezclarse con varios tipos de item o siempre debe ser de un solo tipo?
- La requisicion debe poder generarse antes de que la obra este en ejecucion?
- El formato final queda propuesto como `REQ-{yy}-{catalogo}-{consecutivo}`, con consecutivo por obra/anio/catalogo.
- Que usuarios pueden manipular consecutivos, prefijos o series desde Empresa Config?
- El folio se genera al crear/enviar, sin estado borrador en MVP?

## Recomendacion inicial

Iniciar con un MVP documental y de seguimiento:

1. Captura.
2. Autorizacion.
3. Impresion con firmas.
4. Surtido manual.
5. Recepcion/cierre.

Despues de estabilizar ese flujo, conectar cada tipo con su modulo operativo correspondiente.
## Barrido tecnico inicial

### Obras y tabs

- La vista principal de obra se concentra en `ObraController::edit` y `resources/views/obras/edit.blade.php`.
- El controlador ya carga informacion condicional segun `tab`, por ejemplo caja chica, maquinaria, horas maquina, asistencias y solicitudes.
- El tab nuevo `Requisiciones` puede seguir el patron de tabs existentes: agregar llave al arreglo de tabs, crear partial separado y cargar datos solo cuando `tab === 'requisiciones'`.
- `ObraController::edit` ya esta muy cargado, por lo que conviene evitar meter toda la logica ahi. Mejor cargar solo resumen/listado minimo y mover acciones CRUD a un controlador propio.

### Solicitudes de gasto como referencia

- Ya existe `ObraSolicitudGastoController` con flujo simple: crear, guardar, ver, autorizar y rechazar.
- Usa rutas anidadas bajo obra: `obras/{obra}/solicitudes-gastos/...`.
- Usa modelos `ObraSolicitudGasto` y `ObraSolicitudGastoDetalle`.
- El flujo actual esta ligado a planeacion semanal y partidas, por lo que no debe reutilizarse directamente para requisiciones.
- Si sirve como referencia para estructura de rutas, relacion con obra, estados simples y vistas `resources/views/obras/solicitudes/*`.

### Folios y Empresa Config

- Ya existe tabla/modelo `ObraFolio` con `tipo_obra`, `prefijo`, `anio` y `ultimo_consecutivo`.
- Empresa Config ya tiene tab de `folios`, vista para consultar por anio y editar el ultimo consecutivo usado.
- `ObraController` ya tiene helpers de folio: preview, reserva, base y formateo.
- El esquema actual de folios esta pensado para tipos de obra, no para documentos operativos.
- Para requisiciones conviene crear una configuracion nueva de folios de documentos, no mezclarla con `obra_folios` salvo que se quiera generalizar despues.
- Recomendacion tecnica: crear tabla nueva para folios de requisicion o folios documentales, por ejemplo `empresa_documento_folios`, con documento, tipo/ambito, prefijo, anio, ultimo_consecutivo y activo.
- El folio debe reservarse dentro de transaccion y con bloqueo para evitar duplicados.

### Firmas impresas

- Ya existen `DocumentoFirmante` y `DocumentoFirmaDefinicion`.
- Empresa Config ya administra definiciones de firmas imprimibles: documento, ambito/formato, campo, etiqueta, orden y activo.
- Caja chica ya reutiliza helpers para resolver ambito y obtener firmantes por documento/ambito.
- Para requisiciones hay que agregar una constante o codigo de documento nuevo, por ejemplo `requisicion_obra`.
- Ambitos posibles para firmas:
  - `requisicion_maquinaria`
  - `requisicion_personal`
  - `requisicion_herramienta`
  - `requisicion_material`
  - `requisicion_otro`
- Campos sugeridos: `solicita`, `autoriza`, `surte`, `recibe`.
- Se puede reutilizar el patron de `DocumentoFirmaDefinicionSeeder` para sembrar firmas base.

### Inventario y salidas Huentitan

- Huentitan ya tiene flujo de salidas con `HuentitanSalidaController`.
- Las salidas se crean para una obra, tienen folio, detalles, estado `borrador/aplicada/cancelada`, impresion y aplicacion contra stock.
- Al aplicar una salida se valida stock disponible, se actualiza `inventario_stock` y se inserta movimiento en `inventario_movimientos` con `obra_id`.
- El controlador de salidas ya calcula faltantes y marca partidas que requieren compra.
- Para el MVP de requisiciones no conviene aplicar salidas automaticamente.
- En fase futura, una requisicion autorizada de material podria generar una salida Huentitan en borrador o relacionarse con una salida existente.
- Riesgo: `HuentitanSalidaController` usa folio propio `SAL-HUE-YYYYMM-0000` generado internamente, no desde Empresa Config.

### Inventario generico

- Existe `InventarioDocumento` con tipos `inicial`, `entrada`, `salida`, `ajuste`, `resguardo`, `devolucion`.
- Existe `InventarioDocumentoService::aplicar`, que maneja stock, costo promedio y kardex con bloqueo de stock.
- Este flujo es mas generico que Huentitan, pero las salidas Huentitan tienen reglas de negocio especificas para almacen Huentitan y faltantes/compra.
- Para requisiciones, el MVP solo debe consultar o referenciar productos; el movimiento real debe quedar para fase de integracion.

### Herramientas

- Si existe modelo `Herramienta` y tabla `herramientas`.
- Esta ligada a `almacen_id` y opcionalmente `proveedor_id`.
- Campos disponibles: codigo, nombre, descripcion, costo, vida util, costo residual, fecha compra, marca, modelo, numero serie, estado y activo.
- Estados actuales: `activa`, `en_mantenimiento`, `baja`.
- Tiene rutas y vistas bajo Huentitan para CRUD de herramientas.
- No se encontro en el barrido un flujo completo de prestamo, resguardo, salida o devolucion de herramientas.
- Para el MVP se puede usar como catalogo seleccionable; el control operativo de herramienta debe quedar como integracion futura.

### Maquinaria

- Ya existe modelo `Maquina` con estados: `operativa`, `fuera_servicio`, `baja_definitiva`, `en_reparacion`.
- Ya existe relacion `ObraMaquina` y `Maquina::asignacionActiva()`.
- `ObraController::edit` ya carga maquinaria asignada y maquinaria disponible para tabs de maquinaria/horas maquina/comisiones.
- Para requisiciones de maquinaria se puede elegir maquina exacta o capturar descripcion/tipo requerido.
- Recomendacion MVP: permitir ambas opciones: item ligado a maquina si ya se conoce, o linea manual si solo se pide tipo/capacidad.
- La requisicion no debe crear asignacion `ObraMaquina` automaticamente.

### Personal / empleados

- Ya existe modelo `Empleado`, relacion con obra mediante `obra_empleado` y helpers `asignacionActiva()` / `obraActiva()`.
- `Empleado::obraActiva()` ya filtra obras en ejecucion.
- `ObraController::edit` ya arma lista de empleados asignables y detecta si estan asignados en otra obra.
- Para requisiciones de personal se puede solicitar empleado exacto, rol/puesto o cantidad.
- Recomendacion MVP: permitir item ligado a empleado y tambien linea manual con puesto/cantidad para no bloquear solicitudes tempranas.
- La requisicion no debe crear asignacion `ObraEmpleado` automaticamente.

### Permisos existentes y patron

- El proyecto usa permisos granulares con middleware `permission:*`.
- Huentitan tiene permisos por modulo y accion: view, create, edit, delete/apply/cancel.
- Requisiciones deberia seguir el mismo patron con permisos por accion: ver, crear, editar, enviar, autorizar, rechazar, surtir, recibir, cancelar e imprimir.
- Hay que agregar permisos en migracion/seeder y asignarlos a roles operativos.

### Rutas sugeridas

- `GET obras/{obra}/requisiciones`
- `GET obras/{obra}/requisiciones/create`
- `POST obras/{obra}/requisiciones`
- `GET obras/{obra}/requisiciones/{requisicion}`
- `GET obras/{obra}/requisiciones/{requisicion}/edit`
- `PUT/PATCH obras/{obra}/requisiciones/{requisicion}`
- `POST obras/{obra}/requisiciones/{requisicion}/enviar`
- `POST obras/{obra}/requisiciones/{requisicion}/autorizar`
- `POST obras/{obra}/requisiciones/{requisicion}/rechazar`
- `POST obras/{obra}/requisiciones/{requisicion}/surtir`
- `POST obras/{obra}/requisiciones/{requisicion}/recibir`
- `POST obras/{obra}/requisiciones/{requisicion}/cancelar`
- `GET obras/{obra}/requisiciones/{requisicion}/imprimir`

### Piezas nuevas necesarias

- Modelo `ObraRequisicion`.
- Modelo `ObraRequisicionDetalle`.
- Catalogo/configuracion de tipos de requisicion.
- Configuracion de folios de requisicion desde Empresa Config.
- Controlador propio, por ejemplo `ObraRequisicionController`.
- Partial nuevo para tab de obra.
- Vistas create/show/edit/print.
- Seeder/migracion de permisos.
- Seeder/migracion de firmas base.

### Riesgos tecnicos

- `ObraController::edit` esta muy grande; agregar mas logica ahi puede volverlo dificil de mantener.
- Folios actuales de obra no son suficientemente genericos para requisiciones; conviene resolverlo bien desde el inicio.
- Herramientas existen como catalogo, pero no como flujo completo de prestamo/surtido.
- Huentitan queda fuera del MVP operativo; no debe llamarse automaticamente desde requisiciones.
- Aun asi, la base de datos debe quedar preparada para relacionar una requisicion o sus detalles con salidas/movimientos futuros de Huentitan.
- Debe guardarse snapshot descriptivo en detalles para evitar que cambios futuros de catalogo alteren documentos historicos.
- Hay que definir si el folio se reserva en borrador o al enviar. Tecnica y operativamente conviene reservarlo al crear/enviar segun decision de negocio, pero nunca permitir edicion manual por residente.

### Recomendacion tecnica despues del barrido

- Implementar requisiciones como modulo propio anidado a obra, con controlador propio.
- Mantener el MVP documental y de seguimiento, sin movimientos automaticos y sin dependencia de planeacion.
- Crear configuracion de folios reutilizable para documentos desde Empresa Config.
- Reutilizar `DocumentoFirmante` y `DocumentoFirmaDefinicion` para firmas.
- Reutilizar catalogos existentes solo como fuentes de seleccion: productos, empleados, maquinas y herramientas.
- Dejar integraciones operativas para una segunda fase: salidas Huentitan, asignacion de maquinaria, asignacion de empleados y prestamo/devolucion de herramientas.
- Preparar campos de referencia futura, por ejemplo surtido_origen_type, surtido_origen_id, inventario_documento_id o equivalente, pero sin activar automatizacion en MVP.
## Decision actual - varias partidas por tipo de requisicion

Como el modulo va a crecer con varias partidas/tipos de requisicion, la estrategia para el MVP es mantener un flujo generico y configurable, no crear un controlador por cada partida.

### Decision sobre controladores

Se mantiene un solo controlador principal: `ObraRequisicionController`.

Motivos:

- Las rutas base son las mismas para todos los tipos: listar, crear, guardar, consultar, editar, agregar detalle, eliminar detalle e imprimir.
- La cabecera, folio, estado, observaciones, firmas y vista imprimible son comunes.
- Evita duplicar logica de seguridad, validacion de obra, generacion de folios y carga de firmas.
- Facilita que nuevas partidas entren por configuracion de `empresa_requisicion_tipos` y por resolvers de catalogo.
- Mantiene el MVP simple mientras el flujo operativo real de surtido/autorizacion aun no se separa por modulo.

Solo convendria separar controladores en una fase posterior si un tipo de requisicion desarrolla reglas realmente distintas, por ejemplo:

- Maquinaria genera o modifica asignaciones `ObraMaquina`.
- Materiales generan salidas de almacen o movimientos de inventario.
- Herramientas manejan prestamo/devolucion.
- Personal dispara alta/asignacion de empleados a obra.
- Un tipo requiere autorizaciones, pantallas o estados propios que ya no sean compatibles con el flujo comun.

### Rutas a reutilizar para cada partida

Las requisiciones de personal, maquinaria, herramientas, materiales y otros conceptos deben usar las mismas rutas anidadas a obra:

- `GET obras/{obra}/requisiciones` - index/listado.
- `GET obras/{obra}/requisiciones/create` - crear.
- `GET obras/{obra}/requisiciones/catalogo` - cargar catalogo segun tipo seleccionado.
- `POST obras/{obra}/requisiciones` - guardar cabecera y detalles iniciales.
- `GET obras/{obra}/requisiciones/{requisicion}` - consultar.
- `GET obras/{obra}/requisiciones/{requisicion}/edit` - editar mientras aplique.
- `PATCH obras/{obra}/requisiciones/{requisicion}` - actualizar fecha, observaciones y detalles.
- `POST obras/{obra}/requisiciones/{requisicion}/detalles` - agregar nuevos detalles desde el catalogo del tipo.
- `DELETE obras/{obra}/requisiciones/{requisicion}/detalles/{detalle}` - eliminar detalle; si es el ultimo, eliminar la requisicion completa.
- `GET obras/{obra}/requisiciones/{requisicion}/imprimir` - formato imprimible.

No se crean rutas especificas como `/requisiciones/maquinaria` o `/requisiciones/personal` en el MVP. El tipo se resuelve por `empresa_requisicion_tipos` y por `item_type`.

### Patron para agregar nuevas partidas

Para agregar una nueva partida/tipo al flujo se debe seguir este patron:

1. Crear o activar el tipo en `empresa_requisicion_tipos`.
2. Definir `numero_catalogo` para el folio `REQ-YY-CAT-CONSECUTIVO`.
3. Definir `origen_catalogo` e `item_type`.
4. Crear el resolver de catalogo correspondiente en el controlador o extraerlo despues a un servicio.
5. Normalizar cada item del catalogo al mismo contrato de UI:
   - `id`
   - `item_type`
   - `label`
   - `subtitle`
   - `descripcion_snapshot`
   - `badge`
   - `badge_key`
   - `asignacion` o contexto opcional
6. Ajustar columnas visibles de create/edit segun el tipo, sin cambiar rutas.
7. Validar en backend que los `selected_items` existan en el catalogo resuelto para ese tipo.
8. Guardar detalles con snapshot para conservar el documento historico.
9. Reutilizar la vista imprimible generica.
10. Documentar diferencias operativas antes de conectar automatizaciones reales.

### Segunda partida MVP - Maquinaria

Maquinaria debe entrar como el siguiente tipo usando el mismo flujo que personal.

Configuracion esperada:

- Nombre: `Maquinaria`.
- `numero_catalogo`: `02`.
- `origen_catalogo`: `maquinaria`.
- `item_type`: `maquina`.
- Folio esperado: `REQ-26-02-0001` para anio 2026, con consecutivo por obra/anio/tipo.

Catalogo esperado:

- Debe cargar maquinas existentes del modulo de maquinaria.
- Debe mostrar disponibilidad/contexto de asignacion.
- Debe permitir buscar sin mostrar la lista completa inicialmente.
- Debe agregar con doble click, igual que personal.
- No debe asignar la maquina a la obra automaticamente.

Columnas sugeridas para maquinaria:

- `MAQUINA`
- `NUM. ECO / TIPO`
- `DISPONIBILIDAD`

Badges sugeridos:

- `Disponible` cuando no tenga obra activa.
- Nombre de la obra cuando este asignada a una obra.
- Estado operativo visible si ayuda a la decision, por ejemplo `Fuera de servicio`, `En reparacion` o `Baja`.

Snapshot sugerido para detalle de maquinaria:

- Nombre/equipo.
- Numero economico o identificador visible.
- Marca/modelo si existe.
- Estado operativo.
- Obra asignada al momento de solicitar, si existe.

Regla operativa MVP:

- Crear una requisicion de maquinaria no mueve ni asigna maquinaria.
- El documento solo deja evidencia de solicitud, revision, autorizacion, surtido manual y recepcion/cierre cuando se implementen esos estados.

### Ajustes de UI derivados

Los textos de show/edit deben ser genericos para que no parezcan exclusivos de personal:

- Usar `Agregar partida` o `Agregar recurso` en vez de `Agregar empleado` cuando el tipo no sea personal.
- Usar `Eliminar partida requerida` en vez de `Eliminar empleado requerido`.
- Mantener el alert de ultimo detalle, pero con texto generico: `Si borras la ultima partida se borrara la requisicion completa.`
- La tabla de detalles puede seguir siendo generica; las columnas del buscador/catalogo si deben adaptarse por tipo.

### Estado de implementacion actual

- Personal ya tiene flujo funcional de create, catalogo, guardado, show/edit, agregar detalle, eliminar detalle e impresion.
- La vista imprimible ya es generica y puede servir para maquinaria sin crear otra ruta.
- Las firmas de `obra_requisicion` ya existen en seeder y se cargan por campo: `elaboro`, `autorizo`, `vobo`, `recibe`.
- Maquinaria queda como siguiente expansion usando el mismo patron.

