# Plan tecnico: firmas impresas reutilizables por documento y ambito

Fecha: 2026-08-31  
Modulo: Usuarios / Firmas impresas / Documentos imprimibles  
Estado: Paso 1 ejecutado; pendiente Paso 2 en adelante

## Objetivo

Generalizar la configuracion de firmas impresas para que no sirva solo en ordenes de compra, sino tambien en documentos futuros como reposicion de caja chica, gastos de almacen y Giralda.

Caso inmediato:

- En la impresion de reposicion de caja chica, cambiar la etiqueta `Reviso` por `VoBo`.
- Permitir configurar que usuario aparece como firmante en ese campo.
- Distinguir el ambito donde aplica la firma, por ejemplo `reposicion gastos almacen` o `giralda`.

## Hallazgos actuales

### Reposicion de caja chica

La impresion solicitada usa la ruta:

`/reposicion-caja-chica/imprimir?fecha_inicio=2026-08-24&fecha_fin=2026-08-30`

El controlador que arma la vista es:

- `app/Http/Controllers/ReposicionCajaChicaController.php`
- Metodo: `imprimirReporte`

Actualmente el controlador no consulta firmantes impresos ni envia firmas a la vista.

La vista principal de impresion es:

- `resources/views/reposicion-caja-chica/reporte-imprimir.blade.php`

Al final de la vista existen tres etiquetas fijas:

- `Elaboro`
- `Reviso`
- `Autorizo`

Tambien existe una vista relacionada con la misma estructura de firmas:

- `resources/views/reposicion-caja-chica/relaciones-imprimir.blade.php`

Checkpoint funcional:

- Confirmar si el cambio `Reviso` -> `VoBo` aplica solo a `reporte-imprimir.blade.php` o tambien a `relaciones-imprimir.blade.php`.

### Firmas impresas actuales

El modelo actual es:

- `app/Models/DocumentoFirmante.php`

La tabla es:

- `documento_firmantes`

Campos actuales:

- `documento`
- `campo`
- `user_id`
- `activo`

Restriccion actual:

- Indice unico por `documento + campo`.

Esto permite una sola persona por campo en un documento, por ejemplo:

- `orden_compra + vobo_1`
- `orden_compra + vobo_2`
- `orden_compra + enterado`

Limitacion:

- No permite diferenciar el mismo campo por ambito.
- Ejemplo problematico: no se podria tener un `vobo` para reposicion de gastos de almacen y otro `vobo` para Giralda si ambos viven como `reposicion_caja_chica + vobo`.

### Pantalla de usuarios

La pantalla:

`/usuarios/1/edit`

incluye el tab `Firmas impresas`.

Archivos relevantes:

- `app/Http/Controllers/Admin/UsuarioController.php`
- `resources/views/usuarios/edit.blade.php`

La implementacion actual esta amarrada a ordenes de compra:

- Solo carga firmas donde `documento = orden_compra`.
- Solo muestra campos `VoBo 1`, `VoBo 2` y `ENTERADO`.
- No existe columna de documento.
- No existe columna de ambito, area o ubicacion.

### Configuracion de empresa

Nuevo hallazgo funcional:

- El catalogo de documentos, ambitos y campos de firma debe administrarse desde `/configuracion-empresa`.
- La pantalla de usuarios no debe definir que firmas existen; solo debe reflejar esas definiciones y permitir asignar quien firma.
- Separacion correcta:
  - Configuracion empresa define `que documentos/campos/ambitos existen`.
  - Usuarios define `quien firma cada documento/campo/ambito`.

Implicacion tecnica:

- El Paso 2 ya no debe resolverse como archivo duro `config/documento_firmantes.php` si se quiere una configuracion administrable.
- Conviene crear una tabla de definiciones de firma o integrar una seccion equivalente dentro de la configuracion empresa existente.

## Decision tecnica recomendada

Agregar una columna de ambito a `documento_firmantes`.

Nombre sugerido:

- `ambito`

Valores iniciales sugeridos:

- `general`
- `reposicion_gastos_almacen`
- `giralda`

La llave logica quedaria:

`documento + ambito + campo`

Ejemplos:

- `orden_compra + general + vobo_1`
- `orden_compra + general + vobo_2`
- `orden_compra + general + enterado`
- `reposicion_caja_chica + reposicion_gastos_almacen + vobo`
- `reposicion_caja_chica + giralda + vobo`

Ventajas:

- Evita duplicar logica por cada documento.
- Permite reutilizar el tab de usuarios para documentos futuros.
- Mantiene compatibilidad con ordenes de compra usando `ambito = general`.
- Evita codificar el ambito dentro del campo, por ejemplo `vobo_giralda`, lo cual escalaria mal.

## Preguntas abiertas antes de ejecutar

1. Como debe determinarse el ambito en la impresion de reposicion?

   La URL actual solo trae fechas. No trae un parametro claro como `ambito`, `destino` o `area`.

   Opciones:

   - Agregar parametro explicito en la impresion, por ejemplo `ambito=giralda`.
   - Inferirlo desde los gastos incluidos en el reporte.
   - Usar un ambito default cuando el reporte venga mezclado.

2. La firma nueva sera solo para `VoBo` o tambien se configuraran `Elaboro` y `Autorizo`?

   Para el caso inmediato, el usuario menciono cambiar `Reviso` por `VoBo` y crear un nuevo documento firmante. La implementacion puede iniciar solo con `VoBo`, pero conviene dejar preparada la estructura para los tres campos.

3. `reposicion gastos almacen` y `giralda` deben salir de un catalogo existente o ser constantes internas?

   Si ya existe un catalogo de areas en configuracion, se debe decidir si `ambito` guarda:

   - Una clave string estable.
   - Un `area_id`.

   Recomendacion inicial: usar clave string estable para evitar problemas con indices unicos y valores nulos. Si el negocio exige administrar areas desde catalogo, evaluar `area_id` despues.

## Plan de implementacion

### Paso 1. Migracion y modelo base

Crear una migracion para agregar `ambito` a `documento_firmantes`.

Acciones:

- Agregar columna `ambito` con default `general`.
- Migrar registros existentes de ordenes de compra a `general`.
- Cambiar indice unico de `documento + campo` a `documento + ambito + campo`.
- Agregar `ambito` a `$fillable` en `DocumentoFirmante`.
- Agregar constantes de documentos, campos y ambitos.

Constantes sugeridas:

- `DOCUMENTO_ORDEN_COMPRA = orden_compra`
- `DOCUMENTO_REPOSICION_CAJA_CHICA = reposicion_caja_chica`
- `CAMPO_ELABORO = elaboro`
- `CAMPO_VOBO = vobo`
- `CAMPO_AUTORIZO = autorizo`
- `CAMPO_VOBO_1 = vobo_1`
- `CAMPO_VOBO_2 = vobo_2`
- `CAMPO_ENTERADO = enterado`
- `AMBITO_GENERAL = general`
- `AMBITO_REPOSICION_GASTOS_ALMACEN = reposicion_gastos_almacen`
- `AMBITO_GIRALDA = giralda`

Checkpoint:

- `php artisan migrate` ejecuta sin error en ambiente local.
- Las firmas existentes de orden de compra siguen disponibles.
- No se duplican registros para el mismo `documento + ambito + campo`.

### Paso 2. Catalogo administrable desde configuracion empresa

Crear la base para administrar documentos, ambitos y campos desde `/configuracion-empresa`.

La configuracion de empresa debe responder estas preguntas:

- Que documentos imprimibles aceptan firmas.
- Que ambitos o areas tiene cada documento.
- Que campos de firma existen por ambito.
- En que orden deben mostrarse.
- Si cada definicion esta activa o inactiva.

Tabla sugerida:

- `documento_firma_definiciones`

Campos sugeridos:

- `id`
- `documento`
- `documento_label`
- `ambito`
- `ambito_label`
- `campo`
- `campo_label`
- `orden`
- `activo`
- `created_at`
- `updated_at`

Indice unico sugerido:

- `documento + ambito + campo`

Registros iniciales sugeridos:

- Orden de compra
  - Ambito: General
  - Campos: `VoBo 1`, `VoBo 2`, `ENTERADO`
- Reposicion caja chica
  - Ambito: Reposicion gastos almacen
  - Campos: `Elaboro`, `VoBo`, `Autorizo`
  - Ambito: Giralda
  - Campos: `Elaboro`, `VoBo`, `Autorizo`

Archivos a revisar antes de implementar:

- Rutas, controlador y vistas de `/configuracion-empresa`.
- Modelo o tabla actual donde se guarda configuracion de empresa.
- Permisos existentes para modificar configuracion.

Checkpoint:

- `/configuracion-empresa` permite ver o administrar el catalogo de firmas imprimibles.
- El catalogo queda persistido en base de datos, no hardcodeado solo en una vista.
- Ordenes de compra conserva sus campos actuales como registros iniciales.
- Reposicion queda preparada con ambitos `reposicion_gastos_almacen` y `giralda`.
- La pantalla de usuarios puede consumir estas definiciones sin decidir por su cuenta que firmas existen.

### Paso 3. Generalizar el tab de firmas impresas en usuarios

Actualizar:

- `app/Http/Controllers/Admin/UsuarioController.php`
- `resources/views/usuarios/edit.blade.php`

Acciones:

- Cargar firmas por `documento + ambito + campo`.
- Mostrar columnas:
  - Documento
  - Ambito / area
  - Campo
  - Asignado actual
  - Usar este usuario
- Guardar o reemplazar el firmante seleccionado para cada combinacion.
- Mantener el comportamiento actual de orden de compra.

Checkpoint:

- En `/usuarios/1/edit`, el tab muestra ordenes de compra y reposicion.
- Asignar una firma de orden de compra sigue funcionando.
- Asignar una firma de reposicion no afecta las firmas de orden de compra.
- Asignar `VoBo` de Giralda no pisa `VoBo` de reposicion gastos almacen.

### Paso 4. Resolver firmantes en la impresion de reposicion

Actualizar:

- `app/Http/Controllers/ReposicionCajaChicaController.php`
- `resources/views/reposicion-caja-chica/reporte-imprimir.blade.php`

Acciones:

- Determinar el ambito de la impresion.
- Consultar firmantes activos de `reposicion_caja_chica + ambito`.
- Enviar firmantes a la vista.
- Cambiar etiqueta `Reviso` por `VoBo`.
- Mostrar nombre del firmante configurado cuando exista.
- Mantener vacio o texto generico cuando no exista firma configurada.

Checkpoint:

- La impresion muestra `Elaboro`, `VoBo`, `Autorizo`.
- El campo `VoBo` muestra el usuario configurado para el ambito correcto.
- Si no hay firmante configurado, la impresion no rompe.
- Totales y listado de gastos no cambian.

### Paso 5. Validaciones y pruebas

Pruebas sugeridas:

- Prueba unitaria del resolver de firmas:
  - Encuentra firma por `documento + ambito + campo`.
  - No cruza firmas entre ambitos.
  - No cruza firmas entre documentos.
- Prueba de controlador o feature:
  - La impresion de reposicion responde OK.
  - La vista contiene `VoBo`.
  - La vista ya no contiene `Reviso` en la seccion de firmas.
- Prueba de usuario:
  - Guardar firma de reposicion desde `/usuarios/{id}/edit`.
  - Verificar que no se altera orden de compra.

Comandos de validacion:

- `php -l app/Models/DocumentoFirmante.php`
- `php -l app/Http/Controllers/Admin/UsuarioController.php`
- `php -l app/Http/Controllers/ReposicionCajaChicaController.php`
- `vendor/bin/phpunit` con pruebas especificas del modulo
- `php tools/check_mojibake.php <archivos tocados>`
- `graphify update .`

## Checkpoints de avance

### Checkpoint 1. Base de datos lista

Estado: completado el 2026-08-31.

- Existe `ambito` en `documento_firmantes`.
- Ordenes de compra mantiene datos anteriores con `ambito = general`.
- Indice unico nuevo protege `documento + ambito + campo`.

### Checkpoint 2. Configuracion empresa lista

- `/configuracion-empresa` muestra o administra el catalogo de firmas imprimibles.
- El catalogo central vive en base de datos o en la configuracion persistente de empresa.
- Orden de compra y reposicion viven en la misma estructura.
- Agregar un documento futuro requiere configurarlo, no rehacer el flujo de usuarios.

### Checkpoint 3. UI de usuarios lista

- El tab de firmas impresas muestra documento, ambito y campo.
- El usuario puede asignarse como firmante en cualquier combinacion configurada.
- La asignacion no pisa otros documentos ni otros ambitos.

### Checkpoint 4. Reposicion imprime VoBo

- La etiqueta visible dice `VoBo`.
- El firmante sale de `documento_firmantes`.
- La impresion no cambia importes, filtros ni listado.

### Checkpoint 5. Flujo listo para futuras firmas

- Existe un helper, servicio o metodo reusable para resolver firmantes.
- Las nuevas firmas se agregan con configuracion.
- Las pruebas cubren separacion por documento y ambito.

## Riesgos

- Si la impresion de reposicion puede mezclar gastos de almacen y Giralda, el sistema no sabra que firma usar sin una regla extra.
- Cambiar el indice unico requiere cuidar datos existentes antes de migrar.
- Si se usa `area_id` en lugar de `ambito` string, hay que cuidar valores nulos en indices unicos de MySQL.
- Si se configura solo `VoBo` pero despues se requieren `Elaboro` y `Autorizo`, conviene que la estructura ya acepte los tres desde el inicio.

## Recomendacion de arranque

Paso 1 ya quedo ejecutado.

Siguiente bloque recomendado:

1. Revisar implementacion actual de `/configuracion-empresa`.
2. Crear la estructura persistente del catalogo de firmas imprimibles.
3. Sembrar definiciones iniciales de orden de compra y reposicion.
4. Agregar pruebas del catalogo.

Despues avanzar a la UI de usuarios para que solo asigne firmantes, y finalmente conectar reposicion de caja chica.

## Bitacora

### 2026-08-31 - Paso 1 ejecutado

- Se agrego la migracion `2026_08_31_103000_add_ambito_to_documento_firmantes_table.php`.
- Se agrego `ambito` a `documento_firmantes` con default `general`.
- Se cambio la llave unica de `documento + campo` a `documento + ambito + campo`.
- Se agregaron constantes base para documentos, ambitos y campos en `DocumentoFirmante`.
- Se agrego prueba unitaria `tests/Unit/DocumentoFirmanteTest.php`.
- Validaciones ejecutadas: `php artisan migrate`, `php -l`, `vendor/bin/phpunit tests/Unit/DocumentoFirmanteTest.php`, `php tools/check_mojibake.php`.
### 2026-08-31 - Hallazgo sobre configuracion empresa

- Se aclaro que el catalogo de firmas debe vivir en `/configuracion-empresa`.
- `/usuarios/{id}/edit` debe limitarse a reflejar el catalogo y asignar que usuario firma cada documento, ambito y campo.
- El Paso 2 se ajusto de `config/documento_firmantes.php` a una configuracion administrable/persistente.

