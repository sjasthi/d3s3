<?php
// app/config/lang.php

function load_language(string $lang = 'en'): void {
    $supported = ['en', 'te'];
    if (!in_array($lang, $supported)) {
        $lang = 'en';
    }

    $files = ['case_sheet', 'intake'];

    // Load requested language
    $GLOBALS['_LANG'] = [];
    foreach ($files as $file) {
        $path = __DIR__ . "/../../lang/{$lang}/{$file}.php";
        if (file_exists($path)) {
            $GLOBALS['_LANG'] = array_merge($GLOBALS['_LANG'], require $path);
        }
    }

    // Load English fallback (used by __() when a key is missing in non-English)
    $GLOBALS['_LANG_FALLBACK'] = [];
    if ($lang !== 'en') {
        foreach ($files as $file) {
            $path = __DIR__ . "/../../lang/en/{$file}.php";
            if (file_exists($path)) {
                $GLOBALS['_LANG_FALLBACK'] = array_merge($GLOBALS['_LANG_FALLBACK'], require $path);
            }
        }
    }
}

function __(string $key): string {
    return $GLOBALS['_LANG'][$key]
        ?? $GLOBALS['_LANG_FALLBACK'][$key]
        ?? $key;
}
