<?php
/**
 * Synthetic, dependency-free checks for the public CSV analyzer.
 *
 * Copyright 2026 Max Wellner / Nakaryu GmbH.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
require_once dirname(__DIR__) . '/includes/Analyzer.php';

use Nakaryu\CsvPreflight\Analyzer;

$checks = 0;

function check($condition, $description) {
    global $checks;
    $checks++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $description . "\n");
        exit(1);
    }
}

function report_for($csv, $delimiter = ',') {
    return (new Analyzer())->analyze($csv, $delimiter);
}

function has_finding($report, $code, $record = null) {
    foreach ($report['findings'] as $finding) {
        if ($finding['code'] === $code && ($record === null || $finding['record'] === $record)) {
            return true;
        }
    }
    return false;
}

function rejects($csv, $description, $delimiter = ',') {
    try {
        report_for($csv, $delimiter);
    } catch (RuntimeException $exception) {
        check(strpos($exception->getMessage(), 'PRIVATE-SKU') === false, $description . ' does not echo product data');
        return;
    }
    check(false, $description . ' is rejected');
}

$clean = report_for("\xEF\xBB\xBFSKU,Type,Name,Regular price,Sale price\r\nparent,variable,Shirt,,\r\nchild,variation,Small,24.99,19.99\r\n");
check($clean['errors'] === 1 && has_finding($clean, 'missing_parent', 3), 'a variation without Parent is an error');

$family = report_for("SKU,Type,Name,Parent,Regular price,Sale price\nparent,variable,Shirt,,,\nchild,variation,Small,parent,24.99,19.99\n");
check($family['errors'] === 0 && $family['warnings'] === 0 && $family['rows_checked'] === 2, 'in-file variable parent and lower sale price pass');

$quoted = report_for("SKU;Name;Type\r\nA;\"A \"\"quoted\"\"\nname\";simple\r\n", ';');
check($quoted['rows_checked'] === 1 && $quoted['errors'] === 0, 'semicolon, escaped quotes and multiline fields parse');

$partial = report_for("ID,Sale price\n42,0\n");
check($partial['errors'] === 0 && $partial['warnings'] === 0, 'partial update may omit Name, Type and Regular price');

$duplicates = report_for("ID,SKU,Type,Parent\n7,shared,variable,\n7,shared,variation,shared\n8,child,variation,id:7\n");
check(has_finding($duplicates, 'duplicate_id', 3), 'duplicate ID is found');
check(has_finding($duplicates, 'duplicate_sku', 3), 'duplicate SKU is found');
check(has_finding($duplicates, 'ambiguous_parent', 4), 'a duplicated parent ID is not resolved arbitrarily');

$order = report_for("SKU,Type,Parent\nchild,variation,parent\nparent,variable,\n");
check(has_finding($order, 'parent_after_variation', 2), 'later parent gets an order warning');

$prices = report_for("SKU,Regular price,Sale price\na,100000000000,99999999999.999999\nb,1.00,1.000001\nc,2,1,5\n");
check(!has_finding($prices, 'sale_not_lower', 2), 'large decimals compare without floating point');
check(has_finding($prices, 'sale_not_lower', 3), 'higher sale price is flagged');
check(has_finding($prices, 'column_count', 4), 'extra delimiter is flagged');

$invalid = report_for("SKU,Published,Stock\nPRIVATE-SKU,yes,1,000\n");
check(has_finding($invalid, 'column_count', 2), 'incorrect record width is an error');
check(strpos(json_encode($invalid), 'PRIVATE-SKU') === false, 'findings omit product identifiers');

rejects("SKU,Name\nPRIVATE-SKU,\"unclosed", 'unclosed quote');
rejects("SKU,Name\nPRIVATE-SKU,ba\"d", 'quote inside plain field');
rejects("SKU,Name\nPRIVATE-SKU,na\0me", 'NUL byte');
rejects("SKU,Name\nPRIVATE-SKU,\xFF", 'invalid UTF-8');
rejects("SKU\nPRIVATE-SKU\n", 'unsupported delimiter', '|');
rejects("SKU\n" . str_repeat("PRIVATE-SKU\n", Analyzer::MAX_ROWS + 1), 'record limit');
rejects(str_repeat('x', Analyzer::MAX_BYTES + 1), 'byte limit');

$many_rows = array();
for ($i = 0; $i < 205; $i++) { $many_rows[] = "item-{$i},wrong"; }
$many = report_for("SKU,Published\n" . implode("\n", $many_rows) . "\n");
check($many['rows_checked'] === 205 && $many['errors'] === 205, 'all accepted records contribute to totals');
check(count($many['findings']) === Analyzer::MAX_FINDINGS && $many['findings_truncated'], 'reported details are bounded');

echo "PASS: {$checks} assertions\n";
