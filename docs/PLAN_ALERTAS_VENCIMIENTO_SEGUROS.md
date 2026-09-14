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
