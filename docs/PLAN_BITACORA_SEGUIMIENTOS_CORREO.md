# Bitácora de seguimientos por correo

**Corte del 24 de noviembre de 2026 · Piloto local en Rivera v2**

## Objetivo

Reducir las solicitudes comerciales que se quedan sin atención entre los correos de `pilas@riveraco.com.mx`. La bandeja debe mostrar qué pidió el cliente, quién es responsable, si Rivera respondió, qué se envió y a quién le toca actuar ahora. Se incluyen cotizaciones, disponibilidad, información técnica y otras solicitudes comerciales que requieren respuesta.

El equipo lee y responde desde Outlook. Laravel consulta el correo con Microsoft Graph y conserva la bitácora del hilo. GPT podrá ayudar a clasificar correos nuevos y a explicar la sugerencia. La clasificación, la asignación y los vínculos con Presupuestos se administrarán en Rivera.

## Avances verificados

| Área | Estado al corte |
| --- | --- |
| Microsoft Graph | La aplicación **Rivera - Bitácora Pilas** tiene `Application Mail.Read` asignado mediante RBAC de Exchange Online con alcance al buzón piloto. Exchange devolvió `True` para `pilas@` y `False` para `administracion@`. La prueba desde Laravel autenticó correctamente y leyó **Recibidos** y **Enviados** de `pilas@`. |
| Sincronización | Comando manual, local y limitado a una muestra reciente por carpeta. Usa el identificador de conversación de Graph para asociar los mensajes y el identificador inmutable de cada mensaje para deduplicar. La bitácora guarda metadatos, extracto acotado y enlace a Outlook cuando está disponible. |
| Primer lote real | Se leyeron 8 recibidos y 8 enviados. Se crearon 6 hilos **Por revisar** y se vincularon 5 correos enviados. Tres enviados quedaron fuera porque su conversación no apareció en la muestra. Al repetir la misma muestra se detectaron 13 mensajes ya procesados y no se duplicaron registros. |
| Pantalla | **✉️ Seguimientos** aparece entre Calendario y Presupuestos. La ruta `/seguimientos` requiere sesión. En el piloto local muestra correos reales, filtros, búsqueda, motivo de apertura e historial. Fuera del piloto muestra datos de ejemplo. |
| Selector de responsable | El icono de persona con un **+** abre un modal con empleados activos y búsqueda por nombre o puesto. Se comprobó que la selección aparece en la lista y en el detalle. **Es temporal y se pierde al recargar**. Quedan pendientes la persistencia, la alerta y los permisos específicos. |
| Clasificación | Las reglas locales están en uso. El adaptador de OpenAI ya existe y está apagado. Se verificaron con respuestas simuladas el esquema estricto, el envío de asunto y extracto, la validación de evidencia, el paso a revisión ante error y el procesamiento idempotente de hilos conocidos. No se enviaron correos reales a OpenAI durante el piloto. |

### Prueba real de principio a fin

Hilo local **7**, con asunto **Prueba Seguimientos | Perforación para nave industrial**. Horas de Ciudad de México.

| Hora | Evento | Resultado observado |
| --- | --- | --- |
| 13:07 | El cliente ficticio pidió información técnica y cotización. | La regla local detectó la petición y abrió el hilo como **Sin respuesta**. |
| 13:11 | Rivera respondió desde `pilas@` y pidió confirmación. | El envío quedó en el mismo hilo y el estado pasó a **Esperando al cliente**. Graph permitió confirmar el envío. La persona que lo envió quedó como **Autor por confirmar**. |
| 13:14 | El cliente ficticio contestó en el mismo hilo. | Rivera agregó la réplica y volvió a mostrar el estado **Sin respuesta**, que en ese momento significaba que la siguiente acción había regresado a Rivera. |
| 13:17 | Rivera envió una segunda respuesta desde `pilas@`. | Se registró como cuarto evento, con hora y extracto. El estado siguió en **Sin respuesta** porque el texto no coincidió con las frases que las reglas actuales usan para cambiarlo. |

**Hallazgo:** el sistema ya puede acreditar que Rivera respondió. La etiqueta **Sin respuesta** resulta ambigua cuando ya hay envíos en el historial. El **Motivo de clasificación** explica por qué se abrió el hilo y se conserva como dato histórico. La atención actual debe verse por separado.

## Flujo acordado para el siguiente incremento

1. **Confirmar y asignar.** Cuando el equipo marca una oportunidad y selecciona a un responsable, Rivera abre o actualiza el registro comercial del mismo hilo. Guarda quién hizo la asignación y cuándo. Genera una alerta con enlace al hilo para la cuenta del responsable. El estado inicial, si aún no hay respuesta sustantiva, es **Pendiente de primera respuesta**.
2. **Registrar la primera respuesta.** Cuando Graph detecta un envío real en el hilo, Rivera registra la primera respuesta con fecha, extracto y enlace, aunque el texto no coincida con una frase predefinida. Un acuse automático se conserva en el historial. Para acreditar a una persona como autora hace falta que Graph la identifique o que se confirme manualmente. La asignación identifica a quien da seguimiento.
3. **Mostrar la siguiente acción.** Tras la primera respuesta, **En seguimiento** puede ser el estado inicial si queda por confirmar qué sigue. Si el cliente debe aportar algo, el hilo pasa a **Esperando al cliente**. Si Rivera aún debe enviar información o una propuesta, pasa a **Pendiente de Rivera**. El responsable podrá indicar la próxima acción manualmente cuando el correo sea ambiguo.
4. **Atender la réplica.** Si el cliente vuelve a escribir y requiere atención, se agrega al historial, el pendiente regresa a Rivera y se avisa al responsable. Un cierre manual debe quedar auditado.

En la ficha deben verse por separado **Responsable**, **Primera respuesta de Rivera**, **Última respuesta de Rivera** y **Siguiente acción**. Una respuesta enviada y un pendiente a cargo de Rivera pueden coexistir. Falta decidir qué pasa al asignar a un empleado sin cuenta de Rivera, cómo manejar reasignaciones y en qué casos se genera una alerta adicional. Revisar el sistema de notificaciones existente antes de agregar un canal nuevo.

## Clasificación y registro comercial

La acción propuesta es **Clasificar hilo**. Se guarda en Rivera con su autor, fecha y motivo. La bandera aplica a la conversación y las respuestas posteriores se agregan al historial.

| Opción | Resultado |
| --- | --- |
| **Lead · Cliente nuevo** | Abre un seguimiento y permite registrar un prospecto con nombre, correo, empresa si se conoce y fecha de primer contacto. |
| **Nueva oportunidad · Cliente existente** | Sugiere el cliente por correo y permite confirmar el vínculo. Cada nueva obra o solicitud puede tener su propio seguimiento. |
| **Otro seguimiento comercial** | Registra solicitudes técnicas, disponibilidad u otras preguntas que merecen atención. |
| **Excluir** | Retira el hilo de la bandeja comercial. Guarda una referencia mínima y el motivo para que un descarte se pueda revisar o recuperar. |

### Cruce con los catálogos de Rivera

- Buscar primero el correo exacto en **Clientes** y **Contactos de clientes**. Un dominio corporativo puede sugerir una empresa, con confirmación cuando haya dudas.
- Consultar **Proveedores** como señal de contexto. Una oferta de materiales, una factura de proveedor o una solicitud de pago queda fuera. Si el mismo remitente pide contratar a Rivera, el hilo puede ser una oportunidad.
- Crear prospectos a partir de oportunidades confirmadas. Si más adelante se identifica que el contacto pertenece a un cliente existente, vincularlo y conservar el historial.
- Permitir vincular un presupuesto existente y, cuando se conozca, la versión que se envió. El presupuesto y el vínculo son locales a Laravel. La solicitud puede atenderse desde antes de que exista el presupuesto.

## Reglas y GPT

1. **Hilo comercial conocido.** Asociar recibidos y enviados por conversación. Un texto breve como «Te adjunto los planos» sigue siendo parte del seguimiento.
2. **Hilo nuevo y caso claro.** Aplicar reglas locales para rebotes, boletines evidentes, nómina, altas de personal, trámites internos, copias de facturas enviadas por Rivera y correspondencia de compras. En casos sensibles como nómina, resolver la exclusión antes de enviar texto a GPT. Una palabra aislada como «factura» aporta contexto insuficiente.
3. **Caso dudoso.** GPT sugiere si requiere seguimiento, está por revisar o queda fuera de alcance. Debe aportar tipo de solicitud, acción esperada y evidencia textual. Laravel decide el resultado y envía a revisión los errores o las sugerencias que no pueda validar. El adaptador actual pide `solicitud_presupuesto`, `por_revisar` o `no_aplica`, con motivo y evidencia. Falta ampliarlo a solicitudes comerciales de información y a la acción esperada.
4. **Aprender de la revisión.** Conservar los casos clasificados por el equipo y las reglas aprobadas como referencia. Al corregir un hilo, permitir proponer una regla para casos similares. Una corrección individual permanece limitada al hilo hasta que se apruebe una regla. Durante el piloto, mantener una forma de revisar y recuperar descartes.

Ejemplos del piloto para la muestra de validación:

- Nómina y altas de personal de Ofitécnica. Excluir cuando el mensaje es un trámite de personal.
- Memo de alta de obra Pitahaya, subtramo. Excluir si es trámite interno. Revisar si el reenvío contiene una petición de cliente pendiente.
- Factura enviada por Rivera a un cliente con copia a `pilas@`. Excluir como solicitud nueva.
- Remitente nuevo que pide disponibilidad, ficha técnica o precio. Dar seguimiento si la petición es clara y sugerir registro de prospecto.
- Cliente existente que pregunta por otra obra. Abrir oportunidad independiente y vincular al cliente.

## Orden para retomar

| Prioridad | Trabajo pendiente | Verificación |
| --- | --- | --- |
| 1 | Persistir la asignación desde el modal, agregar una alerta y separar la evidencia de respuesta del estado de atención. | Recargar el hilo y comprobar que conserva responsable. Verificar que se genera una alerta por asignación y que un envío como el de las 13:17 cuenta como respuesta de Rivera. |
| 2 | Agregar clasificación manual, motivo de exclusión y recuperación. Cruzar contactos, habilitar prospectos y vincular Presupuestos. | Clasificar los casos del piloto y comprobar la trazabilidad del hilo, el contacto y el presupuesto. |
| 3 | Crear reglas editables y probar las sugerencias de GPT con una muestra etiquetada. | Comparar con la revisión humana y auditar también mensajes excluidos. Los hilos existentes no se reclasifican con solo repetir la sincronización. Definir cómo reevaluar una muestra ya registrada para comparar resultados. |
| 4 | Preparar el piloto operativo. Definir quién revisa, qué plazo tiene una solicitud y qué permisos tendrá cada rol. Establecer sincronización programada y seguimiento de errores antes de ampliar su uso. | Medir cuántas solicitudes reales omite el filtro, cuántas rebasan el plazo sin respuesta y cuánto correo ajeno llega a la lista comercial. Comparar con una muestra previa. |

## Cómo repetir la prueba local

El servidor local usado en el piloto sirve la pantalla en [Seguimientos](http://127.0.0.1:8000/seguimientos). Para revisar la conversación de prueba, abrir [hilo 7](http://127.0.0.1:8000/seguimientos?hilo=7). El número de hilo corresponde a la base local actual.

```powershell
php artisan mail-followup:test-connection
php artisan mail-followup:pilot-sync --limite=8
php artisan mail-followup:pilot-sync --limite=8 --guardar
php artisan test --filter=MailFollowupPilotTest
```

El primer comando comprueba acceso a ambas carpetas. El segundo lee y cuenta la muestra. El tercero la clasifica y persiste en el piloto local. El límite es **por carpeta**, de 1 a 20. La sincronización sigue siendo manual. La última corrida de las pruebas del piloto pasó **3 pruebas y 12 aserciones**.

En este entorno local se habilitó `MAIL_FOLLOWUP_PILOT_ENABLED=true` y se dejó `MAIL_FOLLOWUP_AI_ENABLED=false`. Los valores de ejemplo están en [`.env.example`](../.env.example). En otra base local, aplicar la [migración de seguimientos](../database/migrations/2026_11_24_000000_create_mail_followup_pilot_tables.php) antes de habilitar la persistencia. Las credenciales de Graph y la futura `OPENAI_API_KEY` se configuran en `.env`. La prueba de RBAC de Exchange validó el alcance de esa asignación. Para validar el acceso efectivo fuera del buzón piloto, revisar también los permisos de aplicación concedidos por separado en Entra.

### Archivos para continuar

- [Diagnóstico de conexión](../app/Console/Commands/MailFollowupTestConnection.php) y [comando de sincronización](../app/Console/Commands/MailFollowupPilotSync.php)
- [Cliente de Microsoft Graph](../app/Services/Mail/MicrosoftGraphFollowupClient.php)
- [Registro de hilos y eventos](../app/Services/Mail/MailFollowupPilot.php) y [clasificador](../app/Services/Mail/MailFollowupClassifier.php)
- [Controlador](../app/Http/Controllers/MailFollowupController.php) y [vista de Seguimientos](../resources/views/seguimientos/index.blade.php)
- [Pruebas del piloto](../tests/Feature/MailFollowupPilotTest.php)
