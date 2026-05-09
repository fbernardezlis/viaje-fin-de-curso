# Preguntas para el proveedor — catálogo SINLI / libro

Documento para llevar a la **librería** (y, si aplica, a la **distribuidora** o plataforma tipo DILVE) antes de diseñar la integración técnica.

**Objetivo:** acordar cómo obtendréis datos **SINLI / ONIX** (o equivalente) y cómo se volcarán en **WooCommerce** / VFC.

**Estado:** pendiente de **confirmación con la librería** de que este es el canal correcto y quién firma el contrato/servicio de datos.

---

## 1. Quién es el interlocutor y el contrato

1. ¿Quién nos **facilita el catálogo**: la librería directamente, una **distribuidora**, una **agregación** (p. ej. entorno DILVE u otro), o la editorial?
2. ¿Existe **documentación contractual** o condiciones de uso de los datos (catálogo, imágenes, precios)?
3. ¿Hay **coste**, límites de uso o cláusulas que impidan **almacenar** ficheros o **cachear** datos en nuestro servidor?
4. ¿Podemos usar los datos en una **tienda online WooCommerce** de un tercero (colegio / plataforma VFC) o solo en un dominio concreto?

---

## 2. Formato técnico del catálogo

5. ¿Qué **formato** entregáis: **ONIX** (¿versión 3.0, 2.1…?), **XML SINLI**, **CSV**, otro?
6. ¿Esquema o **DTD/XSD** disponible para validar ficheros?
7. **Codificación** del fichero (UTF-8 obligatorio, etc.).
8. ¿Los envíos incluyen **solo novedades/cambios** o también **catálogo completo**? ¿Cómo distinguimos uno de otro?
9. ¿Hay **historial de cambios** (fecha de vigencia de precio, de stock, de ficha) en el propio formato?

---

## 3. Canal de entrega y operativa

10. ¿Cómo se **descarga** la información: **API REST** (URL base, documentación OpenAPI/Swagger si existe), **FTP/SFTP**, **HTTPS** con enlace firmado, **correo**, **portal web manual**?
11. Si es API: métodos (**GET** catálogo, paginación, filtros por fecha/ISBN).
12. Si es fichero: ¿**nombre** predecible, **compresión** (zip), **PGP** / firma?
13. **Frecuencia** recomendada: diaria, semanal, bajo demanda.
14. ¿Hay **ventana de mantenimiento** o cortes conocidos?

---

## 4. Autenticación y seguridad

15. ¿Qué credenciales necesitamos (**usuario/contraseña**, **token**, **certificado cliente**, **IP fija** allowlist)?
16. ¿Credenciales **distintas** para entorno de **pruebas** y **producción**?
17. ¿Política de **rotación** de contraseñas o caducidad de tokens?
18. ¿Registro de **accesos** o requisitos RGPD en el acuerdo de tratamiento (si la API registra IPs o identificadores)?

---

## 5. Identificadores y unicidad en WooCommerce

19. ¿Cuál es la **clave estable** del producto: **ISBN-13**, **EAN**, código interno distribuidora, código SINLI?
20. ¿Cómo tratamos **reimpresiones**, **distintos formatos** (rústica, ebook) del mismo título: un solo registro o varios?
21. ¿Incluís **SKU** que debamos usar tal cual en la tienda o generamos nosotros uno derivado del ISBN?

---

## 6. Precios e impuestos

22. Precios **con IVA / sin IVA**; moneda (**EUR**).
23. Si hay **varias tarifas** (PVP, librería, colegio): ¿vienen en el mismo fichero y cómo se discriminan?
24. **Vigencia** de precios (desde/hasta) si aplica.
25. ¿Hay **descuentos** por campaña codificados en el feed?

---

## 7. Stock y disponibilidad

26. ¿Incluís **stock numérico**, solo **sí/no disponible**, o **plazo de reposición**?
27. ¿Frecuencia de actualización del stock respecto al catálogo bibliográfico?
28. ¿Qué hacer si un producto **deja de estar disponible**: ¿desaparece del fichero, viene marcado, hay lista de bajas?

---

## 8. Imágenes y contenido enriquecido

29. ¿URLs de **portadas** en el fichero o carpeta aparte? ¿Formatos (**jpg**, **webp**) y tamaños?
30. **Derechos de imagen**: ¿podemos mostrarlas en web pública / tienda cerrada por colegio?
31. ¿Incluís **sinopsis**, **biografía del autor**, **materias BIC/Thema**, **edad recomendada**?
32. ¿PDF muestra / fragmentos permitidos?

---

## 9. Clasificación y merchandising en tienda

33. Taxonomías que debemos mapear a **categorías WooCommerce**: materia, colección, editorial, idioma.
34. ¿Lista cerrada de valores (taxonomías normalizadas) o texto libre?
35. ¿Etiquetas especiales (**libro curricular**, **recomendado**, etc.)?

---

## 10. Soporte y calidad de datos

36. **Contacto técnico** (email/teléfono) para incidencias de formato o cortes de servicio.
37. ¿Hay **entorno de prueba** con un subconjunto de registros?
38. Ejemplo de **fichero real anonimizado** o **registros de muestra** (2–3 productos completos).
39. ¿Procedimiento si detectamos **errores** en ISBN duplicado, precio incoherente o imagen rota?

---

## 11. Encaje con Viaje fin de curso (WooCommerce + VFC)

40. ¿El catálogo que nos daréis es **único global** o habrá **listados por cliente** (solo ciertos ISBN para un colegio)?
41. Si es global: ¿la librería **restringe** por otro medio qué puede comprar cada centro?
42. ¿Algún campo debemos guardar como **meta personalizada** para trazabilidad (ID distribuida, código SINLI, fecha de importación)?

---

## Después de la reunión (rellenar aquí)

| Pregunta / tema | Respuesta | Responsable | Fecha |
|-----------------|-----------|-------------|-------|
| Formato acordado | | | |
| Canal de entrega | | | |
| Clave producto (ISBN / otro) | | | |
| Entorno pruebas | | | |
| Contacto técnico | | | |

---

*Última revisión del documento: 2026-05-09.*
