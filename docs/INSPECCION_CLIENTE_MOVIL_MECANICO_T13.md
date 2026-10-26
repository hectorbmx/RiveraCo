# T13. Inspección del cliente móvil y checkpoints de integración

Fecha: 2026-10-23. Inspección inicial y seguimiento por checkpoints. C13.2 implementó navegación y consulta inicial; el módulo completo continúa pendiente.

## Proyecto localizado

- Ruta: `C:\xampp\htdocs\rivera\app`.
- Repositorio Git separado del backend, rama `main`, HEAD `dddabff` (`ajustes en captura de horometros`). Sin cambios locales antes ni después de la inspección.
- Ionic 8, Angular 20 y Capacitor 8, según `package.json`; dependencias locales presentes.
- Cámara Capacitor instalada; el registro de vehículo ya usa `Camera.getPhoto()` y subida mediante `FormData`.
- Desarrollo apunta a `http://127.0.0.1:8000/api/v1`; producción tiene su configuración propia. No modificar hosts para esta integración.
- Sin `AGENTS.md` en el directorio padre `rivera` ni en `app`; no hay grafo graphify del cliente. La búsqueda del grafo del backend no cubre sus símbolos, por lo que la inspección se hizo sobre los archivos concretos.

El cliente está fuera de la raíz de escritura de esta conversación. Al editarlo, usar el mecanismo de aprobación de sandbox para las escrituras necesarias, con ruta y alcance explícitos; no copiar el cliente dentro del backend ni duplicar su repositorio. La inspección actual no necesitó escrituras en él.

## Hallazgos

| ID | Archivo / componente | Hallazgo e implicación |
|---|---|---|
| C01 | `src/app/services/auth.ts` | Login y `getMe()` ya almacenan `paneles_disponibles`, obras, defaults y contexto nullable. Los paneles usan clave string, por lo que el tipo no impide recibir `mecanico`. |
| C02 | `src/app/guards/app-shell.guard.ts` | Solo deriva a gerencial o residente. Con panel único mecánico sin obras termina en tabs de residente. Requiere destino explícito sin obra y sin fallback residente para ese panel. |
| C03 | `src/app/pages/selector-panel/selector-panel.page.ts` | `choosePanel()` trata cualquier panel distinto de gerencial como residente. Debe reconocer mecánico y validar la opción contra las disponibles. |
| C04 | `src/app/tabs-gerencial/tabs-gerencial.routes.ts` | Guard gerencial y navegación propia. No abrir ese árbol al permiso mecánico; crear un shell separado con máquinas, vehículos y seguimiento. |
| C05 | `src/app/tabs/tabs.routes.ts`, `guards/auth-guard.ts` | Las rutas residentes se apoyan en token; el nuevo guard/shell mecánico debe aislar el destino y dejar que la API imponga la autorización efectiva. Revisar redirecciones por pérdida de sesión/permiso. |
| C06 | `services/gerencial-maquina.service.ts`, `services/gerencial-vehiculo.service.ts` | Consultan `/gerencial/*`, con paginador `data.data`. Los contratos mecánicos usan arreglo `data` y `meta`. No basta cambiar etiquetas ni navegar a esas páginas existentes. |
| C07 | `pages/gerencial-maquinas/*`, `pages/gerencial-vehiculos/*`, modelos | Hay componentes visuales reutilizables, pero ligados a DTO/transporte gerencial. Vehículos incluyen seguros/documentos/contactos omitidos por la proyección mecánica. Reutilizar presentación compatible sin pedir datos administrativos. |
| C08 | `services/api.ts` | Ya implementa GET, POST, PATCH y `postMultipart`, con token y cabecera Accept. La inyección automática de obra solo cubre residente/KM, por lo que `/mecanico` puede operar sin obra. |
| C09 | `services/api.ts::handleError` | Convierte HttpErrorResponse en Error y pierde status/errores por campo. Para 401/403/409/422 del módulo deben preservarse esos datos sin romper consumidores existentes que leen `message`. |
| C10 | `pages/vehiculo-registro/vehiculo-registro.page.ts` | Referencia de cámara/archivo/FormData existente. Para evidencias usar exactamente `fotos[indice]` y `foto_descripciones[indice]`, sin Content-Type JSON/boundary manual. |
| C11 | `services/gerencial-maquina.service.ts::getMaquinaById`, `services/api.ts::get` | El primero construye URL absoluta mientras el segundo concatena base/endpoint. El servicio mecánico debe usar endpoints relativos consistentes, sin arrastrar ese patrón. No se corrigió ese método gerencial en esta inspección. |
| C12 | `package.json`, `angular.json` | Existen scripts build/test/lint. El build genera `www` y tiene límites de tamaño; validar TypeScript no equivale a compilar plantillas ni probar Android. |

## Checkpoints pequeños dentro de T13

No marcar T13 completa hasta cerrar todos los checkpoints aplicables. Se respeta una pausa mínima de diez minutos entre entregas de la ejecución automática.

### C13.1. Localizar e inspeccionar — completado

- Proyecto, repositorio, árbol de sesión/navegación, equipos, transporte y cámara identificados.
- Baseline sin cambios: `node node_modules/typescript/bin/tsc -p tsconfig.app.json --noEmit --incremental false`, exit 0.
- `git status --short` vacío antes y después. No se ejecutaron llamadas API de escritura ni un build de despliegue.

### C13.2. Sesión y navegación — completado, independiente de D05

- Reconocer `mecanico` en shell y selector; crear guard y navegación propios sin requerir obra.
- Conservar panel directo cuando es único y selector en múltiples accesos; limpiar selección revocada usando opciones de sesión.
- Incorporar destinos de máquinas, vehículos y servicios sin abrir funciones residentes/gerenciales.
- Probar usuario mecánico exclusivo, múltiples paneles, recarga y pérdida de sesión/permiso.

**Validado:** shell propio `/tabs-mecanico`, guard de hijos que revalida `me`, selección explícita sin obra y destinos de máquinas/vehículos/servicios con vistas de consulta reales. Selector y shell reconocen mecánico; guards impiden abrir tabs residentes al mecánico exclusivo y protegen los destinos gerenciales cubiertos. ApiService conserva status/errores junto con `message`. TypeScript de app/spec sin emisión y build de desarrollo completo con plantillas pasaron; 11 pruebas en Chrome Headless. [Entrega C13.2](IMPLEMENTACION_NAVEGACION_MOVIL_MECANICO_C13_2.md). No se afirma prueba en dispositivo ni integración real con usuario mecánico.

### C13.3. Equipos y consultas — completado, independiente de D05

- Cliente tipado para `/mecanico`, respuestas `data`/`meta` y rutas relativas.
- Máquinas/vehículos con búsqueda, paginación, detalle e historial del contrato T04.
- Seguimiento con filtros/listado/detalle y acciones en servidor de T07–T11.
- Evitar consultas N+1 desde el cliente y respuestas antiguas de búsquedas que sustituyan resultados recientes.

Incluido en C13.2 para que la navegación tenga destinos funcionales: listado/detalle de ambos equipos y de servicios, búsqueda/paginación, estado/solo míos en servicios, historial y proyección de orden/materiales/solicitudes/documentos/evidencias. C13.3 agregó filtros de fechas/equipo, selección de catálogo paginada, selección persistente, limpieza y actualización, con descarte de respuestas antiguas. Validados TypeScript app/spec, build y 24 pruebas de navegador, trece nuevas de vistas. [Entrega C13.3](IMPLEMENTACION_CONSULTAS_MOVIL_MECANICO_C13_3.md). El recorrido conectado con usuario real autorizado continúa en T14; no se afirma que esas pruebas controladas lo sustituyan.

### C13.4. Captura y operaciones disponibles — completado, independiente de D05

- Catálogos y lectura sugerida T05; formulario POST/PATCH T06 con un equipo, obra opcional y empleado automático.
- Generación de orden T08, ajuste de inclusión/motivo T09 y solicitudes T10, con confirmación y bloqueo de doble envío.
- Productos Giralda de consulta: búsqueda, unidad, stock actual/reservado/disponible. No introducir precios editables ni asignar productos libremente a un snapshot mediante contratos inexistentes.
- Evidencias T11: selección/cámara, descripciones, límite acumulado, multipart, errores y recarga del detalle.
- Mostrar campos/permisos/capacidades reales; vehículos conservan el límite de abastecimiento automático.

**Validado:** alta/edición de borradores, generación de orden, inclusión/motivos, solicitudes por tipo, consulta de productos y carga de evidencias con selección/cámara mediante contratos T05–T11. Reingreso Ionic revalida datos y evita reutilizar un alta exitosa. 47 pruebas Chrome Headless, TypeScript app/spec y build de desarrollo exitosos. [Entrega C13.4](IMPLEMENTACION_CAPTURA_MOVIL_MECANICO_C13_4.md). Obra opcional omitida en altas y preservada en edición. El dispositivo/cámara real y el recorrido conectado continúan en T14; ejecución espera D05.

### C13.5. Ejecución y validación móvil — completado el 2026-10-26

- Integrar inicio/completado únicamente después de resolver D05 y publicar sus contratos; cancelación sigue solo web según D04.
- Probar flujo completo, expiración, validaciones por campo, conflictos, cámara/galería y doble envío.
- Ejecutar build de plantillas y pruebas pertinentes; validar Android/cámara real como parte de T14. No afirmar recorrido móvil probado por pasar TypeScript.

**Validado C13.5:** contratos T12 integrados con fecha/lectura/notas, acciones del servidor, confirmaciones y manejo de errores/resultados inciertos. 63 pruebas Chrome Headless, TypeScript app/spec y build completo de desarrollo exitosos. [Entrega C13.5](IMPLEMENTACION_EJECUCION_MOVIL_MECANICO_C13_5.md). Recorrido conectado y dispositivo permanecen en T14.

## Dependencias y publicación

D05 fue confirmada por el usuario el 2026-10-26: permitir iniciar y exigir surtido completo al completar, sin bloqueo para servicios sin materiales incluidos. T12 y C13.5 están implementados y validados en sus checkpoints.

La inspección del cliente confirma que no falta el proyecto móvil. C13.2–C13.4 se implementaron y validaron con respuestas controladas de forma independiente de D05; C13.5 se completó después de T12; la entrega integral sigue pendiente de la validación conectada de T14. La última solicitud limita esta ejecución a C13.5.

Backend y cliente son repositorios separados. Al publicar al finalizar, revisar sus cambios/remotos/ramas por separado y no afirmar que un commit del backend contiene el cliente. No se realizó commit ni push durante esta inspección.
