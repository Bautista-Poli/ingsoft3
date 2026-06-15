## 4.1 Arquitectura general

El sistema está diseñado bajo una arquitectura modular basada en WordPress mediante un plugin personalizado, el cual actúa como núcleo de la lógica del negocio. Esta arquitectura se complementa con servicios externos encargados del almacenamiento de datos y procesamiento de pagos.

La solución puede describirse como una arquitectura híbrida compuesta por:

- Un frontend integrado en WordPress.
    
- Un backend lógico implementado en un plugin desarrollado en PHP y JavaScript.
    
- Servicios externos para persistencia de datos y pagos.
    

---

## 4.2 Componentes del sistema

### [WordPress ](https://wordpress.org/documentation/)(plataforma base)

El sistema se ejecuta sobre WordPress, que actúa como CMS principal y contenedor del plugin desarrollado.

---
### [Plugin personalizado ](https://github.com/Bautista-Poli/ingsoft3/releases)(núcleo del sistema)

El plugin desarrollado en PHP y JavaScript es el componente central del sistema. Sus responsabilidades incluyen:

- Renderización del formulario de donación.
    
- Configuración dinámica de campos y textos.
    
- Gestión del flujo de donación.
    
- Integración con servicios externos.
    
- Comunicación con APIs externas.
    

---

### [Salesforce](https://developer.salesforce.com/) (CRM)

Salesforce se utiliza como sistema de gestión de relaciones con donantes (CRM). Permite registrar, organizar y administrar la información de contactos y contribuciones, centralizando los datos recolectados durante el proceso de donación y facilitando su seguimiento dentro de una plataforma especializada.

---

### [Mercado Pago](https://www.mercadopago.com.ar/developers/es) (pasarela de pagos)

Mercado Pago es la pasarela de pagos utilizada para procesar transacciones. El sistema genera la redirección del usuario hacia la plataforma de checkout, donde se realiza el pago.

---

### [LocalWP](https://localwp.com/help-docs/) (entorno de desarrollo)

LocalWP es la herramienta utilizada para la ejecución del entorno de desarrollo local de WordPress, permitiendo simular el comportamiento del sistema sin necesidad de hosting externo.

---

## 4.3 Flujo de arquitectura del sistema

El sistema sigue un flujo desacoplado entre captura de datos, gestión de relaciones con donantes y procesamiento de pagos:

1. El usuario accede al formulario en WordPress.
    
2. El plugin procesa la información ingresada.
    
3. Los datos del usuario se envían a Salesforce, donde se crea o actualiza un Contact.
    
4. El usuario selecciona un método de pago.
    
5. El plugin genera la redirección hacia la pasarela de pago correspondiente.
    
6. El usuario completa el pago en la plataforma externa.
    
7. En caso de corresponder, el sistema registra una Opportunity asociada al Contact dentro de Salesforce.
    
8. La pasarela devuelve el resultado del pago al sistema.
    
9. El usuario es redirigido a una pantalla final de confirmación o error.
	

---

## 4.4 Diagrama lógico del sistema

El sistema puede representarse como una arquitectura de flujo de datos donde WordPress funciona como orquestador central:

- **WordPress + Plugin MS Donaciones** → Orquestación del flujo de negocio y gestión de integraciones.
    
- **Salesforce** → Gestión de contactos y oportunidades de donación (CRM).
    
- **Mercado Pago** → Procesamiento y validación de pagos.
    
- **Usuario** → Interacción principal del sistema.

---

## 4.5 Características arquitectónicas

- Arquitectura desacoplada entre captura de datos y procesamiento de pagos.
    
- Integración con servicios externos mediante APIs.
    
- Diseño extensible para futuras pasarelas de pago.
    
- Separación entre lógica de negocio (plugin) y servicios externos.
    
- Persistencia externa independiente del CMS.
    

---

## 4.6 Consideraciones de diseño

La arquitectura fue diseñada con el objetivo de:

- Reducir la complejidad del sistema dentro de WordPress.
    
- Permitir la integración futura de múltiples pasarelas de pago.
    
- Externalizar la gestión de relaciones con donantes mediante una plataforma CRM especializada (Salesforce).
    
- Centralizar la información de contactos y oportunidades de donación en un sistema institucional.
    
- Mantener una estructura modular, escalable y mantenible.