# Metodologia Kanban de desarrollo (VFC)

Objetivo: ejecutar con foco, decidir bien el orden, y empezar desarrollo sin ambiguedad.

## 1) Estructura del tablero

- `Backlog`: ideas sin detallar.
- `Ready`: tareas refinadas y listas para entrar.
- `Doing`: en curso (WIP limitado).
- `Review`: validacion funcional y tecnica.
- `Done`: terminado y documentado.

## 2) Regla base de comunicacion

- Preguntar primero, implementar despues.
- Mensajes cortos y claros.
- Una duda cada vez, sin mezclar decisiones.
- Cerrar cada decision con criterio concreto ("si/no", "A/B/C", fecha, responsable).

## 3) Criterios para mover tarjetas

## Ready (Definition of Ready)

Una tarjeta entra en `Ready` solo si tiene:

- objetivo funcional en 1 frase;
- alcance (incluye / no incluye);
- datos de entrada y salida;
- criterios de aceptacion comprobables;
- riesgos principales y dependencias.

## Done (Definition of Done)

Una tarjeta pasa a `Done` solo si:

- implementacion hecha;
- validaciones manuales ejecutadas;
- documentacion actualizada (si aplica);
- sin bloqueos abiertos;
- siguiente paso claro.

## 4) Como decidir cuando se ejecuta cada tarea

Usar este orden para priorizar:

1. Bloqueadores de negocio o de integracion.
2. Riesgo tecnico alto (resolver pronto).
3. Dependencias para trabajo posterior.
4. Valor funcional inmediato.
5. Esfuerzo corto (quick wins) para mantener flujo.

Regla practica de cadencia:

- planificacion semanal (30 min);
- revision diaria rapida (10-15 min);
- maximo `Doing`: 2 tarjetas simultaneas.

## 5) Flujo operativo para este proyecto (catalogo SINLI / proveedor)

Referencia de orden al integrar **datos de catálogo** (formato y canal según lo acordado con la librería o distribuidora) — ver [PREGUNTAS-PROVEEDOR-SINLI.md](./PREGUNTAS-PROVEEDOR-SINLI.md).

Secuencia recomendada:

1. Contrato y canal de entrega (formato ONIX/XML/CSV u otro, auth, SLAs).
2. Parser y validación del fichero (esquema, encoding, registros de prueba).
3. Mapeo a producto WooCommerce simple y reglas VFC (SKU/ISBN, precios, impuestos).
4. Variaciones y formatos (si aplican).
5. Imágenes y derechos de uso (URLs, allowlist, almacenamiento en medios WP).
6. Metadatos propios del proveedor en custom fields / meta trazables.
7. Sincronización (manual, CRON o cola) e idempotencia (sin duplicados).
8. Tests de casos clave y documentación de uso operativo.

## 6) Plantilla minima de tarjeta

- `Titulo`: verbo + resultado.
- `Para que`: valor de negocio.
- `Entrada`: payload o datos requeridos.
- `Salida`: respuesta esperada.
- `Aceptacion`: checks concretos.
- `Cuando`: fecha objetivo.
- `Responsable`: persona asignada.
- `Bloquea a`: tareas dependientes.

## 7) Politica de dudas

Antes de implementar una tarjeta, resolver:

- 1 duda funcional;
- 1 duda tecnica;
- 1 duda operativa (deploy/uso);

si cualquiera queda abierta, la tarjeta vuelve a `Backlog` o se marca `Blocked`.
