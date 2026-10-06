<?php
/**
 * Plugin Name: Nakaryu Product CSV Preflight
 * Plugin URI: https://github.com/harukyu/wc-product-csv-preflight
 * Description: Check WooCommerce product CSV files before import. No products are created or changed.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Nakaryu GmbH
 * Author URI: https://nakaryu.de
 * License: GPL-2.0-or-later
 * Text Domain: wc-product-csv-preflight
 */
namespace Nakaryu\CsvPreflight;
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/includes/Analyzer.php';

add_action('admin_menu', function () {
    // Tools menu also works without WooCommerce; analysis never calls its runtime.
    add_management_page('Product CSV Preflight', 'Product CSV Preflight', 'manage_woocommerce', 'nakaryu-csv-preflight', __NAMESPACE__ . '\\render_page');
});

function render_page() {
    if (!current_user_can('manage_woocommerce')) { wp_die(esc_html__('Insufficient permissions.', 'wc-product-csv-preflight')); }
    echo '<div class="wrap"><h1>Product CSV Preflight</h1><p>' . esc_html__('Check an English-header WooCommerce product CSV before import. Analysis runs on this server. No products are imported or modified.', 'wc-product-csv-preflight') . '</p>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('nakaryu_csv_preflight');
    echo '<p><label for="preflight-file">CSV (UTF-8, maximum 5 MiB)</label><br><input id="preflight-file" type="file" name="product_csv" accept=".csv,text/csv" required></p>';
    echo '<p><label for="preflight-delimiter">Delimiter</label> <select id="preflight-delimiter" name="delimiter"><option value="comma">Comma</option><option value="semicolon">Semicolon</option><option value="tab">Tab</option></select></p>';
    submit_button(__('Check CSV', 'wc-product-csv-preflight'));
    echo '</form>';
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('nakaryu_csv_preflight');
        try {
            $file = isset($_FILES['product_csv']) ? $_FILES['product_csv'] : null;
            if (!is_array($file) || !isset($file['error'], $file['tmp_name']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) { throw new \RuntimeException('CSV upload was not accepted. Check the server upload limit.'); }
            // Read PHP's temporary upload only: no media library copy, move, or database entry.
            $text = file_get_contents($file['tmp_name'], false, null, 0, Analyzer::MAX_BYTES + 1);
            if ($text === false) { throw new \RuntimeException('Could not read the uploaded CSV.'); }
            $choice = isset($_POST['delimiter']) && is_string($_POST['delimiter']) ? $_POST['delimiter'] : 'comma';
            $delimiters = array('comma' => ',', 'semicolon' => ';', 'tab' => "\t");
            if (!isset($delimiters[$choice])) { throw new \RuntimeException('Unsupported delimiter.'); }
            $report = (new Analyzer())->analyze($text, $delimiters[$choice]);
            echo '<h2>Report</h2><p>' . esc_html(sprintf('%d records checked / %d errors / %d warnings.', $report['rows_checked'], $report['errors'], $report['warnings'])) . '</p>';
            echo '<table class="widefat striped"><thead><tr><th>Record</th><th>Severity</th><th>Finding</th></tr></thead><tbody>';
            foreach ($report['findings'] as $finding) {
                echo '<tr><td>' . esc_html((string) $finding['record']) . '</td><td>' . esc_html($finding['severity']) . '</td><td>' . esc_html($finding['message']) . '</td></tr>';
            }
            echo '</tbody></table><h3>JSON</h3><pre style="white-space:pre-wrap;overflow-wrap:anywhere">' . esc_html(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
        } catch (\RuntimeException $e) { echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>'; }
    }
    echo '<p>' . esc_html__('A clean report is not an import guarantee. Existing store products, custom mappings, attributes, images and extension columns are not validated. Multiline fields mean record numbers may differ from editor line numbers.', 'wc-product-csv-preflight') . '</p></div>';
}

if (defined('WP_CLI') && WP_CLI) {
    /**
     * Check a local WooCommerce product CSV without importing.
     *
     * ## OPTIONS
     *
     * <file>
     * : Local CSV path.
     *
     * [--delimiter=<delimiter>]
     * : comma, semicolon, or tab. Default: comma.
     *
     * ## EXAMPLES
     *
     *     wp nakaryu csv preflight products.csv
     */
    \WP_CLI::add_command('nakaryu csv preflight', function ($args, $assoc_args) {
        try {
            if (count($args) !== 1 || !is_file($args[0]) || !is_readable($args[0])) { throw new \RuntimeException('Provide one readable local CSV file.'); }
            $delimiters = array('comma' => ',', 'semicolon' => ';', 'tab' => "\t");
            $choice = isset($assoc_args['delimiter']) ? $assoc_args['delimiter'] : 'comma';
            if (!is_string($choice) || !isset($delimiters[$choice])) { throw new \RuntimeException('Unsupported delimiter.'); }
            $text = file_get_contents($args[0], false, null, 0, Analyzer::MAX_BYTES + 1);
            if ($text === false) { throw new \RuntimeException('Could not read CSV.'); }
            $report = (new Analyzer())->analyze($text, $delimiters[$choice]);
            \WP_CLI::line(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            \WP_CLI::halt($report['errors'] ? 2 : ($report['warnings'] ? 1 : 0));
        } catch (\RuntimeException $e) { \WP_CLI::error($e->getMessage()); }
    });
}
