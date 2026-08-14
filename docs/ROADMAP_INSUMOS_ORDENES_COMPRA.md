# Roadmap: Explosion De Insumos Y Ordenes De Compra

## Objetivo

Crear un nuevo flujo para cargar la explosion de insumos de una obra civil y usar esos insumos como base real para generar ordenes de compra.

La orden de compra existente se conserva, pero se ajustara su fuente de datos. En lugar de seleccionar partidas/conceptos del presupuesto general, debera seleccionar insumos de la explosion de insumos asociada a la obra.

## Contexto

Actualmente existe un mecanismo de ordenes de compra que permite seleccionar elementos, capturar cantidades y generar registros con detalle.

Ese mecanismo puede aprovecharse, pero el origen correcto para las ordenes de compra no son las partidas del presupuesto, sino la explosion de insumos.

Separacion conceptual esperada:

```text
Obra civil
 ├─ Partidas/conceptos del presupuesto
 │   └─ Estimaciones
 │
 └─ Explosion de insumos
     └─ Ordenes de compra
```

## Hallazgos Del Excel T23

Archivo revisado:

```text
T23.xlsx
```

Ruta de referencia:

```text
//192.168.105.55/Pilas/PI-26/PI-26-21 AULAS (INFEJAL)/PRESUPUESTO/CONCURSO INFEJAL-LP-0222-2026/T23.xlsx
```

Hoja detectada:

```text
T23 e)Listado Insumos (E)
```

Dimensiones observadas:

```text
1006 filas
8 columnas
```

La tabla inicia en la fila donde la celda de la columna A dice:

```text
Codigo
```

En el archivo revisado, esa celda es:

```text
A17
```

Columnas de la tabla:

| Columna | Campo |
| --- | --- |
| A | Codigo |
| B | Concepto |
| C | Unidad |
| D | Cantidad |
| E | Ano |
| F | Precio |
| G | Importe |
| H | % Incidencia |

Grupos principales detectados:

- `MATERIALES`
- `MANO DE OBRA`
- `EQUIPO Y HERRAMIENTA`

## Regla Principal De Importacion

No se debe quemar la fila 17 como inicio fijo.

El importador debe buscar dinamicamente la fila donde:

```text
Columna A = Codigo
```

Esa fila se toma como encabezado de tabla.

La lectura de insumos inicia en la fila siguiente.

## Renglones Continuados

El Excel puede traer conceptos largos partidos en varias filas.

Ejemplo:

```text
Fila 24:
Codigo: 04008
Concepto: Registro 33 x 33 x 40 cm, fierro galvanizado con
Unidad: PZA
Cantidad: 12
Precio: 570
Importe: 6840

Fila 25:
Codigo: vacio
Concepto: tapa y marco
```

Resultado esperado:

```text
Codigo: 04008
Concepto: Registro 33 x 33 x 40 cm, fierro galvanizado con tapa y marco
Unidad: PZA
Cantidad: 12
Precio: 570
Importe: 6840
```

Regla:

```text
Si columna A esta vacia
y columna B tiene texto
y existe un insumo anterior abierto
y el texto no es una categoria conocida
entonces ese texto pertenece al concepto anterior.
```

## Algoritmo Propuesto Para Cargar Insumos

```text
1. Abrir Excel.
2. Buscar la fila donde la columna A sea igual a "Codigo".
3. Tomar esa fila como encabezado.
4. Iniciar lectura desde encabezado + 1.
5. Mantener una variable current_tipo.
6. Mantener una variable current_insumo.
7. Por cada fila:

   A = codigo
   B = concepto
   C = unidad
   D = cantidad
   F = precio
   G = importe
   H = incidencia

   Si la fila esta vacia:
       continuar

   Si A esta vacia y B es categoria conocida:
       current_tipo = categoria
       continuar

   Si A tiene codigo y la fila tiene datos validos:
       guardar current_insumo pendiente, si existe
       crear nuevo current_insumo
       continuar

   Si A esta vacia y B tiene texto y current_insumo existe:
       anexar B al concepto de current_insumo
       continuar

8. Al terminar, guardar el ultimo current_insumo pendiente.
```

## Validacion De Nuevo Insumo

Para considerar una fila como nuevo insumo, se recomienda validar:

- Columna A tiene codigo.
- Columna B tiene concepto.
- Columna C tiene unidad.
- Columna D tiene cantidad numerica.
- Columna F tiene precio numerico.

La columna G puede usarse como importe importado, pero tambien se puede recalcular:

```text
importe = cantidad * precio_unitario
```

Si hay diferencia entre importe importado e importe calculado, conviene guardar ambos o registrar advertencia.

## Modelo De Datos Propuesto

Tabla:

```text
obra_civil_insumos
```

Campos sugeridos:

- `id`
- `obra_civil_id`
- `tipo`
- `codigo`
- `concepto`
- `unidad`
- `cantidad_presupuestada`
- `precio_unitario`
- `importe`
- `incidencia`
- `source_file`
- `source_sheet`
- `source_row`
- `created_at`
- `updated_at`

Notas:

- `tipo` puede guardar valores como `material`, `mano_obra`, `equipo_herramienta`.
- `codigo` debe conservar el valor original del Excel.
- `concepto` debe guardar el texto ya unido.
- `source_row` ayuda a rastrear errores de importacion.

## Relacion Con Ordenes De Compra

La orden de compra debe usar los insumos como catalogo base.

Flujo esperado:

```text
Obra civil
 -> Cargar insumos
 -> Lista de insumos disponibles
 -> Generar orden de compra
 -> Seleccionar insumos
 -> Capturar cantidades a comprar
 -> Guardar orden de compra
```

Cada detalle de orden de compra debe guardar snapshot del insumo:

- Codigo.
- Concepto.
- Unidad.
- Cantidad solicitada/comprada.
- Precio unitario.
- Importe.
- Referencia al insumo original.

Esto evita que cambios posteriores en la explosion de insumos alteren ordenes ya generadas.

## Ajuste En La Vista De Obra Civil

Vista objetivo:

```text
/obra_civil/{id}/detalles
```

Agregar:

- Boton `Cargar insumos`.
- Seccion o pestana de `Insumos`.
- Resumen de insumos cargados.
- Accion para generar orden de compra desde insumos.

Indicadores sugeridos:

- Total de insumos.
- Total presupuestado de insumos.
- Total comprado.
- Total pendiente por comprar.
- Ordenes de compra generadas.

Ejemplo:

```text
Insumos: 742 | Presupuesto insumos: $19,630,000.00 | Comprado: $850,000.00 | Pendiente: $18,780,000.00
```

## Modal Cargar Insumos

El boton `Cargar insumos` debe permitir subir un archivo Excel.

Comportamiento sugerido:

- Seleccionar archivo `.xlsx`.
- Validar que exista una hoja legible.
- Buscar encabezado `Codigo` en columna A.
- Mostrar vista previa de insumos detectados.
- Mostrar cantidad de insumos por grupo.
- Mostrar advertencias de filas omitidas.
- Confirmar importacion.

Opciones:

- Reemplazar insumos existentes de la obra.
- Agregar nuevos insumos.
- Actualizar insumos por codigo.

Para MVP, se recomienda:

```text
Reemplazar insumos existentes de la obra
```

Esto evita duplicados mientras se valida el flujo.

## Modal Generar Orden De Compra

El modal actual de ordenes de compra se puede reutilizar con nueva fuente.

Fuente anterior:

```text
partidas/conceptos de presupuesto
```

Nueva fuente:

```text
obra_civil_insumos
```

Columnas sugeridas:

- Selector.
- Tipo.
- Codigo.
- Concepto.
- Unidad.
- Cantidad presupuestada.
- Cantidad ya comprada.
- Cantidad disponible.
- Cantidad a comprar.
- Precio unitario.
- Importe.

Validaciones:

- No permitir cantidad menor o igual a 0.
- No permitir comprar mas de la cantidad disponible, salvo permiso posterior.
- No guardar renglones no seleccionados.
- No crear orden vacia.
- Guardar snapshot del insumo en el detalle de orden.

## Estados De Orden De Compra

Si ya existen estados, conservarlos.

Estados que podrian manejarse mas adelante:

- `creada`
- `autorizada`
- `enviada`
- `recibida`
- `cancelada`

Para este ajuste, el foco no es redisenar estados, sino cambiar la fuente de datos a insumos.

## Roadmap Por Fases

### Fase 1: Analisis Del Codigo Existente

Objetivo: ubicar el flujo actual de ordenes de compra y como se relaciona con la obra.

Pasos:

- [ ] Identificar modelos actuales de ordenes de compra.
- [ ] Identificar tablas actuales de detalle de orden de compra.
- [ ] Revisar controlador que genera ordenes de compra.
- [ ] Revisar vista/modal actual de ordenes de compra.
- [ ] Identificar como se cargan partidas/conceptos actuales.
- [ ] Confirmar si ya existe algun modelo reusable para importacion de Excel.

Checkpoint:

- [ ] Sabemos que partes se reutilizan y que partes se cambian.

### Fase 2: Modelo De Insumos

Objetivo: crear la estructura persistente para la explosion de insumos.

Pasos:

- [ ] Crear migracion `obra_civil_insumos`.
- [ ] Crear modelo `ObraCivilInsumo`.
- [ ] Agregar relacion en modelo de obra civil.
- [ ] Definir casts numericos.
- [ ] Definir normalizacion de `tipo`.
- [ ] Definir regla unica sugerida: `obra_civil_id + codigo`.

Checkpoint:

- [ ] La obra puede tener insumos relacionados en base de datos.

### Fase 3: Importador Cargar Insumos

Objetivo: leer el Excel de explosion de insumos y guardarlo limpio.

Pasos:

- [ ] Crear servicio importador de insumos.
- [ ] Buscar encabezado por columna A = `Codigo`.
- [ ] Leer filas desde encabezado + 1.
- [ ] Detectar categorias conocidas.
- [ ] Detectar nuevos insumos.
- [ ] Unir renglones continuados.
- [ ] Validar cantidad y precio.
- [ ] Guardar `source_file`, `source_sheet` y `source_row`.
- [ ] Generar resumen de importacion.
- [ ] Registrar filas omitidas o advertencias.

Checkpoint:

- [ ] Se puede importar el archivo T23 y obtener insumos limpios.

### Fase 4: UI Cargar Insumos

Objetivo: permitir que el usuario cargue la explosion desde la vista de obra.

Pasos:

- [ ] Agregar boton `Cargar insumos`.
- [ ] Crear modal o formulario de subida.
- [ ] Validar extension del archivo.
- [ ] Mostrar resultado de importacion.
- [ ] Mostrar cantidad de insumos importados por tipo.
- [ ] Mostrar errores o advertencias.
- [ ] Confirmar si se reemplazan insumos anteriores.

Checkpoint:

- [ ] El usuario puede cargar insumos desde `/obra_civil/{id}/detalles`.

### Fase 5: Lista De Insumos

Objetivo: mostrar los insumos cargados y sus acumulados.

Pasos:

- [ ] Agregar seccion o pestana de insumos.
- [ ] Mostrar filtros por tipo.
- [ ] Mostrar busqueda por codigo/concepto.
- [ ] Mostrar cantidad presupuestada.
- [ ] Mostrar precio unitario.
- [ ] Mostrar importe.
- [ ] Mostrar cantidad ya comprada.
- [ ] Mostrar cantidad pendiente.

Checkpoint:

- [ ] El usuario puede revisar la explosion importada dentro de la obra.

### Fase 6: Reconectar Ordenes De Compra

Objetivo: usar insumos como fuente para generar ordenes de compra.

Pasos:

- [ ] Cambiar consulta del modal de ordenes para traer insumos.
- [ ] Ajustar columnas visibles del modal.
- [ ] Calcular cantidad ya comprada por insumo.
- [ ] Calcular cantidad disponible.
- [ ] Validar cantidades contra disponibilidad.
- [ ] Guardar referencia a `obra_civil_insumo_id` en detalle de orden.
- [ ] Guardar snapshot de codigo, concepto, unidad y precio.

Checkpoint:

- [ ] Las ordenes de compra se generan desde insumos, no desde partidas.

### Fase 7: Resumen De Compras En Obra

Objetivo: mostrar avance de compras desde la vista de obra.

Pasos:

- [ ] Calcular total presupuestado de insumos.
- [ ] Calcular total comprado.
- [ ] Calcular total pendiente.
- [ ] Calcular numero de ordenes de compra.
- [ ] Excluir ordenes canceladas, si aplica.
- [ ] Mostrar indicadores en header o seccion de compras.

Checkpoint:

- [ ] La obra muestra cuanto se ha comprado y cuanto falta por comprar.

### Fase 8: Pruebas

Objetivo: validar que el ajuste sea confiable.

Pasos:

- [ ] Probar importacion de T23.
- [ ] Probar union de conceptos partidos en varias filas.
- [ ] Probar deteccion de `MATERIALES`.
- [ ] Probar deteccion de `MANO DE OBRA`.
- [ ] Probar deteccion de `EQUIPO Y HERRAMIENTA`.
- [ ] Probar orden de compra con un insumo.
- [ ] Probar orden de compra con varios insumos.
- [ ] Probar bloqueo por cantidad mayor a disponible.
- [ ] Probar que ordenes anteriores no cambien si se reimportan insumos.
- [ ] Probar que el flujo de estimaciones no se afecte.

Checkpoint:

- [ ] El flujo de ordenes de compra queda alineado con explosion de insumos.

## Decisiones Pendientes

- [ ] Si `codigo` sera unico por obra o puede repetirse por tipo.
- [ ] Si el MVP reemplaza todos los insumos al reimportar.
- [ ] Si se permitira comprar mas que la cantidad presupuestada.
- [ ] Si mano de obra y equipo tambien deben aparecer en ordenes de compra o solo materiales.
- [ ] Si se creara una pantalla separada de insumos o solo una seccion en detalles de obra.
- [ ] Si se guardara importe importado, importe calculado o ambos.
- [ ] Si se permitira editar insumos manualmente despues de importarlos.

## Riesgos A Cuidar

- Confundir partidas de estimacion con insumos de compra.
- Duplicar insumos al cargar varias veces el mismo archivo.
- Interpretar renglones continuados como categorias.
- Perder descripcion completa de conceptos largos.
- Que una reimportacion modifique ordenes de compra historicas.
- Que las cantidades compradas no se acumulen correctamente.
- Que conceptos de mano de obra o equipo entren al flujo de compra sin decision de negocio.

## Checkpoints Globales

- [ ] Roadmap de insumos creado.
- [ ] Modelo de insumos definido.
- [ ] Importador de insumos creado.
- [ ] Boton `Cargar insumos` agregado.
- [ ] Vista/lista de insumos agregada.
- [ ] Ordenes de compra reconectadas a insumos.
- [ ] Resumen de compras agregado.
- [ ] Pruebas manuales completadas con T23.

## Bitacora

| Fecha | Checkpoint | Estado | Notas |
| --- | --- | --- | --- |
| 2026-08-14 | Analisis inicial de T23 | Completado | Se detecto encabezado por `Codigo`, grupos principales y renglones continuados. |
| 2026-08-14 | Roadmap inicial creado | Completado | Se definio plan para `Cargar insumos` y reconectar ordenes de compra al nuevo modelo. |

