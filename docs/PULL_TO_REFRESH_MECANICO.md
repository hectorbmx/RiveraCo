# Pull to refresh en consultas del mecánico

Fecha: 2026-11-02. Implementación local; prueba del gesto en dispositivo pendiente.

## Alcance

Se agrega `ion-refresher` con «Desliza para actualizar», «Actualizando…» y spinner
`crescent`, siguiendo el patrón de Ionic ya utilizado en la app.

- Listados de máquinas, vehículos y servicios del panel mecánico.
- Detalle de máquinas y servicios.
- Catálogo informativo de productos de Giralda.
- Detalle compartido de vehículos, tanto en mecánico como en gerencial.

Los formularios de creación/edición de servicios y los modales de captura no
incorporan este gesto para evitar recargar datos mientras el usuario escribe.
Este ajuste no agrega el refresher a todas las pantallas del panel gerencial.

## Comportamiento

- Listados: reutiliza la consulta actual, mantiene filtros, búsqueda y página.
- Detalle de vehículos: actualiza la ficha sin restablecer el desplegable inicial;
  recarga los historiales abiertos con sus filtros y páginas. Los cerrados se
  invalidan y se consultan nuevamente al abrirlos, manteniendo su contexto.
- El refresher del vehículo espera la ficha y las consultas de los historiales
  abiertos. Las cancelaciones también resuelven la espera del historial.
- La secuencia de refresco evita aplicar una respuesta anterior después de salir
  o ejecutar una carga nueva.
- El indicador se completa al terminar, incluso si hay error o se rechaza un
  segundo intento mientras ya se está consultando.
- El gesto y los botones de actualización muestran el loader corporativo con
  logo existente. En vehículos esperan la ficha y los historiales abiertos. El
  overlay se finaliza también al salir de la pantalla; los filtros y la paginación
  mantienen sus indicadores ordinarios.
- En servicios se deshabilita mientras hay cambios de materiales, fotos elegidas,
  formulario de ejecución abierto, captura de cámara, operación en curso o envío
  incierto. También se bloquea al abrir una operación de máquina.
- No se hacen guardados ni movimientos de inventario al deslizar. Los errores de
  consulta siguen usando los mensajes existentes de cada pantalla.

## Archivos

En `C:/xampp/htdocs/rivera/app/src/app/`:

- `mecanico/mecanico-consulta.page.ts` y `.html`.
- `mecanico/mecanico-productos.page.ts` (plantilla inline).
- `pages/gerencial-vehiculos/vehiculo-detalles/vehiculo-detalles.page.ts` y `.html`.

## Checkpoints

- [x] Gesto conectado a consultas y finalización del indicador.
- [x] Filtros y páginas conservados; protección de capturas pendientes.
- [x] Compilación Angular development y revisión de mojibake.
- [ ] Prueba funcional del usuario en dispositivo: gesto, errores de red,
  historiales abiertos y bloqueo durante capturas.

No se modificaron endpoints, permisos ni registros operativos. No se ejecutaron
pruebas funcionales automáticas; la comprobación del flujo corresponde al usuario.
