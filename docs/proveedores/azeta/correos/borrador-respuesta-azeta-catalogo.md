# Borrador de respuesta — Integración catálogo Azeta

**Para:** Mónica García Valverde \<monicagarcia@azetadistribuciones.es\>  
**CC:** Jorge Vargas Delgado \<jvargas@azetadistribuciones.es\>  
**Asunto:** RE: Integración Base de Datos Azeta — dudas técnicas y operativas

---

Buenos días, Mónica (y Jorge):

Muchas gracias por el resumen y por el detalle de las opciones de integración. Hemos revisado la información con nuestro equipo técnico y, antes de dar el siguiente paso (contrato y elección de modalidad), necesitamos aclarar varios puntos para asegurar que la integración encaje con nuestra plataforma online (WooCommerce) y con el modelo de negocio del proyecto.

A continuación os trasladamos las dudas agrupadas por bloques. Os agradecemos respuestas por escrito cuando os sea posible; si algún punto requiere una breve llamada técnica, lo coordinamos sin problema.

---

## 1. Alcance inicial y líneas de catálogo

1. Para la **primera fase**, ¿podemos contratar **solo el catálogo de LIBROS** y dejar papelería/juguetes para una fase posterior, o el servicio exige contratar varias líneas a la vez?
2. En **papelería**, si más adelante nos interesa solo alguna subfamilia (p. ej. Escolar o Juegos y Juguetes), ¿el coste de implantación y la cuota mensual se ajustan por subfamilia o es global?
3. Nuestro modelo prevé un **catálogo online por colegio/centro** (cada centro puede ver y comprar un subconjunto de productos). El fichero que enviáis incluye **todo el fondo**: ¿Azeta puede facilitar en algún momento **listados filtrados por cliente** (ISBN/EAN concretos) o la selección la debemos hacer siempre nosotros tras importar el catálogo completo?
4. ¿Los datos del catálogo y las imágenes pueden utilizarse en una **tienda online de terceros** (centros educativos / familias) bajo el contrato que proponéis, o hay restricción de dominio o de uso?

---

## 2. Catálogo de LIBROS — formato, estructura y actualizaciones

5. ¿En qué **formato** se entregan los ficheros de libros (URL y FTP): **ONIX**, **XML SINLI**, **CSV**, otro? ¿Versión concreta del estándar?
6. ¿Disponéis de **documentación técnica** del fichero (esquema, DTD/XSD, diccionario de campos, ejemplos)?
7. ¿**Codificación** del fichero (UTF-8 u otra)?
8. Confirmamos: **fichero total mensual** + **actualizaciones semanales**. ¿Las semanales son **incrementales** (solo altas, bajas y cambios) o vuelven a ser un extracto parcial? ¿Cómo distinguimos en el nombre del fichero o en su cabecera el tipo de envío?
9. ¿Los productos **sin stock** siguen apareciendo en el catálogo bibliográfico con algún indicador de disponibilidad, o solo se gestiona eso en el fichero CSV horario?
10. ¿Qué **clave única** debemos usar como referencia en nuestro sistema: **EAN/ISBN-13**, código interno Azeta u otro?
11. ¿Cómo se tratan en el fichero las **distintas ediciones o formatos** del mismo título (rústica, ebook, etc.): registros separados por EAN?
12. Los **precios** del catálogo: ¿van **con IVA / sin IVA**? ¿Moneda siempre EUR? ¿Incluyen fecha de vigencia o solo el valor actual?
13. ¿Qué campos incluye el catálogo además de autor, título, precio, portada y sinopsis (editorial, colección, materia/temática, idioma, edad recomendada, etc.)?
14. Las **portadas**: ¿vienen como **URL** en el fichero, ruta en FTP o hay que resolverlas por otro mecanismo? ¿Formatos y tamaños habituales?
15. ¿Podéis facilitarnos un **fichero de muestra** (2–3 registros reales o anonimizados) y, si existe, acceso a un **entorno de pruebas** antes de firmar contrato?

---

## 3. Canal de entrega (URL vs FTP) y operativa

16. Opción **URL**: ¿las URLs son **fijas** o cambian en cada publicación? ¿Requieren **autenticación** (usuario/contraseña, token, IP autorizada)?
17. Opción **FTP**: ¿Azeta sube a **nuestra** carpeta o nosotros accedemos a un FTP de Azeta? ¿Protocolo **SFTP** disponible?
18. ¿Los ficheros van **comprimidos** (zip) o en claro? ¿Tamaño orientativo del fichero mensual completo?
19. ¿Hay **horario o día concreto** de publicación del mensual y de las actualizaciones semanales?
20. ¿Existe **ventana de mantenimiento** o incidencias habituales que debamos tener en cuenta para la sincronización automática?

---

## 4. Fichero de disponibilidad (CSV horario)

21. Confirmamos: actualización **cada hora**, formato `EAN;cantidad`, tope **50** unidades mostradas. ¿El EAN coincide siempre con el del catálogo bibliográfico?
22. Si un libro **no aparece** en el CSV horario, ¿debe interpretarse como **stock 0** o como «sin información»?
23. ¿Podemos recibir también un indicador **sí/no disponible** además de la cantidad, o solo el número?
24. ¿La URL del CSV horario es la misma en cada descarga o cambia? ¿Misma autenticación que el catálogo?
25. Para la tienda online: ¿recomendáis mostrar la **cantidad exacta** (hasta 50) o solo **«disponible / no disponible»** al cliente final?

---

## 5. Costes, contrato y soporte

26. Hemos visto la referencia al **coste** (implantación + cuota mensual) en el enlace que nos enviasteis. ¿Podéis confirmar por escrito qué incluye exactamente la **implantación** (configuración de URLs/FTP, pruebas, soporte inicial)?
27. ¿La cuota mensual cubre **solo libros**, o libros + papelería si contratamos ambas líneas?
28. ¿Hay **límite de descargas**, de almacenamiento en nuestro servidor o de reutilización de imágenes/datos en nuestra plataforma?
29. ¿Quién será nuestro **contacto técnico** para incidencias de formato, cortes de servicio o cambios en la estructura del fichero?
30. ¿El contrato incluye **cláusulas RGPD** / tratamiento de datos si almacenamos catálogo e imágenes en nuestros sistemas?

---

## 6. Servicios web (pedidos, tracking, dropshipping)

Entendemos que, una vez integrado el catálogo, los **servicios web de pedidos** no tienen coste adicional. Necesitamos aclarar:

31. ¿Disponéis de **documentación técnica** de esos servicios web (WSDL, REST, ejemplos de petición/respuesta)?
32. ¿Es necesario un **código de cliente Azeta** o credenciales distintas a las del catálogo?
33. Modalidades: **pedido online** frente a **dropshipping** — ¿son endpoints distintos? ¿Podemos elegir una u otra por pedido?
34. **Tracking**: ¿qué información devuelve (transportista, número de seguimiento, estados) y con qué frecuencia se actualiza?
35. **Dropshipping** (3,20 € + IVA, corte 14:00, 24–48 h): ¿ese coste se informa en la respuesta del servicio web para sumarlo al pedido, o se factura aparte?
36. En reclamaciones: confirmamos que la **atención al cliente** es nuestra y la gestión con Azeta la hacemos nosotros. ¿Hay un **canal o SLA** acordado para incidencias de pedido (roturas, retrasos, devoluciones)?
37. ¿Los servicios web están disponibles desde el **primer día** tras firmar catálogo o requieren una **activación** adicional?

---

## 7. Encaje con nuestro proyecto (fase actual)

Para que lo tengáis en contexto: estamos montando una **plataforma de compras para viajes de fin de curso** (varios colegios, alumnos y familias). En esta fase priorizamos:

- Importación fiable del **catálogo de libros** a nuestra tienda online.
- Sincronización de **stock** con el CSV horario.
- Más adelante, valorar **papelería** y **servicios de pedido** según acuerdo con la librería.

Con vuestras respuestas podremos definir la **modalidad de integración** (URL o FTP), el plan de desarrollo y el momento adecuado para el contrato.

Quedamos a la espera de vuestras aclaraciones. Si lo preferís, podemos organizar una **breve reunión técnica** (30–45 min) para cerrar formato de ficheros y flujo de actualizaciones.

Un cordial saludo,

**Francisco Bernárdez Lis**  
Dempo Digital Solutions  
fbernardez@dempo.es  

---

*Documento interno de borrador — 2026-05-09. Copiar el cuerpo del mail (desde «Buenos días») al cliente de correo.*
