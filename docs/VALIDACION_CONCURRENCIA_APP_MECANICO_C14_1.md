# T14 / C14.1. Concurrencia de órdenes sobre InnoDB

Fecha: 2026-10-23. Checkpoint independiente de la decisión D05 y del inicio/completado de T12.

## Alcance y aislamiento

Se agregó un arnés reproducible que inicializa una instancia MariaDB temporal con los binarios locales, sin instalar servicios de Windows. Usa puerto aleatorio, escucha únicamente en 127.0.0.1 y guarda sus datos en `storage/app/testing/mobile-order-<uuid>/data`.

Antes de crear la base y antes de apagar el servidor, comprueba `@@datadir` contra ese directorio temporal. No lee credenciales de `.env`/`phpunit.xml`; el cliente Laravel recibe configuración de pruebas antes de arrancar y omite las cachés de configuración/rutas operativas. No se ejecutan migraciones ni se copia la base de desarrollo. Las diez tablas mínimas del fixture usan InnoDB.

Cada petición corre en un proceso PHP y kernel HTTP propios sobre las rutas mecánicas reales. Se controlan el usuario autenticado, la consulta de UsuarioApp y la proyección de la respuesta de seguimiento; se mantiene real la resolución de ruta/modelo, permiso mecánico mediante Gate, propiedad por empleado, servicio de generación/edición, catálogo de materiales, transacción y escrituras del snapshot. Se desactiva únicamente el throttle del fixture. Esta prueba no sustituye autenticación con un usuario real ni el contrato completo de lectura.

El primer proceso se detiene mediante una barrera de prueba después de adquirir `SELECT FOR UPDATE`. El segundo avisa cuando intenta el mismo bloqueo. El observador comprueba `information_schema.INNODB_LOCK_WAITS` y `INNODB_TRX`, con los IDs de conexión de ambos procesos, antes de liberar la primera transacción. No se considera probada la concurrencia solo por lanzar dos procesos o introducir un retraso fijo.

## Resultados

Ejecución final sobre **10.4.32-MariaDB**, terminada a **2026-10-23T19:19:48-06:00**. Tres escenarios y seis peticiones HTTP, todos con espera de bloqueo observada:

| Escenario | Respuestas A / B | Persistencia comprobada |
|---|---|---|
| Dos generaciones del mismo borrador | 200 / 200 | Ambas devuelven OSM-26-000001; dos materiales exactos, sin duplicación; autor original 80 y estado orden_generada. |
| Primera generación falla en el segundo material; otra espera | 500 controlado / 200 | Trigger de prueba provoca el fallo SQL; rollback de cabecera/filas, generación completa por B con autor 81 y solo dos materiales. |
| Generación y edición de borrador concurrentes | 200 / 409 | La edición relee el estado después del bloqueo, conserva la categoría Original y no altera la orden. |

En los tres casos se verificaron folio, fecha de generación, autor, estado, IDs y cantidades de los materiales. El stock actual/reservado del fixture quedó intacto y no se crearon solicitudes de abastecimiento. Se confirmó el apagado de la instancia y se retiró su directorio de datos; los temporales restantes se retiran antes de actualizar el grafo.

[Informe JSON de la ejecución final](validaciones/APP_MECANICO_CONCURRENCIA_C14_1.json).

El primer intento del arnés abortó al no observar la espera dentro de su ventana; se agregó señalización del intento transaccional del segundo proceso y diagnóstico de conexiones/bloqueos. Dos ejecuciones posteriores pasaron los tres escenarios; la última incluye además apagado protegido por verificación de `@@datadir`. No se modificó código de producción para hacer pasar la prueba.

## Repetir la prueba

Desde PowerShell en la raíz del backend:

```powershell
& ./tools/test_mobile_order_concurrency.ps1
```

Requiere PHP con pdo_mysql, dependencias Composer instaladas y los binarios locales de MariaDB en `C:\xampp\mysql\bin` (parámetro `-MariaDbBin` para otra ubicación). No necesita un servidor MySQL previo ni cambiar `.env`. El script muestra el directorio de evidencia; conserva informe y logs para diagnóstico, y retira los datos tras detener la instancia. Si no logra confirmar el apagado, conserva los datos y falla explícitamente.

Archivos nuevos: `tools/test_mobile_order_concurrency.ps1`, `tools/test_mobile_order_concurrency.php` y `tests/Support/MobileOrderConcurrencyWorker.php`. Sintaxis PHP y PowerShell, UTF-8/mojibake y actualización de grafo comprobados al cierre.

## Pendientes de T14

La casilla específica de concurrencia InnoDB queda validada. T14 continúa abierta por el recorrido completo conectado web/API/app, usuario mecánico real, dispositivo/cámara y ejecución de T12/C13.5. D05 y los límites de abastecimiento de vehículos permanecen pendientes. No se realizó commit/push final.

## Cierre del checkpoint

Cierre: 2026-10-23T19:23:34.2342304-06:00 (America/Mexico_City). Siguiente punto no antes de 2026-10-23T19:33:34.2342304-06:00. Grafo actualizado con 7725 nodos/13380 relaciones tras reintento elevado por WinError 5; HTML omitido por tamaño. Los JSON de datos no generan nodos AST y continúa el aviso previo de parser SQL ausente.
