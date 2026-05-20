<?php

declare(strict_types=1);

$themeRoot = dirname(__DIR__);
$requiredPaths = array(
    'functions.php',
    'README.md',
    'style.css',
    'template-parts/header-custom.php',
    'template-parts/footer-custom.php',
    'woocommerce/content-product.php',
    'woocommerce/myaccount/my-account.php',
    'includes/admin/text-bar.php',
    'includes/admin/ocellaris-admin-hub.php',
    'includes/blocks/brands-categories.php',
    'includes/blocks/featured-products.php',
    'includes/msi-promotions/admin-page.php',
    'includes/msi-promotions/frontend.php',
    'includes/theme/layout.php',
    'includes/woocommerce/checkout.php',
    'includes/woocommerce/catalog-layout.php',
    'includes/woocommerce/catalog-filters.php',
    'blocks/featured-products/block.js',
    'blocks/featured-brands/block.js',
    'blocks/product-categories/block.js',
    'assets/js/custom-header.js',
    'assets/css/custom-header.css',
    'assets/css/custom-footer.css',
);

$failures = array();

foreach ($requiredPaths as $relativePath) {
    $absolutePath = $themeRoot . '/' . $relativePath;
    if (!file_exists($absolutePath)) {
        $failures[] = 'Missing required file: ' . $relativePath;
        continue;
    }

    if (!is_readable($absolutePath)) {
        $failures[] = 'File is not readable: ' . $relativePath;
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "[FAIL] Ocellaris structure smoke tests\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

echo "[OK] Ocellaris structure smoke tests passed\n";
exit(0);
