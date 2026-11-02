# Máquinas en gerencial: información de consulta

Fecha: 2026-10-30.

## Meta

Llevar la información de consulta de máquinas del panel mecánico al gerencial,
conservando el estilo gerencial: cabecera azul, acentos amarillos, tarjetas blancas
y navegación propia. Las operaciones del mecánico quedan fuera de este ajuste.

## Hallazgos

- `MaquinasGerencialController` ya delega en `MaquinasConsultaService` para listado y detalle.
- La API ya entrega ubicación, horómetro actual, fecha de última lectura y resumen preventivo.
- El listado móvil solo mostraba nombre y obra, infería ubicación desde la asignación
  y no ofrecía navegación a las siguientes páginas.
- Antes del checkpoint 2, la ruta `/tabs-gerencial/maquina-detalles/:id` apuntaba a una pantalla sin implementación.

## Checkpoint 1: contrato y listado — terminado

Archivos de la app móvil en `C:/xampp/htdocs/rivera/app/src/app/`:

- `models/maquina.model.ts`: tipos de preventivos y campos de consulta, incluyendo
  valores nulos y estado real del servidor.
- `services/gerencial-maquina.service.ts`: respuesta del listado tipada.
- `pages/gerencial-maquinas/gerencial-maquinas.page.ts`, `.html`, `.scss`:
  identidad, estado separado de ubicación, obra, horómetro actual, última lectura,
  preventivo prioritario con avance/metas y desplegable de todos los tipos.
  Paginación explícita con «Cargar más máquinas», conteo y reintento ante errores.
  Cancelación de consultas previas para evitar resultados de búsquedas desfasadas.

Se mantiene el filtro `en_uso`, el acceso al perfil y la ruta gerencial al detalle.
No se modificaron endpoints, permisos, reglas operativas ni registros de máquinas.

Verificación: compilación Angular development y revisión de mojibake correctas.
La compilación conserva advertencias ajenas al cambio, como un import IonFooter
sin uso en EmpleadoDetallesPage. Pruebas funcionales y revisión visual pendientes
del usuario, según lo acordado.

## Checkpoint 2: detalle — terminado

Implementado el contenido de `/tabs-gerencial/maquina-detalles/:id`, conservando
la cabecera azul, acentos amarillos, tarjetas blancas y acceso al perfil gerencial.

- Contrato `MaquinaDetalleResponse` acorde con el objeto anidado que entrega la API.
- Consulta mediante la ruta relativa `gerencial/maquinas/{id}`: se corrige la URL
  completa que antes se concatenaba otra vez con la base de `ApiService`.
- Resumen con código, nombre, marca/modelo/tipo, estado y ubicación separados,
  obra, horómetro actual y fecha de última lectura.
- Preventivo prioritario, avance, metas y desplegable de todos los preventivos.
- Asignación anterior pendiente de retiro identificada con una leyenda explícita,
  usando `asignacion_retiro` cuando no existe `asignacion_activa`, como en mecánico.
- Datos técnicos desplegables: placas, serie, color, horómetro base y notas.
- Servicios recientes (hasta los últimos diez entregados por la API), con estado,
  categoría, descripción, fecha, horómetro, mecánico y costo cuando están disponibles.
- Navegación de regreso, actualización, carga y errores con reintento.

Archivos: contrato y servicio anteriores, más los tres archivos de
`pages/gerencial-maquinas/maquina-detalles/`. El detalle reutiliza la hoja de
estilos del listado gerencial y agrega sus estilos particulares.

Verificación: compilación Angular development y revisión de mojibake correctas.
Pruebas funcionales y revisión visual a cargo del usuario. No se incorporaron
operaciones de asignación, captura de horas ni programación de servicios al panel
gerencial. Los enlaces y acciones del panel mecánico no se trasladan a esta vista.
