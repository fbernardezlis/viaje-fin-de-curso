<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * @var string $token
 * @var \VFC\Core\Domain\Matricula\Matricula|null $matricula
 * @var \VFC\Core\Domain\Edicion\Edicion|null $edicion
 * @var \VFC\Core\Domain\Centro\Centro|null $centro
 * @var bool $invalid
 * @var bool $inactive
 */
?>
<section class="vfc-portal-shell vfc-portal-auth">
    <div class="vfc-portal-card">
        <?php if ($invalid): ?>
            <h1><?php esc_html_e('QR no válido', 'vfc-portal'); ?></h1>
            <p><?php esc_html_e('El enlace QR no se reconoce o ha caducado. Pide un QR nuevo desde tu portal.', 'vfc-portal'); ?></p>
            <a class="vfc-portal-btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Ir a la tienda', 'vfc-portal'); ?></a>
        <?php elseif ($inactive): ?>
            <h1><?php esc_html_e('Edición no activa', 'vfc-portal'); ?></h1>
            <p><?php esc_html_e('Este QR pertenece a una edición que aún no está activa o ya ha terminado. No es posible vincular compras.', 'vfc-portal'); ?></p>
            <a class="vfc-portal-btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Volver al inicio', 'vfc-portal'); ?></a>
        <?php else: ?>
            <h1><?php esc_html_e('Comprar para este alumno', 'vfc-portal'); ?></h1>
            <p class="vfc-portal-muted"><?php esc_html_e('Si confirmas, las compras que hagas durante los próximos 30 días se vincularán a este alumno y aportarán saldo a su viaje.', 'vfc-portal'); ?></p>

            <ul class="vfc-portal-list vfc-portal-list-static">
                <li><strong><?php esc_html_e('Alumno:', 'vfc-portal'); ?></strong> <?php echo esc_html((string) $matricula?->alias); ?></li>
                <li><strong><?php esc_html_e('Edición:', 'vfc-portal'); ?></strong> <?php echo esc_html((string) ($edicion?->nombre ?? '—')); ?></li>
                <li><strong><?php esc_html_e('Centro:', 'vfc-portal'); ?></strong> <?php echo esc_html((string) ($centro?->nombre ?? '—')); ?></li>
            </ul>

            <p class="vfc-portal-muted">
                <?php esc_html_e('Puedes salir en cualquier momento desde el banner que aparecerá en la tienda.', 'vfc-portal'); ?>
            </p>

            <a class="vfc-portal-btn vfc-portal-btn-primary" href="<?php echo esc_url(home_url('/qr/' . rawurlencode((string) $token))); ?>">
                <?php esc_html_e('Aceptar y empezar a comprar', 'vfc-portal'); ?>
            </a>
            <a class="vfc-portal-btn" href="<?php echo esc_url(home_url('/')); ?>">
                <?php esc_html_e('Cancelar', 'vfc-portal'); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
