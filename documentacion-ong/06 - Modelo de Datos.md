## 6.1 Enfoque del modelo de datos

El sistema no utiliza una base de datos relacional propia dentro de WordPress como fuente principal de información, sino que delega la persistencia de datos de donantes a un sistema externo: Airtable.

En este contexto, Airtable funciona como un CRM liviano que permite almacenar, visualizar y gestionar la información capturada desde el formulario de donación.

Esto implica que el modelo de datos está desacoplado del sistema de WordPress y se basa en una estructura externa gestionada mediante API.

---

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

## 6.3 Estructura en Airtable

La información se almacena en Airtable bajo una tabla única que centraliza los registros de donantes.

Cada registro en la tabla representa una instancia de donación potencial o contacto generado desde el formulario.

La estructura lógica de la tabla es la siguiente:

| Campo    | Tipo de dato     | Descripción            |
| -------- | ---------------- | ---------------------- |
| Nombre   | Single line text | Nombre del donante     |
| Apellido | Single line text | Apellido del donante   |
| DNI      | Single line text | Documento de identidad |
| Email    | Email            | Correo electrónico     |
| Teléfono | Phone number     | Número de contacto     |

---

## 6.4 Rol de Airtable como CRM

Airtable cumple el rol de sistema de gestión de relaciones con donantes (CRM liviano), permitiendo:

- Visualización centralizada de los datos recolectados.
    
- Filtrado y búsqueda de registros.
    
- Acceso desde múltiples dispositivos.
    
- Gestión operativa por parte de la ONG sin necesidad de acceso al sistema WordPress.
    

Documentación oficial de Airtable:  
[https://airtable.com/developers/web/api/introduction](https://airtable.com/developers/web/api/introduction)

---

## 6.5 Flujo de persistencia de datos

El flujo de almacenamiento de datos se realiza de la siguiente manera:

1. El usuario completa el formulario de donación.
    
2. El plugin procesa la información ingresada.
    
3. Los datos del donante son enviados a la API de Airtable.
    
4. Se crea un nuevo registro en la tabla correspondiente.
    
5. La información queda disponible para su gestión por parte de la ONG.
    

---

## 6.6 Consideraciones del modelo de datos

- El sistema no mantiene persistencia interna en WordPress para datos de donantes.
    
- Airtable actúa como única fuente de almacenamiento estructurado.
    
- El modelo es extensible, permitiendo agregar nuevos campos en el futuro sin modificar la lógica principal del plugin.
    
- La estructura actual está orientada a la identificación y contacto de donantes, no a la gestión financiera de transacciones.