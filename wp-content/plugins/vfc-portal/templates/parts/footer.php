<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="vfc-portal-footer">
    <div class="vfc-portal-shell">
        <small>
            &copy; <?php echo esc_html(gmdate('Y')); ?>
            <?php echo esc_html(get_bloginfo('name')); ?>
            · <?php esc_html_e('Portal privado VFC', 'vfc-portal'); ?>
        </small>
    </div>
</footer>
