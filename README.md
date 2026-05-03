# Viaje fin de curso — WordPress + WooCommerce (Docker)

Tienda WordPress con WooCommerce, pensada para ser ligera y fácil de extender con plugins propios en `wp-content/plugins/`.

## Requisitos

- Docker Desktop en ejecución

## Arranque

1. Copia variables de entorno (solo la primera vez):

   ```powershell
   copy .env.example .env
   ```

2. Levanta los servicios:

   ```powershell
   docker compose up -d
   ```

3. Abre **http://localhost:8080** (puerto configurable con `WP_PORT` en `.env`).

4. Completa el asistente de instalación de WordPress.

5. **WooCommerce**: en el escritorio, *Plugins → Añadir nuevo*, busca *WooCommerce* e instálalo (así solo cargas la tienda cuando la necesites).

### Mensaje «¡Disculpa este desastre!…» (pantalla magenta)

Eso es el **modo próximamente / visibilidad del sitio** de **WooCommerce**, no un fallo del proyecto.

**En wp-admin:** *WooCommerce → Ajustes → pestaña «Visibilidad del sitio»* (o *Site visibility*) → elige **En vivo** / **Live** (no «Próximamente» / *Coming soon*) y guarda.

**Por CLI dentro del contenedor** (útil si no puedes entrar al menú):

```powershell
docker cp docker/scripts/disable-wc-coming-soon.php vfc-wordpress:/tmp/disable-wc-coming-soon.php
docker exec vfc-wordpress php /tmp/disable-wc-coming-soon.php
docker exec vfc-wordpress rm -f /tmp/disable-wc-coming-soon.php
```

Luego recarga la web con **Ctrl+F5**. Si el hosting tiene caché, purgala.

## Estructura

| Ruta | Descripción |
|------|-------------|
| `docker-compose.yml` | WordPress **6.9.4** (Apache + PHP 8.2) y MariaDB 11.4 (imágenes desde `mirror.gcr.io`, equivalentes a las de Docker Hub) |
| `docker/php/zz-performance.ini` | OPcache y límites razonables para desarrollo/tienda |
| `wp-content/plugins/` | Plugins personalizados del proyecto |
| `wp-content/themes/` | Temas hijos o personalizados |

El **núcleo** de WordPress va en el volumen Docker **`wp_core`** (la imagen solo aporta la versión al crear o vaciar ese volumen). **`wp-content`** sigue en la carpeta del repo. La **base de datos** está en el volumen **`db_data`**.

**Subir solo la versión de WordPress** (cambiar el tag de la imagen en `docker-compose.yml`): para que el contenedor use el núcleo nuevo, borra el volumen del núcleo y vuelve a levantar (no borra la BD ni `wp-content`):

```powershell
docker compose down
docker volume rm viaje-fin-de-curso_wp_core
docker compose up -d
```

## Portal VFC (`/portal/login`, etc.)

Las rutas del plugin **vfc-portal** dependen de **permalinks no planos** y de que **Apache aplique `mod_rewrite`** sobre `index.php`. Si **http://localhost:8080/portal/login** devuelve **404** (página de Apache, no WordPress):

1. Asegúrate de que en *Ajustes → Enlaces permanentes* no esté seleccionado “Simple” (o ejecuta una vez el script de abajo).
2. Dentro del contenedor, regenera permalink + reglas en `.htaccess`:

   ```powershell
   docker cp docker/scripts/flush-rewrites.php vfc-wordpress:/tmp/flush-rewrites.php
   docker exec vfc-wordpress php /tmp/flush-rewrites.php
   docker exec vfc-wordpress rm -f /tmp/flush-rewrites.php
   ```

   WordPress redirige `/portal/login` → `/portal/login/` (301); lo normal es **200** en la URL con barra final.

Para **fijar una contraseña de prueba** a un alumno (`vfc_alumno`) en local (último alumno creado, p. ej. tras un smoke test):

```powershell
docker cp docker/scripts/reset-alumno-dev-password.php vfc-wordpress:/tmp/reset-alumno-dev-password.php
docker exec vfc-wordpress php /tmp/reset-alumno-dev-password.php "TuClaveSegura"
docker exec vfc-wordpress rm -f /tmp/reset-alumno-dev-password.php
```

Tercer argumento opcional: `login` o `email` concreto.

## Si falla la portada (`http://localhost:8080/`)

Desde el contenedor la portada suele responder **HTTP 200** con el tema activo (`vfc-portal` hijo de Twenty Twenty-Five). Si en el navegador ves error, corta de carga o “no es posible conectar”:

1. **URL y protocolo**: usa **`http://localhost:8080`** (no `https://` en local salvo que hayas configurado TLS; el certificado suele fallar y el navegador muestra error de seguridad).
2. **Docker**: `docker compose ps` — el servicio `wordpress` debe estar *Up*. Si no, `docker compose up -d`.
3. **Puerto**: en `.env`, `WP_PORT` define el mapeo (`8080` por defecto). La URL debe coincidir con `Ajustes → Generales` (`Dirección de WordPress` y `Dirección del sitio`); si cambiaste el puerto, actualiza también esas URLs en wp-admin.
4. **Comprobación rápida desde PowerShell** (debe verse `HTTP/1.1 200` o `HTTP/1.0 200`):

   ```powershell
   curl.exe -I --max-time 10 http://localhost:8080/
   ```

5. **Diagnóstico dentro de WordPress** (opciones de URL, tema activo, portada):

   ```powershell
   docker cp docker/scripts/diag-home.php vfc-wordpress:/tmp/diag-home.php
   docker exec vfc-wordpress php /tmp/diag-home.php
   docker exec vfc-wordpress rm -f /tmp/diag-home.php
   ```

6. **Errores PHP**: si aparece “Ha habido un error crítico en esta web”, activa temporalmente `WORDPRESS_DEBUG=1` en `.env`, reinicia el contenedor y revisa `wp-content/debug.log` (si existe).

## Comandos útiles

```powershell
docker compose logs -f wordpress
docker compose down
```

`docker compose down` no borra la base de datos (el volumen `db_data` se mantiene). Para empezar de cero: `docker compose down -v` (elimina también los datos de MariaDB).

## Si falla la descarga de imágenes (TLS / certificado)

El `docker-compose.yml` usa **`mirror.gcr.io/library/…`** (espejo de Google de las imágenes oficiales de Docker Hub) para evitar en muchos entornos el error TLS contra el CDN R2 de Hub (`*.r2.cloudflarestorage.com`).

Si aun así falla el `pull`, el cliente HTTPS puede estar viendo un certificado que **no coincide con el nombre del servidor** o no confía en la CA (muy habitual con **proxy o inspección SSL corporativa**).

**Qué puedes hacer (elige según tu entorno):**

1. **Primera descarga en red “limpia”** (datos móviles, casa, otra WiFi sin proxy): ejecuta una vez `docker compose up -d`; las imágenes quedan en caché y luego suele funcionar también en la oficina.
2. **IT / certificado corporativo**: instala la **CA raíz** de la empresa en el entorno que usa Docker Desktop (con backend **WSL 2**, a veces hace falta copiar el `.crt` a la distro Linux y ejecutar `sudo update-ca-certificates`, o seguir la guía de tu organización para Docker).
3. **Exclusión en el proxy/firewall** para dominios de registro e imágenes de Docker Hub (pídeselo a sistemas si no puedes cambiarlo tú).

Más contexto en el [foro de Docker sobre errores x509 al hacer pull](https://forums.docker.com/t/tls-failed-to-verify-certificate-x509-docker-hub/137486).

Para probar solo las descargas (mismas imágenes que usa el compose):

```powershell
docker pull mirror.gcr.io/library/wordpress:6.9.4-php8.2-apache
docker pull mirror.gcr.io/library/mariadb:11.4
```

Luego, en la carpeta del proyecto: `docker compose up -d`.
