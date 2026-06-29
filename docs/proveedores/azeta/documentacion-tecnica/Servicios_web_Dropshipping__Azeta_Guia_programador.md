# Servicios web Dropshipping  Azeta Guia programador

**Fuente:** `Servicios_web_Dropshipping__Azeta_Guia_programador.pdf` (texto extraído automáticamente del PDF)

---

```text
Servicios Web Dropshipping Azeta_ Guía del programador


         Se entiende como Dropshipping, el mecanismo de venta, en el cual, el minorista o
librero, pasa el pedido que realiza un cliente final, directamente al distribuidor (Azeta), y será el
distribuidor el encargado de enviar la mercancía directamente al cliente final. Azeta factura al
minorista con las condiciones comerciales previamente pactadas, y es el minorista el que factura
a cliente final.

        Para poder realizar este proceso, Azeta pone a disposición del comercio minorista una
serie de servicios web para enviar toda la información necesaria para realizar todo el proceso.
Los servicios web serán los siguientes:

    1. Servicio web: pedidoDropShipping

Mediante este servicio web, el librero comunica el pedido a Azeta.

1.1.    Fichero definición del servicio web


http://www.azetadistribuciones.es/html/servicios_web/rest/pedidoDropShipping.xsd

Url llamada al servicio web (POST)

http://www.azetadistribuciones.es/html/servicios_web/rest/index.php?service=pedidoD
ropShipping&format=xml



Ejemplo de llamada al servicio


<?php

$xmlPedir = '<?xml version="1.0" encoding="UTF-8"?>
<pedidoDropShipping>
  <usuario>boy18</usuario>
  <password>defrtsx</password>
  <origen>R</origen>
  <cliente_nombre>Pepe pérez</cliente_nombre>
  <cliente_direccion>Gran vía 150, bloque 3, 4º A</cliente_direccion>
  <cliente_codigo_postal>18005</cliente_codigo_postal>
  <cliente_localidad>Granada</cliente_localidad>
  <cliente_telefono>958123456</cliente_telefono>
  <cliente_email>a@b.com</cliente_email>
  <reservar_completo>N</reservar_completo>
  <modo_pruebas>S</modo_pruebas>
  <mensaje_regalo>Para el mejor padre</mensaje_regalo>
  <para_regalo>Papa</para_regalo>
  <detalle_pedido>
    <linea_pedido>
      <ean>9788446412345</ean>
      <cantidad>7</cantidad>
    </linea_pedido>
    <linea_pedido>
      <ean>9788440620248</ean>
      <cantidad>2</cantidad>
    </linea_pedido>
    <linea_pedido>
      <ean>9788493808105</ean>
      <cantidad>5</cantidad>




                                                                                                        1
Servicios Web Dropshipping Azeta_ Guía del programador


    </linea_pedido>
    <linea_pedido>
      <ean>9788492534623</ean>
      <cantidad>7</cantidad>
      <reservar_completo>S</reservar_completo>
    </linea_pedido>
    <linea_pedido>
      <ean>9788467916874</ean>
      <cantidad>3</cantidad>
    </linea_pedido>
    <linea_pedido>
      <ean>9788467579819</ean>
      <cantidad>8</cantidad>
    </linea_pedido>
    <linea_pedido>
      <ean>0070330129665</ean>
      <cantidad>3</cantidad>
    </linea_pedido>
  </detalle_pedido>
</pedidoDropShipping> ';

¿>



<form
action="http://www.azetadistribuciones.es/html/servicios_web/rest/index.php?se
rvice=pedidoDropShipping&format=xml" method="post" enctype="multipart/form-
data">
      <input type="hidden" name="xml" value="<?php echo $xmlPedir?>">
</form>




1.2.      Campos del pedido


 Nombre campo           Tipo        Obligatorio Descripción
 usuario                Cadena      Si          Usuario de identificación, coincide con
                                                el usuario de acceso a la web de Azeta
                                                para la librería.
 password               Cadena      Si          Clave de identificación, coincide con la
                                                clave de acceso a la web de Azeta para
                                                la librería.
                                                Identificador del origen del pedido
 origen                 Cadena      No
                                                (preguntar a Azeta por él)
 cliente_nombre         Cadena      Si          Nombre del cliente final al que se le
                                                envía el pedido.
 cliente_direccion      Cadena      Si          Dirección completa de envío del pedido
                                                (Calle, bloque, piso…).
 cliente_codigo_postal Cadena       Si          Código postal de envío del pedido, el
                                                servicio web hará un chequeo de que
                                                Azeta puede enviar a ese código postal.
 cliente_localidad      Cadena      Si          Localidad de envío.
 cliente_telefono       Cadena      Si          Teléfono de contacto del cliente del
                                                envío para el transportista.
 cliente_email          Cadena      No          Email del cliente final para las
                                                notificaciones.




                                                                                           2
Servicios Web Dropshipping Azeta_ Guía del programador


 reservar_completo     Cadena       No             Si se marca como “S”, el pedido solo se
                       (S/N)        (defecto N)    registrará en Azeta si se puede reservar
                                                   todas las cantidades solicitadas de todos
                                                   los artículos del pedido.
 modo_pruebas          Cadena       No             Por defecto siempre se trabaja en modo
                       (S/N)        (defecto S)    pruebas, en este caso el servicio web
                                                   devuelve las posibles reservas a realizar,
                                                   pero no se registra el pedido en Azeta.
                                                   Una vez terminada la fase de pruebas,
                                                   para comunicar los pedidos a Azeta, es
                                                   necesario pasar este campo a “N”.
 mensaje_regalo        Cadena       No             Texto donde el cliente puedo poner
                                                   algún tipo de mensaje, observación que
                                                   después se pasará una tarjeta que se
                                                   añadirá al pedido
 para_regalo           Cadena       No             Este campo como el anterior, se
                                                   escribirán dentro de la misma tarjeta.
                                                   Está pensado para añadir el nombre de
                                                   la persona a la que va dirigido el
                                                   contenido del pedido. Estos 2 campos
                                                   “mensaje_regalo” y “para_regalo”
                                                   tienen sentido cuando una persona
                                                   envía el pedido a una tercera persona
                                                   en una dirección distinta (regalo). Nota:
                                                   No es obligatorio rellenar los 2 campos
                                                   “mensaje_regalo” o “para_regalo”, se
                                                   puede rellenar solo uno de los 2. (Ver
                                                   ejemplo de llamada)


1.3.   Campos de los artículos del pedido


 Nombre de campo       Tipo        Obligatorio    Descripción
 ean                   Cadena      Si             EAN 13 del artículo a pedir
 cantidad              Entero      Si             Número de artículos a pedir, si el artículo
                                                  se vende por cajas, por ejemplo, una caja
                                                  de 10 bolígrafos, la cantidad hace
                                                  referencia al número de cajas (No se
                                                  puede pedir unidades sueltas).
 reservar_completo     Cadena      No             Si se marca a “S”, el artículo quedará
                       (S/N)       (defecto N)    registrado en Azeta solamente si puede
                                                  reservar todas las cantidades solicitadas




                                                                                                3
Servicios Web Dropshipping Azeta_ Guía del programador


Ejemplo de respuesta

<?xml version="1.0" encoding="UTF-8"?>
<pedido_dropshipping_result>
   <codigo_error>-8</codigo_error>
   <descripcion_error>
      AVISO: Modo pruebas, no realiza el pedido
   </descripcion_error>
   <linea_pedido>
      <ean>9788446412345</ean>
      <descripcion />
      <editorial />
      <pedidos />
      <reservados />
      <pendientes />
      <codigo_error>-1</codigo_error>
      <descripcion_error>Artículo no encontrado</descripcion_error>
      <ean_nueva_edicion />
   </linea_pedido>
   <linea_pedido>
      <ean>9788440620248</ean>
      <descripcion />
      <editorial />
      <pedidos />
      <reservados />
      <pendientes />
      <codigo_error>-2</codigo_error>
      <descripcion_error>Antigüa edición</descripcion_error>
      <ean_nueva_edicion>9788440636010</ean_nueva_edicion>
   </linea_pedido>
   <linea_pedido>
      <ean>9788493808105</ean>
      <descripcion>HECHO A MANO</descripcion>
      <editorial>LEQTOR</editorial>
      <pedidos>5</pedidos>
      <reservados>5</reservados>
      <pendientes>0</pendientes>
      <codigo_error />
      <descripcion_error />
      <ean_nueva_edicion />
   </linea_pedido>
   <linea_pedido>
      <ean>9788492534623</ean>
      <descripcion>SUPERMAN LA HISTORIA DEL HOMBRE DE ACERO</descripcion>
      <editorial>EDICIONES KRAKEN</editorial>
      <pedidos>7</pedidos>
      <reservados>2</reservados>
      <pendientes>0</pendientes>
      <codigo_error />
      <descripcion_error />
      <ean_nueva_edicion />
   </linea_pedido>
   <linea_pedido>
      <ean>9788467916874</ean>
      <descripcion>NIGHTMARE DARK GODS</descripcion>
      <editorial>NORMA EDITORIAL</editorial>
      <pedidos>3</pedidos>
      <reservados>0</reservados>
      <pendientes>0</pendientes>
      <codigo_error />
      <descripcion_error />
      <ean_nueva_edicion />
   </linea_pedido>
   <linea_pedido>
      <ean>9788467579819</ean>
      <descripcion>PUPI Y LOS PIRATAS</descripcion>




                                                                            4
Servicios Web Dropshipping Azeta_ Guía del programador


      <editorial>CESMA</editorial>
      <pedidos>8</pedidos>
      <reservados>8</reservados>
      <pendientes>0</pendientes>
      <codigo_error />
      <descripcion_error />
      <ean_nueva_edicion />
   </linea_pedido>
   <linea_pedido>
      <ean>0070330129665</ean>
      <descripcion>BOLIGRAFO BIC CRISTAL NEGRO</descripcion>
      <editorial>BIC</editorial>
      <pedidos>150</pedidos>
      <reservados>150</reservados>
      <pendientes>0</pendientes>
      <codigo_error />
      <descripcion_error />
      <ean_nueva_edicion />
   </linea_pedido>
   <pedido_azeta />
   <gastos_envio>0.00</gastos_envio>
</pedido_dropshipping_result>



1.4.   Campos de respuesta del pedido


 Nombre de campo       Tipo        Descripción
 codigo_error          Entero      Código del error o aviso, si está vacío el pedido ha sido
                                   registrado en Azeta. Ver Anexo “códigos de error”
 descripcion_error     Cadena      Descripción del error, si está vacío, el pedido ha sido
                                   registrado en Azeta
 pedido_azeta          Cadena      Código del pedido para Azeta si se registró
                                   correctamente
 gastos_envio          Decimal     Gastos de envío que genera el pedido. El cálculo de los
                                   gastos de envío corresponde a los gastos de envío que
                                   le cobraría Azeta a la librería, en el caso de que la
                                   librería estuviera en el Código postal del destinatario
                                   del envío.


1.5.   Campos de respuesta de los artículos del pedido


 Nombre de campo       Tipo        Descripción
 ean                   Cadena      Códig EAN 13 del artículo pedido
 descripción           Cadena      Titulo / Descripción del artículo pedido
 editorial             Cadena      Editorial / Fabricante del artículo pedido
 precio_azeta          Decimal     PVP sin IVA de los artículos de precio fijo, para los
                                   artículos de precio libre devuelve 0
 precio_venta          Decimal     Mismo precio de venta aportado en la entrada del
                                   servicio web
 pedidos               Entero      Cantidad pedida
 reservados            Entero      Cantidad que Azeta ha podido reservar
 pendientes            Entero      Cantidad pendiente. IMPORTANTE: Esta modalidad de
                                   pedido no deja pendientes, por lo tanto, siempre
                                   devolverá 0.



                                                                                               5
Servicios Web Dropshipping Azeta_ Guía del programador


 codigo_error             Entero       Cualquier incidencia sobre el artículo, actualmente son:
                                           • -1  Artículo no encontrado en la BD de Azeta
                                           • -2  Antigua edición.
                                       Devolverá vacío si no hay error
 descripción_error        Cadena       Descripción del error
 ean_nueva_edicion        Cadena       Si Azeta conoce el ean de la nueva edición en el caso de
                                       que coodigo_error = -2, vendrá aquí.




2. Formas de utilización
Deberá contactar con Azeta para que le facilite el usuario y contraseña para poder usar estos
servicios.

De cara a utilizar de forma correcta este servicio web, es recomendable que antes de comunicar
el pedido a Azeta, asegurarse que Azeta tiene disponibilidad de los artículos, esto es debido a
que los pedidos dropshipping están configurados de forma que artículo que no se pueda
reservar, articulo que no se registra en el pedido. Solo se registran en el pedido los artículos que
hayan podido reservarse.

Para ello se recomienda hacer una doble llamada al servicio web de pedidoDropshipping:

    •   Una primera llamada con el campo modo_pruebas = S, en esta llamada me devolverá
        en el campo “reservados” de cada artículo los que Azeta podría reservar en el caso de
        solicitar la realización del pedido. Quien hace uso de este servicio web, en función de
        estos datos podrá obrar en consecuencia (No solicitar finalmente el pedido por si hay
        algún artículo que no pueda reservar toda la cantidad…)

    •   Si como resultado de la primera llamada las posibles reservas están acorde con el deseo
        del cliente “final”, se podrá hacer una llamada definitiva para confirmar el pedido.
        Lógicamente, si hay mucha diferencia entre la primera llamada y la definitiva, como el
        stock de Azeta es un stock “vivo” y cambiante, podría darse el caso que el resultado del
        pedido no sea el deseado (Se haya quedado fuera algún artículo por no poder
        reservarse). Existe la posibilidad de anular completamente el pedido usando el servicio
        web de borrarPedido. Un pedido dropshipping será anulable durante un tiempo
        determinado (Justo hasta que comienza su preparación), por lo que es necesario
        anularlo lo antes posible




                                                                                                       6
Servicios Web Dropshipping Azeta_ Guía del programador


3. Servicio web: Borrar pedido

Servicio web que borra un pedido en Azeta. Desde que se comunica un pedido, hasta que se
empieza a preparar en el almacén, existe la posibilidad de borrar el pedido. Una vez que el
pedido se está gestionando en el almacén no habrá opción de borrarlo y será enviado.



3.1.    Fichero definición del servicio web


http://www.azetadistribuciones.es/html/servicios_web/rest/borrarPedido.xsd



3.2.    Url llamada al servicio (POST)


http://www.azetadistribuciones.es/html/servicios_web/rest/index.php?service=borrarP
edido&format=xml



Ejemplo de llamada al servicio
<?php
$xmlBorrar =
'<?xml version="1.0" encoding="UTF-8"?>
<borrarPedido>
  <usuario>pedro</usuario>
  <password>xxxx</password>
  <cod_pedido>342156</cod_pedido>
<borrarPedido>';

¿>
<form
action="http://www.azetadistribuciones.es/html/servicios_web/rest/inde
x.php?service=borrarPedido&format=xml" method="post"
enctype="multipart/form-data">
      <input type="hidden" name="xml" value="<?php echo $xmlBorrar?>">
</form>


 Nombre campo             Tipo       Obligatorio   Descripción
 usuario                  Cadena     Si            Usuario de identificación, coincide con el
                                                   usuario de acceso a la web de Azeta para
                                                   la librería.
 password                 Cadena     Si            Clave de identificación, coincide con la
                                                   clave de acceso a la web de Azeta para la
                                                   librería.
 cod_pedido               Cadena     Si            Código de pedido en Azeta. Coincide con
                                                   el campo “pedido_azeta” que devuelve la
                                                   llamada al servicio web que comunica un
                                                   pedido.



                                                                                                7
Servicios Web Dropshipping Azeta_ Guía del programador


Respuesta del servicio

<?xml version="1.0" encoding="UTF-8"?>
<borrar_pedido>
        <codigo_error>OK</codigo_error>
        <descripcion_error>Pedido eliminado</descripcion_error>
</borrar_pedido>

 Nombre campo            Tipo     Descripción
 codigo_error            Cadena   OK  Pedido eliminado
                                  -1  El pedido no existe o no es del usuario identificado
                                  -2  Pedido en preparación, no puede ser eliminado
                                  -3  Error xml de la llamada
                                  -4  Usuario no reconocido
 descripcion_error       Cadena


4. Anexo códigos Error

4.1 Servicio web: pedidoDropShipping

Cualquiera de estos códigos de error impide crear un pedido en Azeta.

 Codigo              Descripción                    Observaciones
 -1                  XML incorrecto                 El XML de llamada al servicio web no está
                                                    bien formado.
 -2                  Validación de datos            Los datos que vienen en el XML no
                                                    cumplen las condiciones del fichero xsd.
 -3                  Usuario no identificado        Las claves de acceso no corresponden
                                                    con ningún cliente de Azeta
 -4                  Error interno                  Error interno
 -5                  No se permite envío a ese      Se ha especificado un código postal
                     código postal                  incorrecto o que Azeta no da servicio de
                                                    envío.
 -6                  PEDIDO ANULADO: No se ha       Se ha especificado servir_completo = S al
                     podido reservar todo el        pedido, y no se ha podido reservar todo
                     pedido                         lo solicitado.
 -7                  El pedido no tiene ninguna     El pedido no tiene ninguna reserva, no se
                     reserva                        crea ningún pedido en Azeta.
                                                    IMPORTANTE: Recuerde que este tipo de
                                                    pedidos no deja artículos pendientes,
                                                    solo se queda en el pedido lo que se
                                                    reserve.
 -8                  Modo pruebas                   Está activo el modo pruebas. Recuerde
                                                    que por defecto el modo pruebas está
                                                    activo, para poder pasar pedido “reales”
                                                    debe pasar en la llamada
                                                    <modo_pruebas>N</modo_pruebas>




                                                                                                8
```
