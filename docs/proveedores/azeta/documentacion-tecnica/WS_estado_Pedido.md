# WS estado Pedido

**Fuente:** `WS_estado_Pedido.pdf` (texto extraído automáticamente del PDF)

---

```text
Estado pedido
                                     Servicio web Azeta distribuciones

Fichero definición del servicio web

http://www.azetadistribuciones.es/servicios_web/rest/estadoPedido.xsd

Url llamada al servicio web (POST)
https://www.azetadistribuciones.es/servicios_web/rest/index.php?service=estadoPe
dido&format=xml



Ejemplo de llamada al servicio
<?php

$xmlEstadoPedido =
“<?xml version="1.0" encoding="UTF-8"?>
<estadoPedido>
 <usuario>Login</usuario>
 <password>Password</password>
 <num_pedido>0000001</num_pedido>
</estadoPedido>”

?>



<form
action="https://www.azetadistribuciones.es/html/servicios_web/rest/index.php?service=esta
doPedido&format=xml" method="post" enctype="multipart/form-data">
      <input type="hidden" name="xml" value="<?php echo $xmlEstadoPedido?>">
</form>


Ejemplo Salida
<?xml version="1.0" encoding="UTF-8"?>
<estado_pedido>
 <codigo_error>0</codigo_error>
 <descripcion_error>OK</descripcion_error>
 <linea_pedido>
  <ean>9781840226355</ean>
  <codigo_estado>0</codigo_estado>
  <descripcion_estado>Pendiente</descripcion_estado>
  <fecha_transporte></fecha_transporte>
  <codigo_seguimiento></codigo_seguimiento>
  <cantidad>1</cantidad>
  <num_albaran></num_albaran>
  <codAlbaran></codAlbaran>
  <transportista></transportista>
  <url_seguimiento></url_seguimiento>
 </linea_pedido>
 <linea_pedido>
  <ean>9788417059996</ean>
  <codigo_estado>1</codigo_estado>
  <descripcion_estado>Reservado</descripcion_estado>
  <fecha_transporte></fecha_transporte>
  <codigo_seguimiento></codigo_seguimiento>
  <cantidad>1</cantidad>
  <num_albaran></num_albaran>
  <codAlbaran></codAlbaran>
  <transportista></transportista>
  <url_seguimiento></url_seguimiento>
 </linea_pedido>
 <linea_pedido>
  <ean>9788424666873</ean>
  <codigo_estado>1</codigo_estado>
  <descripcion_estado>Reservado</descripcion_estado>
  <fecha_transporte></fecha_transporte>
  <codigo_seguimiento></codigo_seguimiento>
  <cantidad>1</cantidad>
  <num_albaran></num_albaran>
  <codAlbaran></codAlbaran>
  <transportista></transportista>
  <url_seguimiento></url_seguimiento>
 </linea_pedido>
 <linea_pedido>
  <ean>9780007447848</ean>
  <codigo_estado>1</codigo_estado>
  <descripcion_estado>Reservado</descripcion_estado>
  <fecha_transporte></fecha_transporte>
  <codigo_seguimiento></codigo_seguimiento>
  <cantidad>1</cantidad>
  <num_albaran></num_albaran>
  <codAlbaran></codAlbaran>
  <transportista></transportista>
  <url_seguimiento></url_seguimiento>
 </linea_pedido>
</estado_pedido>

Descripción campos de salida

Nombre Campo                   Tipo           Descripción
ean                            cadena
codigo_estado                  entero         0 => Pendiente
                                              1 => Reservado
                                              2 => Preparándose
                                              3 => Enviado (En transportista)
                                              4 => Devuelto
                                              5 => Incidencia
Descripcion_estado             cadena         Descripción del campo anterior
fecha_transporte               fecha          dd/mm/yyyy
codigo_seguimiento             cadena         Código de seguimiento que le da el
                                              transportista
cantidad                       entero         Cantidad pedida
num_albaran       cadena   Referencia externa del albarán (Solo a
                           partir del estado 3)
cod_albaran       cadena   Referencia interna del albaran para Azeta
Transportista     Entero   Código del transportista para Azeta
url_seguimiento   Cadena   Url que muestra la web del transportista
                           con el estado del paquete.
```
