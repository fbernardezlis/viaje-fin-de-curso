<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var \WP_User|null $portal_user */
$home = home_url('/');
?>
<header class="vfc-portal-header">
    <div class="vfc-portal-shell">
        <a class="vfc-portal-brand" href="<?php echo esc_url($home); ?>">
            <span class="vfc-portal-brand-mark">VFC</span>
            <span class="vfc-portal-brand-name"><?php echo esc_html(get_bloginfo('name')); ?></span>
        </a>
        <?php if ($portal_user instanceof \WP_User): ?>
            <nav class="vfc-portal-nav" aria-label="<?php esc_attr_e('Menú principal', 'vfc-portal'); ?>">
                <span class="vfc-portal-user"><?php echo esc_html($portal_user->display_name ?: $portal_user->user_login); ?></span>
                <a class="vfc-portal-link" href="<?php echo esc_url(home_url('/portal/logout')); ?>"><?php esc_html_e('Cerrar sesión', 'vfc-portal'); ?></a>
            </nav>
        <?php endif; ?>
    </div>
</header>
