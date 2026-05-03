<?php
/**
 * @var string $page_title
 * @var string $page_slug
 * @var string $rest_root
 * @var string $rest_nonce
 * @var \WP_User|null $portal_user
 */
if (!defined('ABSPATH')) {
    exit;
}

$lang = get_bloginfo('language');
$site_name = get_bloginfo('name');
?><!doctype html>
<html lang="<?php echo esc_attr($lang); ?>" class="vfc-portal">
<head>
<meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="vfc-rest-root" content="<?php echo esc_attr($rest_root); ?>">
<meta name="vfc-rest-nonce" content="<?php echo esc_attr($rest_nonce); ?>">
<meta name="robots" content="noindex,nofollow">
<title><?php echo esc_html($page_title . ' · ' . $site_name); ?></title>
<?php wp_head(); ?>
</head>
<body class="vfc-portal-body vfc-portal-page-<?php echo esc_attr($page_slug); ?>">
<?php require VFC_PORTAL_DIR . 'templates/parts/header.php'; ?>

<main class="vfc-portal-main" role="main">
<?php require VFC_PORTAL_DIR . 'templates/pages/' . $page_slug . '.php'; ?>
</main>

<?php require VFC_PORTAL_DIR . 'templates/parts/footer.php'; ?>
<?php wp_footer(); ?>
</body>
</html>
