# Almacen HUENTITAN

**Fecha de creacion del documento:** 04 de septiembre de 2026  
**Estado:** En definicion funcional y preparacion de desarrollo por etapas  
**Modulo:** HUENTITAN  
**Alcance inicial:** personal, productos, inventario, ordenes de fabricacion, formulas, kardex, stock minimo y preparacion para compras por almacen/area.

## Regla principal de trabajo

HUENTITAN debe manejarse como un modulo operativo independiente dentro de SIRICO, colocado debajo de GIRALDA en el menu, pero conectado a los procesos centrales existentes del sistema.

La intencion no es duplicar logica innecesaria. Antes de construir cualquier pieza se debe revisar si ya existe un flujo reutilizable en V2, especialmente en:

- Empleados de GIRALDA.
- Productos.
- Inventario.
- Kardex.
- Ordenes de compra.
- Permisos.
- Areas y almacenes.

Principio de reutilizacion:

- Reusar modelos y servicios existentes cuando el flujo ya este resuelto.
- Separar el contexto por almacen, area o centro de costo.
- Evitar procesos paralelos que rompan trazabilidad.
- No descontar inventario con operaciones manuales si ya existe un documento o movimiento formal.
- Preparar permisos desde el inicio, aunque algunas autorizaciones se activen despues.

## Contexto funcional

HUENTITAN representa un almacen con operacion propia. A diferencia de un almacen simple, aqui tambien existe fabricacion interna de piezas.

El modulo debe permitir controlar:

- Materia prima e insumos.
- Productos terminados comprados.
- Productos terminados fabricados dentro del almacen.
- Material disponible.
- Material apartado para fabricacion.
- Material entregado a produccion.
- Consumo real.
- Mermas.
- Sobrantes.
- Salidas a obra.
- Entradas y salidas documentales aunque el material fisicamente no pase por el almacen.

La idea no esta desviada: HUENTITAN no debe ser solo un filtro del inventario general. Debe tener vista propia porque mezcla almacen, compras, stock y produccion interna.

## Navegacion propuesta

Dentro del modulo HUENTITAN se proponen estas opciones principales:

- Panel.
- Empleados.
- Productos.
- Inventario.
- Orden compras.
- Orden fabricacion.

Estructura objetivo:

- `HUENTITAN -> Panel`
- `HUENTITAN -> Empleados`
- `HUENTITAN -> Productos`
- `HUENTITAN -> Inventario`
- `HUENTITAN -> Orden compras`
- `HUENTITAN -> Orden fabricacion`

## 1. Empleados

La seccion de empleados debe ser parecida a `GIRALDA -> Empleados`.

Objetivo:

- Mostrar personal relacionado con el almacen HUENTITAN.
- Identificar responsables de entradas, salidas, fabricacion y supervision.
- Mantener una vista filtrada, sin duplicar empleados si ya existen en el catalogo general.

Datos o relaciones deseadas:

- Empleado.
- Area o almacen asignado.
- Puesto.
- Estatus.
- Permisos operativos.
- Responsable de almacen, auxiliar, produccion o supervisor.

Decision inicial:

- Reusar el modelo de empleados existente.
- Crear vistas filtradas por area/almacen, igual que se esta haciendo con GIRALDA.
- No crear una tabla de empleados separada para HUENTITAN salvo que despues se detecte una necesidad real.

## 2. Productos

Productos sera el catalogo operativo del almacen HUENTITAN.

Debe permitir distinguir si un producto es:

- Materia prima.
- Insumo.
- Producto terminado comprado.
- Producto terminado fabricado.
- Producto mixto, es decir, puede comprarse o fabricarse.
- Subensamble, si despues se requiere un nivel intermedio.

Campos sugeridos para preparar el modelo:

- Tipo de inventario.
- Origen de abastecimiento: comprado, fabricado o mixto.
- Requiere formula: si/no.
- Stock minimo.
- Stock recomendado, opcional.
- Punto de reorden, opcional.
- Unidad de medida.
- Costo promedio.
- Ultimo costo.
- Tiempo estimado de fabricacion.
- Activo/inactivo.
- Almacen relacionado.
- Observaciones tecnicas.

Vista sugerida del producto:

- Datos generales.
- Inventario.
- Kardex.
- Especificaciones.
- Formula.
- Costos.
- Historial.

Esta vista puede tomar como referencia la ficha actual de productos, por ejemplo `/productos/{producto}/edit`, pero adaptada al contexto HUENTITAN.

## 3. Kardex del producto

Cada producto debe tener un kardex consultable dentro de HUENTITAN.

El kardex debe mostrar:

- Entradas por compra.
- Entradas por fabricacion.
- Salidas a obra.
- Salidas a produccion.
- Devoluciones.
- Ajustes.
- Mermas.
- Existencia anterior.
- Cantidad del movimiento.
- Existencia resultante.
- Costo unitario.
- Costo total.
- Usuario responsable.
- Documento origen.

Regla importante:

- El kardex debe salir de movimientos formales de inventario, no de modificaciones directas al stock.

## 4. Formula de fabricacion

Cuando un producto terminado se fabrica dentro de HUENTITAN, debe tener una formula.

La formula define el material requerido para producir una unidad del producto terminado.

Datos sugeridos:

- Producto terminado.
- Material requerido.
- Cantidad requerida por unidad.
- Unidad de medida.
- Merma esperada.
- Tiempo estimado de fabricacion.
- Costo de material esperado.
- Mano de obra estimada, opcional.
- Observaciones tecnicas.

Ejemplo:

Para fabricar 1 tubo modelo X se requiere:

- Material Y: 2 piezas.
- Material Z: 1.5 kg.
- Material A: 3 metros.

Decision importante:

- La formula calcula consumo esperado.
- La formula no debe tomarse como consumo real automaticamente.
- El consumo real se confirma al cerrar la orden de fabricacion o al registrar avances.

## 5. Stock minimo y alertas

Cada producto debe permitir configurar stock minimo.

Cuando el stock disponible baje del minimo, el sistema debe poder sugerir una accion:

- Si el producto es comprado: sugerir orden de compra.
- Si el producto es fabricado: sugerir orden de fabricacion.
- Si el producto es mixto: permitir decidir si se compra o se fabrica.

Existencias que conviene separar:

- Stock disponible.
- Stock apartado.
- Stock en produccion.
- Stock fisico o existencia real.

El inventario semanal que hoy se captura puede usarse como punto de arranque o conciliacion, pero el objetivo es que las entradas y salidas permitan revisar inventario diariamente.

## 6. Ordenes de compra

El proceso de orden de compra ya existe en SIRICO.

La recomendacion es no convertirlo en algo exclusivo de HUENTITAN. Conviene desacoplarlo como un flujo reutilizable por area o almacen.

La orden de compra deberia permitir:

- Area solicitante.
- Almacen destino.
- Contexto de compra segun almacen o destino operativo.
- Si la compra es para stock.
- Si la compra es para una obra especifica.
- Si la compra es para una orden de fabricacion especifica.
- Si el material entra fisicamente al almacen.
- Si el material va directo a obra.
- Productos solicitados.
- Autorizaciones.
- Recepcion.
- Diferencias contra lo recibido.

Regla para buscador de productos en compras:

- Cuando una orden de compra tenga como almacen destino `AL-HUENTITAN`, el buscador de productos debe limitarse al catalogo operativo HUENTITAN.
- Esta regla debe funcionar similar a los buscadores filtrados por contexto que ya se usan para obra civil.
- El usuario no debe seleccionar productos ajenos al almacen HUENTITAN si la compra fue marcada para ese almacen.
- Si una orden de compra nace por faltantes de una orden de fabricacion, las partidas deben venir de los materiales calculados por la formula y quedar relacionadas con esa orden de fabricacion.
- La recepcion de esa compra debe poder entrar al almacen HUENTITAN como material disponible o como material apartado para la orden de fabricacion que origino la compra.

Regla de trazabilidad:

- Aunque el material vaya directo a obra, puede registrarse como entrada y salida documental del almacen para conservar trazabilidad.
- Si la compra se hizo para una orden de fabricacion, debe conservar la relacion compra -> recepcion -> apartado/entrega a produccion.

## 7. Orden de fabricacion

La orden de fabricacion aplica solo para productos marcados como fabricables o con `requiere_formula` activo.

Flujo propuesto:

1. El usuario crea una orden de fabricacion.
2. Selecciona el producto terminado a fabricar.
3. Captura la cantidad requerida.
4. El sistema calcula materiales con base en la formula.
5. El sistema valida existencia disponible.
6. Se genera una lista de materiales requeridos.
7. Si no hay existencia suficiente, el sistema muestra faltantes por material.
8. El faltante puede convertirse en orden de compra relacionada con la orden de fabricacion.
9. Al autorizar la orden, el material disponible queda apartado para esa orden.
10. El almacen entrega material a produccion.
11. Produccion fabrica.
12. Se registra consumo real.
13. Se registran sobrantes, mermas o diferencias.
14. Se registra entrada de producto terminado.
15. Se cierra la orden.

Estados sugeridos:

- Borrador.
- Material calculado.
- Material apartado.
- En produccion.
- Parcialmente cerrada.
- Cerrada.
- Cancelada.

## 8. Decision: apartado contra salida inmediata

La mejor decision es no descontar definitivamente el inventario al crear la orden de fabricacion.

Al crear la orden:

- El sistema calcula los materiales.
- El sistema valida stock disponible contra materiales requeridos.
- Si hay faltantes, el sistema alerta y permite generar o ligar una orden de compra.
- No se aparta ni se descuenta inventario todavia.

Al autorizar la orden:

- El sistema aparta el material disponible para esa orden.
- El material apartado deja de estar disponible para otras salidas.
- El material sigue existiendo fisicamente en almacen hasta ser entregado a produccion.

Al entregar material a produccion:

- El material cambia de disponible/apartado a en produccion.
- Se genera un documento o movimiento de salida a produccion.

Al cerrar fabricacion:

- Se confirma consumo real.
- Se registran sobrantes.
- Se registran mermas.
- Entra producto terminado al inventario.

Motivo:

Si se descuenta desde la creacion de la orden, se pierde control cuando:

- La orden se cancela.
- La fabricacion es parcial.
- Cambia la cantidad a fabricar.
- No se entrega todo el material.
- Hay sobrantes.
- Hay merma.
- El consumo real es diferente a la formula.

Conclusion de esta decision:

- Crear orden calcula materiales y detecta faltantes.
- Autorizar orden aparta material.
- Entregar a produccion mueve material a estado en produccion.
- Cerrar orden consume realmente el material y genera producto terminado.

## 9. Inventario

Dentro de Inventario deben existir botones operativos claros:

- Entrada.
- Salida.
- Ajuste.
- Corte o conteo fisico.
- Kardex.
- Stock minimo.
- Productos bajo minimo.

Tipos de entrada:

- Compra.
- Devolucion de obra.
- Devolucion de produccion.
- Fabricacion terminada.
- Ajuste positivo.
- Carga inicial desde inventario semanal.

Tipos de salida:

- Salida a obra.
- Salida a produccion.
- Merma.
- Ajuste negativo.

Regla:

- No mezclar salida a obra con salida a produccion.
- No registrar materia prima consumida nuevamente cuando se despacha producto terminado.
- Cuando se manda un tubo fabricado a obra, sale el producto terminado, no otra vez el material que lo compone.

## 10. Autorizaciones

El modulo debe dejar bases para autorizaciones desde el inicio, aunque no todas se activen en la primera etapa.

No requiere autorizacion inicialmente:

- Crear orden de fabricacion en borrador.
- Calcular material requerido.
- Capturar formula.
- Consultar kardex.
- Guardar especificaciones.

Debe prepararse para autorizacion:

- Aplicar entrada.
- Aplicar salida.
- Apartar material para fabricacion, si se decide controlar estrictamente disponibilidad.
- Entregar material a produccion.
- Cerrar fabricacion.
- Cerrar fabricacion con diferencias importantes.
- Registrar ajustes.
- Cancelar documentos aplicados.
- Autorizar compras.

Roles funcionales sugeridos:

- Gerente de almacen.
- Gerente administrativo.
- Encargado de almacen.
- Auxiliar de almacen.
- Produccion.
- Consulta.

Regla definida por operacion:

- El gerente de almacen decide cuanto fabricar.
- La fabricacion debe contar con autorizacion del gerente administrativo cuando aplique.

## 11. Trazabilidad requerida

Cada movimiento debe quedar relacionado con su origen.

Relaciones minimas:

- Entrada de materia prima: proveedor, compra o recepcion.
- Compra para obra: orden de compra, obra y almacen documental.
- Compra para fabricacion: orden de compra y orden de fabricacion de origen.
- Salida a produccion: orden de fabricacion.
- Apartado de material: orden de fabricacion.
- Consumo real: orden de fabricacion.
- Merma: orden de fabricacion y causa.
- Sobrante: orden de fabricacion de origen.
- Entrada de producto terminado: orden de fabricacion.
- Salida a obra: obra y comprobante de salida.
- Devolucion de obra: obra, salida original y condicion del material.

Para productos fabricados se recomienda manejar lote de produccion mas adelante. Por ahora puede iniciar por cantidad.

## 12. Etapas de desarrollo recomendadas

### Etapa 1: Base del modulo

- Menu HUENTITAN.
- Permiso `huentitan.access`.
- Vista principal del modulo.
- Submenu interno: Panel, Empleados, Productos, Inventario, Orden compras y Orden fabricacion.

### Etapa 2: Personal

- Vista tipo GIRALDA empleados.
- Filtro por almacen/area HUENTITAN.
- Responsables operativos.

### Etapa 3: Productos HUENTITAN

- Vista filtrada de productos del almacen.
- Clasificacion materia prima/producto terminado.
- Origen comprado/fabricado/mixto.
- Stock minimo.
- Especificaciones.
- Kardex por producto.

### Etapa 4: Formula de fabricacion

- Captura de formula.
- Material requerido por unidad.
- Tiempo estimado.
- Merma esperada.
- Costo esperado.

### Etapa 5: Inventario

- Entradas.
- Salidas.
- Kardex.
- Corte fisico.
- Stock disponible.
- Stock apartado.
- Stock en produccion.

### Etapa 6: Orden de fabricacion

- Crear orden.
- Agregar productos a fabricar.
- Calcular materiales requeridos.
- Apartar material.
- Entregar material a produccion.
- Registrar consumo real.
- Registrar sobrantes y mermas.
- Entrada de producto terminado.
- Cierre de orden.

### Etapa 7: Ordenes de compra por contexto

- Revisar flujo actual de ordenes de compra.
- Desacoplarlo para que funcione por area/almacen.
- Permitir compra para stock.
- Permitir compra para obra.
- Permitir compra para orden de fabricacion.
- Filtrar el buscador de productos por catalogo HUENTITAN cuando el almacen destino sea AL-HUENTITAN.
- Permitir recepcion documental aunque el material vaya directo a obra.

## 13. Primer entregable tecnico sugerido

El siguiente paso de desarrollo deberia ser crear la pantalla base del modulo HUENTITAN con sus opciones internas.

Entregable minimo:

- Ruta principal `/inventario/huentitan` o ruta nueva dedicada `/huentitan` si se decide separar del prefijo inventario.
- Layout interno del modulo.
- Cards o accesos a Empleados, Productos, Inventario, Orden compras y Orden fabricacion.
- Permisos preparados para cada seccion.

Permisos sugeridos:

- `huentitan.access`
- `huentitan.empleados.view`
- `huentitan.productos.view`
- `huentitan.productos.manage`
- `huentitan.formulas.manage`
- `huentitan.inventario.view`
- `huentitan.inventario.entradas`
- `huentitan.inventario.salidas`
- `huentitan.inventario.ajustes`
- `huentitan.ordenes_compra.view`
- `huentitan.ordenes_fabricacion.view`
- `huentitan.ordenes_fabricacion.create`
- `huentitan.ordenes_fabricacion.authorize`
- `huentitan.ordenes_fabricacion.close`

## Conclusion

La idea esta bien orientada.

HUENTITAN debe avanzar como modulo operativo propio porque el proceso no solo administra stock: tambien fabrica productos terminados usando materia prima del almacen.

La decision mas importante es manejar estados de inventario y no descontar definitivamente al crear una orden de fabricacion. Primero se aparta, despues se entrega a produccion y al final se confirma el consumo real.

## 14. Checkpoints de avance

Estos checkpoints sirven para validar el desarrollo por fases. No todos implican codigo terminado; algunos son decisiones funcionales que deben quedar confirmadas antes de construir la siguiente etapa.

### Fase 0: Base del modulo

Objetivo: dejar HUENTITAN como modulo independiente, visible y protegido por permisos, sin afectar todavia inventario real.

Checklist:

- [x] Crear permiso base `huentitan.access`.
- [x] Asignar `huentitan.access` al rol `super-admin`.
- [x] Mostrar HUENTITAN debajo de GIRALDA en el menu lateral.
- [x] Proteger rutas actuales de HUENTITAN con `huentitan.access`.
- [x] Definir ruta principal definitiva: `/huentitan`, conservando `/inventario/huentitan` como compatibilidad temporal.
- [x] Crear pantalla principal tipo panel del modulo HUENTITAN.
- [x] Agregar accesos internos a Empleados, Productos, Inventario, Orden compras y Orden fabricacion.
- [x] Crear permisos iniciales por seccion.
- [x] Confirmar almacen HUENTITAN definitivo con codigo `AL-HUENTITAN` y compatibilidad temporal por nombre.
- [x] Documentar cierre de alcance inicial antes de iniciar Fase 1.

Criterio de salida:

- Un usuario con permiso puede entrar al modulo HUENTITAN y ver sus secciones base, aunque algunas pantallas sigan en construccion.

### Fase 1: Empleados HUENTITAN

Objetivo: tener una vista operativa del personal relacionado con HUENTITAN, reutilizando empleados existentes.

Checklist:

- [x] Revisar implementacion actual de `GIRALDA -> Empleados`.
- [x] Definir como se identifica el personal de HUENTITAN: por area `HT`, ligada al almacen `AL-HUENTITAN`.
- [x] Crear ruta y vista `HUENTITAN -> Empleados`.
- [x] Filtrar empleados por HUENTITAN.
- [x] Mostrar estatus activo/baja/todos si aplica.
- [x] Preparar roles funcionales: gerente de almacen, gerente administrativo, encargado, auxiliar, produccion y consulta.

Criterio de salida:

- La pantalla muestra solo personal relacionado con HUENTITAN y puede usarse como base para responsables de movimientos y fabricacion.

### Fase 2: Productos HUENTITAN

Objetivo: separar el catalogo operativo de productos del almacen HUENTITAN.

Checklist:

- [x] Revisar modelo actual de `Producto` y vistas existentes de productos.
- [x] Definir campos necesarios: tipo de inventario, origen, requiere formula, stock minimo y especificaciones.
- [x] Crear vista `HUENTITAN -> Productos`.
- [x] Mostrar productos asociados al almacen HUENTITAN.
- [x] Permitir distinguir materia prima, insumo, producto terminado comprado, producto terminado fabricado y mixto.
- [x] Agregar apartado de stock minimo.
- [x] Preparar estructura para especificaciones tecnicas.
- [x] Preparar tab de kardex por producto.
- [x] Preparar tab de formula cuando `requiere_formula` sea verdadero.

Criterio de salida:

- Cada producto de HUENTITAN puede clasificarse correctamente y queda listo para inventario, formula o compra.

### Fase 3: Inventario HUENTITAN

Objetivo: manejar inventario del almacen sin mezclarlo con otros almacenes.

Checklist:

- [ ] Confirmar modelo actual de stock por almacen.
- [ ] Separar existencias disponibles, apartadas y en produccion.
- [ ] Crear botones de entrada y salida dentro del modulo HUENTITAN.
- [ ] Crear vista de kardex del almacen.
- [ ] Crear vista de productos bajo stock minimo.
- [ ] Definir documentos de entrada, salida, ajuste y corte fisico.
- [ ] Asegurar que cada movimiento genere kardex.
- [ ] Evitar decrementos directos sin documento origen.

Criterio de salida:

- Las entradas y salidas de HUENTITAN afectan solo su almacen y dejan trazabilidad en kardex.

### Fase 4: Formulas de fabricacion

Objetivo: capturar los materiales requeridos para fabricar productos terminados.

Checklist:

- [x] Definir tabla o modelo para formulas.
- [x] Definir tabla o modelo para materiales de formula.
- [x] Permitir una formula por producto fabricado.
- [x] Permitir cantidad requerida por unidad.
- [x] Agregar merma esperada.
- [x] Agregar tiempo estimado.
- [x] Calcular costo esperado de material.
- [x] Preparar costo unitario estimado.

Criterio de salida:

- Un producto fabricado puede calcular materiales requeridos y costo esperado antes de crear una orden de fabricacion.

### Fase 5: Orden de fabricacion

Objetivo: fabricar productos terminados usando materia prima del almacen con control de estados.

Checklist:

- [x] Crear modelo de orden de fabricacion.
- [ ] Crear detalle de productos a fabricar.
- [ ] Calcular materiales requeridos desde formula.
- [ ] Validar existencia disponible.
- [ ] Mostrar alerta de faltantes por material cuando la existencia disponible no alcance.
- [ ] Permitir generar o ligar orden de compra por faltantes de materiales.
- [ ] Apartar material al confirmar la orden.
- [ ] Generar documento de entrega a produccion.
- [ ] Registrar consumo real.
- [ ] Registrar sobrantes.
- [ ] Registrar merma y causa.
- [ ] Registrar entrada de producto terminado.
- [ ] Cerrar orden.
- [ ] Permitir cierre parcial si aplica.

Criterio de salida:

- Una orden de fabricacion puede pasar de borrador a cerrada, afectando inventario de forma trazable y sin mezclar consumo esperado con consumo real.

### Fase 6: Ordenes de compra por contexto

Objetivo: reutilizar el flujo de ordenes de compra para HUENTITAN sin duplicar el modulo existente.

Checklist:

- [ ] Revisar flujo actual de ordenes de compra.
- [ ] Identificar dependencias fuertes con areas existentes.
- [ ] Agregar o confirmar almacen destino.
- [ ] Permitir compra para stock.
- [ ] Permitir compra para obra.
- [ ] Permitir compra para orden de fabricacion.
- [ ] Filtrar buscador de productos al catalogo HUENTITAN cuando almacen_destino_id sea AL-HUENTITAN.
- [ ] Relacionar partidas compradas con materiales faltantes de la orden de fabricacion.
- [ ] Permitir entrada documental aunque el material vaya directo a obra.
- [ ] Mantener autorizaciones existentes.
- [ ] Generar recepcion parcial o total.
- [ ] Conectar recepcion con inventario HUENTITAN.

Criterio de salida:

- HUENTITAN puede generar o recibir compras usando el flujo central, con almacen y destino claramente definidos.

### Fase 7: Costeo y reportes

Objetivo: obtener costo unitario confiable para productos fabricados y visibilidad operativa del almacen.

Checklist:

- [ ] Calcular costo esperado por formula.
- [ ] Calcular costo real por consumo.
- [ ] Comparar esperado contra real.
- [ ] Reportar merma.
- [ ] Reportar productos bajo minimo.
- [ ] Reportar ordenes abiertas.
- [ ] Reportar material apartado.
- [ ] Reportar costo unitario de producto terminado.

Criterio de salida:

- El modulo puede explicar cuanto costo fabricar una pieza y que diferencias hubo contra lo planeado.

## 15. Regla para actualizar checkpoints

Cada vez que se termine una parte del desarrollo, se debe actualizar este documento.

Reglas:

- Marcar con `[x]` solo lo que ya este probado o validado.
- Agregar nuevos checkpoints cuando aparezcan decisiones importantes.
- No borrar checkpoints pendientes salvo que se documente por que dejaron de aplicar.
- Si una fase cambia de alcance, agregar una nota debajo de esa fase.
- Mantener el documento como referencia viva del modulo HUENTITAN.







## 16. Cierre de Fase 0

**Fecha de cierre:** 04 de septiembre de 2026  
**Estado:** Cerrada funcionalmente para iniciar Fase 1.

La Fase 0 deja construido el cascaron operativo del modulo HUENTITAN.

Alcance cerrado:

- Ruta principal definitiva `/huentitan`.
- Compatibilidad temporal desde `/inventario/huentitan` hacia la seccion de inventario del modulo.
- Menu desplegable HUENTITAN debajo de GIRALDA.
- Submenus base: Panel, Empleados, Productos, Inventario, Orden compras y Orden fabricacion.
- Pantalla principal tipo panel.
- Pantallas placeholder para secciones pendientes.
- Permiso general `huentitan.access`.
- Permisos iniciales por seccion.
- Permisos asignados al rol `super-admin`.
- Almacen HUENTITAN fijado por codigo `AL-HUENTITAN`.

Decision de alcance:

- HUENTITAN queda tratado como modulo operativo propio, no solo como una pantalla filtrada de inventario.
- El inventario actual/importador queda como seccion interna del modulo.
- Las secciones Empleados, Productos, Orden compras y Orden fabricacion quedan disponibles en el menu como cascaron para desarrollarse por fases.
- No se aplican nuevos movimientos de inventario ni cambios productivos adicionales en Fase 0.

Siguiente fase:

- Iniciar Fase 1: Empleados HUENTITAN.
- Tomar como referencia `GIRALDA -> Empleados`.
- Definir y construir el filtro operativo para personal relacionado con HUENTITAN.


## 17. Revision Fase 1 Paso 1: GIRALDA -> Empleados

**Fecha de revision:** 04 de septiembre de 2026  
**Estado:** Revisado para tomar como base de HUENTITAN.

Hallazgos principales:

- La ruta actual de Giralda es `giralda.empleados` y apunta a `GiraldaController@empleados`.
- La vista principal es `resources/views/giralda/empleados.blade.php`.
- Giralda identifica su personal por area, usando `Area.codigo = GL` o nombre parecido a Giralda.
- El listado usa el modelo global `Empleado`; no existe una tabla separada para empleados Giralda.
- El filtro principal se hace con la columna `Empleado.Area` contra el id del area Giralda.
- La pantalla incluye tabs de listado, asistencia, horas extras y EPP.
- Asistencia y horas extras usan modelos propios de Giralda: `GiraldaAsistencia` y `GiraldaHoraExtra`.
- EPP reutiliza entregas de empleado y se filtra por empleados del area Giralda.

Decision para HUENTITAN:

- Reutilizar el patron de empleados filtrados por area/almacen.
- No duplicar empleados en una tabla nueva.
- Empezar con una vista simple de empleados HUENTITAN: listado, busqueda y estatus.
- No copiar todavia asistencia, horas extras ni EPP como comportamiento completo.
- Dejar la base preparada para agregar tabs despues si el flujo operativo lo requiere.

Riesgo detectado:

- Si HUENTITAN se filtra por area, debe existir un area/codigo confiable para HUENTITAN.
- Si se filtra por almacen, hace falta definir como se relaciona un empleado con un almacen.
- El paso siguiente debe decidir esta relacion antes de construir la pantalla real.

Siguiente paso:

- Fase 1 Paso 2: definir como se identifica el personal de HUENTITAN: area, almacen, centro de costo o relacion adicional.

## 18. Decision Fase 1 Paso 2: Identificacion de personal HUENTITAN

**Fecha de decision:** 04 de septiembre de 2026  
**Estado:** Definido y aplicado en datos base.

Decision tomada:

- El personal de HUENTITAN se identifica por area.
- El area operativa es `HT`.
- El nombre del area es `HUENTITAN`.
- El almacen operativo es `AL-HUENTITAN`.
- El almacen `AL-HUENTITAN` queda ligado al area `HT` mediante `almacenes.area_id`.

Datos actuales verificados:

- Area HUENTITAN: `id=7`, `codigo=HT`, `activo=1`.
- Almacen HUENTITAN: `id=2`, `codigo=AL-HUENTITAN`, `area_id=7`.
- Empleados actualmente relacionados por area `HT`: 41.

Regla para desarrollo:

- La pantalla `HUENTITAN -> Empleados` debe consultar empleados donde `Empleado.Area` sea igual al id del area con codigo `HT`.
- No se creara una tabla separada de empleados HUENTITAN.
- Si en el futuro una persona trabaja en varios almacenes, se evaluara una relacion adicional empleado-almacen; por ahora no aplica.

Siguiente paso:

- Fase 1 Paso 3: crear ruta y vista real `HUENTITAN -> Empleados`, reemplazando el placeholder actual.


## 19. Avance Fase 1 Paso 3: Vista real de Empleados HUENTITAN

**Fecha de avance:** 04 de septiembre de 2026  
**Estado:** Implementado como listado operativo inicial.

Alcance implementado:

- La ruta HUENTITAN -> Empleados reemplaza el placeholder con una vista real.
- El listado consulta empleados por area HT.
- Se agrego busqueda por nombre, apellidos, puesto o ID.
- Se agrego filtro de estatus: activos, baja y todos.
- La vista mantiene acceso a la ficha general del empleado.

Decision:

- La primera version de empleados HUENTITAN no copia todavia asistencia, horas extras ni EPP de Giralda.
- Esos tabs se evaluaran despues si forman parte del flujo real del almacen.



## 20. Avance Fase 2: Index de Productos HUENTITAN

**Fecha de avance:** 04 de septiembre de 2026  
**Estado:** Implementado como catalogo inicial sin movimientos.

Alcance implementado:

- Se creo la vista real HUENTITAN -> Productos.
- El index usa los productos importados desde el inventario semanal con codigo HUE-*.
- Se muestran clasificacion, origen de abastecimiento, bandera de formula y stock minimo.
- Se consulta el ultimo corte importado para mostrar existencia y valor reportados, sin aplicar movimientos.
- Se agregaron filtros por busqueda, tipo, origen y formula.

Decision:

- Este index es solo catalogo inicial y revision operativa.
- No genera entradas, salidas ni movimientos de inventario.



## 21. Avance Fase 1: Roles funcionales HUENTITAN

**Fecha de avance:** 04 de septiembre de 2026  
**Estado:** Implementado en catalogo de roles operativos de empresa.

Roles agregados al catalogo `catalogo_roles`:

- `GERENTE_ALMACEN` - Gerente de almacen.
- `GERENTE_ADMINISTRATIVO` - Gerente administrativo.
- `ENCARGADO_ALMACEN` - Encargado de almacen.
- `AUXILIAR_ALMACEN` - Auxiliar de almacen.
- `PRODUCCION_ALMACEN` - Produccion almacen.
- `CONSULTA_ALMACEN` - Consulta almacen.

Decision:

- Estos roles quedan en el catalogo operativo de la empresa, el mismo que se administra desde Configuracion de empresa.
- Adicionalmente se crearon roles de acceso en la tabla `roles`, que son los visibles en `Configuracion empresa -> Roles` para editar permisos.

Roles de acceso agregados para permisos finos:

- `huentitan-gerente-almacen`.
- `huentitan-gerente-administrativo`.
- `huentitan-encargado-almacen`.
- `huentitan-auxiliar-almacen`.
- `huentitan-produccion-almacen`.
- `huentitan-consulta-almacen`.
- No son permisos finos de acceso todavia.
- Los permisos finos se trabajaran despues desde usuarios, por ejemplo `/usuarios/{usuario}/edit` en la pestaña permisos.
- La vista inicial de empleados HUENTITAN sigue mostrando el puesto actual del empleado; la asignacion formal contra catalogo se revisara cuando se defina si el puesto operativo sera global o especifico por almacen.

Estado de Fase 1:

- Con este punto queda completa la base inicial de Empleados HUENTITAN.
## 22. Avance Fase 2: Ficha de Producto HUENTITAN

**Fecha de avance:** 06 de septiembre de 2026  
**Estado:** Implementado como cascaron funcional inicial.

Alcance implementado:

- El nombre del producto en `HUENTITAN -> Productos` abre la ficha de detalle del producto.
- La ruta de detalle queda como `/huentitan/productos-detalles/{producto}`.
- La ficha usa tabs similares a la vista general de productos.
- Se agregaron tabs base: Informacion general, Inventario, Kardex, Proveedores y Costos.
- El tab Formula solo se muestra cuando el producto tiene activo `requiere_formula`.
- En Informacion general se agrego el check `requiere_formula` para habilitar o deshabilitar la formula.
- La ficha queda preparada para capturar despues la formula con materiales internos de HUENTITAN.

Decision:

- La formula se construira como modelo separado en Fase 4.
- La orden de fabricacion usara esa formula en Fase 5 para calcular materiales, detectar faltantes, apartar inventario al autorizar y consumir material al cerrar produccion.
## 23. Avance Fase 2.1: Especificaciones Tecnicas

**Fecha de avance:** 06 de septiembre de 2026  
**Estado:** Implementado como captura flexible inicial.

Alcance implementado:

- Se agrego el tab Especificaciones en la ficha de producto HUENTITAN.
- Las especificaciones se guardan como JSON flexible en `productos.especificaciones_tecnicas`.
- Se prepararon campos iniciales: diametro, largo, ancho, alto, espesor/calibre, peso, material base, acabado, norma/referencia y observaciones tecnicas.
- La estructura permite ajustar los campos mas adelante sin rehacer el modelo principal.

Decision:

- No se crearon tablas especializadas todavia porque aun estamos afinando que datos tecnicos necesitan capturar por tipo de producto.
- Cuando las formulas y ordenes de fabricacion esten activas, estas especificaciones serviran como referencia tecnica del producto terminado o material consumido.
## 24. Avance Fase 4: Formulas de Fabricacion

**Fecha de avance:** 06 de septiembre de 2026  
**Estado:** Implementado como primera version operativa.

Alcance implementado:

- Se crearon tablas para formulas de HUENTITAN y materiales de formula.
- Cada producto fabricado puede tener una formula principal.
- La formula permite cantidad base, unidad base, merma esperada, tiempo estimado y notas.
- Los materiales de la formula se seleccionan desde productos internos del catalogo HUENTITAN.
- Cada material permite cantidad requerida, unidad, merma y notas.
- El tab Formula calcula costo esperado con el costo promedio del inventario HUENTITAN.
- Se muestra costo base de la formula y costo unitario estimado.

Decision:

- La formula sigue representando consumo esperado, no consumo real.
- El consumo real se confirmara hasta la orden de fabricacion y cierre de produccion.
- Esta base queda lista para que la Fase 5 calcule faltantes, aparte materiales al autorizar y genere movimientos de inventario.
## 25. Ajuste Fase 4: Buscador de Materiales de Formula

**Fecha de avance:** 06 de septiembre de 2026  
**Estado:** Implementado en tab Formula.

Alcance implementado:

- El selector de materiales de la formula se cambio por buscador autocomplete.
- El buscador devuelve solo productos activos del catalogo HUENTITAN.
- Al seleccionar un material, se llena el producto seleccionado y su unidad.
- La cantidad, merma y notas se capturan despues de seleccionar el material.
- Se excluyen productos con `requiere_formula` activo para evitar formulas encadenadas por ahora.

Decision:

- Por ahora HUENTITAN no fabricara un producto A para usarlo como ingrediente de un producto B.
- Esta regla queda comentada en codigo para reabrirla si despues se decide manejar subensambles o formulas encadenadas.
## 26. Detalle operativo Fase 5: Orden de Fabricacion

**Fecha de definicion:** 06 de septiembre de 2026  
**Estado:** Pendiente de desarrollo por subtareas.

La orden de fabricacion se desarrollara en pasos pequenos para validar el flujo antes de afectar inventario real.

### Fase 5.1: Cascaron de ordenes

Objetivo: tener una pantalla base para consultar y entrar al flujo de ordenes de fabricacion.

Checklist:

- [x] Crear listado `HUENTITAN -> Orden fabricacion`.
- [x] Agregar boton `Nueva orden`.
- [x] Definir folio interno.
- [x] Mostrar producto a fabricar, cantidad solicitada, fecha, estado y usuario creador.
- [x] Preparar estados: borrador, calculada, autorizada, en_produccion, cerrada y cancelada.

### Fase 5.2: Crear orden en borrador

Objetivo: crear una orden sin afectar inventario.

Checklist:

- [x] Crear modelo y migracion de orden de fabricacion.
- [x] Seleccionar solo productos HUENTITAN con `requiere_formula` activo.
- [x] Capturar cantidad a fabricar.
- [x] Guardar usuario creador y fecha.
- [x] Guardar estado inicial `borrador`.
- [x] Copiar la formula vigente como snapshot de la orden.

Regla:

- Una orden vieja no debe cambiar si despues se modifica la formula del producto.

### Fase 5.3: Calculo de materiales

Objetivo: calcular materiales requeridos usando el snapshot de la formula.

Checklist:

- [x] Leer materiales de la formula snapshot.
- [x] Multiplicar cantidades por cantidad a fabricar.
- [x] Considerar merma esperada.
- [x] Mostrar cantidad por unidad y cantidad requerida total.
- [x] Mostrar stock actual, reservado y disponible.
- [x] Mostrar costo promedio y costo esperado.
- [x] Cambiar estado a `calculada` cuando el calculo quede generado.

Regla:

- Calcular no aparta ni descuenta inventario.

### Fase 5.4: Alertas de faltantes

Objetivo: detectar si el almacen tiene material suficiente antes de autorizar.

Checklist:

- [x] Marcar materiales con faltante.
- [x] Mostrar requerido, disponible y faltante por material.
- [x] Mostrar resumen de materiales completos y materiales con faltante.
- [x] Preparar accion futura `Generar orden de compra por faltantes`.
- [ ] Bloquear autorizacion si hay faltantes, salvo permiso especial definido despues.

### Fase 5.5: Autorizar y apartar

Objetivo: reservar material para una orden autorizada sin hacer salida definitiva.

Checklist:

- [ ] Agregar accion `Autorizar`.
- [ ] Validar disponibilidad nuevamente en backend.
- [ ] Incrementar `stock_reservado` en `inventario_stock`.
- [ ] Registrar reserva relacionada con la orden.
- [ ] Guardar usuario y fecha de autorizacion.
- [ ] Cambiar estado a `autorizada`.

Regla:

- Autorizar aparta material, pero el material sigue fisicamente en almacen.

### Fase 5.6: Entregar a produccion

Objetivo: mover material reservado hacia produccion con documento formal.

Checklist:

- [ ] Agregar accion `Entregar a produccion`.
- [ ] Generar documento o movimiento de salida a produccion.
- [ ] Disminuir reservado.
- [ ] Registrar material entregado.
- [ ] Guardar responsable de entrega.
- [ ] Cambiar estado a `en_produccion`.

### Fase 5.7: Cierre de produccion

Objetivo: registrar resultado real de fabricacion.

Checklist:

- [ ] Capturar cantidad fabricada buena.
- [ ] Capturar cantidad rechazada o danada.
- [ ] Capturar consumo real por material.
- [ ] Registrar sobrantes.
- [ ] Registrar merma y causa.
- [ ] Registrar entrada de producto terminado.
- [ ] Cambiar estado a `cerrada` o `parcialmente_cerrada`.

Regla:

- El consumo real se confirma en el cierre, no al crear ni al calcular la orden.

### Fase 5.8: Kardex y trazabilidad

Objetivo: que cada accion importante deje evidencia consultable.

Checklist:

- [ ] Relacionar orden de fabricacion con materiales requeridos.
- [ ] Relacionar reserva de material con orden de fabricacion.
- [ ] Relacionar salida a produccion con orden de fabricacion.
- [ ] Relacionar entrada de producto terminado con orden de fabricacion.
- [ ] Mostrar movimientos desde el kardex del producto.
- [ ] Consultar desde producto terminado que orden lo produjo.

Primer entregable recomendado:

- Construir Fase 5.1, Fase 5.2 y Fase 5.3 juntas.
- Crear orden en borrador, seleccionar producto fabricable, capturar cantidad y ver materiales calculados con faltantes.
- No afectar inventario todavia.
## 27. Avance Fase 5.1: Cascaron de Ordenes de Fabricacion

**Fecha de avance:** 06 de septiembre de 2026  
**Estado:** Implementado como base navegable sin afectacion de inventario.

Alcance implementado:

- Se creo la tabla `huentitan_ordenes_fabricacion` para encabezados de orden.
- Se creo el modelo `HuentitanOrdenFabricacion`.
- Se preparo folio interno, producto a fabricar, cantidad solicitada, fecha, estado y usuario creador.
- Se definieron estados base: borrador, calculada, autorizada, en_produccion, cerrada y cancelada.
- La pantalla `HUENTITAN -> Orden fabricacion` ya muestra listado, resumen por estado y boton `Nueva orden`.
- La pantalla `Nueva orden` queda como cascaron para capturar datos en Fase 5.2.
- Se creo el permiso `huentitan.ordenes_fabricacion.create` y se asigno a `super-admin` en la base local.

Decision:

- Esta fase no calcula materiales, no aparta stock y no genera movimientos.
- La captura real del borrador, snapshot de formula y calculo de materiales quedan para Fase 5.2 y Fase 5.3.
- La asignacion del permiso `create` a roles operativos distintos de `super-admin` queda pendiente de confirmacion funcional.
## 28. Avance Fase 5.2: Crear Orden en Borrador

**Fecha de avance:** 07 de septiembre de 2026  
**Estado:** Implementado y probado con rollback.

Alcance implementado:

- El formulario `Nueva orden fabricacion` ya guarda ordenes en estado `borrador`.
- El producto a fabricar se limita a productos HUENTITAN con `requiere_formula` activo y formula con materiales.
- Se captura cantidad solicitada y fecha.
- Se genera folio interno automatico con formato `OF-HUE-YYYYMM-0001`.
- Se guarda usuario creador.
- Se copia la formula vigente como snapshot en el encabezado de la orden.
- Se copian los materiales de la formula a `huentitan_orden_fabricacion_materiales`.
- Se calcula y guarda costo estimado de materiales con el costo promedio actual del almacen HUENTITAN.

Decision:

- El estado `borrador` se conserva aunque crear la orden no requiera autorizacion, porque permite editar o revisar antes de calcular/apartar inventario.
- Esta fase no aparta stock, no descuenta inventario y no genera kardex.
- La validacion de existencias, faltantes y cambio a `calculada` queda para Fase 5.3.
## 29. Avance Fase 5.3A: Detalle de Orden de Fabricacion

**Fecha de avance:** 07 de septiembre de 2026  
**Estado:** Implementado como ficha de consulta sin afectacion de inventario.

Alcance implementado:

- El folio en el listado `HUENTITAN -> Orden fabricacion` abre el detalle de la orden.
- Se agrego la ruta `/huentitan/ordenes-fabricacion/{orden}`.
- El detalle muestra folio, producto fabricado, cantidad solicitada, fecha, estado, usuario creador, formula base y costo estimado.
- Se muestra la tabla de materiales snapshot de la orden.
- La tabla compara requerido contra stock actual, reservado y disponible del almacen HUENTITAN.
- Se calcula faltante por material en pantalla.
- Se dejaron botones futuros deshabilitados para calcular materiales, generar compra por faltantes y apartar material.

Decision:

- Esta vista es de auditoria y revision previa.
- No aparta stock, no descuenta inventario y no genera movimientos de kardex.
- La autorizacion sigue comentada/reservada para una fase futura si el flujo operativo la requiere.
## 30. Avance Fase 5.3B: Calculo Formal de Materiales

**Fecha de avance:** 07 de septiembre de 2026  
**Estado:** Implementado y probado con rollback.

Alcance implementado:

- Se agrego accion `Calcular materiales` en el detalle de orden.
- El calculo usa los materiales snapshot de la orden, no la formula viva del producto.
- Se guarda por material el stock actual, reservado, disponible y faltante calculado.
- La orden cambia a estado `calculada` al terminar el calculo.
- Se guarda usuario y fecha/hora de calculo.
- La ficha muestra el calculo congelado cuando la orden esta en estado `calculada`.

Decision:

- Calcular materiales no aparta stock, no descuenta inventario y no genera kardex.
- La autorizacion sigue comentada/reservada para una fase futura.
- Si hay faltantes, el siguiente paso sera preparar la accion para generar o ligar una orden de compra.
## 31. Avance Fase 5.4A: Faltantes Preparados para Compra

**Fecha de avance:** 07 de septiembre de 2026  
**Estado:** Implementado y probado con rollback.

Alcance implementado:

- Se agrego bandera `requiere_compra` en materiales de orden de fabricacion.
- Se agrego `cantidad_sugerida_compra` para preparar la compra sin crear OC todavia.
- Al calcular materiales, los faltantes se marcan automaticamente para compra.
- Los materiales sin faltante pueden marcarse manualmente para compra si el usuario quiere aprovechar la solicitud.
- La ficha de orden muestra columna `Compra` con checkbox y cantidad sugerida.
- Se agrego enlace futuro hacia `HUENTITAN -> Orden compras` cuando exista al menos un material marcado para compra.

Decision:

- No se genera automaticamente una orden de compra desde la orden de fabricacion.
- Los materiales marcados se usaran despues como bandeja seleccionable en `HUENTITAN -> Orden compras`, similar al flujo de solicitudes de material de obra civil.
- La cabecera de OC se generara dentro del flujo formal de compras, donde se definiran proveedor, moneda, metodo de pago y demas datos administrativos.
- Apartar material sigue bloqueado; no existe ruta que afecte `stock_reservado` en esta fase.
### Ajuste Fase 5.4A: Checkbox simple para compra

**Fecha de ajuste:** 07 de septiembre de 2026  
**Estado:** Aplicado.

Decision:

- En la orden de fabricacion solo se marca si el material debe pasar a compras.
- No se captura cantidad de compra en la orden de fabricacion.
- La cantidad sugerida se conserva internamente con base en el faltante, pero el usuario la ajustara dentro del flujo formal de orden de compra.
- El proveedor, moneda, metodo de pago y agrupacion por proveedor se resolveran en `HUENTITAN -> Orden compras` o en el flujo general de OC.
- Una orden de fabricacion puede originar varias OC si los materiales pertenecen a proveedores diferentes.


### Nota operativa pendiente: centro de costo default por area

En produccion existen centros de costo por area, por lo que la OC podra automatizar el centro de costo al seleccionar un area. La regla debe implementarse con una relacion configurable `area -> centro de costo default`, no por nombre quemado. Mientras una OC tenga destino `obra`, debe mantenerse la regla actual de no combinar obra y centro de costo.
