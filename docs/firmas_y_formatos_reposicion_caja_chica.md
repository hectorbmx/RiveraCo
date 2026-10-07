# Firmas y formatos de reposicion de caja chica

## Meta

Dejar preparado un flujo configurable para firmas impresas y formatos de reportes de reposicion de caja chica, evitando que cada nuevo formato o campo de firma requiera crear constantes nuevas o reglas duras en codigo.

La meta operativa es que un usuario admin pueda configurar:

- Documento: el reporte o documento imprimible.
- Area/formato: la variante del documento donde se usaran firmas.
- Campo de firma: el espacio que aparecera en el papel, por ejemplo Realizo, Vo. Bo., Reviso o Autorizo.
- Firmante: el usuario asignado a ese campo.

Despues, cada reporte impreso debe consultar esas firmas desde configuracion y no desde nombres quemados en codigo.

## Alcance inicial

- Reposicion de caja chica.
- Reporte actual, sin eliminarlo.
- Nuevo formato tipo hoja administrativa escaneada.
- Separacion por destino actual: general, almacen y obra.
- Separacion por grupos ya existentes:
  - Con efectivo y factura.
  - Con tarjeta y factura.
  - Sin factura (reembolso).
  - Sin factura (viaticos).

## Hallazgos del barrido

### Tablas existentes

Ya existen tablas para manejar firmas imprimibles:

- `documento_firma_definiciones`
- `documento_firmantes`

La tabla `documento_firma_definiciones` ya soporta:

- `documento`
- `documento_label`
- `ambito`
- `ambito_label`
- `campo`
- `campo_label`
- `orden`
- `activo`

La tabla `documento_firmantes` ya guarda:

- `documento`
- `ambito`
- `campo`
- `user_id`
- `activo`

La llave unica ya considera `documento`, `ambito` y `campo`, asi que permite multiples formatos o areas dentro del mismo documento.

### UI actual de configuracion

En `configuracion-empresa?tab=firmas_imprimibles` ya se pueden crear definiciones de firma con documento, ambito y campo.

Problema detectado: el termino visible `Ambito` es confuso. Para el flujo real conviene mostrarlo como `Area / formato` o `Formato del documento`.

### UI actual en usuarios

En `usuarios/{id}/edit?tab=firmas` ya se muestran las definiciones activas y se puede asignar el usuario como firmante.

El flujo actual ya toma definiciones desde base de datos, no desde constantes. Esto esta bien.

### Codigo actual de caja chica

El reporte actual esta en:

- `app/Http/Controllers/ReposicionCajaChicaController.php`
- `resources/views/reposicion-caja-chica/reporte-imprimir.blade.php`
- `resources/views/reposicion-caja-chica/index.blade.php`

Ruta actual:

```php
GET /reposicion-caja-chica/imprimir
name: reposicion-caja-chica.imprimir
```

El controlador ya reutiliza filtros de semana y destino:

- general
- almacen
- obra

Tambien ya agrupa gastos por categoria.

Problema detectado: el metodo `firmasImpresasReposicion()` aun filtra campos fijos:

- `elaboro`
- `vobo`
- `autorizo`

Tambien la resolucion de area/formato de firma para caja chica esta amarrada a valores especificos:

- `reposicion_gastos_almacen`
- `giralda`

Para nuevos formatos no conviene seguir agregando constantes de ambito por codigo.

### Ajuste ya ejecutado

Se agrego la constante de campo:

```php
DocumentoFirmante::CAMPO_REVISO = 'reviso';
```

Esto no resuelve todo el flujo dinamico, pero deja disponible una llave limpia para el campo Revisó cuando sea necesario usarla desde codigo.

## Decision tecnica

No crear una constante nueva de ambito por cada formato.

Mantener `ambito` en base de datos por compatibilidad, pero tratarlo en UI y documentacion como:

- Area / formato
- Formato del documento

El usuario podra crear campos como `reviso`, `realizo`, `vobo`, `autorizo` desde configuracion.

El reporte debe pedir firmas por:

```txt
documento + area/formato
```

y recibir todos los campos activos configurados para esa combinacion.

## Resultado esperado

### Para configuracion

Un admin podra crear definiciones como:

| Documento | Area / formato | Campo | Etiqueta |
| --- | --- | --- | --- |
| reposicion_caja_chica | reporte_actual | elaboro | Elaboro |
| reposicion_caja_chica | reporte_actual | vobo | VoBo |
| reposicion_caja_chica | formato_administrativo | realizo | Realizo |
| reposicion_caja_chica | formato_administrativo | vobo | Vo. Bo. |
| reposicion_caja_chica | formato_administrativo | reviso | Reviso |

### Para usuarios

En el tab de firmas impresas, el usuario podra ser asignado a cualquiera de esas definiciones activas.

### Para reportes

El reporte actual seguira existiendo.

El nuevo formato se abrira desde otro boton/opcion y usara la misma base de datos de gastos, pero con otro Blade y otra distribucion visual.

## Plan de ejecucion

### Fase 1 - Ordenar configuracion de firmas

- [x] Cambiar textos visibles en `configuracion-empresa?tab=firmas_imprimibles` de `Ambito` a `Area / formato`.
- [x] Cambiar textos visibles en `usuarios/{id}/edit?tab=firmas` de `Ambito` a `Area / formato`.
- [x] Agregar ayuda corta en configuracion para explicar Documento, Area/formato y Campo.
- [ ] Verificar que crear una definicion nueva siga funcionando.
- [ ] Verificar que asignar un firmante desde usuarios siga funcionando.

### Fase 2 - Helper dinamico de firmas

- [x] Crear o ajustar helper privado para consultar firmas por `documento` y `area/formato`.
- [x] El helper debe traer todos los campos activos configurados, no solo una lista fija.
- [x] El helper debe devolver una coleccion indexada por `campo`.
- [x] Mantener compatibilidad con el reporte actual.
- [ ] Validar que el reporte actual siga mostrando Elaboro, VoBo y Autorizo.

### Fase 3 - Preparar nuevo formato de reposicion

- [ ] Definir llave de area/formato para el nuevo reporte, por ejemplo `formato_administrativo`.
- [ ] Crear desde configuracion las definiciones necesarias:
  - Realizo
  - Vo. Bo.
  - Reviso
  - Autorizo, si operativamente aplica.
- [ ] Asignar firmantes desde usuarios.
- [ ] Confirmar que el helper puede resolver esas firmas.

### Fase 4 - Nuevo reporte impreso

- [ ] Crear nueva ruta para el formato alterno.
- [ ] Crear metodo de controlador que reutilice la consulta actual de gastos.
- [ ] Crear vista Blade nueva para el formato administrativo.
- [ ] Mantener el reporte actual intacto.
- [ ] Separar el nuevo formato por destino cuando aplique:
  - general
  - almacen
  - obra
- [ ] Mantener grupos operativos:
  - Con efectivo y factura.
  - Con tarjeta y factura.
  - Sin factura (reembolso).
  - Sin factura (viaticos).

### Fase 5 - Botones de impresion

- [ ] Agregar una opcion/boton para imprimir el reporte actual.
- [ ] Agregar una opcion/boton para imprimir el nuevo formato.
- [ ] Si hay obras en la semana, conservar selector de destino.
- [ ] Evitar imprimir ambos formatos con un solo click por ahora.

### Fase 6 - Verificacion

- [ ] Probar reporte actual sin cambios visuales inesperados.
- [ ] Probar nuevo formato general.
- [ ] Probar nuevo formato por almacen.
- [ ] Probar nuevo formato por obra.
- [ ] Probar firmas configuradas completas.
- [ ] Probar caso sin firmantes asignados.
- [ ] Probar caso sin gastos para el periodo.

## Checkpoints de avance

### Checkpoint 1 - Configuracion clara

Estado: completado.

Criterio de cierre:

- La UI ya dice `Area / formato` en lugar de `Ambito`.
- El usuario entiende que puede crear campos como Revisó sin tocar codigo.

### Checkpoint 2 - Firmas dinamicas

Estado: en progreso.

Criterio de cierre:

- El reporte puede obtener firmas configuradas por documento y area/formato.
- Ya no depende de una lista fija de campos para nuevos formatos.

### Checkpoint 3 - Nuevo formato disponible

Estado: pendiente.

Criterio de cierre:

- Existe ruta y vista del formato nuevo.
- El reporte actual sigue funcionando.

### Checkpoint 4 - Operacion validada

Estado: pendiente.

Criterio de cierre:

- Se imprimen correctamente los casos general, almacen y obra.
- Las firmas correctas aparecen segun configuracion.

## Avance ejecutado

- Se actualizo la UI de configuracion de firmas para usar Area/formato en lugar de Ambito.
- Se actualizo el tab de firmas impresas de usuarios con el mismo lenguaje.
- Se agrego ayuda breve en configuracion para explicar Documento, Area/formato y Campo.
- Se ajusto `firmasImpresasReposicion()` para delegar a un helper dinamico por documento y area/formato.
- El helper dinamico ya devuelve todos los campos activos indexados por `campo`.

## Notas abiertas

- Confirmar nombre operativo del nuevo formato: `Formato administrativo`, `Formato reposicion`, u otro.
- Confirmar si el nuevo formato necesita `Autorizo` o solo `Realizo`, `Vo. Bo.` y `Reviso`.
- Confirmar si el campo `Realizo` debe usar `elaboro` por compatibilidad o si se creara como campo nuevo `realizo`.
- Confirmar si el nuevo formato se imprime una hoja por grupo o una hoja acumulada por destino.

## Paso 1 - Mapeo del formato administrativo

Estado: completado.

### Hallazgos de datos

La tabla `reposicion_caja_chica_gastos` contiene datos suficientes para armar el nuevo formato administrativo con estas columnas:

- `fecha_gasto`
- `concepto`
- `importe_registrado`
- `importe_autorizado`
- `estado_autorizacion`
- `forma_pago`
- `proveedor_nombre`
- `proveedor_rfc`
- `categoria_id`
- `subcategoria_id`
- `destino`
- `obra_id`
- `almacen_id`
- `maquina_id`
- `vehiculo_id`

No existe columna `uuid`, `factura`, `folio_fiscal` o equivalente en `reposicion_caja_chica_gastos`. Por eso, la columna `Factura` del nuevo formato debe resolverse como texto derivado y no como dato fiscal real, salvo que despues se agregue ese campo al modelo.

### Mapeo acordado para el formato escaneado

| Columna formato | Fuente actual | Regla propuesta |
| --- | --- | --- |
| Partida | `subcategoria.nombre` o `categoria.nombre` | Usar subcategoria si existe; si no, categoria; si no, `-`. |
| Fecha | `fecha_gasto` | Formato `d/m/Y`. |
| Factura | No existe campo fiscal guardado | Mostrar `S/F` para sin factura y `C/F` para con factura mientras no exista folio fiscal guardado. |
| Concepto | `concepto` | Texto principal del gasto. Si se requiere, despues puede agregarse proveedor como segunda linea. |
| Importe | `importe_autorizado` o `importe_registrado` | Usar autorizado cuando el gasto este `autorizado` o `autorizado_parcial`; si no hay autorizado, usar registrado. |
| Acumulado | Calculo del reporte | Suma corrida por renglon dentro de cada hoja/grupo. |
| Suma | Calculo del reporte | Total final del grupo/hoja. |
| Observaciones | Sin campo global | Dejar vacio por ahora; no mezclar observaciones por renglon en el bloque global. |

### Encabezado del formato

| Campo encabezado | Fuente actual | Regla propuesta |
| --- | --- | --- |
| Obra | `obra.nombre` / contexto del filtro | Si el reporte es por obra, mostrar nombre de obra. Si es almacen/general, mostrar el contexto del reporte. |
| No. Obra | `obra.clave_obra` | Solo aplica cuando el destino filtrado es obra. En almacen/general queda vacio. |
| Fecha | Fecha de impresion o periodo | Usar fecha de generacion del reporte y conservar el periodo como dato secundario si cabe. |

### Firmas para este formato

El formato administrativo debe leer firmas desde `documento_firmantes` usando:

- Documento: `reposicion_caja_chica`
- Area/formato: `formato_administrativo`

Campos esperados:

- `realizo`
- `vobo`
- `reviso`

Si se decide que tambien firme autorizacion, se puede agregar `autorizo` desde configuracion sin tocar codigo.

### Nota tecnica para implementacion

El reporte nuevo debe reutilizar `gastosReporteQuery()`, `agruparGastosPorCategoria()` y el helper dinamico de firmas. La diferencia principal sera una vista Blade nueva con layout tipo hoja administrativa y una preparacion de renglones con acumulado.

## Paso 2 - Ruta del formato administrativo

Estado: completado.

Se agrego una ruta independiente para no alterar el reporte actual:

```php
GET /reposicion-caja-chica/imprimir-formato-administrativo
name: reposicion-caja-chica.imprimir-formato-administrativo
controller: ReposicionCajaChicaController@imprimirFormatoAdministrativo
```

La ruta actual se mantiene intacta:

```php
GET /reposicion-caja-chica/imprimir
name: reposicion-caja-chica.imprimir
controller: ReposicionCajaChicaController@imprimirReporte
```

### Verificacion

`php artisan route:list --name=reposicion-caja-chica --path=reposicion-caja-chica` confirma que la nueva ruta esta registrada.

### Pendiente inmediato

Crear el metodo `imprimirFormatoAdministrativo()` en `ReposicionCajaChicaController` y despues su vista Blade.

## Paso 4 - Controlador y vista base del formato administrativo

Estado: completado.

Se agrego el metodo:

```php
ReposicionCajaChicaController::imprimirFormatoAdministrativo()
```

El metodo reutiliza:

- `resolverRangoSemana()`
- `gastosReporteQuery()`
- `agruparGastosPorCategoria()`
- `stats()`
- `contextoImpresionReposicion()`
- `firmasImpresasReposicion()` con area/formato `formato_administrativo`

Tambien se creo la vista base:

```txt
resources/views/reposicion-caja-chica/reporte-formato-administrativo.blade.php
```

La vista inicial ya renderiza:

- Encabezado tipo formato administrativo.
- Una hoja por grupo de caja chica.
- Columnas Partida, Fecha, Factura, Concepto, Importe y Acumulado.
- Suma final.
- Observaciones.
- Firmas Realizo, Vo. Bo. y Reviso.

### Verificacion

- `php -l app/Http/Controllers/ReposicionCajaChicaController.php` paso bien.
- `php artisan view:cache` paso bien.

### Pendiente inmediato

Agregar la opcion/boton en pantalla para abrir este segundo formato desde la bandeja de reposicion.

## Paso 5 - Selector de formato en la bandeja

Estado: completado.

Se ajusto la vista:

```txt
resources/views/reposicion-caja-chica/index.blade.php
```

Ahora el bloque de impresion permite elegir:

- `Reporte actual`, que abre `reposicion-caja-chica.imprimir`.
- `Formato administrativo`, que abre `reposicion-caja-chica.imprimir-formato-administrativo`.

El mismo selector conserva el filtro de destino cuando hay obras o almacenes disponibles para imprimir.

### Verificacion

- `php artisan view:cache` paso bien.
- `php artisan route:list --name=reposicion-caja-chica.imprimir` confirma ambas rutas de impresion.

## Ajuste visual - Logo del formato administrativo

Estado: completado.

El encabezado del formato administrativo ahora usa una imagen en lugar del texto fijo de la empresa.

Archivo usado por defecto:

```txt
public/images/logoAzul.png
```

URL publica esperada:

```txt
/images/logoAzul.png
```

Si se requiere acercar mas el diseno al formato original, reemplazar ese archivo por el logo correcto en PNG, preferentemente con fondo transparente y orientacion horizontal.
