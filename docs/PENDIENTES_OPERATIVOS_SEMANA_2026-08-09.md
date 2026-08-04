# Pendientes operativos: semana del 9 de agosto de 2026

**Fecha de registro:** 04 de agosto de 2026  
**Semana objetivo:** semana del 9 de agosto de 2026  
**Estado:** Pendiente de revision y definicion tecnica  
**Relacionado con:** calendario operacional, permisos por categoria, obras y avance de perforacion.

## Regla de trabajo

Antes de implementar estos pendientes se debe revisar el codigo existente con el grafo y con los Markdown del proyecto. No se debe construir una solucion nueva si ya existe un patron reutilizable.

Fuentes revisadas para este registro:

- `graphify query "calendario operativo permisos checks obras vehiculos maquinaria seguros ordenes compra RH modal fin real obra avance perforado pilas"`
- `docs/ROADMAP_CALENDARIO_OPERACIONAL.md`
- `docs/ROADMAP_PENDIENTES_OPERATIVOS.md`

Fuentes a revisar antes de tocar codigo:

- Servicio actual del calendario operacional.
- Controlador y rutas del calendario.
- Vistas Blade del calendario y de sus modales.
- Middleware/permisos existentes.
- Modelos relacionados: `Obra`, `ObraPila`, `Vehiculo`, `Maquina`, `Seguro`, `OrdenCompra`, `Empleado`.
- Migraciones o seeders de permisos existentes.

## Pendiente 1: permisos individuales por categoria del calendario

### Contexto

El calendario operacional actual muestra diferentes checks/categorias:

- Obras.
- Vehiculos.
- Maquinaria.
- Seguros.
- Ordenes de compra.
- RH.

No todos los empleados deben poder ver toda la informacion. Por ejemplo, un usuario puede necesitar ver vencimientos de seguros, pero no RH; o puede ver obras, pero no ordenes de compra.

### Objetivo

Crear permisos granulares para cada categoria del calendario, de forma que la visibilidad de eventos sea controlada por permisos y no solo por la interfaz.

### Permisos propuestos

- `calendario.access`: permite entrar al calendario.
- `calendario.obras.view`: permite ver eventos de obras.
- `calendario.vehiculos.view`: permite ver eventos de vehiculos.
- `calendario.maquinaria.view`: permite ver eventos de maquinaria.
- `calendario.seguros.view`: permite ver eventos de seguros.
- `calendario.ordenes_compra.view`: permite ver eventos de ordenes de compra.
- `calendario.rh.view`: permite ver eventos de RH.

### Regla importante

La restriccion debe vivir en dos niveles:

- Frontend: ocultar o deshabilitar checks sin permiso.
- Backend: no devolver eventos de categorias sin permiso aunque el usuario manipule la URL o el request.

No basta con esconder el check en pantalla.

### Checkpoints tecnicos

- [ ] Revisar como se consultan eventos en el calendario actual.
- [ ] Revisar si los checks se mandan por query string, request JSON o estado Alpine/JS.
- [ ] Agregar permisos al seeder/migracion siguiendo el patron existente.
- [ ] Definir roles iniciales que tendran cada permiso.
- [ ] Ajustar el servicio/controlador para filtrar categorias por permisos.
- [ ] Ajustar UI para mostrar solo checks permitidos.
- [ ] Validar acceso directo a eventos sin permiso.
- [ ] Validar que el calendario no quede vacio sin explicar si el usuario no tiene categorias asignadas.

### Definicion de terminado

- Un usuario solo ve checks permitidos.
- El backend solo entrega eventos permitidos.
- Los roles quedan documentados.
- Se valida con al menos dos usuarios con permisos diferentes.

## Pendiente 2: avance de perforacion en modal de fin real de obra

### Contexto

Existe un modal que se levanta cuando esta registrado el fin real de una obra. En ese modal se muestran datos como:

- Fecha.
- Responsable.
- Clave.
- Inicio real.
- Fin real.
- Monto contratado.
- Avance de cobro.
- Facturado.
- Facturado sin pago registrado.
- Borradores.

Se necesita agregar ahi el avance de lo perforado, especialmente cuantas pilas llevan, para poder revisar si la obra va en tiempo o no.

### Objetivo

Agregar al modal de `Obras / Fin real` un resumen operativo de avance de perforacion.

### Informacion deseada

- Pilas contratadas o esperadas, si el dato existe.
- Pilas perforadas / registradas.
- Porcentaje de avance fisico.
- Promedio diario de pilas, si se puede calcular con fechas reales.
- Proyeccion de cierre con base en ritmo actual.
- Indicador simple: en tiempo, en riesgo o atrasado.

### Puntos a revisar antes de implementar

- Si `ObraPila` ya representa las pilas registradas.
- Donde se guarda el total contratado o esperado de pilas.
- Si las pilas tienen fecha de perforacion o avance.
- Si el calendario ya tiene acceso a esos datos desde `CalendarioOperacionalService`.
- Si el modal recibe toda la informacion en el evento o hace una consulta posterior.

### Propuesta inicial de calculo

Si existen total esperado y avance registrado:

- `avance_fisico = pilas_perforadas / pilas_totales * 100`

Si existen fechas reales:

- `dias_transcurridos = fecha_actual - inicio_real`
- `dias_totales = fin_real - inicio_real`
- `avance_esperado = dias_transcurridos / dias_totales * 100`

Comparacion:

- En tiempo: avance fisico igual o mayor al avance esperado.
- En riesgo: avance fisico menor al esperado por un margen moderado.
- Atrasado: avance fisico muy por debajo del esperado o fin real alcanzado sin completar pilas.

Los umbrales deben definirse antes de cerrar la implementacion.

### Checkpoints tecnicos

- [ ] Revisar modelo `ObraPila`.
- [ ] Revisar relaciones disponibles desde `Obra`.
- [ ] Revisar evento actual de calendario para fin real de obra.
- [ ] Revisar Blade/modal actual del calendario.
- [ ] Definir campos exactos disponibles para pilas.
- [ ] Agregar datos al payload del evento o endpoint de detalle.
- [ ] Actualizar modal con una seccion compacta de avance fisico.
- [ ] Validar obra con pilas y obra sin pilas.
- [ ] Evitar romper eventos de obras que no sean de perforacion.

### Definicion de terminado

- El modal de fin real muestra avance de perforacion cuando hay datos.
- Si no hay datos de pilas, el modal muestra un estado claro y no truena.
- La proyeccion ayuda a saber si la obra va en tiempo.
- El calculo queda documentado.

## Roadmap de la semana

### Paso 1 - Descubrimiento

- Consultar grafo para calendario, permisos y `ObraPila`.
- Revisar Markdown existentes del calendario.
- Identificar archivos exactos a modificar.

### Paso 2 - Diseno de permisos

- Definir nombres finales de permisos.
- Definir roles iniciales.
- Definir comportamiento cuando un usuario no tiene ninguna categoria.

### Paso 3 - Diseno del modal de obra

- Identificar de donde saldran las pilas.
- Definir calculos.
- Definir si el dato viaja en el evento o se carga al abrir modal.

### Paso 4 - Implementacion controlada

- Implementar permisos por categoria.
- Implementar filtro backend.
- Implementar UI de checks por permiso.
- Implementar avance perforado en modal.

### Paso 5 - Validacion

- Validar con usuarios de diferentes roles.
- Validar calendario con todas las categorias.
- Validar calendario con categorias limitadas.
- Validar modal de fin real con obra de perforacion.
- Validar modal de fin real con obra sin pilas.

## Comandos sugeridos de revision

```bash
graphify query "CalendarioOperacionalService categorias permisos eventos obras fin real ObraPila" --budget 2000
php artisan route:list | findstr calendario
php artisan view:cache
php -l app/Services/Calendario/CalendarioOperacionalService.php
graphify update .
```

## Notas

- Este pendiente queda separado del modulo GIRALDA.
- Es un pendiente operativo de calendario para revisar durante la semana del 9 de agosto de 2026.
- La implementacion no debe iniciar hasta revisar el flujo actual con grafo y documentacion.

