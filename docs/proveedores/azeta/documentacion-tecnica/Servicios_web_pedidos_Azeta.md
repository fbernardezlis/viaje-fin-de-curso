# Servicios web pedidos Azeta

**Fuente:** `Servicios_web_pedidos_Azeta.pdf` (texto extraído automáticamente del PDF)

---

```text
Azeta. Servicios web
                                                 Guía del programador. Versión 1.0



Contenido
Introducción........................................................................................................................................................................ 2
1.      Servicio web: pedido .................................................................................................................................................. 2
     1.1.       Descripción ........................................................................................................................................................ 2
     1.2.       Fichero XSD con las especificaciones del XML .................................................................................................. 2
     1.3.       URI para la petición ........................................................................................................................................... 2
     1.4.       Descripción de los campos del XML de la petición ........................................................................................... 2
     1.5.       Ejemplo de un XML de petición ........................................................................................................................ 3
     1.6.       Campos del XML de respuesta .......................................................................................................................... 4
     1.7.       Ejemplo de un XML de respuesta ...................................................................................................................... 4
     1.8.       Ejemplo en PHP ................................................................................................................................................. 5
2.      Servicio web: stock..................................................................................................................................................... 6
     2.1.       Descripción ........................................................................................................................................................ 6
     2.2.       Fichero XSD con las especificaciones del XML .................................................................................................. 6
     2.3.       URI del para la petición al servicio web ............................................................................................................ 6
     2.4.       Descripción de los campos del XML de la petición ........................................................................................... 6
     2.5.       Ejemplo de un XML de petición ........................................................................................................................ 6
     2.6.       Campos del XML de respuesta .......................................................................................................................... 6
     2.7.       Ejemplo de un XML de respuesta ...................................................................................................................... 7
     2.8.       Ejemplo en PHP ................................................................................................................................................. 8
3.      Anexo: códigos de error ............................................................................................................................................. 9
Introducción
Azeta pone a disposición de sus clientes una serie de servicios web para poder realizar pedidos y comprobar el stock
de los artículos de su base de datos a través del EAN.

En las páginas siguientes, se detalla cómo hacer uso de estos servicios web:


1. Servicio web: pedido
    1.1.      Descripción
    Mediante este servicio web, el cliente puede realizar un pedido a Azeta.

    1.2.     Fichero XSD con las especificaciones del XML
    https://www.azetadistribuciones.es/html/servicios_web/rest/pedido.xsd

    1.3.      URI para la petición
          POST https://www.azetadistribuciones.es/html/servicios_web/rest/index.php?service=pedido&format=xml



    1.4.      Descripción de los campos del XML de la petición


    Nombre campo            Tipo        Obligatorio   Descripción


    usuario                 Cadena      Si            Usuario de identificación del cliente.


    password                Cadena      Si            Clave de identificación del cliente.


    origen                  Cadena      No            Identificador del origen del pedido (preguntar a Azeta por él)


    numero_transaccion      Cadena      No            Número de pedido del cliente.

                                                      Indica si se desea forzar el envío del pedido, aunque no se
    forzar_envio            Cadena      Si            alcance el importe del “pedido mínimo”. Posibles valores: “S” o
                                                      “N”.

                                                      Importante: de cara a realizar las pruebas, pasar este campo
    modo_pruebas            Cadena      No            con valor “N”, para que el pedido no entre de forma directa a
                                                      Azeta, si este campo no está el pedido se confirmará.

                            Lista de
    detalle_pedido                      Si            Contiene el conjunto de nodos de tipo “linea_pedido”.
                            nodos

                            Lista de
    linea_pedido                        Si            Contiene la especificación de cada línea del pedido.
                            nodos

    ean                     Cadena      Si            EAN 13 del artículo a pedir.

                                                      Cantidad de unidades del artículo a pedir.
                                                      Si el artículo se vende por cajas (ej. una caja de 10 bolígrafos)
    cantidad                Entero      Si
                                                      la cantidad hace referencia al número de cajas (no se pueden
                                                      pedir unidades individuales).
                                       Si se indica “N”, se descartará la cantidad pedida que no se
                                       haya podido reservar. Si se indica “S”, se permitirá que la
                                       cantidad que no se ha podido reservar quede como pendiente
dejar_pendientes    Cadena   No
                                       de reservar.
                                       Posibles valores: “S” o “N”.
                                       Valor por defecto: “S”.

                                       Si se indica “S”, se reservará la cantidad pedida justo en el
                                       momento en el que se realice el pedido. Si se indica “N”, la
pedir_reservar      Cadena   No        reserva se realizará posteriormente.
                                       Posibles valores: “S” o “N”.
                                       Valor por defecto: “S”.

                                       Si se indica “S”, el artículo solamente quedará registrado en
                                       Azeta si se puede reservar toda la cantidad del artículo pedida.
                                       Si se indica “N”, y no se ha podido reservar toda la cantidad
reservar_completo   Cadena   No
                                       pedida, se descarta la línea del artículo.
                                       Posibles valores: “S” o “N”.
                                       Valor por defecto: “N”.

id_linea_pedido     Cadena   No        Identificador del cliente para cada línea del pedido.



1.5.   Ejemplo de un XML de petición
<pedido>
   <usuario>123456</usuario>
   <password>abcdefg</password>
   <origen>X</origen>
   <numero_transaccion>1</numero_transaccion>
   <forzar_envio>N</forzar_envio>
   <modo_pruebas>S</modo_pruebas>
   <detalle_pedido>
         <linea_pedido>
               <ean>9788426720986</ean>
               <cantidad>2</cantidad>
               <dejar_pendientes>S</dejar_pendientes>
               <pedir_reservar>R</pedir_reservar>
               <reservar_completo>N</reservar_completo>
               <id_linea_pedido>1</id_linea_pedido>
         </linea_pedido>
         <linea_pedido>
         <ean>9788491470021</ean>
               <cantidad>3</cantidad>
               <dejar_pendientes>S</dejar_pendientes>
               <pedir_reservar>R</pedir_reservar>
               <reservar_completo>N</reservar_completo>
               <id_linea_pedido>2</id_linea_pedido>
         </linea_pedido>
         <linea_pedido>
               <ean>9788415374732</ean>
               <cantidad>4</cantidad>
               <dejar_pendientes>S</dejar_pendientes>
               <pedir_reservar>R</pedir_reservar>
               <reservar_completo>N</reservar_completo>
               <id_linea_pedido>3</id_linea_pedido>
         </linea_pedido>
   </detalle_pedido>
</pedido>
1.6.    Campos del XML de respuesta


Nombre campo         Tipo       Descripción

                     Lista de
linea_pedido                    Contiene la especificación de cada línea del pedido.
                     nodos

ean                  Cadena     Código EAN 13 del artículo pedido.


cantidad_pendiente   Entero     Cantidad que Azeta ha podido reservar.


cantidad_reservada   Entero     Cantidad pendiente.

                                Precio (sin IVA) de coste del artículo.
precio_siva          Entero
                                Formato: 0000.

                                Porcentaje de descuento aplicado en el pedido.
descuento            Entero
                                Formato: 0000.

                                Cualquier incidencia sobre el artículo. Actualmente son:
                                      -1: Artículo no encontrado en la base de datos de Azeta.
codigo_error         Entero
                                      -2: Antigua edición.
                                El nodo no existirá si no hay error.

descripcion_error    Cadena     Descripción del error.

                                Si Azeta conoce el EAN de la nueva edición (en el caso de que
ean_nueva_edicion    Cadena
                                codigo_error sea -2), vendrá aquí.



1.7.    Ejemplo de un XML de respuesta
<resultado_pedido>
   <linea_pedido>
         <ean>9788491470021</ean>
         <id_linea_pedido>2</id_linea_pedido>
         <error_linea_pedido>
               <codigo_error>-1</codigo_error>
               <descripcion_error>Artículo no encontrado</descripcion_error>
               <ean_nueva_edicion/>
         </error_linea_pedido>
   </linea_pedido>
   <linea_pedido>
         <ean>9788415374732</ean>
         <id_linea_pedido>3</id_linea_pedido>
         <error_linea_pedido>
               <codigo_error>-1</codigo_error>
               <descripcion_error>Artículo no encontrado</descripcion_error>
               <ean_nueva_edicion/>
         </error_linea_pedido>
   </linea_pedido>
   <linea_pedido>
         <ean>9788426720986</ean>
         <cantidad_reservada>0</cantidad_reservada>
         <cantidad_pendiente>2</cantidad_pendiente>
         <precio_siva>1519</precio_siva>
         <descuento>3000</descuento>
         <id_linea_pedido>1</id_linea_pedido>
   </linea_pedido>
</resultado_pedido>


1.8.    Ejemplo en PHP
<?php

$xml =
'<?xml version="1.0" encoding="UTF-8"?>
<pedido>
         <usuario>123456</usuario>
   <password>abcdef</password>
   <numero_transaccion>1</numero_transaccion>
   <forzar_envio>N</forzar_envio>
   <detalle_pedido>
         <linea_pedido>
               <ean>9788426720986</ean>
               <cantidad>2</cantidad>
               <dejar_pendientes>S</dejar_pendientes>
               <pedir_reservar>R</pedir_reservar>
               <reservar_completo>N</reservar_completo>
               <id_linea_pedido>1</id_linea_pedido>
         </linea_pedido>
         <linea_pedido>
         <ean>9788491470021</ean>
               <cantidad>3</cantidad>
               <dejar_pendientes>S</dejar_pendientes>
               <pedir_reservar>R</pedir_reservar>
               <reservar_completo>N</reservar_completo>
               <id_linea_pedido>2</id_linea_pedido>
         </linea_pedido>
         <linea_pedido>
               <ean>9788415374732</ean>
               <cantidad>4</cantidad>
               <dejar_pendientes>S</dejar_pendientes>
               <pedir_reservar>R</pedir_reservar>
               <reservar_completo>N</reservar_completo>
               <id_linea_pedido>3</id_linea_pedido>
         </linea_pedido>
   </detalle_pedido>
</pedido>';
?>

<form action="https://www.azetadistribuciones.es/html/servicios_web
/rest/index.php?service=pedido&format=xml" method="POST"
enctype="multipart/form-data">
     <input type="hidden" name="xml" value="<?php echo $xml ?>">
</form>
2. Servicio web: stock
  2.1.       Descripción
  Mediante este servicio web, el cliente puede consultar, a través del EAN, el stock de un conjunto de artículos de la
  base de datos de Azeta.

  2.2.     Fichero XSD con las especificaciones del XML
  https://www.azetadistribuciones.es/html/servicios_web/rest/stock.xsd

  2.3.       URI del para la petición al servicio web
         POST http://www.azetadistribuciones.es/html/rest/index.php?service=stock&format=xml

  2.4.       Descripción de los campos del XML de la petición

   Nombre campo       Tipo       Obligatorio   Descripción


   usuario            Cadena     Si            Usuario de identificación.


   password           Cadena     Si            Clave de identificación.


   ean                Cadena     Si            EAN 13 del artículo a solicitar.



  2.5.       Ejemplo de un XML de petición
   <?xml version="1.0" encoding="UTF-8"?>
   <stock>
      <usuario>123456</usuario>
      <password>abcdef</password>
      <ean>9788415089155</ean>
      <ean>9788426720986</ean>
      <ean>9788491470021</ean>
      <ean>9788415374732</ean>
      <ean>9788415089162</ean>
      <ean>9788415089117</ean>
      <ean>9788415089995</ean>
   </stock>

  2.6.       Campos del XML de respuesta


   Nombre campo         Tipo             Descripción


   linea_ean            Lista de nodos   Contiene los datos de cada EAN solicitado.

   ean                  Cadena           Código EAN 13 del artículo solicitado.

                                         Stock que dispone Azeta del artículo solicitado.
   stock                Entero
                                         El campo no existirá si ha ocurrido algún error con el artículo.

   error_ean            Lista de nodos   Especificación del error devuelto.
                             Cualquier incidencia sobre el artículo. Actualmente son:
codigo_error        Entero         -1: Artículo no encontrado en la base de datos de Azeta.
                             El nodo no existirá si no hay error.

descripcion_error   Cadena   Descripción del error.



2.7.    Ejemplo de un XML de respuesta

<resultado_stock>
   <linea_ean>
         <ean>9788415089155</ean>
         <stock>5</stock>
   </linea_ean>
   <linea_ean>
         <ean>9788426720986</ean>
         <stock>0</stock>
   </linea_ean>
   <linea_ean>
         <ean>9788491470021</ean>
         <error_ean>
               <codigo_error>-1</codigo_error>
               <descripcion_error>El EAN no se ha
encontrado</descripcion_error>
         </error_ean>
   </linea_ean>
   <linea_ean>
         <ean>9788415374732</ean>
         <error_ean>
               <codigo_error>-1</codigo_error>
               <descripcion_error>El EAN no se ha
encontrado</descripcion_error>
         </error_ean>
   </linea_ean>
   <linea_ean>
         <ean>9788415089162</ean>
         <stock>4</stock>
   </linea_ean>
   <linea_ean>
         <ean>9788415089117</ean>
         <stock>19</stock>
   </linea_ean>
   <linea_ean>
         <ean>9788415089995</ean>
         <stock>16</stock>
   </linea_ean>
</resultado_stock>
2.8.    Ejemplo en PHP
<?php

$xml =
'<?xml version="1.0" encoding="UTF-8"?>
<stock>
   <usuario>123456</usuario>
   <password>abcdef</password>
   <ean>9788415089155</ean>
   <ean>9788426720986</ean>
   <ean>9788491470021</ean>
   <ean>9788415374732</ean>
   <ean>9788415089162</ean>
   <ean>9788415089117</ean>
   <ean>9788415089995</ean>
</stock>';

?>

<form
action="https://www.azetadistribuciones.es/html/servicios_web/rest/index.php?
service=stock&format=xml" method="POST" enctype="multipart/form-data">
         <input type="hidden" name="xml" value="<?php echo $xml ?>">
</form>
3. Anexo: códigos de error

Código   Descripción               Observaciones


-1       XML incorrecto            El XML de llamada al servicio web no está bien formado.

                                   Los valores de los campos que vienen en el XML no cumplen las
-2       Validación de datos
                                   especificaciones del fichero XSD.

-3       Usuario no identificado   Las claves de acceso no corresponden con ningún cliente de Azeta


-4       Error interno             Error interno

         Método llamada no         El método empleado para realizar la llamada al servicio web, no está
-5
         permitido                 permitido.
```
