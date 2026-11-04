# Comisiones por etapas: logica compartida

## Confirmación de guardado en web

El alta, la captura de las cinco etapas y la activación de opcionales muestran
el indicador global de carga desde el manejador de envío compartido, con el
nombre de la operación en curso y bloqueo de envíos repetidos. El alta ya no
desactiva el indicador mediante `data-no-loading`. Al volver con el historial
del navegador se restablece el estado del botón.

Los guardados exitosos y activaciones redirigen al ancla `resultado-guardado`
del detalle. Ahí se muestra un aviso persistente al inicio de la página con el
nombre de la etapa y su resultado; Colado confirma además el cierre cuando
corresponde. El aviso no depende del toast temporal del layout.

## Edición y borrado con permisos puntuales

Cada sección usa tres permisos independientes con guard `web`: editar, borrar
captura y borrar fotos. La revisión local encontró siete de los nueve permisos;
se crearon únicamente `borrar_fotos_perforacion.access` y `borrar_fotos_acero.access`.
Se verificó que los nueve existen y se limpió la caché de permisos. No se asignaron
permisos ni se agregaron migraciones o semillas.

| Permiso | Operación web |
| --- | --- |
| `editar_perforacion.access` | Editar horarios, observaciones, producción y participantes de Perforación. |
| `editar_acero.access` | Editar horarios, observaciones, kilos y participantes de Colocación de acero. |
| `editar_colado.access` | Editar horarios, observaciones, concreto y participantes de Colado. |
| `borrar_perforacion.access` | Borrar la captura de Perforación y dejarla pendiente. |
| `borrar_acero.access` | Borrar la captura de Colocación de acero y dejarla pendiente. |
| `borrar_colado.access` | Borrar la captura de Colado y dejarla pendiente. |
| `borrar_fotos_perforacion.access` | Borrar individualmente una foto de Perforación. |
| `borrar_fotos_acero.access` | Borrar individualmente una foto de Colocación de acero. |
| `borrar_fotos_colado.access` | Borrar individualmente una foto de Colado. |

El permiso general anterior `borrar_foto_comision.acces` ya no autoriza el borrado
de fotografías en estas secciones. No se eliminó del catálogo ni se modificaron
sus asignaciones existentes. No hay equivalencias ni fallback entre permisos.

Las rutas comprueban permiso, el servicio verifica el permiso específico de la
etapa y el alcance de la obra, y Blade envuelve los controles en `@can`. El permiso
de Acero no permite modificar Perforación; tener `obras.edit` no concede estas
operaciones. Se valida que comisión, etapa y foto pertenezcan entre sí. El permiso
puntual no requiere adicionalmente `obras.edit`, pero sí el alcance de obra habitual.

Editar conserva estado, avance y fecha de completado, incluso en un
trabajo cerrado; no cambia importes ni recibos históricos. Para finalizar una etapa
en proceso se utiliza la captura ordinaria, no la corrección. El personal histórico
fuera de la selección activa y los metadatos de participantes conservados permanecen
intactos.

La edición de Perforación, Acero y Colado muestra sus fotos existentes y permite
agregar JPG/PNG/WebP hasta 8 MB por archivo, con selección múltiple, vista previa
y descarte de fotos nuevas antes de enviar. Las existentes se conservan si no se
marcan. `Quitar al guardar` envía sus IDs en `fotos_borrar[]` y solo aparece con
`borrar_fotos_<etapa>.access`; el servidor exige ese mismo permiso y comprueba
que cada ID pertenezca a la etapa de esa comisión. Agregar fotos requiere el
permiso de edición de la etapa, sin exigir permiso de borrado.

El mismo guardado confirma datos, personal, fotos nuevas y eliminación de las
marcadas en una transacción. Si falla, restaura las fotos retiradas y elimina las
nuevas que alcanzaron a guardarse. Una validación fallida conserva la selección
de fotos existentes para quitar; los archivos nuevos deben seleccionarse otra vez.

Borrar captura requiere confirmación y elimina horarios, observaciones, producción
de esa etapa, participantes y fotos; conserva la fila de etapa y su orden. Perforación
reinicia metros, campana y adicional; conserva pila, máquina, diámetro, profundidad
y cantidad como referencias del trabajo. Acero reinicia únicamente kilos y Colado
reinicia únicamente el volumen de concreto. Al borrar Colado de un trabajo cerrado,
este vuelve a pendiente, se limpia `cerrada_at` y Colado queda como siguiente paso;
las etapas previas y su producción se conservan. La etapa
queda pendiente y se actualiza el avance usando el motor compartido. Se bloquea
el borrado si una etapa posterior está en proceso o completada. La eliminación de
sus fotos forma parte de borrar captura; el permiso de fotos se exige al borrado
individual de esa sección. No se modifican otras etapas ni registros de nómina.

Los archivos se retiran temporalmente de su URL durante la transacción. Una falla
de base de datos restaura sus rutas; al confirmar se limpian los temporales. Si falla
esa limpieza final se registra para diagnóstico. El borrado individual respeta el
disco indicado por la foto y conserva el estado de la etapa.

La igualación de la **creación** web se define por separado en
[comisiones_creacion_por_etapas.md](comisiones_creacion_por_etapas.md).
Ese documento corresponde a la tarea 1 de creación y no modifica aún el alta existente.

## Separacion inicial

- `ComisionEtapasService`: listado, detalle, avance, siguiente paso, orden de etapas,
  produccion, personal, fotos, notificaciones y cierre. Recibe la obra explicitamente;
  quien lo invoque debe autorizar primero al usuario.
- `ResidenteComisionesService`: entrada movil con las firmas y respuestas existentes.
  Conserva la comprobacion de `UsuarioApp` activo, contexto residente y obra seleccionada.
- `ComisionesWebService`: entrada preparada para la captura web por etapas. Exige
  `obras.edit` mediante `can()` (incluye denegaciones explicitas), y el alcance de obra
  que utiliza `ObraController`: administradores, residente responsable/asignado o
  personal de la misma area. No exige habilitacion movil activa.
- `ComisionEtapaRules::rules($prefix)`: validaciones existentes de entrada, reutilizables
  por los controladores. El controlador movil ya las utiliza.

El servicio compartido verifica que la comision pertenezca a la obra recibida antes
de leer su detalle, modificar etapas o adjuntar fotos. No se agregan tablas,
permisos, rutas ni controles de pantalla en esta primera tarea.

## Siguientes tareas

El detalle web ya muestra avance y siguiente paso mediante `ComisionEtapasService`.
La consulta conserva los permisos existentes del detalle y no exige permisos de
captura. El bloque no se muestra en registros antiguos sin etapas; una comision
cancelada no presenta un siguiente paso.

## Acciones de seguimiento

La vista muestra las acciones autorizadas por el servicio compartido. `Registrar`
y `Continuar` seleccionan la etapa mediante `?etapa=...` sin modificar datos;
el servidor verifica permiso, obra y disponibilidad de la accion. La seleccion
abre el formulario de captura en el detalle.

`Activar` utiliza POST en `obras.comisiones.etapas.activar`, con CSRF, permiso
`obras.edit` y verificacion del alcance de la obra. Solo activa una etapa opcional
con accion `activar`: la deja pendiente y habilita su requisito de fotografia.
Las comisiones cerradas/canceladas y las etapas bloqueadas no admiten esta accion.
Los lectores sin permiso de captura conservan el detalle sin acciones.

## Formulario de captura

La etapa seleccionada abre el formulario con inicio, fin, observaciones y el campo
de produccion definido en el servicio comun. Los valores existentes se precargan
al continuar. `produccionParaEtapa` comparte la misma configuracion y cantidades
que la respuesta detallada de la app, sin duplicar los tipos de produccion.

`Guardar y completar etapa` envia el formulario multipart a
`obras.comisiones.etapas.guardar`, protegido por sesion, CSRF y `obras.edit`.
Laravel valida horarios obligatorios, produccion de la etapa con hasta dos
decimales, cantidades no negativas, concreto mayor a cero y archivos de imagen
JPG/PNG/WebP de hasta 8 MB. Solo se admite el campo de produccion correspondiente
a la etapa: estado, importes y otros campos enviados por el cliente se ignoran.
Los errores regresan al formulario con los valores capturados y la seleccion de
personal, incluso si se desmarcaron todos. Los archivos deben seleccionarse de nuevo.

## Personal y fotografias

El formulario ofrece las asignaciones activas de la obra, sin fecha de baja y
con empleado disponible. Como en Ionic, excluye el rol `residente` de la seleccion.
Si ya hay participantes registrados, precarga esa seleccion; si no los hay,
selecciona inicialmente a todo el personal disponible. Los participantes antiguos
fuera de las asignaciones disponibles se muestran por separado como historial.
El guardado conserva esos registros historicos, asi como los importes, actividad,
rol y notas de los participantes anteriores que se mantienen seleccionados.
Solo retira participantes de la seleccion activa expresamente desmarcados.

Las nuevas imagenes (`fotos[]`) se acumulan en el navegador con vista previa y
opcion de quitar. El componente Alpine sincroniza el selector nativo para que
solo contenga los archivos pendientes, sin duplicados, y libera las URL temporales.
Admite JPG, PNG y WebP de hasta 8 MB por archivo, el mismo limite de la API movil.
Los archivos existentes se muestran aparte y no tienen acciones de eliminacion.

El servidor vuelve a validar las asignaciones disponibles en la obra dentro de la
transaccion. No acepta personal ajeno, inactivo ni el rol residente como nuevas
selecciones. Las fotos previas se conservan y las nuevas se convierten a WebP
con el mismo servicio que usa la app.

## Guardado y cierre

`ComisionesWebService` autoriza y valida la entrada usando las reglas comunes;
`ComisionEtapasService::completarEtapa` bloquea la comision y relee sus etapas antes
de guardar. Verifica de nuevo que no este cerrada/cancelada y que el orden permita
registrar o continuar la etapa. Guarda horarios, produccion, personal y fotos en
una transaccion; una falla revierte los datos y limpia los archivos nuevos.
Las notificaciones se procesan despues de confirmar la transaccion.

Si hay varios detalles de produccion, ajusta el primero por la diferencia del
total capturado y conserva los restantes. Rechaza un total inferior a la cantidad
ya registrada en esos otros renglones para evitar cantidades negativas.

La etapa queda completada y la comision se cierra cuando todas las etapas
aplicables estan completas. El detalle muestra el avance y siguiente paso
actualizados. Un reenvio de una etapa ya completada no duplica personal ni fotos.
El formulario evita el doble clic y la captura movil conserva sus rutas actuales.

Queda la comprobacion manual de continuidad entre app y web del paso 7.

## Verificacion

Pruebas unitarias e integracion HTTP, con SQLite en memoria y almacenamiento falso.
No ejecutan migraciones ni escrituras sobre la base local de operacion:

```text
php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --do-not-cache-result --filter "ComisionEtapaWebGuardadoTest|ComisionEtapasServiceTest|ComisionesWebServiceTest|AppMobileContextAccessTest"
```
