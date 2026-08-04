# Roadmap: calendario operacional

Fecha: 2026-07-28

## Objetivo

Crear un calendario operacional simple y util para visualizar fechas clave del negocio con filtros por checks.

El primer alcance queda limitado a:

1. RH: cumpleanos de empleados.
2. Vehiculos: servicios programados.
3. Maquinaria: servicios programados.
4. Ordenes de compra: fecha y autorizacion.
5. Seguros: vigencia y vencimientos.
6. Obras: inicio/fin programado y real.

Quedan fuera del primer corte:

- Programacion de pagos.
- Nomina.
- Inventario.
- Facturacion/SAT.
- Documentos generales.
- Logs de kilometraje/gasolina.

## Principio De Diseno

El calendario no debe duplicar captura ni crear informacion paralela.

Debe funcionar como una capa de consulta:

- Lee fechas desde tablas existentes.
- Convierte cada fecha en un evento normalizado.
- Permite activar/desactivar categorias con checks.
- Permite navegar desde el evento al registro original.

## Fuentes Del Primer Corte

### 1. RH: cumpleanos

Fuente:

- `empleados`

Campo relevante:

- `Fecha_nacimiento`

Eventos:

- Cumpleanos de empleado.

Notas:

- El evento debe repetirse cada ano usando dia/mes de `Fecha_nacimiento`.
- No mostrar edad si no se quiere exponer informacion sensible; opcionalmente mostrar solo nombre y puesto.

Filtro/check:

- RH / Cumpleanos.

Prioridad: media.

### 2. Vehiculos: servicios programados

Fuente:

- `mantenimientos`

Condicion:

- `vehiculo_id` no null.

Campos relevantes:

- `fecha_programada`
- futuro: `fecha_confirmada`

Eventos:

- Servicio programado de vehiculo.
- Servicio confirmado de vehiculo, cuando exista el nuevo flujo.

Filtros/checks:

- Vehiculos.
- Servicios programados.
- Preventivo.
- Correctivo.
- Emergencia.

URL origen sugerida:

- Edicion/detalle del mantenimiento.
- O tab de mantenimientos del vehiculo.

Prioridad: alta.

### 3. Maquinaria: servicios programados

Fuente:

- `mantenimientos`

Condicion:

- `maquina_id` no null.

Campos relevantes:

- `fecha_programada`
- futuro: `fecha_confirmada`

Eventos:

- Servicio programado de maquina.
- Servicio confirmado de maquina, cuando exista el nuevo flujo.

Filtros/checks:

- Maquinaria.
- Servicios programados.
- Preventivo.
- Correctivo.
- Emergencia.

URL origen sugerida:

- Detalle del mantenimiento.
- O tab servicios de la maquina.

Prioridad: alta.

### 4. Ordenes de compra: fecha y autorizacion

Fuente:

- `ordenes_compra`

Campos relevantes:

- `fecha`
- `fecha_autorizacion`

Eventos:

- Fecha de orden de compra.
- Orden de compra autorizada.

Filtros/checks:

- Ordenes de compra.
- Fecha OC.
- Autorizaciones.
- Por proveedor.
- Por estatus.

URL origen sugerida:

- Detalle/edicion de la orden de compra.

Prioridad: media.

### 5. Seguros: vigencia y vencimientos

Fuentes principales:

- `seguros`

Fuentes legacy si siguen en uso:

- `seguros_vehiculos`
- `seguros_maquinas`

Campos relevantes:

- `seguros.vigencia_desde`
- `seguros.vigencia_hasta`
- `seguros.fecha_compra`
- `seguros_vehiculos.fecha_inicio`
- `seguros_vehiculos.fecha_fin`
- `seguros_maquinas.vigencia_inicio`
- `seguros_maquinas.vigencia_fin`

Eventos:

- Inicio de vigencia.
- Vencimiento de seguro.
- Compra de seguro, opcional.

Filtros/checks:

- Seguros.
- Inicio de vigencia.
- Vencimientos.
- Vehiculos.
- Maquinas.
- Vigente.
- Vencido.
- Por vencer.

URL origen sugerida:

- Seguro del vehiculo/maquina.

Prioridad: alta.

### 6. Obras: inicio/fin programado y real

Fuente:

- `obras`

Campos relevantes:

- `fecha_inicio_programada`
- `fecha_inicio_real`
- `fecha_fin_programada`
- `fecha_fin_real`

Eventos:

- Inicio programado de obra.
- Inicio real de obra.
- Fin programado de obra.
- Fin real de obra.

Filtros/checks:

- Obras.
- Inicio programado.
- Inicio real.
- Fin programado.
- Fin real.
- Por cliente.
- Por responsable.
- Por estatus.

URL origen sugerida:

- Edicion/detalle de obra.

Prioridad: alta.

## Modelo Normalizado De Evento

Cada fuente debe transformarse a una estructura comun:

```php
[
    'id' => 'obras:5:fecha_inicio_programada',
    'source' => 'obras',
    'source_id' => 5,
    'category' => 'obras',
    'type' => 'inicio_programado',
    'title' => 'Inicio programado: OBRA X',
    'starts_at' => '2026-08-01',
    'ends_at' => null,
    'all_day' => true,
    'status' => 'en_ejecucion',
    'color' => '#2563eb',
    'url' => route('obras.edit', 5),
    'meta' => [
        'cliente' => 'Cliente X',
        'responsable' => 'Usuario X',
    ],
]
```

## Checks Iniciales De La UI

### Categorias principales

- Obras
- Vehiculos
- Maquinaria
- Seguros
- Ordenes de compra
- RH

### Subchecks sugeridos

Obras:

- Inicio programado
- Inicio real
- Fin programado
- Fin real

Vehiculos:

- Servicios programados
- Servicios confirmados, fase posterior

Maquinaria:

- Servicios programados
- Servicios confirmados, fase posterior

Seguros:

- Inicio de vigencia
- Vencimientos
- Por vencer
- Vencidos

Ordenes de compra:

- Fecha OC
- Autorizaciones

RH:

- Cumpleanos

## Checkpoints De Implementacion

### Checkpoint 1: Servicio agregador

Crear:

- `App/Services/Calendario/CalendarioOperacionalService.php`

Responsabilidad:

- Recibir rango `desde` / `hasta`.
- Recibir checks/filtros activos.
- Consultar solo las fuentes activas.
- Devolver eventos normalizados.

Fuentes iniciales:

- Obras.
- Mantenimientos de vehiculos.
- Mantenimientos de maquinaria.
- Seguros.
- Ordenes de compra.
- Cumpleanos de empleados.

Criterios de listo:

- Devuelve eventos en un arreglo normalizado.
- Filtra por rango de fechas.
- No consulta categorias apagadas.
- Cada evento trae `title`, `starts_at`, `category`, `type`, `color`, `url`.

### Checkpoint 2: Endpoint JSON

Crear endpoint:

- `GET /calendario-operacional/events`

Parametros:

- `start`
- `end`
- `categories[]`
- `types[]`
- `obra_id`
- `cliente_id`
- `responsable_id`
- `vehiculo_id`
- `maquina_id`
- `proveedor_id`

Criterios de listo:

- Responde JSON listo para la vista.
- Respeta rango.
- Respeta checks.
- Incluye URL para abrir el origen.

### Checkpoint 3: Vista calendario

Crear vista:

- `GET /calendario-operacional`

UI sugerida:

- Calendario mensual como vista principal.
- Vista lista como alternativa.
- Panel lateral con checks.
- Leyenda de colores.
- Click en evento abre el origen.

Criterios de listo:

- El usuario activa/desactiva categorias.
- El calendario se refresca sin recargar toda la pagina.
- Eventos de distintas categorias se distinguen por color/icono.

### Checkpoint 4: Filtros avanzados

Agregar filtros por entidad:

- Obra.
- Cliente.
- Responsable.
- Vehiculo.
- Maquina.
- Proveedor.

Criterios de listo:

- Se puede ver calendario general.
- Se puede enfocar por una obra, maquina, vehiculo o proveedor.
- Los filtros viajan en query string para compartir URLs.

### Checkpoint 5: Permisos

Definir permiso base:

- `calendario_operacional.access`

Opcional por categoria:

- `calendario_operacional.obras.access`
- `calendario_operacional.vehiculos.access`
- `calendario_operacional.maquinaria.access`
- `calendario_operacional.seguros.access`
- `calendario_operacional.ordenes_compra.access`
- `calendario_operacional.rh.access`

Criterios de listo:

- Un usuario sin permiso no ve el calendario.
- Un usuario solo ve categorias permitidas.

## Consideraciones Tecnicas

- No crear tabla fisica de eventos al inicio.
- Calcular eventos desde las tablas origen.
- Usar rango obligatorio para evitar consultas muy grandes.
- Cumpleanos se calculan por dia/mes dentro del rango consultado.
- Fechas `date` se muestran como eventos de dia completo.
- Fechas `datetime` pueden mostrar hora si existe.
- Cada evento debe tener URL al registro original.
- Colores deben ser consistentes por categoria.

## Preguntas Pendientes

1. El calendario sera mensual solamente o tambien semanal/lista desde el primer corte?
2. RH cumpleanos debe mostrar edad o solo nombre?
3. Ordenes de compra deben mostrar monto o solo folio/proveedor?
4. Seguros vencidos deben verse aunque esten fuera del rango si siguen vencidos, o solo por fecha de vencimiento?
5. Los servicios confirmados de mantenimiento entran ahora o hasta que cerremos el nuevo flujo de mantenimiento?

## Primer Paso Recomendado

Implementar el Checkpoint 1 con el agregador de eventos.

Orden sugerido de fuentes:

1. Obras.
2. Mantenimientos vehiculos/maquinaria.
3. Seguros.
4. Ordenes de compra.
5. Cumpleanos RH.

Despues crear el endpoint JSON y finalmente la vista con checks.
