# MS Donaciones

Plugin WordPress para embeber y administrar el formulario de donaciones de Modulo Sanitario.

Version actual: `1.0.0`

La implementacion actual separa la logica del theme y concentra el formulario, el shortcode, la configuracion del admin, la pagina de equipo y los endpoints REST dentro del plugin.

## Cambios principales en `1.0.0`

- Se implementaron donaciones mensuales mediante suscripciones de Mercado Pago.
- El formulario permite elegir frecuencia `Donacion unica` o `Mensual`.
- En frecuencia mensual, Mercado Pago es el unico metodo disponible.
- Se agrego el endpoint REST para crear suscripciones con la API Preapproval de Mercado Pago.
- Se agrego un retorno propio para procesar la suscripcion cuando el donante vuelve del checkout.
- El webhook de Mercado Pago procesa pagos unicos, autorizaciones de suscripcion y cobros mensuales posteriores.
- Se agregaron locks de idempotencia para evitar oportunidades duplicadas cuando Mercado Pago notifica el mismo evento por mas de un camino.
- Los errores tecnicos de CRM/Salesforce ya no se muestran al donante; quedan registrados internamente en logs y el frontend muestra un mensaje generico configurable.

## Estructura

```txt
ms-donaciones/
  ms-donaciones.php
  assets/
    donacion.js
  includes/
    class-admin.php
    class-shortcodes.php
    class-rest.php
    class-about.php
```

## Instalacion

### Opcion 1: instalacion manual

Copiar la carpeta completa:

```txt
ms-donaciones/
```

dentro de:

```txt
wp-content/plugins/
```

La ruta final debe quedar:

```txt
wp-content/plugins/ms-donaciones/ms-donaciones.php
```

Luego ir al panel de WordPress:

```txt
Plugins > Plugins instalados
```

y activar:

```txt
MS Donaciones
```

### Opcion 2: instalacion por ZIP

Comprimir la carpeta completa `ms-donaciones`, no solo sus archivos internos.

El ZIP debe tener esta forma:

```txt
ms-donaciones.zip
  ms-donaciones/
    ms-donaciones.php
    assets/
    includes/
```

Luego subirlo desde:

```txt
Plugins > Anadir nuevo > Subir plugin
```

## Uso del shortcode

El plugin registra el shortcode:

```txt
[formulario_donacion]
```

Ese shortcode renderiza el contenedor React:

```html
<div id="ms-donacion-root"></div>
```

y carga los assets del formulario solo cuando el shortcode se usa.

## Uso en Elementor

En Elementor se puede insertar de dos formas:

1. Usando el widget **Shortcode**.
2. Usando un widget **HTML** con el shortcode.

Contenido:

```txt
[formulario_donacion]
```

## Panel de administracion

El plugin agrega una seccion al admin de WordPress:

```txt
Donaciones MS
```

Desde ahi se pueden configurar textos y valores del formulario por secciones:

- Textos visibles, con selector interno por seccion.
- Media y links.
- Datos personales a CRM.
- Montos.
- Impacto.
- Mercado Pago.
- Transferencia.
- Equipo.

La configuracion se guarda en la tabla:

```txt
wp_options
```

con la opcion:

```txt
ms_donaciones_labels
```

## Configuraciones disponibles

Actualmente se pueden editar desde el admin, entre otros:

- Labels de Nombre, Apellido, Email, DNI y Telefono.
- URL de imagen principal.
- Texto sobre la imagen principal.
- Metricas del hero.
- Cita del hero.
- Titulos y bajadas del paso 1.
- Mensaje generico de error al guardar datos.
- Configuracion de envio a Salesforce.
- Montos predefinidos.
- Monto inicial.
- Monto minimo.
- Textos de frecuencia unica y mensual.
- Aviso de donacion mensual.
- Mensajes de impacto por monto.
- Nombre, descripcion y tags de metodos de pago.
- Textos de confirmacion.
- Datos de transferencia bancaria.
- Textos del modal.
- Sellos de confianza.
- Links del footer.

## CRM con Salesforce

Cuando el usuario completa el primer paso del formulario, el frontend llama al endpoint REST del plugin:

```txt
POST /wp-json/donacion/v1/guardar
```

Si la integracion CRM esta activada desde el panel de administracion, WordPress crea o actualiza un Contact en Salesforce utilizando la API REST oficial.

La configuracion se realiza desde:

```txt
Donaciones MS > Datos personales a CRM
```

Campos requeridos:

- Client ID.
- Client Secret.
- URL/Dominio de Login.
- Credenciales de la organizacion Salesforce.

La seccion incluye un boton para probar la conexion configurada con Salesforce.

Importante:

```txt
URL/Dominio de Login
```

debe contener la URL de la organizacion Salesforce utilizando el dominio:

```txt
https://xxxxx.my.salesforce.com
```

y no el dominio:

```txt
lightning.force.com
```

Las credenciales se utilizan exclusivamente desde el backend de WordPress y nunca son expuestas al frontend.

La guia completa de configuracion se encuentra en:

```txt
documentacion-ong/SALESFORCE_SETUP.md
```

### Visibilidad de errores CRM

Los errores tecnicos de Salesforce no deben mostrarse al donante.

Si falla la integracion CRM:

1. El detalle tecnico queda registrado en `error_log`.
2. La respuesta publica de `/guardar` devuelve un mensaje generico.
3. El formulario muestra el texto configurable `step1_save_error`.

Esto evita que el usuario vea mensajes con nombres de proveedores, credenciales, objetos internos o errores de API.

### Flujo CRM

Al completar el formulario:

1. Se crea o actualiza un Contact en Salesforce.
2. Se almacena la informacion del potencial donante.
3. Cuando se concreta una donacion mediante Mercado Pago, el sistema puede crear una Opportunity asociada al Contact correspondiente.

## REST API

El plugin registra los siguientes endpoints:

```txt
POST /wp-json/donacion/v1/guardar
POST /wp-json/donacion/v1/crear-preferencia
POST /wp-json/donacion/v1/crear-suscripcion
GET  /wp-json/donacion/v1/retorno-suscripcion
GET  /wp-json/donacion/v1/retorno-pago
POST /wp-json/donacion/v1/webhook
```

### Guardar datos del donante

Payload esperado:

```json
{
  "nombre": "Facundo",
  "apellido": "Alonso",
  "email": "facundoalonso@uca.edu.ar",
  "dni": "12345678",
  "telefono": "1122334455",
  "monto": "",
  "metodo": "",
  "crm_event": "step_1_completed"
}
```

Respuesta publica esperada:

```json
{
  "success": true,
  "message": "Datos recibidos correctamente",
  "crm_result": {
    "enabled": true,
    "success": true,
    "message": "Datos recibidos correctamente."
  }
}
```

Si el CRM esta desactivado, el endpoint igualmente responde correctamente y devuelve `crm_result.enabled` en `false`.

## Mercado Pago

El plugin integra Mercado Pago para dos flujos:

- Donacion unica: Checkout Pro mediante preferencias.
- Donacion mensual: suscripcion mensual mediante Preapproval.

La configuracion se realiza desde:

```txt
Donaciones MS > Mercado Pago
```

Campos requeridos:

- Access Token de Mercado Pago (`TEST-...` para pruebas o `APP_USR-...` para produccion).
- Titulo del item.
- Descriptor.
- URLs de exito, fallo y pendiente.
- URL de Webhook publica con HTTPS.

El Access Token se usa server-side desde WordPress y no se expone en `window.MS_DONACIONES`.

La seccion incluye un boton para probar la conexion guardada con Mercado Pago. Si la conexion no esta validada, la opcion Mercado Pago aparece deshabilitada en el formulario con el texto "No disponible por el momento".

### Donacion unica

El plugin crea preferencias de Mercado Pago Checkout Pro desde el endpoint:

```txt
POST /wp-json/donacion/v1/crear-preferencia
```

El endpoint devuelve `init_point` y el frontend redirige al donante a Mercado Pago.

Cuando el pago unico se aprueba, el sistema puede registrar una Opportunity en Salesforce asociada al Contact previamente creado.

### Donacion mensual por suscripcion

Cuando el donante elige frecuencia mensual, el frontend usa:

```txt
POST /wp-json/donacion/v1/crear-suscripcion
```

Ese endpoint crea un Preapproval en Mercado Pago con:

- `frequency`: `1`.
- `frequency_type`: `months`.
- `transaction_amount`: monto elegido por el donante.
- `currency_id`: `ARS`.
- `external_reference`: referencia interna de la suscripcion.
- `back_url`: retorno publico del plugin.
- `notification_url`: webhook publico del plugin.

La suscripcion se crea sin fecha de finalizacion: Mercado Pago cobra una vez por mes hasta que la suscripcion se cancele desde Mercado Pago.

En el formulario, cuando la frecuencia es mensual:

- Se ocultan los metodos alternativos.
- Solo queda disponible Mercado Pago.
- Se muestra un aviso indicando que las donaciones mensuales se procesan exclusivamente por Mercado Pago.

### Retorno y webhook de suscripciones

El retorno configurado para suscripciones es:

```txt
GET /wp-json/donacion/v1/retorno-suscripcion
```

Cuando el donante vuelve desde Mercado Pago, WordPress consulta el estado del Preapproval. Si esta `authorized`, procesa la donacion mensual inmediatamente y luego redirige a la pagina de exito configurada.

El webhook general es:

```txt
POST /wp-json/donacion/v1/webhook
```

Debe recibir estos eventos de Mercado Pago:

- `payment`: pagos unicos.
- `subscription_preapproval`: autorizacion inicial de una suscripcion.
- `subscription_authorized_payment`: cobros mensuales posteriores.

El retorno y el webhook comparten logica idempotente. Si el retorno y el webhook informan la misma autorizacion inicial, el plugin crea una sola oportunidad y descarta el duplicado.

Para cobros mensuales posteriores, Mercado Pago envia eventos `subscription_authorized_payment`. El plugin recupera el donante asociado a la suscripcion y registra una nueva oportunidad mensual por cada cobro real.

### Opportunities

Cuando el flujo de donacion continua correctamente, el sistema puede registrar una Opportunity en Salesforce asociada al Contact previamente creado, permitiendo realizar seguimiento de las contribuciones dentro del CRM.

Las oportunidades se diferencian por tipo:

| Frecuencia | Nombre de Opportunity | Descripcion |
|------------|-----------------------|-------------|
| Unica | `Donacion MP Unica - <nombre>` | Donacion unica via Mercado Pago |
| Mensual | `Donacion MP Mensual - <nombre>` | Donacion mensual via Mercado Pago |

La guia especifica de setup y pruebas de suscripciones mensuales se encuentra en:

```txt
documentacion-ong/PAGOS_MENSUALES_SETUP.md
```

## Archivos principales

### `ms-donaciones.php`

Archivo principal del plugin. Define constantes, carga clases e inicializa:

- Shortcodes.
- REST API.
- Admin panel.
- Pagina de equipo.

### `includes/class-shortcodes.php`

Registra:

```txt
[formulario_donacion]
```

Tambien carga React, ReactDOM, Babel y `assets/donacion.js`.

Ademas pasa la configuracion publica del admin al frontend mediante:

```php
wp_localize_script()
```

como variable global:

```js
window.MS_DONACIONES
```

Las credenciales y claves internas de Salesforce y Mercado Pago no se pasan al frontend.

### `includes/class-admin.php`

Define el panel de administracion `Donaciones MS`.

Permite editar los textos, montos, mensajes de impacto, datos bancarios, configuracion CRM y configuracion de Mercado Pago.

### `includes/class-rest.php`

Define los endpoints REST:

```txt
/wp-json/donacion/v1/guardar
/wp-json/donacion/v1/crear-preferencia
/wp-json/donacion/v1/crear-suscripcion
/wp-json/donacion/v1/retorno-suscripcion
/wp-json/donacion/v1/retorno-pago
/wp-json/donacion/v1/webhook
```

Sanitiza los datos recibidos y, si la integracion CRM esta activada, los envia a Salesforce.

Tambien crea preferencias de Checkout Pro, suscripciones Preapproval, procesa retornos desde Mercado Pago y atiende webhooks de pagos y suscripciones.

### `includes/class-about.php`

Define la subpagina `Equipo` dentro del admin del plugin.

Muestra integrantes, informacion institucional y la version actual del plugin.

### `assets/donacion.js`

Contiene el formulario React embebido.

Lee la configuracion publica desde:

```js
window.MS_DONACIONES.labels
```

Gestiona:

- Datos del donante.
- Frecuencia unica o mensual.
- Montos predefinidos y monto personalizado.
- Seleccion de metodo de pago.
- Redireccion a Mercado Pago.
- Mensajes genericos para errores visibles al donante.

## Notas de desarrollo

El formulario actual usa React 18 y Babel desde CDN para facilitar la integracion rapida dentro de WordPress.

A futuro se recomienda compilar el frontend con un build step y reemplazar Babel en navegador por un bundle estatico.

## Pendientes

- Separar CSS a `assets/donacion.css`.
- Agregar validaciones REST mas estrictas.
- Agregar tests o validaciones automatizadas.
