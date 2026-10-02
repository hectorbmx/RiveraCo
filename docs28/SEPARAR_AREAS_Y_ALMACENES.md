# Separar Areas y Almacenes en Configuracion de Empresa

## Objetivo

Separar visual y operativamente el tab actual de `Areas`, porque hoy mezcla dos conceptos distintos:

- Areas: estructura operativa/administrativa de la empresa.
- Almacenes: ubicaciones o puntos de inventario/gasto relacionados con areas.

La meta inicial es evitar confusion en `configuracion-empresa`, dejando un tab claro para areas y otro para almacenes.

## Hallazgos iniciales

- La vista actual vive principalmente en `resources/views/empresa_config/edit.blade.php`.
- El tab actual es `areas`.
- Dentro del mismo tab existe:
  - Formulario/boton para crear area.
  - Modal para crear/editar area.
  - Tabla de areas.
  - Formulario de nuevo almacen.
  - Relacion de area con almacen.
- El controlador usado es `App\Http\Controllers\Admin\EmpresaConfigAreaController`.
- Ya existe ruta para crear almacen:
  - `empresa-config.almacenes.store`
- Actualmente `storeAlmacen()` redirige a:
  - `configuracion-empresa?tab=areas`
- No se confirmaron todavia rutas dedicadas para editar, activar/desactivar o eliminar almacenes.

## Alcance fase 1

Separacion visual y organizacion de la vista, sin cambiar todavia reglas de negocio de almacenes.

## Tareas y checkpoints

### 1. Barrido puntual

- [x] Revisar el bloque completo del tab `areas`.
- [x] Identificar secciones de areas.
- [x] Identificar secciones de almacenes.
- [x] Confirmar variables disponibles en la vista: `$areas`, `$almacenes`.
- [x] Confirmar rutas actuales usadas por el bloque.

Checkpoint:
- [x] Lista clara de bloques a mover.
- [x] Sin cambios de codigo funcional.

Resultado del barrido:

- El tab `areas` vive inline en `resources/views/empresa_config/edit.blade.php`.
- El bloque mezcla encabezado de areas, formulario de alta rapida de almacen, tabla de areas, modal de crear/editar area y el script Alpine `areasTab()`.
- La parte de areas incluye boton `+ Agregar area`, tabla con codigo, nombre, descripcion, horario base, almacen relacionado, estatus y acciones.
- La parte de almacenes por ahora solo tiene formulario `Nuevo almacen`; no existe todavia tabla propia de almacenes ni modal de edicion dentro de esta vista.
- `EmpresaConfigController` carga `$areas` con relaciones `horarioActivo` y `almacen`.
- `$almacenes` se carga actualmente solo con almacenes activos y campos `id`, `nombre`, `area_id`; para listar almacenes en tab propio conviene ampliar la consulta a `tipo`, `activo` y relacion con area.
- Las rutas de areas ya cubren crear, actualizar, activar/desactivar y eliminar.
- Para almacenes hoy solo existe `empresa-config.almacenes.store`.
- La separacion visual se puede hacer sin cambiar la regla actual de relacion area-almacen.

### 2. Crear partial de areas

- [x] Crear `resources/views/empresa_config/partials/_areas.blade.php`.
- [x] Mover al partial el contenido correspondiente a areas.
- [x] Mantener modal de crear/editar area.
- [x] Mantener funcion Alpine `areasTab()` dentro del partial o junto al bloque correspondiente.
- [x] Dejar `edit.blade.php` incluyendo el partial.

Checkpoint:
- [x] `/configuracion-empresa?tab=areas` sigue mostrando areas.
- [x] Crear area sigue funcionando a nivel de rutas/vista compilada.
- [x] Editar area sigue funcionando a nivel de rutas/vista compilada.

Resultado:

- Se creo el partial `resources/views/empresa_config/partials/_areas.blade.php`.
- `edit.blade.php` ahora incluye `@include('empresa_config.partials._areas')` en el tab de areas.
- El modal y la funcion Alpine `areasTab()` quedaron dentro del partial.
- Por ahora el formulario rapido `Nuevo almacen` sigue dentro del partial de areas; se movera al partial de almacenes en la tarea 3.
- Se valido con `php artisan view:cache` y despues se limpio con `php artisan view:clear`.

### 3. Crear partial de almacenes

- [ ] Crear `resources/views/empresa_config/partials/_almacenes.blade.php`.
- [ ] Mover el formulario actual de `Nuevo almacen` al nuevo partial.
- [ ] Crear tabla/listado de almacenes.
- [ ] Mostrar columnas iniciales:
  - Nombre
  - Area relacionada
  - Tipo
  - Estatus
- [ ] Mantener la creacion usando ruta existente `empresa-config.almacenes.store`.

Checkpoint:
- [ ] El formulario de almacenes ya no aparece dentro de Areas.
- [ ] `/configuracion-empresa?tab=almacenes` muestra formulario y listado de almacenes.

### 4. Agregar tab nuevo

- [ ] Agregar tab `almacenes` en `resources/views/empresa_config/edit.blade.php`.
- [ ] Incluir `_areas.blade.php`.
- [ ] Incluir `_almacenes.blade.php`.
- [ ] Confirmar que los tabs no se enciman ni rompen navegacion Alpine.

Checkpoint:
- [ ] Existe tab Areas.
- [ ] Existe tab Almacenes.
- [ ] Cada tab muestra solo su contexto principal.

### 5. Ajustar redireccion de almacen

- [ ] Cambiar `EmpresaConfigAreaController@storeAlmacen` para redirigir a `tab=almacenes`.

Checkpoint:
- [ ] Al crear almacen, el usuario vuelve al tab Almacenes.

### 6. Verificacion fase 1

- [ ] Compilar vistas con `php artisan view:cache`.
- [ ] Limpiar vistas con `php artisan view:clear`.
- [ ] Probar `/configuracion-empresa?tab=areas`.
- [ ] Probar `/configuracion-empresa?tab=almacenes`.
- [ ] Probar crear area.
- [ ] Probar crear almacen.

Checkpoint final fase 1:
- [ ] Areas y almacenes separados visualmente.
- [ ] Sin perdida de funcionalidad existente.

## Fase posterior opcional

Estas tareas quedan fuera de la separacion inicial, porque implican nuevas operaciones para almacenes:

- [ ] Editar almacen.
- [ ] Activar/desactivar almacen.
- [ ] Eliminar almacen, si aplica.
- [ ] Agregar validaciones adicionales de codigo/tipo si se requiere.
- [ ] Evaluar controlador dedicado para almacenes si crece el flujo.

## Notas operativas

- Mantener compatibilidad con la relacion actual `Area -> almacen`.
- No eliminar la relacion de almacen dentro del modal de area hasta validar si el usuario la sigue necesitando para asociacion rapida.
- Separar primero visualmente; despues decidir si almacenes requiere CRUD completo.


