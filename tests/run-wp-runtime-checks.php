<?php

if (!defined('ABSPATH')) {
    fwrite(STDERR, "[FAIL] This script must run with WordPress loaded (use wp eval-file).\n");
    exit(1);
}

$failures = array();

$requiredActions = array(
    array('hook' => 'admin_menu', 'callback' => 'ocellaris_register_admin_hub_menu'),
    array('hook' => 'init', 'callback' => 'ocellaris_register_featured_products_block'),
    array('hook' => 'init', 'callback' => 'ocellaris_register_filter_blocks'),
    array('hook' => 'wp_enqueue_scripts', 'callback' => 'ocellaris_enqueue_catalog_styles'),
    array('hook' => 'wp', 'callback' => 'ocellaris_ensure_woocommerce_hooks'),
);

$requiredFilters = array(
    array('hook' => 'woocommerce_add_to_cart_fragments', 'callback' => 'ocellaris_cart_count_fragment'),
    array('hook' => 'woocommerce_default_address_fields', 'callback' => 'ocellaris_custom_checkout_field_labels'),
    array('hook' => 'woocommerce_account_menu_items', 'callback' => 'ocellaris_remove_downloads_from_account_menu'),
    array('hook' => 'loop_shop_columns', 'callback' => 'ocellaris_shop_columns'),
);

$requiredBlocks = array(
    'ocellaris/featured-products',
    'ocellaris/product-categories',
    'ocellaris/featured-brands',
    'ocellaris/all-brands',
    'ocellaris/filter-categories',
    'ocellaris/filter-brand',
);

foreach ($requiredActions as $entry) {
    $hook = $entry['hook'];
    $callback = $entry['callback'];

    if (!function_exists($callback)) {
        $failures[] = sprintf('Missing callback function: %s', $callback);
        continue;
    }

    if (has_action($hook, $callback) === false) {
        $failures[] = sprintf('Missing action registration: %s -> %s', $hook, $callback);
    }
}

foreach ($requiredFilters as $entry) {
    $hook = $entry['hook'];
    $callback = $entry['callback'];

    if (!function_exists($callback)) {
        $failures[] = sprintf('Missing callback function: %s', $callback);
        continue;
    }

    if (has_filter($hook, $callback) === false) {
        $failures[] = sprintf('Missing filter registration: %s -> %s', $hook, $callback);
    }
}

$registry = WP_Block_Type_Registry::get_instance();
foreach ($requiredBlocks as $blockName) {
    if (!$registry->is_registered($blockName)) {
        $failures[] = sprintf('Block is not registered: %s', $blockName);
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "[FAIL] WP runtime checks\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[OK] WP runtime checks passed\n";
exit(0);
