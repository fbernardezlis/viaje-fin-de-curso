<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * @var array{id:int,display_name:string} $tutor
 * @var array<int, array{id:int,display_name:string,email:?string}> $alumnos
 * @var int $selected_id
 * @var array<string, mixed>|null $detail
 */
?>
<section class="vfc-portal-shell vfc-portal-dashboard">
    <header class="vfc-portal-page-header">
        <h1><?php echo esc_html(sprintf(__('Hola, %s', 'vfc-portal'), $tutor['display_name'])); ?></h1>
        <p class="vfc-portal-muted"><?php esc_html_e('Selecciona uno de tus alumnos para ver su saldo, historial y QR.', 'vfc-portal'); ?></p>
    </header>

    <?php if ($alumnos === []): ?>
        <div class="vfc-portal-card">
            <p><?php esc_html_e('Aún no tienes ningún alumno vinculado a tu cuenta. Contacta con el administrador del colegio.', 'vfc-portal'); ?></p>
        </div>
    <?php else: ?>
        <div class="vfc-portal-grid vfc-portal-grid-sidebar">
            <aside class="vfc-portal-sidebar" data-vfc-switcher>
                <h2><?php esc_html_e('Mis alumnos', 'vfc-portal'); ?></h2>
                <ul class="vfc-portal-list">
                    <?php foreach ($alumnos as $a): ?>
                        <?php $isActive = (int) $a['id'] === $selected_id; ?>
                        <li>
                            <a class="vfc-portal-list-item <?php echo $isActive ? 'is-active' : ''; ?>"
                               href="<?php echo esc_url(home_url('/portal/tutor/' . (int) $a['id'])); ?>"
                               data-alumno-id="<?php echo esc_attr((string) $a['id']); ?>">
                                <strong><?php echo esc_html($a['display_name']); ?></strong>
                                <?php if (!empty($a['email'])): ?>
                                    <small class="vfc-portal-muted"><?php echo esc_html((string) $a['email']); ?></small>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <div class="vfc-portal-detail">
                <?php if ($detail !== null): ?>
                    <header class="vfc-portal-page-header">
                        <h2><?php echo esc_html((string) $detail['alumno']['display_name']); ?></h2>
                    </header>
                    <div class="vfc-portal-grid vfc-portal-grid-3">
                        <div class="vfc-portal-card vfc-portal-stat" data-vfc-saldo data-alumno-id="<?php echo esc_attr((string) $detail['alumno']['id']); ?>">
                            <span class="vfc-portal-label"><?php esc_html_e('Saldo confirmado', 'vfc-portal'); ?></span>
                            <strong class="vfc-portal-value" data-key="confirmado"><?php echo esc_html(number_format((float) $detail['saldo']['confirmado'], 2, ',', '.')); ?> €</strong>
                        </div>
                        <div class="vfc-portal-card vfc-portal-stat">
                            <span class="vfc-portal-label"><?php esc_html_e('En bloqueo', 'vfc-portal'); ?></span>
                            <strong class="vfc-portal-value" data-key="bloqueado"><?php echo esc_html(number_format((float) $detail['saldo']['bloqueado'], 2, ',', '.')); ?> €</strong>
                        </div>
                        <div class="vfc-portal-card vfc-portal-stat">
                            <span class="vfc-portal-label"><?php esc_html_e('Liquidado', 'vfc-portal'); ?></span>
                            <strong class="vfc-portal-value" data-key="liquidado"><?php echo esc_html(number_format((float) $detail['saldo']['liquidado'], 2, ',', '.')); ?> €</strong>
                        </div>
                    </div>

                    <h3><?php esc_html_e('QR de las matrículas', 'vfc-portal'); ?></h3>
                    <?php if ((array) $detail['matriculas'] === []): ?>
                        <p class="vfc-portal-muted"><?php esc_html_e('Este alumno no tiene matrículas activas.', 'vfc-portal'); ?></p>
                    <?php else: ?>
                        <div class="vfc-portal-grid vfc-portal-grid-2">
                            <?php foreach ($detail['matriculas'] as $m): ?>
                                <div class="vfc-portal-card vfc-portal-qr" data-vfc-qr data-matricula-id="<?php echo esc_attr((string) $m['matricula_id']); ?>">
                                    <header>
                                        <strong><?php echo esc_html((string) $m['alias']); ?></strong>
                                        <span class="vfc-portal-tag vfc-portal-tag-<?php echo esc_attr((string) $m['edicion_estado']); ?>"><?php echo esc_html((string) $m['edicion']); ?></span>
                                    </header>
                                    <small class="vfc-portal-muted"><?php echo esc_html((string) $m['centro']); ?></small>
                                    <button type="button" class="vfc-portal-btn vfc-portal-btn-primary" data-vfc-qr-show>
                                        <?php esc_html_e('Generar / mostrar QR', 'vfc-portal'); ?>
                                    </button>
                                    <div class="vfc-portal-qr-image" hidden>
                                        <img alt="QR" data-vfc-qr-img-src="<?php echo esc_attr((string) $m['qr_image_url']); ?>">
                                    </div>
                                    <div class="vfc-portal-qr-actions">
                                        <button type="button" class="vfc-portal-btn" data-vfc-qr-resend>
                                            <?php esc_html_e('Reenviar al alumno', 'vfc-portal'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h3><?php esc_html_e('Historial', 'vfc-portal'); ?></h3>
                    <?php if ((array) $detail['historial']['items'] === []): ?>
                        <p class="vfc-portal-muted"><?php esc_html_e('Sin movimientos.', 'vfc-portal'); ?></p>
                    <?php else: ?>
                        <div class="vfc-portal-table-wrap">
                            <table class="vfc-portal-table">
                                <thead>
                                <tr>
                                    <th><?php esc_html_e('Fecha', 'vfc-portal'); ?></th>
                                    <th><?php esc_html_e('Tipo', 'vfc-portal'); ?></th>
                                    <th><?php esc_html_e('Estado', 'vfc-portal'); ?></th>
                                    <th><?php esc_html_e('Pedido', 'vfc-portal'); ?></th>
                                    <th class="num"><?php esc_html_e('Importe', 'vfc-portal'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($detail['historial']['items'] as $row): ?>
                                    <tr>
                                        <td><?php echo esc_html(mysql2date(get_option('date_format', 'Y-m-d'), (string) $row['fecha_pedido'])); ?></td>
                                        <td><?php echo esc_html((string) $row['tipo']); ?></td>
                                        <td><span class="vfc-portal-tag vfc-portal-tag-<?php echo esc_attr((string) $row['estado']); ?>"><?php echo esc_html((string) $row['estado']); ?></span></td>
                                        <td>#<?php echo esc_html((string) $row['order_id']); ?></td>
                                        <td class="num"><?php echo esc_html(number_format((float) $row['importe_sin_iva'], 2, ',', '.')); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="vfc-portal-card">
                        <p><?php esc_html_e('Selecciona un alumno en el listado.', 'vfc-portal'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
