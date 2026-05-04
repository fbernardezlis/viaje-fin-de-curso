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
 * @var bool $consent_required
 */
$consent_required = !empty($consent_required);
$qrAttachUrl = esc_url_raw(home_url('/qr/' . rawurlencode((string) $token)));
$restConsentUrl = esc_url_raw(rest_url('vfc/v1/privacy/consent'));
?>
<section class="vfc-portal-shell vfc-portal-auth">
    <div class="vfc-portal-card">
        <?php if ($invalid): ?>
            <h1><?php esc_html_e('QR no válido', 'vfc-portal'); ?></h1>
            <p><?php esc_html_e('El enlace QR no se reconoce. Comprueba que la URL sea la correcta o contacta con el alumno o el centro.', 'vfc-portal'); ?></p>
            <a class="vfc-portal-btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Ir a la tienda', 'vfc-portal'); ?></a>
        <?php elseif ($inactive): ?>
            <h1><?php esc_html_e('Edición no activa', 'vfc-portal'); ?></h1>
            <p><?php esc_html_e('Este QR pertenece a una edición que aún no está activa o ya ha terminado. No es posible vincular compras.', 'vfc-portal'); ?></p>
            <a class="vfc-portal-btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Volver al inicio', 'vfc-portal'); ?></a>
        <?php else: ?>
            <?php if ($consent_required): ?>
                <h1><?php esc_html_e('Consentimiento de cookies', 'vfc-portal'); ?></h1>
                <p class="vfc-portal-muted"><?php esc_html_e('Para vincular las compras a este alumno debe aceptar las cookies funcionales (cookie técnica vfc_beneficiario). Consulte la política de cookies del sitio si lo desea.', 'vfc-portal'); ?></p>
            <?php else: ?>
                <h1><?php esc_html_e('Comprar para este alumno', 'vfc-portal'); ?></h1>
                <p class="vfc-portal-muted"><?php esc_html_e('Si confirmas, las compras que hagas durante los próximos 30 días se vincularán a este alumno y aportarán saldo a su viaje.', 'vfc-portal'); ?></p>
            <?php endif; ?>

            <ul class="vfc-portal-list vfc-portal-list-static">
                <li><strong><?php esc_html_e('Alumno:', 'vfc-portal'); ?></strong> <?php echo esc_html((string) $matricula?->alias); ?></li>
                <li><strong><?php esc_html_e('Edición:', 'vfc-portal'); ?></strong> <?php echo esc_html((string) ($edicion?->nombre ?? '—')); ?></li>
                <li><strong><?php esc_html_e('Centro:', 'vfc-portal'); ?></strong> <?php echo esc_html((string) ($centro?->nombre ?? '—')); ?></li>
            </ul>

            <?php if (!$consent_required): ?>
                <p class="vfc-portal-muted">
                    <?php esc_html_e('Puedes salir en cualquier momento desde el banner que aparecerá en la tienda.', 'vfc-portal'); ?>
                </p>
            <?php endif; ?>

            <button type="button" class="vfc-portal-btn vfc-portal-btn-primary" id="vfc-portal-qr-continue">
                <?php echo $consent_required
                    ? esc_html__('Aceptar cookies funcionales y continuar', 'vfc-portal')
                    : esc_html__('Aceptar y empezar a comprar', 'vfc-portal'); ?>
            </button>
            <a class="vfc-portal-btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Cancelar', 'vfc-portal'); ?></a>
            <script>
            (function () {
                var btn = document.getElementById('vfc-portal-qr-continue');
                if (!btn) return;
                var meta = document.querySelector('meta[name="vfc-rest-nonce"]');
                var nonce = meta ? meta.getAttribute('content') : '';
                var rest = <?php echo wp_json_encode($restConsentUrl); ?>;
                var next = <?php echo wp_json_encode($qrAttachUrl); ?>;
                btn.addEventListener('click', function () {
                    btn.disabled = true;
                    fetch(rest, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': nonce
                        },
                        body: JSON.stringify({ functional: true, analytics: false, revoke: false })
                    }).then(function (r) {
                        if (r.ok) {
                            window.location.href = next;
                        } else {
                            btn.disabled = false;
                            alert(<?php echo wp_json_encode(__('No se pudo guardar el consentimiento. Recargue la página e inténtelo de nuevo.', 'vfc-portal')); ?>);
                        }
                    }).catch(function () {
                        btn.disabled = false;
                        alert(<?php echo wp_json_encode(__('Error de red. Inténtelo de nuevo.', 'vfc-portal')); ?>);
                    });
                });
            })();
            </script>
        <?php endif; ?>
    </div>
</section>
