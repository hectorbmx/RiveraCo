# Detalle de vehículos en el panel mecánico

Fecha: 2026-10-30. Implementado en local; pruebas funcionales del usuario pendientes.

## Alcance

Se incorpora al panel mecánico la información del detalle gerencial de vehículos:
ficha, asignaciones, servicios, seguros y las dos fuentes de combustible, incluyendo
fotos, pólizas, comprobantes, odómetro y tickets. No requiere acceso gerencial.

La ruta móvil existente `/tabs-mecanico/vehiculos/:id` abre el detalle compartido
en modo mecánico. El listado continúa en su componente anterior y enlaza a la misma
URL. Máquinas, servicios y las operaciones existentes no cambian de ruta.

## API y autorización

Rutas GET nuevas dentro de `/api/v1/mecanico/vehiculos/{vehiculo}`:

- `/detalle`: ficha, asignación actual y póliza vigente.
- `/asignaciones`: historial paginado de asignaciones y fotos.
- `/servicios`: historial paginado de servicios.
- `/seguros`: historial paginado de seguros y archivos.
- `/combustible?fuente=registros`: kilometraje, gasolina, fotos y tickets.
- `/combustible?fuente=almacen`: salidas aplicadas del vehículo.

Las consultas requieren Sanctum y `EnsureMobileAppAccess:mecanico`, como el resto
del módulo. Ese middleware conserva la validación del acceso móvil y permiso de
mecánico; no se crean permisos ni se asigna acceso gerencial al usuario.

Se reutiliza `VehiculosHistorialConsultaService`: mismos filtros, tipos, paginación
independiente y pertenencia al vehículo definidos en
[CONTRATO_DETALLE_VEHICULOS_GERENCIAL.md](CONTRATO_DETALLE_VEHICULOS_GERENCIAL.md).

La extensión de acceso a seguros, comprobantes e historiales corresponde a la
solicitud expresa de llevar estos detalles al mecánico. El endpoint original
`GET /api/v1/mecanico/vehiculos/{vehiculo}` y su proyección de T04 permanecen
intactos. La ficha ampliada solo publica vehículo, asignación actual y póliza
vigente; no devuelve documentos administrativos ni resúmenes ajenos a la pantalla.
Las asignaciones históricas no exponen teléfono ni puesto del empleado.

## Integración móvil

- `VehiculoDetalleConsultaService` comparte métodos tipados y recibe un panel
  explícito `gerencial` o `mecanico`. La ficha mecánica consulta `/detalle`; los
  historiales consultan únicamente el prefijo del panel correspondiente.
- `VehiculosGerencialService` conserva sus métodos existentes y delega los
  historiales al servicio común. Su ficha original sigue con su contrato anterior.
- DTO `VehiculoFichaDetalleDto`: campos compartidos realmente usados en la ficha;
  el detalle completo gerencial conserva su DTO anterior.
- Se reutiliza `VehiculoDetallesPage`, con modo definido por los datos de ruta.
  Gerencial conserva su cabecera y diseño. Mecánico usa toolbar con volver,
  actualizar, acceso entre paneles cuando existe y salida con confirmación.
- Historiales mecánicos con cabeceras azules y texto blanco; mismos desplegables,
  fechas, estados, paginación, carga, errores y visor de archivos.
- Se conserva «Programar servicio» cuando existe empleado asociado. Los servicios
  de tipo `programado` enlazan a su detalle en mecánico, como en la pantalla anterior.
- No se realizan capturas, cargas de archivos ni modificaciones de datos al abrir
  esta pantalla. La separación entre gasolina y salidas de almacén se conserva.

## Verificación y checkpoints

- [x] Rutas nuevas y proyección ampliada sin modificar el endpoint original.
- [x] Servicio móvil compartido, navegación y presentación por panel.
- [x] Historiales paginados, fotos, pólizas, comprobantes y tickets reutilizados.
- [x] Sintaxis PHP, rutas con middleware, compilación Angular y revisión de UTF-8.
- [ ] Pruebas funcionales del usuario con cuenta solo mecánico: navegar desde el
  listado, abrir los historiales y archivos, paginar, filtrar y programar servicio.
- [ ] Revisión visual en navegador y dispositivo, incluyendo regreso al listado y
  comprobación de que gerencial conserva su presentación.

No se ejecutaron pruebas funcionales automáticas, migraciones ni cambios de datos.
La compilación conserva advertencias previas de otras pantallas y Stencil.

## Ajuste visual — 2026-11-02

La primera ficha del panel mecánico se puede ocultar o mostrar pulsando su franja
azul. La cabecera muestra el nombre del vehículo (marca, modelo y año), con flecha
y estado accesible de expansión. Inicia abierta y conserva sus datos al plegarse.
El panel gerencial mantiene la ficha estática anterior. Compilación Angular y
revisión de mojibake correctas; comprobación visual a cargo del usuario.

## Listado con tarjetas — 2026-11-02

El índice `/tabs-mecanico/vehiculos` utiliza tarjetas plegables del mismo estilo
que máquinas: franja azul, placas, badge de estado y flecha; todas inician cerradas.
Al desplegar aparecen marca/modelo/año, tipo/color, empleado asignado, serie y
datos de la asignación que ya entrega la API. «Km al asignar» identifica la lectura
inicial y no se presenta como odómetro actual.

El botón «Ver detalle» conserva el acceso a `/tabs-mecanico/vehiculos/:id`.
Búsqueda, paginación, pull to refresh y loaders continúan en la pantalla original.
No se agregan consultas por tarjeta ni se alteran endpoints, datos o permisos.

La revisión visual posterior del mismo día cambia las franjas de las tarjetas
por filas blancas compactas y coloca el azul en la cabecera del índice, siguiendo
la referencia del usuario. Véase
[ESTILO_INDICES_EQUIPOS_MECANICO.md](ESTILO_INDICES_EQUIPOS_MECANICO.md).

Archivos nuevos: `mecanico-vehiculo-card.component.ts`, `.html`, `.scss`; se
reutiliza la hoja de estilos de la tarjeta de máquinas. El DTO de consulta incorpora
los campos de vehículo que ya estaban en la respuesta. Compilación Angular y
mojibake verificados; prueba visual y navegación a cargo del usuario.
