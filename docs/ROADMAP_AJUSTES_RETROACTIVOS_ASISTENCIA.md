# Roadmap: ajustes retroactivos de asistencia

## Contexto operativo

El residente genera una lista semanal adelantada, normalmente el martes, para que oficina pueda revisar, autorizar y programar el pago de los trabajadores. Esa lista no representa asistencia comprobada; representa la expectativa administrativa de quien debe considerarse para pago.

Durante la semana, la asistencia real se confirma con las capturas de campo existentes: entrada, salida, foto, fecha, ubicacion y posibles excepciones. Si despues se detecta una falta o una hora extra, el ajuste no modifica el pago ya programado, sino que se aplica en la siguiente semana.

## Objetivos

- Mantener separado el reporte adelantado de la evidencia real de campo.
- Conservar el reporte original como historico de lo que se envio a oficina.
- Detectar diferencias entre asistencia programada y asistencia real.
- Generar ajustes retroactivos para aplicarse en la siguiente semana.
- Permitir descuentos por faltas pagadas de mas.
- Permitir pagos adicionales por horas extra detectadas.
- Preparar el flujo para revision, autorizacion y aplicacion en nomina.
- Evitar que el residente tenga que resolver reglas de nomina desde campo.

## Hallazgos

- El reporte adelantado debe ser simple: empleados, dias marcados y total de asistencias programadas.
- No debe imprimir entrada, salida ni horas extra, porque al momento de generarlo aun no existe evidencia completa.
- Las horas de entrada/salida pertenecen a la evidencia real de campo, no al documento administrativo adelantado.
- Las horas extra deben calcularse retroactivamente cuando exista entrada y salida.
- La regla inicial para horas extra puede ser: si el lapso trabajado excede 8 horas, el excedente cuenta como hora extra.
- Las faltas deben compararse contra lo que ya fue programado o pagado.
- Una falta detectada despues del pago debe convertirse en descuento para la siguiente semana.
- Una excepcion autorizada debe impedir que la ausencia de foto se trate automaticamente como falta.
- El ajuste retroactivo debe ser una entidad propia, no una modificacion del reporte semanal original.

## Reglas iniciales propuestas

### Falta retroactiva

Se genera ajuste negativo cuando:

- El dia estaba marcado como asistencia en la lista adelantada.
- No existe entrada ni salida real en campo.
- No existe excepcion autorizada.
- La semana ya fue generada, revisada, autorizada o pagada.

Resultado:

- Ajuste de `-1 dia`.
- Aplicable en la siguiente semana.

### Hora extra retroactiva

Se genera ajuste positivo cuando:

- Existe entrada y salida en campo.
- El lapso entre entrada y salida supera 8 horas.

Resultado:

- Ajuste por el excedente.
- Unidad: horas o minutos.
- Aplicable en la siguiente semana.

### Excepcion

Se usa cuando no hay evidencia completa, pero existe una justificacion valida:

- Sin señal.
- Sin camara.
- Ocupacion de obra.
- Autorizada.
- Otro motivo documentado.

La excepcion debe poder quedar pendiente de revision por oficina.

## Entidad sugerida

Tabla propuesta: `nomina_ajustes_asistencia`.

Campos base:

- `id`
- `obra_id`
- `empleado_id`
- `reporte_semanal_id`
- `detalle_reporte_id`
- `semana_origen_inicio`
- `semana_origen_fin`
- `fecha_origen`
- `tipo`
- `cantidad`
- `unidad`
- `monto_estimado`
- `motivo`
- `evidencia_estado`
- `aplicar_en_semana_inicio`
- `estatus`
- `obra_asistencia_entrada_id`
- `obra_asistencia_salida_id`
- `revisado_por_user_id`
- `autorizado_por_user_id`
- `aplicado_por_user_id`
- `created_at`
- `updated_at`

Tipos sugeridos:

- `falta`
- `hora_extra`
- `excepcion_rechazada`
- `correccion_manual`

Estados sugeridos:

- `pendiente`
- `revisado`
- `autorizado`
- `aplicado`
- `cancelado`

## Pantalla propuesta

En el tab de asistencias:

1. Bloque superior: lista semanal para pago.
2. Bloque inferior: ajustes detectados de la semana anterior.

Columnas sugeridas para el bloque retroactivo:

- Empleado.
- Semana origen.
- Fecha.
- Concepto.
- Cantidad.
- Motivo.
- Evidencia.
- Aplicar en semana.
- Estado.
- Acciones.

## Checkpoints

### Checkpoint 1: definir modelo de ajustes

- Confirmar nombre de tabla.
- Confirmar tipos de ajuste.
- Confirmar estados.
- Confirmar si el ajuste guardara monto o solo cantidad/unidad.

### Checkpoint 2: detectar diferencias

- Comparar lista semanal adelantada contra asistencias reales.
- Detectar faltas sin excepcion.
- Detectar horas extra.
- Evitar duplicar ajustes ya generados.

### Checkpoint 3: vista retroactiva

- Mostrar ajustes pendientes de la semana anterior.
- Permitir revisar o cancelar ajustes.
- Mostrar evidencia relacionada.
- Mostrar excepciones pendientes.

### Checkpoint 4: autorizacion

- Definir quien puede revisar.
- Definir quien puede autorizar.
- Registrar usuario y fecha de cada cambio.

### Checkpoint 5: integracion con nomina

- Preparar enlace con corrida de nomina.
- Marcar ajustes como aplicados.
- Evitar aplicar el mismo ajuste mas de una vez.
- Dejar trazabilidad hacia el reporte semanal original.

## Preguntas pendientes

- La falta descuenta siempre 1 dia completo o puede haber medio dia.
- Las horas extra se pagan por minuto, por fraccion de hora o redondeadas.
- Las excepciones las autoriza oficina, residente o gerente.
- Que ocurre si hay entrada sin salida.
- Que ocurre si el empleado no estaba en la lista adelantada pero si asistio.
- En que momento exacto se considera que una semana ya fue pagada.
- Si el ajuste debe afectar sueldo base, viaticos, bonos u otros conceptos.

## Criterio de exito

El sistema debe permitir que la empresa siga operando con pago adelantado, pero sin perder control: lo que se pago por buena fe queda registrado, lo que realmente ocurrio en campo queda comprobado, y cualquier diferencia se convierte en un ajuste claro, revisable y aplicable en la siguiente semana.
