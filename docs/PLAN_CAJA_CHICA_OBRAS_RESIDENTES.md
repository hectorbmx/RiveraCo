# Plan: Caja chica por obra para residentes

## Objetivo

Replicar el funcionamiento operativo de `reposicion-caja-chica` usado para almacen/Giralda, pero aplicado a obras para que los residentes capturen gastos de caja chica sin obligarlos a ligarlos a planeacion, semana o partida.

El flujo existente de reposicion de gastos por obra debe conservarse intacto, porque pertenece a otro proceso: planeacion, solicitudes, partidas, programacion, aprovisionamiento y autorizacion.

## Decision de diseno

Crear una entrada separada dentro de la vista de obra, en un tab nuevo al final, para el flujo de caja chica por obra.

Tabs existentes a comentar/ordenar en la vista de obra:

- Planeacion
- Solicitudes
- Reposicion gastos

Nuevo tab propuesto:

- Caja chica obra

El contenido del nuevo tab debe ir en un partial nuevo para no seguir saturando `resources/views/obras/edit.blade.php`.

Partial propuesto:

- `resources/views/obras/partials/caja_chica_obra/tab.blade.php`

## Hallazgos actuales

- El modulo `reposicion-caja-chica` ya soporta destino por obra.
- La tabla `reposicion_caja_chica_gastos` ya tiene `destino`, `obra_id` y `almacen_id`.
- El modelo `ReposicionCajaChicaGasto` ya tiene relaciones con `obra`, `almacen`, `categoria`, `subcategoria`, `archivos`, `maquina`, `vehiculo`, `solicitadoPor` y `resueltoPor`.
- `ReposicionCajaChicaController::create()` ya carga obras activas, almacenes, maquinas, vehiculos, categorias y subcategorias.
- `ReposicionCajaChicaController::store()` ya guarda lotes, XML, evidencias, proveedor, forma de pago, importe y estado.
- `ReposicionCajaChicaController::parseXml()` ya procesa XML para carga automatica.
- La vista `reposicion-caja-chica/create.blade.php` ya permite seleccionar destino `obra` o `almacen`.
- El tab actual `resources/views/obras/partials/caja_chica/tab.blade.php` ya lista gastos de `reposicion_caja_chica_gastos` filtrados por `obra_id`.
- `ObraController` ya calcula `$cajaChicaGastos` y `$cajaChicaStats` cuando el tab es `caja-chica`.
- El flujo viejo `ObraReposicionGastoController` esta ligado a `obra_reposicion_gastos`, `partida_id`, `semana` y detalles de planeacion. No conviene mezclarlo con este nuevo flujo.
- Los ambitos de firma actuales para reposicion de caja chica son `reposicion_gastos_almacen` y `giralda`. No se encontro un ambito especifico para obra.
- Problema detectado: el tab de obra no debe mostrar gastos cargados desde almacen/oficina aunque tengan `obra_id`, porque para el residente pueden causar friccion y confusion.
- La separacion no debe depender solo de `obra_id`; hace falta distinguir el origen de captura del gasto.

## Riesgos / puntos a cuidar

- No romper el flujo existente de `obras/{obra}/reposicion-gastos`.
- No mezclar gastos de caja chica por obra con gastos ligados a planeacion.
- Evitar que residentes puedan capturar o ver gastos de obras que no les corresponden.
- Mantener el flujo de almacen/Giralda funcionando igual.
- Definir si la impresion de obra usara firmas propias o reutilizara el formato administrativo actual.
- Definir si residente puede guardar borrador, enviar a revision, o ambas.
- Evitar que el residente vea gastos administrativos cargados por almacen/oficina a su obra.


## Ajuste definido: origen de captura

Para separar gastos cargados desde almacen/oficina y gastos creados por residentes desde obra, se agregara un campo de trazabilidad en `reposicion_caja_chica_gastos`.

Campo propuesto:

- `origen_captura`

Valores iniciales:

- `almacen`: gastos creados desde el flujo general de reposicion de caja chica / almacen / Giralda.
- `obra_residente`: gastos creados desde el tab de caja chica de una obra.

Regla de visibilidad:

- El tab `Caja chica obra` solo debe mostrar gastos con:
  - `obra_id = {obra actual}`
  - `origen_captura = obra_residente`
- La bandeja administrativa de reposicion de caja chica puede seguir viendo todos los gastos, con filtros por destino, obra, almacen y origen.

Backfill sugerido:

- Como no hay datos reales en produccion, los registros existentes pueden inicializarse como `origen_captura = almacen`.
- Esto evita que gastos previamente capturados desde almacen aparezcan al residente, aunque tengan `obra_id`.

No se recomienda usar `created_by`, rol del usuario o `ambito` para esta separacion, porque no son trazabilidad estable del flujo de captura.
## Tareas pequenas

### 1. Ordenar tabs de obra

- [x] Identificar el arreglo/listado donde se definen los tabs en `resources/views/obras/edit.blade.php`.
- [x] Agregar comentarios visibles en codigo para separar:
  - Planeacion
  - Solicitudes
  - Reposicion gastos
  - Caja chica obra
- [x] Mover `caja-chica` al final como tab `Caja chica obra`, conservando el key para no romper URLs existentes.
- [x] No eliminar ni renombrar los tabs existentes.

Checkpoint:

- [x] La vista de obra sigue cargando/compila en Blade.
- [x] Los tabs existentes conservan sus URLs actuales.
- [x] El tab `Caja chica obra` queda al final del arreglo de tabs.


Nota de barrido paso 1:

- `resources/views/obras/edit.blade.php` define los tabs en un arreglo `$tabs` al inicio de la vista.
- El contenido actual de caja chica se renderiza con `@include('obras.partials.caja_chica.tab')` cuando `$tab === 'caja-chica'`.
- Para no romper enlaces existentes, el key sigue siendo `caja-chica`; solo se movio al final y se cambio la etiqueta visible a `Caja chica obra`.
- La separacion real a `obras.partials.caja_chica_obra.tab` queda para el paso 2.
### 2. Crear partial nuevo para caja chica obra

- [x] Crear `resources/views/obras/partials/caja_chica_obra/tab.blade.php`.
- [x] Replicar de forma limpia la tabla/resumen del partial actual de caja chica en el partial nuevo.
- [x] Agregar botones contextuales:
  - Ver bandeja filtrada por obra.
  - Nuevo gasto para esta obra.
- [x] Evitar meter mas HTML dentro de `obras/edit.blade.php`.

Checkpoint:

- [x] El partial nuevo renderiza solo cuando se abre el tab `caja-chica`.
- [x] La vista principal de obra queda apuntando al nuevo partial, sin agregar HTML extra.


Nota de barrido paso 2:

- El partial viejo `obras.partials.caja_chica.tab` se conserva intacto.
- El tab `caja-chica` ahora incluye `obras.partials.caja_chica_obra.tab`.
- El nuevo partial agrega enlaces contextuales con `destino=obra`, `obra_id={obra_id}` y `ambito=obra`.
- La preseleccion real en `reposicion-caja-chica/create` queda para el paso 4; por ahora el enlace ya transporta el contexto.

### 2.1. Separar gastos por origen de captura

- [x] Crear migracion para agregar `origen_captura` a `reposicion_caja_chica_gastos`.
- [x] Inicializar registros existentes con `origen_captura = almacen`.
- [x] Agregar `origen_captura` al `$fillable` de `ReposicionCajaChicaGasto`.
- [x] En captura desde obra, mandar `origen_captura = obra_residente`.
- [x] En captura general/almacen, usar default `origen_captura = almacen`.
- [x] Filtrar el tab `caja_chica_obra` desde `ObraController` para mostrar solo `obra_residente`.
- [ ] Opcional: agregar filtro/etiqueta de origen en la bandeja administrativa.

Checkpoint:

- [x] Gastos viejos o creados desde almacen no aparecen en el tab del residente.
- [x] Gastos creados desde obra si aparecen en el tab del residente.
- [x] Administracion conserva visibilidad de todos los gastos.
### 3. Prefiltrar bandeja por obra

- [x] Ajustar enlaces para abrir:
  - `reposicion-caja-chica?destino=obra&obra_id={obra_id}&ambito=obra`
- [x] Verificar y ajustar la bandeja para filtrar correctamente por `destino`, `obra_id` y `origen_captura`.
- [x] Agregar selector/filtro visible de obra sin afectar almacen.

Checkpoint:

- [x] Desde una obra se abre la bandeja con `destino=obra`, `obra_id` y `origen_captura=obra_residente`.
- [x] La bandeja general sigue mostrando todos segun filtros.

### 4. Prefijar obra en captura

- [x] Permitir abrir:
  - `reposicion-caja-chica/create?destino=obra&obra_id={obra_id}`
- [x] En `ReposicionCajaChicaController::create()`, leer esos query params y pasarlos a la vista.
- [x] En `create.blade.php`, inicializar Alpine con `targetDestino = obra` y `targetObraId = {obra_id}`.
- [ ] Si viene desde obra, considerar bloquear el selector de destino/obra para residentes.

Checkpoint:

- [x] Desde el tab de obra, "Nuevo gasto" abre captura con la obra ya seleccionada.
- [x] Al cargar XML, los renglones quedan con `destino = obra` y `obra_id` correcto.

### 5. Redireccion despues de guardar

- [ ] Si el lote viene desde contexto de obra, redirigir de regreso a:
  - `obras/{obra}/edit?tab=caja-chica-obra`
- [ ] Si viene de bandeja general, mantener redireccion actual.
- [ ] Preservar mensaje de exito.

Checkpoint:

- [ ] Guardar borrador desde obra regresa al tab correcto.
- [ ] Guardar y enviar desde obra regresa al tab correcto.
- [ ] Almacen/Giralda no cambia su redireccion esperada.

### 6. Permisos y alcance de residente

- [ ] Revisar permisos actuales `caja_chica.view`, `caja_chica.create`, `caja_chica.submit`.
- [ ] Verificar que los residentes tengan los permisos necesarios.
- [ ] Definir como se determina que una obra pertenece al residente.
- [ ] Aplicar filtro de seguridad en index/create/store/show para usuarios residentes.

Checkpoint:

- [ ] Un residente solo ve gastos de sus obras.
- [ ] Un residente no puede crear gastos para otra obra cambiando el query string.
- [ ] Admin/super-admin conserva vision general.

### 7. Firma e impresion

- [ ] Decidir si obra usara un ambito nuevo: `reposicion_gastos_obra`.
- [ ] Si se requiere, agregar definiciones/firma para ese ambito.
- [ ] Ajustar resolucion de ambito para reportes de gastos con `destino = obra`.
- [ ] Confirmar si el formato administrativo actual sirve o si necesita encabezado/contexto de obra.

Checkpoint:

- [ ] Imprimir desde bandeja filtrada por obra muestra contexto de obra.
- [ ] Las firmas son correctas para obra.

### 8. Pruebas manuales

- [ ] Abrir obra 18 en el nuevo tab.
- [ ] Abrir bandeja filtrada por obra.
- [ ] Crear gasto manual sin factura para obra.
- [ ] Crear gasto con XML para obra.
- [ ] Guardar como borrador.
- [ ] Guardar y enviar a revision.
- [ ] Confirmar que aparece en el tab de la obra.
- [ ] Confirmar que aparece en revision oficina.
- [ ] Confirmar que almacen/Giralda sigue igual.

## Preguntas abiertas

- ¿El residente podra elegir cualquier obra o solo sus obras asignadas?
- ¿El residente puede guardar borrador o siempre debe enviar a revision?
- ¿El tab nuevo se llamara `Caja chica obra`, `Caja chica`, o `Reposicion caja chica`?
- ¿La firma para obra debe ser diferente a almacen?
- ¿Los gastos de caja chica por obra deben afectar algun presupuesto, o solo son control administrativo?

## Estado actual

- [x] Barrido inicial del modulo de almacen/Giralda.
- [x] Barrido del flujo viejo de reposicion por obra.
- [x] Confirmado que `reposicion_caja_chica_gastos` ya soporta `obra_id`.
- [x] Confirmado que no conviene borrar ni mezclar el flujo de planeacion/partida.
- [ ] Implementacion pendiente.
