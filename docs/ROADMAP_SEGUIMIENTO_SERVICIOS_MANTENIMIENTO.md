# Roadmap: seguimiento de servicios de mantenimiento

Fecha: 2026-07-28

Fuente funcional: `C:/Users/hecto/Documents/RIVERA/2026/ESPECIFICACION FUNCIONAL DE MANTENIMIENTOS.docx`

## Objetivo

Cerrar el ciclo completo de servicios y mantenimientos de vehiculos y maquinaria:

1. Programar servicios preventivos, correctivos, manuales o de emergencia.
2. Coordinar fecha y responsable tecnico.
3. Confirmar disponibilidad del mecanico.
4. Solicitar y surtir materiales cuando aplique.
5. Registrar la ejecucion real del servicio.
6. Capturar materiales, refacciones, mano de obra y evidencias.
7. Validar administrativamente la informacion.
8. Cerrar el servicio y dejarlo como historial oficial del equipo.
9. Actualizar el kilometraje u horometro base para el siguiente preventivo.

## Principio De Diseno

El cierre del servicio no debe depender solo de cambiar un estatus.

Un servicio debe considerarse ejecutado y cerrado solo cuando exista:

- Registro de ejecucion.
- Mecanico o responsable que ejecuto.
- Fecha real.
- Trabajo realizado.
- Kilometraje u horometro final, cuando aplique.
- Material utilizado o confirmacion de que no aplica.
- Evidencia minima.
- Validacion administrativa.
- Usuario y fecha de cierre.

## Hallazgos Del Grafo Y Codigo

### Modelo principal existente

Archivo: `app/Models/Mantenimiento.php`

El modelo `Mantenimiento` ya tiene campos utiles:

- `vehiculo_id`
- `maquina_id`
- `obra_id`
- `tipo`
- `categoria_mantenimiento`
- `descripcion`
- `km_actuales`
- `km_proximo_servicio`
- `horometro`
- `fecha_programada`
- `fecha_inicio`
- `fecha_fin`
- `estatus`
- `mecanico_id`
- `costo_mano_obra`
- `costo_refacciones`
- `costo_total`
- `notas`

Tambien ya tiene relaciones:

- `vehiculo()`
- `maquina()`
- `obra()`
- `mecanico()`
- `detalles()`
- `fotos()`

### Tabla de mantenimientos existente

Archivo: `database/migrations/2025_12_11_192310_create_mantenimientos_table.php`

Actualmente `estatus` solo contempla:

- `pendiente`
- `en_proceso`
- `completado`
- `cancelado`

Estos estados ya no alcanzan para el flujo funcional completo.

Recomendacion tecnica:

- Migrar `estatus` de `enum` a `string`.
- Definir constantes de estado en `Mantenimiento.php`.
- Evitar nuevas migraciones cada vez que se agregue un estado operativo.

### Detalles de mantenimiento existentes

Archivo: `database/migrations/2025_12_11_192314_create_mantenimiento_detalles_table.php`

Ya existe `mantenimiento_detalles`, con:

- `mantenimiento_id`
- `concepto`
- `cantidad`
- `costo_unitario`
- `costo_total`
- `tipo`

Se puede reutilizar para:

- Materiales.
- Refacciones.
- Mano de obra.
- Consumibles.
- Servicios externos.

### Evidencias existentes

Archivo: `database/migrations/2025_12_11_192318_create_mantenimiento_fotos_table.php`

Ya existe `mantenimiento_fotos`, con:

- `mantenimiento_id`
- `ruta`
- `descripcion`

Sirve para el primer corte de evidencias.

Para una fase posterior conviene evolucionarlo a evidencias documentales con:

- Tipo de evidencia.
- Nombre original.
- Usuario que cargo.
- Fecha/hora de carga.
- Observaciones.
- Relacion opcional con orden de compra, salida de almacen o factura.

### Controlador actual

Archivo: `app/Http/Controllers/MantenimientoController.php`

Actualmente permite:

- Listar.
- Crear.
- Editar.
- Actualizar datos basicos.
- Mostrar detalle.

Falta:

- Confirmar servicio.
- Reprogramar con motivo.
- Solicitar material.
- Iniciar ejecucion.
- Capturar ejecucion.
- Enviar a revision.
- Devolver para correccion.
- Validar y cerrar.
- Bloquear edicion normal cuando este cerrado.

## Estados Propuestos

### Estados principales

Usar estos estados como constantes en `Mantenimiento`:

- `servicio_generado`
- `pendiente_coordinacion`
- `pendiente_confirmacion`
- `confirmado`
- `material_solicitado`
- `material_en_revision`
- `material_surtido`
- `en_ejecucion`
- `en_revision`
- `cerrado`

### Estados alternos

- `pendiente_reprogramacion`
- `reprogramado`
- `material_insuficiente`
- `en_proceso_compra`
- `oc_pendiente_autorizacion`
- `oc_rechazada`
- `oc_requiere_ajuste`
- `requiere_correccion`
- `cancelado`

### Mapeo desde estados actuales

- `pendiente` -> `pendiente_coordinacion`
- `en_proceso` -> `en_ejecucion`
- `completado` -> `cerrado`
- `cancelado` -> `cancelado`

## Flujo Funcional Ajustado

### 1. Servicio generado o registrado

Origenes:

- Automatico desde configuracion de empresa.
- Manual por emergencia, falla o necesidad operativa.

Datos minimos:

- Vehiculo o maquina.
- Tipo de servicio.
- Descripcion.
- Fecha propuesta.
- Prioridad.
- Kilometraje u horometro actual, cuando aplique.
- Proximo servicio estimado, cuando aplique.

Estado sugerido:

- Automatico: `servicio_generado`
- Manual/emergencia: `pendiente_coordinacion`

### 2. Coordinacion

El encargado de almacen o administrativo revisa fecha con el mecanico.

Si no hay disponibilidad:

- Estado: `pendiente_reprogramacion`
- Registrar `motivo_reprogramacion`.

Si se acuerda nueva fecha:

- Estado: `reprogramado`
- Guardar nueva fecha propuesta.

### 3. Confirmacion

El servicio queda confirmado cuando almacen/administracion y mecanico aceptan fecha.

Guardar:

- `fecha_confirmada`
- `mecanico_id`
- `confirmado_por_user_id`
- `confirmado_at`
- Observaciones de confirmacion.

Estado:

- `confirmado`

### 4. Materiales

El mecanico puede solicitar material antes o durante la ejecucion.

Primer corte:

- Captura manual en `mantenimiento_detalles`.
- No bloquear servicios que no requieren material.

Fase posterior:

- Solicitud formal de material.
- Revision de inventario.
- Salida de almacen.
- Requisicion si falta material.
- Orden de compra si aplica.

Estados relacionados:

- `material_solicitado`
- `material_en_revision`
- `material_insuficiente`
- `en_proceso_compra`
- `material_surtido`

### 5. Ejecucion

El mecanico ejecuta el servicio.

Actualmente, segun la especificacion, el mecanico puede reportar en papel. El encargado de almacen captura posteriormente la informacion digital en SIRICO.

Al iniciar ejecucion:

- `estatus = en_ejecucion`
- `fecha_inicio = now()` o fecha real capturada
- `iniciado_por_user_id = auth()->id()`

### 6. Registro digital

El encargado captura lo reportado por el mecanico.

Campos minimos:

- Fecha real de ejecucion.
- Mecanico que ejecuto.
- Trabajo realizado.
- Material utilizado o "sin material".
- Observaciones.
- Kilometraje final, para vehiculo.
- Horometro final, para maquina.
- Estado final del equipo.
- Proximo servicio sugerido.
- Evidencias.

Al terminar captura:

- `estatus = en_revision`
- `capturado_por_user_id = auth()->id()`
- `capturado_at = now()`

### 7. Validacion administrativa

El responsable administrativo revisa:

- Equipo correcto.
- Tipo de servicio.
- Fecha real.
- Mecanico.
- Trabajo realizado.
- Material utilizado.
- Salidas de almacen relacionadas, si aplica.
- Requisiciones/ordenes de compra relacionadas, si aplica.
- Evidencias.
- Kilometraje u horometro.
- Observaciones.
- Proximo servicio.

Si falta informacion:

- `estatus = requiere_correccion`
- Guardar `motivo_correccion`.

Si todo esta correcto:

- Validar y cerrar.

### 8. Cierre

El cierre lo realiza administracion, no el mecanico.

Al cerrar:

- `estatus = cerrado`
- `fecha_fin = now()` o fecha validada
- `validado_por_user_id = auth()->id()`
- `validado_at = now()`
- `cerrado_por_user_id = auth()->id()`
- `cerrado_at = now()`
- Bloquear edicion normal.
- Actualizar historial oficial del equipo.
- Actualizar base del siguiente preventivo.

## Campos Nuevos Recomendados

### En `mantenimientos`

Agregar en una primera migracion:

- `prioridad` string nullable/default `normal`
- `fecha_confirmada` datetime nullable
- `confirmado_por_user_id` foreign nullable
- `confirmado_at` datetime nullable
- `iniciado_por_user_id` foreign nullable
- `capturado_por_user_id` foreign nullable
- `capturado_at` datetime nullable
- `validado_por_user_id` foreign nullable
- `validado_at` datetime nullable
- `cerrado_por_user_id` foreign nullable
- `cerrado_at` datetime nullable
- `trabajo_realizado` text nullable
- `estado_equipo_final` string nullable
- `motivo_reprogramacion` text nullable
- `motivo_cancelacion` text nullable
- `motivo_correccion` text nullable

Actualizar tambien:

- `estatus` a string.

### En `Mantenimiento.php`

Agregar constantes:

- `ESTADO_SERVICIO_GENERADO`
- `ESTADO_PENDIENTE_COORDINACION`
- `ESTADO_PENDIENTE_CONFIRMACION`
- `ESTADO_CONFIRMADO`
- `ESTADO_MATERIAL_SOLICITADO`
- `ESTADO_MATERIAL_EN_REVISION`
- `ESTADO_MATERIAL_SURTIDO`
- `ESTADO_EN_EJECUCION`
- `ESTADO_EN_REVISION`
- `ESTADO_CERRADO`
- `ESTADO_REQUIERE_CORRECCION`
- `ESTADO_CANCELADO`

Agregar helpers:

- `estaCerrado()`
- `puedeConfirmarse()`
- `puedeIniciarse()`
- `puedeCapturarse()`
- `puedeValidarse()`
- `puedeCancelarse()`

Agregar relaciones:

- `confirmadoPor()`
- `iniciadoPor()`
- `capturadoPor()`
- `validadoPor()`
- `cerradoPor()`

## Checkpoints De Implementacion

### Checkpoint 1: Normalizar estados y auditoria base

Objetivo:

Preparar la tabla/modelo para soportar el flujo robusto sin cambiar todavia inventario/compras.

Tareas:

- Crear migracion para convertir `mantenimientos.estatus` de `enum` a `string`.
- Mapear datos actuales:
  - `pendiente` -> `pendiente_coordinacion`
  - `en_proceso` -> `en_ejecucion`
  - `completado` -> `cerrado`
  - `cancelado` -> `cancelado`
- Agregar campos nuevos de auditoria y seguimiento.
- Actualizar `$fillable`.
- Agregar casts de fechas.
- Agregar constantes, listas de estados y helpers.
- Agregar relaciones a `User`.

Criterios de listo:

- Migraciones corren sin error.
- Servicios existentes siguen abriendo.
- Badges/listados muestran estados nuevos.
- Un servicio cerrado sigue siendo interpretado como servicio ejecutado para preventivos.

### Checkpoint 2: Ajustar UI de estados sin acciones complejas

Objetivo:

Que la web muestre correctamente el nuevo flujo antes de agregar botones operativos.

Tareas:

- Actualizar labels y colores de estados en index/show/tabs.
- Evitar que el usuario seleccione cualquier estado libremente desde un dropdown simple.
- Mostrar bloque de trazabilidad:
  - Programado.
  - Confirmado.
  - Iniciado.
  - Capturado.
  - Validado.
  - Cerrado.

Criterios de listo:

- El usuario entiende en que etapa esta el servicio.
- No se rompen vistas de vehiculos ni maquinas.
- Los servicios cerrados se distinguen claramente de los que solo estan en revision.

### Checkpoint 3: Acciones de coordinacion

Objetivo:

Controlar fecha y confirmacion antes de ejecutar.

Tareas:

- Agregar acciones:
  - Confirmar servicio.
  - Reprogramar servicio.
  - Cancelar servicio.
- Guardar:
  - `fecha_confirmada`
  - `confirmado_por_user_id`
  - `confirmado_at`
  - `motivo_reprogramacion`
  - `motivo_cancelacion`
- Notificar a responsables cuando se confirme o reprograme.

Criterios de listo:

- Un servicio pendiente puede confirmarse.
- Un servicio puede reprogramarse con motivo.
- Un servicio cancelado no cuenta como ejecutado.

### Checkpoint 4: Iniciar ejecucion

Objetivo:

Registrar cuando el servicio realmente inicia.

Tareas:

- Agregar accion `iniciar`.
- Validar que solo se pueda iniciar si esta confirmado o material surtido.
- Guardar:
  - `fecha_inicio`
  - `iniciado_por_user_id`
- Cambiar estado a `en_ejecucion`.

Criterios de listo:

- No se puede cerrar un servicio que nunca inicio o no fue capturado.
- El historial muestra fecha y usuario de inicio.

### Checkpoint 5: Registro digital de ejecucion

Objetivo:

Capturar lo reportado por el mecanico.

Tareas:

- Crear formulario de captura de ejecucion.
- Campos:
  - Fecha real.
  - Mecanico.
  - Trabajo realizado.
  - Observaciones.
  - KM final u horometro final.
  - Estado final del equipo.
  - Proximo servicio sugerido.
  - Material utilizado o "sin material".
- Guardar `capturado_por_user_id` y `capturado_at`.
- Mover a `en_revision`.

Criterios de listo:

- El servicio queda listo para validacion administrativa.
- El badge preventivo todavia no se apaga hasta cerrar.
- La vista detalle muestra lo capturado.

### Checkpoint 6: Materiales/refacciones manuales

Objetivo:

Registrar materiales sin conectar aun inventario.

Tareas:

- Usar `mantenimiento_detalles`.
- Permitir filas dinamicas:
  - `tipo`
  - `concepto`
  - `cantidad`
  - `costo_unitario`
  - `costo_total`
- Tipos sugeridos:
  - `material`
  - `refaccion`
  - `mano_obra`
  - `servicio_externo`
  - `consumible`
- Recalcular:
  - `costo_refacciones`
  - `costo_mano_obra`
  - `costo_total`

Criterios de listo:

- Se puede cerrar servicio con materiales.
- Se puede cerrar servicio sin materiales marcando que no aplica.
- Los costos se reflejan en historial.

### Checkpoint 7: Evidencias documentales

Objetivo:

Guardar evidencia minima antes de cerrar.

Tareas:

- Reutilizar `mantenimiento_fotos` en primer corte.
- Permitir subir una o varias imagenes.
- Guardar descripcion.
- Definir evidencia minima recomendada:
  - Reporte del mecanico escaneado/foto.
  - Fotos antes/despues opcionales.
  - Factura/cotizacion/OC/salida de almacen opcionales.

Criterios de listo:

- Un servicio en revision muestra evidencias.
- Administracion puede validar con base en evidencia.
- Las evidencias quedan visibles en historial.

### Checkpoint 8: Validacion y cierre administrativo

Objetivo:

Separar captura de ejecucion de cierre oficial.

Tareas:

- Agregar accion `validar_y_cerrar`.
- Agregar accion `devolver_para_correccion`.
- Validar datos minimos:
  - Trabajo realizado.
  - Mecanico.
  - Fecha real.
  - KM u horometro si aplica.
  - Material usado o marcado como no aplica.
  - Evidencia minima recomendada.
- Guardar:
  - `validado_por_user_id`
  - `validado_at`
  - `cerrado_por_user_id`
  - `cerrado_at`
  - `fecha_fin`
- Bloquear edicion normal cuando `estatus = cerrado`.

Criterios de listo:

- Solo administracion cierra el servicio.
- Si falta informacion, vuelve a `requiere_correccion`.
- Solo `cerrado` cuenta como servicio ejecutado oficial.

### Checkpoint 9: Integracion con preventivo KM/horometro

Objetivo:

Que el cierre alimente alertas y proximo servicio.

Tareas:

- Ajustar `PreventivoVehiculoService` para tomar solo servicios `cerrado`.
- Ajustar `PreventivoMaquinaService` con el mismo criterio.
- Confirmar que `km_actuales` o `horometro` final sean la base.
- Confirmar que `km_proximo_servicio` se respete o calcule segun configuracion.

Criterios de listo:

- Un vehiculo vencido deja de aparecer vencido al cerrar servicio correcto.
- Un servicio en revision no apaga alerta todavia.
- Un servicio cancelado no afecta calculo.

### Checkpoint 10: Materiales con inventario y compras

Objetivo:

Conectar el flujo con almacen, inventario y ordenes de compra.

Tareas:

- Crear solicitud formal de material ligada a mantenimiento.
- Revisar stock.
- Generar salida de inventario si hay material.
- Generar requisicion si falta material.
- Relacionar orden de compra.
- Relacionar entrada de almacen.
- Relacionar salida final al mecanico.

Criterios de listo:

- Material de inventario descuenta stock.
- Material comprado queda ligado al servicio.
- El historial muestra salida, OC y evidencia documental.

## Validaciones Necesarias

### Web

- Crear mantenimiento automatico o manual.
- Confirmarlo.
- Reprogramarlo con motivo.
- Cancelarlo con motivo.
- Iniciarlo.
- Capturar ejecucion.
- Adjuntar material/evidencia.
- Enviarlo a revision.
- Devolverlo para correccion.
- Validarlo y cerrarlo.

### Vehiculos

- Cerrar servicio con KM final.
- Confirmar que el badge de preventivo se actualiza solo al cerrar.
- Confirmar que el tab de mantenimientos muestra historial.

### Maquinaria

- Cerrar servicio con horometro final.
- Confirmar que no exige KM.
- Confirmar que el preventivo de maquinas usa solo cerrados.

### Alertas

Ejecutar:

```bash
php artisan vehiculos:alertas-preventivo-km --dry-run
```

Confirmar:

- Servicios en revision no apagan alerta.
- Servicios cerrados si actualizan el calculo.
- Servicios cancelados no cuentan.

## Preguntas Pendientes

1. Que usuarios pueden confirmar, iniciar, capturar, validar y cerrar?
2. Administracion sera el unico rol que puede cerrar?
3. La evidencia minima sera obligatoria desde el primer corte?
4. El material usado puede capturarse manualmente al inicio o debe nacer como solicitud?
5. El proximo servicio se captura manual o se calcula automaticamente desde configuracion?
6. Los servicios externos entran desde el primer corte o se dejan como fase posterior?

## Recomendacion De Arranque

Arrancar por el Checkpoint 1.

No conviene empezar por pantallas ni materiales hasta que el modelo soporte el flujo completo.

Primer bloque concreto:

1. Migrar `estatus` a string.
2. Mapear estados legacy.
3. Agregar campos de auditoria.
4. Agregar constantes y helpers en `Mantenimiento`.
5. Ajustar listados para no romper estados existentes.

Despues de eso, implementar coordinacion y acciones por etapas.
