# Plan: Comprobacion diaria de asistencias de obra

## Objetivo

Separar claramente dos fases del flujo de asistencia en obra:

1. **Lista semanal para nomina**: reporte administrativo que el residente genera para que oficina procese la nomina de la semana.
2. **Comprobacion diaria de asistencia**: validacion posterior dia por dia, usando registros reales de entrada/salida desde app movil o captura manual en Laravel.

La lista semanal puede generarse de buena fe, pero la comprobacion diaria debe alimentar horarios reales, horas extra, evidencias y futuros ajustes/descuentos.

## Contexto actual

- La app movil registra asistencias reales en `obras_asistencias` con `tipo = entrada/salida`, `checked_at`, `checked_date`, foto, ubicacion y usuario.
- El tab web `/obras/{obra}/edit?tab=asistencias` arma una lista semanal administrativa usando `obra_asistencia_semanal_reportes` y `obra_asistencia_semanal_detalles`.
- En `buildAsistenciaSemanalRows()`, si no existe detalle guardado, `planeado_asistir` inicia como `true`, por eso todos los checks aparecen marcados.
- La lista semanal impresa solo refleja asistencia administrativa, no necesariamente evidencia real de entrada/salida.
- Hoy Laravel no permite capturar manualmente hora de entrada/salida para suplir la app movil.

## Reglas operativas buscadas

- Antes de generar/enviar la lista semanal, la tabla puede permanecer blanca y editable como lista administrativa.
- Despues de generar/enviar la lista semanal, la tabla pasa a modo comprobacion diaria.
- No se permite capturar dias futuros.
- No se permite capturar semanas pasadas, salvo permiso especial futuro.
- Si un dia pasado dentro de la semana actual no fue comprobado, debe quedar como pendiente vencido y seguir capturable.
- Solo debe existir un dia verde: el dia actual capturable.
- Dias pasados sin captura no deben seguir verdes; deben verse como pendientes vencidos.
- Las capturas manuales desde Laravel no exigen foto, pero si deben exigir motivo/nota.
- La captura manual debe guardar en `obras_asistencias`, no solo en el detalle semanal.
- La comprobacion diaria sera la base futura para horas extra y ajustes/descuentos.

## Estados visuales por celda

- **Blanco**: lista semanal no generada; modo armado administrativo.
- **Gris claro**: lista generada/enviada; modo comprobacion.
- **Verde**: dia actual capturable.
- **Amarillo**: dia pasado dentro de la semana actual, planeado, sin evidencia completa; pendiente vencido.
- **Azul o verde suave**: dia comprobado con entrada y salida.
- **Azul claro**: captura parcial, solo entrada o solo salida.
- **Morado**: excepcion registrada/autorizada.
- **Rojo suave**: falta/no asistio confirmado.
- **Gris bloqueado**: futuro o semana no capturable.

## Fase 1: Barrido tecnico

### Tareas

- Revisar `ObraController`:
  - armado del tab `asistencias`
  - `guardarAsistenciaSemanal()`
  - `imprimirAsistenciaSemanal()`
  - `buildAsistenciaSemanalRows()`
- Revisar modelos:
  - `ObraAsistencia`
  - `ObraAsistenciaSemanalReporte`
  - `ObraAsistenciaSemanalDetalle`
- Revisar API movil:
  - `Api/V1/AsistenciasController@store`
  - reglas de entrada/salida y foto
- Revisar vistas:
  - `resources/views/obras/partials/asistencias/_weekly-list.blade.php`
  - `_workflow.blade.php`
  - `_filters.blade.php`
  - `resources/views/obras/asistencias/semanal-imprimir.blade.php`
- Revisar rutas actuales de asistencia en `routes/web.php` y API.

### Checkpoint

- Documento/resumen con mapa de archivos, tablas, reglas actuales y puntos exactos de cambio.
- Confirmar si se requiere nueva ruta web o si se puede extender una existente.

### Hallazgos del barrido tecnico - 2026-10-05

#### Rutas actuales

- API movil:
  - `POST api/v1/obras/{obra}/asistencias` -> `Api\V1\AsistenciasController@store`
  - `GET api/v1/obras/{obra}/asistencias` -> `Api\V1\AsistenciasController@show`
  - `GET api/v1/obras/{obra}/empleados/{empleado}/asistencias` -> `Api\V1\AsistenciasController@showEmpleado`
  - `DELETE api/v1/obras/{obra}/asistencias/{asistencia}` -> `Api\V1\AsistenciasController@destroy`
- Web:
  - `GET obras/{obra}/asistencias/reporte` -> `ObraController@reporteAsistencias`
  - `POST obras/{obra}/asistencias/semanal` -> `ObraController@guardarAsistenciaSemanal`
  - `GET obras/{obra}/asistencias/semanal/{reporte}/imprimir` -> `ObraController@imprimirAsistenciaSemanal`
- No existe ruta web para capturar entrada/salida manual en `obras_asistencias`.

#### Tablas y modelos involucrados

- `obras_asistencias`:
  - Es la fuente real de asistencia de campo.
  - Guarda `obra_id`, `empleado_id`, `registrado_por_user_id`, `tipo`, `checked_at`, `checked_date`, foto, ubicacion y `meta`.
  - Tiene llave unica por `obra_id`, `empleado_id`, `checked_date`, `tipo`.
  - Ya permite distinguir origen sin migracion usando `meta.origen`.
- `obra_asistencia_semanal_reportes`:
  - Es el encabezado del reporte semanal administrativo.
  - Controla `semana_inicio`, `semana_fin`, `estatus` y fechas de flujo: generado, revisado, autorizado y pagado.
  - Tiene llave unica por `obra_id`, `semana_inicio`.
- `obra_asistencia_semanal_detalles`:
  - Guarda el detalle administrativo por empleado y fecha.
  - Tiene `planeado_asistir`, `estado_admin`, `estado_campo`, links opcionales a entrada/salida reales y excepciones.

#### Flujo actual de app movil

- `Api\V1\AsistenciasController@store` valida empleado, fecha/hora, foto opcional/obligatoria segun caso, ubicacion y `meta`.
- Calcula `checked_date` con zona `America/Mexico_City`.
- Verifica que el empleado este activo en la obra.
- Decide automaticamente el tipo:
  - Si no hay registros del dia: `entrada`.
  - Si ya hay entrada: `salida`.
  - Si ya hay entrada y salida: rechaza nueva captura.
- La entrada exige foto; la salida permite foto opcional.
- Inserta en `obras_asistencias`.

#### Flujo actual web del tab asistencias

- `ObraController@edit` arma el tab cuando `tab=asistencias`.
- Carga registros reales desde `obras_asistencias`, agrupados por empleado y fecha.
- Carga el reporte semanal si existe por obra y `semana_inicio`.
- Llama a `buildAsistenciaSemanalRows()` para armar las celdas de la tabla semanal.
- Los dias ya se ajustaron a etiquetas en espanol (`LUN`, `MAR`, `MIE`, etc.).

#### Regla que explica los checks marcados

- En `buildAsistenciaSemanalRows()`, si no existe detalle guardado, la celda inicia con:
  - `planeado_asistir = true`
- Por eso la pantalla aparece con todos los checks marcados.
- Esto confirma que la tabla actual representa una lista administrativa de buena fe, no evidencia real de asistencia.

#### Vistas revisadas

- `resources/views/obras/partials/asistencias/tab.blade.php`
- `resources/views/obras/partials/asistencias/_weekly-list.blade.php`
- `resources/views/obras/partials/asistencias/_workflow.blade.php`
- `resources/views/obras/partials/asistencias/_filters.blade.php`
- `resources/views/obras/partials/asistencias/_registered.blade.php`
- `resources/views/obras/asistencias/semanal-imprimir.blade.php`

#### Hallazgos en vistas

- `_weekly-list.blade.php` muestra:
  - check administrativo por dia
  - badges `Ent --` y `Sal --`
  - estado de evidencia
  - excepcion y nota
- No tiene inputs para capturar horarios manuales.
- No tiene estados visuales por celda como `hoy`, `pendiente vencido`, `futuro bloqueado`, `comprobado manual` o `comprobado app`.
- `_registered.blade.php` si muestra registros reales de entrada/salida, pero es solo lectura.
- `_workflow.blade.php` ya expone el estatus del reporte y puede servir para activar el modo comprobacion.
- El PDF semanal imprime checks administrativos; no imprime origen de evidencia ni horarios reales.

#### Puntos exactos de cambio detectados

- Se necesita una ruta web nueva para captura manual, porque ninguna ruta actual guarda entrada/salida desde Laravel.
- `buildAsistenciaSemanalRows()` debe enriquecerse para calcular estado por celda antes de tocar el UI:
  - `capturable`
  - `bloqueada`
  - `estado_visual`
  - `label_visual`
  - `origen_evidencia`
  - `es_hoy`
  - `es_futuro`
  - `es_pasado_en_semana_actual`
  - `tiene_entrada`
  - `tiene_salida`
- La captura manual debe guardar en `obras_asistencias`, no solo en `obra_asistencia_semanal_detalles`.
- El origen manual puede guardarse inicialmente en `meta`:
  - `meta.origen = web_manual`
  - `meta.motivo = ...`
  - `meta.capturado_desde = laravel`
- No se requiere migracion para arrancar la captura manual si usamos `meta`.

#### Riesgos tecnicos encontrados

- `estado_campo` puede quedar guardado en el detalle semanal con una fotografia del momento; si despues llega una captura app/manual, hay que decidir si se recalcula en vivo o si se actualiza el detalle al guardar.
- Ya existe calculo interno de minutos trabajados/horas extra en `buildAsistenciaSemanalRows()`, pero hoy no se muestra ni se guarda como dato operativo.
- Si se permite editar dias comprobados sin permiso, se puede alterar evidencia ya usada para nomina.
- El reporte PDF actual no debe cambiar en esta fase para no romper el flujo administrativo.

#### Checkpoint fase 1

- Confirmado: si se requiere una nueva ruta web para la captura manual de entrada/salida.
- Confirmado: el modelo actual de `obras_asistencias` soporta el fallback manual usando `meta`, sin migracion inicial.
- Confirmado: el siguiente paso logico es agregar calculo de estado por celda en backend antes de tocar la captura manual.
## Fase 2: Calculo de estado por celda

### Tareas

- Extender `buildAsistenciaSemanalRows()` para calcular por dia:
  - `modo_celda`
  - `capturable`
  - `bloqueada`
  - `origen_evidencia`: `app`, `web_manual`, `sin_evidencia`, `excepcion`
  - `estado_visual`
  - `label_visual`
- Detectar si la lista semanal ya fue generada:
  - `estatus = generado/revisado/autorizado/pagado`
- Determinar fecha actual en `America/Mexico_City`.
- Determinar si el dia pertenece a semana actual.
- Determinar si el dia es futuro, hoy, pasado dentro de semana actual o semana anterior.

### Checkpoint

- Sin modificar captura todavia, la vista debe poder recibir datos de estado por celda.
- Verificar con semana actual:
  - hoy verde
  - futuro gris
  - pasado sin evidencia amarillo
  - comprobado azul/verde suave

## Fase 3: Pintar estados visuales

### Tareas

- Ajustar `_weekly-list.blade.php` para usar `estado_visual`.
- Si lista no generada: mantener apariencia blanca.
- Si lista generada: aplicar fondo gris general.
- Pintar la celda segun estado:
  - hoy capturable
  - pendiente vencido
  - comprobado
  - parcial
  - excepcion
  - futuro bloqueado
- Agregar etiqueta visible por celda:
  - `Hoy`
  - `Pendiente`
  - `Comprobado`
  - `Parcial`
  - `Manual`
  - `App`
  - `Futuro`
  - `Excepcion`

### Checkpoint

- No debe haber dos celdas verdes al mismo tiempo.
- Los dias vencidos deben ser visibles como pendientes, no como activos.
- Los futuros deben verse bloqueados.

## Fase 4: Ruta y metodo para captura manual Laravel

### Tareas

- Crear ruta web, propuesta:
  - `POST obras/{obra}/asistencias/manual`
  - nombre: `obras.asistencias.manual.guardar`
- Crear metodo en `ObraController`, propuesta:
  - `guardarAsistenciaManual(Request $request, Obra $obra)`
- Validaciones:
  - empleado requerido y asignado activo a la obra
  - fecha requerida
  - fecha no futura
  - fecha dentro de la semana actual
  - entrada y/o salida requerida
  - si hay entrada y salida, salida mayor que entrada
  - motivo requerido
  - no permitir semana pasada sin permiso especial futuro
- Guardado:
  - crear/actualizar registro `entrada` en `obras_asistencias`
  - crear/actualizar registro `salida` en `obras_asistencias`
  - `checked_at` con fecha + hora en zona `America/Mexico_City`, guardado como UTC si se mantiene criterio actual
  - `checked_date` como fecha local Mexico
  - `registrado_por_user_id = auth()->id()`
  - `meta.origen = web_manual`
  - `meta.motivo = ...`
  - `meta.capturado_desde = laravel`
- No exigir foto.

### Checkpoint

- Capturar manualmente entrada/salida para un empleado y dia actual.
- Confirmar que aparecen en la celda como `Manual`.
- Confirmar que se guardan en `obras_asistencias` con `tipo = entrada/salida`.

## Fase 5: Modal de captura manual

### Tareas

- En `_weekly-list.blade.php`, mostrar boton/modal solo si `capturable = true`.
- Modal con:
  - empleado
  - fecha
  - hora entrada
  - hora salida
  - motivo/nota obligatorio
- Permitir capturar:
  - dia actual
  - dias pasados pendientes dentro de semana actual
- Bloquear:
  - dias futuros
  - semana pasada
  - dias ya comprobados, salvo permiso futuro

### Checkpoint

- El usuario sabe donde capturar.
- Despues de guardar, la celda cambia de pendiente a comprobada manual.
- Si se captura solo entrada o salida, queda como parcial.

## Fase 6: Diferenciar origen de evidencia

### Tareas

- Si la asistencia viene desde app movil:
  - mostrar `App`
  - conservar foto si existe
- Si viene de Laravel:
  - mostrar `Manual`
  - mostrar motivo/nota
- Si no hay evidencia:
  - mostrar `Sin evidencia` o `Pendiente`
- Si hay excepcion:
  - mostrar `Excepcion`

### Checkpoint

- Una celda con app y otra manual se distinguen claramente.
- El usuario puede saber que ya se guardo un registro real.

## Fase 7: Resumen por empleado

### Tareas

- Ajustar resumen lateral por empleado para mostrar:
  - asistencias administrativas planeadas
  - comprobadas en campo
  - comprobadas manualmente
  - parciales
  - pendientes vencidas
  - sin evidencia
  - excepciones
- Mantener total de asistencia administrativa para PDF.

### Checkpoint

- El resumen refleja lo que se ve en celdas.
- El residente puede ubicar pendientes rapidamente.

## Fase 8: Pruebas del flujo completo

### Escenarios

- Lista no generada: tabla blanca.
- Lista generada: tabla gris/modo comprobacion.
- Hoy sin captura: hoy verde.
- Ayer sin captura en misma semana: amarillo pendiente vencido.
- Manana: gris bloqueado.
- App movil registra entrada/salida: celda comprobada `App`.
- Laravel registra entrada/salida: celda comprobada `Manual`.
- Captura parcial: celda parcial.
- Excepcion: celda morada.
- Semana pasada: bloqueada.

### Checkpoint

- Todos los escenarios se comportan segun reglas.
- No se rompe la impresion semanal actual.

## Fuera de alcance por ahora

- Descuentos automaticos en nomina.
- Permiso especial para corregir semanas pasadas.
- Firma/autorizacion avanzada del reporte semanal.
- Recalculo automatico de horas extra en nomina.

## Riesgos y decisiones pendientes

- Definir si un dia ya comprobado puede editarse sin permiso.
- Definir si capturar solo salida sin entrada es valido.
- Definir si la captura manual debe requerir motivo siempre o solo cuando el dia ya vencio.
- Definir si la semana actual se calcula lunes-domingo siempre.
- Definir si el reporte PDF debe seguir mostrando solo checks administrativos o tambien origen/evidencia.

