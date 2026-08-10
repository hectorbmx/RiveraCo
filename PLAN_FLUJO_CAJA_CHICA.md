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