# MS Donaciones

Plugin de WordPress para donaciones únicas y recurrentes de Módulo Sanitario.

## Requisitos

- WordPress 6.0 o superior.
- PHP 8.0 o superior.
- Aplicación de Mercado Pago con Access Token.
- Salesforce con una Connected App configurada para OAuth Client Credentials.
- URL pública HTTPS para webhooks.

## Instalación

1. Copiar `ms-donaciones` en `wp-content/plugins/`.
2. Activar **MS Donaciones** desde WordPress.
3. Insertar el shortcode:

   ```text
   [formulario_donacion]
   ```

4. Configurar las integraciones desde **Donaciones MS**.

## Mercado Pago

Configurar:

- Access Token de pruebas o producción.
- URL pública del webhook:

  ```text
  https://tu-dominio.com/wp-json/donacion/v1/webhook
  ```

- Opcionalmente, páginas propias de éxito, fallo y pago pendiente.

No es necesario crear una página `/gracias`. De forma predeterminada, Mercado Pago vuelve a los endpoints del plugin, el plugin verifica el estado real de la operación y regresa al formulario mostrando un mensaje de:

- Pago aprobado.
- Pago pendiente.
- Pago rechazado.
- Suscripción autorizada.

Las URLs personalizadas sólo se utilizan al activar **Usar páginas personalizadas de resultado**.

El plugin procesa:

- `payment`: pagos puntuales.
- `subscription_preapproval`: autorización de una suscripción.
- `subscription_authorized_payment`: cobros procesados de una suscripción.

La autorización de una suscripción no se registra como dinero cobrado. La Opportunity se crea cuando Mercado Pago confirma el cobro mediante `subscription_authorized_payment`.

`subscription_preapproval` también notifica actualizaciones posteriores. Si la persona pausa o cancela la suscripción desde Mercado Pago, el plugin consulta el Preapproval actualizado y puede guardar en Contact el estado, el ID y la fecha de cancelación. Las Opportunities de cobros anteriores permanecen sin cambios.

## Salesforce

La autenticación utiliza OAuth 2.0 Client Credentials mediante Consumer Key, Consumer Secret y un usuario configurado como Run As User. Los campos de usuario, contraseña y Security Token no son necesarios.

La búsqueda de Contact se realiza en este orden:

1. Email.
2. DNI, si se configuró un campo para ese dato.

Si existe un Contact con el mismo email, se actualiza en lugar de crear otro. Si Salesforce no puede completar la consulta, el plugin no crea un Contact nuevo para evitar duplicados.

La prueba **Probar conexión y campos personalizados** valida:

- Autenticación OAuth.
- API Names configurados de Contact.
- Campo DNI.
- API Names personalizados de Opportunity.
- Stage de Opportunity.
- Valores de `Opportunity.Type` para pagos puntuales y recurrentes.

La pantalla muestra checks individuales para campos encontrados, faltantes u opcionales.

Las Opportunities se distinguen como:

- `PAGO_PUNTUAL`
- `PAGO_RECURRENTE`

La descripción de cada Opportunity incluye, cuando Mercado Pago los informa:

- Payment ID.
- Authorized Payment ID, si Mercado Pago lo entrega separado.
- Preapproval o Subscription ID.
- External reference.
- Estado y detalle del estado.
- Importe y moneda.
- Medio y tipo de pago.
- Cantidad de cuotas.
- Fecha informada por Mercado Pago.

El Contact se relaciona con la Opportunity mediante `OpportunityContactRole`.

### Campos personalizados

El DNI se crea en el objeto `Contact`:

| Etiqueta | Nombre al crear | API Name esperado | Tipo |
|---|---|---|---|
| DNI | `DNI` | `DNI__c` | Texto, 20 |
| Mercado Pago Preapproval ID | `Mercado_Pago_Preapproval_ID` | `Mercado_Pago_Preapproval_ID__c` | Texto, 100 |
| Estado de suscripción | `Estado_Suscripcion` | `Estado_Suscripcion__c` | Texto, 30 |
| Fecha de cancelación | `Fecha_Cancelacion_Suscripcion` | `Fecha_Cancelacion_Suscripcion__c` | Fecha/Hora |

Al crear campos en Salesforce no se escribe `__c`; Salesforce agrega el sufijo automáticamente. En WordPress se debe pegar posteriormente el API Name final.

### Campos opcionales de Opportunity

Se pueden crear campos personalizados en Salesforce y configurar sus API Names desde WordPress:

| Etiqueta | Nombre al crear | API Name esperado | Tipo |
|---|---|---|---|
| Mercado Pago Payment ID | `Mercado_Pago_Payment_ID` | `Mercado_Pago_Payment_ID__c` | Texto, 100 |
| Mercado Pago Preapproval ID | `Mercado_Pago_Preapproval_ID` | `Mercado_Pago_Preapproval_ID__c` | Texto, 100 |
| External Reference | `External_Reference` | `External_Reference__c` | Texto, 150 |
| Tipo de pago | `Tipo_de_Pago` | `Tipo_de_Pago__c` | Texto, 50 |

Los valores predeterminados de `Opportunity.Type` son:

- `Donación puntual`
- `Donación recurrente`

Ambos valores deben existir en el picklist de Salesforce y coincidir exactamente.

## Desarrollo local con ngrok

Para este proyecto de Local, el puerto HTTP es `10005`:

```powershell
ngrok http 10005 --host-header=sandbox-modulo-sanitario.local
```

Luego usar:

```text
https://tu-subdominio.ngrok-free.app/wp-json/donacion/v1/webhook
```

## Seguridad

- No subir credenciales ni exportaciones de base de datos con secretos a Git.
- Los tokens de Mercado Pago y Salesforce se utilizan del lado del servidor.
- En producción se recomienda mantener `WP_DEBUG` desactivado.

## Versión

Versión actual: **1.1.0**

### Cambios principales de 1.1.0

- Validación de campos y picklists de Salesforce desde el panel.
- Deduplicación segura de Contact por email y DNI.
- Clasificación de Opportunities puntuales y recurrentes.
- Registro de IDs y detalles recibidos desde Mercado Pago.
- Sincronización en Contact de estados de suscripción, pausas y cancelaciones.
- Registro opcional de Preapproval ID, estado y fecha de cancelación.
- Retornos automáticos sin depender de una página `/gracias`.
- Guías integradas para Salesforce y Mercado Pago Developers.
- Mejoras responsive y compatibilidad con ngrok.

## Licencia

GPL-2.0
