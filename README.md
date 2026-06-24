# MS Donaciones

Plugin de WordPress para gestionar el formulario de donaciones de Módulo Sanitario.

Incluye:

- Donaciones únicas mediante Mercado Pago Checkout Pro.
- Donaciones mensuales mediante suscripciones de Mercado Pago.
- Transferencias bancarias.
- Registro y actualización de donantes en Salesforce.
- Creación de oportunidades en Salesforce después de pagos aprobados.
- Panel de administración para configurar textos, montos, medios de pago e integraciones.

## Requisitos

- WordPress 6.0 o superior.
- PHP 8.0 o superior.
- Una aplicación de Mercado Pago con Access Token.
- Una Connected App de Salesforce para utilizar la integración CRM.
- Un sitio público con HTTPS para recibir webhooks.

## Instalación

1. Copiar la carpeta `ms-donaciones` dentro de:

   ```text
   wp-content/plugins/
   ```

2. Activar **MS Donaciones** desde el panel de plugins de WordPress.
3. Crear o editar la página donde se mostrará el formulario.
4. Insertar el shortcode:

   ```text
   [formulario_donacion]
   ```

5. Configurar el plugin desde **Donaciones MS** en el panel de administración.

## Mercado Pago

La misma credencial se utiliza para donaciones únicas y suscripciones mensuales.

En **Donaciones MS → Mercado Pago**, configurar:

- **Access Token:** `TEST-...` para pruebas o `APP_USR-...` para producción.
- **URL de Webhook:**

  ```text
  https://tu-dominio.com/wp-json/donacion/v1/webhook
  ```

- URL de éxito.
- URL de fallo.
- URL de pago pendiente.
- Título del ítem.
- Descriptor que aparecerá en el resumen de la tarjeta.

Después de guardar, utilizar **Probar conexión con Mercado Pago**.

### Eventos procesados

El webhook procesa los siguientes eventos:

- `payment`
- `subscription_preapproval`
- `subscription_authorized_payment`

### Endpoints

```text
POST /wp-json/donacion/v1/crear-preferencia
POST /wp-json/donacion/v1/crear-suscripcion
POST /wp-json/donacion/v1/webhook
GET  /wp-json/donacion/v1/retorno-pago
GET  /wp-json/donacion/v1/retorno-suscripcion
```

## Salesforce

La autenticación utiliza OAuth 2.0 con el flujo `client_credentials`.

### Connected App

1. Crear un usuario de integración con acceso a la API.
2. Crear una Connected App en Salesforce.
3. Activar OAuth.
4. Habilitar el flujo Client Credentials.
5. Seleccionar el usuario de integración como **Run As User**.
6. Conceder permisos sobre:

   - `Contact`
   - `Account`
   - `Opportunity`
   - Campos personalizados utilizados por la integración

### Configuración en WordPress

En **Donaciones MS → Datos personales a CRM**, configurar:

- Activar envío a Salesforce.
- Sandbox, si corresponde.
- URL de My Domain, si la organización lo requiere.
- Consumer Key.
- Consumer Secret.
- API Names de los campos de Contact.
- API Name del campo DNI.
- Stage de la oportunidad, por ejemplo `Closed Won`.

Guardar y utilizar **Probar conexión con Salesforce**.

El plugin busca contactos primero por DNI y luego por correo electrónico. Si encuentra uno, lo actualiza; de lo contrario, crea un Contact nuevo.

## Desarrollo local con ngrok

Mercado Pago necesita una URL pública HTTPS para enviar notificaciones.

En un sitio creado con Local, identificar primero el puerto HTTP de Nginx. Para este proyecto es `10005`.

Ejecutar:

```powershell
ngrok http 10005 --host-header=sandbox-modulo-sanitario.local
```

Configurar como webhook la URL HTTPS generada:

```text
https://tu-subdominio.ngrok-free.app/wp-json/donacion/v1/webhook
```

La URL debe actualizarse en WordPress y Mercado Pago cada vez que cambie el dominio gratuito de ngrok.

El inspector local de solicitudes está disponible en:

```text
http://localhost:4040
```

## Seguridad

- Las credenciales de Mercado Pago y Salesforce se utilizan únicamente del lado del servidor.
- Los Access Tokens, secretos y contraseñas no deben incluirse en Git.
- No compartir exportaciones de la base de datos que contengan opciones del plugin sin eliminar previamente las credenciales.
- En producción se recomienda limitar los logs y mantener `WP_DEBUG` desactivado.

## Estructura

```text
ms-donaciones/
├── assets/
│   └── donacion.js
├── includes/
│   ├── class-about.php
│   ├── class-admin.php
│   ├── class-rest.php
│   └── class-shortcodes.php
├── ms-donaciones.php
└── README.md
```

## Versión

Versión actual: **1.0.1**

## Licencia

GPL-2.0
