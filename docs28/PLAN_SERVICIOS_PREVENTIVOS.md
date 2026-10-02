# Plan de Servicios Preventivos por Tipo

Fecha: 2026-09-29

## Objetivo

Evolucionar la configuracion actual de servicios preventivos para soportar multiples tipos de servicio por ambito:

- Maquinaria: servicios por horas de horometro.
- Vehiculos: servicios por kilometraje.

Ejemplos esperados para maquinaria:

| Codigo | Nombre | Intervalo |
| --- | --- | --- |
| basico | Basico | cada 250 h |
| general | General | cada 1000 h |
| mayor | Mayor | cada 3000 h |

La meta es que el sistema pueda crear N tipos de servicio desde configuracion, registrar que tipo de servicio se realizo en cada mantenimiento y calcular el siguiente servicio esperado sin depender de una sola columna global.

## Hallazgos Del Barrido

### Configuracion Actual

El modelo `EmpresaConfig` contiene campos globales simples:

- `maquinaria_servicio_horas`
- `maquinaria_servicio_meses`
- `maquinaria_alerta_horas`
- `vehiculo_servicio_km`
- `vehiculo_servicio_meses`
- `vehiculo_alerta_km`
- `vehiculo_alerta_dias`
- `vehiculo_alertas_activas`

Estos campos permiten un solo intervalo por ambito, pero no alcanzan para Basico, General, Mayor o N servicios.

### UI Actual De Configuracion

Archivo: `resources/views/empresa_config/edit.blade.php`

- Tab `vehiculos`: formulario global con km, meses, alerta km, alerta dias, alertas activas y destinatarios.
- Tab `maquinaria`: formulario global con horas, meses y alerta horas.

Actualmente no hay un catalogo editable de tipos de servicio.

### Guardado Actual De Configuracion

Archivo: `app/Http/Controllers/EmpresaConfigController.php`

- `section === maquinaria` valida y actualiza los campos globales de maquinaria en `empresa_config`.
- `section === vehiculos` valida y actualiza los campos globales de vehiculos y destinatarios de alerta.

Esto conviene mantenerlo como fallback mientras se migra el calculo al nuevo catalogo.

### Calculo Actual Maquinaria

Archivo: `app/Services/Maquinas/PreventivoMaquinaService.php`

- Usa `maquinaria_servicio_horas` como intervalo unico.
- Usa `maquinaria_servicio_meses` como intervalo de tiempo.
- Usa `maquinaria_alerta_horas` como umbral de alerta.
- Busca ultimo mantenimiento programado/completado con `horometro`.
- Devuelve un solo estado/label/proximo horometro.

Limitacion: no puede distinguir si el proximo servicio esperado es Basico, General o Mayor.

### Calculo Actual Vehiculos

Archivo: `app/Services/Vehiculos/PreventivoVehiculoService.php`

- Usa `vehiculo_servicio_km` como intervalo unico.
- Usa `vehiculo_servicio_meses` como intervalo de tiempo.
- Usa `vehiculo_alerta_km` como umbral de alerta.
- Busca ultimo mantenimiento programado/completado con `km_actuales`.
- Devuelve un solo estado/label/proximo km.

Limitacion: igual que maquinaria, solo maneja un tipo global.

### Mantenimientos Actuales

Tabla: `mantenimientos`

Campos relevantes actuales:

- `vehiculo_id`
- `maquina_id`
- `tipo` (`programado`, `emergencia`)
- `categoria_mantenimiento` texto libre
- `km_actuales`
- `km_proximo_servicio`
- `horometro`
- `fecha_programada`, `fecha_inicio`, `fecha_fin`
- `estatus`

Modelo: `app/Models/Mantenimiento.php`

No existe todavia una relacion a un tipo de servicio preventivo.

Controlador: `app/Http/Controllers/MantenimientoController.php`

- `store` y `update` validan `categoria_mantenimiento` como texto libre.
- No validan ni guardan `servicio_preventivo_tipo_id`.

Vistas: `resources/views/mantenimiento/create.blade.php` y `edit.blade.php`

- Usan campo `categoria_mantenimiento` como input de texto.
- Tienen campos de horometro o km segun contexto.

## Decision De Diseno Recomendada

Crear una tabla nueva de catalogo compartida para maquinaria y vehiculos:

`empresa_servicio_preventivo_tipos`

Campos propuestos:

| Campo | Tipo | Notas |
| --- | --- | --- |
| id | bigint | PK |
| ambito | string(30) | `maquinaria` o `vehiculo` |
| nombre | string(100) | Basico, General, Mayor |
| codigo | string(80) | basico, general, mayor |
| unidad | string(20) | `horas` o `km` |
| intervalo_valor | unsigned integer | 250, 1000, 3000, 5000, etc. |
| intervalo_meses | unsigned integer nullable | regla por tiempo |
| alerta_valor | unsigned integer nullable | horas/km antes de vencer |
| alerta_dias | unsigned integer nullable | mas util para vehiculos |
| activo | boolean | visible/usable |
| orden | unsigned integer | orden UI y desempates |
| timestamps | timestamps | auditoria basica |

Indices recomendados:

- unique: `ambito + codigo`
- index: `ambito + activo + orden`

Mantener por ahora los campos viejos de `empresa_config` como fallback.

## Seeder Inicial

Crear un seeder idempotente con `updateOrCreate`.

Maquinaria:

| Ambito | Codigo | Nombre | Unidad | Intervalo | Meses | Alerta |
| --- | --- | --- | --- | --- | --- | --- |
| maquinaria | basico | Basico | horas | 250 | 6 | 20 |
| maquinaria | general | General | horas | 1000 | 6 | 50 |
| maquinaria | mayor | Mayor | horas | 3000 | 12 | 100 |

Vehiculos, por ahora base:

| Ambito | Codigo | Nombre | Unidad | Intervalo | Meses | Alerta valor | Alerta dias |
| --- | --- | --- | --- | --- | --- | --- | --- |
| vehiculo | basico | Basico | km | 5000 | 6 | 500 | 10 |

## Tareas Pequenas Propuestas

### Fase 1: Base De Datos Y Seeder

1. Crear migracion `create_empresa_servicio_preventivo_tipos_table`.
2. Crear modelo `EmpresaServicioPreventivoTipo`.
3. Agregar fillable y casts al modelo.
4. Agregar scopes:
   - `activos()`
   - `maquinaria()`
   - `vehiculos()`
   - `ordenados()`
5. Crear seeder `EmpresaServicioPreventivoTipoSeeder`.
6. Agregar seeder a `DatabaseSeeder` o dejarlo documentado para correr manualmente.
7. Verificar con `php -l` y, despues de migrar, con consulta/tinker.

### Fase 2: Mostrar Catalogo En Configuracion

8. En `EmpresaConfigController@edit`, cargar tipos preventivos separados:
   - `$serviciosPreventivosMaquinaria`
   - `$serviciosPreventivosVehiculos`
9. Pasar ambas colecciones a `empresa_config.edit`.
10. En tab `maquinaria`, mostrar tabla de tipos actuales.
11. En tab `vehiculos`, mostrar tabla de tipos actuales.
12. Mantener formularios globales viejos como fallback o marcarlos como configuracion legacy.

### Fase 3: CRUD Del Catalogo

13. Definir rutas para crear/actualizar/desactivar tipos preventivos.
14. Opcion A: metodos dentro de `EmpresaConfigController`.
15. Opcion B recomendada: controlador dedicado `EmpresaServicioPreventivoTipoController`.
16. Validar:
   - `ambito`: maquinaria/vehiculo
   - `nombre`: requerido
   - `codigo`: requerido, unico por ambito
   - `unidad`: horas si maquinaria, km si vehiculo
   - `intervalo_valor`: mayor a 0
   - `intervalo_meses`: nullable, 1..120
   - `alerta_valor`: nullable, >= 0
   - `alerta_dias`: nullable, >= 0
   - `orden`: nullable/int
17. Agregar acciones UI:
   - crear
   - editar campos basicos
   - activar/desactivar

### Fase 4: Ligar Mantenimiento A Tipo De Servicio

18. Crear migracion para `mantenimientos.servicio_preventivo_tipo_id` nullable.
19. Agregar FK a `empresa_servicio_preventivo_tipos` con nullOnDelete.
20. Agregar campo al fillable de `Mantenimiento`.
21. Agregar relacion `servicioPreventivoTipo()` en `Mantenimiento`.
22. En `MantenimientoController@create`, cargar tipos segun contexto si viene `vehiculo_id` o `maquina_id`.
23. En `store` y `update`, validar `servicio_preventivo_tipo_id` nullable/existing.
24. En formularios `mantenimiento/create.blade.php` y `edit.blade.php`, agregar select de tipo preventivo.
25. En index/show de mantenimiento, mostrar nombre del tipo si existe.
26. Mantener `categoria_mantenimiento` como texto libre/descripcion adicional mientras se decide si se depreca.

### Fase 5: Calculo Preventivo Con Catalogo

27. Ajustar `PreventivoMaquinaService` para cargar tipos activos de maquinaria.
28. Mantener fallback a `EmpresaConfig` si no hay tipos activos.
29. Primera version simple:
   - usar el tipo activo de menor `intervalo_valor` como servicio base.
   - conservar estructura de respuesta actual para no romper UI.
30. Segunda version completa:
   - calcular el proximo hito entre todos los tipos activos.
   - si varios vencen al mismo horometro/km, gana el de mayor intervalo o menor orden segun regla definida.
31. Agregar al resultado campos nuevos opcionales:
   - `servicio_tipo_actual`
   - `servicio_tipo_siguiente`
   - `servicio_tipo_codigo`
   - `servicio_tipo_nombre`
32. Repetir ajuste para `PreventivoVehiculoService`.

### Fase 6: UI De Preventivos

33. Ajustar badges de maquinaria para mostrar tipo esperado si existe.
34. Ajustar vista de vehiculos para mostrar tipo esperado si existe.
35. Mantener texto viejo si no hay tipo.

### Fase 7: Pruebas

36. Migracion crea tabla nueva correctamente.
37. Seeder crea registros sin duplicar al correr varias veces.
38. Configuracion maquinaria lista/crea/edita/desactiva tipos.
39. Configuracion vehiculos lista/crea/edita/desactiva tipos.
40. Mantenimiento de maquina permite seleccionar tipo maquinaria.
41. Mantenimiento de vehiculo permite seleccionar tipo vehiculo.
42. Preventivo maquinaria funciona con catalogo y con fallback.
43. Preventivo vehiculo funciona con catalogo y con fallback.
44. No se rompe `/maquinas`.
45. No se rompe `/mantenimiento/vehiculos`.
46. No se rompe `configuracion-empresa?tab=maquinaria` ni `tab=vehiculos`.

## Riesgos Y Decisiones Pendientes

1. Regla de siguiente servicio:
   - Si una maquina llega a 3000 h, tambien coincide con 250 y 1000. Recomendacion: gana el intervalo mayor, es decir Mayor.
2. Servicios por tiempo:
   - Definir si el tipo Mayor tambien vence por meses o solo por horas/km.
3. Vehiculos:
   - Solo tenemos Basico inicial, pero la tabla permite agregar General/Mayor despues.
4. Categoria actual:
   - `categoria_mantenimiento` puede coexistir como texto libre, pero a futuro conviene usar el catalogo como dato principal.
5. Compatibilidad:
   - No borrar campos actuales de `empresa_config` hasta que calculos y UI esten migrados y probados.

## Propuesta De Ejecucion Inicial

Empezar por Fase 1 completa:

1. Migracion de tabla nueva.
2. Modelo.
3. Seeder.
4. Registrar o documentar ejecucion del seeder.
5. No tocar calculos ni UI todavia.

Esto deja la base lista sin cambiar comportamiento productivo.{
NOTAS ECTRAS 
Notas operativas generales
nos falta en config-empresa
crear elcatalogo de tipos de suedo-> hoy tenemos 2 tipos pre-definidos semanal y quincenal, pero no puedo agregar otro desde el sistema, tendria que hacerlo desde la base de datos , quiero verlos en la vista de config de empresa, en un tab nuebvo siguiendo el patron de partialls para no cargar mucho la vista

en el partial 
[https://sirico.riveraco.com.mx/v2/public/configuracion-empresa?tab=general](https://sirico.riveraco.com.mx/v2/public/configuracion-empresa?tab=general)
quiero agregar el check para que que la empresa que elijamos (hoy solo hay una pero mas adelante seran mas en facturacion )-> quiero poder elegir la empresa por defecto en el select 
[https://sirico.riveraco.com.mx/v2/public/sat/facturacion/create](https://sirico.riveraco.com.mx/v2/public/sat/facturacion/create)

aqui agregar un boton para reporte mensual
https://sirico.riveraco.com.mx/v2/public/sat/facturacion
facturado->total
facturado->no cobrado
facturado->cobrado
ahi mismo podiramos agregar datos generales
cliente con mayor facturacion
cliente que se tardo mas en pagar una factura
cliente que paga mas rapido las facturas (algo asi)

