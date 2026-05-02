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

Si ves errores del tipo `certificate is not valid` al hacer `pull`, suele deberse a inspección HTTPS (empresa, antivirus, VPN). Revisa certificados en Docker Desktop, proxy corporativo o prueba otra red; el `docker-compose.yml` no necesita cambios.
