<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $error */
$errors = [
    'nonce' => __('La sesión ha caducado. Inténtalo de nuevo.', 'vfc-portal'),
    'empty' => __('Indica usuario y contraseña.', 'vfc-portal'),
    'invalid' => __('Usuario o contraseña incorrectos.', 'vfc-portal'),
];
?>
<section class="vfc-portal-shell vfc-portal-auth">
    <div class="vfc-portal-card">
        <h1><?php esc_html_e('Acceso al portal', 'vfc-portal'); ?></h1>
        <p class="vfc-portal-muted"><?php esc_html_e('Introduce tu usuario y contraseña para acceder a tu área privada.', 'vfc-portal'); ?></p>

        <?php if ($error !== '' && isset($errors[$error])): ?>
            <div class="vfc-portal-flash vfc-portal-flash-error"><?php echo esc_html($errors[$error]); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="vfc-portal-form">
            <input type="hidden" name="action" value="vfc_portal_login">
            <?php wp_nonce_field(\VFC\Portal\Auth\AuthController::NONCE_LOGIN); ?>

            <label for="vfc-log"><?php esc_html_e('Usuario o email', 'vfc-portal'); ?></label>
            <input id="vfc-log" name="log" type="text" autocomplete="username" required>

            <label for="vfc-pwd"><?php esc_html_e('Contraseña', 'vfc-portal'); ?></label>
            <input id="vfc-pwd" name="pwd" type="password" autocomplete="current-password" required>

            <label class="vfc-portal-checkbox">
                <input type="checkbox" name="rememberme" value="1">
                <?php esc_html_e('Mantener sesión iniciada', 'vfc-portal'); ?>
            </label>

            <button type="submit" class="vfc-portal-btn vfc-portal-btn-primary"><?php esc_html_e('Entrar', 'vfc-portal'); ?></button>
        </form>

        <p class="vfc-portal-muted">
            <a href="<?php echo esc_url(home_url('/portal/reset')); ?>"><?php esc_html_e('¿Olvidaste tu contraseña?', 'vfc-portal'); ?></a>
        </p>
    </div>
</section>
