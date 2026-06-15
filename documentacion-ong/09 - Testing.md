## 9.1 Objetivo de las pruebas

El objetivo de esta etapa es verificar el correcto funcionamiento del sistema de captación de donaciones en sus diferentes módulos, asegurando la integridad del flujo completo desde la carga de datos del usuario hasta la redirección a la pasarela de pago y el retorno al sistema.

Las pruebas se centran principalmente en la validación funcional del sistema, dado que la aplicación se encuentra en etapa media de desarrollo.

---

## 9.2 Enfoque de testing

El sistema fue evaluado mediante pruebas funcionales manuales, simulando el comportamiento de un usuario final dentro del entorno de desarrollo local.

No se implementaron aún pruebas automatizadas (unitarias o de integración), aunque el diseño modular del plugin permite su incorporación futura.

Adicionalmente, se incorporaron herramientas de verificación dentro del plugin para comprobar el estado de conexión con Salesforce y Mercado Pago, permitiendo validar de manera rápida si las integraciones externas fueron configuradas correctamente.

Las pruebas realizadas contemplan tanto la correcta comunicación con los servicios externos como el funcionamiento del flujo completo de captura de datos, creación de registros en el CRM y procesamiento de pagos.

---

## 9.3 Casos de prueba principales

### 1. Envío de formulario de donación

- **Descripción:** Verificar que el formulario pueda ser completado correctamente.
    
- **Resultado esperado:** Los datos se envían sin errores al backend del plugin.
    
- **Resultado observado:** Correcto en entorno local.
    

---

### 2. Registro en Salesforce

- **Descripción:** Validar que los datos del donante se registren correctamente en Salesforce.
    
- **Resultado esperado:** Creación o actualización exitosa de un Contact dentro del CRM.
    
- **Resultado observado:** Correcto mediante integración API.

---

### 3. Integración con Mercado Pago

- **Descripción:** Verificar la generación de la preferencia de pago y redirección.
    
- **Resultado esperado:** Redirección al checkout de Mercado Pago.
    
- **Resultado observado:** Correcto en modo de prueba.
    

---

### 4. Retorno del flujo de pago

- **Descripción:** Validar el comportamiento del sistema luego del pago.
    
- **Resultado esperado:** Redirección a pantalla de éxito o error según el resultado de la transacción.
    
- **Resultado observado:** Correcto según respuesta de la pasarela.
    

---

### 5. Validaciones de formulario

- **Descripción:** Verificar campos obligatorios y formatos válidos.
    
- **Resultado esperado:** Bloqueo de envío en caso de datos inválidos.
    
- **Resultado observado:** Correcto a nivel frontend.
    

---

## 9.4 Resultados generales

El sistema cumple con el flujo funcional esperado en entorno de desarrollo, permitiendo:

- Captura de datos de donantes.
    
- Registro y gestión de información en Salesforce.
    
- Creación y actualización de Contacts.
    
- Registro de Opportunities asociadas a las donaciones.
    
- Integración con Mercado Pago.
    
- Redirección y retorno del flujo de pago.
	
---

## 9.5 Limitaciones de las pruebas

- Las pruebas fueron realizadas en entorno local (LocalWP), no en producción.
    
- No se realizaron pruebas de carga o estrés.
    
- No se implementaron aún pruebas automatizadas.
    
- La validación de seguridad es básica y requiere refuerzo en futuras iteraciones.
    

---

## 9.6 Validación del sistema

El sistema se considera funcional en su estado actual de desarrollo medio, cumpliendo con los objetivos principales de captación de datos y derivación a pasarelas de pago externas.

Se recomienda realizar pruebas adicionales en entorno de producción antes de su implementación definitiva por parte de la ONG.