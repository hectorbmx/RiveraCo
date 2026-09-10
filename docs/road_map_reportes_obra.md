# Roadmap: reportes ejecutivos de obra

## Objetivo
Crear un dashboard estilo Power BI para cada obra, orientado a reportes ejecutivos y a impresión, con datos claros, reutilizables y fáciles de mantener. La vista debe responder tres preguntas clave:

1. ¿Cuánto vale la obra?
2. ¿Cuánto se ha facturado y cobrado?
3. ¿Qué tanto avanza la obra y cuánto se ha gastado?

No se debe mezclar avance físico, finanzas y compras en una sola métrica. Cada bloque debe representar una capa de negocio distinta.

---

## Hallazgos actuales

### 1) El valor de la obra ya está definido por el negocio
La base correcta es:
- Valor de obra = monto_contratado

Esto ya aparece en la lógica que calcula KPIs ejecutivos en `ObraController` y es coherente con la idea de reportes ejecutivos.

Recomendación:
- Usar `monto_contratado` como valor base del dashboard.
- No usar `monto_modificado` como valor principal salvo que exista una regla de negocio explícita para mostrarlo como “valor ajustado”.

### 2) Facturado y cobrado no son lo mismo
La lógica actual ya hace una separación importante entre facturas relacionadas a la obra y pagos registrados. Eso es correcto.

Reglas sugeridas:
- Facturado = suma de facturas asociadas a la obra, locales + SAT/FacturAPI, excluyendo canceladas.
- Cobrado = pagos realmente recibidos.
- Si la factura es PUE: el pago se registra directamente por el usuario.
- Si la factura es PPD: el cobro debe calcularse desde el complemento de pago.

Esto evita confundir “emitido” con “recibido”.

### 3) Gastado debe arrancar desde órdenes de compra
Para no inventar lógica duplicada, el gasto debe iniciar desde órdenes de compra, que ya son una fuente con mayor consistencia para control financiero.

Definición base:
- Total OC = suma de órdenes de compra asociadas a la obra.
- Gastado = base inicial desde compras/OC.
- Pagado = pagos asociados a esas OC.
- Pendiente = diferencia entre total gastado y pagado, si aplica.

Esto conviene como primera versión, con evolución posterior si se requiere un desglose más fino.

### 4) El avance general no es financiero
El avance general debe representar el avance físico capturado desde la app móvil, no deuda ni facturación.

Ya existe lógica para:
- profundidad
- acero
- bentonita
- concreto
- avance por pilas y comisiones

Debe permanecer separado de facturas y compras.

### 5) La vista de obra ya tiene la base técnica necesaria
Hay dos puntos muy útiles ya presentes:
- en `ObraController` existen totales financieros por obra
- en la vista de edición ya hay barras de avance físico por obra

Esto reduce el riesgo de crear lógica muy fragmentada o duplicada en Blade.

---

## Principios de diseño

### Regla 1: separar capas de negocio
Cada bloque del dashboard debe responder una sola pregunta:

- Financiero: ¿cuánto vale, se facturó y se cobró?
- Compras: ¿cuánto se gastó y cuánto falta pagar?
- Operación: ¿qué tanto avanza la obra?

Nunca mezclar estas tres capas en una sola barra, tarjeta o cálculo.

### Regla 2: preparar datos en el controlador
La vista debe consumir datos ya calculados, no realizar lógica compleja ad hoc.

Se recomienda:
- crear un servicio o un método auxiliar dedicado a calcular KPIs de obra
- devolver un array con métricas listas para renderizar
- que la vista solo haga presentación, no cálculo

### Regla 3: reutilizar la lógica base existente
No duplicar código de:
- totales de facturas
- pagos de obra
- avance físico
- órdenes de compra
- sumas por estatus

Siempre que exista una función o un query relevante, reutilizarla como fuente.

### Regla 4: mantener la vista imprimible
Cuando se diseñe la pantalla, pensarlo como reporte ejecutivo y no como un panel de administración técnica.

Debe tener:
- resumen ejecutivo
- cards con métricas claras
- barras simples
- tablas pequeñas y legibles
- tono visual sobrio y claro
- formato que permita imprimir sin romper el layout

### Regla 5: evitar “código espagueti suelto”
No se deben hacer cálculos inline en Blade para cada tarjeta si se repiten. Si un mismo cálculo se usa en varias secciones, moverlo a un servicio o a un método del controller.

---

## Estructura recomendada del dashboard

## A. Header ejecutivo
Debe incluir:
- nombre de la obra
- clave de obra
- cliente
- responsable
- estatus
- fecha inicio / fin
- % avance general

## B. Bloque financiero
Tarjetas:
- Valor de obra
- Facturado
- Cobrado
- Facturado no pagado
- Por facturar

Notas:
- Base = monto_contratado
- Facturado y cobrado deben calcularse desde sumas reales
- Mantener los cálculos separados por fuente y estado

## C. Bloque de gastos / compras
Tarjetas:
- Total de OC
- Total gastado
- Total pagado por compras
- Pendiente por pagar
- % gasto sobre valor de obra

Notas:
- Primera fase con base en órdenes de compra
- Se puede ir profundizando luego en detalle por proveedor o partida

## D. Bloque de avance operativo
Seccion:
- avance físico general
- porcentaje general
- bloque por actividad o componente

Ejemplos:
- profundidad ejecutada
- acero colocado
- concreto
- bentonita
- progreso por pilas / actividades

---

## Reglas para reutilizar código

### Opción recomendada: crear capa de métricas
Se recomienda una capa central para las métricas de obra, por ejemplo:

- `App\Services\Obra\ObraDashboardService`
- o `App\Services\Obra\ObraReporteService`

Responsabilidades:
- calcular valor de obra
- calcular facturado
- calcular cobrado
- calcular por facturar
- calcular gastos iniciales por OC
- calcular avance general
- devolver todas las métricas en un array listo para la vista

Esto centraliza la lógica y evita:
- fórmulas repetidas en Blade
- consultas dispersas en varias vistas
- cálculo duplicado entre componentes

### Criterio de reutilización
Si una fórmula se usa en:
- el dashboard
- el resumen ejecutivo
- una tarjeta parcial
- una impresión

Entonces debe estar en la capa de servicio o controller y no en Blade.

---

## Regla SOLID aplicada a este caso

### S (Single Responsibility)
Cada clase debe tener una responsabilidad clara:
- una clase para métricas financieras
- otra para avance físico
- otra para compras/gastos
- la vista solo presenta datos

### O (Open/Closed)
El sistema debe poder agregarse nuevos indicadores sin romper el dashboard principal.

Ejemplo:
- más adelante agregar “margen bruto”, “variación de costo”, “rentabilidad”, sin tocar la vista completa.

### L (Liskov)
Las métricas y servicios deben comportarse consistentemente bajo el mismo contrato.

Ejemplo:
- cada servicio devuelve la misma estructura de array o DTO para cada obra.

### I (Interface Segregation)
No forzar interfaces enormes.

Si se usan contratos, deben ser pequeños y específicos por dominio:
- `ObraFinanceMetrics`
- `ObraProgressMetrics`
- `ObraPurchaseMetrics`

### D (Dependency Inversion)
La vista no debe depender de consultas directas ni de lógica dispersa.

Debe depender de un servicio con contrato claro.

---

## Tareas pequeñas por iteración

## Iteración 1: base de métricas financieras
Objetivo: dejar listos los datos base del dashboard.

Tareas:
- definir el valor base de obra = monto_contratado
- preparar cálculo de facturado
- preparar cálculo de cobrado
- preparar cálculo de por facturar
- preparar cálculo de facturado no pagado

Checkpoints:
- [ ] El valor de obra se calcula siempre desde `monto_contratado`
- [ ] El total facturado excluye canceladas
- [ ] El total cobrado considera pagos reales
- [ ] La diferencia facturado - cobrado es consistente

## Iteración 2: integrar compras / gastos
Objetivo: añadir el bloque de gastos usando órdenes de compra como fuente base.

Tareas:
- sumar OC por obra
- calcular gastado acumulado
- calcular pagado por compras
- calcular pendiente por pagar
- dejar el bloque listo para impresión

Checkpoints:
- [ ] El total de OC se calcula por obra
- [ ] La vista muestra gasto acumulado en forma legible
- [ ] Se diferencia entre gastado y pagado
- [ ] El bloque no duplica facturas ni pagos

## Iteración 3: avance físico operativo
Objetivo: incorporar el avance real de obra desde la app móvil.

Tareas:
- reutilizar la lógica existente de avance por profundidad, acero, concreto, bentonita
- preparar % general de avance
- mostrar detalle por componente / actividad
- separar el bloque de avance del financiero

Checkpoints:
- [ ] El avance físico no se usa como KPI financiero
- [ ] El porcentaje general se calcula desde datos reales de obra
- [ ] La vista de avance es legible en formato impreso

## Iteración 4: dashboard ejecutivo final
Objetivo: armar la pantalla tipo reportes ejecutivos.

Tareas:
- header resumen ejecutivo
- cards de KPI
- barras de progreso
- bloques organizados por sección
- diseño para impresión
- revisar textos y unidades

Checkpoints:
- [ ] La pantalla es entendible sin contexto adicional
- [ ] Las tarjetas representan conceptos distintos
- [ ] El diseño funciona en impresión o exportación
- [ ] No hay lógica de negocio en Blade

---

## Recomendaciones de implementación práctica

### 1) No construir el dashboard directo en la vista
Primero preparar las métricas en controller/service.

### 2) Crear un array final de dashboard
Ejemplo conceptual:

```php
[
    'valor_obra' => 1250000,
    'facturado' => 860000,
    'cobrado' => 720000,
    'facturado_no_pagado' => 140000,
    'por_facturar' => 390000,
    'total_oc' => 540000,
    'gastado' => 410000,
    'pagado_compra' => 350000,
    'pendiente_pagar' => 60000,
    'avance_general' => 68,
]
```

Esto hace la vista limpia y reutilizable.

### 3) Mantener los textos y unidades consistentes
Usar siempre:
- moneda con 2 decimales
- porcentaje con 0 o 1 decimal dependiendo el contexto
- mismo nombre para facturado/cobrado/avance

### 4) No duplicar reglas de negocio entre controller y vista
Si una regla se decide en un sitio, debe reflejarse en la fuente de cálculo única.

---

## Criterio de aceptación del dashboard

El dashboard se considera listo cuando:

- [ ] el valor de la obra está definido por `monto_contratado` y es claro
- [ ] la diferencia entre facturado y cobrado es explícita
- [ ] el avance físico está separado del financiero
- [ ] el gasto inicia desde órdenes de compra
- [ ] la vista se puede imprimir y se entiende en segundos
- [ ] la lógica de negocio no está dispersa en Blade
- [ ] el código sigue una estructura reutilizable y limpia

---

## Siguiente paso recomendado

Se debe avanzar por este orden estricto:

1. preparar datos financieros de la obra
2. preparar gastos desde OC
3. preparar avance físico
4. armar la vista ejecutiva final

Esto minimiza riesgo, reduce cambios de diseño y evita reescrituras costosas.

---

## Conclusión
La idea del dashboard ejecutivo de obra es válida y encaja con la arquitectura del proyecto. El punto más importante no es solo “hacer una bonita vista”, sino diferenciar bien cada capa del negocio para que los reportes sean útiles, consistentes y confiables.

La clave está en convertir la vista en presentación, y la lógica de negocio en servicios o métodos calculados centralmente.
