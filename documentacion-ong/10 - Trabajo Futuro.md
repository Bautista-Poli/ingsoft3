## 10.1 Extensión de pasarelas de pago

En la versión actual del sistema, la integración de pagos se encuentra implementada principalmente con Mercado Pago. Como línea de mejora futura, se contempla la incorporación de nuevas pasarelas de pago con el objetivo de ampliar las opciones disponibles para los usuarios.

Entre las posibles integraciones se incluyen:

- Tarjetas internacionales mediante proveedores alternativos.
    
- Sistemas bancarios locales.
    
- Nuevas plataformas de pago digital.
    
- Pasarelas con soporte nativo para suscripciones y donaciones recurrentes.
    

Esto permitiría aumentar la flexibilidad del sistema y adaptarlo a diferentes contextos geográficos y financieros.

---

## 10.2 Implementación de recurrencia de pagos

Actualmente, la funcionalidad de donaciones recurrentes (mensuales) no se encuentra completamente integrada en el flujo de pago.

Como mejora futura, se propone:

- Implementar soporte nativo de suscripciones en las pasarelas compatibles.
    
- Gestionar estados de recurrencia dentro del sistema.
    
- Permitir seguimiento de donaciones periódicas desde Salesforce.
    
- Automatizar la actualización de oportunidades y registros asociados a donantes recurrentes.
    

---

## 10.3 Fortalecimiento de seguridad

El sistema requiere mejoras en términos de seguridad para su despliegue en producción. Las principales áreas de mejora incluyen:

- Implementación de sanitización avanzada de datos en backend.
    
- Uso de variables de entorno para credenciales sensibles.
    
- Integración de mecanismos anti-bot (CAPTCHA).
    
- Registro de logs de actividad y auditoría.
    
- Validaciones redundantes entre frontend y backend.
    
- Fortalecimiento de la gestión de credenciales de Salesforce y Mercado Pago.
    

---

## 10.4 Mejora de la experiencia de administración

Se propone la incorporación de un panel administrativo más completo dentro del plugin de WordPress, que permita:

- Visualización centralizada de configuraciones.
    
- Estado de conexiones con servicios externos.
    
- Estadísticas básicas de uso del formulario.
    
- Gestión más intuitiva de configuraciones del sistema.
    
- Herramientas de diagnóstico para las integraciones con Salesforce y Mercado Pago.
    

---

## 10.5 Automatización de procesos

Como evolución del sistema, se plantea la automatización de ciertos procesos, tales como:

- Notificaciones automáticas por email a la ONG.
    
- Confirmaciones automáticas de donación para los usuarios.
    
- Automatización de procesos de seguimiento dentro de Salesforce.
    
- Creación y actualización automática de registros CRM según el estado de las donaciones.
    
- Flujos de seguimiento y segmentación de donantes.
    

---

## 10.6 Escalabilidad del sistema

La arquitectura actual permite futuras expansiones sin modificaciones estructurales profundas. Sin embargo, se identifican mejoras potenciales para soportar mayor escala:

- Optimización de llamadas a APIs externas.
    
- Implementación de caché en ciertas operaciones.
    
- Optimización de integraciones con Salesforce y pasarelas de pago.
    
- Migración parcial a servicios backend dedicados si el volumen de usuarios aumenta.
    

---

## 10.7 Consideraciones finales

El sistema se encuentra en una etapa de desarrollo intermedia, por lo que estas mejoras representan líneas evolutivas naturales del proyecto.

La arquitectura modular implementada facilita la incorporación progresiva de nuevas funcionalidades sin afectar el funcionamiento actual.

La adopción de Salesforce como plataforma CRM proporciona una base sólida para futuras capacidades de gestión, automatización y seguimiento de donantes, permitiendo que el sistema evolucione junto con las necesidades operativas de la organización.