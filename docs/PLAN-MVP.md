# Plan de trabajo — MVP Viaje fin de curso (WordPress + WooCommerce)

Documento vivo para alinear **pasarelas**, **alcance**, **arquitectura** y **fases** del desarrollo. Las comisiones de pago son **orientativas**: dependen del **contrato con la entidad** y del **volumen**; hay que pedir tarifas cerradas antes de decidir.

---

## 1. Pasarelas de pago (España, WooCommerce, coste / adopción)

Objetivo: **máxima conversión local** con **coste por transacción razonable** y **un solo comercio** (la plataforma cobra; luego se liquida al colegio fuera de WooCommerce).

| Opción | Perfil | Notas prácticas |
|--------|--------|------------------|
| **Redsys** (tarjeta, a menudo Bizum, Apple/Google Pay según contrato) | Muy habitual en España; suele ser la base de un TPV con entidad española | Comisiones **negociables** con el banco / adquirente; integración WooCommerce mediante **plugin** (a veces de pago + mantenimiento). Referencia de producto oficial: [Pasarela Redsys para WooCommerce](https://woocommerce.com/products/redsys-gateway/). |
| **Bizum** | Muy deseado por familias en España | En la práctica suele ir **empaquetado** con Redsys/TPV del banco o con agregadores; el coste no es “un solo número” universal. |
| **Stripe** | Excelente API, buena para equipos técnicos y pagos internacionales | Tarifas públicas tipo **% + fijo por operación**; suele ser **más cara** que un buen acuerdo Redsys solo nacional, pero **simple** de integrar. Útil si queréis **tarjeta + Apple/Google Pay** sin depender solo del paquete bancario. |
| **PayPal** | Alta confianza percebida por algunos usuarios | Comisiones **suelen ser más altas**; conviene como **opcional** si métricas lo justifican. |
| **MONEI / otros agregadores** | “Todo en uno” (incl. Bizum en algunos casos) | Valorar **T&C**, comisiones y **reembolsos** frente a contrato bancario directo. |

**Recomendación de producto (no legal ni fiscal):**

1. **Primario:** **Redsys + Bizum** (vía contrato con entidad / TPV que ya soporte Bizum en WooCommerce), negociando tarifas por volumen.  
2. **Secundario (opcional):** **Stripe** si necesitáis **métodos** o **operativa** que el banco no cubra bien.  
3. **PayPal** solo si tras lanzamiento veis abandono de carrito por falta de confianza; monitorizar coste.

**Plugins:** minimizar “zoo” de pasarelas; cada una es **superficie de error**, **PCI** y **reconciliación**. El desarrollo custom (`vfc-woocommerce`) debe ser **agnóstico** de pasarela en lo posible (engancharse a **pedidos WooCommerce** y estados), salvo requisitos puntuales (preautorización, etc.).

---

## 2. Resumen del alcance acordado (MVP)

- **Un solo catálogo global**; **% de aportación al “saldo” del alumno** definido por **superadmin** por producto, sobre **subtotal de línea sin IVA**; IVA sigue las reglas de WooCommerce en el total del pedido.  
- **Colegio estable** + **ediciones de viaje** (superadmin **aprueba** edición).  
- **Admin de colegio:** productos permitidos para su centro (**por defecto todos**); misma regla para **todas** las ediciones.  
- **Matrícula** alumno ↔ edición; **QR único por alumno + edición**; si edición **no activa**, bloquear compra con mensaje claro.  
- **Sesión “comprando para…”** hasta acción explícita **“Salir / dejar de comprar para este alumno”**; cumplimiento **cookies / RGPD** (consentimiento, finalidad, política).  
- Con sesión vinculada: **solo catálogo del colegio**; cabecera con **colegio** + **alias** (mínimos datos personales); sin sesión: **catálogo global** + **aviso informativo** en checkout si compran sin vínculo (**sin** checkbox obligatorio).  
- **Abono al saldo del alumno** solo con pedido **Completado**; **15 días** de bloqueo configurables por superadmin; **reembolsos** revierten la parte imputada.  
- **Saldo** = **apunte interno** (no pago automático al alumno); **liquidaciones manuales al colegio** (transferencia), registro solo **superadmin**, **historial** visible por colegio; **exportación CSV** de **auditoría por colegio** desde el MVP.  
- **Alumnos** y **tutores** (1–2) con **usuario WP**; tutor con **varios alumnos** y selector en portal; **QR tras establecer contraseña**; reenvíos e invitaciones por email.  
- **Portal front** propio (calidad UX; poco “aspecto WordPress”).  
- **Multilenguaje:** **ES primero**, luego **CA** e **EN**; stack i18n **gratis** preferible (**Polylang** + valorar add-on Woo de pago si hace falta).  
- **Arquitectura recomendada:** **`vfc-core`** + **`vfc-woocommerce`**.

---

## 3. Arquitectura de plugins

### 3.1 `vfc-core` (dominio plataforma)

- Entidades: **Centro**, **Edición**, **Matrícula**, **Tutor ↔ alumno**, **Alias**.  
- Flujos: **creación/edición de edición**, **aprobación superadmin**, **auditoría** (quién, qué, cuándo, entidad afectada).  
- **Liquidaciones** al colegio (CRUD solo superadmin; lectura colegio).  
- **Exportaciones CSV** (auditoría por colegio; filtros por fechas en v1 si cabe).  
- **REST API** + capacidades para el portal.  
- Integración mínima con usuarios/roles WP (`superadmin` plataforma, `admin_colegio`, `alumno`, `tutor` — nombres internos a definir).

### 3.2 `vfc-woocommerce` (tienda y reglas económicas)

- **Token / QR** por **alumno + edición**; validación **activa**.  
- **Sesión** de beneficiario; persistencia hasta “salir”; cookie con **base legal** documentada.  
- **Filtro de catálogo** por colegio cuando hay sesión; **sin sesión**, catálogo global.  
- **Metadatos de pedido/línea**: vínculo a alumno, edición, colegio, importes de aporte (sin IVA), estado de **bloqueo 15 días**, reversión en **reembolso**.  
- Hooks de estado: crédito efectivo al pasar a **Completado** (y lógica de bloqueo/liberación).  
- **Aviso checkout** sin vínculo (solo informativo).  
- Compatibilidad con **HPOS** (High-Performance Order Storage): decidir pronto y probar en entornos de staging.

### 3.3 Portal (front)

- Implementación posible: **tema hijo** (bloques) + rutas del plugin, o **SPA** embebida servida por WP (menos “WP admin”, más control UI).  
- Misma identidad visual para **colegio / alumno / tutor** con **menus distintos** por rol.

---

## 4. Fases de trabajo sugeridas

### Fase 0 — Fundaciones (1 bloque corto)

- Repos: estructura `wp-content/plugins/vfc-core`, `vfc-woocommerce`; estándares (PHP, CS, tests mínimos si aplica).  
- Roles/capabilities esqueleto; modelo de datos (tablas custom vs CPT) **decisión técnica**.  
- Entorno: Docker actual + staging; backups BD antes de migraciones.

### Fase 1 — Núcleo operativo (`vfc-core`)

- CRUD **Centro** y **Edición** (borrador → pendiente aprobación → aprobada/rechazada → activa/inactiva).  
- **Matrícula** alumno en edición; **tutores** enlazados; **alias** (propuesta admin, editable alumno).  
- Emails transaccionales (invitación, establecer contraseña, QR cuando proceda).  
- **Auditoría** + export **CSV por colegio**.  
- **Liquidaciones** manuales + listados.

### Fase 2 — Integración WooCommerce (`vfc-woocommerce`)

- Generación y resolución de **QR/token**; pantalla de error si edición inactiva.  
- **Sesión vinculada** + UI “salir”; cabecera colegio + alias.  
- **Filtro catálogo** colegio con sesión; aviso checkout sin vínculo.  
- Cálculo **% sin IVA**; persistencia en pedido; **Completado** + **15 días** + **reembolsos**.  
- Pruebas con **2–3** métodos de pago de prueba (Stripe test + simulación Redsys si hay entorno).

### Fase 3 — Portal front

- Flujos **admin colegio** (ediciones, matrículas, productos del colegio, lectura liquidaciones/auditoría export).  
- Flujos **alumno** (perfil, alias, saldo, historial, QR, invitaciones).  
- Flujos **tutor** (selector de hijos, mismas capacidades operativas necesarias).  
- i18n: cadenas en `__()` / `.pot`; preparación **ES → CA → EN**.

### Fase 4 — Endurecimiento pre‑producción

- Pruebas de carga ligera; revisión de permisos; **logs** y trazabilidad.  
- Revisión **RGPD** (textos legales, cookies, retención, export/borrado si aplica).  
- Plan de **despliegue** y checklist go‑live.

---

## 5. Riesgos y decisiones pendientes

| Tema | Riesgo / nota | Acción |
|------|----------------|--------|
| **Polylang + Woo gratis estricto** | Puede faltar cobertura de emails/tienda sin add-on | Decidir presupuesto para **add-on** o alcance i18n reducido en MVP |
| **HPOS** | Plugins mal adaptados rompen pedidos | Probar `vfc-woocommerce` con HPOS activado en staging |
| **Menores y tutores** | Responsabilidad del tratamiento y comunicaciones | Textos legales y flujos revisados con asesoría |
| **Pasarelas** | Comisiones reales ≠ tablas de blog | Pedir **oferta cerrada** a banco / Stripe / agregador |
| **Un solo comercio** | Facturación B2C y nombre en ticket | Definir con asesor fiscal quién es el **vendedor** en ticket |

---

## 6. Próximos pasos inmediatos

1. **Cerrar** entidad bancaria / pasarela (**Redsys+BIZUM** como hipótesis principal) y **plugin** de integración WooCommerce homologado para vuestro caso.  
2. **Decidir** modelo de datos (**tablas propias** vs **CPT**) para Centro/Edición/Matrícula (impacto en informes y CSV).  
3. **Crear repositorio de issues** (o tablero) a partir de las **fases 1–4** desglosadas en tareas de 1–2 días.  
4. **Prototipo UI** del portal (wireframes) antes de implementar pantallas completas.

---

## 7. Referencias externas (orientación general)

- Alternativas y costes orientativos en ecommerce: [Raiola Networks — alternativas PayPal](https://raiolanetworks.com/blog/alternativas-paypal-ecommerce/).  
- Producto oficial WooCommerce Redsys: [woocommerce.com — Redsys Gateway](https://woocommerce.com/products/redsys-gateway/).

---

*Última actualización: documento generado a partir de los requisitos acordados en la conversación de producto.*
