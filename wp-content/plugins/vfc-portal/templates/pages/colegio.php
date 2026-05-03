<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * @var array<int, array{id:int,nombre:string}> $centros
 * @var int $selected_id
 * @var array<string, mixed>|null $detail
 */
?>
<section class="vfc-portal-shell vfc-portal-dashboard">
    <header class="vfc-portal-page-header">
        <h1><?php esc_html_e('Panel del centro', 'vfc-portal'); ?></h1>
        <p class="vfc-portal-muted"><?php esc_html_e('Resumen del centro: alumnos, ediciones, productos y liquidaciones recibidas.', 'vfc-portal'); ?></p>
    </header>

    <?php if ($centros === []): ?>
        <div class="vfc-portal-card">
            <p><?php esc_html_e('Tu cuenta no tiene ningún centro asignado.', 'vfc-portal'); ?></p>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <?php if (count($centros) > 1): ?>
        <nav class="vfc-portal-tabs" aria-label="<?php esc_attr_e('Centros', 'vfc-portal'); ?>">
            <?php foreach ($centros as $c): ?>
                <a class="vfc-portal-tab <?php echo (int) $c['id'] === $selected_id ? 'is-active' : ''; ?>"
                   href="<?php echo esc_url(home_url('/portal/colegio/' . (int) $c['id'])); ?>">
                    <?php echo esc_html($c['nombre']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($detail === null): ?>
        <div class="vfc-portal-card">
            <p><?php esc_html_e('Centro no encontrado.', 'vfc-portal'); ?></p>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <?php $resumen = $detail['resumen']; ?>
    <div class="vfc-portal-grid vfc-portal-grid-4">
        <div class="vfc-portal-card vfc-portal-stat">
            <span class="vfc-portal-label"><?php esc_html_e('Ediciones', 'vfc-portal'); ?></span>
            <strong class="vfc-portal-value"><?php echo (int) $resumen['ediciones']; ?></strong>
        </div>
        <div class="vfc-portal-card vfc-portal-stat">
            <span class="vfc-portal-label"><?php esc_html_e('Alumnos', 'vfc-portal'); ?></span>
            <strong class="vfc-portal-value"><?php echo (int) $resumen['alumnos']; ?></strong>
        </div>
        <div class="vfc-portal-card vfc-portal-stat">
            <span class="vfc-portal-label"><?php esc_html_e('Matrículas', 'vfc-portal'); ?></span>
            <strong class="vfc-portal-value"><?php echo (int) $resumen['matriculas']; ?></strong>
        </div>
        <div class="vfc-portal-card vfc-portal-stat">
            <span class="vfc-portal-label"><?php esc_html_e('Liquidado total', 'vfc-portal'); ?></span>
            <strong class="vfc-portal-value"><?php echo esc_html(number_format((float) $resumen['total_liquidado'], 2, ',', '.')); ?> €</strong>
        </div>
    </div>

    <h2><?php esc_html_e('Ediciones', 'vfc-portal'); ?></h2>
    <?php if ((array) $detail['ediciones'] === []): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('Sin ediciones todavía.', 'vfc-portal'); ?></p>
    <?php else: ?>
        <div class="vfc-portal-table-wrap">
            <table class="vfc-portal-table">
                <thead>
                <tr>
                    <th><?php esc_html_e('Nombre', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Estado', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Inicio', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Fin', 'vfc-portal'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($detail['ediciones'] as $e): ?>
                    <tr>
                        <td><?php echo esc_html((string) $e['nombre']); ?></td>
                        <td><span class="vfc-portal-tag vfc-portal-tag-<?php echo esc_attr((string) $e['estado']); ?>"><?php echo esc_html((string) $e['estado']); ?></span></td>
                        <td><?php echo esc_html((string) ($e['fecha_inicio'] ?? '—')); ?></td>
                        <td><?php echo esc_html((string) ($e['fecha_fin'] ?? '—')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2><?php esc_html_e('Alumnos', 'vfc-portal'); ?></h2>
    <?php if ((array) $detail['alumnos'] === []): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('Sin alumnos.', 'vfc-portal'); ?></p>
    <?php else: ?>
        <div class="vfc-portal-table-wrap">
            <table class="vfc-portal-table">
                <thead>
                <tr>
                    <th><?php esc_html_e('Alumno', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Email', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Matrículas', 'vfc-portal'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($detail['alumnos'] as $a): ?>
                    <tr>
                        <td><?php echo esc_html((string) $a['display_name']); ?></td>
                        <td><?php echo esc_html((string) ($a['email'] ?? '—')); ?></td>
                        <td>
                            <?php foreach ((array) $a['matriculas'] as $m): ?>
                                <span class="vfc-portal-tag vfc-portal-tag-<?php echo esc_attr((string) $m['edicion_estado']); ?>">
                                    <?php echo esc_html((string) $m['alias']); ?> · <?php echo esc_html((string) $m['edicion']); ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2><?php esc_html_e('Productos del centro', 'vfc-portal'); ?></h2>
    <?php if ((array) $detail['productos_overrides'] === []): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('No hay configuraciones específicas. Todos los productos del catálogo están disponibles.', 'vfc-portal'); ?></p>
    <?php else: ?>
        <div class="vfc-portal-table-wrap">
            <table class="vfc-portal-table">
                <thead>
                <tr>
                    <th><?php esc_html_e('Producto', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Estado', 'vfc-portal'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($detail['productos_overrides'] as $p): ?>
                    <tr>
                        <td><?php echo esc_html((string) $p['name']); ?></td>
                        <td>
                            <span class="vfc-portal-tag vfc-portal-tag-<?php echo $p['activo'] ? 'activa' : 'inactiva'; ?>">
                                <?php echo $p['activo'] ? esc_html__('Activo', 'vfc-portal') : esc_html__('Excluido', 'vfc-portal'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2><?php esc_html_e('Liquidaciones recibidas', 'vfc-portal'); ?></h2>
    <?php if ((array) $detail['liquidaciones'] === []): ?>
        <p class="vfc-portal-muted"><?php esc_html_e('Aún no hay liquidaciones registradas.', 'vfc-portal'); ?></p>
    <?php else: ?>
        <div class="vfc-portal-table-wrap">
            <table class="vfc-portal-table">
                <thead>
                <tr>
                    <th><?php esc_html_e('Fecha', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Referencia', 'vfc-portal'); ?></th>
                    <th><?php esc_html_e('Estado', 'vfc-portal'); ?></th>
                    <th class="num"><?php esc_html_e('Importe', 'vfc-portal'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($detail['liquidaciones'] as $l): ?>
                    <tr>
                        <td><?php echo esc_html((string) $l['fecha']); ?></td>
                        <td><?php echo esc_html((string) ($l['referencia'] ?? '—')); ?></td>
                        <td><?php echo esc_html((string) $l['estado']); ?></td>
                        <td class="num"><?php echo esc_html(number_format((float) $l['importe'], 2, ',', '.')); ?> €</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
