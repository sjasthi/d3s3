<?php
// app/config/lang.php

function _load_csv(string $path): array {
    $map = [];
    if (!file_exists($path)) return $map;
    $fh = fopen($path, 'r');
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) >= 2) {
            $map[$row[0]] = $row[1];
        }
    }
    fclose($fh);
    return $map;
}

function load_language(string $lang = 'en'): void {
    $supported = ['en', 'te'];
    if (!in_array($lang, $supported)) {
        $lang = 'en';
    }

    $GLOBALS['_LANG'] = _load_csv(__DIR__ . "/../../lang/labels_{$lang}.csv");

    $GLOBALS['_LANG_FALLBACK'] = [];
    if ($lang !== 'en') {
        $GLOBALS['_LANG_FALLBACK'] = _load_csv(__DIR__ . '/../../lang/labels_en.csv');
    }
}

function __(string $key): string {
    return $GLOBALS['_LANG'][$key]
        ?? $GLOBALS['_LANG_FALLBACK'][$key]
        ?? $key;
}
