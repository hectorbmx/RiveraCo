# Roadmap: Pendientes operativos

## Pendientes registrados

Fecha de registro: 2026-06-30.

### Equipos de computo

- Crear motor de folios para equipos de computo.
- Formato inicial sugerido: `LAP-001`.
- Ultimo folio operativo reportado: `LAP-009`.
- Crear controlador de folios en configuracion de empresa.

### CFDI SAT descargados

Vista:

- `http://127.0.0.1:8000/sat/cfdis?sat_empresa_id=5`

Pendientes:

- Agregar exportador Excel de documentos filtrados.
- Agregar selector de elementos a mostrar al inicio de la tabla.

### Usuarios y roles

Vista:

- `http://127.0.0.1:8000/usuarios/{usuario}/edit`

Pendientes:

- Agregar selector de rol en la edicion de usuarios.
- Permitir que `super-admin` pueda cambiar el rol de un usuario.
- Crear permiso granular para esta accion, siguiendo el patron `.access`.
- Proteger el guardado del rol con el nuevo permiso.
## Pendientes para ejecutar manana

Fecha de registro: 2026-07-09.

### Notificaciones del agente instalable

- [ ] Agregar notificacion in-app y agente cuando alguien timbra una factura.
- [ ] Agregar notificacion in-app y agente cuando alguien cancela una factura.
- [ ] Revisar eventos existentes del flujo de facturacion para conectar las notificaciones sin duplicarlas.
- [ ] Confirmar destinatarios: creador de la factura, usuarios de facturacion, admin-rivera y/o super-admin.

### Calidad de datos de clientes

- [ ] En el catalogo de clientes, agregar indicador visual de datos faltantes.
- [ ] En configuracion de empresa, crear catalogo de documentos requeridos para clientes.
- [ ] Permitir configurar documentos obligatorios y opcionales por empresa.
- [ ] Ejemplos de documentos/datos requeridos:
  - RFC.
  - Comprobante de domicilio.
  - INE del responsable.
  - Documentos adicionales definidos por la empresa.
- [ ] Reutilizar el patron ya trabajado para documentos de empleados, si aplica.
- [ ] En crear/editar cliente, validar RFC con expresion regular antes de guardar.
- [ ] En facturacion, si el cliente no tiene datos fiscales completos, mostrar alerta clara con la lista exacta de campos faltantes.
- [ ] Caso observado: al facturar, el error no indicaba que faltaban domicilio, CP, colonia, ciudad, etc.

### Facturacion CFDI: borrador antes de timbrar

Vista:

- `/sat/facturacion/create`

Pendientes:

- [ ] Evitar perdida de captura cuando falla el timbrado por validacion SAT/Facturapi.
- [ ] Evaluar si conviene autosave automatico despues de agregar el primer concepto.
- [ ] Evaluar alternativa visible: boton `Guardar borrador` cerca de acciones de conceptos/resumen.
- [ ] Si se implementa `Guardar borrador`, permitir recuperar el borrador al volver a `/sat/facturacion/create`.
- [ ] Al timbrar correctamente, no regresar automaticamente al listado; permanecer en pantalla de resultado/detalle de la factura para que el usuario confirme PDF/XML/envio.
- [ ] Caso observado: factura fallo por razon social distinta a SAT; al corregir cliente se perdio la factura capturada.
