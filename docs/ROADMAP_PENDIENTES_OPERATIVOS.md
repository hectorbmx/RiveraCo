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
