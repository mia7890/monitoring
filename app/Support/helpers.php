<?php

if (!function_exists('asset_versioned')) {
    /**
     * URL to a public asset with a filemtime-based cache-busting query
     * string, so browsers only re-download the file when it changes.
     */
    function asset_versioned(string $path): string
    {
        $fullPath = public_path($path);
        $version = is_file($fullPath) ? filemtime($fullPath) : '0';

        return asset($path) . '?v=' . $version;
    }
}