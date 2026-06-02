# Guía de conexión con Salesforce y Mercado Pago — ms-donaciones

Esta guía explica cómo conectar el plugin con Salesforce y Mercado Pago desde cero.
Al terminar:
- Cada vez que alguien complete el Paso 1, sus datos aparecen como un **Contacto** en Salesforce.
- Cuando un pago se aprueba en Mercado Pago, se crea una **Oportunidad** en Salesforce vinculada al contacto.

---

## Parte 1 — Salesforce

### Paso 1 — Crear una cuenta de Salesforce Developer Edition (gratis)

Si ya tenés una cuenta de Salesforce, saltá al Paso 2.

1. Entrá a: https://developer.salesforce.com/signup
2. Completá el formulario → revisá tu email → verificá la cuenta
3. Elegí una contraseña → accedés a tu org de desarrollo

---

### Paso 2 — Encontrar las URLs de tu org

- **URL de la interfaz**: `https://tudominio.develop.lightning.force.com`
- **URL de login/API** (la que usa WordPress): `https://tudominio.develop.my.salesforce.com`

Ejemplo:
```
Interfaz:  https://orgfarm-5780dccadb-dev-ed.develop.lightning.force.com/...
API:       https://orgfarm-5780dccadb-dev-ed.develop.my.salesforce.com
```

---

### Paso 3 — Crear la aplicación cliente externa

1. En Salesforce → engranaje ⚙️ → **Configuración**
2. En el buscador escribí `aplicacion cliente` → clic en **Gestor de aplicaciones cliente externas**
3. Clic en **Nueva aplicación cliente externa**
4. Completá:
   - **Nombre**: `ms-donaciones`
   - **Email de contacto**: tu email
5. En **Configuración de OAuth**:
   - Tildá **Habilitar configuración de OAuth**
   - **URL de devolución de llamada**: `https://localhost`
   - **Alcances de OAuth seleccionados**: agregá:
     - `Gestionar datos de usuario a través de las API (api)`
     - `Acceso completo (full)`
     - `Realizar solicitudes en cualquier momento (refresh_token, offline_access)`
6. En **Activación de flujos**:
   - Tildá **Activar flujo de credenciales de cliente**
7. Clic en **Guardar**

> ⏳ Salesforce puede tardar hasta 10 minutos en activar la app.

---

### Paso 4 — Asignar usuario al flujo de credenciales

1. Abrí la app → pestaña **Políticas**
2. En **Flujos de OAuth** → **Flujo de credenciales de cliente** → **Ejecutar como** → seleccioná tu usuario admin
3. En **Autorización de aplicación** → **Relajación de IP** → seleccioná **Relajar restricciones de IP**
4. Guardá

---

### Paso 5 — Habilitar el flujo OAuth org-wide

1. En Setup → buscá `OAuth` → **Configuración de OAuth y OpenID Connect**
2. Activá **Permitir flujos de contraseña-nombre de usuario de OAuth**
3. Guardá

---

### Paso 6 — Copiar las credenciales

1. **Gestor de aplicaciones cliente externas** → clic en tu app
2. Pestaña **Configuración** → **Clave y secreto de consumidor**
3. Copiá la **Clave de consumidor** y la **Pregunta secreta de consumidor**

---

### Paso 7 — Configurar WordPress

1. WordPress → **Donaciones MS** → tab **Datos personales a CRM**
2. Completá:

| Campo | Qué poner |
|-------|-----------|
| Activar envío a Salesforce | ✅ tildado |
| Usar sandbox de Salesforce | Sin tildar |
| URL/Dominio de login | Tu URL de API. Ej: `https://orgfarm-xxx.develop.my.salesforce.com` |
| Consumer Key | Clave de consumidor del Paso 6 |
| Consumer Secret | Pregunta secreta de consumidor del Paso 6 |
| API Name: Nombre | `FirstName` |
| API Name: Apellido | `LastName` |
| API Name: Email | `Email` |
| API Name: Telefono | `MobilePhone` |
| Stage de Oportunidad | `Closed Won` |

3. Clic en **Guardar** → **Probar conexión con Salesforce** → debe decir **"Conexión válida"**

---

## Parte 2 — Mercado Pago

### Paso 1 — Obtener el Access Token

1. Entrá a https://mercadopago.com/developers e iniciá sesión
2. Creá una aplicación → elegí **Checkout Pro**
3. En **Credenciales de prueba** copiá el **Access Token**

### Paso 2 — Configurar el webhook

Para recibir notificaciones de pagos aprobados necesitás una URL pública.

**En desarrollo** (localhost) usá ngrok:
```bash
brew install ngrok
ngrok config add-authtoken TU_TOKEN_DE_NGROK
ngrok http --host-header=ms-donaciones.local 10008
```
La URL pública aparece en la terminal. Ejemplo: `https://xyz.ngrok-free.dev`

> ⚠️ **Importante:** Con la cuenta gratuita de ngrok, la URL cambia cada vez que reiniciás ngrok. Cada vez que apagás la compu o cerrás la terminal tenés que:
> 1. Volver a correr el comando de ngrok
> 2. Ver la nueva URL en `localhost:4040`
> 3. Actualizar **Donaciones MS → Mercado Pago → URL de Webhook** en WordPress
> 4. Actualizar la URL en el portal de MP Developers → Webhooks
>
> **Para evitar esto**, creá un dominio estático gratuito en ngrok:
> 1. Andá a **ngrok.com → Dashboard → Domains** → creá un dominio fijo
> 2. Usá siempre ese dominio:
> ```bash
> ngrok http --host-header=ms-donaciones.local --domain=TU_DOMINIO.ngrok-free.app 10008
> ```
> Así la URL nunca cambia.

**En producción** la URL es: `https://tu-sitio.com`

En el portal de MP → tu app → **Webhooks** → **Configurar notificaciones**:
- **URL para prueba**: `https://xyz.ngrok-free.dev/wp-json/donacion/v1/webhook`
- **Eventos**: tildá **Pagos**
- Guardá

### Paso 3 — Configurar WordPress

1. WordPress → **Donaciones MS** → tab **Mercado Pago**
2. Completá:

| Campo | Qué poner |
|-------|-----------|
| Access Token | El token del Paso 1 |
| URL de Webhook | `https://xyz.ngrok-free.dev/wp-json/donacion/v1/webhook` |

3. Clic en **Guardar** → **Probar conexión con Mercado Pago** → debe decir **"Conexión válida"**

---

## Verificar en Salesforce

**Contactos** (se crean al completar el Paso 1):
```sql
SELECT Id, FirstName, LastName, Email, CreatedDate
FROM Contact
ORDER BY CreatedDate DESC
```

**Oportunidades** (se crean cuando un pago se aprueba):
```sql
SELECT Id, Name, Amount, CloseDate, StageName
FROM Opportunity
ORDER BY CreatedDate DESC
```

---

## Errores comunes

| Error | Solución |
|-------|----------|
| `request not supported on this domain` | La URL de login usa `lightning.force.com` en vez de `my.salesforce.com` |
| `no valid scopes defined` | Falta el scope `api` en la app de Salesforce |
| `authentication failure` | Consumer Key o Secret incorrectos, o flujo de credenciales sin usuario asignado |
| Opportunity no se crea | ngrok no está corriendo o la URL de webhook no está configurada en WordPress |
| Opportunity duplicada | Ya corregido — el transient se guarda al inicio del procesamiento |
