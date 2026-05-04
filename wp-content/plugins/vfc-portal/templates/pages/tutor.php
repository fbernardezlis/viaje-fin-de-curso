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
                                    <p class="vfc-portal-label vfc-portal-qr-url-heading"><?php esc_html_e('Enlace directo (permanente)', 'vfc-portal'); ?></p>
                                    <div class="vfc-portal-qr-urlbox">
                                        <input type="text" class="vfc-portal-qr-url-input" readonly value="<?php echo esc_attr((string) ($m['qr_link_url'] ?? '')); ?>" aria-label="<?php esc_attr_e('URL del enlace QR', 'vfc-portal'); ?>">
                                        <button type="button" class="vfc-portal-btn" data-vfc-qr-copy-url><?php esc_html_e('Copiar enlace', 'vfc-portal'); ?></button>
                                    </div>
                                    <button type="button" class="vfc-portal-btn vfc-portal-btn-primary vfc-portal-qr-show-btn" data-vfc-qr-show>
                                        <?php esc_html_e('Mostrar código QR', 'vfc-portal'); ?>
                                    </button>
                                    <p class="vfc-portal-qr-fallback">
                                        <a class="vfc-portal-btn" href="<?php echo esc_url((string) $m['qr_image_url']); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php esc_html_e('Abrir imagen QR (nueva pestaña)', 'vfc-portal'); ?>
                                        </a>
                                    </p>
                                    <div class="vfc-portal-qr-image" hidden>
                                        <img alt="QR" data-vfc-qr-img-src="<?php echo esc_attr((string) $m['qr_image_url']); ?>">
                                    </div>
                                    <div class="vfc-portal-qr-actions">
                                        <button type="button" class="vfc-portal-btn" data-vfc-qr-resend>
                                            <?php esc_html_e('Reenviar al alumno', 'vfc-portal'); ?>
                                        </button>
                                    </div>
                                    <p class="vfc-portal-muted vfc-portal-qr-hint">
                                        <?php esc_html_e('El enlace no cambia; el reenvío solo repite el correo al alumno.', 'vfc-portal'); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h3><?php esc_html_e('Historial', 'vfc-portal'); ?></h3>
                    <?php
                    $hHist = $detail['historial'];
                    $hPages = max(1, (int) ceil(($hHist['total'] ?: 0) / max(1, $hHist['per_page'])));
                    $hPage = max(1, $hHist['page']);
                    $hEf = $detail['ediciones_filtro'] ?? [];
                    $hHf = $detail['historial_hf'] ?? [];
                    $hHfq = $detail['historial_hf_query'] ?? [];
                    $hHasF = $hHf !== [];
                    $hFrom = isset($hHf['from']) ? substr((string) $hHf['from'], 0, 10) : '';
                    $hTo = isset($hHf['to']) ? substr((string) $hHf['to'], 0, 10) : '';
                    $tutorHistAction = home_url('/portal/tutor/' . (int) $detail['alumno']['id'] . '/');
                    ?>
                    <form class="vfc-portal-filters" method="get" action="<?php echo esc_url($tutorHistAction); ?>">
                        <div class="vfc-portal-filters-grid">
                            <label class="vfc-portal-field">
                                <span class="vfc-portal-label"><?php esc_html_e('Edición', 'vfc-portal'); ?></span>
                                <select name="hf_edicion">
                                    <option value=""><?php esc_html_e('Todas', 'vfc-portal'); ?></option>
                                    <?php foreach ($hEf as $eid => $enombre): ?>
                                        <option value="<?php echo (int) $eid; ?>"<?php selected((int) ($hHf['edicion_id'] ?? 0), (int) $eid); ?>><?php echo esc_html((string) $enombre); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="vfc-portal-field">
                                <span class="vfc-portal-label"><?php esc_html_e('Estado', 'vfc-portal'); ?></span>
                                <select name="hf_estado">
                                    <option value=""><?php esc_html_e('Todos', 'vfc-portal'); ?></option>
                                    <option value="BLOQUEADO"<?php selected((string) ($hHf['estado'] ?? ''), 'BLOQUEADO'); ?>><?php echo esc_html('BLOQUEADO'); ?></option>
                                    <option value="CONFIRMADO"<?php selected((string) ($hHf['estado'] ?? ''), 'CONFIRMADO'); ?>><?php echo esc_html('CONFIRMADO'); ?></option>
                                    <option value="REVERTIDO"<?php selected((string) ($hHf['estado'] ?? ''), 'REVERTIDO'); ?>><?php echo esc_html('REVERTIDO'); ?></option>
                                </select>
                            </label>
                            <label class="vfc-portal-field">
                                <span class="vfc-portal-label"><?php esc_html_e('Tipo', 'vfc-portal'); ?></span>
                                <select name="hf_tipo">
                                    <option value=""><?php esc_html_e('Todos', 'vfc-portal'); ?></option>
                                    <option value="abono"<?php selected((string) ($hHf['tipo'] ?? ''), 'abono'); ?>><?php echo esc_html('abono'); ?></option>
                                    <option value="reverso"<?php selected((string) ($hHf['tipo'] ?? ''), 'reverso'); ?>><?php echo esc_html('reverso'); ?></option>
                                </select>
                            </label>
                            <label class="vfc-portal-field">
                                <span class="vfc-portal-label"><?php esc_html_e('Desde', 'vfc-portal'); ?></span>
                                <input type="date" name="hf_from" value="<?php echo esc_attr($hFrom); ?>">
                            </label>
                            <label class="vfc-portal-field">
                                <span class="vfc-portal-label"><?php esc_html_e('Hasta', 'vfc-portal'); ?></span>
                                <input type="date" name="hf_to" value="<?php echo esc_attr($hTo); ?>">
                            </label>
                        </div>
                        <div class="vfc-portal-filters-actions">
                            <button type="submit" class="vfc-portal-btn vfc-portal-btn-primary"><?php esc_html_e('Aplicar filtros', 'vfc-portal'); ?></button>
                            <a class="vfc-portal-btn" href="<?php echo esc_url($tutorHistAction); ?>"><?php esc_html_e('Limpiar', 'vfc-portal'); ?></a>
                        </div>
                    </form>

                    <?php if ($hHist['total'] === 0 && !$hHasF): ?>
                        <p class="vfc-portal-muted"><?php esc_html_e('Sin movimientos.', 'vfc-portal'); ?></p>
                    <?php elseif ($hHist['total'] === 0): ?>
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
                                <?php foreach ($hHist['items'] as $row): ?>
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
                        <?php if ($hPages > 1): ?>
                            <nav class="vfc-portal-pager" aria-label="<?php esc_attr_e('Paginación', 'vfc-portal'); ?>">
                                <?php for ($i = 1; $i <= $hPages; $i++): ?>
                                    <?php if ($i === $hPage): ?>
                                        <span class="vfc-portal-pager-current"><?php echo (int) $i; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url(add_query_arg(array_merge($hHfq, ['page' => $i]), $tutorHistAction)); ?>"><?php echo (int) $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
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
