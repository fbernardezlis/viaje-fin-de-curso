<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * @var array{id:int,display_name:string,email:string} $alumno
 * @var array<int, array<string, mixed>> $matriculas
 * @var array{bloqueado:float,confirmado:float,liquidado:float,neto:float} $saldo
 * @var array{items: array<int, array<string,mixed>>, total: int, page: int, per_page: int} $historial
 */
$pages = max(1, (int) ceil(($historial['total'] ?: 0) / max(1, $historial['per_page'])));
$page = max(1, $historial['page']);
?>
<section class="vfc-portal-shell vfc-portal-dashboard">
    <header class="vfc-portal-page-header">
        <h1><?php echo esc_html(sprintf(__('Hola, %s', 'vfc-portal'), $alumno['display_name'])); ?></h1>
        <p class="vfc-portal-muted"><?php esc_html_e('Tu saldo, tu historial de movimientos y tu QR personal.', 'vfc-portal'); ?></p>
    </header>

    <div class="vfc-portal-grid vfc-portal-grid-3">
        <div class="vfc-portal-card vfc-portal-stat" data-vfc-saldo data-alumno-id="<?php echo esc_attr((string) $alumno['id']); ?>">
            <span class="vfc-portal-label"><?php esc_html_e('Saldo confirmado', 'vfc-portal'); ?></span>
            <strong class="vfc-portal-value" data-key="confirmado"><?php echo esc_html(number_format((float) $saldo['confirmado'], 2, ',', '.')); ?> €</strong>
            <small class="vfc-portal-muted"><?php esc_html_e('Disponible para liquidar al colegio.', 'vfc-portal'); ?></small>
        </div>
        <div class="vfc-portal-card vfc-portal-stat">
            <span class="vfc-portal-label"><?php esc_html_e('En periodo de bloqueo', 'vfc-portal'); ?></span>
            <strong class="vfc-portal-value" data-key="bloqueado"><?php echo esc_html(number_format((float) $saldo['bloqueado'], 2, ',', '.')); ?> €</strong>
            <small class="vfc-portal-muted"><?php esc_html_e('Pendientes de liberación.', 'vfc-portal'); ?></small>
        </div>
        <div class="vfc-portal-card vfc-portal-stat">
            <span class="vfc-portal-label"><?php esc_html_e('Ya liquidado', 'vfc-portal'); ?></span>
            <strong class="vfc-portal-value" data-key="liquidado"><?php echo esc_html(number_format((float) $saldo['liquidado'], 2, ',', '.')); ?> €</strong>
            <small class="vfc-portal-muted"><?php esc_html_e('Importes ya transferidos al centro.', 'vfc-portal'); ?></small>
        </div>
    </div>

    <h2><?php esc_html_e('Mis QR', 'vfc-portal'); ?></h2>
    <?php if ($matriculas === []): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('Aún no tienes ninguna matrícula activa.', 'vfc-portal'); ?></p>
    <?php else: ?>
        <div class="vfc-portal-grid vfc-portal-grid-2">
            <?php foreach ($matriculas as $m): ?>
                <div class="vfc-portal-card vfc-portal-qr" data-vfc-qr data-matricula-id="<?php echo esc_attr((string) $m['matricula_id']); ?>">
                    <header>
                        <strong><?php echo esc_html($m['alias']); ?></strong>
                        <span class="vfc-portal-tag vfc-portal-tag-<?php echo esc_attr((string) $m['edicion_estado']); ?>">
                            <?php echo esc_html($m['edicion']); ?>
                        </span>
                    </header>
                    <small class="vfc-portal-muted"><?php echo esc_html($m['centro']); ?></small>
                    <button type="button" class="vfc-portal-btn vfc-portal-btn-primary" data-vfc-qr-show>
                        <?php esc_html_e('Generar / mostrar QR', 'vfc-portal'); ?>
                    </button>
                    <div class="vfc-portal-qr-image" hidden>
                        <img alt="QR" data-vfc-qr-img-src="<?php echo esc_attr($m['qr_image_url']); ?>">
                    </div>
                    <div class="vfc-portal-qr-actions">
                        <button type="button" class="vfc-portal-btn" data-vfc-qr-resend>
                            <?php esc_html_e('Reenviar a mi email', 'vfc-portal'); ?>
                        </button>
                    </div>
                    <p class="vfc-portal-muted vfc-portal-qr-hint">
                        <?php esc_html_e('Generar el QR rota el token anterior. Solo el último enlace activo es válido.', 'vfc-portal'); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2><?php esc_html_e('Historial de movimientos', 'vfc-portal'); ?></h2>
    <?php if ($historial['items'] === []): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('Todavía no hay movimientos en tu cuenta.', 'vfc-portal'); ?></p>
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
                <?php foreach ($historial['items'] as $row): ?>
                    <tr>
                        <td><?php echo esc_html(mysql2date(get_option('date_format', 'Y-m-d'), (string) $row['fecha_pedido'])); ?></td>
                        <td><?php echo esc_html($row['tipo']); ?></td>
                        <td><span class="vfc-portal-tag vfc-portal-tag-<?php echo esc_attr((string) $row['estado']); ?>"><?php echo esc_html($row['estado']); ?></span></td>
                        <td>#<?php echo esc_html((string) $row['order_id']); ?></td>
                        <td class="num"><?php echo esc_html(number_format((float) $row['importe_sin_iva'], 2, ',', '.')); ?> €</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="vfc-portal-pager" aria-label="<?php esc_attr_e('Paginación', 'vfc-portal'); ?>">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="vfc-portal-pager-current"><?php echo (int) $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo esc_url(add_query_arg('page', $i)); ?>"><?php echo (int) $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
