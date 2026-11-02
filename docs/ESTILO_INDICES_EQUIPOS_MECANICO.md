# Estilo compacto de índices de equipos

Fecha: 2026-11-02. Ajuste local basado en la referencia visual del usuario.

## Pantallas

- `/tabs-mecanico/maquinas`.
- `/tabs-mecanico/vehiculos`.

Cabecera azul con título, buscador y contador de resultados. Se mantienen los
botones de actualización, acceso entre paneles cuando aplica y salida.

Tarjetas blancas redondeadas con indicador circular de estado, identificación
principal, subtítulo y badge de estado. Máquinas muestran código y datos de
nombre/modelo; vehículos muestran placas y marca/modelo/año. Indicadores verdes
para estados operativos/activos, naranja para reparación/mantenimiento, rojos
para fuera de servicio/baja y gris para estados no clasificados.

Las tarjetas conservan la flecha y su capacidad de plegarse; inician cerradas.
Al abrirlas se conserva el contenido y las acciones anteriores, incluyendo
«Ver detalle», programación y operaciones autorizadas de máquinas.

## Alcance técnico

- Se reutilizan los componentes de tarjeta existentes con una hoja compartida
  `mecanico-equipo-listado.scss` para el modo compacto.
- En máquinas el diseño compacto se aplica solo al listado (`detalle = false`).
  La ficha de máquina individual mantiene su cabecera azul anterior.
- `mecanico-consulta.page.*` coloca búsqueda y contador en la cabecera únicamente
  para los índices de máquinas/vehículos; la pantalla de servicios se conserva.
- Sin cambios en API, permisos, búsqueda, paginación, loaders o datos operativos.
- Esta revisión sustituye visualmente las franjas azules de cada tarjeta del
  índice anterior: el azul pasa a la cabecera de la pantalla, conforme a la referencia.

## Verificaciones

Compilación Angular development, compilación Sass del ajuste final y revisión
de mojibake correctas. Pendiente del usuario: revisión visual en dispositivo,
expandir tarjetas y navegar a los detalles de ambos tipos de equipo.
