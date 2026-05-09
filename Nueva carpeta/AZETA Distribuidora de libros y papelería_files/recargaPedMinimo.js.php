
var cliente = 410699;
var timeout = 300000;  //Segundos entre actualizaciones

function actualizarLocalStorage(origen = 'automatico') {
    const tiempoActual = Date.now(); // Obtener la hora actual en milisegundos
    const ultimaActualizacion = localStorage.getItem("ultimaActualizacion");

    // Si no hay una última actualización registrada o han pasado 5 minutos (300,000 ms)
    if (!ultimaActualizacion || tiempoActual - parseInt(ultimaActualizacion) >= timeout) {
        actualizarDatosGesdis(origen);
    } else {
        mostrarDatos();
        console.log("Aún no han pasado 5 minutos, no se actualiza.");
    }
}

function actualizarDatosGesdis(origen) {

    separador = '|';

    //Añadir local storage la fecha de ultima actualización
    const tiempoActual = Date.now(); // Obtener la hora actual en milisegundos
    localStorage.setItem("ultimaActualizacion", tiempoActual.toString());

    //Llamar a gesdis para traerse los datos
            url = 'https://www.azetadistribuciones.es/utilidades/generadatosPedMinimo.php?cliente='+cliente+'&cache='+Math.random();
    
    datosPedMin = llamadaAjaxPedMin(url);
    //alert(datosPedMin);
    arrayDatos = datosPedMin.split(separador);

    //Guardar los datos
    localStorage.setItem("cliente", arrayDatos[0]);
    localStorage.setItem("importe_reservado", arrayDatos[1]);
    localStorage.setItem("importe_preparacion", arrayDatos[2]);
    localStorage.setItem("importe_agencia", arrayDatos[3]);
    localStorage.setItem("total_pedido", arrayDatos[4]);
    localStorage.setItem("pedido_minimo", arrayDatos[5]);
    localStorage.setItem("enviar_gratis", arrayDatos[6]);

    mostrarDatos();

    console.log("Datos actualizados. Origen: " + origen);

}

function forzarRecargaPedMinimo(origen = 'manual')
{
    //En función del origen podremos ejecutar una lógica u otra; por ahora simplemente se lanza el actualizarLocalStorage para mantener el intervalo de 1 minuto
    actualizarLocalStorage(origen);
}

function llamadaAjaxPedMin(url)
{
    var xhttp = new XMLHttpRequest();
    var salida;
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200)
            salida = this.responseText;
    };
    xhttp.open('GET', url, false);
    xhttp.send();
    return salida;
}

function mostrarDatos() {

    // Recojo los datos
    clienteLS           = localStorage.getItem("cliente");

    if (clienteLS != cliente) {
        actualizarDatosGesdis("Cambio cliente mismo ordenador");
        return;
    }

//pintaContador();

    importe_reservado   = localStorage.getItem("importe_reservado");
    importe_preparacion = localStorage.getItem("importe_preparacion");
    importe_agencia     = localStorage.getItem("importe_agencia");
    total_pedido        = localStorage.getItem("total_pedido");
    pedido_minimo       = localStorage.getItem("pedido_minimo");
    enviar_gratis       = localStorage.getItem("enviar_gratis");

// Convertir strings "123,45" a float 123.45
    total_pedido   = parseFloat(total_pedido.replace(',', '.'));
    pedido_minimo  = parseFloat(pedido_minimo.replace(',', '.'));

    /*
    if (enviar_gratis == 'S') {
       document.getElementById("idMensajeContador").classList.replace("destacado-rojo","destacado-verde");
    } else {
       document.getElementById("idMensajeContador").classList.replace("destacado-verde","destacado-rojo");
    }
    */

// Calcular porcentaje de progreso
    if (pedido_minimo > 0) {
        progreso = Math.round((total_pedido / pedido_minimo) * 100);
    } else {
        progreso = 0;
    }
// Límite máximo 100%
    progreso = Math.min(progreso, 100);

// Lógica para el mensaje de envío gratis
    if (total_pedido == 0) {
        // Caso 1: El pedido está en cero
        mensajeEnvio = "Finalice su pedido para conseguir su envío gratis";
        document.getElementById("iconOverlay").style.display = "none";
    } else if (total_pedido < pedido_minimo) {
        // Caso 2: Aún no llegamos al mínimo
        diferencia = pedido_minimo - total_pedido;
        // formatear la diferencia con 2 decimales y coma
        faltaFormateada = diferencia.toFixed(2).replace('.',',');
        mensajeEnvio = "Añada "+"<span class='destacado-rojo'>"+faltaFormateada+"€</span>"+" para conseguir su envío gratis";
        // Cambiamos el iconOverlay para que muestre el "plus"
        let overlay = document.getElementById("iconOverlay");
        overlay.src = "/images/icono-plus.svg";
        overlay.style.display = "block";
    } else {
        // Caso 3: Ya cumple el mínimo
        mensajeEnvio = "<span class='destacado-verde'>¡Envío gratis conseguido!</span>";
        // Cambiamos el iconOverlay para que muestre el "check"
        let overlay = document.getElementById("iconOverlay");
        overlay.src = "/images/icono-check.svg";
        overlay.style.display = "block";
    }

    document.getElementById('mensajeEnvio').innerHTML       = mensajeEnvio;
    document.getElementById('importe_reservado').innerHTML  = "Reservas: "+"<span class='destacado-amarillo'>"+importe_reservado+"€</span>";
    document.getElementById('importe_preparacion').innerHTML= "Preparación : "+"<span class='destacado-amarillo'>"+importe_preparacion+"€</span>";
    document.getElementById('importe_agencia').innerHTML    = "Entregado a Agencia: "+"<span class='destacado-verde'>"+importe_agencia+"€</span>";

    var barra = document.getElementById("barraProgreso");
    barra.style.width = progreso + "%";

// Si está al 100%, añadimos la clase 'completo'
    if (progreso >= 100) {
        barra.classList.add("completo");
    } else {
        barra.classList.remove("completo");
    }

}


// Ejecutar la actualización cada 5 minutos automáticamente
// setInterval(actualizarLocalStorage, timeout);

// También se puede llamar manualmente sin que sobreescriba antes de tiempo
actualizarLocalStorage();
