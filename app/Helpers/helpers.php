<?php

/**
 * DWAI Studio Helper Functions
 * 
 * Add custom helper functions here.
 * Functions are auto-loaded via composer.json
 */

if (!function_exists('dwai_asset')) {
    /**
     * Generate asset URL for local development.
     */
    function dwai_asset(string $path): string
    {
        if (app()->environment('local')) {
            return asset($path);
        }
        return asset($path);
    }
}

if (!function_exists('format_memory')) {
    /**
     * Format bytes to human readable.
     */
    function format_memory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
