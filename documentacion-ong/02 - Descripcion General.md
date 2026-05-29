## 2.1 Descripción funcional

El sistema desarrollado consiste en una plataforma de captación de donaciones integrada dentro de un entorno WordPress existente, mediante un plugin personalizado que extiende las capacidades del sitio web de la ONG.

El objetivo principal del sistema es permitir la recolección estructurada de información de potenciales donantes, facilitando posteriormente el procesamiento de donaciones a través de pasarelas de pago externas.

A diferencia de un sistema de gestión financiera completo, la plataforma no administra directamente fondos ni realiza conciliaciones contables, sino que actúa como un intermediario entre el usuario, la ONG y los proveedores de pago.

El sistema permite configurar dinámicamente el formulario de donación, incluyendo campos visibles, textos informativos y parámetros de integración con servicios externos.

---

## 2.2 Flujo general del sistema

El flujo de funcionamiento del sistema se compone de una serie de etapas secuenciales que permiten guiar al usuario desde el ingreso al formulario hasta la finalización del proceso de donación:

1. El usuario accede al formulario de donación dentro del sitio WordPress.
    
2. Completa sus datos personales en el formulario.
    
3. El sistema registra la información del usuario en Airtable como repositorio de tipo CRM.
    
4. El usuario selecciona el método de pago disponible.
    
5. El sistema genera la redirección hacia la pasarela de pago correspondiente.
		
6. El usuario completa la transacción en la plataforma externa.
    
7. La pasarela de pago devuelve el resultado del proceso al sistema.
    
8. El sistema redirige al usuario a una pantalla final de confirmación o error dentro del sitio.
    

---

## 2.3 Componentes del sistema

El sistema se compone de los siguientes elementos principales:

### Frontend (WordPress)

Interfaz de usuario donde se presenta el formulario de donación. Es la capa encargada de la interacción directa con el donante.

### Plugin personalizado

Módulo desarrollado en PHP y JavaScript que extiende WordPress y centraliza la lógica del sistema, incluyendo:

- configuración del formulario,
    
- procesamiento de datos,
    
- integración con servicios externos,
    
- gestión del flujo de donación.
    

### Airtable (CRM liviano)

Se utiliza como sistema de almacenamiento estructurado para registrar los datos de los donantes, funcionando como una base de datos externa de tipo CRM.

### Pasarelas de pago

Servicios externos encargados del procesamiento de pagos. En la versión actual del sistema se encuentra integrada la plataforma Mercado Pago, con posibilidad de extender a otros proveedores en el futuro.

---

## 2.4 Características generales del sistema

- Sistema modular basado en WordPress mediante plugin propio.
    
- Configuración dinámica del formulario de donación.
    
- Integración con servicios externos de pago.
    
- Registro centralizado de datos de donantes en Airtable.
    
- Arquitectura desacoplada entre captura de datos y procesamiento de pagos.
    
- Diseño orientado a extensibilidad para futuras integraciones.
    

---

## 2.5 Naturaleza del sistema

El sistema no constituye una plataforma de gestión financiera integral, sino una solución de captación de datos y derivación a sistemas externos de pago.

Su principal valor radica en:

- la flexibilidad de configuración,
    
- la integración de múltiples servicios,
    
- y la centralización de información de potenciales donantes para su posterior gestión por parte de la ONG.