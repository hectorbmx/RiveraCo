# Roadmap: grabaciones de llamadas UCM6304A

Fecha de registro: 2026-07-27.

## Objetivo

Agregar soporte operativo y tecnico para grabaciones de llamadas del Grandstream UCM6304A en SIRICO, aprovechando el agente local que ya funciona para click-to-call desde produccion.

## Estado actual confirmado

- El enlace produccion -> agente local -> UCM ya funciona para llamar desde produccion.
- El UCM6304A ya muestra CDR con columna `AI Transcription`.
- En CDR, llamadas sin grabacion aparecen con:
  - `AI Transcription`: `Not Transcribed`.
  - `Recordings`: `-`.
- El File Manager del UCM muestra que los archivos de grabacion estan configurados en:
  - `Local (In Use)`.
- NAS, USB, SD Card y GDMS Cloud aparecen como no disponibles.
- En PBX General Settings existe seccion `Recording Settings`.
- Configuracion global observada:
  - `Record Prompt`: apagado.
  - `Allow External Numbers to Cancel Recording`: apagado.
  - `Merge Same Call Recordings`: encendido.
  - `Stereo Recording`: apagado.
  - `Start/Stop Recording Feature Codes`: liga a la pagina de codigos.

## Hallazgos de API

- La documentacion del UCM muestra que `updateSIPAccount` tiene parametro `auto_record`.
- `auto_record` pertenece a la configuracion SIP de la extension.
- Valores observados/documentados parcialmente:
  - `all`.
  - `external`.
- La misma tabla advierte que para actualizar presencia/configuracion se debe enviar la lista completa correspondiente; por seguridad no conviene modificar la extension por API sin leer y reenviar todos los campos requeridos.
- En SIRICO ya existe campo `recordfiles` en `phone_calls`.
- El mapper actual ya importa `recordfiles` desde el CDR:
  - `App\Services\Telephony\GrandstreamCallMapper`.
- El modelo actual ya permite guardar `recordfiles`:
  - `App\Models\PhoneCall`.

## Hallazgos de interfaz UCM

Se revisaron estos lugares sin encontrar el switch de grabacion automatica de la extension 25:

- `Extensions > Edit Extension: 25 > Media`.
- `Extensions > Edit Extension: 25 > Features`.
- `Extensions > Edit Extension: 25 > Advanced Settings`.
- `Call Features > Feature Codes`.

Pendiente revisar:

- `Extensions > Edit Extension: 25 > Basic Settings`, completo, buscando `Auto Record`.
- Listado general `Extensions > Extensions`, seleccionando extension 25 y usando accion masiva tipo `Batch Edit`, `Edit Selected`, `More` o equivalente.
- Configuracion de troncal/rutas si se decide grabar por llamadas externas en vez de por extension.

## Decisiones recomendadas

- Para primera prueba, no activar grabacion en todo el PBX.
- Probar primero con la extension personal 25.
- Preferir `auto_record = external` para grabar solo llamadas externas y evitar ruido de llamadas internas.
- Mantener `Merge Same Call Recordings` encendido.
- Mantener `Allow External Numbers to Cancel Recording` apagado.
- Dejar `Stereo Recording` apagado en la primera prueba para reducir tamano y complejidad.
- Evaluar `Stereo Recording` despues si ayuda a separar cliente/usuario para transcripcion.
- Considerar encender `Record Prompt` por cumplimiento/aviso operativo antes de uso productivo.

## Prueba manual inmediata

1. Confirmar codigo manual de grabacion en `Start/Stop Recording Feature Codes`.
2. Hacer llamada contestada desde extension 25.
3. Marcar el codigo manual de grabacion durante la llamada.
4. Terminar la llamada.
5. Revisar `CDR`.
6. Confirmar que `Recordings` ya no muestre `-`.
7. Confirmar que el CDR importado a SIRICO trae `recordfiles`.
8. Confirmar si `AI Transcription` cambia despues de existir grabacion.

## Prueba de grabacion automatica

1. Encontrar `Auto Record` para extension 25.
2. Configurar `external`.
3. Guardar.
4. Aplicar cambios en UCM.
5. Hacer llamada externa contestada.
6. Revisar CDR.
7. Confirmar archivo de grabacion.
8. Sincronizar CDR con agente local.
9. Confirmar que `phone_calls.recordfiles` queda poblado.

## Integracion tecnica SIRICO propuesta

### Fase 1: metadata de grabacion

- Mostrar indicador en listado/detalle de llamadas cuando `recordfiles` tenga valor.
- Agregar filtro `Con grabacion`.
- Agregar permiso granular:
  - `telefonia.recordings.view.access`.
- Ocultar acciones de grabacion para usuarios sin permiso.

### Fase 2: descarga por agente local

- Investigar/activar REC API del UCM solo para la IP local del agente.
- Agregar al agente metodo para descargar archivo de grabacion desde UCM.
- Validar formato esperado:
  - nombre desde `recordfiles`.
  - posible `filedir`.
  - extension `.wav`.
- Evitar reintentos agresivos si UCM responde error.
- Registrar estado de descarga.

### Fase 3: almacenamiento en SIRICO

Crear tabla sugerida `phone_call_recordings`:

- `id`.
- `phone_call_id`.
- `ucm_filename`.
- `ucm_filedir`.
- `storage_disk`.
- `path`.
- `mime_type`.
- `size_bytes`.
- `duration_seconds`.
- `downloaded_at`.
- `status`.
- `error_message`.
- `raw_payload`.
- timestamps.

Estados sugeridos:

- `pending`.
- `downloaded`.
- `failed`.
- `deleted`.

### Fase 4: API del agente

Endpoints sugeridos:

- `POST /api/agent/telephony/recordings`
  - registra metadata y/o sube archivo.
- `POST /api/agent/telephony/recordings/{recording}/fail`
  - reporta error de descarga.

Alternativa:

- Si los archivos son grandes, usar subida por multipart directa al backend.
- Si se decide NAS local, subir solo metadata y servir bajo demanda desde agente no es recomendable para produccion; mejor copiar a storage controlado por SIRICO.

### Fase 5: UI

- En detalle de llamada, mostrar:
  - boton `Escuchar`.
  - boton `Descargar`.
  - estado de grabacion.
  - fecha de descarga.
- Registrar auditoria de accesos a grabaciones:
  - usuario.
  - llamada.
  - accion.
  - fecha/hora.

## AI Transcription

El UCM muestra columna `AI Transcription`, pero las llamadas actuales aparecen como `Not Transcribed`.

Hipotesis:

- La transcripcion requiere que exista grabacion.
- La transcripcion puede depender de configuracion AI/GDMS/RemoteConnect.
- Puede no estar disponible por API, aunque se vea en UI.

Pendientes:

- Revisar tab `AI` de la extension 25.
- Revisar menu `Integrations > AI`.
- Confirmar si requiere licencia, trial o servicio GDMS.
- Hacer llamada grabada y validar si aparece transcripcion.
- Revisar si `cdrapi` devuelve campos de transcripcion/resumen.

Decision recomendada:

- Probar primero AI nativo del UCM.
- No depender de AI nativo hasta confirmar que el texto sale por API.
- Si no sale por API, hacer transcripcion propia en SIRICO a partir del WAV descargado.

## Riesgos y consideraciones

- El almacenamiento local del UCM puede llenarse si se graban muchas llamadas.
- Antes de produccion conviene usar NAS, USB/SD o almacenamiento externo controlado.
- Las grabaciones son informacion sensible.
- Se deben definir permisos, auditoria y politica de retencion.
- Conviene avisar a usuarios/clientes si se grabaran llamadas.
- Evitar activar grabacion global hasta tener retencion y monitoreo.

## Pendientes concretos

- [ ] Confirmar codigo manual de inicio/fin de grabacion.
- [ ] Probar grabacion manual con extension 25.
- [ ] Confirmar que CDR muestra archivo en `Recordings`.
- [ ] Confirmar que `cdrapi` trae `recordfiles` lleno.
- [ ] Encontrar `Auto Record` en UI para extension 25 o accion masiva.
- [ ] Probar `auto_record = external`.
- [ ] Confirmar storage disponible y politica de limpieza.
- [ ] Investigar REC API para descarga del WAV.
- [ ] Crear migracion `phone_call_recordings`.
- [ ] Agregar endpoint del agente para reportar/subir grabaciones.
- [ ] Agregar UI basica en SIRICO para ver llamadas con grabacion.
- [ ] Agregar permisos y auditoria.
- [ ] Revisar AI nativo del UCM y si la transcripcion sale por API.
