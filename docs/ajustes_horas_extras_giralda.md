# Horas extra Giralda: tarifas y snapshots

Fecha: 2026-10-26. Implementación local; despliegue y llenado de producción pendientes.

## Objetivo y hallazgos

Conservar por captura la tarifa e importe de horas extra, evitando que un cambio posterior de sueldo o cuota modifique su valor. El importe no acredita un pago: el pago efectivo pertenece a nómina.

Anteriormente `giralda_horas_extras` solo conservaba las horas y datos operativos. Impresión y nómina recalculaban con el sueldo actual. La configuración de tarifas por empleado se guarda en `empleado_hora_extra_tarifas`, con modalidad, cuota y vigencias.

No existe una relación individual entre cada captura y un recibo pagado. El total `horas_extra` del recibo también puede incluir otros conceptos; no se reparte automáticamente para inventar importes históricos.

## Implementado

- [x] Tabla y modelo de tarifas por empleado, con historial de vigencias.
- [x] Configuración en Empleados → Datos generales → Nómina base.
- [x] Vista previa de tarifa por fecha e importe en el modal de Giralda.
- [x] Migración `2026_10_26_000003_add_snapshot_to_giralda_horas_extras_table.php`, aplicada localmente.
- [x] Guardado de snapshots de capturas nuevas desde el servidor.
- [x] Edición de horas conservando tarifa, base salarial, origen y fecha de captura originales; el importe se recalcula con la tarifa guardada.
- [x] Consulta, acumulado semanal, CSV e impresión utilizan los importes guardados.
- [x] Nómina suma snapshots por registro, admite varias tarifas por periodo y rechaza sincronizar periodos con registros sin snapshot.
- [x] Servicio de reconstrucción y comando con simulación por defecto.
- [ ] Revisar resultados y aplicar llenado histórico bajo las reglas acordadas.
- [ ] Desplegar y comprobar en producción.

## Datos conservados por captura

La migración agrega columnas nullable para conservar los registros existentes:

| Campo | Contenido |
| --- | --- |
| `empleado_hora_extra_tarifa_id` | Configuración utilizada, si existía |
| `costo_hora_extra` | Importe final por hora extra, cuatro decimales |
| `importe` | Horas multiplicadas por tarifa, redondeadas a centavos por registro |
| `sueldo_base_snapshot` | Sueldo utilizado en cálculo automático; null para cuota fija |
| `origen_tarifa` | `automatica` o `cuota_fija` |
| `snapshot_fuente` | Captura, reconstrucción por cuota fija o estimación por sueldo actual |
| `snapshot_at` | Fecha de generación del snapshot |
| `snapshot_por` | Usuario de la captura; null para reconstrucción por consola |

En cálculo automático se conserva la regla existente: `Sueldo_real` positivo; en su defecto `Sueldo + Complemento`. La tarifa es `base / 7 / 8 * 2`, redondeada a cuatro decimales. No se cambia aquí la regla de periodicidad salarial.

Las capturas nuevas sin sueldo ni cuota válida se rechazan; no se inventa una tarifa cero. El navegador solo muestra una vista previa: el servidor vuelve a determinar la tarifa al guardar.

La edición conserva la fecha porque esta determina la vigencia aplicable. No se incorpora edición manual de tarifas por captura en esta etapa. Los registros sin snapshot requieren completar su historial antes de editarse. Edición y eliminación se rechazan si el periodo del empleado tiene nómina cerrada o pagada.

## Comando histórico

Servicio: `App\Services\Empleados\GiraldaHoraExtraSnapshotService`.

Comando: `giralda:horas-extras-snapshots`.

Primero ejecutar sin `--aplicar`; muestra clasificación y cantidades sin escribir registros:

```powershell
php artisan giralda:horas-extras-snapshots
php artisan giralda:horas-extras-snapshots --empleado=3729 --desde=2026-10-01 --hasta=2026-10-31 --detalle
```

Para aplicar solo reconstrucciones con cuota fija vigente en la fecha:

```powershell
php artisan giralda:horas-extras-snapshots --aplicar
```

Cuando se haya decidido aceptar el sueldo actual como estimación del histórico, simular y revisar antes de aplicar:

```powershell
php artisan giralda:horas-extras-snapshots --estimar-sueldo-actual --detalle
php artisan giralda:horas-extras-snapshots --aplicar --estimar-sueldo-actual
```

Ambas modalidades aceptan `--empleado`, `--desde`, `--hasta`, `--lote=200` (de 1 a 1000) y `--detalle`. Los filtros pueden combinarse; `--hasta` también puede usarse sin `--desde`.

### Interpretación de resultados

| Resultado | Significado |
| --- | --- |
| `cuota_fija` | Se puede reconstruir con la cuota vigente en la fecha |
| `estimado` | Se utilizó el sueldo actual con opción explícita; fuente `estimado_sueldo_actual` |
| `requiere_sueldo_historico` | Cálculo automático sin autorización para estimarlo con sueldo actual |
| `nomina_protegida` | Existe recibo o corrida cerrada/pagada en el periodo; requiere revisión |
| `parcial_revisar` | Tiene algunos datos de snapshot; no se sobrescriben |
| `sin_tarifa` | No hay sueldo ni cuota válida |
| `sin_empleado` / `horas_invalidas` | Datos que requieren corrección previa |
| `existente` | Otro proceso ya completó el snapshot; se conserva |

El comando procesa lotes y bloquea cada registro durante su revisión. Es repetible: no sobrescribe snapshots completos ni parciales. Al aplicar no modifica horarios, autorizaciones, `updated_at`, recibos ni estados de nómina. El código de salida 0 significa que el recorrido terminó; revisar siempre el contador de pendientes. Las opciones inválidas devuelven 2.

La protección por nómina se basa en empleado y periodo porque no hay vínculo individual. Si un recibo cerrado/pagado carece de límites de periodo, se trata conservadoramente como posible coincidencia. Los casos protegidos se resuelven por revisión y conciliación, no con una opción para forzar el llenado.

## Despliegue

Publicar el código y assets construidos, incluyendo `public/build/manifest.json` y los archivos referenciados. Aplicar las migraciones en orden; si la primera ya está registrada, Laravel la omite:

```powershell
php artisan migrate --path=database/migrations/2026_10_26_000002_create_empleado_hora_extra_tarifas_table.php --force
php artisan migrate --path=database/migrations/2026_10_26_000003_add_snapshot_to_giralda_horas_extras_table.php --force
php artisan view:cache
php artisan giralda:horas-extras-snapshots
```

Mientras existan horas extra sin snapshot, los totales incompletos se muestran como pendientes y nómina no los sincroniza. Completar o conciliar esos registros antes de operar la sincronización de esos periodos. No recalcular ni regenerar recibos cerrados/pagados para este cambio.

Revertir la migración de snapshots elimina sus columnas y la información guardada; no utilizar el rollback como corrección de importes.

## Checkpoints de validación

- Captura automática y cuota fija calculadas en servidor, ignorando importes enviados por el navegador.
- Cambiar sueldo o cuota posterior no modifica snapshots existentes.
- Editar horas usa la tarifa guardada; cambiar fecha se rechaza.
- Nómina e impresión suman registros con tarifas distintas sin multiplicar todo el periodo por una tarifa única.
- Capturas sin snapshot se identifican y no se consideran importes cero.
- Simulación sin escrituras, aplicación repetible, filtros y lotes.
- Cuotas históricas y estimaciones diferenciadas; recibos protegidos y snapshots parciales conservados.

Pruebas: `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --filter="EmpleadoHoraExtraTarifaTest|GiraldaHoraExtraSnapshotTest"` y `node --test tests/Frontend/giralda-horas-extra.test.cjs`.

## Resultado local inicial

La simulación del 2026-10-26 encontró nueve registros anteriores que requieren sueldo histórico. La simulación con `--estimar-sueldo-actual` los clasifica como nueve estimaciones completables. No se aplicó llenado histórico ni se modificaron recibos; producción no fue consultada ni modificada.
