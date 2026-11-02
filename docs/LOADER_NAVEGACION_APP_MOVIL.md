# Loader corporativo al cambiar de vista

Fecha: 2026-11-02. Implementado localmente; prueba funcional en dispositivo pendiente.

## Alcance

El cambio entre pantallas y pestañas de la app utiliza el loader corporativo con
logo Rivera existente, mediante `MecanicoLoadingService`. Se inicia desde el
componente raíz, por lo que cubre navegación de la aplicación, no únicamente el
panel mecánico. No modifica rutas, guards, permisos ni payloads.

## Coordinación

- `NavigationStart` abre una referencia de carga. Navegación cancelada, fallida o
  saltada libera la referencia correspondiente sin cerrar overlays ajenos.
- Los eventos Ionic `ionViewWillEnter` y `ionViewDidEnter` coordinan las transiciones
  de los outlets, incluyendo pestañas y páginas conservadas por Ionic.
- Los eventos se observan en captura porque Ionic los emite sin propagación.
  Se ignoran visores en modales y popovers: abrir una foto no es cambiar de ruta.
- El interceptor de navegación registra únicamente GET JSON de la API configurada
  mientras hay una navegación activa. No añade tokens de autorización, no cambia
  solicitudes y no descarga archivos adicionales.
- La carga espera el final de navegación, las vistas que estén entrando y las
  consultas capturadas. Éxito, error y cancelación de suscripciones liberan cada
  consulta mediante `finalize`.
- Dos cuadros de render permiten montar la vista e iniciar sus consultas antes
  del cierre. No se agrega una espera de duración fija para prolongar el logo.
- Una navegación nueva libera la anterior. Respuestas viejas no cierran la carga
  de una navegación más reciente. Al destruir el servicio se retiran los listeners.
- Si una transición Ionic comienza después de finalizar Angular, su evento de
  entrada también puede iniciar la referencia de carga y esperar sus consultas.
- El contador del servicio corporativo evita loaders duplicados cuando coincide
  navegación con guardados o actualizaciones manuales.

Las búsquedas, filtros y paginación fuera de una navegación conservan el alcance
anterior: sus GET no activan por sí solos el loader de navegación. El pull to
refresh y los botones Actualizar siguen usando la integración explícita existente.

## Archivos móviles

En `C:/xampp/htdocs/rivera/app/src/`:

- `app/services/navegacion-loading.service.ts`.
- `app/interceptors/navegacion-loading.interceptor.ts`.
- `app/app.component.ts`: inicialización global.
- `main.ts`: registro del interceptor adicional, conservando el de movimientos y
  `withInterceptorsFromDi`.

## Checkpoints

- [x] Compilación Angular development y revisión de mojibake.
- [x] Coordinación de navegación, transiciones y consultas sin cambiar datos.
- [ ] Prueba del usuario: cambiar pestañas, entrar al detalle, volver al listado,
  acceder a páginas conservadas, cancelaciones de guards y errores de red.
- [ ] Confirmar en dispositivo que el logo se retira al terminar y que abrir/cerrar
  fotos no inicia otra carga de navegación.

Sin pruebas funcionales automáticas, migraciones, cambios operativos ni despliegue.
