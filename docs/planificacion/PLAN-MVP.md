# Plan de trabajo — MVP Viaje fin de curso (WordPress + WooCommerce)

Documento vivo para alinear **pasarelas**, **alcance**, **arquitectura** y **fases** del desarrollo. Las comisiones Redsys son **las del contrato con el banco**; hay que cerrarlas por escrito con la entidad.

---

## 1. Pasarela de pago — **decisión: Redsys**

**Objetivo comercial:** un solo **comercio** (TPV Redsys de la plataforma); el cobro online va a esa cuenta; las **liquidaciones a colegios** siguen siendo **manuales** fuera de WooCommerce según el modelo acordado.

**Por qué Redsys:** estándar en España, buen encaje con **tarjeta** y, si el banco lo incluye en el contrato, **Bizum** / wallets en el mismo TPV.

**Integración WooCommerce:** usar un **plugin de Redsys mantenido y probado** con vuestra versión de WooCommerce. Referencia habitual del ecosistema WooCommerce.com: [Pasarela Redsys para WooCommerce](https://woocommerce.com/products/redsys-gateway/) (revisar licencia, renovación y compatibilidad con **HPOS** en la ficha del producto antes de comprar).

**Checklist técnico‑operativo (cuando tengáis banco):**

1. Contrato TPV **Redsys** con la entidad (comisiones, plazos de liquidación bancaria al comercio, **reembolsos**).  
2. Credenciales de **entorno de pruebas** (FUC, terminal, claves SHA) y paso a **producción**.  
3. Confirmar si el paquete incluye **Bizum** y qué métodos adicionales activáis en el plugin.  
4. Configurar en WooCommerce: moneda **EUR**, zonas de impuesto España, URLs de **callback** correctas en staging/producción (HTTPS).  
5. Probar flujo completo: carrito → pago Redsys (test) → pedido **Completado** → que `vfc-woocommerce` reciba los hooks necesarios para saldos y bloqueos.

**Bizum:** no es “otra pasarela” aparte: suele activarse **dentro del mismo contrato Redsys** y del plugin que elija el banco o el desarrollador. Si el banco **no** ofrece Bizum, valorarlo como incidencia de producto con el comercial, no solo como tema técnico.

**Desarrollo custom:** `vfc-woocommerce` sigue siendo **agnóstico** de la pasarela en lo posible (pedidos y estados WooCommerce); solo se acopla a Redsys si hiciera falta algún comportamiento especial (p. ej. preautorización), documentándolo aparte.

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
- Pruebas con **Redsys en modo pruebas** (tarjeta de test del banco / documentación del plugin); ampliar a **Bizum** solo si el contrato y el plugin lo soportan en sandbox.

### Fase 3 — Portal front (**cerrada**)

Implementación y documentación: [PLAN-FASE3.md](PLAN-FASE3.md).

- Rutas `/portal/*`, auth, paneles alumno / tutor / colegio, QR inmutable + URL en portal, REST `vfc/v1/portal/*`, historial con filtros en UI.  
- i18n **solo ES** en código; CA/EN previstos en Fase 4.

### Fase 4 — Endurecimiento pre‑producción (**en curso / planificación**)

Detalle y checklist: [PLAN-FASE4.md](PLAN-FASE4.md).

- **Redsys** (sandbox → producción), **RGPD** / cookies, **i18n** (Polylang u opción de pago), mejoras opcionales de portal (perfil, PDF QRs).  
- Pruebas de carga ligera, permisos, logs, **despliegue** (AWS u otro) y go-live.

---

## 5. Riesgos y decisiones pendientes

| Tema | Riesgo / nota | Acción |
|------|----------------|--------|
| **Polylang + Woo gratis estricto** | Puede faltar cobertura de emails/tienda sin add-on | Decidir presupuesto para **add-on** o alcance i18n reducido en MVP |
| **HPOS** | Plugins mal adaptados rompen pedidos | Probar `vfc-woocommerce` con HPOS activado en staging |
| **Menores y tutores** | Responsabilidad del tratamiento y comunicaciones | Textos legales y flujos revisados con asesoría |
| **Redsys / TPV** | Comisiones y plazos dependen del contrato; claves mal configuradas rompen el callback | **Oferta cerrada** con el banco; entorno **sandbox** antes de producción |
| **Un solo comercio** | Facturación B2C y nombre en ticket | Definir con asesor fiscal quién es el **vendedor** en ticket |

---

## 6. Próximos pasos inmediatos

1. **Fase 4**: seguir [PLAN-FASE4.md](PLAN-FASE4.md) — empezar por **Redsys en sandbox** y **textos RGPD** en staging.  
2. **Contratar y configurar TPV Redsys** con la entidad bancaria; credenciales de test; plugin compatible con WooCommerce y **HPOS**.  
3. **Despliegue**: elegir entorno (p. ej. Lightsail/EC2/ECS), dominio HTTPS, correo (SES/SMTP), backups BD.  
4. **Tablero de issues** a partir de Fase 4 desglosada en tareas de 1–2 días.

---

## 7. Referencias externas

- Pasarela Redsys (WooCommerce.com): [Redsys Gateway](https://woocommerce.com/products/redsys-gateway/).  
- Contexto comparativo de pasarelas en España (referencia general, no sustituye al banco): [Raiola Networks — alternativas PayPal](https://raiolanetworks.com/blog/alternativas-paypal-ecommerce/).

---

*Última actualización: mayo 2026 — Fase 3 portal cerrada; Fase 4 detallada en PLAN-FASE4.md.*
