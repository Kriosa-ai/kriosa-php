# They install once
composer require kriosa/kriosa


<?php
// then add the key!
require_once 'vendor/autoload.php';
define('KRIOSA_API_KEY', 'sk_their_key');

// Your app continues...
echo "Hello World!";