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
