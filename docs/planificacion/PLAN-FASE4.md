# Fase 4 — Endurecimiento, producción y producto

**Estado: en planificación** (mayo 2026). La Fase 3 del portal está **cerrada**; ver [PLAN-FASE3.md](PLAN-FASE3.md). La Fase 2 WooCommerce sigue documentada en el MVP global: [PLAN-MVP.md](PLAN-MVP.md).

Este documento agrupa el trabajo previsto **antes y durante** el paso a producción: pagos, legalidad, idiomas, operaciones y mejoras de producto que quedaron fuera del alcance cerrado de Fase 3.

---

## 1. Objetivos de la fase

| Área | Objetivo |
|------|----------|
| **Pagos** | Integrar TPV **Redsys** (y Bizum si el contrato y el plugin lo permiten) en WooCommerce; pruebas sandbox y paso a producción. |
| **Legal / RGPD** | Textos legales, política de cookies, consentimiento donde proceda, retención y comunicaciones (menores/tutores revisados con asesoría). |
| **i18n** | Catalán e inglés: cadenas `vfc-portal`, `vfc-core`, `vfc-woocommerce`; decisión **Polylang** frente a add-on Woo de pago según presupuesto. |
| **Portal producto** | Edición de perfil/alias desde portal si el negocio lo exige; PDF o lote de QRs para colegio (si se prioriza). |
| **Ops / calidad** | Pruebas de carga ligeras, revisión de permisos, logs y trazabilidad, backups, despliegue (AWS/Lightsail/ECS, etc.), checklist go-live. |

---

## 2. Pagos (Redsys)

Referencia de producto en el MVP: [PLAN-MVP.md §1](PLAN-MVP.md).

**Tareas típicas**

1. Contrato TPV con entidad; credenciales **entorno de pruebas** (FUC, terminal, claves SHA).
2. Elegir e instalar **plugin Redsys** compatible con la versión de WooCommerce y **HPOS** (verificar ficha del plugin).
3. Configurar moneda EUR, impuestos España, URLs de retorno/callback con **HTTPS** en staging y producción.
4. Flujo de prueba: carrito → pago test → pedido **Completado** → comprobar que `vfc-woocommerce` registra movimientos de saldo como hoy.
5. Bizum: solo si el banco y el plugin lo exponen en sandbox/producción.

**Criterio de hecho:** al menos un pedido de prueba end-to-end en staging con Redsys test y pedido real en producción validado con importe mínimo.

---

## 3. RGPD y cookies

**Implementación técnica en repo (vfc-core 0.1.1+):**

- Cookie firmada `vfc_consent` (HttpOnly) y REST `POST /wp-json/vfc/v1/privacy/consent` (nonce `wp_rest`).
- Banner de cookies en el front (`CookieBanner`): solo necesarias / necesarias+funcionales / panel configurar y revocar.
- Borradores de páginas **Política de privacidad (VFC)**, **Aviso legal (VFC)**, **Política de cookies (VFC)** (slugs `politica-privacidad-vfc`, etc.) creados una vez; hay que **publicar y sustituir textos** tras asesoría.
- Texto sugerido en **Herramientas → Privacidad** (`wp_add_privacy_policy_content`) sobre `vfc_beneficiario`, `vfc_consent` y sesión WP/Woo.
- El endpoint `/qr/{token}` no emite `vfc_beneficiario` sin consentimiento funcional; el portal `/portal/qr/{token}` envía el consentimiento vía REST antes de redirigir a `/qr/{token}` (y `?consent_required=1` si alguien llega a `/qr/` sin haber aceptado).
- Enlace **Preferencias de cookies** (`?vfc_cookie_prefs=1`) en el pie del portal.

**Tareas que siguen siendo de negocio / legal**

- Sustituir los borradores legales por textos definitivos revisados con asesoría.
- Banner coherente con cualquier otra herramienta de analítica o terceros que se añadan después.
- Checklist interno de RGPD + revisión en staging antes de producción.

**Criterio de hecho:** checklist interno de RGPD + textos publicados en el sitio staging y revisados antes de producción.

---

## 4. Internacionalización (ES → CA → EN)

- Extraer y revisar cadenas en plugins `vfc-portal`, `vfc-core`, `vfc-woocommerce` (textdomains existentes).
- Generar/actualizar catálogos `.pot` y traducciones `.po`/`.mo` en `wp-content/languages/plugins/` o rutas que use Polylang.
- Probar **URLs de portal** (`/portal/*`) y **tienda** con idioma secundario (slugs, emails, Woo).
- Decisión documentada: **Polylang** (gratis) vs **add-on** Woo multilingüe si hiciera falta cobertura de emails/checkout.

**Criterio de hecho:** al menos CA o EN completo en staging para flujos críticos (portal alumno + checkout).

---

## 5. Portal — mejoras opcionales de producto

Fuera del cierre de Fase 3 según [PLAN-FASE3.md §Fuera de alcance](PLAN-FASE3.md):

- **Editar datos** del usuario desde el portal (alias, email con verificación, contraseña con flujo seguro).
- **Exportación / PDF** de QRs por edición o por centro (generación masiva, descarga zip).
- Ajustes UX tras feedback real (Kadence + `portal.css`).

Priorizar con negocio; cada ítem puede ser un issue independiente.

---

## 6. Operaciones y despliegue

**Infra (ejemplos)**

- **Lightsail / EC2 + Docker**: similar al `docker-compose.yml` local; BD en RDS o MariaDB en la misma VM; volúmenes o EFS para `uploads`.
- **ECS Fargate**: imagen propia + EFS para `wp-content`; RDS; ALB + ACM.

**Checklist técnico mínimo**

- Variables de entorno y secretos fuera del repo (SSM / Secrets Manager).
- `WP_HOME` / `WP_SITEURL` acordes al dominio público HTTPS.
- Permalinks + `flush-rewrites` tras despliegue (`/portal/*`, `/qr/*`).
- Instalar **WooCommerce** y **Kadence** en el entorno (no van en git según `.gitignore` actual); tema hijo `vfc-portal`.
- Migraciones `vfc-core` (`vfc_db_version`); backups de BD antes de upgrades.
- Correo transaccional (SES, SMTP) para invitaciones y QR.
- Monitorización básica (logs PHP, salud HTTP, espacio en disco).

**Criterio de hecho:** documento **go-live** firmado (responsable, fecha, rollback, contactos).

---

## 7. Calidad y seguridad

- Repaso de **capabilities** y rutas REST (`vfc/v1` y `vfc/v1/portal`).
- Prueba con **HPOS** activado en staging durante varios días.
- Revisión de dependencias (Composer en `vfc-portal`) y actualizaciones WordPress/WooCommerce planificadas.

---

## 8. Referencias en repo

| Documento | Uso |
|-----------|-----|
| [PLAN-MVP.md](PLAN-MVP.md) | Alcance MVP, fases 0–4 resumidas, Redsys, riesgos. |
| [PLAN-FASE3.md](PLAN-FASE3.md) | Portal cerrado: rutas, REST, QR inmutable, historial. |
| [README.md](../README.md) | Docker local, scripts útiles, portal. |

---

*Última actualización: mayo 2026 — Fase 4 abierta a iteración según prioridad de negocio.*
