# Fase 3 — vfc-portal

Plan vivo de la Fase 3. La Fase 2 (`vfc-woocommerce`) ya esta cerrada y en `main`. Esta fase introduce el portal frontend privado para alumnos, tutores y admins de colegio, con look propio (no parece WP), generacion de imagen QR y endpoints REST especificos para el portal.

## Decisiones cerradas

- **Stack del portal**: plantillas PHP propias (servidas por el plugin via `template_include`) + islas JS Vanilla para interactividad puntual (saldo en vivo, switcher tutor, copia portapapeles).
- **Routing**: rewrite rules dedicadas bajo `/portal/*`. Las pages WordPress no se usan.
- **Areas en Fase 3**: panel Alumno, panel Tutor (con switcher de alumno), panel Admin de Colegio (lectura ampliada del centro), pagina publica de QR (`/portal/qr/{token}` pre-cookie), Auth (login, logout, reset).
- **Imagen QR**: [endroid/qr-code v6](https://github.com/endroid/qr-code) instalada con Composer en `wp-content/plugins/vfc-portal/`.
- **REST auth**: cookie de sesion WP + `wp_create_nonce('wp_rest')` inyectado en cada vista. El portal nunca se abre sin login (excepto `/portal/qr/{token}` y `/portal/login`).
- **Look & feel**: CSS custom basado en tokens (paleta, tipografia, spacing, radii, sombras) en un solo archivo `portal.css`. Sin frameworks. Tipografia system stack y un acento corporativo (`#0a3d62`).
- **HPOS / WC**: el portal solo lee pedidos completados via `wc_get_orders` (HPOS-safe). No se tocan flujos de Fase 2.
- **i18n**: textdomain `vfc-portal`, ES por defecto. CA y EN llegan en Fase 4.

## Arquitectura

Nuevo plugin `wp-content/plugins/vfc-portal/`. El theme `vfc-portal` existente sigue activo pero el plugin **secuestra** el render para todas las URLs `^portal/`.

### Estructura de archivos nuevos

```
wp-content/plugins/vfc-portal/
  vfc-portal.php                 (header del plugin + bootstrap)
  composer.json
  composer.lock
  vendor/                        (Endroid + deps; sin tocar a mano)
  src/
    Autoloader.php               (PSR-4 manual para VFC\Portal\)
    Plugin.php                   (boot: dependencias + modulos)
    Activator.php                (rewrites + flush)
    Deactivator.php              (flush)
    Routing/
      PortalRouter.php           (rewrites, query vars, template_include, asset enqueue, dispatch a vistas)
      Permissions.php            (helpers: currentUserRoles, requireAlumnoOrTutor, requireAdminColegio, requireSuperAdmin)
    Auth/
      AuthController.php         (login/logout/reset POST handlers)
      RoleRedirector.php         (decide panel destino segun rol)
    Services/
      QrImageService.php         (Endroid; PNG/SVG; cache disco corto via transient)
      ResendQrService.php        (rota token y reenvia mail; reusa QrEmailService)
      DashboardService.php       (consultas agregadas para el admin de colegio)
    Views/
      LoginView.php
      ResetView.php
      AlumnoView.php             (saldo + historial + QR)
      TutorView.php              (lista + switcher + reusa AlumnoView)
      ColegioView.php            (alumnos, ediciones, productos, liquidaciones)
      PublicQrView.php           (pre-cookie info + boton aceptar)
      Layout.php                 (helpers de render: header, footer, banner, nonce REST, csrf)
    Rest/
      SaldoController.php        (GET /vfc/v1/portal/saldo?alumno_id&edicion_id)
      HistorialController.php    (GET /vfc/v1/portal/movimientos?alumno_id&edicion_id&estado&page)
      MisAlumnosController.php   (GET /vfc/v1/portal/mis-alumnos)  [tutor]
      CentroController.php       (GET /vfc/v1/portal/centro/{id} dashboard)  [admin colegio]
      ResendQrController.php     (POST /vfc/v1/portal/qr/resend)
  templates/
    layout.php                   (HTML base con tokens CSS y nonce REST)
    parts/
      header.php
      footer.php
      flash.php
    pages/
      login.php
      reset.php
      alumno.php
      tutor.php
      colegio.php
      public-qr.php
  assets/
    css/portal.css               (tokens + componentes)
    js/portal.js                 (utilidades fetch+nonce)
    js/saldo.js                  (refresca saldo cada N seg)
    js/switcher.js               (tutor: cambia alumno activo)
    js/copy.js                   (copia URL QR al portapapeles)
```

### Cambios fuera de `vfc-portal`

- [wp-content/plugins/vfc-core/src/Domain/Saldo/SaldoRepository.php](wp-content/plugins/vfc-core/src/Domain/Saldo/SaldoRepository.php) **(nuevo)**: encapsula consultas de saldo activo y movimientos del alumno/edicion. Reutilizado por REST y vistas. Sin schema bump.
- [wp-content/plugins/vfc-core/src/Domain/Centro/CentroAdminRepository.php](wp-content/plugins/vfc-core/src/Domain/Centro/CentroAdminRepository.php) **(nuevo)**: lectura/escritura de `wp_vfc_centro_admins` (solo `listCentrosForUser`, `isAdminOfCentro`, `assign`/`unassign`). Necesario para el panel del Admin de Colegio.
- [wp-content/plugins/vfc-core/src/Plugin.php](wp-content/plugins/vfc-core/src/Plugin.php): no se toca de momento; los repos nuevos se instancian on-demand desde `vfc-portal`.

## Rutas del portal

| URL                         | Quien                       | Vista              |
| --------------------------- | --------------------------- | ------------------ |
| `/portal/login`             | publico                     | `LoginView`        |
| `/portal/logout`            | logueado                    | accion + redirect  |
| `/portal/reset`             | publico                     | `ResetView`        |
| `/portal/alumno`            | rol `vfc_alumno`            | `AlumnoView`       |
| `/portal/tutor`             | rol `vfc_tutor`             | `TutorView`        |
| `/portal/tutor/{alumnoId}`  | tutor + alumno vinculado    | `TutorView` filtrada |
| `/portal/colegio`           | rol `vfc_admin_colegio`     | `ColegioView`      |
| `/portal/qr/{token}`        | publico                     | `PublicQrView`     |
| `/portal/qr-image/{matricula}.png` | autorizado          | `QrImageService::stream()` |

`/portal/qr/{token}` es **distinto** del endpoint `/qr/{token}` de Fase 2. Este ultimo emite la cookie y redirige; `/portal/qr/{token}` muestra una pagina informativa con un boton "Aceptar y comprar" que hace el POST/redirect al `/qr/{token}` real.

## Cookie de nonce y CSRF

- Todas las paginas server-rendered inyectan `<meta name="vfc-rest-nonce" content="{nonce}">` y un `window.VFC_PORTAL = { restRoot, nonce }` para las islas.
- Formularios sensibles (login, reset, resend QR) llevan `wp_nonce_field('vfc_portal_<accion>')`.
- Login/reset usan los flujos nativos de WP (`wp_signon`, `retrieve_password`) por debajo, pero el render es propio.

## REST endpoints

Namespace `vfc/v1/portal`. Permisos validados con `Permissions`.

| Metodo | Endpoint                                  | Permiso                            | Devuelve |
| ------ | ----------------------------------------- | ---------------------------------- | -------- |
| GET    | `/saldo`                                  | self / tutor de alumno / super     | `{ alumno_id, edicion_id, saldo: {bloqueado, confirmado, neto} }` |
| GET    | `/movimientos`                            | self / tutor / super               | `{ items: [...], total, page, per_page }` |
| GET    | `/mis-alumnos`                            | rol tutor                          | `{ items: [{alumno_id, alias, edicion, centro}] }` |
| GET    | `/centro/{id}`                            | admin del centro / super           | `{ centro, alumnos[], ediciones[], productos[], liquidaciones[] }` |
| POST   | `/qr/resend`                              | self alumno / tutor de alumno      | `{ ok: true }` |

## SaldoRepository (vfc-core)

```php
public function saldoNeto(int $alumnoUserId, ?int $edicionId = null): array {
    // returns { bloqueado: float, confirmado: float, neto: float }
}

public function historial(array $args = []): array {
    // args: alumno_user_id, edicion_id, estado, tipo, from, to, per_page, page
    // returns { items, total, page, per_page }
}
```

## QrImageService

- Recibe `$matriculaId`. Carga la matricula. Si no se ha cargado nunca, se rota el token (porque no se almacena en claro) y se reemplaza el del email anterior. **Decision**: para evitar invalidar cookies activas, mejor que el alumno solicite QR explicitamente desde la vista (boton "Generar nuevo QR"), que rota y descarga la imagen en la misma respuesta.
- Mientras tanto, el panel muestra un boton "Reenviar QR a mi email" que rota el token y dispara `vfc_send_qr_to_alumno`.
- La imagen no se almacena: se sirve binaria (`Content-Type: image/png`).

## Vistas

### AlumnoView

- Header con nombre alias y centro/edicion.
- Tarjeta saldo: `bloqueado`, `confirmado` (= saldo activo no liquidado), `neto`.
- Historial: tabla paginada. Filtros: edicion, estado, tipo, fechas.
- Bloque QR: imagen (lazy o on-demand), URL acortada, boton copiar, boton reenviar.

### TutorView

- Sidebar con sus alumnos (`MisAlumnosController`). Switcher.
- Resto: reusa `AlumnoView` para el alumno seleccionado (incluye saldo, historial, QR).

### ColegioView

- Tarjetas resumen: alumnos del centro, ediciones activas, liquidaciones recientes.
- Listas: alumnos, ediciones, productos del centro, liquidaciones recibidas (tabla + total).
- Solo lectura. Las acciones siguen en wp-admin.

### PublicQrView

- Recibe `token` por URL. Resuelve hash y matricula. Si no es valido, mensaje de "QR invalido".
- Si es valido: muestra alias + edicion + centro + texto explicativo "Las compras se vincularan al saldo de {alias}". Boton "Aceptar y comprar" -> redirige a `/qr/{token}` real (Fase 2) que emite la cookie y devuelve a la home.

## Verificacion

Script `docker/scripts/smoke-test-phase3.php`:
1. Activar plugins.
2. Login programatico como alumno: visitar `/portal/alumno`, verificar 200 y presencia de saldo.
3. Login como tutor con 2 alumnos vinculados: `/portal/tutor` lista 2; `MisAlumnosController` devuelve 2 con datos minimos.
4. `/portal/qr/{token}` con token valido: muestra info + boton; con token invalido: mensaje error.
5. `QrImageService` genera PNG no vacio para una matricula.
6. `ResendQrController` rota token y dispara accion `vfc_send_qr_to_alumno`.
7. Permisos: alumno NO accede a `/portal/colegio` (403); tutor NO accede a `/portal/alumno` salvo que sea su propio user; admin colegio accede al panel solo de centros que administra.
8. SaldoRepository devuelve los importes correctos (con datos de Fase 2 reales).

## Fuera de alcance Fase 3

- Polylang y traducciones extra (Fase 4).
- Banner de cookies / RGPD (Fase 4).
- Pasarela Redsys (Fase 4).
- Editar datos del usuario desde el portal (Fase 4): por ahora solo lectura + cambios soportados (alias, password).
- Generar QR en bloque (PDF imprimible) — queda para Fase 4.

## Riesgos identificados

- **Composer en plugin**: hay que ejecutar `composer install` dentro del contenedor o subir `vendor/` al repo. **Decision**: `vendor/` se sube al repo para evitar dependencia de Composer en produccion (mismo enfoque que muchos plugins en wp.org).
- **Login propio vs `wp-login.php`**: si la session expira en mitad de un POST, redirigimos a `/portal/login?next=...`.
- **Permission leakage**: el tutor podria intentar leer `/portal/alumno` con `?alumno_id=XXX` ajeno. Toda comprobacion de permisos pasa por `Permissions::canSeeAlumno($user, $alumnoId)` (chequeando `TutorAlumnoRepository` o `currentUserId === alumnoId`).
- **HMAC de cookie de Fase 2**: no se toca; la Fase 3 solo lee sesion via cookie WP estandar.
