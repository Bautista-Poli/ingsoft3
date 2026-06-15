# ADR-001: Selección de WordPress como plataforma base

## Estado

Aceptado

## Fecha

Primer Cuatrimestre 2026

---

## Contexto

La ONG ya contaba con un sitio web desarrollado sobre WordPress y necesitaba incorporar una solución de captación de donaciones integrada a su infraestructura existente.

El objetivo principal era implementar un formulario de donaciones configurable, extensible y fácil de administrar por personal no técnico, evitando la necesidad de desarrollar una aplicación web completamente independiente.

Asimismo, la solución debía permitir futuras integraciones con sistemas externos de CRM y pasarelas de pago.

---

## Decisión

Se decidió utilizar WordPress como plataforma base del sistema e implementar la funcionalidad mediante un plugin personalizado.

La lógica específica del proyecto se desarrolló de forma desacoplada del tema (theme) utilizado por el sitio, permitiendo mantener independencia entre la capa visual y la funcionalidad de negocio.

---

## Justificación

WordPress fue seleccionado debido a los siguientes factores:

- La ONG ya utilizaba WordPress como CMS principal.
    
- Reduce costos de implementación y capacitación.
    
- Permite reutilizar la infraestructura existente.
    
- Facilita la administración de contenidos por usuarios no técnicos.
    
- Posee un ecosistema maduro de extensiones e integraciones.
    
- Permite desarrollar funcionalidades específicas mediante plugins personalizados.
    
- Facilita futuros despliegues en entornos productivos convencionales.
    

---

## Consecuencias

### Positivas

- Integración directa con el sitio existente.
    
- Menor tiempo de desarrollo.
    
- Facilidad de mantenimiento operativo.
    
- Curva de aprendizaje reducida para la ONG.
    
- Compatibilidad con futuras integraciones externas.
    

### Negativas

- Dependencia de la arquitectura y limitaciones propias de WordPress.
    
- Necesidad de adaptarse al ciclo de vida y convenciones del CMS.
    
- Posible complejidad adicional ante futuras necesidades de escalabilidad extrema.
    

---

## Alternativas consideradas

### Aplicación web independiente

Se evaluó desarrollar una aplicación completamente separada del sitio institucional.

**Motivo de descarte:** mayor complejidad de desarrollo, despliegue y mantenimiento.

### Personalización directa del tema de WordPress

Se evaluó incorporar toda la lógica dentro del tema activo.

**Motivo de descarte:** fuerte acoplamiento con la capa visual y menor mantenibilidad.

---

## Resultado

La decisión permitió desarrollar una solución integrada, modular y extensible, alineada con las capacidades técnicas de la ONG y con los objetivos académicos del proyecto.