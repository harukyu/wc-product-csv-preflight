<?php
if (PHP_SAPI !== 'cli') { exit; }
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/Analyzer.php';
use Nakaryu\CsvPreflight\Analyzer;

$arguments = $argv;
array_shift($arguments);
if ($arguments === array('--help')) {
    echo "Usage: php bin/preflight.php <file.csv|-> [comma|semicolon|tab]\nJSON to stdout. Exit: 0 clean, 1 warnings, 2 findings with errors, 3 input error.\n";
    exit(0);
}
try {
    if (count($arguments) < 1 || count($arguments) > 2) { throw new RuntimeException('Use --help for usage.'); }
    $choices = array('comma' => ',', 'semicolon' => ';', 'tab' => "\t");
    $choice = isset($arguments[1]) ? $arguments[1] : 'comma';
    if (!isset($choices[$choice])) { throw new RuntimeException('Unsupported delimiter.'); }
    if ($arguments[0] === '-') { $handle = STDIN; }
    else {
        if (!is_file($arguments[0]) || !is_readable($arguments[0])) { throw new RuntimeException('Provide a readable local CSV file.'); }
        $handle = @fopen($arguments[0], 'rb');
        if ($handle === false) { throw new RuntimeException('Could not open CSV.'); }
    }
    $text = stream_get_contents($handle, Analyzer::MAX_BYTES + 1);
    if ($arguments[0] !== '-') { fclose($handle); }
    if ($text === false) { throw new RuntimeException('Could not read CSV.'); }
    $report = (new Analyzer())->analyze($text, $choices[$choice]);
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    exit($report['errors'] ? 2 : ($report['warnings'] ? 1 : 0));
} catch (RuntimeException $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(3); }
