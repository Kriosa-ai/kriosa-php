<?php
// examples/basic.php
require_once __DIR__ . '/../vendor/autoload.php';

// Set your API key
define('KRIOSA_API_KEY', 'sk_your_api_key_here');

echo "Kriosa Security is active!\n";
echo "Your app is now protected from:\n";
echo "- SQL Injection\n";
echo "- XSS (Cross-Site Scripting)\n";
echo "- Command Injection\n";
echo "- And 10+ other attacks\n";