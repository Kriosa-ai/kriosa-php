<?php
/**
 * WordPress Integration Example
 * 
 * How to integrate Kriosa Security with WordPress
 * 
 * Installation:
 * 1. Copy kriosa.php to your WordPress root directory (where wp-config.php is)
 * 2. Add the code below to your theme's functions.php OR wp-config.php
 * 3. Define your API key
 * 
 * Method 1: Add to wp-config.php (Recommended - protects everything)
 * Method 2: Add to theme's functions.php (protects only that theme)
 * Method 3: WordPress Plugin (easiest - search "Kriosa Security" in plugins)
 */

// ==================== METHOD 1: wp-config.php (Best for whole site) ====================
/*
// Add this to your wp-config.php file (after database settings, before "stop editing")

// Load Kriosa
require_once dirname(__FILE__) . '/kriosa.php';

// Set your API key (get from https://cloud.kriosa.ai)
define('KRIOSA_API_KEY', 'sk_your_api_key_here');

// Optional: Disable auto-protection for specific paths
// define('KRIOSA_SKIP_PATHS', ['/wp-admin/admin-ajax.php', '/wp-cron.php']);

// Kriosa will automatically protect every request!
*/


// ==================== METHOD 2: functions.php (Theme-specific) ====================
/*
// Add this to your theme's functions.php

add_action('init', 'kriosa_wordpress_protect');

function kriosa_wordpress_protect() {
    // Define paths to skip (optional)
    $skipPaths = ['/wp-admin/admin-ajax.php', '/wp-cron.php'];
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    
    foreach ($skipPaths as $skip) {
        if (strpos($currentPath, $skip) !== false) {
            return; // Skip protection for this path
        }
    }
    
    require_once dirname(dirname(__FILE__)) . '/kriosa.php';
    
    if (!kriosa_protect(get_option('kriosa_api_key', ''))) {
        wp_die(
            '<h1>Request Blocked</h1><p>This request was blocked by Kriosa Security.</p>',
            'Security Violation',
            ['response' => 403]
        );
    }
}

// Add settings page to enter API key
add_action('admin_menu', 'kriosa_add_admin_menu');

function kriosa_add_admin_menu() {
    add_options_page(
        'Kriosa Security',
        'Kriosa Security',
        'manage_options',
        'kriosa-security',
        'kriosa_settings_page'
    );
}

function kriosa_settings_page() {
    if (isset($_POST['kriosa_api_key'])) {
        update_option('kriosa_api_key', sanitize_text_field($_POST['kriosa_api_key']));
        echo '<div class="notice notice-success"><p>API key saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Kriosa Security Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="text" 
                               name="kriosa_api_key" 
                               value="<?php echo esc_attr(get_option('kriosa_api_key', '')); ?>" 
                               class="regular-text" 
                               placeholder="sk_..." />
                        <p class="description">Get your API key from <a href="https://cloud.kriosa.ai" target="_blank">cloud.kriosa.ai</a></p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save API Key'); ?>
        </form>
    </div>
    <?php
}
*/


// ==================== METHOD 3: Simple Protection (Add to any plugin) ====================
/*
// Simplest - just add to any PHP file in your WordPress installation

require_once dirname(__FILE__) . '/kriosa.php';
define('KRIOSA_API_KEY', 'sk_your_api_key_here');

// That's it! Kriosa auto-protects everything.
*/


// ==================== TEST YOUR INTEGRATION ====================
/*
// Add this to a test page to verify Kriosa is working
// Create a file called test-kriosa.php in your WordPress root

require_once 'kriosa.php';

$apiKey = 'sk_your_api_key_here'; // Your actual API key

echo "<h1>Kriosa Security Test</h1>";

// Test 1: Normal request
echo "<h3>Test 1: Normal Request</h3>";
$result = kriosa_protect($apiKey);
echo $result ? "✅ Allowed (Normal request passed)" : "❌ Blocked (Unexpected)";
echo "<br>";

// Test 2: SQL injection (should be blocked)
echo "<h3>Test 2: SQL Injection Attack</h3>";
$_POST['search'] = "' OR '1'='1' --";
$result = kriosa_protect($apiKey);
echo !$result ? "✅ Blocked (SQL injection detected)" : "❌ Allowed (Security failed)";
echo "<br>";

// Test 3: XSS attack (should be blocked)
echo "<h3>Test 3: XSS Attack</h3>";
$_POST['comment'] = "<script>alert('XSS')</script>";
$result = kriosa_protect($apiKey);
echo !$result ? "✅ Blocked (XSS detected)" : "❌ Allowed (Security failed)";

// Clean up
unset($_POST['search']);
unset($_POST['comment']);

echo "<hr>";
echo "<p>If all tests passed, your WordPress site is protected!</p>";
*/


// ==================== IMPORTANT NOTES ====================
/*
 * 1. Always keep your API key secret - never commit to public repositories
 * 2. Test on staging before enabling on production
 * 3. Monitor your security logs in the Kriosa dashboard
 * 4. White-list false positives in your dashboard
 * 5. For high-traffic sites, enable caching
 * 
 * Need help? Contact: support@kriosa.com
 */