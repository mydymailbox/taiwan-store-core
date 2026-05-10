<?php
/**
 * Mass Rename Script (Safe Encoding)
 */

$dir = __DIR__ . '/includes';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$php_files = new RegexIterator($files, '/\.php$/');

$replacements = [
    'wc-tw-core' => 'taiwan-store-core',
    'WC_TW_Core' => 'Taiwan_Store_Core',
    'wc_tw_core_' => 'taiwan_store_core_',
    'WC_TW_CORE_' => 'TAIWAN_STORE_CORE_',
];

foreach ($php_files as $file) {
    $path = $file->getRealPath();
    $content = file_get_contents($path);
    $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        echo "Updated: $path\n";
    }
}

// Also main file
$main_file = __DIR__ . '/taiwan-store-core.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
    file_put_contents($main_file, $newContent);
}

// Also readme.txt
$readme = __DIR__ . '/readme.txt';
if (file_exists($readme)) {
    $content = file_get_contents($readme);
    $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
    file_put_contents($readme, $newContent);
}
