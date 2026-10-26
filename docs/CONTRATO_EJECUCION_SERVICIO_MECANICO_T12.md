# T12: ejecución de servicios programados

D05 confirmada el 2026-10-26 (America/Mexico_City): permitir iniciar con materiales pendientes; completar solo con todo surtido. Sin materiales incluidos no hay bloqueo. D04 conserva cancelación exclusivamente web.

## API

POST `/api/v1/mecanico/servicios/{id}/iniciar`: `fecha_inicio` requerida. Desde orden_generada o pendiente con folio, pasa a en_proceso. No exige surtido.

POST `/api/v1/mecanico/servicios/{id}/completar`: `fecha_fin` requerida y posterior al inicio guardado; lectura final requerida `horometro` para máquina o `km_actuales` entero para vehículo; `notas` opcionales hasta 5000 caracteres. Desde en_proceso pasa a completado. La lectura no retrocede respecto del servicio; maquinaria conserva además la validación del consolidado. No se modifica el medidor del equipo.

Ambas requieren sesión activa, app.mecanico.access y empleado propio existente, sin obra. Respuesta 200 `{ok:true,data:detalle}`. 403 ajeno, 404 no programado/inexistente, 409 transición incompatible o materiales pendientes, 422 payload/fechas/lectura inválidos. Campos desconocidos, costos, actor y equipo rechazados. Repetición idéntica devuelve el resultado sin recalcular costos; payload diferente en estado destino devuelve 409.

Las transacciones bloquean y recargan el mantenimiento. El cierre agrupa requerimientos incluidos por producto y compara cantidades atendidas de sus solicitudes, la misma fuente del abastecimiento actual. Material sin producto y cantidad positiva bloquea; excluido no bloquea; filas repetidas no reutilizan la misma cantidad atendida. Stock disponible o solicitud emitida no equivalen a surtido.

El cálculo automático de mano de obra existente se comparte entre web y API: duración, sueldo/período y jornada de ocho horas. Sin configuración salarial válida conserva su comportamiento previo, sin inventar tarifas. No se exponen sueldos. El cierre sirve como referencia a los cálculos preventivos existentes, sin crear otra programación.

## Web y alcance

La web comparte la validación de ejecución de programados y D05 bajo bloqueo, también al guardar fotografías. Conserva permisos, emergencias y cancelación web. No se publica cancelación móvil. Las banderas iniciar/completar reflejan propiedad, estado, folio y surtido; el servidor siempre revalida. La integración móvil corresponde a C13.5 después de la pausa.

## Validación T12 (2026-10-26)

- Suite `AppMobile`: 99 pruebas, 959 comprobaciones exitosas con SQLite en memoria. Incluye diez pruebas nuevas de ejecución y conserva las anteriores.
- HTTP API/web: inicio con faltantes, cierre bloqueado/surtido, servicio sin incluidos, vehículo sin plantilla, validación de fechas y lecturas, propiedad/permiso/empleado/tipo, payload protegido y reintento sin recalcular costos.
- Material repetido: avance y cierre no reutilizan cantidades atendidas. Producto sin vínculo bloquea; material excluido no bloquea. Se ajustó también el cálculo compartido de avance y la bandera para solicitar faltantes.
- Fallo SQL en mano de obra y callback web: rollback de estado, lectura y fecha final. La web conserva reasignación a empleado existente y evita retroceder una ejecución iniciada.
- Calculador preventivo real de maquinaria: próximo horómetro pasa de 250 a 360 tras completar a 110 con intervalo 250. Sin escrituras en el medidor del equipo. Fechas UTC y con offset equivalente verificadas.
- Sintaxis PHP, inventario de rutas (inicio/cierre presentes; cancelación móvil ausente) y UTF-8 validados. La configuración salarial ausente conserva costos sin inventar tarifas.

El recorrido conectado con usuario y dispositivo/cámara y las carreras específicas de ejecución con surtido pertenecen a T14; estas pruebas no sustituyen esa validación integral. C13.5 incorporó las pantallas de inicio/cierre el 2026-10-26; su validación controlada está registrada en IMPLEMENTACION_EJECUCION_MOVIL_MECANICO_C13_5.md. No se realizó commit ni push final.
