---
name: kanban-dev-flow
description: Planifica y ejecuta trabajo tecnico con metodologia Kanban en este proyecto. Usar cuando se definan fases, prioridades, orden de implementacion, criterios de done, o seguimiento de tareas.
disable-model-invocation: true
---

# Kanban Dev Flow

## Objetivo

Mantener el desarrollo en flujo corto, con decisiones claras y baja ambiguedad.

## Pasos

1. Definir alcance en 1-2 frases.
2. Dividir en tarjetas pequenas (1 resultado por tarjeta).
3. Validar Definition of Ready por tarjeta.
4. Priorizar por bloqueadores, riesgo, dependencias y valor.
5. Ejecutar con WIP maximo de 2 en paralelo.
6. Pasar por Review con pruebas minimas.
7. Cerrar con Definition of Done y siguiente accion.

## Definition of Ready (minimo)

- objetivo;
- entrada/salida;
- criterios de aceptacion;
- dependencias.

## Definition of Done (minimo)

- implementado;
- verificado;
- documentado si aplica;
- sin dudas abiertas.

## Formato de seguimiento recomendado

- `Backlog`: pendientes sin detalle.
- `Ready`: listas para ejecutar.
- `Doing`: en curso.
- `Review`: validacion.
- `Done`: cerradas.
