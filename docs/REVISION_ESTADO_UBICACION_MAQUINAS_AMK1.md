# Revisión de estado, ubicación y cambios del residente: AMK1

Fecha: 2026-10-29. Inspección de código y consultas de solo lectura en la base local. Sin cambios de máquinas, asignaciones, permisos o reglas; sin ejecución de pruebas funcionales.

## Resultado de AMK1

- Máquina 17, código AMK1: `estado=fuera_servicio`, `ubicacion=en_obra`.
- Asignación 4, obra 4 AMPL.SIEMENS-2 (HYA), inicio 2026-03-02, estado activa y fecha de fin nula.
- La obra tiene `estatus_nuevo=4`, que corresponde a TERMINADA. Por tanto hay una asignación abierta que ya no es vigente según la relación `Maquina::asignacionActiva()`. No debe confundirse con una máquina realmente libre/sin historial pendiente.
- Movimiento 10: asignación, patio a obra, registrado 2026-03-13, autor Hector Barreto Vargas.
- Movimiento 48: operativa a fuera de servicio, registrado `2026-09-24T03:14:17Z` en la serialización, autor JUAN DIEGO MARTINEZ (usuario 10). No cambió ubicación; no conserva motivo, notas ni vínculo de obra/asignación.
- El movimiento 48 antecede a los cambios T15/D09 de octubre. No corresponde a un cambio creado por esta implementación.
- No puede demostrarse el canal exacto del movimiento 48: el historial de cambios de estado no conserva origen web/app. La API actual exige motivo y envía vínculo de asignación; los campos nulos no coinciden con ese payload actual. No atribuirlo al residente ni a la web sin evidencia adicional del código/log histórico.

En el catálogo local hay 22 máquinas: 2 fuera de servicio/en obra (AMK1 y BG-12), 9 fuera de servicio/en patio, 1 en reparación/en patio y 10 operativas (4 en obra y 6 en patio). Son combinaciones observadas; no se declaran todas inválidas por la sola combinación de estado/ubicación.

## Reglas existentes y separación D09

Estado operativo responde si la máquina puede trabajar. Ubicación indica dónde está. Una máquina puede averiarse y seguir físicamente en una obra; reportar falla no demuestra un traslado al taller/patio. D09 aprobado conserva esta independencia y la asignación al cambiar a camino/reparación.

`MaquinaService::cambiarEstado()` valida únicamente la transición de estado; actualiza `estado`, registra el cambio y emite notificación. No modifica ubicación ni finaliza asignación. Permite operativa a fuera de servicio/baja; fuera de servicio a reparación/operativa/baja; reparación a operativa/baja. La baja es terminal para este flujo.

`MaquinaUbicacionService` coordina cambios físicos según D09, sin alterar estado operativo. Bloquea asignaciones abiertas a obras terminadas/canceladas y múltiples asignaciones activas. Por tanto AMK1 requiere regularización de la asignación antes de ese cambio directo; no se corrige automáticamente en este barrido.

## Flujo residente confirmado en fuente

Pantalla `C:/xampp/htdocs/rivera/app/src/app/pages/maquina-registro/maquina-registro.page.html`:

- Operativa: Reportar Falla y Registro Horas.
- Fuera de servicio: Iniciar Reparación.
- En reparación: Finalizar Reparación.

El componente usa `MaquinaService.postCambiarEstado()` para reportar falla y cambiar estado. Ese cliente invoca POST `/api/v1/maquinas/{obraMaquina}/actualizar-estado`; el identificador es de asignación, no de máquina.

Existe también POST `/api/v1/maquinas/{obraMaquina}/reportar-falla`, pero la pantalla examinada no lo usa para su botón actual de falla.

## Hallazgos del barrido

| Hallazgo | Evidencia y efecto |
|---|---|
| Autorización de obra incompleta en actualizar estado | `MaquinaRegistroController::actualizarEstado()` valida estado/motivo/notas y llama al servicio, sin `usuarioPuedeAccederObra()`. El middleware legacy comprueba usuario móvil activo y acceso residente/gerencial, pero no que la asignación solicitada corresponda a una obra autorizada. Conocer otro ID no debe bastar para modificarlo. |
| Asignación vigente no validada en cambios del residente | Actualizar estado y reportar falla no comprueban asignación finalizada/fecha de fin/obra terminada antes de cambiar la máquina. Reportar falla sí comprueba acceso a la obra del usuario; actualizar estado no replica esa comprobación. |
| Auditoría insuficiente para determinar canal | Cambio de estado conserva usuario pero no origen web/app. El movimiento histórico de AMK1 carece además de contexto y motivo. No reconstruirlos como hechos confirmados. |
| Coordinación de cambios de estado anterior a T15 | `cambiarEstado()` usa la instancia recibida, sin relectura/bloqueo de máquina ni recuperación exacta del envío. Puede validar sobre una referencia obsoleta; no comparte aún las garantías nuevas de horas/asignación/ubicación. |
| Controles web distintos | `MaquinaController::cambiarEstado()` y `toggleServicio()` permiten motivo opcional y no declaran comprobación específica de permiso en esos métodos/rutas puntuales. No equipararlos al nuevo permiso de ubicación. Revisar autorización de acceso web y permiso de acción antes de ampliar el flujo. |
| Error web ocultado | El catch de `MaquinaController::cambiarEstado()` no devuelve el error al usuario. Una transición rechazada puede terminar sin respuesta útil. |
| Interfaz de horas no equivale a regla del servidor | La pantalla residente muestra Registro Horas solo para operativa; `MaquinaHorometroService::crearRegistro()` no exige estado operativo actual, valida asignación/secuencia. Definir cómo registrar horas anteriores a una falla antes de imponer un bloqueo general. |
| Consulta de asignación desigual | El listado móvil de máquinas recoge asignaciones abiertas por estado/fecha, incluso de obra terminada; detalle y opciones usan relación de asignación vigente, que excluye terminadas/canceladas. AMK1 puede aparecer asignada en una pantalla y sin asignación vigente en otra. |

EmpresaConfigMaquinaController y updateGeneral no aceptan estado/ubicación en sus campos validados de actualización; el listener del evento de estado envía notificación y no traslada la máquina. No se encontró ahí la causa de AMK1.

## Recomendación para continuar, pendiente de autorización

1. Unificar autorización por obra y vigencia de asignación en los cambios del residente; revisar las acciones de iniciar/finalizar reparación que hoy tiene disponibles. No quitar funciones al residente por inferencia.
2. Coordinar cambio de estado mediante reglas compartidas, bloqueo/relectura, motivo obligatorio, autor/origen y errores visibles. Mantener ubicación física independiente mientras no se acuerde otra regla.
3. Regularizar AMK1 y otras asignaciones abiertas a obras terminadas después de confirmar fecha real de retiro y ubicación actual. No inferir patio ni cierre retroactivo.
4. Unificar presentación de asignación vigente frente a asignación pendiente de cierre. Conservar visibilidad del pendiente para evitar falsa disponibilidad.
5. Si el usuario quiere prohibir fuera de servicio/en obra, definir primero la ubicación alternativa real: una falla no permite inventar un traslado. Una opción de presentación es «En obra · Fuera de servicio».

El cierre documental y actualización final del grafo solicitados previamente quedan pospuestos por esta revisión dirigida por el usuario. T15.6 y T14 permanecen abiertos; las pruebas siguen a cargo del usuario.

## Ampliación: cierre de obra y envío automático al patio

Revisión solicitada posteriormente por el usuario, 2026-10-29. Sí existe liberación automática en `ObraController::update()`:

1. Captura estatus anterior y guarda los datos de la obra.
2. Solo si pasa de un estatus no cerrado a TERMINADA (4) o CANCELADA (5), libera empleados y consulta asignaciones de máquinas con estado activa y fecha de fin nula.
3. Por cada asignación llama a `MaquinaService::finalizarAsignacion()`, usando fecha de fin real de obra o, si falta, la fecha actual.
4. El servicio finaliza la asignación, cambia ubicación a patio y registra desasignación. Conserva estado operativo; fuera de servicio no se convierte automáticamente en operativa.

Planeación, ejecución y suspensión no liberan automáticamente. Guardar una obra que ya estaba terminada/cancelada o pasar entre esos dos estatus cerrados tampoco reejecuta la liberación. Reabrir no vuelve a asignar las máquinas anteriores.

### Evidencia histórica y local

- La obra 4 de AMK1 tiene `updated_at=2026-06-24T22:19:47Z`, estatus terminada y fecha de fin real nula. No hay historial de transición que confirme su fecha exacta de cierre; el dato apunta a un cierre anterior a la automatización.
- Git sitúa la incorporación del bloque de liberación en julio de 2026 (`34875d28` y `54b58c3e`). No es una reparación retroactiva de obras cerradas anteriormente.
- Hasta el cambio `accfbdc5` del 2026-09-25, el bloque finalizaba la asignación con actualización masiva, sin llamar al servicio de maquinaria ni mover físicamente el campo de ubicación a patio. Desde ese cambio llama al servicio y genera el movimiento correspondiente.
- Consulta local de solo lectura: quedan tres asignaciones activas sin fecha de fin en obras terminadas: AMK1/asignación 4/obra 4, BG-12/asignación 5/obra 5 y S3/asignación 7/obra 7. Las tres máquinas conservan ubicación en obra. No se modificaron estos registros.

### Fragilidades actuales

- La lógica está en el controlador de actualización de obra; no es un hook del modelo ni un proceso de conciliación histórica.
- Solo actúa al cruzar de estatus no cerrado a cerrado. Un pendiente previo o un cierre parcial no se corrige al guardar otra vez el mismo estatus cerrado.
- No hay una transacción externa para obra, empleados y todas las máquinas. El servicio usa una transacción por asignación: si un retiro falla después de guardar la obra (por ejemplo, fecha de cierre anterior a una lectura), pueden quedar la obra cerrada, empleados liberados y maquinaria pendiente o parcialmente liberada. El siguiente guardado ya no cruza la condición de estatus y no reintenta automáticamente.
- Las validaciones cronológicas nuevas del servicio protegen lecturas, pero hacen especialmente necesario coordinar la atomicidad del cierre de obra. No relajar las fechas para forzar un cierre ni asumir una lectura/fecha histórica que no está confirmada.

### Continuación recomendada

Conservar la regla automática para terminada/cancelada, reforzar su coordinación transaccional y recuperación de pendientes, y regularizar por separado los tres casos históricos después de acordar fecha de retiro. No cambiar el estado operativo ni alternar artificialmente una obra cerrada a ejecución para disparar el bloque. La revisión del residente sigue siendo un hallazgo independiente del cierre de obra.
