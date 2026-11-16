<?php
/**
 * Compare the public analyzer with WooCommerce's real CSV importer.
 *
 * Run only in a disposable WordPress installation with WooCommerce active:
 * wp eval-file tests/woocommerce-importer.php
 *
 * Copyright 2026 Max Wellner / Nakaryu GmbH.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

if (!defined('WP_CLI') || !WP_CLI || !defined('WC_ABSPATH')) {
    fwrite(STDERR, "Run this test under WP-CLI with WooCommerce active.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/Analyzer.php';

// The importer is an admin class and may not already be loaded in a CLI request.
if (!class_exists('WC_Product_CSV_Importer_Controller')) {
    require_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
}
if (!class_exists('WC_Product_CSV_Importer')) {
    require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
}

$expected = array(
    '1' => 'publish',
    '0' => 'private',
    '-1' => 'draft',
    '2' => 'pending',
    'TRUE' => 'publish',
    'false' => 'draft',
);

// A random prefix prevents collisions if a developer reruns this against a test site.
$prefix = 'nakaryu-parity-' . bin2hex(random_bytes(6));
$rows = array('Type,SKU,Name,Published');
$products = array();
$number = 0;
foreach ($expected as $published => $status) {
    $number++;
    $sku = $prefix . '-' . $number;
    $products[$sku] = $status;
    $rows[] = 'simple,' . $sku . ',Synthetic Product ' . $number . ',' . $published;
}
$csv = implode("\n", $rows) . "\n";
$report = (new \Nakaryu\CsvPreflight\Analyzer())->analyze($csv);
if ($report['errors'] !== 0 || $report['warnings'] !== 0 || $report['rows_checked'] !== count($products)) {
    fwrite(STDERR, "Preflight did not accept the six synthetic publication values.\n");
    exit(1);
}

$uploads = wp_upload_dir();
if (!empty($uploads['error']) || empty($uploads['basedir'])) {
    fwrite(STDERR, "Could not locate the disposable site's uploads directory.\n");
    exit(1);
}
// WooCommerce only accepts CSVs inside the site's upload area.
$file = trailingslashit($uploads['basedir']) . $prefix . '.csv';
if (file_put_contents($file, $csv) === false) {
    fwrite(STDERR, "Could not create the disposable CSV fixture.\n");
    exit(1);
}
try {
    // Explicit mappings mirror the importer wizard's recognized English columns.
    $importer = new WC_Product_CSV_Importer($file, array(
        'mapping' => array('Type' => 'type', 'SKU' => 'sku', 'Name' => 'name', 'Published' => 'published'),
        'parse' => true,
        'update_existing' => false,
        'prevent_timeouts' => false,
    ));
    $result = $importer->import();
} finally {
    unlink($file);
}

if (count($result['imported']) !== count($products) || $result['failed'] || $result['skipped']) {
    fwrite(STDERR, "WooCommerce did not import every synthetic row.\n");
    exit(1);
}
foreach ($products as $sku => $status) {
    $id = wc_get_product_id_by_sku($sku);
    $product = $id ? wc_get_product($id) : false;
    if (!$product || $product->get_status() !== $status) {
        fwrite(STDERR, "WooCommerce publication status differs from the documented expectation.\n");
        exit(1);
    }
}

echo "PASS: six Published forms match WooCommerce importer statuses.\n";
