# They install once
composer require kriosa-ai/kriosa-php


<?php
// then add the key!
require_once 'vendor/autoload.php';

// Add this to your index.php or front controller 

/**
 * Kriosa Security Middleware
 * 
 * Protect your application by analyzing every incoming request.
 * Must be placed at the VERY TOP of your entry file or header the covers all the pages (index.php)
 */


$apiKey = getenv('KRIOSA_API_KEY') ?: 'YOUR_API_KEY_HERE';

try {
    $kriosa = new Kriosa($apiKey, [
        'timeout' => 3,          // API request timeout (seconds)
        'debug' => false,        // Enable debug logging
        'fail_closed' => false,   // Block requests if Kriosa is unreachable
        'show_badge' => true,    // Show Kriosa badge on blocked pages (for testing)
    ]);

    if (!$kriosa->protect()) {
        header('X-Kriosa-Blocked: true');
        http_response_code(403);
         exit('Access Denied');
    }
} catch (Exception $e) {
    error_log('Kriosa Security Error: ' . $e->getMessage());
}

// Your application continues safely here...
