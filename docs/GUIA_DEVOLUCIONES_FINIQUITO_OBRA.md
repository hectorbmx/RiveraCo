# Devoluciones y finiquito preliminar de materiales de obra

Esta guía describe el flujo implementado para solicitar y registrar la devolución de materiales entregados a una obra. El finiquito disponible es una **consulta preliminar**: todavía no captura la distribución definitiva de lo consumido y lo devuelto ni cierra la obra.

## Permisos y alcance

| Permiso | Uso actual |
| --- | --- |
| `obra.devoluciones.view.access` | Consultar materiales y solicitudes de una obra permitida, imprimir solicitudes y consultar o imprimir el finiquito preliminar. |
| `obra.devoluciones.create.access` | Con el permiso de consulta y acceso a la obra, guardar borradores, presentar solicitudes y cancelar solicitudes sin recepción confirmada. |
| `obra.devoluciones.receive.access` | Con el permiso de consulta, acceder a devoluciones pendientes de almacén, registrar recepciones físicas, marcar herramientas pendientes de evaluación y aplicar partidas aptas. |

Las pantallas de obra también aplican la restricción de acceso por obra. La consulta de almacén muestra solicitudes de **todas las obras** a quien tenga ambos permisos de consulta y recepción; no existe un filtro de autorización por almacén receptor. Las asignaciones reales de estos permisos a usuarios y roles deben comprobarse en cada ambiente. La prueba persistente del paso 19.23 usó permisos simulados.

## Cómo preparar una devolución

1. Entra a la obra y abre **Materiales en obra**. Esta pestaña requiere el permiso de consulta.
2. Revisa las entregas identificadas. Se incluyen entregas directas y salidas de almacén general o Huentitán aplicadas a la obra, aunque no tengan OC ni requisición. Las OC y requisiciones vinculadas son referencias; no se vuelven a sumar como otra entrega.
3. Selecciona las partidas por su entrega de origen, captura cantidad, condición y observaciones, y guarda un borrador o presenta la solicitud. Para solicitudes nuevas se requiere una condición válida por partida. Una misma entrega se selecciona una vez por solicitud; si se necesitan condiciones distintas para un mismo origen, deben usarse solicitudes separadas.
4. Un **borrador** se puede corregir y no compromete saldo. Al **presentar** la solicitud el sistema vuelve a validar la entrega y compromete la cantidad solicitada para evitar solicitudes que excedan el saldo. Se genera un folio `DEV-OB-...` y se puede imprimir la solicitud.
5. Una solicitud sin recepción confirmada se puede cancelar con motivo; se conserva el historial y se libera el compromiso. Una solicitud con recepción física registrada no se cancela por este mecanismo.

El saldo disponible es informativo y el sistema lo vuelve a comprobar al presentar. Si el origen, su costo o alguna devolución histórica no se pueden identificar con certeza, la entrega se marca para revisión y no se atribuye automáticamente el saldo.

## Recepción física y aplicación

1. Almacén abre **Devoluciones de obra pendientes** desde Documentos de inventario o Entradas de Huentitán, o visita `/inventario/devoluciones-obra`.
2. Abre la solicitud y selecciona **Registrar recepción física**. Captura el almacén que recibe, la fecha y, únicamente para las partidas recibidas en esa ocasión, cantidad, condición y observaciones. Se permiten recepciones parciales y no se acepta una cantidad mayor a la pendiente.
3. La confirmación crea un folio `REC-OB-...` y registra lo recibido físicamente. En este momento queda **pendiente de aplicación**: por sí sola no crea entrada de inventario ni reduce el costo de la obra.
4. Para las partidas en buen estado cuya condición coincide con la declarada, usa **Aplicar entrada y costo de obra**. Se genera una devolución de inventario y su movimiento con vínculo al origen; aumentan las existencias del almacén receptor y el costo neto de materiales se ajusta usando el importe histórico de la entrega. Los documentos y movimientos se registran conjuntamente.
5. En una recepción mixta se aplican las partidas aptas; las demás permanecen pendientes. Un reintento de aplicación no debe duplicar la entrada. Una condición diferente se muestra para aclaración y no se resuelve automáticamente.
6. Para una herramienta dañada o desgastada, si su producto está clasificado como `herramienta`, usa **Registrar herramientas pendientes de evaluación**. El registro conserva condición, cantidad, usuario y origen. La herramienta queda en custodia pendiente de evaluación: no se incorpora al inventario disponible, no genera todavía movimiento de kardex y no se le determina un costo definitivo.

Una recepción aplicada no se debe editar, borrar ni cancelar mediante el flujo general de inventario. La corrección de recepciones confirmadas aún no tiene un mecanismo implementado.

## Cómo leer el seguimiento y el finiquito

Desde **Materiales en obra**, abre **Consultar finiquito de materiales**. También puedes imprimir el resumen preliminar desde esa pantalla.

- **Entregado:** cantidad de la entrega aplicada a la obra.
- **Devolución aplicada:** cantidad cuya entrada y ajuste de costo ya se registraron.
- **Recibido, sin aplicar:** cantidad que almacén confirmó físicamente pero que todavía no tiene aplicación de inventario y costo. Puede incluir herramientas pendientes de evaluación.
- **Pendiente de recibir:** cantidad solicitada que almacén aún no confirmó físicamente. Está incluida en el saldo sin devolución física; no debe sumarse otra vez a ese saldo.
- **Sin devolución física registrada:** entregado menos lo devuelto y lo recibido físicamente sin aplicar. Puede incluir material consumido o todavía pendiente de devolver; no identifica por sí solo cuál de los dos casos corresponde.
- **Pendiente de evaluación:** herramientas recibidas y registradas para evaluación. Es un subconjunto de cantidades recibidas, no una cantidad adicional para sumar.
- **Costo neto provisional:** costo histórico entregado menos devoluciones aplicadas. No es necesariamente el costo final consumido, y no incluye una valuación definitiva de herramientas pendientes.

El seguimiento de cada solicitud muestra cantidades, folios de recepción, condiciones y observaciones. Una cantidad pendiente no se clasifica automáticamente como pérdida. Si los datos presentan inconsistencias, la vista indica **Revisar**. El resumen impreso refleja la consulta al generarse; no crea un folio de cierre ni una fotografía inmutable y puede cambiar tras nuevos movimientos.

## Temas pendientes de definir e implementar

- Capturar y autorizar el consumo total o parcial y la distribución definitiva del material al finiquitar la obra; definir si el finiquito requiere cabecera persistente, versiones y autorización.
- Definir quién evalúa herramientas dañadas o desgastadas, la tabla de prorrateo, su valuación definitiva y los permisos necesarios.
- Definir y registrar la disposición posterior de herramientas en evaluación, incluida su entrada o segregación en inventario y su movimiento de kardex; hoy no están disponibles automáticamente.
- Definir cómo aclarar y resolver diferencias de cantidad o condición, y el tratamiento de material dañado que no esté clasificado como herramienta.
- Definir corrección o reversión auditada de recepciones ya confirmadas o aplicadas, con sus permisos y efectos conjuntos sobre inventario y costo.
- Completar la revisión operativa con los usuarios y validar sus permisos reales. Las pruebas de los tres orígenes y recepciones mixtas se ejecutaron en SQLite; la validación persistente en MySQL local cubrió una entrega directa y la concurrencia de aplicación de una recepción. No se ha validado el flujo en producción.

## Evidencia local

Se conservan las pruebas identificadas en la obra local de simulación 42: `DEV-OB-00000004` / `REC-OB-00000001` para concurrencia y `DEV-OB-00000005` / `REC-OB-00000002` / `REC-OB-00000003` para recepción parcial y condición diferente. Son datos de prueba, no entregas reales. El detalle de las comprobaciones y los checkpoints está en `docs/PLAN_RECEPCION_DIRECTA_OBRA.md`.
