# Roadmap: Modulo de Obra Civil

## Objetivo

Agregar al sistema una nueva modalidad de obra llamada **Obra Civil**, independiente del flujo actual basado en pilas de cimentacion.

La nueva modalidad debe permitir importar un catalogo de conceptos desde Excel, organizarlo por edificios y partidas, guardar cada concepto con un ID propio, y usar esos conceptos como base para generar ordenes de compra controlando cantidades, montos y saldos.

## Contexto Inicial

El archivo recibido contiene una hoja llamada `CATALOGO`.

Columnas principales detectadas:

- `A`: Clave
- `B`: Descripcion
- `C`: Unidad
- `D`: Cantidad
- `E`: Precio
- `F`: Precio unitario en texto
- `G`: Importe
- `H`: Columna auxiliar / separador visual segun formato

La hoja esta separada visualmente por:

- Filas azules: edificios o frentes principales.
- Filas verdes: partidas.
- Filas normales: conceptos comprables.

Edificios detectados inicialmente en las filas:

- 6
- 116
- 259
- 389
- 412

## Principios Del Diseno

- Obra Civil debe ser un modulo propio, no una variante forzada del modelo de pilas.
- Las ordenes de compra pueden compartir infraestructura existente, pero los conceptos deben venir del catalogo civil.
- Cada concepto importado debe tener un ID interno unico.
- La clave del Excel no debe usarse como identificador unico global.
- El sistema debe conservar trazabilidad del archivo importado, fila origen y estructura original.
- El saldo debe calcularse de forma auditable, no editarse manualmente sin registro.

## Modelo Funcional Propuesto

```text
Obra Civil
  Edificio / Frente
    Partida
      Concepto
        Ordenes de compra
        Cantidad comprometida
        Cantidad comprada
        Saldo
```

## Fase 1: Analisis Y Definicion Funcional

### Tareas

- Confirmar si una obra puede tener uno o varios catalogos importados.
- Confirmar si el catalogo se importa una sola vez o si habra versiones/reimportaciones.
- Definir si el control de saldo sera por cantidad, monto o ambos.
- Definir si se permitiran compras con precio distinto al precio del catalogo.
- Definir estados de orden de compra que afectaran el saldo.
- Definir si se permitiran sobregiros contra el presupuesto.
- Definir permisos para importar, validar, aprobar y usar catalogos.

### Decisiones Recomendadas

- Controlar por cantidad y monto.
- Permitir precio diferente en OC, pero registrar variacion contra precio catalogo.
- No descontar saldo en OC borrador.
- Comprometer saldo cuando la OC sea autorizada.
- Consumir saldo real cuando la OC sea recibida, cerrada o facturada.
- Liberar saldo si una OC autorizada se cancela.
- Bloquear sobregiros en primera version, salvo autorizacion futura.

### Checkpoint

- Documento funcional aprobado.
- Reglas de saldo aprobadas.
- Estados de OC que afectan saldo definidos.
- Alcance de la primera version cerrado.

## Fase 2: Diseno De Datos

### Tablas Nuevas Propuestas

#### `civil_catalog_imports`

Guarda cada carga de catalogo.

Campos sugeridos:

- `id`
- `obra_id`
- `filename`
- `original_path`
- `sheet_name`
- `status`
- `imported_by`
- `validated_by`
- `created_at`
- `validated_at`

Estados sugeridos:

- `draft`
- `validated`
- `imported`
- `rejected`

#### `civil_buildings`

Representa filas azules del Excel.

Campos sugeridos:

- `id`
- `catalog_import_id`
- `name`
- `excel_row`
- `sort_order`

#### `civil_partidas`

Representa filas verdes del Excel.

Campos sugeridos:

- `id`
- `building_id`
- `code`
- `name`
- `budget_amount`
- `excel_row`
- `sort_order`

#### `civil_concepts`

Representa conceptos comprables.

Campos sugeridos:

- `id`
- `partida_id`
- `excel_code`
- `description`
- `unit`
- `budget_quantity`
- `unit_price`
- `unit_price_text`
- `budget_amount`
- `excel_row`
- `sort_order`
- `is_active`

#### Relacion Con Ordenes De Compra

La tabla de conceptos o partidas de orden de compra debe poder referenciar:

- `civil_concept_id`
- `quantity`
- `unit_price`
- `amount`
- `status_effect`

Si la tabla actual de ordenes de compra ya tiene items, se recomienda extender esa tabla en vez de crear un flujo duplicado.

### Checkpoint

- Modelo de datos validado.
- Llaves foraneas definidas.
- Relacion con ordenes de compra existente identificada.
- Migraciones listas para implementarse.

## Fase 3: Importador De Excel

### Objetivo

Crear un importador que lea la hoja `CATALOGO`, detecte la estructura visual y genere una previsualizacion antes de guardar definitivamente.

### Reglas De Deteccion

- Leer columnas `A` a `H`.
- Detectar encabezados y descartarlos.
- Detectar edificios por color azul o por filas configuradas.
- Detectar partidas por color verde.
- Detectar conceptos por filas con clave, descripcion, unidad, cantidad, precio e importe.
- Asociar cada concepto a la partida activa mas cercana.
- Asociar cada partida al edificio activo mas cercano.
- Guardar `excel_row` para trazabilidad.

### Validaciones

- Concepto sin partida.
- Partida sin edificio.
- Cantidad vacia o no numerica.
- Precio vacio o no numerico.
- Importe inconsistente con cantidad por precio.
- Clave duplicada dentro de la misma partida.
- Concepto repetido en diferentes edificios.
- Filas ignoradas por no coincidir con reglas.

### Resultado Del Importador

Antes de guardar, mostrar:

- Total de edificios detectados.
- Total de partidas detectadas.
- Total de conceptos detectados.
- Importe total calculado.
- Errores bloqueantes.
- Advertencias no bloqueantes.
- Tabla previa con edificio, partida, clave, descripcion, unidad, cantidad, precio e importe.

### Checkpoint

- El archivo de ejemplo se importa correctamente.
- Los edificios de filas 6, 116, 259, 389 y 412 se detectan.
- Las partidas verdes se agrupan bajo el edificio correcto.
- Los conceptos se agrupan bajo la partida correcta.
- El total importado coincide con el total del Excel o se explican diferencias.

## Fase 4: Pantalla De Catalogo Civil

### Objetivo

Permitir consultar y validar el catalogo importado.

### Funciones

- Ver catalogos importados por obra.
- Ver estructura por edificio.
- Expandir partidas.
- Buscar conceptos por clave o descripcion.
- Ver presupuesto por cantidad y monto.
- Ver comprometido, comprado y saldo.
- Ver ordenes de compra relacionadas a cada concepto.

### Filtros Sugeridos

- Edificio.
- Partida.
- Clave.
- Descripcion.
- Unidad.
- Conceptos con saldo.
- Conceptos excedidos.
- Conceptos sin movimientos.

### Checkpoint

- Usuario puede ubicar cualquier concepto importado.
- Usuario puede ver presupuesto, comprado y saldo.
- Usuario puede entender de que edificio y partida viene cada concepto.

## Fase 5: Integracion Con Ordenes De Compra

### Objetivo

Permitir crear ordenes de compra usando conceptos del catalogo civil.

### Flujo

- Seleccionar obra civil.
- Seleccionar edificio.
- Seleccionar partida.
- Buscar concepto.
- Capturar cantidad a comprar.
- Mostrar cantidad disponible.
- Mostrar precio catalogo.
- Permitir precio OC.
- Calcular monto OC.
- Validar saldo.
- Guardar item de OC con referencia a `civil_concept_id`.

### Reglas De Saldo

Estados propuestos:

- `draft`: no afecta saldo.
- `requested`: puede reservar saldo si el negocio lo requiere.
- `approved`: compromete saldo.
- `received`: consume saldo.
- `closed`: confirma consumo.
- `cancelled`: libera saldo comprometido.

Primera version recomendada:

- Borrador no afecta.
- Autorizada compromete.
- Cancelada libera.
- Cerrada consume.

### Checkpoint

- Una OC puede tomar conceptos del catalogo civil.
- El sistema bloquea cantidades mayores al saldo disponible.
- El saldo del concepto cambia segun el estado de la OC.
- La OC conserva la referencia al concepto original.

## Fase 6: Reportes Y Auditoria

### Reportes Minimos

- Presupuesto por edificio.
- Presupuesto por partida.
- Presupuesto vs comprado por concepto.
- Saldos disponibles.
- Conceptos sobregirados.
- Ordenes de compra por concepto.
- Variacion de precio catalogo vs precio OC.

### Auditoria

Registrar:

- Usuario que importo catalogo.
- Usuario que valido catalogo.
- Usuario que creo OC.
- Usuario que autorizo OC.
- Cambios de estado de OC.
- Cambios que afectaron saldo.

### Checkpoint

- Los saldos pueden explicarse desde las ordenes de compra.
- El usuario puede rastrear cada concepto hasta su fila original del Excel.
- Los totales por edificio y partida son verificables.

## Fase 7: Reimportacion Y Versiones

### Objetivo

Permitir cambios futuros al catalogo sin romper ordenes de compra existentes.

### Reglas Propuestas

- No sobrescribir conceptos ya usados sin crear version.
- Permitir importar una nueva version del catalogo.
- Comparar version anterior contra version nueva.
- Detectar conceptos nuevos, eliminados o modificados.
- Mantener historico de precios y cantidades.

### Checkpoint

- Se puede cargar una nueva version sin perder trazabilidad.
- Las OC antiguas siguen apuntando al concepto/version original.
- El usuario puede revisar diferencias antes de aprobar cambios.

## Primera Version Recomendada

Alcance minimo viable:

- Crear modalidad de obra civil.
- Importar Excel hoja `CATALOGO`.
- Detectar edificios, partidas y conceptos.
- Guardar catalogo con IDs internos.
- Consultar catalogo civil.
- Crear OC referenciando conceptos.
- Validar saldo disponible.
- Reporte basico de presupuesto, comprado y saldo.

Fuera de la primera version:

- Reimportacion avanzada.
- Autorizacion de sobregiros.
- Comparador visual de versiones.
- Ajustes manuales masivos.
- Integracion contable especial.

## Riesgos

- El color del Excel puede cambiar y romper la deteccion.
- Algunas filas pueden parecer conceptos pero ser subtotales.
- La clave del Excel puede repetirse.
- Los importes pueden tener diferencias por redondeo.
- Las ordenes de compra existentes pueden no estar preparadas para conceptos externos al modelo de pilas.
- Si se permite editar catalogos ya usados, se puede perder trazabilidad.

## Mitigaciones

- Guardar la fila original del Excel.
- Mostrar previsualizacion antes de importar.
- Separar errores bloqueantes de advertencias.
- Usar IDs internos, no claves Excel como llave principal.
- Calcular saldos desde movimientos de OC.
- Mantener historico de importaciones.
- Agregar pruebas con el archivo real de Tesistan.

## Preguntas Pendientes

- ¿Una obra civil puede tener varios edificios dentro del mismo catalogo?
- ¿Una obra civil puede tener mas de un catalogo activo?
- ¿Las ordenes de compra deben descontar cantidad, monto o ambos?
- ¿Se puede comprar con precio diferente al catalogo?
- ¿Quien puede autorizar un sobregiro?
- ¿El catalogo se considera presupuesto contractual o solo referencia interna?
- ¿Las estimaciones del Excel se usaran despues o solo el catalogo base?
- ¿Los conceptos deben poder vincularse tambien a requisiciones antes de OC?

## Checkpoint General De Aprobacion

El modulo se considera listo para desarrollo cuando esten definidos:

- Alcance de primera version.
- Modelo de datos.
- Reglas de importacion.
- Reglas de saldo.
- Integracion con ordenes de compra.
- Estados de OC que afectan saldo.
- Validaciones obligatorias.
- Reportes minimos.

