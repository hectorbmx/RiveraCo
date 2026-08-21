# Roadmap De Rendimiento Del Sistema

## Objetivo

Mejorar la fluidez general del sistema sin detener el desarrollo actual ni hacer una reestructuracion masiva riesgosa. La estrategia sera aplicar reglas nuevas desde ahora y migrar gradualmente las pantallas existentes mas pesadas.

## Diagnostico Inicial

### Hallazgos globales

- El layout principal se ejecuta en cada navegacion y actualmente hace trabajo repetido de permisos y notificaciones.
- Varias vistas Blade son muy grandes y mezclan muchas responsabilidades en una sola pantalla.
- Hay controladores que cargan catalogos completos con `get()` aunque esos datos pueden crecer.
- Algunas vistas embeben datasets grandes en HTML/JS con `@js(...)`, lo que aumenta el peso inicial de la pagina.
- En produccion, si no estan activos los caches de Laravel y OPcache, cada navegacion paga un costo extra.

### Pantallas candidatas a optimizacion

1. `obras/edit.blade.php`
2. `obra_civil/details.blade.php`
3. `obra_civil/insumos/index.blade.php`
4. `empresa_config/edit.blade.php`
5. `sat/cfdis/index.blade.php`
6. `ordencompra/index.blade.php`
7. `ordencompra/edit.blade.php`

## Principios Para Lo Nuevo

A partir de este punto, las pantallas nuevas o cambios grandes deberian seguir estas reglas:

- No cargar tablas grandes con `get()` si pueden crecer; usar paginacion.
- Todo listado operativo debe tener filtros en servidor, no solo en frontend.
- Evitar mandar colecciones grandes dentro del HTML con `@js(...)`.
- Cargar secciones pesadas por endpoints dedicados cuando no sean necesarias para el primer render.
- Toda accion `POST`, `PUT`, `PATCH` o `DELETE` debe mostrar modal de carga.
- Mantener indicadores globales separados de resultados filtrados/paginados.
- Evitar que vistas Blade concentren demasiadas secciones no relacionadas.

## Fase 1: Base Global

### 1.1 Layout principal

Objetivo: reducir trabajo repetido en cada navegacion.

Tareas:

- Mover calculo de permisos del menu fuera del Blade directo.
- Cachear permisos efectivos del usuario durante la request o por un TTL corto.
- Consultar notificaciones no leidas una sola vez por request.
- Pasar al layout variables ya preparadas: `menuPermissions`, `unreadNotificationsCount`, `unreadNotificationsPreview`.

Riesgo:

- Medio. Toca navegacion global y permisos visibles del menu.

Prioridad:

- Alta.

### 1.2 Configuracion de produccion

Objetivo: asegurar que Laravel corra en modo produccion real.

Checklist:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- OPcache activo en PHP.
- Revisar driver de cache y sesiones.

Riesgo:

- Bajo si se ejecuta con cuidado.

Prioridad:

- Alta.

### 1.3 Modal global de carga

Estado:

- Iniciado. Ya existe un handler global para formularios no `GET`.

Pendientes:

- Revisar formularios especiales que deban excluirse con `data-no-loading="true"`.
- Homologar mensajes `data-loading-message` en acciones importantes.

Riesgo:

- Bajo.

Prioridad:

- Media.

## Fase 2: Listados Y Tablas

### 2.1 Paginacion estandar

Objetivo: que ninguna tabla grande renderice todos los registros.

Regla propuesta:

- Default: 25 registros.
- Opciones: 25, 50, 100, 200.
- Maximo permitido por request: 200.
- Preservar query string con `withQueryString()`.

Pantallas iniciales:

- `obra_civil/insumos`
- `ordenes_compra/index`
- `sat/cfdis/index`
- `clientes/index` si crece mas.

Riesgo:

- Bajo a medio.

Prioridad:

- Alta.

### 2.2 Busqueda y filtros en servidor

Objetivo: que los filtros reduzcan datos antes de renderizar.

Reglas:

- Usar parametros `q`, `per_page`, `page` y filtros especificos.
- Mantener totales globales separados de totales filtrados.
- Mostrar texto de resultados: `Mostrando X de Y`.

Riesgo:

- Bajo.

Prioridad:

- Alta.

## Fase 3: Carga Diferida Por Secciones

Objetivo: que la estructura de la vista cargue primero y las secciones pesadas despues.

Patron recomendado:

- Blade renderiza estructura base.
- Un endpoint devuelve JSON o un parcial HTML.
- El frontend usa `fetch()` para llenar la seccion.
- Mostrar skeleton/loading mientras responde.

Candidatos:

- Tabla de conceptos en `obra_civil/details`.
- Modal de generar estimacion.
- Secciones secundarias en `obras/edit`.
- Tablas de CFDI/SAT.

Riesgo:

- Medio.

Prioridad:

- Media.

## Fase 4: Pantallas Pesadas Existentes

### 4.1 `obra_civil/details`

Problema:

- Carga todo el arbol `buildings.partidas.concepts`.
- Embebe conceptos en Alpine con `@js(...)`.
- Puede generar HTML muy pesado.

Plan:

- Separar resumen del detalle completo.
- Cargar conceptos por partida bajo demanda.
- Cambiar modal de estimaciones a busqueda AJAX.
- Calcular balances solo para conceptos visibles o seleccionados.

Prioridad:

- Alta.

### 4.2 `obra_civil/insumos`

Problema:

- Actualmente aun puede cargar todos los insumos y balances.

Plan:

- Agregar paginacion en servidor.
- Filtro por unidad.
- Selector `per_page`.
- Calcular balances solo para pagina visible.
- Mantener cards superiores globales.

Prioridad:

- Alta.

### 4.3 `obras/edit`

Problema:

- Vista Blade muy grande.
- Probablemente mezcla muchas secciones en una sola carga inicial.

Plan:

- Dividir en tabs/secciones cargadas bajo demanda.
- Cargar historiales y tablas secundarias por endpoint.
- Mantener solo datos basicos de la obra en el primer render.

Prioridad:

- Alta, pero requiere cuidado.

### 4.4 `empresa_config/edit`

Problema:

- Vista grande con muchas configuraciones distintas.

Plan:

- Separar por tabs reales o parciales cargados bajo demanda.
- Evitar traer todos los catalogos si el tab no esta activo.

Prioridad:

- Media.

### 4.5 SAT y CFDI

Problema:

- Listados y formularios con muchos catalogos y estados.

Plan:

- Revisar paginacion.
- Mover catalogos grandes a busqueda AJAX.
- Cachear catalogos SAT estables.

Prioridad:

- Media.

## Fase 5: Observabilidad

Objetivo: medir antes y despues para no optimizar a ciegas.

Tareas:

- Agregar medicion temporal de tiempo de request en local/staging.
- Identificar numero de queries por pantalla.
- Medir peso de HTML renderizado en pantallas clave.
- Revisar queries lentas en produccion.

Herramientas posibles:

- Laravel Telescope en entorno no productivo.
- Laravel Debugbar solo local.
- Logs manuales temporales con `DB::listen` en staging.
- Slow query log de MySQL.

Prioridad:

- Media.

## Orden Recomendado De Ejecucion

1. Validar configuracion de produccion y caches.
2. Optimizar layout global: permisos y notificaciones.
3. Terminar paginacion/filtros de `obra_civil/insumos`.
4. Optimizar `obra_civil/details` quitando `@js(...)` pesado.
5. Auditar `obras/edit` y dividir secciones pesadas.
6. Revisar SAT/CFDI y Empresa Config.
7. Medir de nuevo y ajustar prioridades.

## Criterios De Terminado

Una pantalla optimizada debe cumplir:

- Primer render razonablemente rapido.
- No renderizar miles de filas en HTML.
- No cargar datasets grandes en `@js(...)` salvo que sean pequenos.
- Acciones de escritura con modal de carga.
- Filtros y paginacion conservan query string.
- No rompe permisos ni reglas de negocio.

## Notas

- No conviene hacer una reestructuracion total de golpe.
- Lo nuevo debe seguir este patron desde ahora.
- Lo existente se migrara por prioridad, empezando por pantallas que el usuario realmente siente lentas.
