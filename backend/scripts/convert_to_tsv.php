<?php
/**
 * Convert transactions SQL file → TSV untuk LOAD DATA INFILE.
 * Format: tab-separated, NULL = \N
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dir = storage_path('app/backup');
$src = $argv[1] ?? null;
if (! $src) {
    $files = glob($dir . '/db_legacy_tx_*.sql');
    sort($files);
    // skip _ignore.sql
    foreach (array_reverse($files) as $f) {
        if (! str_contains(basename($f), '_ignore')) { $src = $f; break; }
    }
}
$src = realpath($src);
echo "Convert: {$src}\n";

$dst = str_replace('.sql', '.tsv', $src);
$fh = fopen($src, 'r');
$outFh = fopen($dst, 'w');
$rowCount = 0;

while (($line = fgets($fh)) !== false) {
    $trim = trim($line);
    if (! preg_match('/^INSERT(?:\s+IGNORE)?\s+INTO\s+`transactions`\s+\([^)]+\)\s+VALUES\s+(.+);$/i', $trim, $m)) continue;
    $valuesStr = $m[1];

    // Parse rows: split by '),('
    $rows = preg_split('/\),\(/', $valuesStr);
    foreach ($rows as &$row) {
        // Hapus kurung buka/tutup kalau ada
        if (str_starts_with($row, '(')) $row = substr($row, 1);
        if (str_ends_with($row, ')')) $row = substr($row, 0, -1);

        // Parse fields: handle strings & NULL
        $fields = [];
        $buf = '';
        $inStr = false;
        $len = strlen($row);
        for ($i = 0; $i < $len; $i++) {
            $ch = $row[$i];
            if ($ch === '\\' && $inStr && $i + 1 < $len) {
                $next = $row[$i + 1];
                if ($next === 'n') $buf .= "\n";
                elseif ($next === 't') $buf .= "\t";
                elseif ($next === '\\') $buf .= '\\';
                elseif ($next === "'") $buf .= "'";
                else $buf .= $next;
                $i++;
                continue;
            }
            if ($ch === "'" && ! $inStr) { $inStr = true; continue; }
            if ($ch === "'" && $inStr) { $inStr = false; continue; }
            if ($ch === "," && ! $inStr) {
                $fields[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        $fields[] = $buf;

        // Normalisasi: NULL → \N
        $fields = array_map(function ($f) {
            return $f === 'NULL' ? '\\N' : $f;
        }, $fields);

        // Escape tab/newline untuk string fields (replace dengan escaped versions)
        $fields = array_map(function ($f) {
            if ($f === '\\N') return $f;
            $f = str_replace(["\t", "\n", "\r"], ['\\t', '\\n', '\\r'], $f);
            return $f;
        }, $fields);

        fwrite($outFh, implode("\t", $fields) . "\n");
        $rowCount++;
    }
}
fclose($fh);
fclose($outFh);

echo "Wrote {$rowCount} rows to: {$dst} (" . filesize($dst) . " bytes)\n";