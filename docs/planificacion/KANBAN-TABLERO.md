# Tablero Kanban — Viaje fin de curso (vivo)

Fuente de verdad operativa del desarrollo. Metodología y definiciones: [KANBAN-DESARROLLO.md](./KANBAN-DESARROLLO.md).

**Última actualización:** 2026-06-29 (épica integración Azeta en Backlog)  
**Límite WIP `Doing`:** máximo 2 tarjetas.

---

## Backlog

*(Ideas o épicas sin refinar; aquí no entra desarrollo.)*

### Épica: Integración Azeta — catálogo, stock y pedidos

- **Para qué:** sincronizar productos desde Azeta (ONIX 3.0 / FTP, CSV de disponibilidad) y enviar pedidos dropshipping vía servicios web hacia **WooCommerce / VFC**.
- **Nota:** documentación técnica en [INTEGRACION-WOOCOMMERCE.md](../proveedores/azeta/INTEGRACION-WOOCOMMERCE.md).
- **Estado:** diseño documentado; pendiente contrato y plugin `vfc-azeta`.
- **Siguiente paso:** cerrar dudas abiertas con Azeta → tarjetas de Fase A (importador catálogo) a Ready.

---


## Ready

*(Refinadas: objetivo en 1 frase, alcance, entrada/salida, criterios de aceptación, riesgos/dependencias.)*

---

## Doing

*(En curso.)*

---

## Review

*(Validación funcional y técnica pendiente.)*

---

## Done

*(Implementado, probado, documentación actualizada si aplica.)*

---

## Plantilla rápida (copiar en cada tarjeta)

```markdown
### Título: [verbo + resultado]

- **Para qué:** …
- **Entrada:** …
- **Salida:** …
- **Aceptación:** …
- **Cuándo:** …
- **Responsable:** …
- **Bloquea a:** …
```
