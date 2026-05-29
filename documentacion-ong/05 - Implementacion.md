## 5.1 Estructura del plugin

El sistema se implementa mediante un plugin personalizado de WordPress desarrollado en PHP y JavaScript. Este plugin constituye el núcleo funcional de la aplicación, ya que encapsula toda la lógica relacionada con el formulario de donación, la configuración dinámica y la integración con servicios externos.

El plugin se organiza en los siguientes módulos funcionales:

- **Textos visibles:** administración de títulos, descripciones y mensajes mostrados al usuario dentro del formulario.
    
- **Media y links:** configuración de imágenes, recursos multimedia y enlaces externos utilizados en la interfaz.
    
- **Datos personales a CRM:** gestión y envío de la información personal del donante hacia Airtable.
    
- **Montos:** configuración de los importes de donación disponibles para el usuario.
    
- **Impacto:** administración de mensajes o descripciones asociadas al impacto de cada monto de donación.
    
- **Mercado Pago:** integración y configuración de la pasarela de pagos Mercado Pago.
    
- **Transferencia:** configuración de datos e instrucciones para donaciones mediante transferencia bancaria.
    

---

## 5.2 Configuración dinámica

Una de las características principales del plugin es la posibilidad de configurar dinámicamente distintos aspectos del formulario sin necesidad de modificar el código fuente.

Esto incluye:

- Textos visibles en la interfaz del formulario.
    
- Campos habilitados o deshabilitados.
    
- Parámetros de conexión con servicios externos.
    
- Tokens o credenciales de integración.
    

Esta flexibilidad permite adaptar el sistema a diferentes campañas o necesidades de la ONG sin intervención técnica directa.

---

## 5.3 Implementación del frontend

El frontend del sistema se implementa mediante JavaScript embebido en el entorno de WordPress, complementando la renderización del formulario.

Sus responsabilidades principales son:

- Manejo de interacción del usuario.
    
- Validación básica de campos del formulario.
    
- Gestión de selección de métodos de pago.
    
- Comunicación con el backend del plugin mediante solicitudes HTTP.
    

La interfaz está diseñada para ser simple, accesible y compatible con el sitio existente de WordPress.

---

## 5.4 Implementación del backend

El backend del sistema está desarrollado en PHP utilizando las capacidades nativas de WordPress para la creación de plugins.

Sus principales funciones incluyen:

- Procesamiento de datos enviados desde el frontend.
    
- Validación de información del usuario.
    
- Coordinación del flujo de donación.
    
- Integración con APIs externas (Airtable y Mercado Pago).
    
- Generación de redirecciones hacia pasarelas de pago.
    

---

## 5.5 Integración con Airtable

El sistema utiliza Airtable como repositorio externo de datos tipo CRM.

La integración se realiza mediante su API REST, permitiendo almacenar la información de los donantes de manera estructurada.

Documentación oficial:  
[https://airtable.com/developers/web/api/introduction](https://airtable.com/developers/web/api/introduction)

Los datos enviados incluyen información personal del donante, tales como nombre, apellido, DNI, correo electrónico y número de teléfono.

---

## 5.6 Integración con Mercado Pago

La integración con Mercado Pago permite la generación de links de pago y la redirección del usuario hacia la plataforma de checkout externo.

Documentación oficial:  
[https://www.mercadopago.com.ar/developers/es](https://www.mercadopago.com.ar/developers/es)

El flujo de implementación consiste en:

1. Generación de una preferencia de pago desde el backend del plugin.
    
2. Redirección del usuario a la interfaz de pago de Mercado Pago.
    
3. Procesamiento de la transacción en la plataforma externa.
    
4. Retorno del resultado (éxito o error) al sistema WordPress.
    

---

## 5.7 Flujo de implementación general

El comportamiento del sistema en tiempo de ejecución puede resumirse de la siguiente manera:

1. El usuario completa el formulario en WordPress.
    
2. El frontend valida y envía los datos al backend del plugin.
    
3. El backend registra la información en Airtable.
    
4. El usuario selecciona el método de pago.
    
5. Se genera la integración con la pasarela correspondiente.
    
6. El usuario es redirigido al checkout externo.
    
7. Se procesa el pago.
    
8. El usuario retorna al sistema con el resultado de la operación.
    

---

## 5.8 Consideraciones de implementación

- El sistema se basa en una arquitectura extensible mediante módulos dentro del plugin.
    
- Las integraciones externas están desacopladas del núcleo del sistema.
    
- El uso de APIs permite la futura incorporación de nuevos proveedores de pago.
    
- La configuración dinámica reduce la necesidad de cambios en el código para ajustes operativos.