## 8.1 Requisitos previos

Antes de proceder con la instalación del sistema, es necesario contar con los siguientes elementos configurados:

- Un entorno de desarrollo WordPress activo (preferentemente mediante LocalWP).
    
- Acceso al repositorio del plugin en Git.
    
- Credenciales de Airtable.
    
- Credenciales de Mercado Pago (producción o entorno de pruebas).
    
- Un navegador web moderno para pruebas del sistema.
    

---

## 8.2 Configuración del entorno local

El sistema está diseñado para ejecutarse inicialmente en un entorno local de desarrollo utilizando LocalWP.

Pasos generales:

1. Instalar LocalWP.
    
2. Crear una nueva instancia de WordPress.
    
3. Iniciar el entorno local.
    
4. Acceder al panel de administración de WordPress.
    

Documentación oficial de LocalWP:  
[https://localwp.com/help-docs/](https://localwp.com/help-docs/)

---

## 8.3 Instalación del plugin

Para instalar el plugin personalizado en WordPress:

1. Descargar el archivo `ms-donaciones.zip`.
	
2. Acceder al panel de administración de WordPress
	
3. Navegar a la sección “Plugins”.
	
4. Seleccionar “Add Plugin”.
	
5. Arrastrar y soltar el archivo `ms-donaciones.zip` en la interfaz de instalación.
	
6. Instalar y activar el plugin correspondiente.
	

Una vez activado, el sistema quedará disponible dentro del entorno WordPress.

---

## 8.4 Configuración de Airtable

La configuración de Airtable es necesaria para habilitar el almacenamiento de datos de los donantes.

Pasos generales:

1. Crear una cuenta en Airtable.
    
2. Crear una base de datos con una tabla de donantes.
    
3. Configurar los campos requeridos:
    
    - nombre
        
    - apellido
        
    - dni
        
    - email
        
    - teléfono
        
4. Obtener la API Key o token de acceso.
    
5. Configurar las credenciales dentro del plugin de WordPress.
    

Documentación oficial de Airtable:  
[https://airtable.com/developers/web/api/introduction](https://airtable.com/developers/web/api/introduction)

---

## 8.5 Configuración de Mercado Pago

Para habilitar el sistema de pagos mediante Mercado Pago:

1. Crear una cuenta en Mercado Pago.
    
2. Acceder al panel de desarrolladores.
    
3. Generar credenciales de API (Access Token / Public Key).
    
4. Configurar dichas credenciales dentro del plugin.
    
5. Definir el entorno de uso:
    
    - Producción
        
    - Sandbox (pruebas)
        

Documentación oficial de Mercado Pago:  
[https://www.mercadopago.com.ar/developers/es](https://www.mercadopago.com.ar/developers/es)

---

## 8.6 Verificación del sistema

Una vez completada la instalación y configuración, se recomienda realizar las siguientes pruebas:

- Acceso al formulario de donación desde WordPress.
	
- Envío de datos de prueba.
	
- Verificación de registro en Airtable.
	
- Prueba de redirección a pasarela de pago.
	
- Validación del flujo de retorno (éxito / error).
	
- Utilización de los botones de prueba de conexión provistos por el plugin para verificar la conectividad con los servicios externos.
	
- Verificación de conexión correcta con Airtable (CRM).
	
- Verificación de conexión correcta con Mercado Pago.
	
- Confirmación de que las credenciales configuradas responden correctamente antes de habilitar el sistema en producción.
	
---

## 8.7 Consideraciones finales

El sistema está diseñado para ser instalado en entornos WordPress estándar sin modificaciones estructurales del core del CMS.

La configuración depende principalmente de la correcta integración de credenciales externas, las cuales son esenciales para el funcionamiento completo del flujo de donación.