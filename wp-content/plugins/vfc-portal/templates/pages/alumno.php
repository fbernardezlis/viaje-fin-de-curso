<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * @var array{id:int,display_name:string,email:string} $alumno
 * @var array<int, array<string, mixed>> $matriculas
 * @var array<int, string> $ediciones_filtro id edición => nombre
 * @var array{bloqueado:float,confirmado:float,liquidado:float,neto:float} $saldo
 * @var array{items: array<int, array<string,mixed>>, total: int, page: int, per_page: int} $historial
 * @var array<string, mixed> $historial_hf filtros activos (edicion_id, estado, tipo, from, to)
 * @var array<string, int|string> $historial_hf_query params GET para paginación
 */
$ediciones_filtro = $ediciones_filtro ?? [];
$historial_hf = $historial_hf ?? [];
$historial_hf_query = $historial_hf_query ?? [];
$hasHistorialFilters = $historial_hf !== [];
$pages = max(1, (int) ceil(($historial['total'] ?: 0) / max(1, $historial['per_page'])));
$page = max(1, $historial['page']);
$hfFrom = isset($historial_hf['from']) ? substr((string) $historial_hf['from'], 0, 10) : '';
$hfTo = isset($historial_hf['to']) ? substr((string) $historial_hf['to'], 0, 10) : '';
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
        <div class="vfc-portal-card vfc-portal-muted">
            <p><?php esc_html_e('Aún no tienes ninguna matrícula en el sistema.', 'vfc-portal'); ?></p>
            <p><?php esc_html_e('Sin matrícula no se puede generar tu QR personal: el colegio debe darte de alta en una edición desde el panel de administración de Viaje fin de curso (matrícula vinculada a tu usuario).', 'vfc-portal'); ?></p>
        </div>
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
                    <p class="vfc-portal-label vfc-portal-qr-url-heading"><?php esc_html_e('Enlace directo (permanente)', 'vfc-portal'); ?></p>
                    <div class="vfc-portal-qr-urlbox">
                        <input type="text" class="vfc-portal-qr-url-input" readonly value="<?php echo esc_attr((string) ($m['qr_link_url'] ?? '')); ?>" aria-label="<?php esc_attr_e('URL del enlace QR', 'vfc-portal'); ?>">
                        <button type="button" class="vfc-portal-btn" data-vfc-qr-copy-url><?php esc_html_e('Copiar enlace', 'vfc-portal'); ?></button>
                    </div>
                    <button type="button" class="vfc-portal-btn vfc-portal-btn-primary vfc-portal-qr-show-btn" data-vfc-qr-show>
                        <?php esc_html_e('Mostrar código QR', 'vfc-portal'); ?>
                    </button>
                    <p class="vfc-portal-qr-fallback">
                        <a class="vfc-portal-btn" href="<?php echo esc_url($m['qr_image_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Abrir imagen QR (nueva pestaña)', 'vfc-portal'); ?>
                        </a>
                    </p>
                    <div class="vfc-portal-qr-image" hidden>
                        <img alt="QR" data-vfc-qr-img-src="<?php echo esc_attr($m['qr_image_url']); ?>">
                    </div>
                    <div class="vfc-portal-qr-actions">
                        <button type="button" class="vfc-portal-btn" data-vfc-qr-resend>
                            <?php esc_html_e('Reenviar a mi email', 'vfc-portal'); ?>
                        </button>
                    </div>
                    <p class="vfc-portal-muted vfc-portal-qr-hint">
                        <?php esc_html_e('Este enlace y el QR no cambian mientras exista la matrícula. Quien lo abra podrá comprar vinculado a ti hasta que pulse “Salir” en la tienda.', 'vfc-portal'); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2><?php esc_html_e('Historial de movimientos', 'vfc-portal'); ?></h2>
    <form class="vfc-portal-filters" method="get" action="<?php echo esc_url(home_url('/portal/alumno/')); ?>">
        <div class="vfc-portal-filters-grid">
            <label class="vfc-portal-field">
                <span class="vfc-portal-label"><?php esc_html_e('Edición', 'vfc-portal'); ?></span>
                <select name="hf_edicion">
                    <option value=""><?php esc_html_e('Todas', 'vfc-portal'); ?></option>
                    <?php foreach ($ediciones_filtro as $eid => $enombre): ?>
                        <option value="<?php echo (int) $eid; ?>"<?php selected((int) ($historial_hf['edicion_id'] ?? 0), (int) $eid); ?>><?php echo esc_html((string) $enombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="vfc-portal-field">
                <span class="vfc-portal-label"><?php esc_html_e('Estado', 'vfc-portal'); ?></span>
                <select name="hf_estado">
                    <option value=""><?php esc_html_e('Todos', 'vfc-portal'); ?></option>
                    <option value="BLOQUEADO"<?php selected((string) ($historial_hf['estado'] ?? ''), 'BLOQUEADO'); ?>><?php echo esc_html('BLOQUEADO'); ?></option>
                    <option value="CONFIRMADO"<?php selected((string) ($historial_hf['estado'] ?? ''), 'CONFIRMADO'); ?>><?php echo esc_html('CONFIRMADO'); ?></option>
                    <option value="REVERTIDO"<?php selected((string) ($historial_hf['estado'] ?? ''), 'REVERTIDO'); ?>><?php echo esc_html('REVERTIDO'); ?></option>
                </select>
            </label>
            <label class="vfc-portal-field">
                <span class="vfc-portal-label"><?php esc_html_e('Tipo', 'vfc-portal'); ?></span>
                <select name="hf_tipo">
                    <option value=""><?php esc_html_e('Todos', 'vfc-portal'); ?></option>
                    <option value="abono"<?php selected((string) ($historial_hf['tipo'] ?? ''), 'abono'); ?>><?php echo esc_html('abono'); ?></option>
                    <option value="reverso"<?php selected((string) ($historial_hf['tipo'] ?? ''), 'reverso'); ?>><?php echo esc_html('reverso'); ?></option>
                </select>
            </label>
            <label class="vfc-portal-field">
                <span class="vfc-portal-label"><?php esc_html_e('Desde', 'vfc-portal'); ?></span>
                <input type="date" name="hf_from" value="<?php echo esc_attr($hfFrom); ?>">
            </label>
            <label class="vfc-portal-field">
                <span class="vfc-portal-label"><?php esc_html_e('Hasta', 'vfc-portal'); ?></span>
                <input type="date" name="hf_to" value="<?php echo esc_attr($hfTo); ?>">
            </label>
        </div>
        <div class="vfc-portal-filters-actions">
            <button type="submit" class="vfc-portal-btn vfc-portal-btn-primary"><?php esc_html_e('Aplicar filtros', 'vfc-portal'); ?></button>
            <a class="vfc-portal-btn" href="<?php echo esc_url(home_url('/portal/alumno/')); ?>"><?php esc_html_e('Limpiar', 'vfc-portal'); ?></a>
        </div>
    </form>

    <?php if ($historial['total'] === 0 && !$hasHistorialFilters): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('Todavía no hay movimientos en tu cuenta.', 'vfc-portal'); ?></p>
    <?php elseif ($historial['total'] === 0): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('Ningún movimiento coincide con los filtros seleccionados.', 'vfc-portal'); ?></p>
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
                        <a href="<?php echo esc_url(add_query_arg(array_merge($historial_hf_query, ['page' => $i]), home_url('/portal/alumno/'))); ?>"><?php echo (int) $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
