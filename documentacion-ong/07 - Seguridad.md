## 7.1 Estado actual de seguridad

El sistema, en su estado actual de desarrollo, incorpora únicamente mecanismos básicos de validación y control de datos, sin contar aún con un esquema completo de seguridad avanzado implementado a nivel de producción.

Dado que la arquitectura se basa en integraciones con servicios externos (Airtable y Mercado Pago), parte de la seguridad del sistema depende de las garantías provistas por dichos proveedores.

---

## 7.2 Validación de datos

El sistema realiza validaciones básicas sobre los datos ingresados en el formulario de donación con el objetivo de asegurar la consistencia de la información antes de su procesamiento.

Estas validaciones incluyen:

- Verificación de campos obligatorios.
    
- Validación de formato de correo electrónico.
    
- Control de campos numéricos (teléfono).
    
- Validación mínima de campos vacíos antes del envío a servicios externos.
    

Estas validaciones se implementan principalmente en el frontend (JavaScript) y son complementadas por lógica en el backend del plugin.

---

## 7.3 Manejo de credenciales

El sistema requiere credenciales para la integración con servicios externos:

- API Key de Airtable.
    
- Token de acceso de Mercado Pago.
    

Estas credenciales son configuradas dentro del plugin de WordPress y se utilizan para autenticar las solicitudes hacia las APIs correspondientes.

Actualmente, el manejo de credenciales no incluye mecanismos avanzados de encriptación o gestión de secretos, por lo que se recomienda su configuración en entornos controlados.

---

## 7.4 Riesgos identificados

Se identifican los siguientes riesgos asociados al estado actual del sistema:

- **Exposición de credenciales:** almacenamiento de tokens dentro de la configuración del plugin.
    
- **Validación insuficiente en backend:** dependencia parcial de validaciones del frontend.
    
- **Dependencia de servicios externos:** el sistema depende del correcto funcionamiento de Airtable y Mercado Pago.
    
- **Falta de mecanismos anti-spam:** posibilidad de envíos automatizados al formulario.
    
- **Ausencia de control avanzado de sesiones o autenticación:** el formulario es de acceso público.
    

---

## 7.5 Consideraciones sobre transacciones externas

El sistema no procesa ni almacena información financiera sensible directamente. Todo el procesamiento de pagos se realiza en plataformas externas como Mercado Pago.

Esto implica que:

- Los datos de tarjetas o cuentas bancarias no son manipulados por el sistema.
    
- La seguridad de la transacción depende de la pasarela de pago utilizada.
    
- El sistema únicamente gestiona el flujo de redirección y retorno de estado.
    

Documentación oficial de Mercado Pago:  
[https://www.mercadopago.com.ar/developers/es](https://www.mercadopago.com.ar/developers/es)

---

## 7.6 Recomendaciones de mejora

Para futuras iteraciones del sistema se recomienda implementar:

- Sanitización avanzada de entradas en backend.
    
- Validaciones redundantes entre frontend y backend.
    
- Implementación de CAPTCHA o mecanismos anti-bot.
    
- Gestión segura de credenciales mediante variables de entorno.
    
- Registro de logs de seguridad y auditoría de eventos.
    
- Uso de HTTPS obligatorio en entornos de producción.
    

---

## 7.7 Consideraciones generales

Si bien el sistema no fue diseñado inicialmente como una plataforma financiera crítica, la naturaleza del flujo de donaciones requiere considerar prácticas de seguridad adecuadas, especialmente en lo relativo a la protección de datos personales y la integridad de las integraciones externas.