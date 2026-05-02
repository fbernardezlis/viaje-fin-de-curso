<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Por seguridad, en MVP no borramos tablas ni datos al desinstalar.
// El borrado destructivo se hará desde un comando WP-CLI explícito (futuro).
