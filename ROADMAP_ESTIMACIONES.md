# Roadmap: Estimaciones de Obra Civil

## Objetivo

Crear un nuevo flujo de estimaciones para obra civil usando como base los conceptos y partidas importados desde Excel.

La mecanica inicial sera parecida a la usada en ordenes de compra: el usuario selecciona conceptos, captura cantidades y el sistema genera un registro con detalle. La diferencia es que este nuevo flujo representa avance estimado de obra, no una compra.

La orden de compra existente se conserva tal como esta. Aunque el concepto original no era el correcto para esta necesidad, su implementacion puede servir como referencia tecnica y de experiencia de usuario.

## Concepto General

Una estimacion es un registro asociado a una obra civil que contiene una o varias partidas/conceptos con cantidades a estimar.

Cada estimacion debe tener:

- Obra civil relacionada.
- Folio o numero consecutivo.
- Estado.
- Fecha de creacion.
- Total estimado.
- Detalle de conceptos estimados.
- Usuario creador.
- Datos de autorizacion cuando aplique.

Cada renglon del detalle debe guardar una copia de la informacion importante del concepto usado:

- Partida o clave.
- Concepto o descripcion.
- Unidad.
- Cantidad estimada.
- Precio unitario.
- Importe.

Esto evita que una estimacion historica cambie si despues se modifica el Excel, el presupuesto o el concepto original.

## Estados

Estados iniciales propuestos:

- `creada`: estimacion capturada, aun editable.
- `autorizada`: estimacion aprobada, ya no editable en cantidades.
- `facturada`: estimacion marcada como facturada.
- `pagada`: estimacion marcada como pagada.
- `cancelada`: estimacion anulada, no debe contar en totales activos.

Flujo principal:

```text
creada -> autorizada -> facturada -> pagada
```

Flujo de cancelacion:

```text
creada -> cancelada
autorizada -> cancelada
facturada -> cancelada
```

Por ahora no se recomienda permitir:

```text
pagada -> cancelada
```

Si mas adelante se necesita revertir una estimacion pagada, conviene crear un flujo formal de cancelacion, nota de credito, ajuste o contraestimacion.

## Vista Principal

Vista objetivo:

```text
/obra_civil/{id}/detalles
```

Cambios esperados:

- Agregar boton `Generar estimacion`.
- Abrir modal con la lista de partidas/conceptos importados desde Excel.
- Permitir seleccionar partidas.
- Permitir ingresar cantidad a estimar por partida.
- Calcular importe por partida.
- Calcular total de la estimacion.
- Guardar la estimacion en estado `creada`.
- Mostrar lista de estimaciones existentes.
- Mostrar estados y totales.
- Agregar resumen en el header de la obra.

## Header De La Obra

Agregar indicadores compactos en el header de detalles de obra civil.

Indicadores recomendados:

- Numero de estimaciones activas.
- Total estimado.
- Total autorizado.
- Total facturado.
- Total pagado.

Reglas de calculo:

- `Total estimado`: suma de estimaciones `creada`, `autorizada`, `facturada` y `pagada`.
- `Total autorizado`: suma de estimaciones `autorizada`, `facturada` y `pagada`.
- `Total facturado`: suma de estimaciones `facturada` y `pagada`.
- `Total pagado`: suma de estimaciones `pagada`.
- `cancelada` no suma en totales activos.

Ejemplo visual:

```text
Estimaciones: 4 | Estimado: $150,000.00 | Autorizado: $120,000.00 | Facturado: $80,000.00 | Pagado: $50,000.00
```

## Modal Generar Estimacion

El modal debe reutilizar la logica visual de seleccion usada en ordenes de compra cuando sea conveniente.

Columnas sugeridas:

- Selector.
- Partida/clave.
- Concepto.
- Unidad.
- Cantidad original/importada.
- Cantidad ya estimada.
- Cantidad disponible.
- Cantidad a estimar.
- Precio unitario.
- Importe.

Validaciones:

- La cantidad a estimar debe ser mayor a 0.
- No permitir estimar mas que la cantidad disponible.
- No guardar renglones no seleccionados.
- No permitir crear estimacion sin partidas.
- No permitir editar cantidades si la estimacion ya no esta en estado `creada`.

## Lista De Estimaciones

En la misma vista de detalles de obra, mostrar una seccion de estimaciones.

Datos sugeridos:

- Folio.
- Fecha.
- Estado.
- Numero de conceptos.
- Total.
- Usuario creador.
- Fecha de autorizacion si aplica.
- Acciones.

Acciones iniciales:

- Ver detalle.
- Editar, solo si esta `creada`.
- Autorizar, solo si esta `creada`.
- Marcar como facturada, solo si esta `autorizada`.
- Marcar como pagada, solo si esta `facturada`.
- Cancelar, si esta `creada`, `autorizada` o `facturada`.

## Modelo De Datos Propuesto

Tabla: `estimaciones`

- `id`
- `obra_civil_id`
- `folio`
- `estado`
- `fecha`
- `subtotal` o `total`
- `created_by`
- `authorized_by`
- `authorized_at`
- `facturated_at` o `facturada_at`
- `paid_at` o `pagada_at`
- `cancelled_at` o `cancelada_at`
- `cancelled_by` o `cancelada_by`
- `created_at`
- `updated_at`

Tabla: `estimacion_detalles`

- `id`
- `estimacion_id`
- Referencia al concepto original, si existe.
- `partida`
- `concepto`
- `unidad`
- `cantidad_original`
- `cantidad_estimada`
- `precio_unitario`
- `importe`
- `created_at`
- `updated_at`

Notas:

- El detalle debe funcionar como snapshot.
- La referencia al concepto original ayuda a calcular acumulados, pero la informacion textual y economica debe guardarse tambien en el detalle.
- Los nombres finales deben adaptarse a las convenciones actuales del proyecto.

## Roadmap Por Fases

### Fase 1: Analisis Del Flujo Existente

Objetivo: entender como se implemento la orden de compra y como se cargan los conceptos desde Excel.

Pasos:

- [ ] Identificar modelo/tablas usados para conceptos importados.
- [ ] Identificar modelo/tablas usados para ordenes de compra.
- [ ] Revisar controlador de detalles de obra civil.
- [ ] Revisar vista `/obra_civil/{id}/detalles`.
- [ ] Identificar funciones JS del modal de orden de compra.
- [ ] Confirmar de donde salen cantidad, unidad y precio unitario.

Checkpoint:

- [ ] Tenemos claro que codigo se puede reutilizar y que codigo debe quedar separado.

### Fase 2: Diseno Tecnico Minimo

Objetivo: definir estructura sin implementar aun toda la generacion avanzada.

Pasos:

- [ ] Definir migracion de `estimaciones`.
- [ ] Definir migracion de `estimacion_detalles`.
- [ ] Definir relaciones Eloquent.
- [ ] Definir nombres de rutas.
- [ ] Definir permisos o restricciones, si aplica.
- [ ] Definir reglas de estado.

Checkpoint:

- [ ] El flujo se puede explicar de punta a punta antes de escribir la implementacion.

### Fase 3: Persistencia

Objetivo: crear la base de datos y modelos.

Pasos:

- [ ] Crear migracion `estimaciones`.
- [ ] Crear migracion `estimacion_detalles`.
- [ ] Crear modelo `Estimacion`.
- [ ] Crear modelo `EstimacionDetalle`.
- [ ] Agregar relaciones con obra civil.
- [ ] Agregar metodos de calculo de totales/acumulados si conviene.

Checkpoint:

- [ ] Se puede crear una estimacion con detalle desde backend o prueba manual.

### Fase 4: Crear Estimacion Desde La Vista

Objetivo: agregar el boton y modal para capturar cantidades.

Pasos:

- [ ] Agregar boton `Generar estimacion` en la vista de detalles.
- [ ] Construir modal con conceptos importados.
- [ ] Agregar inputs de cantidad a estimar.
- [ ] Calcular importes en frontend.
- [ ] Validar cantidades en frontend.
- [ ] Enviar datos al backend.
- [ ] Validar cantidades nuevamente en backend.
- [ ] Guardar estimacion en estado `creada`.

Checkpoint:

- [ ] El usuario puede generar una estimacion real desde los conceptos del Excel.

### Fase 5: Lista De Estimaciones

Objetivo: mostrar lo creado y permitir seguimiento.

Pasos:

- [ ] Agregar seccion/lista de estimaciones en detalles de obra.
- [ ] Mostrar folio, fecha, estado, total y numero de conceptos.
- [ ] Agregar vista o modal de detalle de estimacion.
- [ ] Agregar accion editar para estado `creada`.
- [ ] Bloquear edicion cuando la estimacion cambie de estado.

Checkpoint:

- [ ] El usuario puede consultar las estimaciones creadas sin entrar a base de datos.

### Fase 6: Estados Y Acciones

Objetivo: operar el ciclo basico de la estimacion.

Pasos:

- [ ] Implementar accion `Autorizar`.
- [ ] Implementar accion `Marcar como facturada`.
- [ ] Implementar accion `Marcar como pagada`.
- [ ] Implementar accion `Cancelar`.
- [ ] Registrar usuario y fecha cuando aplique.
- [ ] Evitar transiciones invalidas.

Checkpoint:

- [ ] El estado de una estimacion puede avanzar de forma controlada.

### Fase 7: Resumen En Header

Objetivo: dar visibilidad rapida del avance economico.

Pasos:

- [ ] Calcular numero de estimaciones activas.
- [ ] Calcular total estimado.
- [ ] Calcular total autorizado.
- [ ] Calcular total facturado.
- [ ] Calcular total pagado.
- [ ] Mostrar indicadores en el header de detalles de obra.
- [ ] Excluir estimaciones canceladas de totales activos.

Checkpoint:

- [ ] Desde el header se entiende cuanto se ha estimado y en que estado financiero va.

### Fase 8: Pruebas Y Ajustes

Objetivo: confirmar que el flujo sea confiable.

Pasos:

- [ ] Probar crear estimacion con una partida.
- [ ] Probar crear estimacion con varias partidas.
- [ ] Probar impedir cantidad mayor a disponible.
- [ ] Probar acumulados cuando una partida aparece en varias estimaciones.
- [ ] Probar cancelacion y exclusion de totales.
- [ ] Probar autorizacion y bloqueo de edicion.
- [ ] Probar facturada y pagada.
- [ ] Revisar que orden de compra no se haya roto.

Checkpoint:

- [ ] El MVP esta listo para uso interno.

## Evolucion Posterior: Generador

Cuando el flujo basico ya funcione, se puede evolucionar hacia un generador formal de estimaciones.

Ideas futuras:

- Periodo de estimacion.
- Numero de estimacion por contrato.
- Generadores por concepto.
- Croquis o evidencia.
- Fotografias.
- Firmas.
- PDF imprimible.
- Caratula de estimacion.
- Retenciones.
- Amortizaciones.
- IVA.
- Factura relacionada.
- Comprobante de pago.
- Historial de cambios.
- Observaciones por autorizacion o rechazo.

## Riesgos A Cuidar

- Duplicar demasiada logica de ordenes de compra sin separarla conceptualmente.
- Permitir estimar mas cantidad de la disponible.
- Que cambios al Excel afecten estimaciones ya creadas.
- Que estimaciones canceladas sigan sumando en el header.
- Que una estimacion autorizada pueda modificarse sin control.
- Mezclar estados financieros reales con simples marcas manuales sin dejarlo claro.

## Decisiones Pendientes

- [ ] Nombre visible final: `Estimacion`, `Estimacion de obra`, `Generador de estimacion`.
- [ ] Si el folio sera global o consecutivo por obra.
- [ ] Si se requiere fecha o periodo desde el MVP.
- [ ] Si se permitira cancelar una estimacion facturada.
- [ ] Si se usara IVA desde el primer paso.
- [ ] Si se mostrara porcentaje estimado contra total contratado.
- [ ] Si existira una vista individual para cada estimacion o solo modal.

## Bitacora De Checkpoints

Usar esta seccion para registrar avances reales.

| Fecha | Checkpoint | Estado | Notas |
| --- | --- | --- | --- |
| 2026-08-14 | Roadmap inicial creado | Completado | Se definio el concepto, estados, fases y reglas base. |

