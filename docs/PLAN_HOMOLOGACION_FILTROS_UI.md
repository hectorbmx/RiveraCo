# Plan de homologacion de filtros UI

Fecha: 2026-09-03

## Objetivo

Homologar los headers/cards de busqueda y filtros en toda la aplicacion para que tengan un comportamiento visual consistente, reduzcan duplicacion de clases Blade/Tailwind y permitan evolucionar el diseno desde un solo lugar.

El patron aprobado visualmente es el card azul usado en `productos` y `giralda/empleados`:

- Fondo azul del sidebar: `#0B265A`.
- Contenedor `rounded-2xl`, sombra y padding uniforme.
- Labels en blanco suave.
- Inputs/selects blancos, compactos, con `rounded-xl`.
- Campo principal de busqueda con borde/halo amarillo.
- Boton primario amarillo `#FFC107` con texto azul.
- Boton limpiar transparente sobre azul.
- Acciones alineadas a la derecha en desktop y fluidas en mobile.

## Principios de trabajo

- Reutilizar primero: crear componentes Blade pequenos antes de seguir copiando clases por pantalla.
- SOLID aplicado al frontend Blade:
  - Single Responsibility: cada componente resuelve una parte concreta del filtro.
  - Open/Closed: agregar nuevos campos por slots/props sin modificar el componente base.
  - Interface Segregation: inputs, selects, fechas y acciones como piezas separadas.
  - Dependency Inversion: las vistas entregan datos/opciones; el componente no conoce modelos ni controladores.
- No mezclar cambios visuales con cambios de negocio si no es necesario.
- Mantener compatibilidad con query strings existentes y paginacion.
- Evitar un componente gigante que intente resolver todos los casos.
- Migrar por bloques pequenos y validar despues de cada bloque.

## Componentes propuestos

Ubicacion sugerida: `resources/views/components/filters/`

### `filters.card`

Responsabilidad: contenedor visual y estructura general del formulario.

Props sugeridas:

- `action`: URL o ruta del formulario.
- `method`: por defecto `GET`.
- `columns`: clases grid opcionales, por defecto `grid grid-cols-1 md:grid-cols-12 gap-3 items-end`.
- `class`: clases extra si una pantalla necesita ancho/espaciado adicional.

Debe renderizar:

- `form` con fondo `bg-[#0B265A]`.
- Slot principal para campos.
- Sin logica de dominio.

### `filters.input`

Responsabilidad: campo de texto/busqueda reutilizable.

Props sugeridas:

- `name`
- `label`
- `value`
- `placeholder`
- `span`: clases de columna, ejemplo `md:col-span-5`.
- `glow`: booleano para aplicar halo amarillo.
- `type`: por defecto `text`, tambien puede ser `search`.

### `filters.select`

Responsabilidad: select reutilizable.

Props sugeridas:

- `name`
- `label`
- `value`
- `options`: arreglo simple `value => label`.
- `span`: clases de columna, ejemplo `md:col-span-2 md:max-w-44`.
- `autoSubmit`: opcional, si alguna vista ya usa `onchange="this.form.submit()"`.

### `filters.date`

Responsabilidad: input de fecha con estilo consistente.

Props sugeridas:

- `name`
- `label`
- `value`
- `span`

### `filters.actions`

Responsabilidad: botones de accion del filtro.

Props sugeridas:

- `submitLabel`: `Filtrar`, `Buscar`, `Aplicar filtros`, etc.
- `clearUrl`: URL para limpiar filtros.
- `span`: clases de columna.
- Slot opcional para acciones extra: `Excel`, `Exportar`, `Imprimir`, `Generar corrida`.

## Pantallas base ya ajustadas manualmente

Estas dos pantallas sirven como referencia visual y luego deben migrarse al componente para cerrar duplicacion:

- `resources/views/productos/index.blade.php`
- `resources/views/giralda/empleados.blade.php`

Checkpoint:

- Confirmar que al migrarlas al componente no cambia el HTML funcional ni se pierden query params.
- Confirmar que `php artisan view:cache` compila.

## Fase 1: crear componentes sin migrar pantallas

Trabajo:

- Crear componentes Blade en `resources/views/components/filters/`.
- Copiar el patron visual aprobado desde productos/Giralda.
- Documentar en comentarios minimos las props esperadas si hace falta.
- No tocar controladores en esta fase.

Checklist:

- [ ] Crear `filters.card`.
- [ ] Crear `filters.input`.
- [ ] Crear `filters.select`.
- [ ] Crear `filters.date`.
- [ ] Crear `filters.actions`.
- [ ] Compilar vistas con `php artisan view:cache`.
- [ ] Revisar que no haya dependencia de una sola pantalla.

Checkpoint de aceptacion:

- Se puede construir el filtro de productos usando componentes sin perder el diseno aprobado.

## Fase 2: migrar las pantallas patron

Pantallas:

- `/productos`
- `/giralda/empleados?tab=asistencia`

Trabajo:

- Reemplazar el markup manual por componentes.
- Mantener los parametros actuales:
  - Productos: `q`, `estado`, `existencias`.
  - Giralda: `tab`, `q`, `estatus`, `semana`.
- Mantener enlaces de limpiar, imprimir y navegacion de semana.

Checklist:

- [ ] Migrar productos.
- [ ] Migrar Giralda empleados.
- [ ] Verificar filtros activos en URL.
- [ ] Verificar limpiar filtros.
- [ ] Verificar paginacion/links con query string cuando aplique.
- [ ] `php artisan view:cache`.

Checkpoint de aceptacion:

- Visualmente se mantiene igual o mejor que el patron actual.
- No cambia la logica de filtrado.

## Fase 3: migrar pantallas que ya tienen filtros funcionales

Pantallas detectadas en el barrido inicial:

- `/clientes` -> `resources/views/clientes/index.blade.php`
- `/agenda` -> `resources/views/agenda/index.blade.php`
- `/empleados` -> `resources/views/empleados/index.blade.php`
- `/attendance/logs` -> `resources/views/attendance/logs/index.blade.php`
- `/nomina/generador` -> `resources/views/nomina/generador.blade.php`

Trabajo por pantalla:

- Mantener nombres de parametros actuales.
- No cambiar query de backend salvo que exista bug evidente.
- Homologar contenedor, labels, inputs/selects y botones.
- Conservar acciones especiales:
  - Attendance logs: exportar Excel.
  - Nomina generador: generar corrida.
  - Clientes/Agenda: selector de filas por pagina.
  - Empleados: filtros por area y estatus.

Checklist por pantalla:

- [ ] Identificar formulario GET principal.
- [ ] Reemplazar contenedor por `filters.card`.
- [ ] Reemplazar inputs/selects por componentes.
- [ ] Revisar botones extra.
- [ ] Confirmar que limpiar filtros lleva al estado esperado.
- [ ] Confirmar paginacion con filtros.
- [ ] Compilar Blade.

Checkpoint de aceptacion:

- Cada pantalla conserva su funcionalidad previa y adopta el mismo lenguaje visual.

## Fase 4: agregar filtros donde hoy no existen

Pantallas detectadas sin filtro superior funcional:

- `/mantenimiento/vehiculos`
- `/maquinas`
- `/mantenimiento/mantenimientos`

Este bloque debe tratarse como cambio funcional, no solo visual.

### Vehiculos

Posibles filtros:

- Busqueda por marca, modelo, placas, serie, tipo.
- Estatus.
- Asignacion: todos, asignados, no asignados.
- Atencion documental: todos, con alertas, sin alertas.

Checkpoint tecnico:

- Cambiar `VehiculoController@index()` para recibir `Request`.
- Aplicar filtros antes de paginar.
- Mantener calculos de preventivo/documentos despues de obtener la coleccion paginada.
- Usar `withQueryString()`.

### Maquinas

Posibles filtros:

- Busqueda por codigo, nombre, tipo.
- Estado.
- Ubicacion.
- Obra actual.
- Preventivo: ok, proximo, vencido, sin datos.

Checkpoint tecnico:

- Cambiar `MaquinaController@index()` para recibir `Request` junto con `PreventivoMaquinaService`.
- Aplicar filtros de BD antes de cargar la coleccion.
- Definir si preventivo se filtra en memoria o con datos persistidos. Si se filtra en memoria, documentar el tradeoff.

### Mantenimientos

Posibles filtros:

- Busqueda por activo, placas/codigo, mecanico.
- Tipo: vehiculo/maquina.
- Estatus.
- Categoria.
- Rango de fechas.

Checkpoint tecnico:

- Cambiar `MantenimientoController@index()` para recibir `Request`.
- Filtrar con relaciones `vehiculo`, `maquina`, `mecanico`.
- Usar `withQueryString()`.

Checklist:

- [ ] Definir filtros minimos por pantalla con usuario antes de implementar.
- [ ] Agregar backend.
- [ ] Agregar UI con componentes.
- [ ] Probar combinaciones basicas.
- [ ] Compilar Blade.

## Fase 5: barrido extendido de toda la app

El barrido global encontro mas formularios GET similares. Candidatos para homologacion posterior:

- `cajas-chicas/index.blade.php`
- `admin/costos/materiales/index.blade.php`
- `admin/costos/materiales/show.blade.php`
- `inventario/stock/index.blade.php`
- `inventario/kardex/index.blade.php`
- `inventario/documentos/index.blade.php`
- `ordencompra/index.blade.php`
- `facturas/index.blade.php`
- `pagos_proveedores/index.blade.php`
- `proveedores/index.blade.php`
- `programacion_pagos/index.blade.php`
- `reposicion-caja-chica/index.blade.php`
- `obras/index.blade.php`
- `obras/partials/asistencias/_filters.blade.php`
- `sat/cfdis/index.blade.php`
- `sat/facturacion/index.blade.php`
- `sat/complementos-pago/index.blade.php`
- `sat/cfdis/estadisticas/index.blade.php`
- `giralda/index.blade.php`
- `empresa_config/edit.blade.php`

Criterio para entrar al componente:

- Es un filtro GET visible de listado, dashboard operativo o reporte.
- Tiene 2 o mas controles o un buscador principal.
- No es un formulario inline minimo ni un boton aislado.

Criterio para posponer:

- Formularios dentro de tabs complejos con layout muy especifico.
- Filtros embebidos en reportes impresos o embeds.
- Formularios GET usados como navegacion puntual.

## Checkpoints tecnicos generales

Despues de cada lote:

- [ ] `php artisan view:cache`.
- [ ] `php -l` en controladores modificados.
- [ ] Verificar URLs con query string.
- [ ] Verificar boton limpiar.
- [ ] Verificar paginacion conserva filtros.
- [ ] Verificar mobile: grid en una columna, sin solapes.
- [ ] Verificar desktop: acciones a la derecha.
- [ ] Revisar `git diff` para confirmar cambios acotados.

## Riesgos y mitigaciones

### Riesgo: componente demasiado rigido

Mitigacion:

- Usar slots y props simples.
- Permitir clases de columna por campo.
- Mantener acciones extra como slot.

### Riesgo: romper query strings existentes

Mitigacion:

- Migrar una pantalla por vez.
- Mantener `name` de cada input.
- Revisar `withQueryString()` o `appends()` en controladores paginados.

### Riesgo: mezclar estilo con logica de negocio

Mitigacion:

- Fases 1 a 3 solo visual/componente en pantallas con filtros existentes.
- Fase 4 separada para filtros nuevos.

### Riesgo: inconsistencia de labels y botones

Mitigacion:

- Definir labels por dominio en cada vista.
- Mantener estilo centralizado en componentes.
- No forzar todos los botones a llamarse igual si la accion real cambia.

## Orden recomendado de ejecucion

1. Crear componentes reusable.
2. Migrar `/productos`.
3. Migrar `/giralda/empleados`.
4. Migrar `/clientes`.
5. Migrar `/agenda`.
6. Migrar `/empleados`.
7. Migrar `/attendance/logs`.
8. Migrar `/nomina/generador`.
9. Definir e implementar filtros nuevos en vehiculos, maquinas y mantenimientos.
10. Ejecutar barrido extendido por modulos.

## Resultado esperado

Al final, cada pantalla con buscador/filtros debe sentirse parte del mismo sistema visual, y cualquier ajuste futuro al estilo base debe poder hacerse en componentes, sin perseguir clases repetidas por toda la aplicacion.
