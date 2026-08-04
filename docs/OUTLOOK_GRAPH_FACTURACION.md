# Migracion de envio de facturas a Outlook con Microsoft Graph

## Objetivo

Quitar lo antes posible el envio de facturas desde una cuenta personal de Gmail con contrasena de app y mover Sirico a un envio institucional por Microsoft 365 / Outlook usando Microsoft Graph.

La meta es mantener habilitadas las politicas modernas de seguridad de Microsoft, MFA y Security Defaults, evitando SMTP con usuario y contrasena.

## Problema actual

Sirico puede enviar correos usando SMTP tradicional, pero Microsoft 365 bloquea ese flujo cuando Security Defaults esta activo. El error visto fue:

```text
535 5.7.139 Authentication unsuccessful, user is locked by your organization's security defaults policy.
```

Eso significa que Outlook rechazo la autenticacion SMTP basica. Resolverlo desactivando Security Defaults o bajando MFA no es recomendable.

Temporalmente se uso una contrasena de app de Gmail personal. Eso debe retirarse porque:

- Depende de un correo personal.
- La contrasena de app funciona como secreto de larga duracion.
- No deja el envio bajo dominio y control institucional.
- No es ideal para auditoria, continuidad ni seguridad.

## Enfoque recomendado

Usar Microsoft Graph API con una App Registration en Microsoft Entra ID.

Sirico ya no enviaria con:

```env
MAIL_HOST=smtp.office365.com
MAIL_USERNAME=administracion@riveraco.com.mx
MAIL_PASSWORD=...
```

En su lugar, Sirico usaria OAuth2:

```env
FACTURACION_MAIL_PROVIDER=graph
FACTURACION_GRAPH_TENANT_ID=
FACTURACION_GRAPH_CLIENT_ID=
FACTURACION_GRAPH_CLIENT_SECRET=
FACTURACION_GRAPH_USER=administracion@riveraco.com.mx
FACTURACION_MAIL_FROM_ADDRESS=administracion@riveraco.com.mx
FACTURACION_MAIL_FROM_NAME="Rivera Construcciones"
```

El envio se haria con:

```text
POST https://graph.microsoft.com/v1.0/users/administracion@riveraco.com.mx/sendMail
```

## Parte que debe hacer Hector en Microsoft 365 / Entra

### 1. Confirmar el buzon institucional

Decidir que buzon enviara las facturas.

Recomendado:

```text
administracion@riveraco.com.mx
```

Alternativas aceptables:

```text
facturacion@riveraco.com.mx
noreply@riveraco.com.mx
```

El buzon debe existir en Microsoft 365 y tener licencia/capacidad de correo en Exchange Online.

### 2. Entrar al panel correcto

Ir a:

```text
https://entra.microsoft.com
```

Entrar con una cuenta administradora del tenant.

### 3. Crear App Registration

Ruta:

```text
Microsoft Entra admin center
> Identity
> Applications
> App registrations
> New registration
```

Valores sugeridos:

```text
Name: Sirico Facturacion Mail Sender
Supported account types: Accounts in this organizational directory only
Redirect URI: dejar vacio
```

Crear la app.

### 4. Guardar datos de la app

En la pantalla Overview copiar:

```text
Application (client) ID
Directory (tenant) ID
```

Estos valores se usaran despues en `.env`:

```env
FACTURACION_GRAPH_CLIENT_ID=
FACTURACION_GRAPH_TENANT_ID=
```

### 5. Crear client secret

Ruta dentro de la app:

```text
Certificates & secrets
> Client secrets
> New client secret
```

Valores sugeridos:

```text
Description: Sirico production mail sender
Expires: 6 months o 12 months
```

Al crearlo, copiar inmediatamente el campo **Value**. Ese valor solo se muestra una vez.

Se usara en:

```env
FACTURACION_GRAPH_CLIENT_SECRET=
```

### 6. Agregar permiso Microsoft Graph

Ruta dentro de la app:

```text
API permissions
> Add a permission
> Microsoft Graph
> Application permissions
> Mail.Send
> Add permissions
```

Importante: debe ser **Application permission**, no Delegated permission.

### 7. Dar consentimiento de administrador

En:

```text
API permissions
> Grant admin consent
```

Confirmar el consentimiento.

Debe quedar el permiso:

```text
Mail.Send
Type: Application
Status: Granted
```

### 8. Restringir la app al buzon de facturacion

Este paso es importante porque `Mail.Send` como permiso de aplicacion puede permitir enviar como usuarios de la organizacion. Hay que limitarlo al buzon elegido.

Esto normalmente se hace desde Exchange Online PowerShell o Application RBAC.

Objetivo de seguridad:

```text
La app Sirico Facturacion Mail Sender solo puede enviar desde:
administracion@riveraco.com.mx
```

Si no tienes claro como aplicar esta restriccion, no avances a produccion todavia. Se puede hacer primero una prueba controlada y despues cerrar el alcance.

### 9. Pasarme los datos necesarios

Pasarme estos valores, idealmente por un medio seguro:

```env
FACTURACION_GRAPH_TENANT_ID=...
FACTURACION_GRAPH_CLIENT_ID=...
FACTURACION_GRAPH_CLIENT_SECRET=...
FACTURACION_GRAPH_USER=administracion@riveraco.com.mx
FACTURACION_MAIL_FROM_ADDRESS=administracion@riveraco.com.mx
FACTURACION_MAIL_FROM_NAME="Rivera Construcciones"
```

No enviar capturas con secretos si no es necesario. El `client_secret` debe tratarse como contrasena.

## Parte que ejecutaria Codex en Sirico

### 1. Revisar el flujo actual de envio

Archivos actuales relevantes:

```text
config/mail.php
config/services.php
app/Mail/SatFacturaMail.php
app/Http/Controllers/Sat/SatFacturacionController.php
app/Http/Controllers/Sat/SatFacturaPagoController.php
resources/views/emails/sat/factura.blade.php
```

Validar:

- Envio de factura CFDI.
- Envio de complemento de pago.
- Adjuntos XML y PDF.
- Remitente y nombre visible.
- Mensajes de error al usuario.

### 2. Crear servicio Microsoft Graph

Crear un servicio dedicado, por ejemplo:

```text
app/Services/Mail/MicrosoftGraphMailService.php
```

Responsabilidades:

- Pedir token OAuth2 con `client_credentials`.
- Renderizar el HTML del correo actual.
- Adjuntar XML y PDF.
- Enviar a Graph con `/users/{correo}/sendMail`.
- Lanzar errores claros si faltan variables o Microsoft rechaza el envio.

### 3. Agregar configuracion

Actualizar:

```text
config/services.php
```

Agregar:

```php
'facturacion_mail' => [
    'provider' => env('FACTURACION_MAIL_PROVIDER', 'laravel'),
    'mailer' => env('FACTURACION_MAIL_MAILER', 'facturas'),
    'from_address' => env('FACTURACION_MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
    'from_name' => env('FACTURACION_MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Rivera Construcciones')),
    'microsoft_graph' => [
        'tenant_id' => env('FACTURACION_GRAPH_TENANT_ID'),
        'client_id' => env('FACTURACION_GRAPH_CLIENT_ID'),
        'client_secret' => env('FACTURACION_GRAPH_CLIENT_SECRET'),
        'user' => env('FACTURACION_GRAPH_USER', env('FACTURACION_MAIL_FROM_ADDRESS')),
    ],
],
```

### 4. Agregar variables de entorno

En `.env` de produccion:

```env
FACTURACION_MAIL_PROVIDER=graph
FACTURACION_GRAPH_TENANT_ID=
FACTURACION_GRAPH_CLIENT_ID=
FACTURACION_GRAPH_CLIENT_SECRET=
FACTURACION_GRAPH_USER=administracion@riveraco.com.mx
FACTURACION_MAIL_FROM_ADDRESS=administracion@riveraco.com.mx
FACTURACION_MAIL_FROM_NAME="Rivera Construcciones"
```

Mantener el mailer anterior como fallback temporal:

```env
FACTURACION_MAIL_PROVIDER=laravel
```

Eso permite volver al comportamiento anterior sin revertir codigo si hubiera un problema durante la prueba.

### 5. Cambiar solo facturacion primero

Modificar unicamente:

```text
SatFacturacionController::enviar
SatFacturaPagoController::enviar
```

No cambiar de golpe todos los correos del sistema.

Regla:

- Si `FACTURACION_MAIL_PROVIDER=graph`, enviar por Graph.
- Si no, usar `Mail::mailer(...)` como hasta ahora.

### 6. Probar en ambiente controlado

Pruebas minimas:

- Enviar una factura a un correo interno.
- Enviar una factura a un correo externo.
- Enviar complemento de pago.
- Confirmar que llega HTML correcto.
- Confirmar adjuntos XML y PDF.
- Confirmar que aparece en Elementos enviados del buzon institucional.
- Confirmar que el remitente visible es el correcto.

### 7. Quitar Gmail personal

Despues de validar Graph:

- Quitar credenciales Gmail del `.env`.
- Revocar la contrasena de app de Gmail desde la cuenta personal.
- Confirmar que Sirico ya no depende de ese correo.
- Documentar fecha del cambio.

### 8. Endurecer seguridad

Pendientes recomendados:

- Rotacion programada del `client_secret`.
- Restriccion de app al buzon de facturacion.
- Guardar secretos solo en `.env`/panel seguro, nunca en Git.
- Registrar errores de Graph sin exponer secretos.
- Considerar alertas si falla el envio de facturas.

## Checklist de avance

- [ ] Elegir buzon institucional definitivo.
- [ ] Crear App Registration en Entra.
- [ ] Copiar Tenant ID y Client ID.
- [ ] Crear Client Secret.
- [ ] Agregar permiso Application `Mail.Send`.
- [ ] Dar admin consent.
- [ ] Restringir app al buzon de facturacion.
- [ ] Configurar variables en `.env`.
- [ ] Implementar servicio Graph en Sirico.
- [ ] Probar factura con XML/PDF.
- [ ] Probar complemento de pago.
- [ ] Confirmar Elementos enviados en Outlook.
- [ ] Cambiar produccion a `FACTURACION_MAIL_PROVIDER=graph`.
- [ ] Revocar contrasena de app de Gmail.

## Decision recomendada

No desactivar Security Defaults ni MFA para resolver el envio.

El camino correcto es:

```text
Sirico -> Microsoft Graph OAuth2 -> Outlook institucional
```

Esto quita la dependencia de Gmail personal y evita volver a SMTP basico.
