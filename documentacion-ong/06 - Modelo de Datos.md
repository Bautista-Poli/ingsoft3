## 6.1 Enfoque del modelo de datos

El sistema no utiliza una base de datos relacional propia dentro de WordPress como fuente principal de información para la gestión de donantes, sino que delega dicha responsabilidad a un sistema CRM externo: Salesforce.

En este contexto, Salesforce permite almacenar, visualizar y gestionar la información capturada desde el formulario de donación, centralizando los datos de contactos y las oportunidades asociadas a las contribuciones realizadas.

Esto implica que el modelo de datos está desacoplado del sistema de WordPress y se basa en una estructura externa gestionada mediante las APIs de Salesforce, permitiendo integrar la información de donantes con los procesos de seguimiento y administración de la organización.

## 6.2 Entidad principal: Donante

La entidad central del sistema es el **Donante**, que representa a cualquier usuario que complete el formulario de donación.

### Atributos del Donante

Los datos almacenados para cada registro son los siguientes:

- **Nombre:** nombre del donante.
    
- **Apellido:** apellido del donante.
    
- **DNI:** documento de identidad.
    
- **Email:** correo electrónico de contacto.
    
- **Teléfono celular:** número de contacto telefónico.
    

Estos campos conforman el conjunto mínimo de información necesaria para identificar y contactar a un donante desde la ONG.

---

## 6.3 Estructura en Salesforce

La información se almacena en Salesforce utilizando objetos estándar del CRM que permiten gestionar tanto los datos de los donantes como las oportunidades de contribución.

La integración se basa principalmente en los siguientes objetos:

### Contact

Cada Contact representa un donante o potencial donante registrado a través del formulario.

La información mínima gestionada por el sistema incluye:

|Campo|Descripción|
|---|---|
|Nombre|Nombre del donante|
|Apellido|Apellido del donante|
|DNI|Documento de identidad|
|Email|Correo electrónico|
|Teléfono|Número de contacto|

### Opportunity

Las Opportunities permiten registrar y realizar seguimiento de las contribuciones asociadas a un Contact dentro del CRM.

La estructura exacta puede variar según la configuración de la organización Salesforce utilizada.

---

## 6.4 Rol de Salesforce como CRM

Salesforce cumple el rol de sistema de gestión de relaciones con donantes (CRM), permitiendo:

- Centralizar la información de contactos y contribuciones.
    
- Gestionar potenciales donantes y donantes activos.
    
- Realizar seguimiento de oportunidades de donación.
    
- Integrar la información con los procesos institucionales de la organización.
    
- Acceder a la información desde múltiples dispositivos y perfiles de usuario.
    

Documentación oficial de Salesforce:  
[https://developer.salesforce.com/](https://developer.salesforce.com/)

---

## 6.5 Flujo de persistencia de datos

El flujo de almacenamiento de datos se realiza de la siguiente manera:

1. El usuario completa el formulario de donación.
    
2. El plugin procesa la información ingresada.
    
3. Los datos del donante son enviados a Salesforce mediante las APIs correspondientes.
    
4. Se crea o actualiza un Contact con la información recibida.
    
5. En caso de corresponder, se genera una Opportunity asociada al Contact.
    
6. La información queda disponible para su gestión y seguimiento por parte de la ONG dentro del CRM.
---

## 6.6 Consideraciones del modelo de datos

- El sistema no mantiene persistencia interna en WordPress para la gestión de donantes.
    
- Salesforce actúa como fuente principal de información para contactos y oportunidades de donación.
    
- El modelo es extensible, permitiendo incorporar nuevos campos, objetos o procesos de negocio sin requerir modificaciones significativas en la arquitectura general del plugin.
    
- La estructura actual está orientada a la identificación, seguimiento y gestión de donantes dentro del CRM, mientras que el procesamiento financiero de las transacciones es responsabilidad de las pasarelas de pago integradas.