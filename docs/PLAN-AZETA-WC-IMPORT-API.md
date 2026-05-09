# Plan — API de alta de productos WooCommerce desde ficha Azeta (extensión Chrome)

**Estado:** listo para implementación (decisiones cerradas mayo 2026).

Integración para crear productos WooCommerce desde una **extensión de Chrome** que puede ejecutarse desde **cualquier URL**. La autenticación usa **contraseña de aplicación de WordPress** sobre **HTTPS** y cuenta **administrador**.

---

## 1. Objetivos

- Un único `POST` crea el producto (simple o variable), con **precio según modelo VFC** ya existente en `vfc-woocommerce`.
- Descarga de **todas las imágenes por URL** en la misma petición (primera = portada, resto = galería).
- SKU del catálogo = **EAN**; rechazo claro si el EAN ya existe.
- Producto nuevo siempre en **`draft`**.
- Sin categorías automáticas en **v1** (se asignan en admin).

---

## 2. Endpoint REST

| Método | Ruta propuesta |
|--------|----------------|
| `POST` | `/wp-json/vfc/v1/integrations/azeta/products` |

- **Namespace:** `vfc/v1`, subruta acotada a `integrations/azeta/` para separar del portal (`vfc/v1/portal`) y del dominio core.
- **Plugin:** registrar controlador REST en **`vfc-woocommerce`** y enlazarlo desde `Plugin::boot()` tras comprobar dependencias (como el resto del plugin).

---

## 3. Autenticación y permisos

- Cabecera **`Authorization: Basic`** con usuario WP + **contraseña de aplicación** ([documentación WordPress](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#basic-authentication-with-application-passwords)).
- Quién puede usar la API: cualquier usuario con **`manage_options`** / rol **Administrator** (o capacidad suficiente para crear productos; concretar en código como **`manage_woocommerce` + `edit_products`** para alinear con operación de catálogo).
- Respuesta **`401`** si falla auth; **`403`** si usuario válido pero sin capacidad.

---

## 4. Precios y modelo económico VFC

Alineado con ```11:13:viaje-fin-de-curso/wp-content/plugins/vfc-woocommerce/src/Services/PricingService.php```:

- El **precio sin IVA que muestra Azeta (“Precio S/IVA”)** se guarda como **`_vfc_precio_base`** (coste/base sin IVA).
- Los **porcentajes % empresa / % alumno** no son obligatorios en el payload: si no vienen, se usan los **globales de Ajustes VFC** (`PercentageService` / opciones ya definidas en el plugin).
- Tras persistir base y % efectivos, invocar **`PricingService::syncRegularPrice()`** sobre el producto simple o cada variación según corresponda, para que **`regular_price`** refleje el total sin IVA más la conversión a lo que Woo espere según “Impuestos” (precios con/sin IVA tal como está en ```203:228:viaje-fin-de-curso/wp-content/plugins/vfc-woocommerce/src/Services/PricingService.php```).

Opcional futuro (fuera de v1 obligatorio): permitir en JSON `_vfc_pct_empresa` / `_vfc_pct_alumno` por producto o variación para sobrescribir globales.

---

## 5. SKU, duplicados y estado

| Regla | Detalle |
|-------|---------|
| SKU WooCommerce | **EAN** (simple o cada variación). |
| Código artículo Azeta | Meta dedicada (ver §8), **no** como SKU principal. |
| EAN duplicado | **No crear ni actualizar.** Respuesta **`409 Conflict`** con cuerpo JSON `{ "code": "...", "message": "...", "data": { "sku": "..." } }`. |
| Estado de publicación | **`draft`**. |

Comprobación de duplicado: usar API de Woo para resolver ID por SKU (`wc_get_product_id_by_sku()` o equivalente compatible **HPOS/catalog** en la versión objetivo) **antes** de crear el post.

---

## 6. Textos

| Campo Woo | Origen (v1) |
|-----------|-------------|
| `post_title` / nombre | Título de la ficha (equivalente al `h2` en Azeta). |
| `post_content` / descripción larga | Pestaña principal “Descripción”. |
| Descripción corta | **Opcional** en el payload; si falta, cadena vacía. |

Opcional: segundo bloque (p. ej. “Otros datos / advertencias”) puede ser otro campo string en JSON que se concatene al final de la descripción larga con separador claro, **solo si** negocio lo exige en una iteración posterior.

---

## 7. Imágenes

- El body es **JSON** con propiedad **`images`: array de strings** (URLs **HTTPS** absolutas).
- Orden: índice **0** → **imagen destacada**; **1..n** → **galería**.
- Servidor: validar host contra **lista permitida configurable**:
  - Opción en **Ajustes VFC** (lista de dominios, una por línea), y/o
  - Filtro `vfc_azeta_import_allowed_image_hosts`, y/o
  - Constante en `wp-config.php` si se define (prioridad documentada en código).
- Rechazar URLs que no coincidan (**`400`**) para mitigar SSRF.
- Implementación técnica: tras crear el producto, usar utilidades de adjuntos de WordPress (p. ej. `media_handle_sideload` tras `download_url`) con `wp_check_filetype`; límites de tamaño y MIME acordes con el servidor.
- **Atomicidad recomendada:** si falla cualquier imagen, eliminar el borrador creado en esta petición y responder **`422`** con detalle del fallo (o transacción/lógica equivalente).

---

## 8. Metadatos Azeta (trasabilidad)

Cuando el payload los incluya, persistir **siempre** (nombres meta a filar en implementación, prefijo sugerido `_vfc_azeta_`):

| Dato ficha | Ejemplo | Meta sugerida |
|------------|---------|----------------|
| Código artículo | `BS70040002` | `_vfc_azeta_cod_articulo` |
| Fabricante | `TECH ONE TECH` | `_vfc_azeta_fabricante` |
| Ref. fabricante | `TEC1002` | `_vfc_azeta_ref_fabricante` |

En **variables**: meta de cabecera en el **padre** si aplica a todo el artículo; las variaciones solo llevan las metas propias si el modelo lo requiere (EAN ya es SKU por variación).

---

## 9. Stock

- **`manage_stock`:** `true`.
- Payload: **`stock_quantity`** (entero ≥ 0) obligatorio por **simple** o por **cada variación**.
- Opcional: **`stock_status`** (`instock`, `outofstock`, `onbackorder`). Si no se envía, derivar de cantidad (`> 0` → `instock`) o según regla única documentada en código.

---

## 10. Impuestos

- **`tax_class`:** dejar el **comportamiento por defecto de WooCommerce** (clase estándar del sitio); no exigir campo en v1.

---

## 11. Categorías

- **v1:** no asignar términos de `product_cat` por API.

---

## 12. Producto variable

Payload con:

- **`type`:** `"variable"`.
- **`attributes`:** definición de atributos usados por variaciones (nombre + opciones), coherente con lo que espera Woo al crear variaciones por código.
- **`variations`:** array de objetos; cada uno incluye como mínimo:
  - SKU = **EAN**,
  - **`precio_base`** (precio Azeta sin IVA para esa variante),
  - atributos que discriminan la variación (p. ej. color / talla),
  - **stock_quantity** (+ **stock_status** opcional).

**Duplicados:** comprobar **cada** EAN antes de crear; ante conflicto **`409`** (sin crear el padre, o hacer la comprobación en fase previa y no persistir nada si falla ninguna).

**Imágenes:** en v1, las URLs de §7 aplican al **producto padre** (misma portada/galería para todas las variaciones salvo decisión futura de imágenes por variación).

**`syncRegularPrice`:** ejecutar por variación tras guardar `_vfc_precio_base` y `%` (globales o propios).

---

## 13. Producto simple

- **`type`:** `"simple"` (u omitido con default simple).
- Campos mínimos: **`name`**, **`precio_base`**, **`sku`** (EAN), **`stock_quantity`**, **`images`** (si la política de negocio exige al menos una imagen, marcarlo como `required` en validación; si no, permitir array vacío y documentarlo).

---

## 14. Cliente extensión Chrome

- Declarar **`host_permissions`** para el origen del sitio WordPress (HTTPS).
- Petición `fetch` desde **background** o **offscreen** con `Authorization: Basic ...`.
- Cuerpo `Content-Type: application/json`.
- No depender de cookies del sitio Azeta para la llamada a WordPress.

---

## 15. Tareas de implementación (checklist)

1. Añadir opción de ajustes “Dominios permitidos para imágenes Azeta” (textarea + saneamiento).
2. Crear `AzetaProductImportController` (o nombre final) en `vfc-woocommerce/src/Rest/`, registro en `rest_api_init`.
3. Validar JSON (schema mínimo: tipos, campos requeridos según simple/variable).
4. Servicio de dominio `AzetaProductFactory` (o similar): crear `WC_Product_Simple` / `WC_Product_Variable`, variaciones, metas VFC, metas Azeta, stock, estado `draft`.
5. Orquestar imágenes con allowlist + sideload + rollback en error.
6. Tests manuales: simple, variable 2 variaciones, EAN duplicado → 409, URL imagen no permitida → 400, auth inválida → 401.
7. Documentar en README o wiki interna: ejemplo `curl` con Basic Auth (sin credenciales reales).

---

## 16. Fuera de alcance v1

- Asignación automática de categorías / etiquetas.
- Sincronización periódica de stock con Azeta.
- Pasarela u otros canales distintos de este `POST`.
- Traducción i18n de textos importados (se trata en fases globales del proyecto si aplica).

---

*Documento generado a partir de las decisiones acordadas en la planificación; ajustar nombres de meta o códigos HTTP si el equipo estandariza otra convención.*
