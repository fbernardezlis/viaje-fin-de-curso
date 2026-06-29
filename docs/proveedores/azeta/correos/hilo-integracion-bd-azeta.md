# RE: Integración Base de Datos Azeta

**De:** Francisco Bernárdez Lis <fbernardez@dempo.es>  
**Para:** Mónica García Valverde <monicagarcia@azetadistribuciones.es>, Jorge Vargas Delgado <jvargas@azetadistribuciones.es>, "tbayonrebes@gmail.com" <tbayonrebes@gmail.com>  
**Fecha:** Mon, 29 Jun 2026 11:56:00 +0000  
**Fuente:** `RE- Integración Base de Datos Azeta.eml` (convertido desde .eml)

---

Buenas tardes Mónica,
Muchas gracias por la información y la documentación facilitada.
Tras revisar vuestras respuestas, nos quedan únicamente un par de cuestiones para cerrar el análisis:

  *
Respecto a las bajas de productos del catálogo de libros, entendemos que los registros no se eliminan de la base de datos, sino que permanecen informados y se identifican mediante el campo Activo = N cuando dejan de estar disponibles. ¿Es correcto?
  *
En relación con el catálogo de papelería, nos sería de gran ayuda disponer de algún fichero de ejemplo de una o varias subfamilias para poder revisar la estructura completa, los campos disponibles y validar el esfuerzo de integración. No es necesario que contenga muchos registros; una muestra representativa sería suficiente para nuestro análisis.

Por nuestra parte, el resto de las cuestiones quedan aclaradas.
Quedamos atentos a vuestros comentarios.
Un saludo.

________________________________
De: Mónica García Valverde <monicagarcia@azetadistribuciones.es>
Enviado: miércoles, 10 de junio de 2026 14:10
Para: Francisco Bernárdez Lis <fbernardez@dempo.es>; Jorge Vargas Delgado <jvargas@azetadistribuciones.es>; tbayonrebes@gmail.com <tbayonrebes@gmail.com>
Asunto: RE: Integración Base de Datos Azeta

Buenas tardes,

  1.  ¿Nos podéis hacer llegar un ejemplo del fichero de catálogo de libros para analizar la estructura y toda la información que contiene? Sería suficiente con unos pocos productos de prueba. --> Si optáis por la descarga mediante URL, la estructura del fichero es la estándar de Onix 3.0, con todas las etiquetas correspondientes.

En el caso de habilitarnos un FTP, adjunto el fichero “feed_parcial.xlsx”

  1.  En la base de datos de libros, ¿se dan de baja o eliminan productos, o una vez incorporados al catálogo permanecen siempre en los ficheros? --> en el fichero, en la última columna hay un campo que es Activo= S/N para informar de los registros que dejan de incluirse en los feed por cambios de situación u otra característica que haga que no entre en el universo de libros a informar como disponible.

Iría una S para los activos (cambios) y una N para los que ya no lo están.

  1.  En el caso del catálogo de papelería, ¿todas las subfamilias mantienen la misma estructura de datos o cada una tiene campos diferentes? --> los ficheros siempre tienen los mismos campos.

En caso de que existan diferencias, ¿nos podríais facilitar una muestra de cada subfamilia para analizar su implementación y valorar cada caso?

  1.  ¿Nos podéis facilitar la documentación técnica de los servicios web disponibles para estudiar la integración de pedidos, dropshipping y consulta de estado/tracking? --> adjunto la documentación al correo.

  1.  Por último, respecto al contrato que comentáis, ¿se trata de un contrato de confidencialidad para la transmisión de información o del contrato necesario para iniciar el servicio? --> Se trata de un Acuerdo de prestación de servicio y acceso a la Base de datos. En el que se recoge, entre otras cosas que los datos que facilitamos son solamente para vuestro cliente y que no se pueden ceder a un tercero, el coste del servicio, duración inicial de un año, etc. Más adelante podemos analizarlo sin problema.

Seguimos en contacto,

Gracias

Un cordial saludo

Mónica García Valverde

627 302 443             916 866 892

www.azeta.es
