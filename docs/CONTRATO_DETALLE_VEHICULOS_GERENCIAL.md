# Contrato de consulta de vehículos en gerencial

Fecha: 2026-10-30.
Estado: pasos 1 a 4 terminados en local; pruebas funcionales del usuario pendientes.
Las rutas nuevas están implementadas en código local. No se ha realizado despliegue.

## Objetivo y alcance

Dar contenido al detalle móvil de vehículos con ficha general e historiales de
asignaciones, servicios, seguros y combustible. Mantener el estilo gerencial y
consultar los datos existentes en Laravel. Este alcance es de lectura; no agrega
capturas, modificaciones, nuevas tablas ni permisos operativos del mecánico.

Ruta móvil implementada: `/tabs-gerencial/vehiculos/:id`.
Conectada desde el listado en el paso 3.

## Hallazgos y fuentes

| Información | Fuente actual | Situación |
|---|---|---|
| Ficha y resumen | `VehiculosConsultaService::show` | Ya existe en la API gerencial. |
| Asignaciones | `Vehiculo::asignaciones`, `VehiculoEmpleado`, fotos | El detalle devuelve las últimas 10. |
| Servicios | `Vehiculo::mantenimientos`, `Mantenimiento` | El detalle devuelve los últimos 10 y un resumen de estados. |
| Seguros | `Vehiculo::seguros`, relación polimórfica `Seguro` | El detalle devuelve los últimos 10 y una póliza vigente. |
| Kilometraje y gasolina | `VehiculoEmpleadoKmLog` | Existe en Laravel; falta consulta gerencial por vehículo. |
| Salidas de almacén | `InventarioDocumentoDetalle`, documento y producto | Laravel muestra salidas aplicadas del vehículo; no filtra exclusivamente combustible. |

Referencias de implementación:

- `app/Services/Mobile/Equipos/VehiculosConsultaService.php`.
- `app/Http/Controllers/Api/V1/Gerencial/VehiculosGerencialController.php`.
- `app/Http/Controllers/VehiculoController.php`, método `edit`.
- `resources/views/vehiculos/edit.blade.php`.
- App móvil: `src/app/models/vehiculo.model.ts` y
  `src/app/services/gerencial-vehiculo.service.ts`.

El endpoint operativo `vehiculos/km-log` consulta el contexto del residente, su
asignación y su obra. El detalle gerencial no dependerá de ese contexto: debe
consultar el vehículo completo, incluyendo asignaciones anteriores.

## Acceso

- Bearer de Sanctum y `Accept: application/json`.
- Mismos controles del grupo gerencial existente: acceso móvil gerencial activo
  y permiso efectivo `app.gerencial.access`.
- No requiere que el gerente tenga empleado, vehículo u obra asignados.
- Todas las consultas se limitan al vehículo de la URL; un filtro de historial
  nunca permite obtener registros de otro vehículo.
- No amplía la respuesta del panel mecánico ni reutiliza sus permisos de operación.

## Rutas GET

Base: `/api/v1/gerencial/vehiculos`.

| Ruta relativa a la base | Estado | Propósito |
|---|---|---|
| `/{vehiculo}` | Existente; conservar | Ficha y resúmenes actuales. |
| `/{vehiculo}/asignaciones` | Implementada en local | Historial completo paginado de asignaciones. |
| `/{vehiculo}/servicios` | Implementada en local | Historial completo paginado de servicios. |
| `/{vehiculo}/seguros` | Implementada en local | Historial completo paginado de seguros. |
| `/{vehiculo}/combustible?fuente=registros` | Implementada en local | Lecturas de kilometraje y registros de gasolina. |
| `/{vehiculo}/combustible?fuente=almacen` | Implementada en local | Salidas aplicadas de inventario vinculadas al vehículo. |

`fuente` es obligatorio en combustible; solo admite `registros` y `almacen`.
Cada fuente tiene su propia página, total y tipo de registro. No habrá un arreglo
mezclado ni una opción `todos` en esta primera implementación.

El servicio móvil usará rutas relativas para `ApiService`, por ejemplo
`gerencial/vehiculos/15/servicios`, sin volver a concatenar la URL base.

## Paginación y filtros

Aplica a las cuatro rutas nuevas de historial:

- `page`: entero positivo, predeterminado 1.
- `per_page`: entero de 1 a 50, predeterminado 20.
- `desde`, `hasta`: fechas civiles opcionales `YYYY-MM-DD`, inclusivas.
- Si se envían ambas, `hasta` debe ser mayor o igual que `desde`.
- Valores inválidos devuelven 422. No se convierten silenciosamente en otro filtro.
- La paginación se aplica en la base de datos; no se carga todo el historial para
  paginarlo en memoria.

| Historial | Fecha para filtrar | Orden descendente estable | Filtro adicional |
|---|---|---|---|
| Asignaciones | `fecha_asignacion` | `fecha_asignacion`, `id` | Ninguno. |
| Servicios | `fecha_programada` | `fecha_programada`, `id` | `estatus`, validado contra estados admitidos por el módulo. |
| Seguros | `vigencia_hasta` | `vigencia_hasta`, `id` | `estatus`: `futura`, `vigente`, `vencida`, `cancelada`. |
| Combustible: registros | `fecha` del log | `fecha`, `id` | Ninguno. |
| Combustible: almacén | `documento.fecha` | `documento.fecha`, `documento_id`, `detalle.id` | Ninguno. |

Los filtros de fecha se refieren exclusivamente a la columna indicada. En seguros
filtran fecha de vencimiento, no solapamiento de vigencias. Con un filtro de fecha,
un registro sin esa fecha queda fuera; sin filtro puede aparecer al final.

Los cambios de filtro reinician la página a 1. No se promete una fotografía fija
entre solicitudes: pueden aparecer registros nuevos mientras se consulta.

### Respuesta común de historial

`data` es un arreglo directo; no usar el paginador anidado del listado antiguo.

```json
{
  "ok": true,
  "data": [],
  "meta": {
    "vehiculo_id": 15,
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 0
  }
}
```

En combustible se agrega `meta.fuente` con el valor solicitado. `total` cuenta
todos los registros que cumplen filtros, no solo la página visible. Una página
fuera de rango devuelve 200 con `data: []` y la metadata real, sin repetir registros.

## Compatibilidad del detalle existente

Conservar `ok` y estos campos de `data`, con su estructura y tipos existentes:

- `vehiculo`: `id`, `marca`, `modelo`, `anio`, `color`, `placas`, `serie`, `tipo`,
  `estatus`, `fecha_registro`, `foto_url`.
- `asignacion_actual`: objeto de asignación o `null`.
- `asignaciones_recientes`: hasta 10; no representa el historial completo.
- `seguro_vigente`: objeto de seguro o `null`.
- `seguros_recientes`: hasta 10.
- `mantenimientos_recientes`: hasta 10.
- `mantenimientos_resumen`: `total`, `pendiente`, `en_proceso`, `completado`, `cancelado`.
- `documentos` y `documentos_resumen`: conservar; no eliminarlos aunque la primera
  pantalla no muestre un historial documental independiente.

La nueva UI consultará los historiales paginados al abrir cada sección. No debe
mezclar las listas recientes del detalle con la primera página del historial.
El contrato móvil necesita un DTO de detalle propio: no equivale al DTO del listado.

## Contrato de los registros nuevos

Todos los identificadores son enteros. Los números de los nuevos historiales se
serializan como números JSON, sin separadores ni símbolos monetarios. Si falta el
dato, se usa `null`, nunca cero inventado. El detalle existente conserva sus tipos
para no romper consumidores (algunas lecturas se reciben como cadena numérica).

### Asignación

- `id`, `vehiculo_id`: entero.
- `empleado_id`: entero o `null`.
- `fecha_asignacion`, `fecha_fin`: fecha civil o `null`.
- `km_inicial`, `km_final`: número o `null`.
- `notas`: texto o `null`.
- `empleado`: `null` o `{id, nombre, puesto, celular}`; `puesto` y `celular` admiten `null`.
- `fotos`: arreglo de `{id, orden, url}`; `orden` y `url` admiten `null`.

Incluir asignaciones abiertas y cerradas. La asignación actual conserva la regla
existente: sin `fecha_fin`, ordenada por fecha de asignación. No inferir una baja
del vehículo a partir del cierre de una asignación.

### Servicio

- `id`: entero.
- `tipo`, `categoria_mantenimiento`, `descripcion`, `estatus`, `notas`: texto o `null`.
- `km_actuales`, `km_proximo_servicio`, `horometro`, `costo_total`: número o `null`.
- `fecha_programada`: fecha civil o `null`.
- `fecha_inicio`, `fecha_fin`: fecha y hora con zona o `null` en la ruta nueva.
- `mecanico`: `null` o `{id, nombre}`.

Conservar todos los tipos y estados de mantenimiento del vehículo; sin filtro de
estado no se ocultan borradores, pendientes ni cancelados. No publicar un nombre
de servicio preventivo que la relación actual no haya proporcionado ni confundir
el tipo de mantenimiento con la descripción manual.

### Seguro

- `id`: entero.
- `aseguradora`, `poliza_numero`, `tipo_seguro`, `metodo_pago`, `frecuencia_pago`,
  `moneda`, `cobertura`, `observaciones`: texto o `null`.
- `fecha_compra`, `vigencia_desde`, `vigencia_hasta`: fecha civil o `null`.
- `costo`, `suma_asegurada`, `deducible`: número o `null`.
- `estatus`: estado calculado con las reglas existentes de seguros.
- `alerta_vencimiento_activa`: booleano; `dias_preaviso`: entero o `null`.
- `documento_url`, `comprobante_url`: URL o `null`.

Conservar la semántica de vigencias y cancelación del módulo actual; no determinar
la póliza vigente a partir de la página que esté abierta. Un historial sin filas
no significa que el vehículo no tenga póliza vigente fuera de los filtros.

### Combustible: registros de kilometraje y gasolina

- `id`, `vehiculo_id`: entero; el segundo es el vehículo resuelto por la consulta.
- `vehiculo_empleado_id`: entero o `null`.
- `origen`: valor almacenado (`app`, `web`, `web_residente`, otro legado) o `null`.
- `fecha`: fecha y hora con zona o `null`.
- `km`, `monto_gasolina`: número o `null`.
- `notas`: texto o `null`.
- `obra`: `null` o `{id, nombre, clave_obra}`; la clave admite `null`.
- `empleado`: `null` o `{id, nombre}`, tomado de la asignación de ese registro.
- `capturado_por`: `null` o `{id, nombre}`; no exponer el objeto completo de usuario.
- `foto_url`, `foto_ticket_gasolina_url`: URL o `null`.

Reglas de selección:

1. Consultar logs no eliminados de todas las asignaciones del vehículo, no solo
   la actual y sin limitar por la obra activa del usuario.
2. Usar `vehiculo_id` directo cuando esté informado; para registros legados sin
   ese campo resolver el vehículo mediante la asignación. No duplicar un log que
   coincida por ambos caminos. Un ID directo de otro vehículo no se debe incluir
   por una asignación contradictoria.
3. Incluir lecturas sin importe o sin ticket. `monto_gasolina: null` significa sin
   importe registrado; no equivale a una compra de cero pesos.
4. No inventar litros, precio por litro ni rendimiento: esos campos no existen
   en este modelo. `km` es lectura de odómetro, no kilómetros recorridos.

### Combustible: salidas de almacén

- `id`: ID del detalle de inventario; `vehiculo_id`: entero.
- `documento`: `{id, folio, fecha, tipo, estado}`; fecha civil o `null`, folio admite
  `null`; `tipo` siempre `salida` y `estado` siempre `aplicado`.
- `producto`: `null` o `{id, nombre, sku}`; nombre y SKU admiten `null`.
- `cantidad`, `costo_unitario`: número o `null`.
- `obra`: `null` o `{id, nombre, clave_obra}`.
- `notas`: texto o `null`.

Filtrar `detalle.vehiculo_id` por el vehículo solicitado y exigir documento de
salida aplicado, no eliminado. Excluir entradas, borradores y cancelaciones.
Mantener una fila por detalle, aunque el mismo producto aparezca en varios folios.
Mostrar el nombre «Salidas de almacén»: los registros pueden ser materiales de
otra clase. No etiquetar todas las cantidades como litros.

No sumar importes de logs con costos de salidas, ni deducir automáticamente que
una salida y un ticket son compras diferentes. No hay una conciliación entre
ambas fuentes en este alcance. Tampoco calcular totales globales con una sola
página. La gráfica mensual de Laravel queda fuera de esta primera entrega.

## Fechas, evidencias y respuestas vacías

- Fechas civiles: `YYYY-MM-DD`; la app las muestra sin desplazamiento de zona.
- Instantes de las rutas nuevas: ISO 8601 con zona explícita. Normalizar usando la
  zona de origen configurada en Laravel; no añadir `Z` a una hora local.
- En el detalle heredado conservar la serialización actual hasta una migración
  de contrato explícita; los nuevos DTO no deben asumir que toda fecha lleva zona.
- URL de imagen/documento: URL utilizable desde el dispositivo y `null` si no hay
  archivo referenciado. Resolver rutas de Storage con la configuración existente,
  sin exponer rutas físicas locales ni cambiar la política actual de archivos.
- Historial vacío: `data: []`, `total: 0`. Relaciones ausentes: `null`. La UI debe
  distinguir sin registros de un error de consulta y de datos sin capturar.

## Errores

- 401: sesión no autenticada.
- 403: sin acceso al panel gerencial.
- 404: vehículo inexistente según el binding existente.
- 422: filtros inválidos, con `message` y errores por campo.
- 500: error de servidor, sin convertirlo en un historial vacío exitoso.

Mantener el formato y comportamiento de autenticación/autorización del grupo
gerencial actual. No mostrar al usuario mensajes internos de SQL o rutas de archivos.

## Checkpoints y siguiente ejecución

- [x] Paso 1: fuentes revisadas; rutas, filtros, tipos, compatibilidad y reglas de
  combustible definidos en este documento.
- [x] Paso 2: rutas nuevas y consultas/mapeadores gerenciales implementados;
  se mantienen el detalle y los consumidores actuales. Sin migración.
- [x] Paso 3: contratos TypeScript, servicio móvil, navegación desde el listado y
  pantalla de detalle con secciones independientes y paginación.
- [x] Paso 4: presentar fotos, pólizas y tickets que ya incluya el contrato.
- [ ] Pruebas funcionales del usuario con historiales largos, registros sin
  importe/ticket, asignaciones cerradas y cada fuente de combustible.

Las verificaciones posteriores deben comprobar pertenencia al vehículo, acceso
gerencial, fechas y paginación; los historiales no deben quedar truncados a diez
ni mezclar fuentes o presentar una falla de red como «sin registros».

## Cierre del paso 2 — 2026-10-30

- Nuevo `VehiculosHistorialConsultaService`: validación de parámetros, pertenencia
  al vehículo, filtros en SQL, orden estable y paginación con metadata independiente.
- Se agregan cuatro métodos al controlador gerencial y cuatro rutas GET, dentro
  del grupo protegido ya existente. No se crean permisos ni se cambia su asignación.
- Se reutilizan los mapeadores de asignación, servicio y seguro de
  `VehiculosConsultaService`, haciendo públicos esos tres métodos sin cambiar sus
  respuestas actuales. La normalización numérica y las fechas ISO de servicios se
  aplican exclusivamente a las respuestas del servicio nuevo.
- El filtro de seguros se aplica antes de paginar y conserva la prioridad de la
  clasificación actual: cancelada, futura, vencida y vigente.
- Combustible resuelve primero `vehiculo_id` directo; el respaldo por asignación
  solo se usa cuando ese ID es nulo. Si existe una asignación contradictoria en un
  log directo, su empleado no se expone como responsable de este vehículo.
- Las salidas de almacén se ordenan por la fecha del documento y conservan el
  alcance de salidas aplicadas no eliminadas, sin inferir litros o combustible.
- No se ejecutaron migraciones ni se modificaron registros operativos. La app
  móvil todavía no consume estos historiales; eso corresponde al paso 3.

Verificaciones realizadas: `php -l` de los cuatro archivos PHP tocados,
`php artisan route:list --path=api/v1/gerencial/vehiculos -v` (seis rutas,
incluyendo las dos anteriores, con Sanctum y controles gerenciales) y revisión
de mojibake. Pruebas funcionales pendientes del usuario según lo acordado.

## Cierre del paso 3 — 2026-10-30

- El listado abre `/tabs-gerencial/vehiculos/:id`. Conserva sus separadores y estilo.
- DTO de detalle separado del listado; tipos de servicios y de las dos fuentes de
  combustible, metadata y filtros; se conservan los tipos numéricos heredados de
  las listas recientes en la respuesta antigua.
- Métodos tipados de `VehiculosGerencialService` con rutas relativas: detalle,
  asignaciones, servicios, seguros, registros de gasolina y salidas de almacén.
- Pantalla `pages/gerencial-vehiculos/vehiculo-detalles/vehiculo-detalles.page.*`
  con ficha general, empleado actual, póliza vigente, retorno al listado,
  actualización y estados de carga/error.
- Secciones desplegables que consultan al abrirse; cada historial conserva sus
  propios datos, página, fechas, filtros, carga y error. Combustible agrupa dos
  secciones independientes: kilometraje/gasolina y salidas de almacén.
- Componente `vehiculo-historial.component.*` para filtros, conteo, navegación y
  reintentos. Servicios y seguros incluyen los filtros de estado del contrato.
  En seguros, las fechas se etiquetan como vencimiento para evitar ambigüedad.
- Cambiar filtros reinicia a la página 1. Cada reintento usa la página solicitada;
  actualizar o salir cancela solicitudes anteriores. El error se muestra sin
  convertirlo en un historial vacío.
- Se reutiliza la hoja de estilos gerencial de vehículos y se agregan estilos
  específicos de ficha e historiales, con controles adaptables al ancho móvil.
- Los importes nulos se muestran como no capturados; cero sigue siendo cero.
  No se calculan litros ni se suman los costos de las dos fuentes.
- Al cerrar el paso 3, las URLs de fotos, pólizas y tickets quedaron tipadas para
  implementar su presentación en el paso 4.

Verificaciones: compilación Angular development de la app, revisión de mojibake
y formato del diff. La compilación mantiene advertencias preexistentes de otros
componentes y Stencil. No se realizaron pruebas funcionales ni cambios de datos;
la validación del flujo queda a cargo del usuario.

## Cierre del paso 4 — 2026-10-30

- Nuevo componente móvil `vehiculo-adjunto.component.*`, reutilizado en el detalle.
- Foto principal del vehículo cuando existe, fotos de cada asignación histórica,
  foto del odómetro y ticket de cada registro de gasolina.
- Miniaturas con carga diferida, modal de ampliación, ajuste a pantalla o tamaño
  original y enlace para abrir el archivo. Incluye estados de carga e imagen fallida.
- Botones para abrir póliza y comprobante del seguro vigente y de cada seguro del
  historial, mediante los enlaces que ya entrega la API. Se abren en otra ventana
  o mediante el comportamiento del navegador/dispositivo para ese archivo; no se
  instala un visor PDF ni un plugin nativo adicional.
- Ausencia de archivo y fallo de imagen tienen mensajes distintos; no se inventan
  evidencias ni se realizan cargas, descargas de respaldo o cambios en Storage.
- Las URLs relativas se resuelven contra la base de Laravel configurada en el
  entorno; las absolutas HTTP/HTTPS se conservan. No se admiten otros esquemas.
- Cambios limitados a componentes del detalle gerencial y este documento. La API,
  los permisos y los datos operativos permanecen con el alcance del paso 2.

Verificación: compilación Angular development y revisión de mojibake correctas.
Pendiente del usuario: abrir fotos y archivos reales tanto en navegador como en
el dispositivo móvil, incluyendo archivos faltantes y pólizas PDF. Los cuatro
pasos de implementación están terminados; esta comprobación funcional sigue abierta.
