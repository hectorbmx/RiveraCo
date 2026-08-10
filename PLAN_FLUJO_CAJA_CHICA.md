# Plan de accion: flujo semanal de caja chica para almacen

## Objetivo

Separar el flujo operativo de las OC de almacen/caja chica del flujo normal de pagos a proveedores.

El flujo objetivo es:

```text
OC autorizada de almacen
-> verificacion
-> corte semanal
-> reposicion al encargado de almacen
```

La vista base sera:

```text
/ordenes_compra?area_codigo=GL&semana=YYYY-MM-DD
```

## Principios

- No modificar OC autorizadas.
- Si una OC autorizada tiene error, se cancela y se crea otra.
- Las OC de almacen siguen siendo ordenes de compra.
- La reposicion de caja chica no debe mezclarse con pagos individuales a proveedores.
- El pago real es una reposicion interna al encargado de almacen.
- La vista semanal GL sera la bandeja principal del flujo.

## Fase 1: definir estados y reglas

### Paso 1.1: confirmar estados actuales

- Revisar estados usados actualmente por OC:
  - `BORRADOR`
  - `AUTORIZADA`
  - `CANCELADA`
  - estados relacionados con pago/programacion si existen.

Checkpoint:

- Lista documentada de estados actuales.
- Confirmar que `AUTORIZADA` bloquea edicion.

### Paso 1.2: definir estado nuevo de verificacion

Propuesta:

```text
VERIFICADA
```

Uso:

```text
AUTORIZADA -> VERIFICADA
```

Checkpoint:

- Confirmar nombre tecnico del estado.
- Confirmar texto visible en UI: `Verificada`.

### Paso 1.3: definir regla de alcance

La verificacion aplica primero solo para:

```text
area_codigo = GL
```

Checkpoint:

- Confirmar que no cambia el flujo de OC normales.
- Confirmar que pagos-proveedores no recibe automaticamente estas OC como pagos individuales.

## Fase 2: permisos

### Paso 2.1: crear permiso de verificacion

Propuesta:

```text
ordenes_compra.verify.access
```

Alternativa si se prefiere espanol:

```text
ordenes_compra.verificar
```

Checkpoint:

- Permiso definido.
- Usuario verificador asignado.
- Usuarios sin permiso no ven el boton.

### Paso 2.2: proteger accion en backend

La accion de verificar debe validar:

- Usuario autenticado.
- Permiso de verificar.
- OC existe.
- OC pertenece a area GL.
- OC esta autorizada.
- OC no esta cancelada.

Checkpoint:

- Usuario sin permiso recibe 403.
- Usuario con permiso puede verificar.
- OC no autorizada no se puede verificar.

## Fase 3: vista semanal de almacen

### Paso 3.1: detectar modo almacen

En la vista de ordenes, identificar cuando:

```text
area_codigo = GL
```

Checkpoint:

- La pantalla normal de OC no cambia.
- La pantalla GL activa elementos especiales de caja chica.

### Paso 3.2: agregar resumen semanal

Agregar un bloque/badge cerca de `SEMANA SELECCIONADA`.

Datos sugeridos:

```text
Acumulado semana
Autorizado
Verificado
Pendiente de verificar
Reposicion sugerida
```

Checkpoint:

- El acumulado coincide con la suma de las OC visibles de la semana.
- El total no depende de la paginacion.
- El resumen solo aparece en GL.

### Paso 3.3: calcular acumulado semanal

Calcular con las OC de GL dentro del rango:

```text
inicioSemana -> finSemana
```

Separar:

```text
total_autorizado
total_verificado
total_pendiente_verificar
conteo_pendiente_verificar
```

Checkpoint:

- Una OC cancelada no suma.
- Una OC borrador no suma como autorizada.
- Una OC autorizada sin verificar suma en pendiente.
- Una OC verificada suma en verificado.

### Paso 3.4: calcular fecha sugerida de reposicion

Regla operativa:

```text
corte semanal: viernes
reposicion: martes de la semana siguiente
```

Para una semana seleccionada:

```text
reposicion_sugerida = martes posterior al domingo de esa semana
```

Ejemplo:

```text
Semana 03/08/2026 al 09/08/2026
Reposicion sugerida: martes 11/08/2026
```

Checkpoint:

- La fecha se muestra correctamente para semanas anteriores.
- La fecha no se confunde con la semana actual.

## Fase 4: boton dinamico Verificar

### Paso 4.1: mostrar accion por estado

Reglas:

```text
AUTORIZADA + permiso verificar + GL -> Verificar
VERIFICADA -> badge Verificada
CANCELADA -> sin Verificar
BORRADOR -> sin Verificar
```

Checkpoint:

- Solo el verificador ve `Verificar`.
- Usuarios normales no ven el boton.
- El boton no aparece fuera de GL.

### Paso 4.2: confirmar accion

Antes de verificar, mostrar confirmacion:

```text
Confirmar que esta OC ya fue revisada para el corte semanal.
```

Checkpoint:

- Evita verificaciones accidentales.
- La accion es clara para el usuario.

### Paso 4.3: guardar datos de verificacion

Campos sugeridos:

```text
verificado_por
usuario_verifica
fecha_verificacion
```

Checkpoint:

- Se registra quien verifico.
- Se registra fecha/hora.
- Se puede mostrar en detalle de OC.

## Fase 5: corte semanal y reposicion

### Paso 5.1: cambiar concepto de Pagar en GL

En la vista GL, evitar que la accion diga solamente:

```text
Pagar
```

Opciones recomendadas:

```text
Reposicion
Programar reposicion
Incluir en reposicion
```

Checkpoint:

- El usuario entiende que no es pago al proveedor.
- No se mezcla mentalmente con pagos-proveedores.

### Paso 5.2: definir si la reposicion sera por OC o agrupada

Recomendacion:

```text
Reposicion agrupada por semana
```

Ejemplo:

```text
Semana: 03/08/2026 al 09/08/2026
Encargado: almacen
Total verificado: $X
Fecha sugerida: martes 11/08/2026
```

Checkpoint:

- Confirmar si se creara un registro nuevo de reposicion.
- Confirmar si primero solo se mostrara el total semanal.

### Paso 5.3: primera version sin modulo nuevo

Para reducir riesgo, primera version:

- Agrega verificacion.
- Agrega acumulado semanal.
- Agrega fecha sugerida de reposicion.
- Cambia texto visual de accion en GL.
- No crea todavia modulo formal de reposiciones.

Checkpoint:

- Flujo usable sin redisenar pagos.
- No afecta pagos-proveedores.

## Fase 6: reportes/exportes

### Paso 6.1: revisar exportar efectivo y exportar TC

Confirmar si deben incluir:

- Todas las OC de la semana.
- Solo autorizadas.
- Solo verificadas.
- Separadas por forma de pago.

Checkpoint:

- Exportes respetan el estado esperado.
- Exportes muestran total semanal correcto.

### Paso 6.2: agregar datos de verificacion al export

Campos sugeridos:

```text
Estado
Verificado por
Fecha verificacion
Reposicion sugerida
```

Checkpoint:

- El corte impreso/exportado sirve como soporte de revision.

## Fase 7: pruebas manuales

### Paso 7.1: usuario sin permiso

Probar:

- Entra a semana GL.
- Ve acumulado si tiene acceso a la vista.
- No ve boton `Verificar`.

Checkpoint:

- No puede verificar por UI.
- No puede verificar por URL directa.

### Paso 7.2: usuario verificador

Probar:

- Entra a semana GL.
- Ve boton `Verificar` en OC autorizada.
- Verifica una OC.
- La OC cambia a `VERIFICADA`.
- El acumulado se actualiza.

Checkpoint:

- Pendiente baja.
- Verificado sube.
- La fila ya no muestra boton Verificar.

### Paso 7.3: estados no validos

Probar:

- OC borrador.
- OC cancelada.
- OC ya verificada.
- OC fuera de GL.

Checkpoint:

- Ninguna permite verificacion incorrecta.

### Paso 7.4: semana y reposicion

Probar semana:

```text
03/08/2026 al 09/08/2026
```

Resultado esperado:

```text
Reposicion sugerida: 11/08/2026
```

Checkpoint:

- La fecha coincide con martes de la semana siguiente.

## Fase 8: decision posterior

Cuando esta primera version funcione, decidir si conviene crear modulo formal:

```text
Reposiciones de caja chica
```

Ese modulo podria tener:

- Semana.
- Encargado.
- Total verificado.
- Fecha de reposicion.
- Estado de reposicion.
- Usuario que programa.
- Usuario que paga.
- OCs incluidas.

Checkpoint:

- Solo avanzar a este modulo si el flujo semanal ya esta validado por operacion.

## Orden recomendado de implementacion

1. Agregar estado `VERIFICADA`.
2. Agregar permiso de verificacion.
3. Agregar accion backend para verificar.
4. Mostrar boton dinamico en vista GL.
5. Agregar acumulado semanal.
6. Agregar fecha sugerida de reposicion.
7. Ajustar texto de accion `Pagar` en GL.
8. Revisar exportes.
9. Probar con usuario verificador y usuario normal.

## Checkpoint final

El cambio se considera listo cuando:

- Las OC normales siguen funcionando igual.
- Las OC GL se pueden revisar por semana.
- El verificador puede marcar OC autorizadas como verificadas.
- El acumulado semanal muestra el gasto de almacen.
- La fecha de reposicion sugerida es correcta.
- Pagos-proveedores no recibe cajas chicas individuales por accidente.
- La operacion puede cerrar la semana e identificar cuanto se debe reponer al encargado.

## Checkpoint ejecutado: backend de verificacion

Cambios aplicados:

- Se agrego el estado normalizado `verificada` para `VERIFICADA`.
- Se agrego la ruta backend:
  - `POST /ordenes_compra/{id}/verificar`
  - nombre: `ordenes_compra.verificar`
- Se agrego el permiso:
  - `ordenes_compra.verify.access`
- Se agregaron campos de trazabilidad en migracion:
  - `usuario_verifica`
  - `verificado_por`
  - `fecha_verificacion`
- Se agrego la accion `verificar` en `OrdenCompraController`.

Validaciones de la accion:

- Requiere permiso `ordenes_compra.verify.access`.
- Solo permite verificar OC autorizadas.
- No permite verificar OC canceladas.
- Si ya esta verificada, responde sin duplicar la accion.
- Solo aplica para area con codigo `GL`.
- Registra usuario, usuario_id y fecha/hora de verificacion.

Validacion tecnica:

- `php -l app/Models/OrdenCompra.php`: OK.
- `php -l app/Http/Controllers/OrdenCompraController.php`: OK.
- `php -l routes/web.php`: OK.
- `php -l database/migrations/2026_08_10_090000_add_verification_fields_to_ordenes_compra.php`: OK.
---

# Plan de accion: descuentos por partida en ordenes de compra

## Objetivo

Permitir que cada renglon/producto de una orden de compra tenga un descuento independiente en porcentaje.

El descuento debe afectar la base de la linea antes de IVA, retenciones, otros impuestos y total.

Formula objetivo:

```text
bruto = cantidad * precio_unitario
descuento_importe = bruto * (descuento_porcentaje / 100)
importe = bruto - descuento_importe
iva_monto = importe * (iva / 100)
total_linea = importe + iva_monto + otros_impuestos - retenciones
```

## Principios

- El usuario captura el descuento como porcentaje.
- El sistema guarda tambien el importe descontado para auditoria.
- Si no hay descuento, el comportamiento actual debe quedar igual.
- El descuento se aplica antes del IVA.
- El descuento debe reflejarse igual en pantalla, totales, PDF, exportes y autorizacion.
- No se debe permitir descuento negativo.
- El descuento maximo recomendado es 100%.

## Fase 1: base de datos y modelo

### Paso 1.1: crear migracion

Agregar columnas a `orden_compra_detalles`:

```text
descuento_porcentaje decimal(5,2) default 0
descuento_importe decimal(12,2) default 0
```

Checkpoint:

- Migracion creada.
- Migracion reversible.
- Valores default en cero para no afectar detalles existentes.

### Paso 1.2: actualizar modelo `OrdenCompraDetalle`

Agregar a `fillable`:

```text
descuento_porcentaje
descuento_importe
```

Agregar casts:

```text
descuento_porcentaje => decimal:2
descuento_importe => decimal:2
```

Checkpoint:

- Modelo acepta y castea los nuevos campos.
- Detalles existentes siguen funcionando con cero descuento.

## Fase 2: validacion de requests

### Paso 2.1: actualizar `StoreOrdenCompraDetalleRequest`

Agregar regla:

```text
descuento_porcentaje nullable numeric min:0 max:100
```

Checkpoint:

- Se puede crear detalle sin descuento.
- Se puede crear detalle con descuento entre 0 y 100.
- No acepta descuento negativo.
- No acepta descuento mayor a 100.

### Paso 2.2: actualizar `UpdateOrdenCompraDetalleRequest`

Agregar la misma regla:

```text
descuento_porcentaje nullable numeric min:0 max:100
```

Checkpoint:

- Si en el futuro se edita detalle, mantiene la misma regla.

## Fase 3: calculo al guardar detalles

### Paso 3.1: ajustar `OrdenCompraDetalleController@store`

Cambiar calculo actual:

```text
importe = cantidad * precio_unitario
```

por:

```text
bruto = cantidad * precio_unitario
descuento_porcentaje = request descuento o 0
descuento_importe = bruto * descuento_porcentaje / 100
importe = bruto - descuento_importe
```

Guardar:

```text
descuento_porcentaje
descuento_importe
importe
```

Checkpoint:

- Con 0% descuento, `importe` queda igual que hoy.
- Con 10% descuento, `importe` baja correctamente.
- Retenciones se calculan sobre el importe ya descontado.

### Paso 3.2: ajustar `OrdenCompraDetalleController@update`

Aplicar el mismo calculo en actualizacion de detalles.

Checkpoint:

- Store y update calculan igual.
- No hay doble descuento.

### Paso 3.3: revisar sincronizacion de precio proveedor

Actualmente se guarda `precio_unitario` como precio historico del proveedor.

Decision recomendada:

```text
producto_proveedor.precio_lista = precio_unitario antes de descuento
```

Motivo:

- El descuento es una condicion comercial de esa OC.
- El precio unitario del proveedor no debe contaminarse con precio neto descontado.

Checkpoint:

- No cambiar sincronizacion de precio proveedor.
- Guardar descuento solo en detalle de OC.

## Fase 4: recalculo de totales

### Paso 4.1: revisar `OrdenCompraTotalesService`

Hoy suma:

```text
SUM(importe)
SUM(importe * iva/100)
```

Si `importe` ya queda neto de descuento, no requiere cambiar la formula.

Checkpoint:

- Confirmar que `importe` representa base neta despues de descuento.
- Totales de cabecera se recalculan correctamente.

### Paso 4.2: revisar calculos manuales en `OrdenCompraController@index`

Actualmente hay calculos manuales con:

```text
precio_unitario * cantidad
```

Deben cambiar a:

```text
detalle->importe ?? precio_unitario * cantidad
```

Checkpoint:

- Listado muestra total correcto con descuentos.
- Resumen semanal caja chica usa importes descontados.

### Paso 4.3: revisar `OrdenCompraController@edit`

Actualmente calcula subtotal visual con:

```text
precio_unitario * cantidad
```

Debe usar:

```text
bruto = precio_unitario * cantidad
descuento_importe = detalle->descuento_importe
subtotal = detalle->importe
```

Checkpoint:

- El subtotal de cada renglon muestra base neta.
- El total de la OC coincide con el guardado.
- Si se quiere mostrar bruto y descuento, ambos se ven claramente.

## Fase 5: interfaz de captura en edit

### Paso 5.1: agregar input de descuento al formulario de detalle

Agregar campo:

```text
name="descuento_porcentaje"
type="number"
step="0.01"
min="0"
max="100"
placeholder="0"
```

Etiqueta visible:

```text
Desc. %
```

Checkpoint:

- El campo cabe en la fila de captura.
- Cantidad y precio unitario quedan mas compactos.
- El usuario puede dejarlo vacio o en cero.

### Paso 5.2: ajustar layout de captura

Actualmente el formulario usa varias columnas.

Propuesta:

```text
Descripcion: ancho principal
Cantidad: compacto
P. Unit: compacto
Desc %: compacto
IVA: compacto
Retencion: compacto
Agregar: compacto
```

Checkpoint:

- No se enciman campos.
- En desktop se ve todo en una fila usable.
- En pantallas chicas puede bajar de linea sin romperse.

### Paso 5.3: mostrar descuento en tabla de detalles

Agregar columna:

```text
Desc.
```

Mostrar:

```text
10.00%
-$125.00
```

Si no hay descuento:

```text
-
```

Checkpoint:

- Las filas antiguas muestran `-`.
- Las filas con descuento muestran porcentaje e importe.

## Fase 6: PDF de orden de compra

### Paso 6.1: decidir columnas del PDF

Opcion recomendada:

```text
CANT | UNIDAD | DESCRIPCION | P. UNIT. | DESC. | IVA | RET. | IMPORTE
```

Si no hay descuentos en ninguna partida, se puede ocultar la columna `DESC.` para conservar espacio.

Checkpoint:

- PDF no se desborda horizontalmente.
- Cuando no hay descuentos, PDF queda casi igual que hoy.
- Cuando hay descuentos, el proveedor/verificador entiende el neto.

### Paso 6.2: ajustar calculos del PDF

El PDF debe usar:

```text
subtotalLinea = detalle->importe
ivaLinea = subtotalLinea * iva%
importeLinea = subtotalLinea + ivaLinea + otros - retenciones
```

Y mostrar descuento si aplica:

```text
-$descuento_importe
```

o:

```text
10.00%
-$descuento_importe
```

Checkpoint:

- El subtotal del PDF coincide con la cabecera.
- El IVA se calcula sobre base descontada.
- El total final coincide con la OC.

## Fase 7: exportes y resumen semanal

### Paso 7.1: revisar exportes de GL/caja chica

Los exportes deben usar el total guardado/calculado con `importe` neto.

Checkpoint:

- Export efectivo respeta descuentos.
- Export TC respeta descuentos.
- No hay diferencia contra PDF.

### Paso 7.2: revisar resumen semanal caja chica

El badge semanal debe sumar usando:

```text
detalle->importe
```

no:

```text
precio_unitario * cantidad
```

Checkpoint:

- Acumulado semanal respeta descuentos.
- Pendiente/verificado respeta descuentos.

## Fase 8: autorizacion y presupuesto

### Paso 8.1: revisar validacion de presupuesto al autorizar

La autorizacion compara contra:

```text
oc->total
```

Si `oc->total` ya fue recalculado con descuento, no requiere cambio.

Checkpoint:

- Una OC con descuento reduce el total contra presupuesto.
- No se autoriza con total bruto por error.

### Paso 8.2: bloquear modificaciones post autorizacion/verificacion

Regla existente:

```text
autorizada/verificada/cancelada no se modifica
```

Checkpoint:

- No se puede agregar descuento despues de autorizar.
- Si hay error, se cancela y se crea otra OC.

## Fase 9: pruebas manuales

### Paso 9.1: detalle sin descuento

Crear detalle:

```text
cantidad = 10
precio = 100
descuento = 0
iva = 16
```

Esperado:

```text
subtotal = 1000
iva = 160
total = 1160
```

Checkpoint:

- Resultado igual que flujo actual.

### Paso 9.2: detalle con descuento

Crear detalle:

```text
cantidad = 10
precio = 100
descuento = 10
iva = 16
```

Esperado:

```text
bruto = 1000
descuento = 100
subtotal neto = 900
iva = 144
total = 1044
```

Checkpoint:

- Vista edit muestra descuento.
- Cabecera muestra total 1044.
- PDF muestra total 1044.

### Paso 9.3: mezcla de productos

Crear OC con:

```text
producto A sin descuento
producto B con 5%
producto C con 20%
```

Checkpoint:

- Cada renglon calcula independiente.
- Total general coincide con suma de lineas.

### Paso 9.4: caja chica con descuento

Crear OC GL caja chica con descuento.

Checkpoint:

- Acumulado semanal respeta descuento.
- Verificacion sigue funcionando.

## Orden recomendado de implementacion

1. Migracion de campos descuento.
2. Modelo y requests.
3. Calculo store/update de detalle.
4. Servicio y calculos manuales de listado/edit/resumen.
5. UI de captura y tabla detalle.
6. PDF.
7. Exportes/resumen semanal.
8. Pruebas manuales.

## Checkpoint final

La implementacion queda lista cuando:

- Una partida puede tener descuento porcentual.
- El importe guardado es neto de descuento.
- IVA se calcula sobre el importe neto.
- Totales de OC coinciden en vista, PDF y exportes.
- El flujo sin descuento se comporta igual que antes.
- Caja chica y proveedores normales respetan los mismos calculos.