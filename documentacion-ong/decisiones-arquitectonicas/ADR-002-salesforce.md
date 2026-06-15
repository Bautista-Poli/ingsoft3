# ADR-002: Selección de Salesforce como plataforma CRM

## Estado

Aceptado

## Fecha

Primer Cuatrimestre 2026

---

## Contexto

El sistema requiere almacenar y gestionar información de donantes obtenida a través del formulario de donaciones.

La ONG ya utiliza Salesforce como plataforma CRM para la administración de contactos y procesos relacionados con sus actividades institucionales.

Dado que uno de los objetivos del proyecto es facilitar una futura adopción por parte de la organización, resultaba necesario que la solución se integrara con las herramientas ya utilizadas por la ONG, evitando la generación de repositorios de información paralelos o duplicados.

Asimismo, la solución debía integrarse mediante APIs y ser compatible con una arquitectura desacoplada basada en WordPress.

---

## Decisión

Se decidió utilizar Salesforce como plataforma CRM principal para la gestión de contactos y oportunidades de donación.

La integración se implementa mediante las APIs oficiales de Salesforce, permitiendo registrar información de donantes como Contacts y asociar las contribuciones mediante Opportunities.

---

## Justificación

Salesforce fue seleccionado debido a los siguientes factores:

- La ONG ya utiliza Salesforce como plataforma CRM institucional.
    
- Permite integrar la información de donantes dentro de los procesos existentes de la organización.
    
- Evita la duplicación de datos entre múltiples sistemas.
    
- Facilita la adopción futura del sistema por parte de la ONG.
    
- Permite centralizar la gestión de contactos y oportunidades.
    
- Posee APIs maduras y ampliamente documentadas.
    
- Proporciona capacidades de automatización y escalabilidad para futuras etapas del proyecto.
	

---

## Consecuencias

### Positivas

- Centralización de la información de donantes.
    
- Gestión estructurada de contactos y oportunidades.
    
- Mayor capacidad de crecimiento y automatización futura.
    
- Integración con herramientas empresariales del ecosistema Salesforce.
    
- Menor complejidad operativa para la ONG en el largo plazo.
    

### Negativas

- Mayor complejidad de configuración inicial.
    
- Dependencia de un servicio externo para la gestión de datos.
    
- Necesidad de administrar credenciales y configuraciones de integración.
    
- Curva de aprendizaje superior respecto de soluciones de almacenamiento más simples.
    

---

## Alternativas consideradas

### Airtable

Se evaluó utilizar Airtable como repositorio de datos para la gestión de donantes.

**Motivo de descarte:** si bien permitía una implementación sencilla, implicaba mantener una plataforma adicional separada del CRM institucional utilizado por la ONG.

### Base de datos propia en WordPress

Se evaluó almacenar la información directamente en tablas personalizadas dentro de WordPress.

**Motivo de descarte:** generaba una solución aislada respecto de las herramientas ya utilizadas por la organización y aumentaba la responsabilidad de mantenimiento del sistema.

---

## Resultado

La adopción de Salesforce permitió integrar el sistema de donaciones con la plataforma CRM ya utilizada por la ONG, favoreciendo la centralización de la información, la continuidad operativa y una futura implementación productiva dentro de la organización.