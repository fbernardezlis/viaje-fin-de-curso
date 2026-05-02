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

## Estructura

| Ruta | Descripción |
|------|-------------|
| `docker-compose.yml` | WordPress (Apache + PHP 8.2) y MariaDB 11.4 |
| `docker/php/zz-performance.ini` | OPcache y límites razonables para desarrollo/tienda |
| `wp-content/plugins/` | Plugins personalizados del proyecto |
| `wp-content/themes/` | Temas hijos o personalizados |

El núcleo de WordPress va en la imagen; en el disco solo persistes **base de datos** (volumen Docker) y **`wp-content`** (carpeta del repo).

## Comandos útiles

```powershell
docker compose logs -f wordpress
docker compose down
```

`docker compose down` no borra la base de datos (el volumen `db_data` se mantiene). Para empezar de cero: `docker compose down -v` (elimina también los datos de MariaDB).

## Si falla la descarga de imágenes (TLS / certificado)

Si al hacer `docker compose up` aparece algo como `tls: failed to verify certificate` hacia `*.r2.cloudflarestorage.com` (CDN de capas de Docker Hub), el cliente HTTPS ve un certificado que **no coincide con el nombre del servidor** o no confía en la CA (muy habitual con **proxy o inspección SSL corporativa**).

**Qué puedes hacer (elige según tu entorno):**

1. **Primera descarga en red “limpia”** (datos móviles, casa, otra WiFi sin proxy): ejecuta una vez `docker compose up -d`; las imágenes quedan en caché y luego suele funcionar también en la oficina.
2. **IT / certificado corporativo**: instala la **CA raíz** de la empresa en el entorno que usa Docker Desktop (con backend **WSL 2**, a veces hace falta copiar el `.crt` a la distro Linux y ejecutar `sudo update-ca-certificates`, o seguir la guía de tu organización para Docker).
3. **Exclusión en el proxy/firewall** para dominios de registro e imágenes de Docker Hub (pídeselo a sistemas si no puedes cambiarlo tú).

Más contexto en el [foro de Docker sobre errores x509 al hacer pull](https://forums.docker.com/t/tls-failed-to-verify-certificate-x509-docker-hub/137486).

Cuando `docker pull wordpress:6.7-php8.2-apache` y `docker pull mariadb:11.4` funcionen sin error, en la carpeta del proyecto:

```powershell
cd "c:\Users\fber\OneDrive - Dempo Digital Solutions S.L\Documentos\Projects\viaje-fin-de-curso"
docker compose up -d
```
