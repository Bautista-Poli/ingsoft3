Esta sección detalla los requisitos necesarios para la instalación, configuración y ejecución del sistema de captación de donaciones en un entorno de desarrollo o pruebas.

---

## 3.1 Requisitos de entorno

Para poder ejecutar el sistema de manera local es necesario contar con las siguientes herramientas:

### LocalWP

Se requiere la instalación de LocalWP como entorno de desarrollo local para WordPress. Esta herramienta permite levantar una instancia funcional de WordPress de manera aislada sin necesidad de un servidor productivo.

---

### WordPress

El sistema se ejecuta como un plugin dentro de una[ instalación de WordPress.](https://localwp.com/) Por lo tanto, es necesario contar con un entorno WordPress activo, el cual puede ser gestionado directamente desde LocalWP.

---

### Repositorio del plugin

Es necesario descargar el código fuente del plugin desde el [repositorio correspondiente ](https://github.com/Bautista-Poli/ingsoft3/releases/tag/v0.1.16)en Git. Una vez descargado, el plugin debe ser instalado dentro de la carpeta de plugins de WordPress y activado desde el panel administrativo.

---

## 3.2 Requisitos de servicios externos

### Salesforce

Se requiere una cuenta en Salesforce para la gestión y almacenamiento de la información de los donantes. El sistema utiliza Salesforce como CRM principal, permitiendo registrar contactos y asociar oportunidades de donación dentro de una plataforma centralizada de administración y seguimiento.

Para realizar pruebas o implementar el sistema, es posible utilizar una cuenta gratuita de Salesforce Developer Edition. La configuración de la integración se encuentra detallada en el archivo `SALESFORCE_SETUP.md`.

---

### Mercado Pago

El sistema utiliza Mercado Pago como pasarela principal de pagos.

Es necesario contar con una [cuenta activa en Mercado Pago](https://www.mercadopago.com.ar/cuenta) para configurar las [credenciales de acceso](https://www.mercadopago.com.ar/developers/en/docs/your-integrations/credentials) (tokens o claves API).

Para pruebas del sistema, se recomienda utilizar una [cuenta de sandbox o modo test](https://www.mercadopago.com.ar/developers/en/docs/your-integrations/test/accounts), lo cual permite simular transacciones sin movimiento real de dinero.

---

## 3.3 Requisitos opcionales

- Acceso a un cliente Git para la descarga y actualización del repositorio.
    
- Cuenta de GitHub o similar para clonar y versionar el proyecto.
    
- Navegador web moderno para pruebas del formulario y flujo de donación.
    

---

## 3.4 Consideraciones generales

El sistema está diseñado para funcionar en entornos de desarrollo locales y puede ser posteriormente adaptado a un entorno de producción en un servidor web.

La configuración de servicios externos (Salesforce y Mercado Pago) es necesaria para el correcto funcionamiento del flujo completo de donación. Asimismo, es necesario contar con las credenciales correspondientes para cada integración y verificar su correcta configuración antes de realizar pruebas funcionales o despliegues en producción.