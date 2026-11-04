# Definición de la creación de comisiones por etapas

Fecha de revisión: 2026-11-04.

Estado: definición de la tarea 1, servicio de la tarea 2, datos generales de la
tarea 3, perforación inicial de la tarea 4, personal/fotos de la tarea 5 y
guardado operativo completo de la tarea 6.
Los puntos de compatibilidad con importes y nómina siguen pendientes.

## Preparación implementada en la tarea 2

- `ComisionEtapasService::createRecord` crea la cabecera y las cinco etapas en
  una transacción y devuelve el modelo. Se puede componer con una transacción
  exterior para incorporar evidencias en una tarea posterior.
- `ComisionEtapasService::create` conserva la respuesta de detalle que utiliza
  la app y delega la escritura a ese mismo creador. No se cambiaron sus rutas
  ni el contrato de cantidades adelantadas de la captura móvil.
- `ComisionesWebService::createForUser` es la entrada preparada para el alta
  web: exige `obras.edit`, alcance de obra, pila activa de la obra, máquina con
  asignación activa sin fecha de fin y residente activo con empleado disponible.
- La entrada web determina cantidad 1, estado inicial, residente, tarifario y
  autoría en servidor. Solo admite la producción de Perforación y opciones de
  Bentonita/Ademe; cantidades de etapas posteriores y metadatos financieros
  arbitrarios no se trasladan desde el cliente.
- La máquina queda vinculada al detalle de producción. La selección inicial de
  personal se valida por obra y disponibilidad y se asocia solo a Perforación.
- Las notificaciones se ejecutan después del commit exterior; un rollback no
  publica una notificación de un trabajo inexistente.
- Se probaron escritura real con SQLite en memoria, estados y avance, referencias
  ajenas/inactivas, permisos, rollback, notificaciones y respuesta del creador móvil.

El formulario nuevo utiliza `ComisionEtapaController::crear` y el POST
`obras.comisiones.store-etapas`. Conserva separado el antiguo
`ComisionController::store` para no modificar su contrato financiero. El alta por
etapas incorpora fotos y no genera proyecciones hacia `comision_personal` ni
recibos de nómina, siguiendo el comportamiento de captura de la app.

## Datos generales implementados en la tarea 3

`ComisionController::create` abre `create-etapas.blade.php` con los datos preparados
por `ComisionesWebService::datosGeneralesForUser`. La entrada comprueba permiso
`obras.edit` y alcance de obra; las obras terminadas/canceladas no admiten altas.

El formulario muestra fecha, una pila activa, máquina asignada, residente de solo
lectura, número de formato, cliente y observaciones generales. Las máquinas se
limitan a asignaciones activas de la obra, sin fecha de fin y con equipo disponible.
Se preselecciona la perforadora cuando existe; si solo hay una pila, se preselecciona.
El residente utiliza el mismo resolvedor que el creador preparado en la tarea 2.
El formulario no envía un `residente_id` editable.

La vista explica cuando faltan pilas, máquinas o residente y deshabilita el guardado
en ese caso. Los datos recuperados mediante `old()` se muestran solo en las opciones
de los catálogos válidos. El navegador valida los campos antes del envío y el
servidor vuelve a validar referencias, permisos y archivos.

La vista antigua se conserva en el proyecto, pero la ruta de creación muestra la
nueva pantalla por etapas. El endpoint anterior se conserva y el formulario nuevo
envía al guardado por etapas agregado en la tarea 6.

## Perforación inicial implementada en la tarea 4

La captura incluye diámetro, profundidad, metros de comisión, cantidad fija 1,
inicio, fin opcional y observaciones específicas de Perforación. Las opciones
explícitas de Bentonita y Ademe indican si aplican etapas posteriores; no las
completan ni capturan sus volúmenes por adelantado. Campana y adicional se conservan
en el bloque desplegable de otros datos.

El componente Alpine `comisionAltaFormulario` carga diámetro y profundidad de la
pila seleccionada y propone los metros de comisión con el criterio inicial de la
app (profundidad de la pila), permitiendo editarlos. Al inicializar respeta valores
ya capturados recuperados por `old()`. Cambiar de pila vuelve a cargar los valores
de referencia de esa pila. No se incorpora una fórmula nueva de comisiones.

El estado mostrado es una previsión: sin inicio, sin iniciar; con inicio y sin fin,
en proceso; con inicio y fin, perforación completada. Se verificó en el navegador
que la revisión admite inicio sin fin y que cambiar los horarios actualiza el
estado sin guardar. Los nombres de campo utilizan `perforacion[...]`, compatibles
con el creador preparado en la tarea 2. Las observaciones generales y las de etapa
se mantienen separadas.

La selección de personal/fotos se describe a continuación; `Guardar comisión`
conecta toda la captura mediante el guardado de la tarea 6.

## Personal y fotografías implementados en la tarea 5

El alta muestra el personal del mismo `personalDisponible` utilizado por la
continuación: asignaciones activas, sin baja, con empleado disponible y sin el
rol Residente. Se propone la selección completa, permitiendo desmarcar a quienes
no participaron y filtrar por nombre o rol sin perder las selecciones ocultas.
La selección utiliza `perforacion[personal_ids][]` y conserva una selección vacía
al recuperar el formulario; los IDs recuperados no agregan opciones ajenas al catálogo.

Las fotografías reutilizan el componente JavaScript de continuación y el parcial
`fotos-nuevas`. Admiten selección múltiple/acumulada, vista previa, eliminación,
descarte de duplicados y validación local de JPG/PNG/WebP hasta 8 MB por foto.
La lista visible sincroniza el campo `fotos[]`; quitar una foto libera su URL
temporal. También se liberan las vistas previas al destruir el componente.
El envío deshabilita el botón para evitar un segundo clic mientras se guarda.

El formulario envía multipart al guardado por etapas. Recargar o abandonar la
página requiere volver a seleccionar las fotos.

## Guardado operativo implementado en la tarea 6

El nuevo POST exige autenticación, verificación y `obras.edit`, además del alcance
de obra validado en el servicio. Revalida la asignación de máquina y la existencia
del equipo; no basta enviar un ID que estuvo disponible al abrir el formulario.
Valida fotos reales JPG/PNG/WebP hasta 8 MB antes de escribir datos.

`createForUser` envuelve cabecera, cinco etapas, detalle, participantes y fotos de
Perforación en una transacción. Reutiliza `storeFoto` para convertir a WebP y
guardar autoría/metadatos. Si falla cualquier foto, revierte los registros y
elimina los archivos creados anteriormente. La notificación preparada por el
motor espera al commit exterior, una vez guardadas todas las fotos.

El éxito redirige al detalle de la misma comisión: sin fin, Perforación sigue en
proceso; con fin, queda completada y se muestra la siguiente etapa aplicable.
Los errores de validación o almacenamiento regresan al alta con mensajes y datos
recuperados, excluyendo archivos y token CSRF. Las fotos deben seleccionarse de
nuevo. La comprobación de persistencia usa exclusivamente SQLite en memoria y
almacenamiento de prueba; no crea trabajos reales.

El alcance es la captura operativa igualada con la app. Los puntos financieros
listados más abajo siguen pendientes antes de publicar importes automáticamente
para nómina o retirar el endpoint anterior. Este guardado no cambia recibos ni
recalcula importes históricos.

### Corrección del alta sin producción de ademe/campana

Se detectó en el log local que el alta enviaba `ml_ademe_bauer = NULL`, aunque
la migración de ademe/campana y el esquema MySQL real exigen valores no nulos
con predeterminado cero. El motor ahora usa cero cuando no hay un valor previo
ni uno capturado en esos dos campos, conservando la producción existente al
continuar. Las pruebas cargan esa migración real en lugar de columnas simuladas
que permitían NULL; así reproducen la restricción que ocasionó el fallo.

Los errores inesperados del alta muestran el tipo de fallo (base de datos,
procesamiento/almacenamiento de imágenes o interno) y una referencia corta.
El mismo identificador se registra con la excepción, obra y usuario en el log.
La pantalla no expone consultas SQL ni mensajes técnicos sin filtrar.

## Unidad del registro

- Una comisión corresponde a una pila seleccionada de una obra y un trabajo.
- El alta nueva usa una sola pila y cantidad inicial 1, como la captura móvil.
- La comisión conserva el mismo ID durante Perforación, Bentonita, Ademe,
  Colocación de acero y Colado. Continuar no crea otra comisión.
- Los registros históricos con varios detalles permanecen consultables; no se
  dividen ni convierten automáticamente al abrirlos.
- Se reutilizan las tablas existentes. No se necesita otra cabecera ni una tabla
  puente para conectar creación y seguimiento.

## Datos generales y su origen

| Dato | Origen y regla del alta web |
| --- | --- |
| Obra | Obra de la ruta, autorizada mediante el alcance web existente. |
| Fecha | Capturada por el usuario; requerida y válida. |
| Pila | Una pila activa perteneciente a la obra; la existencia global no basta. |
| Máquina | Asignación activa a la misma obra; conservar `obra_maquina_id` en el detalle. |
| Residente | Asignación de residente de la obra resuelta por el servidor; no sustituirla por el usuario que captura en oficina. |
| Número de formato | Opcional, máximo 50 caracteres; no usarlo como identidad del trabajo. |
| Cliente en formato | Nombre de la obra como valor inicial, con la edición que ya permite el formulario web. |
| Trabajo | Resolver/validar contra el catálogo existente; conservar `trabajo_id` para identificar la tarifa correcta. |
| Tarifario | Resolver en servidor usando el criterio existente: vigente más reciente o último disponible. Conservar `tarifario_id` en la comisión. |
| Autoría | `created_by` y `updated_by` son el usuario autenticado, independientemente del residente. |

Si falta una asignación necesaria, el alta debe explicarlo y no inventar IDs ni
aceptar referencias de otra obra. No se exige una habilitación móvil activa al
usuario de oficina.

## Perforación inicial

| Dato | Destino / comportamiento |
| --- | --- |
| Diámetro | `comision_detalles.diametro`; valor inicial de la pila, editable. |
| Profundidad | `comision_detalles.profundidad`; valor inicial de la pila, editable. |
| Cantidad | 1 en la nueva captura por pila. |
| Metros comisión | Entrada `metros_comision`, persistida como `metros_sujetos_comision`. No confundir con profundidad. |
| Inicio | `perforacion.hora_inicio`; requerido para iniciar el trabajo. |
| Fin | `perforacion.hora_fin`; opcional al crear, necesario para completar la perforación. |
| Observaciones | Separar las generales de las observaciones de perforación; ambas opcionales. |
| Personal | Participantes de esa perforación, referidos por `obra_empleado_id`; no son participantes automáticos de las etapas siguientes. |
| Fotos | Evidencias asociadas a la etapa Perforación, con autoría y metadatos. |
| Campana / adicional | Conservar la información existente de producción; no crear una sexta etapa ni inventar unidades o tarifas para `adicional`. |

Las cantidades son no negativas, con la precisión/capacidad de los campos
existentes; cantidad y piezas de campana son enteros. Los horarios mantienen la
semántica actual, sin inferir horas extra del intervalo inicio/fin.

Acero y concreto se capturan en su etapa. En el alta web, Bentonita y Ademe se
indican como aplicables mediante opciones explícitas; su producción se registra
después. La app actual también envía volúmenes adelantados y deriva esas opciones
de cantidades mayores a cero: se conserva la lectura de ese contrato para no
romperla, pero una cantidad adelantada nunca completa la etapa por sí sola.

## Estados y recorrido

| Situación al guardar | Estado de comisión | Estado de Perforación | Siguiente paso |
| --- | --- | --- | --- |
| Inicio sin fin | `pendiente` | `en_proceso` | Continuar Perforación. |
| Inicio y fin válidos | `pendiente` | `completada` | Primera etapa aplicable pendiente. |
| Todas las etapas aplicables terminadas | `cerrada` | Conserva su estado | Sin siguiente paso. |

Siempre se crean las cinco etapas, en este orden:

1. Perforación: obligatoria.
2. Bentonita: pendiente si aplica; `no_aplica` si no aplica.
3. Ademe: pendiente si aplica; `no_aplica` si no aplica.
4. Colocación de acero: obligatoria, pendiente.
5. Colado: obligatorio, pendiente.

El avance considera únicamente etapas aplicables. Con Perforación completa y
sin Bentonita ni Ademe, el resultado es 1/3 = 33 %. Con las cinco aplicables,
es 1/5 = 20 %. Una perforación en proceso no cuenta como completada.

La nueva creación debe asignar `pendiente` explícitamente: el valor predeterminado
`cerrada` de la migración de comisiones no sirve para este recorrido.

## Personal y evidencias

- Usar asignaciones activas, sin fecha de baja y pertenecientes a la obra.
- Excluir al residente de la selección de participantes, siguiendo el criterio
  móvil/web ya utilizado; su identificación como responsable se mantiene aparte.
- El servidor determina empleado, rol y autoría. No acepta importes o roles
  arbitrarios enviados por el navegador.
- Las evidencias admiten JPG, PNG y WebP, hasta 8 MB por foto, con conversión y
  almacenamiento mediante el servicio existente.
- Guardar cabecera, etapas, producción, participantes y nuevas fotos como una
  unidad; limpiar archivos nuevos ante una falla y notificar después del commit.
- Reutilizar en el alta los componentes del formulario de continuación ya creados.

## Cálculos que deben conservarse

### Lo observado en el código actual

- `ComisionController::store` crea `comision_personal`, sus actividades y detalles,
  y persiste `importe_comision` usando producción por actividad y tarifa del rol.
- El detalle web calcula también importes por participantes de cada etapa,
  usando cantidad de producción por tarifa; esos valores visuales no equivalen
  a una actualización de los importes persistidos.
- `ComisionEtapasService::create` crea participantes de etapa con importe inicial
  cero si no se proporciona otro; no reproduce el recálculo del alta antigua.
- Nómina consulta `comision_personal` y su `importe_comision`, no únicamente
  `comision_etapa_personal`. La consulta revisada no filtra por estado de etapa
  ni por `comisiones.estado`.

### Contrato de compatibilidad

1. Conservar la fórmula de producción por participante comisionable:
   cantidad de la actividad por tarifa del rol, trabajo y tarifario de la comisión.
2. Conservar metros, acero, bentonita, concreto, ademe y campana, sin asumir que
   todas las actividades son pagables ni que todas tienen tarifa configurada.
3. No multiplicar por el número de participantes ni repartir automáticamente
   entre ellos: cada participante conserva su cálculo individual.
4. No volver a sumar producción por cada renglón si ya se utiliza un total
   agregado de la comisión. No sumar simultáneamente la representación antigua
   y la representación por etapas de la misma participación.
5. Mantener el concepto de horas extra separado de producción. No calcularlo a
   partir de horarios operativos; conservar las cantidades, tarifas y montos
   anteriores cuando ya existan.
6. Conservar el tarifario vinculado y el rol de la participación histórica;
   no sustituirlos por las asignaciones actuales al continuar.
7. Mantener las referencias existentes de nómina y los registros ya pagados.
   No reescribir ni publicar nuevos importes en recibos existentes desde el alta.

### Diferencias a resolver antes de habilitar el nuevo guardado de creación

- La selección de tarifas debe considerar el trabajo: la clave única del
  tarifario incluye `trabajo_id`, mientras varias consultas antiguas indexan solo
  por rol/variable. No aplicar por accidente una tarifa de otro trabajo.
- Un auxiliar antiguo usa `hora_extra` como variable, pero el alta y la consulta
  de nómina usan `tiempo_extra`. Verificar los datos reales y mantener esa
  compatibilidad; no reemplazar claves sin comprobar su uso.
- El modelo de detalle del tarifario contiene nombres como `precio_unitario` y
  `estado`, mientras la migración y los cálculos revisados usan `tarifa` y
  `activo`. Esa diferencia debe resolverse contra el esquema operativo antes de
  persistir nuevos cálculos, sin asumir que ambos nombres son equivalentes.
- Definir la proyección de las participaciones por etapas hacia los registros
  que consume nómina, reutilizando `comision_personal` y sus IDs donde corresponda.
  El importe mostrado en la página no resuelve por sí solo esta compatibilidad.
- El momento de disponibilidad para nómina (por etapa terminada o al cerrar
  todo el trabajo) es una decisión operativa pendiente. No se cambia esa política
  ni se publica un importe de un trabajo en proceso de forma implícita en esta tarea.

Estos puntos deben quedar resueltos y probados antes de sustituir el guardado
anterior. La captura y los estados pueden prepararse sin tocar los recibos.

## Contrato de entrada para las siguientes tareas

La entrada web separa datos generales, opciones y `perforacion`. El servidor
adapta la selección de personal y archivos al contrato compartido existente:

```text
fecha, pila_id, obra_maquina_id, numero_formato, cliente_nombre,
observaciones, trabajo_id
opcionales: { bentonita, ademe }
perforacion: {
  diametro, profundidad, cantidad: 1, metros_comision,
  hora_inicio, hora_fin?, observaciones?, campana_pzas?, adicional?,
  personal_ids: [ IDs de asignación de la obra ]
}
fotos: [ archivos de Perforación ]
```

Obra, estado inicial, residente, tarifario, rol de cada participante y autoría se
resuelven/validan en servidor. La entrada no sustituye todavía ninguna ruta ni
cambia el contrato móvil. No requiere migraciones ni transformar datos históricos.

## Resultado esperado y comprobaciones de aceptación

- Crear sin fin deja disponible `Continuar Perforación` en web y app.
- Crear con fin muestra avance y la primera etapa aplicable pendiente.
- Activar opcionales no registra su producción ni los marca como completados.
- Una captura nueva no nace cerrada ni sin sus cinco etapas.
- Los participantes y fotos de Perforación aparecen en su etapa, no en todas.
- Pila, máquina y participantes de otra obra son rechazados.
- Un fallo al crear o guardar evidencias no deja comisiones parciales.
- El cuadro de importes, la persistencia y la nómina deben conciliar con un
  ejemplo controlado por actividad/rol antes de reemplazar el alta anterior.

## Fuentes revisadas

- `app/Http/Controllers/ComisionController.php`: create, store, show, print y recalcularImportes.
- `app/Services/Comisiones/ComisionEtapasService.php`: create, guardarEtapa y producción por etapa.
- `app/Services/Comisiones/ComisionesWebService.php`: autorización y captura web.
- `app/Http/Controllers/Api/V1/ResidenteComisionController.php`: contrato móvil.
- `app/Http/Controllers/Nomina/NominaCorridaController.php`: consulta y trazabilidad de comisiones.
- `app/Models/ComisionTarifarioDetalle.php` y migraciones de tarifarios/etapas.
- `resources/views/obras/comisiones/create.blade.php` y `app/src/app/tab3/` en el proyecto Ionic hermano.
