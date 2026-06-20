<?php
/**
 * Kriosa Helper Functions
 * 
 * This allows the ONE-LINE protection that users love
 * 
 * Note: The Kriosa class is already in global namespace (no namespace declared in kriosa.php)
 */

if (!function_exists('kriosa_protect')) {
    /**
     * Ultra-simple protection - ONE LINE
     * 
     * @param string $apiKey Your Kriosa API key
     * @return bool True if allowed, false if blocked
     */
    function kriosa_protect(string $apiKey): bool
    {
        // Kriosa class is in global namespace (no \Kriosa\ prefix needed)
        return Kriosa::quick($apiKey);
    }
}

if (!function_exists('kriosa_check')) {
    /**
     * Detailed protection check
     * 
     * @param string $apiKey Your Kriosa API key
     * @return array Detailed result
     */
    function kriosa_check(string $apiKey): array
    {
        $kriosa = new Kriosa($apiKey);
        $allowed = $kriosa->protect();
        
        return [
            'allowed' => $allowed,
            'timestamp' => time(),
            'source' => 'kriosa_php_sdk',
            'version' => '3.0.0'
        ];
    }
}