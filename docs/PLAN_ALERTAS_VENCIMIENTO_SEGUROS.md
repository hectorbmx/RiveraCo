# Plan alertas de vencimiento de seguros

Objetivo: configurar desde `configuracion-empresa?tab=vehiculos` las alertas de vencimiento de polizas, usando lo que ya existe en `seguros` y reutilizando la tabla de destinatarios `empresa_alerta_destinatarios` sin dejar procesos legacy a medias.

## Estado actual

- La tabla `seguros` ya tiene campos por poliza:
  - `alerta_vencimiento_activa`
  - `dias_preaviso`
  - `ultima_alerta_enviada_at`
- Ya existe el comando `app:check-insurance-expirations`.
- El scheduler lo ejecuta diario a las 08:00.
- La notificacion `SeguroVehiculoVencimiento` ya existe, pero solo envia notificacion interna por `database`.
- El comando actual usa una ventana fija de 15 dias.
- El comando actual manda a `User::role('administrador')`, rol que parece legacy frente a `admin-rivera` y `super-admin`.
- En `configuracion-empresa?tab=vehiculos` ya existe una seccion de alertas preventivas con destinatarios configurables.
- `empresa_alerta_destinatarios` ya soporta usuario interno, email externo, correo, notificacion interna y activo/inactivo.

## Decision propuesta

Usar una regla global de empresa para seguros, pero conservar los campos por poliza como fallback/override puntual.

Orden de decision:

1. Si la empresa tiene alertas de seguros desactivadas, no enviar alertas.
2. Si la poliza tiene `alerta_vencimiento_activa = false`, saltar esa poliza.
3. Dias de aviso:
   - usar `seguros.dias_preaviso` si tiene valor valido y queremos permitir override por poliza;
   - si no, usar `empresa_config.seguro_alerta_dias`;
   - si no existe config todavia, fallback a 15 dias para compatibilidad temporal.
4. Destinatarios:
   - primero `empresa_alerta_destinatarios.modulo = seguros`;
   - si no hay, fallback a `modulo = vehiculos`;
   - si no hay, fallback a usuarios `admin-rivera` y `super-admin`;
   - si no hay, fallback legacy a `administrador`.

## Fase 1: inventario y confirmacion

1. Revisar roles reales en base de datos local restaurada.
2. Confirmar si existe algun usuario con rol `administrador`.
3. Confirmar si ya hay registros en `empresa_alerta_destinatarios` para `vehiculos`.
4. Confirmar si alguna poliza tiene `dias_preaviso` diferente a 30.
5. Confirmar si el usuario quiere permitir override por poliza o solo manejar dias globales.
6. Confirmar si la alerta debe enviarse todos los dias mientras este dentro de ventana o solo una vez por vencimiento.

Checkpoint:

- [ ] Roles reales confirmados.
- [ ] Destinatarios actuales de vehiculos revisados.
- [ ] Uso actual de `dias_preaviso` por poliza entendido.
- [ ] Regla de repeticion de alertas definida.

## Fase 2: estructura de datos

1. Crear migracion para agregar campos globales en `empresa_config`:
   - `seguro_alertas_activas` boolean default true.
   - `seguro_alerta_dias` unsigned integer default 30.
2. Agregar esos campos a `$fillable` de `EmpresaConfig`.
3. No crear tabla nueva de destinatarios: reutilizar `empresa_alerta_destinatarios` con `modulo = seguros`.
4. Mantener campos existentes de `seguros`.
5. No eliminar `dias_preaviso` de `seguros`; dejarlo como fallback/override para no romper datos previos.

Checkpoint:

- [ ] Migracion creada sin tocar datos existentes.
- [ ] `EmpresaConfig` acepta los nuevos campos.
- [ ] No se duplica la tabla de destinatarios.
- [ ] Rollback de migracion definido.

## Fase 3: UI en configuracion de empresa

1. En `configuracion-empresa?tab=vehiculos`, separar visualmente dos bloques:
   - `Alertas de servicio preventivo`.
   - `Alertas de vencimiento de seguros`.
2. Mantener el bloque actual de preventivos sin cambiar nombres de inputs para no romper guardado.
3. Agregar inputs nuevos para seguros:
   - checkbox `seguro_alertas_activas`.
   - number `seguro_alerta_dias`.
4. Agregar tabla de destinatarios de seguros usando el mismo patron actual:
   - usuario interno.
   - email externo / alterno.
   - correo.
   - notificacion.
   - activo.
5. Usar nombres separados para no chocar con los destinatarios de vehiculos:
   - `seguro_destinatarios[...]`
   - `nuevo_seguro_destinatario[...]`
6. Cargar coleccion `$seguroAlertaDestinatarios` desde el controller.

Checkpoint:

- [ ] La pestaña vehiculos muestra preventivos y seguros separados.
- [ ] Guardar preventivos sigue funcionando igual.
- [ ] Guardar seguros no pisa destinatarios de preventivos.
- [ ] Se puede agregar usuario interno como destinatario de seguros.
- [ ] Se puede agregar email externo como destinatario de seguros.

## Fase 4: guardado en controller

1. Extender el branch `section === 'vehiculos'` en `EmpresaConfigController`.
2. Validar nuevos campos:
   - `seguro_alerta_dias`: integer, min 0, max 365.
   - `seguro_alertas_activas`: boolean nullable.
   - `seguro_destinatarios`: array nullable.
   - `nuevo_seguro_destinatario`: array nullable.
3. Actualizar `empresa_config` con campos de preventivos y seguros.
4. Generalizar `guardarDestinatariosAlertaVehiculos` a un metodo reutilizable, por ejemplo `guardarDestinatariosAlertaModulo`.
5. Usar ese metodo para:
   - `vehiculos` con `destinatarios` y `nuevo_destinatario`.
   - `seguros` con `seguro_destinatarios` y `nuevo_seguro_destinatario`.
6. Redirigir de vuelta a `tab=vehiculos`.

Checkpoint:

- [ ] Controller guarda `seguro_alertas_activas`.
- [ ] Controller guarda `seguro_alerta_dias`.
- [ ] Controller crea destinatarios `modulo = seguros`.
- [ ] Controller actualiza destinatarios existentes `modulo = seguros`.
- [ ] El metodo reutilizable sigue guardando destinatarios `modulo = vehiculos`.

## Fase 5: comando de vencimiento de seguros

1. Modificar `CheckInsuranceExpirations` para cargar `EmpresaConfig::first()`.
2. Si no hay config, usar fallback temporal:
   - alertas activas true.
   - dias 15.
3. Si `seguro_alertas_activas` es false, terminar sin enviar.
4. Calcular ventana de busqueda con dias configurados.
5. Evaluar solo seguros con:
   - `alerta_vencimiento_activa = true`.
   - `vigencia_hasta >= hoy`.
   - `vigencia_hasta <= hoy + dias`.
   - no alertados hoy, o segun la regla final de repeticion.
6. Si se conserva override por poliza, calcular cada seguro contra su propio `dias_preaviso` cuando aplique.
7. Cargar destinatarios:
   - `modulo = seguros`.
   - fallback `modulo = vehiculos`.
   - fallback roles `admin-rivera`, `super-admin`.
   - fallback legacy `administrador`.
8. Enviar notificacion interna a usuarios configurados.
9. Enviar correo a emails configurados cuando `notificar_correo` este activo.
10. Actualizar `ultima_alerta_enviada_at` solo si se envio algo.

Checkpoint:

- [ ] El comando ya no usa 15 dias hardcodeado como regla principal.
- [ ] El comando ya no depende solo del rol `administrador`.
- [ ] El comando respeta `seguro_alertas_activas`.
- [ ] El comando respeta `alerta_vencimiento_activa` por poliza.
- [ ] El comando usa destinatarios `modulo = seguros`.
- [ ] El fallback evita que la alerta quede muda si no hay destinatarios de seguros.

## Fase 6: notificaciones y correo

1. Revisar si `SeguroVehiculoVencimiento` debe renombrarse a un nombre mas generico, por ejemplo `SeguroVencimientoNotification`.
2. Mantener compatibilidad si el rename aumenta el riesgo; no es obligatorio para la primera version.
3. Agregar `toMail` a la notificacion si se decide enviar correo usando Laravel Notifications.
4. O replicar el patron de `VehiculosAlertasPreventivoKm` usando `MicrosoftGraphMailService` si las alertas de empresa ya salen por Graph.
5. Agregar URL destino en payload de notificacion:
   - vehiculo: `/mantenimiento/vehiculos/{id}/edit?tab=seguro`.
   - maquina: `/maquinas/maquinas/{id}?tab=seguros`.
6. Incluir datos minimos en mensaje:
   - tipo de activo.
   - identificador/nombre del activo.
   - aseguradora.
   - numero de poliza.
   - fecha de vencimiento.
   - dias restantes.

Checkpoint:

- [ ] Notificacion interna abre el activo correcto.
- [ ] Correo incluye datos suficientes para actuar.
- [ ] Vehiculos y maquinas se muestran correctamente.
- [ ] No se duplica notificacion si correo y sistema estan activos para el mismo usuario.

## Fase 7: pruebas manuales y dry-run

1. Agregar o usar opcion `--dry-run` en el comando de seguros.
2. Crear escenario local con una poliza que venza dentro de la ventana.
3. Crear escenario local con una poliza fuera de la ventana.
4. Crear escenario local con una poliza con `alerta_vencimiento_activa = false`.
5. Probar con destinatarios `modulo = seguros`.
6. Probar sin destinatarios `modulo = seguros` para validar fallback a `vehiculos`.
7. Probar sin destinatarios para validar fallback a roles.
8. Confirmar que `ultima_alerta_enviada_at` solo cambia cuando se envia.

Checkpoint:

- [ ] `php artisan app:check-insurance-expirations --dry-run` muestra candidatos correctos.
- [ ] Polizas fuera de ventana no se notifican.
- [ ] Polizas desactivadas no se notifican.
- [ ] Destinatarios configurados reciben notificacion/correo segun checkboxes.
- [ ] Fallback funciona sin dejar el proceso silencioso.


## Alcance adicional: consulta de seguros en app movil

Objetivo: cuando un residente tenga un vehiculo asignado, la app movil debe permitir consultar la informacion vigente del seguro y la tarjeta de circulacion del vehiculo desde `vehiculo-registro/{vehiculo}`. El residente no debe capturar ni editar datos desde Ionic; solo consulta.

### Principio de implementacion

- Reutilizar la logica web que ya identifica seguro vigente, ultimo seguro y tarjeta de circulacion vigente en `VehiculoController`.
- Evitar duplicar reglas en Ionic; Ionic solo debe renderizar un payload ya resuelto por backend.
- Aislar la UI en Ionic en una pieza pequena para que `VehiculoRegistroPage` no siga creciendo.
- Mantener el registro de KM como flujo principal; documentos debe ser consulta secundaria.
- No agregar captura movil de polizas ni tarjeta de circulacion.

### Estado tecnico encontrado

- Ionic tiene la ruta `vehiculo-registro/:vehiculo_id` en `app.routes.ts`.
- La pantalla es `pages/vehiculo-registro/vehiculo-registro.page.*`.
- La pantalla consume `GET /api/v1/vehiculos/km-log`.
- El endpoint esta en `App\Http\Controllers\Api\V1\VehiculoKmController@index`.
- `VehiculoKmController` ya devuelve `asignacion`, `obra_actual` y `data`.
- `formatearAsignacion()` devuelve datos basicos del vehiculo, pero no seguros ni tarjeta.
- `Vehiculo` ya tiene relaciones:
  - `seguros()` como morphMany a `Seguro`.
  - `documentoTarjetaCirculacionVigente()` para `tipo = tarjeta_circulacion` y `vigente = true`.
- En web, `VehiculoController@index` ya calcula alertas de seguro/tarjeta con una ventana de 30 dias.

### Barrido Laravel: contratos API existentes

Rutas API relevantes revisadas:

- `POST /api/v1/login` -> `AuthController@login`.
- `GET /api/v1/me` -> `AuthController@me`.
- `GET /api/v1/app/contexto-opciones` -> `AppContextController@opciones`.
- `GET /api/v1/vehiculos/km-log` -> `VehiculoKmController@index`.
- `POST /api/v1/vehiculos/km-log` -> `VehiculoKmController@store`.

Contratos actuales relacionados:

1. `login` / `me`
   - Devuelven `contexto` cuando el usuario entra como residente.
   - `contexto.vehiculo` existe, pero trae solo datos basicos del vehiculo asignado.
   - No devuelve seguros ni tarjeta de circulacion.
   - Este contrato alimenta la pantalla principal/tab residente.

2. `app/contexto-opciones`
   - Devuelve paneles disponibles, obras residente y defaults.
   - No devuelve documentos del vehiculo.
   - No conviene tocarlo para este feature.

3. `GET vehiculos/km-log`
   - Es el contrato principal de `/vehiculo-registro/{vehiculo}` en Ionic.
   - Devuelve:
     - `ok`.
     - `vehiculo_empleado_id`.
     - `asignacion`.
     - `obra_actual`.
     - `data` con historial de registros.
   - `asignacion.vehiculo` trae `id`, `nombre`, `marca`, `modelo`, `placas`.
   - No devuelve seguros ni tarjeta.
   - Este es el primer contrato que conviene extender.

4. `POST vehiculos/km-log`
   - Registra kilometraje, foto de odometro, ticket de gasolina, monto y notas.
   - No debe modificarse para consulta documental.
   - La consulta de poliza/tarjeta no debe agregar captura movil.

Datos reales confirmados:

- Poliza:
  - Modelo: `Seguro`.
  - Relacion: `Vehiculo::seguros()` como `morphMany(Seguro::class, 'asegurable')`.
  - Archivo: `seguros.documento_path`.
  - Fechas: `vigencia_desde`, `vigencia_hasta`.
  - Estado: `estatus`.
  - URL usada en web: `asset('storage/' . $seguro->documento_path)` o `Storage::url($seguro->documento_path)`.

- Tarjeta de circulacion:
  - Modelo: `VehiculoDocumento`.
  - Tabla: `vehiculo_documentos`.
  - Tipo: `tarjeta_circulacion`.
  - Archivo: `archivo_path`.
  - Fecha de vencimiento: `fecha_vencimiento`.
  - Vigente: `vigente`.
  - Relacion: `Vehiculo::documentoTarjetaCirculacionVigente()`.
  - URL usada en web: `asset('storage/' . $documento->archivo_path)` o `Storage::url($documento->archivo_path)`.

Decision de contrato inicial:

- No crear endpoint nuevo en primera version.
- Extender `GET /api/v1/vehiculos/km-log` agregando datos de consulta documental dentro de `asignacion.vehiculo.documentos_consulta`.
- Mantener intactas todas las llaves actuales para no romper Ionic.
- Dejar `login/me` sin cambios en la primera version, pero preparar mapper reusable para poder llevar el mismo contrato a `contexto.vehiculo` despues.
- No tocar `POST /api/v1/vehiculos/km-log`.

Contrato nuevo a agregar:

```json
{
  "asignacion": {
    "vehiculo": {
      "id": 11,
      "nombre": "FORD P-8",
      "marca": "FORD",
      "modelo": "P-8",
      "placas": "JT40817",
      "documentos_consulta": {
        "estado": "ok",
        "seguro": {
          "id": 123,
          "estatus": "vigente",
          "estado_visual": "ok",
          "aseguradora": "GNP",
          "poliza_numero": "ABC-123",
          "vigencia_desde": "2026-01-01",
          "vigencia_hasta": "2026-12-31",
          "dias_restantes": 45,
          "documento_url": "http://127.0.0.1:8000/storage/seguros/documentos/poliza.pdf",
          "documento_disponible": true
        },
        "tarjeta_circulacion": {
          "id": 55,
          "estatus": "vigente",
          "estado_visual": "ok",
          "fecha_vencimiento": "2026-12-31",
          "dias_restantes": 45,
          "documento_url": "http://127.0.0.1:8000/storage/vehiculos/11/documentos/tarjeta.pdf",
          "documento_disponible": true
        }
      }
    }
  }
}
```

Checks de contrato antes de implementar:

- [ ] Confirmar si `documentos_consulta` debe vivir dentro de `asignacion.vehiculo` o como bloque hermano `documentos_vehiculo`.
- [ ] Confirmar si URLs publicas `/storage/...` son aceptables para app movil o si se requiere endpoint protegido.
- [ ] Confirmar ventana para `warning`: 30 dias como web actual o valor configurable futuro.
- [ ] Confirmar si `estatus = cancelada` debe excluir seguro aunque las fechas esten vigentes.
- [ ] Confirmar si cuando no hay poliza vigente se muestra ultimo seguro vencido o estado `empty`.
### Tarea 1: confirmar datos reales sin modificar

1. Revisar campos reales de `vehiculo_documentos` o modelo `VehiculoDocumento`.
2. Confirmar nombre exacto del campo archivo de tarjeta de circulacion.
3. Confirmar si la URL publica actual se arma con `Storage::disk('public')->url(...)` o con ruta protegida.
4. Revisar si `seguros.documento_path` siempre apunta al PDF/archivo de poliza.
5. Confirmar si una poliza vigente se define por fechas o tambien por `estatus`.

Checks:

- [ ] Campo archivo de tarjeta identificado.
- [ ] Campo vencimiento de tarjeta identificado.
- [ ] Forma de construir URL de tarjeta identificada.
- [ ] Regla de poliza vigente confirmada.
- [ ] Se confirma si se usara archivo publico o endpoint protegido.

### Primer bloque ejecutable: mapper backend sin reescribir reglas

Consigna: no reescribir codigo ni reglas que ya existen. Primero extraer/reutilizar la logica actual de seguro/tarjeta y encapsularla para API. Si una regla ya vive en `VehiculoController@index`, se debe copiar como punto de partida solo para aislarla en un mapper/service, y dejar anotado despues el pendiente de hacer que web tambien consuma ese mapper.

Objetivo del bloque: extender el contrato de lectura de `GET /api/v1/vehiculos/km-log` agregando `asignacion.vehiculo.documentos_consulta`, sin cambiar el contrato existente ni tocar Ionic.

Pasos pequenos:

1. Crear service backend pequeno:
   - Ruta sugerida: `app/Services/Vehiculos/VehiculoDocumentosConsultaService.php`.
   - Metodo sugerido: `map(Vehiculo $vehiculo, int $diasAdvertencia = 30): array`.
   - No debe depender de `Request`, auth ni controller.
2. Mover al service la regla ya existente en web:
   - seguros no cancelados.
   - poliza vigente por `vigencia_desde <= hoy <= vigencia_hasta`.
   - si no hay vigente, considerar ultimo seguro relevante para estado vencido/sin seguro.
   - tarjeta vigente desde `documentoTarjetaCirculacionVigente`.
   - warning si vence dentro de 30 dias, igual que web actual.
3. El service debe devolver estructura estable aunque no haya datos:
   - `estado` general.
   - `seguro` con objeto o `null`.
   - `tarjeta_circulacion` con objeto o `null`.
4. En `VehiculoKmController`, cargar relaciones necesarias:
   - `vehiculo.seguros`.
   - `vehiculo.documentoTarjetaCirculacionVigente`.
5. Inyectar o resolver el service en `VehiculoKmController`.
6. Agregar `documentos_consulta` dentro de `formatearAsignacion()` en `vehiculo`.
7. No cambiar nombres ni estructura de las llaves existentes.
8. No tocar `POST /api/v1/vehiculos/km-log`.
9. No tocar Ionic todavia.
10. Probar sintaxis y respuesta del endpoint/service.

Checks de avance:

- [x] Service creado con una sola responsabilidad.
- [x] Service usa modelos/relaciones existentes; no consulta tablas inventadas.
- [x] No se cambia el comportamiento de `VehiculoController@index` en este bloque.
- [x] `VehiculoKmController@index` conserva las llaves existentes.
- [x] `POST /api/v1/vehiculos/km-log` no fue modificado.
- [x] Si no hay seguro, `documentos_consulta.seguro` queda `null` y no rompe JSON.
- [x] Si no hay tarjeta, `documentos_consulta.tarjeta_circulacion` queda `null` y no rompe JSON.
- [x] URLs se generan con el mismo mecanismo que la API ya usa: `Storage::disk('public')->url(...)`.
- [x] `php -l` limpio en archivos PHP tocados.
- [x] Se obtuvo al menos una respuesta JSON de ejemplo o prueba por tinker.

Continuidad si se pausa aqui:

- [x] Anotar archivo exacto del service creado: `app/Services/Vehiculos/VehiculoDocumentosConsultaService.php`.
- [x] Anotar si `VehiculoKmController` ya consume el service: si, en `formatearAsignacion()`.
- [x] Anotar ejemplo de `documentos_consulta` recibido: controller devolvio `seguro = null` y `tarjeta_circulacion` vigente para vehiculo 12.
- [x] Anotar si falta probar con vehiculo sin seguro/tarjeta: probado con Alejandro Gonzalez / obra 35 / vehiculo 11, ambos `null`.

Nota de avance 2026-09-15: primer bloque backend completado. Se creo `VehiculoDocumentosConsultaService`, `VehiculoKmController@index` conserva sus llaves base (`ok`, `vehiculo_empleado_id`, `asignacion`, `obra_actual`, `data`) y ahora agrega `asignacion.vehiculo.documentos_consulta`. Pruebas por tinker: vehiculo 11 sin documentos devuelve `seguro = null` y `tarjeta_circulacion = null`; vehiculo 3 devuelve seguro vencido con `documento_url` y tarjeta vigente con `documento_url`; invocacion del controller con usuario app devolvio status 200 y `tarjeta_circulacion` vigente.
### Tarea 2: extraer/reutilizar resolucion de documentos del vehiculo

1. Crear o reutilizar un helper/service backend pequeno para resolver documentos consultables del vehiculo.
2. Entrada sugerida: `Vehiculo $vehiculo` y dias de advertencia.
3. Salida sugerida:
   - `seguro_consulta`.
   - `tarjeta_circulacion`.
   - `documentos_estado`.
4. Mover ahi la regla de seleccion de seguro vigente o ultimo seguro relevante.
5. Mover ahi la regla de tarjeta vigente/vencida/por vencer.
6. Usarlo despues desde endpoint movil.
7. Dejar como siguiente mejora reutilizarlo tambien en `VehiculoController@index` para quitar duplicacion web.

Checks:

- [ ] La regla queda en una clase/metodo backend reutilizable.
- [ ] No se duplica logica nueva dentro de Ionic.
- [ ] El service no depende de Request ni de auth.
- [ ] La salida no rompe si no hay seguro.
- [ ] La salida no rompe si no hay tarjeta.

### Tarea 3: extender payload movil sin romper clientes existentes

1. En `VehiculoKmController@index`, cargar la asignacion con vehiculo, seguros y tarjeta vigente.
2. Agregar la consulta documental dentro de `asignacion.vehiculo` o como bloque hermano.
3. Preferencia de payload para bajo riesgo:

```json
{
  "asignacion": {
    "vehiculo": {
      "id": 11,
      "nombre": "FORD P-8",
      "marca": "FORD",
      "modelo": "P-8",
      "placas": "JT40817",
      "documentos_consulta": {
        "seguro": {},
        "tarjeta_circulacion": {},
        "estado": "ok"
      }
    }
  }
}
```

4. Mantener intactas las llaves existentes: `vehiculo_empleado_id`, `asignacion`, `obra_actual`, `data`.
5. No agregar endpoint nuevo en primera version si el endpoint actual ya alimenta la pantalla.
6. Si el payload crece demasiado en el futuro, entonces separar a `GET vehiculos/km-log/documentos`.

Checks:

- [x] `GET /api/v1/vehiculos/km-log` sigue respondiendo el historial de KM.
- [x] La app actual no se rompe si ignora `documentos_consulta`.
- [x] El payload trae poliza/ultimo seguro relevante cuando existe.
- [x] El payload trae fallback de ultimo seguro cuando no hay vigente.
- [x] El payload trae tarjeta vigente cuando existe.


Nota de avance 2026-09-15: contrato API validado por ruta real con `Sanctum::actingAs` y `GET /api/v1/vehiculos/km-log`. Escenarios probados: Alfonso Reynoso / obra 7 / vehiculo 3 devuelve seguro vencido con `documento_url` y tarjeta vigente; Luis Navarro / obra 33 / vehiculo 12 devuelve `seguro = null` y tarjeta vigente; Alejandro Gonzalez / obra 35 / vehiculo 11 devuelve `seguro = null` y `tarjeta_circulacion = null`. Las llaves base del endpoint se mantienen.
### Tarea 4: definir contrato del payload

Contrato sugerido para seguro:

```json
{
  "id": 123,
  "estatus": "vigente",
  "estado_visual": "ok",
  "aseguradora": "GNP",
  "poliza_numero": "ABC-123",
  "vigencia_desde": "2026-01-01",
  "vigencia_hasta": "2026-12-31",
  "dias_restantes": 45,
  "documento_url": "http://127.0.0.1:8000/storage/seguros/documentos/archivo.pdf",
  "documento_disponible": true
}
```

Contrato sugerido para tarjeta:

```json
{
  "id": 55,
  "estatus": "vigente",
  "estado_visual": "ok",
  "fecha_vencimiento": "2026-12-31",
  "dias_restantes": 45,
  "documento_url": "http://127.0.0.1:8000/storage/vehiculos/documentos/tarjeta.pdf",
  "documento_disponible": true
}
```

Estados visuales sugeridos:

- `ok`: vigente.
- `warning`: por vencer.
- `danger`: vencido o no existe documento requerido.
- `empty`: no hay registro cargado.

Checks:

- [ ] Contrato validado contra campos reales.
- [ ] Fechas salen en `Y-m-d` para Ionic.
- [ ] URLs son absolutas o consumibles desde Ionic.
- [ ] `documento_disponible` evita que Ionic infiera por strings vacios.

### Tarea 5: aislar UI en Ionic

1. Crear componente standalone para consulta documental, por ejemplo:
   - `src/app/components/vehiculo-documentos-consulta/vehiculo-documentos-consulta.component.ts`
2. Entradas del componente:
   - `documentosConsulta`.
   - opcional `compact` si despues se reutiliza en otra pantalla.
3. El componente debe renderizar:
   - boton `Ver poliza y tarjeta`.
   - modal o panel expandible de consulta.
   - tarjeta de seguro.
   - tarjeta de circulacion.
   - botones para abrir documento si existe.
4. `VehiculoRegistroPage` solo pasa `asignacion?.vehiculo?.documentos_consulta` al componente.
5. No meter reglas de vigencia en el componente; solo usar `estado_visual`, fechas y textos del backend.

Checks:

- [x] `VehiculoRegistroPage` mantiene poca logica nueva.
- [x] Componente creado como standalone; build global pendiente por dependencias biometricas faltantes.
- [x] Modal/panel queda separado del formulario de KM.
- [x] Estado sin documentos se ve claro.
- [x] Botones de PDF/tarjeta se deshabilitan cuando no hay URL.


Nota de avance 2026-09-15: segundo bloque Ionic avanzado. Se creo `src/app/components/vehiculo-documentos-consulta/vehiculo-documentos-consulta.component.ts` como componente standalone, y `VehiculoRegistroPage` solo pasa `asignacion?.vehiculo?.documentos_consulta`. El componente muestra boton de consulta, modal con poliza/tarjeta, estados visuales y botones deshabilitados si no hay URL. `npm run build` fue intentado; el build no llega a cerrar por dependencias biometricas faltantes ya referenciadas en `src/app/services/biometric-login.service.ts`, no por errores del componente nuevo.
### Tarea 6: apertura de documentos en Ionic

1. Empezar con apertura simple usando link externo o `window.open(url, '_blank')`.
2. Validar en navegador local `localhost:8100`.
3. Validar en dispositivo Android/iPhone despues, porque WebView puede comportarse distinto con PDFs.
4. Si `window.open` falla en dispositivo, evaluar usar Capacitor Browser o plugin equivalente.
5. Mantener fallback: copiar/abrir URL en navegador externo si el PDF no se muestra embebido.

Checks:

- [ ] PDF de poliza abre en navegador local.
- [ ] Imagen/PDF de tarjeta abre en navegador local.
- [ ] En dispositivo, el documento abre fuera de la app o en visor compatible.
- [ ] No se intenta previsualizar PDF pesado dentro de la pantalla principal.

### Tarea 7: seguridad y autorizacion

1. Confirmar que el endpoint solo devuelve documentos del vehiculo asignado al contexto permitido.
2. No aceptar `vehiculo_id` del URL como unica fuente de autorizacion.
3. Reutilizar `AppMobileContextService` para validar residente/obra/vehiculo.
4. Si los archivos quedan publicos en `storage`, documentar ese comportamiento.
5. Si se requiere privacidad real, crear endpoint protegido para descargar/ver documento y no exponer `storage` directo.

Checks:

- [ ] Residente A no puede consultar vehiculo de residente B cambiando el ID de la ruta.
- [ ] Admin Rivera/Super Admin en panel residente respeta obra seleccionada.
- [ ] Sin vehiculo asignado no se devuelve documento.
- [ ] No se agrega captura movil de documentos.

### Tarea 8: pruebas por escenarios

1. Residente con vehiculo, poliza vigente y tarjeta vigente.
2. Residente con vehiculo y poliza por vencer.
3. Residente con vehiculo y poliza vencida.
4. Residente con vehiculo sin poliza.
5. Residente con vehiculo sin tarjeta de circulacion.
6. Residente con vehiculo pero sin obra activa seleccionada.
7. Admin Rivera/Super Admin entrando al panel residente con obra seleccionada.
8. Usuario sin empleado y sin permiso global.

Checks:

- [ ] Cada escenario tiene respuesta backend esperada.
- [ ] Cada escenario tiene estado visual correcto en Ionic.
- [ ] El registro de KM sigue funcionando despues de agregar documentos.
- [ ] No aparecen botones rotos con URL vacia.

### Tarea 9: continuidad para siguiente sesion

Antes de cerrar cualquier avance, actualizar esta lista:

- [ ] Ultimo archivo backend tocado documentado.
- [x] Ultimo archivo Ionic tocado documentado: `app/src/app/components/vehiculo-documentos-consulta/vehiculo-documentos-consulta.component.ts` y `app/src/app/pages/vehiculo-registro/vehiculo-registro.page.*`.
- [ ] Endpoint probado y respuesta ejemplo guardada en notas del PR/tarea.
- [ ] Escenario de prueba usado anotado con usuario, obra y vehiculo.
- [ ] Pendientes bloqueantes escritos aqui mismo.
- [ ] `npm run build` de Laravel si se tocó `rivera-v2` frontend.
- [x] `npm run build` de Ionic intentado; falla por dependencias biometricas faltantes (`@aparajita/capacitor-biometric-auth`, `@aparajita/capacitor-secure-storage`) ya referenciadas en el proyecto.
- [x] No quedaron cambios de captura movil de documentos.

### Orden recomendado de ejecucion

1. Backend lectura de datos reales.
2. Backend service/payload.
3. Prueba JSON del endpoint.
4. Ionic tipos/modelos minimos.
5. Ionic componente aislado.
6. Integracion del componente en `VehiculoRegistroPage`.
7. Pruebas de apertura de PDF/tarjeta.
8. Limpieza, build y notas de continuidad.
## Fase 8: limpieza y cierre

1. Revisar textos con acentos/encoding en la vista de configuracion si se toca esa zona.
2. Ejecutar `php -l` en controllers/comandos modificados.
3. Ejecutar `npm run build` si se cambia Blade/JS con assets requeridos.
4. Ejecutar `php artisan view:clear`.
5. Revisar `git diff --check`.
6. Documentar en este archivo la decision final sobre override por poliza.
7. Preparar resumen para deploy a produccion.

Checkpoint:

- [ ] Sintaxis PHP limpia.
- [ ] Build frontend limpio.
- [ ] Vista de configuracion guarda y vuelve a `tab=vehiculos`.
- [ ] Comando programado conserva horario diario 08:00.
- [ ] Plan actualizado con decisiones finales.

## Criterio de listo

- Desde `configuracion-empresa?tab=vehiculos` se pueden configurar dias de alerta y destinatarios de seguros.
- Las alertas de seguros usan destinatarios configurables antes que roles hardcodeados.
- Los campos legacy de `seguros` siguen funcionando como control por poliza o fallback.
- Si no hay configuracion nueva, el proceso no queda mudo: usa fallback controlado.
- Vehiculos y maquinas generan alertas de vencimiento con links claros al registro correspondiente.
