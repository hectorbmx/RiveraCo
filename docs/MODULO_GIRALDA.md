# Modulo: GIRALDA

**Fecha de creacion del documento:** 04 de agosto de 2026  
**Estado:** En definicion e implementacion por etapas  
**Modulo:** GIRALDA  
**Alcance inicial:** almacen, centro de costos y control operativo de personal asignado a Giralda.

## Regla principal de trabajo

Antes de construir o modificar cualquier pieza del modulo GIRALDA se debe revisar primero el codigo existente y la documentacion del proyecto. La intencion es no inventar procesos desde cero si ya existe una base funcional en V2.

Fuentes obligatorias de revision:

- Grafo del proyecto con `graphify query`, `graphify path` o `graphify explain`.
- Documentos existentes en `docs/`.
- Reportes y referencias del grafo cuando apliquen, especialmente `docs/REFERENCIA_GENERAL_GRAFO.md` y `graphify-out/graph.json`.
- Controladores, modelos, migraciones, vistas, permisos y servicios ya existentes.

Principio de reutilizacion:

- Reusar patrones de rutas, permisos y menu ya existentes.
- Reusar vistas y componentes Blade cuando el flujo sea similar.
- Reusar modelos existentes como `Empleado`, `Area`, `Obra`, `OrdenCompra` e inventario.
- Reusar servicios existentes para trazabilidad, documentos y movimientos de inventario.
- Evitar decrementos manuales de stock o procesos paralelos que rompan auditoria.

## Contexto funcional

GIRALDA representa dos cosas dentro de la operacion:

- Es un almacen.
- Es un centro de costos / area operativa con cierta independencia.

Aunque GIRALDA tendra procesos propios, estos siguen relacionados con la empresa. Por eso el modulo debe funcionar como una entrada independiente en el menu, pero conectado a los flujos centrales: empleados, inventario, ordenes de compra, obras, areas y permisos.

Estructura deseada de navegacion:

- `Giralda -> Empleados`
- `Giralda -> Ordenes compra`
- `Giralda -> Almacen`

El personal de Giralda no debe usar la lista general de empleados en `/empleados`. Debe entrar a una lista filtrada para Giralda, basada en los mismos datos de empleados, pero limitada a personal cuya area sea Giralda.

## Alcance inicial

### 1. Empleados Giralda

Vista base:

- Ruta objetivo: `/giralda/empleados`
- Mostrar una lista filtrada de empleados de Giralda.
- Filtro por estatus: activos / baja / todos.
- No usar filtros por fecha en el listado general de empleados, porque no aportan valor ahi.

Acciones por empleado:

- Registrar horas extras.
- Agregar entrega de EPP.
- Ver notas.
- Ver fotos.
- Consultar historial de EPP.
- Consultar historial de horas extras.

El enfoque definido es mantener la lista de empleados como pantalla principal y abrir modales para acciones rapidas, sin obligar al usuario a entrar a la ficha general del empleado para tareas operativas simples.

### 2. Entrega de EPP

Objetivo:

- Llevar un historial de EPP entregado a cada empleado.
- Conectar el articulo entregado con inventario.
- Mantener trazabilidad formal mediante documentos y movimientos de inventario.

Articulos iniciales:

- Botas.
- Cascos.
- Chalecos.
- Guantes.
- Lentes.
- Otros equipos configurables desde inventario.

Datos por entrega:

- Empleado.
- Articulo de inventario.
- Cantidad.
- Talla, cuando corresponda.
- Fecha de entrega.
- Condicion del equipo.
- Area.
- Obra.
- Usuario que entrega.
- Observaciones.

Decision tomada:

- `Area` y `Obra` deben estar separados.
- `Obra` debe ser un select con obras activas.
- Las obras terminadas y canceladas deben quedar fuera.
- El check "Confirmado por empleado" no se usara por ahora; se podra recuperar en una etapa posterior si se implementa firma o confirmacion digital.

Punto critico pendiente:

- Las entregas de EPP no deben quedarse solo como registros historicos.
- Cada entrega debe afectar inventario usando la trazabilidad existente.
- No se debe descontar stock directo desde EPP si ya existe un flujo formal de documentos/movimientos.

### 3. Horas extras

Objetivo:

- Centralizar el control operativo de horas extras del personal de Giralda.

Datos por registro:

- Empleado.
- Fecha.
- Hora inicial.
- Hora final.
- Total de horas.
- Motivo.
- Responsable que solicita.
- Responsable que autoriza.
- Observaciones.
- Estatus de autorizacion.

Vista objetivo:

- `/giralda/empleados?tab=horas_extras`
- Mantener la lista de empleados.
- Abrir modal por empleado para registrar horas.
- Permitir historial por empleado.
- Filtrar horas extra por periodo dentro del tab o reporte correspondiente.
- Generar formato requerido.
- Exportar o imprimir.

### 4. Ordenes de compra Giralda

Objetivo:

- Tener una entrada propia de ordenes de compra para Giralda.
- Reusar el flujo existente de ordenes de compra.
- Aplicar prefijo o identificador propio de Giralda.
- Asociar el gasto al centro de costos correspondiente.

Pendiente por definir:

- Si el prefijo vive en folio, serie, campo extra o regla del controlador.
- Que permisos tendra Giralda para crear, ver, autorizar o dar seguimiento a OC.
- Si Giralda solo vera sus OC o tambien referencias cruzadas con compras generales.

### 5. Almacen Giralda

Objetivo:

- Mover o agrupar la entrada de almacen bajo `Giralda -> Almacen` para reducir espacio en navbar.
- Reusar el modulo de inventario existente.
- Mantener documentos, stock, movimientos y trazabilidad sin duplicar logica.

Pendiente por definir:

- Identificar el almacen Giralda en las tablas actuales.
- Decidir si el acceso debe filtrar por almacen Giralda.
- Revisar si hay operaciones compartidas con otros almacenes.

## Avance actual registrado

Se ha trabajado una primera base del modulo:

- Controlador principal de Giralda.
- Vista principal del modulo.
- Vista de empleados Giralda con tabs.
- Tab `Listado`.
- Tab `Horas extras`.
- Tab `EPP`.
- Modal para registrar horas extras.
- Modal para registrar entrega de EPP.
- Modal para historial EPP por empleado.
- Relacion de empleados con entregas EPP.
- Relacion de empleados con horas extras Giralda.
- Separacion de `Area` y `Obra` en entrega EPP.
- Carga de obras activas, excluyendo terminadas y canceladas.
- Buscador de articulos conectado a inventario para EPP.
- Registro historico de EPP por empleado.
- Conteo de entregas EPP con accion para abrir historial.
- Rutas iniciales `giralda.*`.
- Menu desplegable GIRALDA.

Observaciones del avance:

- Ya existe un flujo visual util para registrar EPP y horas extra desde la lista.
- El historial EPP ya muestra entregas por empleado.
- Hubo registros iniciales donde obra/area no aparecian en historial; esto se debe considerar para backfill o tolerancia visual de registros antiguos.
- Falta confirmar permisos reales para que GIRALDA aparezca en navbar a los perfiles correctos.
- Falta conectar EPP con movimientos/documentos de inventario.

## Checkpoint tecnico: inventario y trazabilidad

El siguiente bloque de trabajo debe iniciar revisando el flujo actual de inventario.

Archivos y conceptos a revisar con grafo antes de tocar codigo:

- `App\Http\Controllers\Inventario\InventarioDocumentoController`
- `App\Services\Inventario\InventarioDocumentoService`
- Modelos de documento de inventario.
- Modelos de detalle de documento.
- Modelo de stock.
- Modelo de movimientos.
- Migraciones de `inventario_documentos`, `inventario_documento_detalles`, `inventario_stock` e `inventario_movimientos`.
- Rutas de inventario.
- Vistas de documentos de inventario.

Preguntas que debe responder la revision:

- Como se crea una salida formal de inventario hoy.
- Que campos son obligatorios en documento y detalle.
- Donde se calcula o valida stock.
- Como se registra el movimiento en `inventario_movimientos`.
- Como se mantiene el costo o valor de inventario.
- Que folios o documentos se generan.
- Que relaciones existen para auditoria.
- Si el documento puede tener origen o referencia externa.

Regla de implementacion para EPP:

- La entrega EPP debe crear o vincular un documento formal de salida de inventario.
- El movimiento debe pasar por el servicio existente de inventario.
- La entrega EPP debe guardar referencia al documento/movimiento generado.
- Toda la operacion debe correr en transaccion.
- Si no hay stock suficiente, no debe guardarse la entrega.
- Si falla el documento de inventario, no debe guardarse la entrega.

## Roadmap propuesto

### Fase 0 - Auditoria con grafo y Markdown

Objetivo:

- Entender los flujos existentes antes de ampliar GIRALDA.

Checklist:

- Ejecutar `graphify query "GIRALDA inventario EPP empleados ordenes compra horas extras trazabilidad"`.
- Ejecutar consultas especificas para inventario y documentos.
- Revisar Markdown existentes en `docs/`.
- Identificar patrones existentes de controladores, rutas, permisos y vistas.
- Documentar archivos fuente que se van a reutilizar.

Salida esperada:

- Lista de archivos a tocar.
- Decision tecnica para EPP inventario.
- Riesgos conocidos.
- Plan de migraciones.

### Fase 1 - Estabilizar navegacion y permisos

Objetivo:

- Que GIRALDA aparezca en navbar solo para usuarios autorizados.
- Que el personal de Giralda no use la lista general de empleados.

Checklist:

- Revisar permisos existentes y nombres reales de roles.
- Confirmar permiso `giralda.access` o equivalente.
- Confirmar subpermisos para empleados, OC y almacen.
- Validar menu desplegable.
- Validar rutas protegidas.
- Validar acceso directo por URL.

Salida esperada:

- Menu GIRALDA visible para perfiles correctos.
- Lista general de empleados protegida segun rol.
- Lista filtrada GIRALDA disponible para usuarios Giralda.

### Fase 2 - EPP con inventario formal

Objetivo:

- Hacer que cada entrega EPP descuente inventario y deje trazabilidad documental.

Checklist:

- Revisar `InventarioDocumentoService`.
- Definir tipo de documento: salida por entrega EPP.
- Definir almacen origen: Giralda.
- Definir campos de referencia: empleado, area, obra, usuario, observaciones.
- Agregar campos de enlace en `empleado_epp_entregas` si hacen falta.
- Validar stock antes de guardar.
- Crear documento y detalle de inventario en transaccion.
- Aplicar servicio de inventario.
- Guardar entrega EPP ligada al documento.
- Mostrar folio/documento en historial EPP.
- Mostrar errores claros si no hay stock.

Salida esperada:

- Historial EPP con documento relacionado.
- Movimiento en inventario.
- Stock actualizado.
- Auditoria completa desde EPP hacia inventario.

### Fase 3 - Horas extras operativas

Objetivo:

- Completar el flujo operativo de horas extras para Giralda.

Checklist:

- Mantener lista de empleados por tab.
- Modal de alta por empleado.
- Historial por empleado.
- Filtros por periodo dentro de horas extras.
- Estatus: pendiente, autorizada, rechazada.
- Responsable que solicita.
- Responsable que autoriza.
- Formato imprimible.
- Exportacion CSV o Excel.

Salida esperada:

- Captura rapida por empleado.
- Reporte por periodo.
- Historial individual.
- Flujo basico de autorizacion.

### Fase 4 - Ordenes de compra Giralda

Objetivo:

- Reusar el modulo actual de ordenes de compra con identidad GIRALDA.

Checklist:

- Revisar `OrdenCompraController`, modelo `OrdenCompra` y migraciones.
- Revisar como se generan folios.
- Definir prefijo GIRALDA.
- Definir centro de costos o area.
- Filtrar OC por Giralda.
- Validar permisos de creacion/autorizacion.
- Evitar duplicar el modulo de compras.

Salida esperada:

- Entrada `Giralda -> Ordenes compra`.
- OC con prefijo o marca Giralda.
- Consulta filtrada.
- Trazabilidad con proveedores/pagos existentes.

### Fase 5 - Almacen Giralda

Objetivo:

- Integrar almacen bajo GIRALDA sin romper inventario general.

Checklist:

- Identificar almacen Giralda.
- Reusar vistas de inventario.
- Revisar rutas y permisos.
- Filtrar documentos y stock por almacen.
- Validar entradas, salidas, transferencias y ajustes.
- Conectar entrega EPP con salida desde almacen Giralda.

Salida esperada:

- Entrada `Giralda -> Almacen`.
- Operaciones filtradas al almacen Giralda.
- Menos ruido en navbar.
- Inventario con trazabilidad completa.

### Fase 6 - Reportes, auditoria y cierre

Objetivo:

- Consolidar reportes operativos y auditoria del modulo.

Checklist:

- Reporte de EPP por empleado.
- Reporte de EPP por periodo.
- Reporte de EPP por obra/area.
- Reporte de horas extras por empleado.
- Reporte de horas extras por periodo.
- Reporte de OC Giralda.
- Reporte de movimientos de almacen Giralda.
- Exportacion/impresion donde aplique.

Salida esperada:

- GIRALDA como modulo operativo completo.
- Trazabilidad desde empleado hasta inventario/documentos.
- Reportes utiles para control y auditoria.

## Decisiones abiertas

- Confirmar si EPP debe ser una salida definitiva de inventario o un resguardo retornable.
- Confirmar si ciertos equipos deben tener devolucion, reposicion o vida util.
- Confirmar si todos los EPP salen siempre del almacen Giralda.
- Confirmar si obra sera obligatoria, opcional o dependiente del area.
- Confirmar si area default debe ser GIRALDA cuando el empleado pertenece a Giralda.
- Confirmar si se debe hacer backfill de entregas EPP existentes sin area/obra.
- Confirmar roles que pueden ver GIRALDA.
- Confirmar roles que pueden autorizar horas extras.
- Confirmar si OC Giralda requiere flujo de autorizacion distinto.
- Confirmar si fotos y notas se reutilizaran desde expediente del empleado o tendran vista resumida en Giralda.

## Riesgos a cuidar

- Duplicar logica de inventario y romper trazabilidad.
- Descontar stock sin documento formal.
- Crear documentos incompletos sin folio o sin detalle.
- Dejar entregas EPP guardadas cuando falle inventario.
- Mostrar empleados fuera de Giralda a usuarios Giralda.
- Permisos incompletos que oculten el menu o permitan acceso directo indebido.
- Registros historicos con datos nulos por migraciones previas.
- Concurrencia de stock cuando dos usuarios entregan el mismo articulo.

## Definicion de terminado por bloque

Cada bloque se considera listo cuando cumpla:

- Se consulto el grafo antes de modificar.
- Se revisaron documentos Markdown relacionados.
- Se identificaron archivos existentes a reutilizar.
- Se implemento sin duplicar logica central.
- Se agregaron migraciones necesarias.
- Se validaron rutas.
- Se valido `php artisan view:cache`.
- Se valido sintaxis PHP con `php -l` en archivos tocados.
- Se hizo prueba manual del flujo.
- Se documento cualquier decision nueva en este archivo.
- Si hubo cambios de codigo, se actualizo el grafo con `graphify update .`.

## Siguiente checkpoint recomendado

Checkpoint inmediato:

- Revisar a detalle el flujo actual de documentos de inventario.
- Definir como una entrega EPP genera una salida formal.
- Proponer la migracion de enlaces entre `empleado_epp_entregas` e inventario.
- Validar el flujo con un articulo real de inventario.
- Despues de aprobar la idea, implementar en una transaccion.

Consulta sugerida al grafo:

```bash
graphify query "como se crean documentos de salida de inventario y movimientos de stock en InventarioDocumentoService" --budget 2000
```

Validaciones sugeridas:

```bash
php artisan route:list --name=giralda
php artisan route:list --name=inventario
php artisan view:cache
php artisan migrate
graphify update .
```

