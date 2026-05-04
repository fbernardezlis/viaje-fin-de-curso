<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="vfc-portal-footer">
    <div class="vfc-portal-shell">
        <small>
            &copy; <?php echo esc_html(gmdate('Y')); ?>
            <?php echo esc_html(get_bloginfo('name')); ?>
            · <?php esc_html_e('Portal privado VFC', 'vfc-portal'); ?>
            <?php
            $prefsUrl = esc_url(add_query_arg('vfc_cookie_prefs', '1', home_url('/')));
            $cookiesUrl = class_exists(\VFC\Core\Privacy\LegalPages::class)
                ? \VFC\Core\Privacy\LegalPages::urlCookies()
                : '';
            ?>
            · <a href="<?php echo $prefsUrl; ?>"><?php esc_html_e('Preferencias de cookies', 'vfc-portal'); ?></a>
            <?php if ($cookiesUrl !== ''): ?>
                · <a href="<?php echo esc_url($cookiesUrl); ?>"><?php esc_html_e('Política de cookies', 'vfc-portal'); ?></a>
            <?php endif; ?>
        </small>
    </div>
</footer>
