# Roadmap: documentos configurables para clientes

## Objetivo

Permitir cargar, consultar y administrar documentos del expediente de clientes desde `clientes/{cliente}/edit?tab=docs`, usando una configuracion similar a la que hoy existe para documentos de empleados en `configuracion-empresa?tab=documentos`.

La idea principal es reutilizar el catalogo de tipos de documento, pero separar los documentos cargados por entidad. Es decir: un mismo catalogo configurable para empleados/clientes, pero tablas de expediente separadas.

## Decisiones base

- Reutilizar `empresa_documento_tipos` como catalogo general de tipos documentales.
- Agregar un campo `aplica_a` para distinguir si un tipo documental aplica a:
  - `empleado`
  - `cliente`
  - `ambos`
- Mantener el tab actual de configuracion `Documentos`, pero aclarar para quien aplica cada tipo.
- Crear una tabla separada `cliente_documentos` para documentos cargados de clientes.
- No mezclar archivos de clientes con archivos de empleados.
- Guardar archivos de clientes en `storage/app/public/clientes/{cliente_id}/documentos`.
- Usar el mismo criterio de versiones que empleados: cuando se sube un nuevo documento del mismo tipo, el anterior puede quedar como historico (`vigente = false`).

## Arquitectura propuesta

1. Administrador configura tipos de documento en `configuracion-empresa?tab=documentos`.
2. Cada tipo indica si aplica a empleados, clientes o ambos.
3. En el tab `clientes/{cliente}/edit?tab=docs`, el sistema muestra solo tipos activos que aplican a clientes.
4. Usuario sube documento con tipo, nombre opcional, fechas, observaciones y archivo.
5. Sistema guarda archivo en storage publico y crea registro en `cliente_documentos`.
6. El expediente del cliente muestra historial, vigencia, validacion y acceso al archivo.
7. Usuario puede eliminar documentos; se borra el archivo fisico y se hace soft delete del registro.

## Checkpoint 1: ampliar catalogo de tipos documentales

### Objetivo

Permitir que `empresa_documento_tipos` sirva para empleados, clientes o ambos.

### Cambios

- [x] Crear migracion para agregar columna `aplica_a` a `empresa_documento_tipos`.
- [x] Usar default `empleado` para preservar el comportamiento actual.
- [x] Valores esperados:
  - `empleado`
  - `cliente`
  - `ambos`
- [x] Agregar indice por `aplica_a` si resulta util para consultas.
- [x] Actualizar modelo `EmpresaDocumentoTipo`:
  - [x] incluir `aplica_a` en `fillable`
  - [x] agregar constantes o helper para valores permitidos
  - [x] agregar scope `aplicaACliente()`
  - [x] agregar scope `aplicaAEmpleado()`

### Validacion

- [x] Migracion corre sin afectar tipos existentes.
- [x] Tipos existentes quedan como `empleado`.
- [x] Tinker confirma filtros para cliente/empleado.

## Checkpoint 2: ajustar configuracion de empresa

### Objetivo

En `configuracion-empresa?tab=documentos`, permitir configurar para quien aplica cada documento.

### Cambios

- [x] Ajustar `EmpresaConfigController@edit` si se necesitan colecciones separadas o filtros.
- [x] Ajustar `storeDocumentoEmpleado` para recibir `aplica_a`.
- [x] Ajustar `updateDocumentoEmpleado` para actualizar `aplica_a`.
- [x] Renombrar textos visibles del tab para que no parezca exclusivo de empleados.
  - Ejemplo: `Documentos configurables`
  - Descripcion: `Configura documentos requeridos para empleados y clientes.`
- [x] Agregar selector `Aplica a` al formulario:
  - Empleados
  - Clientes
  - Ambos
- [x] Mostrar badge en la tabla:
  - Empleado
  - Cliente
  - Ambos

### Validacion

- [x] Crear tipos iniciales de cliente con `aplica_a = cliente`.
- [ ] Crear tipo compartido con `aplica_a = ambos`. Pendiente si se requiere un tipo compartido.
- [x] Tipos de empleados existentes siguen apareciendo en empleados.
- [ ] Tipos solo cliente no aparecen en empleados. Pendiente al crear tipos cliente.

## Checkpoint 3: crear expediente documental de clientes

### Objetivo

Tener una tabla y modelo para documentos cargados al expediente de cada cliente.

### Cambios

- [x] Crear migracion `create_cliente_documentos_table`.
- [ ] Campos sugeridos:
  - `id`
  - `cliente_id`
  - `documento_tipo_id`
  - `tipo_documento`
  - `nombre_documento`
  - `archivo_path`
  - `archivo_nombre_original`
  - `mime_type`
  - `extension`
  - `tamano_bytes`
  - `fecha_documento`
  - `fecha_vencimiento`
  - `vigente`
  - `estatus_validacion`
  - `validado_por`
  - `validado_en`
  - `observaciones`
  - `created_by`
  - `updated_by`
  - `deleted_at`
  - `created_at`
  - `updated_at`
- [x] Crear modelo `ClienteDocumento`.
- [x] Agregar relaciones:
  - [x] `ClienteDocumento -> cliente`
  - [x] `ClienteDocumento -> documentoTipo`
  - [x] `ClienteDocumento -> validador`
  - [x] `ClienteDocumento -> creador`
  - [x] `ClienteDocumento -> actualizador`
  - [x] `Cliente -> documentos`
- [x] Usar casts de fechas, booleanos y datetime.

### Validacion

- [x] Migracion crea tabla.
- [x] Se puede crear documento de cliente desde tinker.
- [x] Relacion `$cliente->documentos` funciona.

## Checkpoint 4: controlador de documentos de cliente

### Objetivo

Crear endpoints para subir y eliminar documentos del cliente.

### Cambios

- [x] Crear `ClienteDocumentoController`.
- [x] Metodo `store(Request $request, Cliente $cliente)`.
- [x] Validaciones:
  - [x] `documento_tipo_id`: requerido, existe y aplica a cliente o ambos
  - [x] `nombre_documento`: nullable string max 255
  - [x] `fecha_documento`: nullable date
  - [x] `fecha_vencimiento`: nullable date after_or_equal fecha_documento
  - [x] `observaciones`: nullable string
  - [x] `archivo`: requerido, file, mimes pdf/jpg/jpeg/png/webp, max 10MB
- [x] Guardar archivo en:
  - `clientes/{cliente_id}/documentos`
- [x] Generar nombre seguro con codigo del tipo documental + fecha + random.
- [x] Marcar documentos vigentes anteriores del mismo tipo como `vigente = false`.
- [x] Crear nuevo `ClienteDocumento` como vigente y pendiente de validacion.
- [x] Metodo `destroy(Cliente $cliente, ClienteDocumento $documento)`.
- [x] Validar pertenencia del documento al cliente.
- [x] Eliminar archivo fisico si existe.
- [x] Soft delete del registro.

### Validacion

- [ ] Subir PDF funciona. Pendiente de rutas/UI.
- [ ] Subir JPG/PNG/WEBP funciona. Pendiente de rutas/UI.
- [ ] Archivo mayor a 10MB falla con mensaje claro. Pendiente de rutas/UI.
- [ ] Subir nueva version del mismo tipo marca la anterior como historica. Pendiente de prueba con ruta.
- [ ] Eliminar documento borra archivo y registro queda soft deleted. Pendiente de prueba con ruta.

## Checkpoint 5: rutas web

### Objetivo

Conectar el controlador de documentos al expediente del cliente.

### Cambios

- [x] Agregar import de `ClienteDocumentoController` en `routes/web.php`.
- [ ] Agregar rutas antes de `Route::resource('clientes', ...)`:

```php
Route::post('clientes/{cliente}/documentos', [ClienteDocumentoController::class, 'store'])
    ->name('clientes.documentos.store');

Route::delete('clientes/{cliente}/documentos/{documento}', [ClienteDocumentoController::class, 'destroy'])
    ->name('clientes.documentos.destroy');
```

### Validacion

- [x] `php artisan route:list --name=clientes.documentos` muestra rutas.
- [x] Rutas no chocan con `clientes/{cliente}/edit`.

## Checkpoint 6: cargar datos en ClienteController

### Objetivo

Preparar los datos que requiere el tab `docs` del cliente.

### Cambios

- [x] En `ClienteController@edit`, cuando `$tab === 'docs'`:
  - [x] cargar documentos del cliente con `documentoTipo`
  - [x] cargar tipos de documento activos que aplican a cliente o ambos
- [x] Pasar variables a la vista:
  - [x] `$documentos`
  - [x] `$documentosTipos`

### Validacion

- [x] Entrar a `clientes/{id}/edit?tab=docs` no rompe.
- [x] El select muestra tipos cliente/ambos activos; existen 5 tipos iniciales.
- [x] Tipos solo empleado no aparecen.

## Checkpoint 7: UI del tab documentos en clientes

### Objetivo

Reemplazar el placeholder actual de `clientes/{id}/edit?tab=docs` con un expediente documental funcional.

### Cambios

- [x] Crear parcial `resources/views/clientes/partials/_documentos.blade.php`.
- [x] Basarse en `empleados/partials/_documentos.blade.php`, ajustando textos a cliente.
- [x] Formulario de carga:
  - tipo de documento
  - nombre del documento
  - fecha documento
  - fecha vencimiento
  - observaciones
  - archivo
- [ ] Tabla de historial:
  - tipo
  - nombre
  - fecha
  - vencimiento
  - vigente/historico
  - validacion
  - archivo
  - acciones
- [x] En `clientes/edit.blade.php`, cambiar el tab `docs` para incluir el parcial.

### Validacion

- [x] Vista compila con `php artisan view:cache`.
- [ ] Tab se ve correctamente en desktop.
- [x] Formulario conserva errores y old values.
- [ ] Archivo se abre con link `asset('storage/...')`.

## Checkpoint 8: permisos y seguridad

### Objetivo

Evitar que usuarios sin acceso gestionen documentos indebidamente.

### Cambios

- [x] Revisar permisos actuales de `clientes.access` y documentos empleados.
- [x] Definir si basta con acceso a clientes o si se requieren permisos nuevos: por ahora se heredan permisos existentes de clientes.
  - `clientes.documentos.view`
  - `clientes.documentos.upload`
  - `clientes.documentos.delete`
- [x] Si se agregan permisos, registrar en configuracion/seed correspondiente. No se agregaron permisos nuevos; se usan `clientes.edit`, `clientes.delete` y compatibilidad con `clientes.access`.
- [x] Validar en controlador o rutas.

### Validacion

- [x] Usuario con acceso a clientes puede ver documentos segun regla definida.
- [x] Usuario sin permisos recibe 403 en acciones directas y no ve formulario/boton de eliminar.

## Checkpoint 9: pruebas funcionales end-to-end

### Escenarios

- [ ] Configurar documento tipo `Constancia fiscal cliente` con `aplica_a = cliente`.
- [ ] Configurar documento tipo `Contrato` con `aplica_a = ambos`.
- [ ] Abrir `clientes/2/edit?tab=docs`.
- [ ] Confirmar que aparecen ambos tipos.
- [ ] Confirmar que un tipo solo empleado no aparece.
- [ ] Subir documento PDF.
- [ ] Ver archivo desde la tabla.
- [ ] Subir nueva version del mismo tipo.
- [ ] Confirmar que la version anterior queda historica.
- [ ] Eliminar documento.
- [ ] Confirmar que archivo fisico se elimina.

## Checkpoint 10: limpieza y mejoras opcionales

### Opcionales

- [ ] Agregar contador/resumen de documentos obligatorios faltantes por cliente.
- [ ] Mostrar alerta de documentos vencidos o por vencer.
- [ ] Agregar validacion/aprobacion de documentos.
- [ ] Agregar filtros por vigente/historico/tipo.
- [ ] Agregar descarga directa.
- [ ] Agregar vista compacta de cumplimiento documental en index de clientes.

## Riesgos y notas

- Si se reutiliza `empresa_documento_tipos`, hay que cuidar que documentos existentes de empleados sigan con `aplica_a = empleado`.
- Si un tipo documental cambia de `cliente` a `empleado`, documentos ya cargados seguiran existiendo; solo dejara de aparecer como opcion nueva.
- Los archivos se exponen por storage publico igual que empleados; verificar que `php artisan storage:link` exista en ambientes donde se pruebe.
- El tab actual de clientes usa `docs`, mientras empleados usa `documentos`. Mantener `docs` para no romper links existentes.

## Estado

- [ ] En progreso: checkpoints 1-7 implementados; faltan seguridad/permisos, prueba manual E2E y limpieza opcional.