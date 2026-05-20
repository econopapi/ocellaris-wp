<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$themeRoot = dirname(__DIR__);
$moduleFiles = array(
    'includes/admin/text-bar.php',
    'includes/admin/ocellaris-admin-hub.php',
    'includes/blocks/brands-categories.php',
    'includes/blocks/featured-products.php',
    'includes/msi-promotions/admin-page.php',
    'includes/msi-promotions/frontend.php',
    'includes/theme/layout.php',
    'includes/woocommerce/catalog-filters.php',
    'includes/woocommerce/catalog-layout.php',
    'includes/woocommerce/checkout.php',
);

$checks = array(
    array('type' => 'action', 'hook' => 'admin_menu', 'callback' => 'ocellaris_register_admin_hub_menu', 'priority' => 5, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'admin_init', 'callback' => 'ocellaris_register_text_bar_settings', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'init', 'callback' => 'ocellaris_register_featured_products_block', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'init', 'callback' => 'ocellaris_register_product_categories_block', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'init', 'callback' => 'ocellaris_register_filter_blocks', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'wp_enqueue_scripts', 'callback' => 'ocellaris_msi_enqueue_checkout_assets', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'wp_enqueue_scripts', 'callback' => 'ocellaris_custom_header_assets', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'wp_enqueue_scripts', 'callback' => 'ocellaris_enqueue_catalog_styles', 'priority' => 20, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'before_delete_post', 'callback' => 'ocellaris_delete_product_images', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'action', 'hook' => 'wp', 'callback' => 'ocellaris_ensure_woocommerce_hooks', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'filter', 'hook' => 'woocommerce_add_to_cart_fragments', 'callback' => 'ocellaris_cart_count_fragment', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'filter', 'hook' => 'woocommerce_default_address_fields', 'callback' => 'ocellaris_custom_checkout_field_labels', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'filter', 'hook' => 'woocommerce_account_menu_items', 'callback' => 'ocellaris_remove_downloads_from_account_menu', 'priority' => 10, 'accepted_args' => 1),
    array('type' => 'filter', 'hook' => 'loop_shop_columns', 'callback' => 'ocellaris_shop_columns', 'priority' => 999, 'accepted_args' => 1),
    array('type' => 'filter', 'hook' => 'woocommerce_ship_to_different_address_checked', 'callback' => 'ocellaris_disable_ship_to_different_address', 'priority' => 10, 'accepted_args' => 1),
);

$failures = array();

foreach ($moduleFiles as $relativePath) {
    $absolutePath = $themeRoot . '/' . $relativePath;
    if (!file_exists($absolutePath)) {
        $failures[] = 'Missing module file: ' . $relativePath;
        continue;
    }
    require_once $absolutePath;
}

foreach ($checks as $check) {
    $registry = $check['type'] === 'action'
        ? $GLOBALS['ocellaris_registered_actions']
        : $GLOBALS['ocellaris_registered_filters'];

    if (!hook_registered($registry, $check)) {
        $failures[] = sprintf(
            'Missing %s: %s -> %s (priority %d, args %d)',
            $check['type'],
            $check['hook'],
            $check['callback'],
            $check['priority'],
            $check['accepted_args']
        );
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "[FAIL] Ocellaris smoke tests\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[OK] Ocellaris smoke tests passed\n";
exit(0);

function hook_registered(array $registry, array $expected): bool
{
    foreach ($registry as $entry) {
        if (
            $entry['hook'] === $expected['hook']
            && $entry['priority'] === $expected['priority']
            && $entry['accepted_args'] === $expected['accepted_args']
            && callback_matches($entry['callback'], $expected['callback'])
        ) {
            return true;
        }
    }

    return false;
}

function callback_matches($actual, string $expected): bool
{
    if (is_string($actual)) {
        return $actual === $expected;
    }

    if (is_array($actual) && count($actual) === 2) {
        if (is_string($actual[1]) && $actual[1] === $expected) {
            return true;
        }

        $owner = is_object($actual[0]) ? get_class($actual[0]) : (string) $actual[0];
        return ($owner . '::' . (string) $actual[1]) === $expected;
    }

    return false;
}
