<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $msg */
$messages = [
    'sent' => __('Si el usuario existe, te hemos enviado un email para recuperar el acceso.', 'vfc-portal'),
    'nonce' => __('La sesión ha caducado. Inténtalo de nuevo.', 'vfc-portal'),
    'empty' => __('Indica un usuario o email.', 'vfc-portal'),
    'invalid' => __('No hemos podido procesar la solicitud.', 'vfc-portal'),
];
?>
<section class="vfc-portal-shell vfc-portal-auth">
    <div class="vfc-portal-card">
        <h1><?php esc_html_e('Recuperar contraseña', 'vfc-portal'); ?></h1>
        <p class="vfc-portal-muted"><?php esc_html_e('Te enviaremos un email con un enlace para establecer una nueva contraseña.', 'vfc-portal'); ?></p>

        <?php if ($msg !== '' && isset($messages[$msg])): ?>
            <div class="vfc-portal-flash <?php echo $msg === 'sent' ? 'vfc-portal-flash-success' : 'vfc-portal-flash-error'; ?>">
                <?php echo esc_html($messages[$msg]); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="vfc-portal-form">
            <input type="hidden" name="action" value="vfc_portal_reset">
            <?php wp_nonce_field(\VFC\Portal\Auth\AuthController::NONCE_RESET); ?>

            <label for="vfc-reset-login"><?php esc_html_e('Usuario o email', 'vfc-portal'); ?></label>
            <input id="vfc-reset-login" name="user_login" type="text" autocomplete="username" required>

            <button type="submit" class="vfc-portal-btn vfc-portal-btn-primary"><?php esc_html_e('Enviar enlace', 'vfc-portal'); ?></button>
        </form>

        <p class="vfc-portal-muted">
            <a href="<?php echo esc_url(home_url('/portal/login')); ?>"><?php esc_html_e('Volver al login', 'vfc-portal'); ?></a>
        </p>
    </div>
</section>
