# Donaciones mensuales (suscripciones) — ms-donaciones

Esta guía explica el feature de **donaciones mensuales** del plugin: qué hace, cómo está
construido y cómo configurarlo y probarlo de punta a punta.

> **Requisito previo:** este feature se apoya en el setup base de Salesforce + Mercado Pago.
> Si todavía no lo hiciste, seguí primero **[SALESFORCE_SETUP.md](SALESFORCE_SETUP.md)** (creación
> de la app de Salesforce, credenciales, Access Token de MP, webhook). Acá solo se cubre lo
> **específico de las suscripciones**.

---

## 1. Qué hace

En el formulario de donación, el donante puede elegir la frecuencia **Única** o **Mensual**.

- **Única** → flujo Checkout Pro de siempre (sin cambios). Puede pagar con cualquier método.
- **Mensual** → se crea una **suscripción** en Mercado Pago (API *Preapproval*) que debita
  automáticamente todos los meses. En mensual, el **único método disponible es Mercado Pago**
  (las demás tarjetas y la transferencia se ocultan).

En ambos casos, cuando el pago/suscripción se concreta, se crea una **Oportunidad** en Salesforce
vinculada al **Contacto** del donante, **diferenciada por tipo**:

| Tipo | Name de la Oportunidad | Description |
|------|------------------------|-------------|
| Única | `Donacion MP Unica - <nombre>` | `Donacion única via Mercado Pago. Payment ID: ...` |
| Mensual | `Donacion MP Mensual - <nombre>` | `Donacion mensual (suscripción) via Mercado Pago. Payment ID: ...` |

---

## 2. Cómo funciona (arquitectura)

```
Formulario (frecuencia = Mensual)
   │
   ├─ POST /wp-json/donacion/v1/crear-suscripcion
   │     → crea un Preapproval en MP (https://api.mercadopago.com/preapproval)
   │     → guarda los datos del donante en un transient (12 h)
   │     → devuelve init_point y redirige al donante al checkout de MP
   │
   ▼
Donante autoriza el débito mensual en Mercado Pago
   │
   ├─(A) Vuelve por back_url ──► GET /wp-json/donacion/v1/retorno-suscripcion?preapproval_id=...
   │                              → verifica que esté "authorized"
   │                              → crea Contacto + Oportunidad en Salesforce
   │                              → redirige a la página de gracias
   │
   └─(B) MP notifica ──────────► POST /wp-json/donacion/v1/webhook
                                  (topic subscription_preapproval / subscription_authorized_payment)
                                  → misma lógica: Contacto + Oportunidad en Salesforce
```

**Por qué dos caminos (A y B):**
- El **retorno (A)** procesa la suscripción al instante cuando el donante vuelve del checkout.
  Es confiable incluso en sandbox, donde MP a veces **no** envía el webhook automáticamente.
- El **webhook (B)** cubre los **cobros mensuales siguientes** (mes 2, 3, …), donde no hay
  retorno de usuario, y la redundancia de la autorización inicial en producción.

**Sin duplicados:** ambos caminos pasan por un **lock de idempotencia**
(`ms_don_lock_sub_<id>` / `ms_don_lock_subpay_<id>`). Si los dos disparan para el mismo evento,
el primero crea la Oportunidad y el segundo se ignora.

### Endpoints REST involucrados

| Método | Ruta | Para qué |
|--------|------|----------|
| POST | `/wp-json/donacion/v1/crear-suscripcion` | Crea el Preapproval y devuelve el `init_point` |
| GET  | `/wp-json/donacion/v1/retorno-suscripcion` | `back_url`: procesa la suscripción al volver del checkout |
| POST | `/wp-json/donacion/v1/webhook` | Notificaciones de MP (pagos y suscripciones) |

### Datos de la suscripción

El Preapproval se crea **indefinido** (sin fecha de fin): debita **una vez por mes**
hasta que se cancele.

```jsonc
{
  "reason": "Donación Módulo Sanitario",
  "external_reference": "suscripcion-...",
  "payer_email": "donante@email.com",
  "back_url": "https://<host-publico>/wp-json/donacion/v1/retorno-suscripcion",
  "notification_url": "https://<host-publico>/wp-json/donacion/v1/webhook",
  "auto_recurring": {
    "frequency": 1,
    "frequency_type": "months",
    "transaction_amount": 5000,
    "currency_id": "ARS"
  }
}
```

> El `back_url` y el `notification_url` se arman automáticamente con el host de la **URL de Webhook**
> configurada en WordPress (ngrok en desarrollo, tu dominio real en producción). MP exige que el
> `back_url` sea **HTTPS**.

---

## 3. Configuración en Mercado Pago

### 3.1 — Habilitar el producto Suscripciones

En [tu panel de MP](https://www.mercadopago.com.ar/developers/panel/app) usá **una sola aplicación**.
El Access Token autoriza por **cuenta**, no por producto: el mismo token sirve para Checkout Pro
y para Suscripciones. El radio "¿Qué producto estás integrando?" es solo una etiqueta; no
restringe la API ni los webhooks.

> Si creaste dos aplicaciones separadas (una para Checkout Pro y otra para Suscripciones),
> quedate con **una** y usá su token para todo.

### 3.2 — Webhook con los dos topics

En tu app → **Webhooks → Configurar notificaciones**:

- **URL**: `https://<tu-host>/wp-json/donacion/v1/webhook`
- **Eventos** (tildá los dos):
  - ✅ **Pagos** (`payment`) — para donaciones únicas
  - ✅ **Planes y suscripciones** (`subscription_preapproval`, `subscription_authorized_payment`)

### 3.3 — WordPress

En **Donaciones MS → Mercado Pago**:

| Campo | Qué poner |
|-------|-----------|
| Access Token | El token de tu app (prueba `TEST-...`/`APP_USR-...test`, o producción `APP_USR-...`) |
| URL de Webhook | `https://<tu-host>/wp-json/donacion/v1/webhook` |
| URL de éxito | Una URL **HTTPS válida** (ej. `https://modulosanitario.org/gracias`). **No la dejes vacía** |

> La **URL de éxito** es a dónde se redirige al donante después de donar, y MP exige que el
> `back_url` de la suscripción sea HTTPS. Si la dejás vacía, el donante termina en el sitio local
> (con error de certificado en desarrollo).

### 3.4 — (Opcional) Mapear el tipo al campo `Type` de Salesforce

Por defecto la diferenciación va en el **Name** y la **Description** de la Oportunidad, sin tocar
picklists. Si querés además mapear el tipo al campo estándar **`Type`** de Opportunity, configurá
en las opciones del plugin (`ms_donaciones_labels`):

- `sf_opp_type_mensual` → ej. `"Donación recurrente"`
- `sf_opp_type_unico` → ej. `"Donación única"`

Si los dejás vacíos, el campo `Type` no se envía (evita choques con picklists restringidos).

---

## 4. Probar en sandbox (paso a paso)

### 4.1 — ngrok (exponer el WordPress local)

LocalWP sirve el sitio en un dominio `.local` por un puerto de nginx. Averiguá el puerto del sitio
y exponelo **reescribiendo el Host** (clave para que nginx sirva el sitio correcto):

```bash
ngrok http <PUERTO_NGINX> --host-header=<tu-sitio>.local
# ej: ngrok http 10004 --host-header=prueba-modulo-mensual.local
```

> El puerto de nginx lo ves en la config de LocalWP del sitio (no es el 80). La URL pública aparece
> en la consola de ngrok y en `http://localhost:4040`.
>
> Con ngrok free te dan **un dominio estático** por cuenta — usalo siempre así no tenés que
> reconfigurar las URLs cada vez. Si la URL cambia, actualizá la **URL de Webhook** en el plugin
> y en el panel de MP.

> ⚠️ **Aviso de ngrok free:** la **primera vez** que el navegador entra a una URL de ngrok
> (por ejemplo al volver del checkout), aparece una pantalla intermedia de ngrok que dice
> *"You are about to visit..."*. Hacé click en **"Visit Site"** para continuar. Pasa solo en
> desarrollo con ngrok free y por lo general una sola vez por sesión del navegador. En producción
> (dominio real, sin ngrok) **no aparece**.

### 4.2 — Usuarios de prueba de MP

En el panel → **Cuentas de prueba** → creá (o usá) **dos**:

- **Vendedor (Seller)** → de su app/credenciales sale el **Access Token** que va en el plugin.
- **Comprador (Buyer)** → su email es el que se usa para pagar.

> ⚠️ **El email NO es el "Usuario" que muestra el panel.** El panel muestra el *nickname*
> (`TESTUSER8755390473306374079`). El **email real** es:
>
> ```
> test_user_<numero>@testuser.com
> ```
>
> Es decir: al nickname le sacás `TESTUSER`, lo pasás a minúsculas y le ponés el prefijo
> `test_user_`. Ej: `TESTUSER8755390473306374079` → `test_user_8755390473306374079@testuser.com`.
> (Si usás el nickname como email, MP devuelve **HTTP 500 Internal server error**.)

### 4.3 — Flujo completo

1. **Asegurate de pagar como el comprador de prueba, no con tu cuenta real de MP.** Tenés dos formas:
   - Abrir una **ventana de incógnito** (lo más cómodo: no arrastra tu sesión real), o
   - **Cerrar sesión** de tu cuenta real de MP e **iniciar sesión con la del comprador de prueba**.
2. En el **formulario**, completá el email con el del **comprador de prueba**
   (`test_user_<numero>@testuser.com`).
3. Paso 2 → elegí **Mensual** → confirmá que **solo aparece Mercado Pago** → elegí monto.
4. En el checkout de MP, **logueate con el comprador de prueba** (nickname + contraseña del panel).
5. Pagá con una **tarjeta de prueba** (Tarjetas de prueba en el panel de MP). Ej:
   - Mastercard `5031 7557 3453 0604`, CVV `123`, vencimiento futuro, titular **APRO**, DNI `12345678`.
6. Al volver, deberías aterrizar en la **página de gracias** configurada.

### 4.4 — Verificar

**Forma principal (la que importa):**
**Salesforce** → pestaña **Opportunities** → debería aparecer `Donacion MP Mensual - <nombre>`,
vinculada al Contacto del donante. Con esto ya sabés que funcionó.

**Forma opcional (para desarrolladores): el log de WordPress.**
WordPress puede escribir mensajes internos del plugin en un archivo de log. Sirve **solo para
diagnosticar** qué pasó del lado del servidor (no es necesario para el uso normal; el donante y el
admin nunca lo tocan).

1. **Activarlo** (una vez): editá `wp-config.php` del sitio y agregá, antes de
   `/* That's all, stop editing! */`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```
2. **Leerlo**: los mensajes quedan en `wp-content/debug.log`. Tras una donación mensual exitosa
   deberías ver:
   ```
   Retorno suscripcion <id> status: authorized
   SF Opportunity created for payment <id>
   ```
   Si algo falla, acá aparece el motivo (ej. un error de Mercado Pago o de Salesforce).

> En **producción** conviene dejar el log **desactivado** (o sin `WP_DEBUG_DISPLAY`) para no
> exponer información ni llenar el disco. Es una herramienta de desarrollo/diagnóstico.

---

## 5. Cómo se ven las recurrencias en Salesforce

La suscripción es **indefinida**: MP cobra **todos los meses hasta que se cancele**. Cada cobro
mensual exitoso dispara el webhook `subscription_authorized_payment` y **crea una Oportunidad
nueva** marcada como "Mensual". Es decir:

- Una suscripción que dura 5 meses → **5 Oportunidades** "Mensual" (una por cobro real).

**Cómo se cancela:**
- El donante: desde su cuenta de MP → Suscripciones → Cancelar.
- La ONG: `PUT https://api.mercadopago.com/preapproval/{id}` con `{"status":"cancelled"}`, o desde el panel de MP.

> **Consideraciones del diseño actual** (a tener en cuenta para mejoras futuras):
> - **El primer mes NO se cobra dos veces.** MP debita **una sola vez**. Lo que pasa es que MP
>   manda **dos notificaciones distintas** ese mes (`subscription_preapproval` = suscripción
>   autorizada, y `subscription_authorized_payment` = primer cobro). Como cada notificación crea
>   una Oportunidad, podrían quedar **2 Oportunidades en Salesforce para un único cobro real**.
>   La implicancia es de **datos en el CRM** (un reporte mostraría el doble de monto ese mes), no
>   de dinero. El lock de idempotencia evita duplicados del *mismo* evento, pero no unifica
>   eventos distintos. Si esto molesta, se resuelve creando la Oportunidad solo en el cobro real
>   (`subscription_authorized_payment`) y no en la autorización.
> - La **cancelación no se refleja** en Salesforce: cuando un donante cancela, MP deja de cobrar
>   (no se crean más Oportunidades), pero no se marca la suscripción como cancelada en ningún lado.

---

## 6. Errores comunes (suscripciones)

| Error / síntoma | Causa | Solución |
|-----------------|-------|----------|
| `Both payer and collector must be real or test users` (HTTP 400) | El email del donante no es un usuario de prueba | Usá el email de un **comprador de prueba** de MP |
| `Internal server error` (HTTP 500) al crear la suscripción | Email mal formado (usaste el *nickname* `TESTUSER...` como email) | Usá el formato real `test_user_<numero>@testuser.com` |
| `Payer and collector cannot be the same user` (HTTP 400) | Pusiste el email del **vendedor** como pagador | Usá el email del **comprador**, distinto del vendedor |
| "Una de las partes con la que intentás hacer el pago es de prueba" | En el checkout estás logueado con tu cuenta **real** | Pagá logueado como el **comprador de prueba** (incógnito) |
| "Your connection is not private" (`.local`) al volver | Redirección al sitio local con certificado autofirmado / `URL de éxito` vacía | Configurá una **URL de éxito** HTTPS válida (en prod es tu dominio real con certificado) |
| La suscripción queda `authorized` pero no se crea la Oportunidad | En **sandbox** MP a veces no envía el webhook automático | Cubierto por el **retorno (back_url)**; verificá que la **URL de Webhook** del plugin tenga el host público correcto |
| ngrok recibe 0 requests | Puerto o Host header equivocado, o URL vieja en el panel de MP | `ngrok http <puerto-nginx> --host-header=<sitio>.local`; actualizá la URL en el plugin y en MP |
