# ADR-003: Selección de Mercado Pago como pasarela de pagos inicial

## Estado

Aceptado

## Fecha

Primer Cuatrimestre 2026

---

## Contexto

El sistema requiere una pasarela de pagos que permita procesar donaciones realizadas desde el formulario integrado en WordPress.

La solución debía ofrecer una integración relativamente sencilla, soporte para distintos medios de pago y una experiencia conocida para usuarios del mercado argentino.

Asimismo, la arquitectura debía permitir incorporar nuevas pasarelas en el futuro sin afectar significativamente la lógica principal del sistema.

---

## Decisión

Se decidió utilizar Mercado Pago como primera pasarela de pagos integrada al sistema.

La integración se implementa mediante Checkout Pro, permitiendo delegar el procesamiento de pagos a la infraestructura de Mercado Pago y redirigir al usuario hacia una plataforma externa especializada para completar la transacción.

---

## Justificación

Mercado Pago fue seleccionado debido a los siguientes factores:

- Amplia adopción en Argentina y Latinoamérica.
    
- Soporte para múltiples medios de pago.
    
- Disponibilidad de entornos de prueba (sandbox).
    
- APIs y documentación ampliamente disponibles.
    
- Integración compatible con aplicaciones web y WordPress.
    
- Reducción de la complejidad asociada al procesamiento directo de pagos.
    
- Posibilidad de incorporar la solución rápidamente dentro del alcance del proyecto.
    

---

## Consecuencias

### Positivas

- Implementación relativamente rápida.
    
- Soporte para múltiples métodos de pago mediante una única integración.
    
- Infraestructura de pagos administrada por un proveedor especializado.
    
- Menor responsabilidad sobre el manejo directo de información financiera sensible.
    
- Disponibilidad de herramientas de prueba y validación.
    

### Negativas

- Dependencia de un servicio externo para el procesamiento de pagos.
    
- Dependencia de los flujos y limitaciones impuestos por la plataforma.
    
- Necesidad de gestionar credenciales y configuraciones específicas.
    
- Posibles cambios futuros en APIs, políticas o costos del proveedor.
    

---

## Alternativas consideradas

### Integración con múltiples pasarelas desde el inicio

Se evaluó implementar varias pasarelas de pago simultáneamente.

**Motivo de descarte:** incrementaba significativamente la complejidad técnica y el tiempo de desarrollo para la etapa actual del proyecto.

### Procesamiento de pagos propio

Se evaluó implementar una solución de procesamiento directo de pagos.

**Motivo de descarte:** implicaba mayores responsabilidades de seguridad, cumplimiento normativo y mantenimiento operativo.

### Transferencias bancarias únicamente

Se evaluó utilizar exclusivamente donaciones mediante transferencia bancaria.

**Motivo de descarte:** limitaba la experiencia del usuario y reducía la flexibilidad del sistema para aceptar distintos medios de pago.

---

## Resultado

La adopción de Mercado Pago permitió implementar un flujo completo de donación funcional dentro del alcance del proyecto, proporcionando una base sólida para el procesamiento de pagos y permitiendo futuras extensiones hacia nuevas pasarelas de pago cuando las necesidades de la organización lo requieran.