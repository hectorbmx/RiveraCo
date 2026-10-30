# Retiro al patio de asignaciones pendientes y acceso desde ubicación

Fecha: 2026-10-29. Implementación entregada; comprobaciones estáticas completas, pruebas funcionales a cargo del usuario.

## Regla acordada

AMK1 está fuera de servicio y conserva una asignación abierta a una obra terminada. Desde su ubicación se debe ofrecer únicamente «Pasar al patio», sin permitir una nueva obra. Confirmar el retiro cierra la asignación, cambia ubicación a patio y registra el movimiento/responsable. El estado fuera de servicio permanece.

La corrección habilita el flujo; no realiza el retiro de AMK1 ni de otras máquinas automáticamente. El usuario registrará el retiro al probar. Conserva las validaciones de fecha contra asignación y últimas lecturas, sin fabricar una fecha de cierre histórica.

## Backend

- `MaquinaAsignacionService::asignacionParaRetiro()` identifica una única asignación abierta por estado y fecha de fin, aunque su obra esté terminada/cancelada. Si existen varias abiertas no selecciona una arbitrariamente.
- Retiro móvil valida el contexto contra esa asignación abierta bajo bloqueo, reutilizando transacción, huella y finalización existente. No permite una nueva asignación por estar la obra anterior cerrada; la máquina debe seguir operativa y libre para asignarse.
- Opciones/detalle incorporan `asignacion_retiro`. Opciones entregan también `contexto_retiro`, separado del contexto previo de horas, para conservar su contrato de asignación vigente.
- `retirar_obra` se habilita con permiso efectivo y una asignación retirable; no exige que la obra siga vigente ni que la máquina esté operativa.
- La retirada web usa el mismo selector de asignación pendiente. No se habilita el PATCH directo para evitar la finalización; el retiro sigue siendo una operación explícita.

## Interfaz móvil

- La etiqueta de ubicación es un botón accesible desde listado y detalle, con indicación de apertura y foco de teclado.
- Abre un menú que consulta contexto/capacidades actuales antes de mostrar acciones. Máquina con asignación pendiente a obra cerrada muestra aviso; fuera de servicio no ofrece nueva obra.
- AMK1 ofrece Pasar al patio. Si falta el permiso de retiro, el botón aparece deshabilitado con explicación, sin ocultar la acción ni eludir la autorización.
- Máquinas operativas sin asignación pueden elegir Asignar a nueva obra, sujeto a capacidad del servidor. Con asignación vigente se mantiene retiro explícito antes de otra obra y, cuando corresponde, cambio directo D09.
- El menú se cierra antes de abrir el formulario correspondiente; reutiliza fecha/confirmación del retiro, errores y recuperación de envíos inciertos existentes.
- Listado y detalle conservan la obra pendiente al refrescar, sin convertir una asignación a obra terminada en máquina libre en el detalle.

## Permisos y pendientes

El usuario decidió registrar/asignar manualmente `baja_maquinas.access`. No se crean permisos ni se conceden accesos en esta implementación. Después de configurarlo, cerrar sesión e ingresar otra vez para renovar los permisos de la app. Cambiar ubicación D09 conserva su permiso separado; no es necesario para ejecutar un retiro explícito autorizado.

No se regularizaron registros ni se modificó el cierre automático de obra. Continúan pendientes su coordinación transaccional/conciliación y la autorización por obra en cambios del residente, documentadas en la revisión AMK1. El cierre final/grafo sigue pospuesto durante estos ajustes.

## Comprobaciones

- Sintaxis PHP de servicio, controladores y presentadores/consulta.
- TypeScript y compilación Angular de desarrollo completos; advertencias previas de otras pantallas/Stencil sin errores de compilación.
- UTF-8/mojibake y formato verificados; temporales retirados. Sin pruebas funcionales, capturas operativas, migraciones, despliegue ni cambios de usuarios/permisos.
