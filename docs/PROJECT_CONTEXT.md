# PROJECT_CONTEXT.md

## Proyecto

**Nombre:** SIRICO V2
**Empresa:** Rivera Construcciones
**Tipo:** ERP para gestión integral de empresa constructora.

---

# Stack Tecnológico

## Backend

* Laravel 10
* PHP 8.2+
* MySQL
* Spatie Permissions
* Laravel Queues
* Laravel Scheduler

## Frontend

* Blade
* Tailwind CSS
* Alpine.js

## Aplicaciones móviles

* Ionic Angular
* Capacitor
* Android
* iOS

---

# Ambientes

## Producción

* Hosting: Bluehost
* URL:

```
https://sirico.riveraco.com.mx/v2/public
```

Base de datos:

```
riverac3_v2
```

## Legacy

Sistema anterior.

Base de datos:

```
riverac3_sirico
```

---

# Objetivo del proyecto

Migrar gradualmente desde el sistema Legacy hacia SIRICO V2 manteniendo continuidad operativa.

La prioridad es construir módulos modernos manteniendo compatibilidad con la operación actual.

---

# Convenciones generales

## Base de datos

* Todas las nuevas tablas usan:

```
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
created_at TIMESTAMP NULL
updated_at TIMESTAMP NULL
```

* Relaciones mediante:

```
foreignId()->constrained()
```

* SoftDeletes únicamente cuando sea necesario.

---

## Controladores

Separar por dominio:

```
App\Http\Controllers\
```

Ejemplos:

```
Obras\
Vehiculos\
Nomina\
SAT\
Inventario\
```

---

## Vistas

Ubicación:

```
resources/views/
```

Estructura:

```
modulo/
    index.blade.php
    create.blade.php
    edit.blade.php
    show.blade.php
    partials/
```

---

# Módulos implementados

## Clientes

Tabla:

```
clientes
```

Campos principales:

* nombre_comercial
* razon_social
* rfc
* telefono
* email
* direccion
* regimen_fiscal
* uso_cfdi_default

Notas:

* RFC es único.
* Integración con FacturAPI.

---

## Obras

Gestión completa de proyectos.

Incluye:

* Información general
* Presupuestos
* Avances
* Centros de costo
* Personal asignado

---

## Vehículos

Funciones:

* Asignaciones
* Evidencias fotográficas
* Mantenimientos
* Documentación
* Seguros

---

## Maquinaria

Funciones:

* Estados operativos
* Ubicación actual
* Mantenimientos
* Seguros
* Comisiones relacionadas

---

## Empleados

Funciones:

* Expediente digital
* Contactos de emergencia
* Documentos
* Asignaciones a obra
* Nómina

---

## Nómina

Características:

* Corridas semanales
* Corridas quincenales
* Comisiones
* Horas extra
* Aguinaldo
* Prima vacacional

---

## Checadas

Integración:

ZKTeco F1500 Plus

Funciones:

* Importación automática
* Resúmenes semanales
* Reportes por empleado

---

## SAT

Funciones:

### Descarga Masiva SAT

* Descarga XML emitidos
* Descarga XML recibidos
* Procesamiento automático

### Facturación

Proveedor PAC:

```
FacturAPI
```

Características:

* CFDI 4.0
* PDF
* XML
* Pagos PPD
* Pagos PUE
* Cancelaciones

---

## Inventario

Funciones:

* Entradas
* Salidas
* Ajustes
* Kardex
* Almacenes

---

## Cajas chicas

Funciones:

* Solicitudes
* Comprobaciones
* Autorizaciones

---

## Programación de pagos

Funciones:

* Calendario de pagos
* Proveedores
* Control de vencimientos

---

# Convenciones para IA

Siempre asumir:

* Laravel 10
* PHP 8.2
* MySQL
* Tailwind CSS
* Alpine.js

Prioridades:

1. Mantener compatibilidad con producción.
2. Evitar cambios destructivos.
3. Proponer migraciones reversibles.
4. Generar código listo para copiar y pegar.
5. Explicar riesgos antes de modificar producción.

---

# Flujo Git

Ramas principales:

```
master → producción
develop → desarrollo
feature/* → nuevas funcionalidades
```

Antes de modificar:

* Revisar migraciones existentes.
* Validar impacto en producción.
* Generar respaldo cuando aplique.

---

# Información importante

* Nunca asumir que producción puede reconstruirse desde cero.
* La base Legacy continúa siendo referencia histórica.
* Cualquier migración de datos debe ser idempotente.
* Siempre validar duplicados antes de importar.

---

# Estado actual

El sistema se encuentra en producción activa y evoluciona mediante migración progresiva desde Legacy hacia SIRICO V2.
