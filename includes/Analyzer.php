<?php
namespace Nakaryu\CsvPreflight;

final class Analyzer {
    const VERSION = '0.1.0';
    const MAX_BYTES = 5242880;
    const MAX_ROWS = 10000;
    const MAX_COLUMNS = 256;
    const MAX_FINDINGS = 200;
    private $report;

    /** Analyze UTF-8 CSV contents without WordPress, a database, or network access. */
    public function analyze($text, $delimiter = ',') {
        if (!is_string($text) || strlen($text) > self::MAX_BYTES) { throw new \RuntimeException('Input must be a CSV of at most 5 MiB.'); }
        if (!in_array($delimiter, array(',', ';', "\t"), true)) { throw new \RuntimeException('Delimiter must be comma, semicolon, or tab.'); }
        if (substr($text, 0, 3) === "\xEF\xBB\xBF") { $text = substr($text, 3); }
        if (strpos($text, "\0") !== false || preg_match('//u', $text) !== 1) { throw new \RuntimeException('Input must be UTF-8 without NUL bytes.'); }
        $this->report = array('schema_version' => 1, 'version' => self::VERSION, 'complete' => true, 'rows_checked' => 0, 'columns' => 0, 'errors' => 0, 'warnings' => 0, 'findings_truncated' => false, 'findings' => array());
        $records = $this->parse($text, $delimiter);
        if (!$records) { $this->issue('error', 'empty_csv', 0, 'CSV is empty.'); return $this->report; }
        $header = array_shift($records);
        $this->report['columns'] = count($header);
        $map = array();
        $supported = array('id', 'type', 'sku', 'name', 'parent', 'regular price', 'sale price', 'stock', 'published');
        foreach ($header as $index => $label) {
            $key = strtolower(trim($label));
            if ($key === '') { $this->issue('error', 'blank_header', 0, 'Column ' . ($index + 1) . ' has no header.'); }
            elseif (isset($map[$key])) { $this->issue('error', 'duplicate_header', 0, 'Column ' . ($index + 1) . ' repeats another header.'); }
            else { $map[$key] = $index; }
        }
        if (!isset($map['sku']) && !isset($map['id']) && !isset($map['name'])) {
            $this->issue('error', 'identity_header_missing', 0, 'At least one English header SKU, ID, or Name is needed. Custom mappings are not inferred.');
        }
        $ignored = count(array_diff(array_keys($map), $supported));
        if ($ignored) { $this->issue('warning', 'unchecked_columns', 0, $ignored . ' columns are outside this checker\'s supported subset.'); }
        $rows = array(); $skus = array(); $ids = array();
        foreach ($records as $index => $record) {
            $number = $index + 2;
            if (count($record) === 1 && $record[0] === '') { continue; }
            $this->report['rows_checked']++;
            if (count($record) !== count($header)) { $this->issue('error', 'column_count', $number, 'Record width differs from header width.'); continue; }
            $row = array('row' => $number);
            foreach ($supported as $key) { $row[$key] = isset($map[$key]) ? trim($record[$map[$key]]) : ''; }
            $id = $row['id']; $sku = $row['sku'];
            if ($id !== '' && !preg_match('/\A[1-9][0-9]{0,17}\z/', $id)) { $this->issue('error', 'invalid_id', $number, 'ID must be a positive integer of at most 18 digits.'); }
            elseif ($id !== '') {
                if (isset($ids['id:' . $id])) { $this->issue('error', 'duplicate_id', $number, 'ID repeats record ' . $ids['id:' . $id]['row'] . '.'); $ids['id:' . $id]['ambiguous'] = true; }
                else { $ids['id:' . $id] = array('row' => $number, 'type' => '', 'ambiguous' => false); }
            }
            if ($sku !== '') {
                if (isset($skus['sku:' . $sku])) { $this->issue('error', 'duplicate_sku', $number, 'SKU repeats record ' . $skus['sku:' . $sku]['row'] . ' (exact match).'); $skus['sku:' . $sku]['ambiguous'] = true; }
                else { $skus['sku:' . $sku] = array('row' => $number, 'type' => '', 'ambiguous' => false); }
            } elseif ($id === '') { $this->issue('warning', 'missing_identifier', $number, 'No SKU or ID supplied; repeat imports may lack a stable identifier.'); }
            if (isset($map['name']) && $row['name'] === '') { $this->issue('warning', 'blank_name', $number, 'Name is blank; this may be intentional for an update, but needs review for a new product.'); }
            $base = '';
            if ($row['type'] !== '') {
                $types = array_map('trim', explode(',', strtolower($row['type'])));
                $bases = array_intersect($types, array('simple', 'variable', 'grouped', 'external', 'variation'));
                $unknown = array_diff($types, array('simple', 'variable', 'grouped', 'external', 'variation', 'virtual', 'downloadable'));
                if ($unknown || count($bases) !== 1 || count($types) !== count(array_unique($types))) { $this->issue('error', 'unsupported_type', $number, 'Type needs one standard base type and optional virtual/downloadable flags. Extension types are not supported.'); }
                else { $base = reset($bases); }
            }
            $row['base'] = $base;
            if ($id !== '' && isset($ids['id:' . $id]) && $ids['id:' . $id]['row'] === $number) { $ids['id:' . $id]['type'] = $base; }
            if ($sku !== '' && $skus['sku:' . $sku]['row'] === $number) { $skus['sku:' . $sku]['type'] = $base; }
            foreach (array('regular price', 'sale price') as $key) {
                if ($row[$key] !== '' && !$this->is_price($row[$key])) { $this->issue('error', 'invalid_price', $number, ucfirst($key) . ' must be a non-negative decimal with a dot, up to 12 integer and 6 fractional digits.'); }
            }
            if ($this->is_price($row['regular price']) && $this->is_price($row['sale price']) && $this->price_compare($row['sale price'], $row['regular price']) >= 0) { $this->issue('warning', 'sale_not_lower', $number, 'Sale price is equal to or above regular price; sale dates are not evaluated.'); }
            if ($row['stock'] !== '' && $row['stock'] !== 'parent' && !preg_match('/\A-?[0-9]{1,12}(?:\.[0-9]{1,6})?\z/', $row['stock'])) { $this->issue('error', 'invalid_stock', $number, 'Stock must be a plain number or parent.'); }
            if ($row['stock'] === 'parent' && $base !== '' && $base !== 'variation') { $this->issue('warning', 'stock_parent_type', $number, 'Stock parent is intended for variations.'); }
            if ($row['published'] !== '' && !in_array($row['published'], array('1', '0', '-1'), true)) { $this->issue('error', 'invalid_published', $number, 'Published must be 1, 0, or -1.'); }
            if ($base === 'variation' && $row['parent'] === '') { $this->issue('error', 'missing_parent', $number, 'Variation has no Parent reference.'); }
            if ($row['parent'] !== '' && $base !== '' && $base !== 'variation') { $this->issue('warning', 'unexpected_parent', $number, 'Parent supplied for a non-variation type.'); }
            $rows[] = $row;
        }
        foreach ($rows as $row) {
            $parent = $row['parent'];
            if ($parent === '') { continue; }
            if (strpos($parent, 'id:') === 0) {
                if (!preg_match('/\Aid:[1-9][0-9]{0,17}\z/', $parent)) { $this->issue('error', 'invalid_parent_id', $row['row'], 'Parent ID must use id: followed by a positive integer.'); continue; }
                $target = isset($ids[$parent]) ? $ids[$parent] : null;
                $self = $parent === 'id:' . $row['id'];
            } else {
                $target = isset($skus['sku:' . $parent]) ? $skus['sku:' . $parent] : null;
                $self = $parent === $row['sku'];
            }
            if ($self) { $this->issue('error', 'self_parent', $row['row'], 'A record references itself as Parent.'); }
            elseif ($target === null) { $this->issue('warning', 'external_parent', $row['row'], 'Parent is absent from this CSV. It may exist in the store; no database lookup was made.'); }
            elseif ($target['ambiguous']) { $this->issue('error', 'ambiguous_parent', $row['row'], 'Parent identifier occurs more than once in this CSV.'); }
            else {
                if ($target['type'] !== '' && $target['type'] !== 'variable') { $this->issue('error', 'non_variable_parent', $row['row'], 'Parent record does not have variable type.'); }
                if ($target['type'] === '') { $this->issue('warning', 'parent_type_unknown', $row['row'], 'Parent type was not resolved in this CSV.'); }
                if ($target['row'] > $row['row']) { $this->issue('warning', 'parent_after_variation', $row['row'], 'Parent occurs after the variation; import order may need adjustment.'); }
            }
        }
        return $this->report;
    }

    /** Strict RFC-style quoted fields; records can include embedded newlines. */
    private function parse($text, $delimiter) {
        $records = array(); $record = array(); $field = ''; $state = 'start'; $length = strlen($text); $cells = 0;
        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            if ($state === 'quoted') {
                if ($char === '"') {
                    if ($i + 1 < $length && $text[$i + 1] === '"') { $field .= '"'; $i++; }
                    else { $state = 'closed'; }
                } else { $field .= $char; }
                continue;
            }
            if ($char === $delimiter || $char === "\n" || $char === "\r") {
                $record[] = $field; $field = ''; $state = 'start'; $cells++;
                if ($cells > 100000) { throw new \RuntimeException('CSV exceeds 100000 cells.'); }
                if (count($record) > self::MAX_COLUMNS) { throw new \RuntimeException('CSV exceeds 256 columns.'); }
                if ($char !== $delimiter) {
                    if ($char === "\r" && $i + 1 < $length && $text[$i + 1] === "\n") { $i++; }
                    $records[] = $record; $record = array();
                    if (count($records) > self::MAX_ROWS + 1) { throw new \RuntimeException('CSV exceeds 10000 data records including blank records.'); }
                }
            } elseif ($char === '"' && $state === 'start') { $state = 'quoted'; }
            elseif ($char === '"' || $state === 'closed') { throw new \RuntimeException('Malformed quoting: quotes must enclose the whole field and use doubled quote escaping.'); }
            else { $field .= $char; $state = 'plain'; }
        }
        if ($state === 'quoted') { throw new \RuntimeException('Unclosed quoted field.'); }
        if ($state !== 'start' || $field !== '' || $record) { $record[] = $field; $records[] = $record; $cells++; }
        if (count($record) > self::MAX_COLUMNS || count($records) > self::MAX_ROWS + 1 || $cells > 100000) { throw new \RuntimeException('CSV exceeds row, column, or cell limits.'); }
        return $records;
    }

    private function issue($severity, $code, $row, $message) {
        $this->report[$severity === 'error' ? 'errors' : 'warnings']++;
        if (count($this->report['findings']) < self::MAX_FINDINGS) {
            $this->report['findings'][] = array('severity' => $severity, 'code' => $code, 'record' => $row, 'message' => $message);
        } else { $this->report['findings_truncated'] = true; }
    }

    private function is_price($value) { return preg_match('/\A[0-9]{1,12}(?:\.[0-9]{1,6})?\z/', $value) === 1; }
    private function price_compare($left, $right) {
        $parts_l = explode('.', $left); $parts_r = explode('.', $right);
        $integer_l = ltrim($parts_l[0], '0'); $integer_r = ltrim($parts_r[0], '0');
        if (strlen($integer_l) !== strlen($integer_r)) { return strlen($integer_l) < strlen($integer_r) ? -1 : 1; }
        $comparison = strcmp($integer_l, $integer_r);
        if ($comparison !== 0) { return $comparison; }
        return strcmp(str_pad(isset($parts_l[1]) ? $parts_l[1] : '', 6, '0'), str_pad(isset($parts_r[1]) ? $parts_r[1] : '', 6, '0'));
    }
}
