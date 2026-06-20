<?php

/**
 * Kriosa Security - Production Ready SDK
 * 
 * One file. Zero dependencies. Instant protection.
 * 
 * @version 3.0.0
 * @link https://kriosa.com
 * @license MIT
 */
// ==================== MAIN CLASS ====================

class Kriosa
{
    private const VERSION = '3.0.0';
    private const DEFAULT_TIMEOUT = 5;
    private const CACHE_TTL = 300;

    private string $apiKey;
    private string $apiUrl;
    private int $timeout;
    private bool $debug;
    private ?string $cacheDir;
    private array $config;

    // Static properties for auto-badge injection
    private static ?Kriosa $instance = null;
    private static bool $isProtected = false;
    private static bool $badgeInjected = false;
    private static bool $bufferStarted = false;

    public function __construct(string $apiKey, array $options = [])
    {
        if (!$this->isValidApiKey($apiKey)) {
            throw new InvalidArgumentException('Invalid API key format. API key should start with "sk_" and be at least 20 characters.');
        }

        $this->apiKey = $apiKey;
        $this->config = $options;
        $this->apiUrl = $options['api_url'] ?? $this->detectApiUrl();
        $this->timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        $this->debug = $options['debug'] ?? false;
        $this->cacheDir = $options['cache_dir'] ?? $this->getDefaultCacheDir();

        if ($this->cacheDir && !is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        // ==================== AUTO-BADGE SETUP ====================
        self::$instance = $this;
        $showBadge = $this->config['show_badge'] ?? true;
        
        // Start output buffering to inject the badge automatically before </body>
        if ($showBadge && !self::$bufferStarted && php_sapi_name() !== 'cli') {
            ob_start([$this, 'injectBadgeCallback']);
            self::$bufferStarted = true;
        }
        // =======================================================

        $this->log("Kriosa initialized", [
            'version' => self::VERSION,
            'api_url' => $this->apiUrl,
            'cache_enabled' => $this->cacheDir !== null
        ]);
    }

    private function isGoodBot(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $goodBots = [
            'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 
            'YandexBot', 'facebookexternalhit', 'Twitterbot', 'LinkedInBot', 'Applebot', 'PetalBot'
        ];

        foreach ($goodBots as $bot) {
            if (stripos($userAgent, $bot) !== false) return true;
        }
        return false;
    }

    public function protect(): bool
    {
        if ($this->isGoodBot()) {
            error_log("KRIOSA: Allowing good bot - " . $_SERVER['HTTP_USER_AGENT']);
            self::$isProtected = true; // Mark as protected to show badge
            return true;
        }

        $startTime = microtime(true);
        $requestId = $this->generateRequestId();
        $this->log("Security check started", ['request_id' => $requestId]);

        try {
            $requestData = $this->buildRequestData($requestId);
            $cachedResult = $this->getCachedResult($requestData);
            
            if ($cachedResult !== null) {
                $this->log("Cache hit", ['request_id' => $requestId]);
                self::$isProtected = true;
                return $cachedResult;
            }

            $result = $this->callApi($requestData, $requestId);

            if ($result['allowed']) {
                self::$isProtected = true; // Mark as protected to show badge
                $this->cacheResult($requestData, $result['allowed']);
            } else {
                $this->showBlockPage($result);
            }

            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->log("Security check completed", [
                'request_id' => $requestId,
                'allowed' => $result['allowed'],
                'processing_time_ms' => round($processingTime, 2)
            ]);

            return $result['allowed'];
        } catch (Exception $e) {
            $this->log("Security check failed", [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
            
            $allowed = !($this->config['fail_closed'] ?? false);
            if ($allowed) self::$isProtected = true;
            
            return $allowed;
        }
    }

    private function showBlockPage(array $result): void
    {
        http_response_code(403);
        header('X-Kriosa-Blocked: true');
        $refId = substr(md5(uniqid()), 0, 12);

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>403 - Access Denied</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; }
        @keyframes shakeHand { 0% { transform: rotate(0deg); } 20% { transform: rotate(15deg); } 40% { transform: rotate(-12deg); } 60% { transform: rotate(8deg); } 80% { transform: rotate(-4deg); } 100% { transform: rotate(0deg); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .container { text-align: center; max-width: 450px; animation: fadeIn 0.5s ease; }
        .hand-icon { margin-bottom: 30px; }
        .hand-icon i { font-size: 80px; color: #ff3366; display: inline-block; animation: shakeHand 0.6s ease-in-out 3; filter: drop-shadow(0 0 20px rgba(255,51,102,0.3)); }
        .code { font-size: 72px; font-weight: 800; background: linear-gradient(135deg, #ff3366, #ff6b6b); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 16px; letter-spacing: 4px; }
        h1 { color: #ffffff; font-size: 24px; font-weight: 500; margin-bottom: 12px; }
        .divider { width: 50px; height: 2px; background: linear-gradient(90deg, transparent, #ff3366, transparent); margin: 20px auto; }
        p { color: rgba(255,255,255,0.6); font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
        .ref { font-size: 11px; font-family: monospace; color: rgba(255,255,255,0.25); margin-bottom: 24px; word-break: break-all; }
        .btn { display: inline-block; padding: 12px 28px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #ffffff; text-decoration: none; border-radius: 40px; font-size: 14px; font-weight: 500; transition: all 0.3s ease; cursor: pointer; }
        .btn:hover { background: rgba(255,51,102,0.2); border-color: rgba(255,51,102,0.5); transform: translateY(-2px); }
        .secure-badge { margin-top: 30px; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 10px; color: rgba(255,255,255,0.2); }
        .secure-badge i { font-size: 10px; }
        @media (max-width: 480px) { .hand-icon i { font-size: 60px; } .code { font-size: 48px; } h1 { font-size: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="hand-icon"><i class="fas fa-hand-peace"></i></div>
        <div class="code">403</div>
        <h1>Access Denied</h1>
        <div class="divider"></div>
        <p>The server could not process your request.<br>Please verify your input and try again.</p>
        <div class="ref">REF: {$refId}</div>
        <button class="btn" onclick="history.back()">← Go Back</button>
        <div class="secure-badge">
            <i class="fas fa-shield-alt"></i>
            <span>Secure Connection</span>
            <i class="fas fa-lock"></i>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }

    public static function quick(string $apiKey): bool
    {
        static $instance = null;
        if ($instance === null) $instance = new self($apiKey);
        return $instance->protect();
    }

    public function check(): array
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();

        try {
            $requestData = $this->buildRequestData($requestId);
            $result = $this->callApi($requestData, $requestId);
            $result['processing_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            $result['request_id'] = $requestId;
            $result['kriosa_version'] = self::VERSION;
            return $result;
        } catch (Exception $e) {
            return [
                'allowed' => true, 'error' => $e->getMessage(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'request_id' => $requestId, 'kriosa_version' => self::VERSION, 'fallback' => true
            ];
        }
    }

    private function buildRequestData(string $requestId): array
    {
        $server = $_SERVER;
        $ip = $this->getClientIp();
        $userAgent = $server['HTTP_USER_AGENT'] ?? '';
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = $server['REQUEST_URI'] ?? '/';

        // FIX: Safely check session status to prevent PHP warnings
        $hasSession = session_status() === PHP_SESSION_ACTIVE;
        $sessionId = $hasSession ? session_id() : null;
        $userId = $hasSession ? ($_SESSION['user_id'] ?? $_SESSION['userid'] ?? $_SESSION['uid'] ?? 0) : 0;
        $username = $hasSession ? ($_SESSION['username'] ?? $_SESSION['user'] ?? $_SESSION['email'] ?? null) : null;
        $tenantId = $hasSession ? ($_SESSION['tenant_id'] ?? $_SESSION['tenant'] ?? null) : null;
        $companyId = $hasSession ? ($_SESSION['company_id'] ?? $_SESSION['company'] ?? null) : null;

        $isAuthenticated = ($userId > 0) || !empty($server['HTTP_AUTHORIZATION']) || !empty($server['HTTP_X_API_KEY']);

        return [
            'api_key' => $this->apiKey, 'request_id' => $requestId, 'timestamp' => time(), 'source' => 'kriosa_php_sdk_v3',
            'ip_address' => $ip, 'user_agent' => $userAgent, 'session_id' => $sessionId,
            'session_user_id' => $userId, 'session_username' => $username, 'session_tenant_id' => $tenantId, 'session_company_id' => $companyId,
            'method' => $method, 'resource' => $uri, 'path' => $uri, 'query_string' => $server['QUERY_STRING'] ?? '',
            'payload' => $this->getPayload(), 'query_params' => $_GET, 'headers' => $this->getRelevantHeaders(),
            'is_authenticated' => $isAuthenticated, 'sdk_version' => self::VERSION, 'sdk_language' => 'php', 'php_version' => PHP_VERSION
        ];
    }

    private function getPayload(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            if ($input) {
                $data = json_decode($input, true);
                if (is_array($data)) return $data;
            }
        }
        return $_POST;
    }

    private function getRelevantHeaders(): array
    {
        $headers = [];
        $relevant = [
            'HTTP_REFERER' => 'Referer', 'HTTP_ORIGIN' => 'Origin', 'HTTP_ACCEPT_LANGUAGE' => 'Accept-Language',
            'HTTP_ACCEPT_ENCODING' => 'Accept-Encoding', 'HTTP_X_FORWARDED_FOR' => 'X-Forwarded-For',
            'HTTP_X_REAL_IP' => 'X-Real-IP', 'HTTP_X_REQUEST_ID' => 'X-Request-Id'
        ];
        foreach ($relevant as $serverKey => $headerName) {
            if (isset($_SERVER[$serverKey]) && $_SERVER[$serverKey]) $headers[$headerName] = $_SERVER[$serverKey];
        }
        return $headers;
    }

    private function getClientIp(): string
    {
        $ip = null;
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        elseif (isset($_SERVER['HTTP_X_REAL_IP'])) $ip = $_SERVER['HTTP_X_REAL_IP'];
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
        else $ip = '127.0.0.1';

        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') $ip = '127.0.0.1';
        if (empty($ip) || $ip === '0.0.0.0') $ip = '127.0.0.1';
        
        return $ip;
    }

    private function isValidIp(string $ip): bool { return filter_var($ip, FILTER_VALIDATE_IP) !== false; }

    private function callApi(array $requestData, string $requestId): array
    {
        if (!function_exists('curl_init')) return $this->fallbackResponse('cURL not available');

        $ch = curl_init($this->apiUrl);
        $jsonData = json_encode($requestData);
        if ($jsonData === false) return $this->fallbackResponse('JSON encoding failed');

        $headers = [
            'Content-Type: application/json', 'Accept: application/json',
            'User-Agent: Kriosa-PHP-SDK/' . self::VERSION, 'X-Request-ID: ' . $requestId
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) return $this->fallbackResponse($curlError);
        if ($httpCode === 403) return ['allowed' => false, 'reason' => 'attack_blocked'];
        if ($httpCode !== 200) return $this->fallbackResponse("HTTP {$httpCode}");

        $result = json_decode($response, true);
        if (!is_array($result)) return $this->fallbackResponse('Invalid JSON response');

        return $result;
    }

    private function fallbackResponse(string $reason = ''): array
    {
        if ($this->hasLocalAttackPattern()) return ['allowed' => false, 'reason' => 'local_pattern_match'];
        return ['allowed' => true, 'fallback' => true, 'reason' => $reason];
    }

    private function hasLocalAttackPattern(): bool
    {
        $input = json_encode($_POST) . ' ' . json_encode($_GET) . ' ' . ($_SERVER['REQUEST_URI'] ?? '');
        $patterns = [
            '/\b(union\s+select|select.*from|insert\s+into|delete\s+from|drop\s+table)\b/i',
            '/<script[^>]*>.*<\/script[^>]*>/is', '/javascript\s*:/i', '/;.*(ls|dir|cat|whoami|id|pwd)/i',
            '/\.\.\//', '/\.\.\\\\/', '/\$\w+/'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) return true;
        }
        return false;
    }

    private function getCachedResult(array $requestData): ?bool
    {
        if (!$this->cacheDir) return null;
        $cacheFile = $this->cacheDir . '/' . $this->getCacheKey($requestData) . '.cache';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            $data = @unserialize(@file_get_contents($cacheFile));
            if (is_array($data) && isset($data['allowed'])) return $data['allowed'];
        }
        return null;
    }

    private function cacheResult(array $requestData, bool $allowed): void
    {
        if (!$this->cacheDir) return;
        $cacheFile = $this->cacheDir . '/' . $this->getCacheKey($requestData) . '.cache';
        @file_put_contents($cacheFile, serialize(['allowed' => $allowed, 'cached_at' => time()]));
    }

    private function getCacheKey(array $requestData): string
    {
        $keyData = [
            'api_key' => substr(md5($this->apiKey), 0, 16),
            'resource' => $requestData['resource'] ?? '/',
            'method' => $requestData['method'] ?? 'GET'
        ];
        return 'kriosa_' . md5(json_encode($keyData));
    }

    private function isValidApiKey(string $apiKey): bool { return strpos($apiKey, 'sk_') === 0 && strlen($apiKey) >= 20; }

    private function detectApiUrl(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) return 'http://localhost/api.php/v1/protect';
        return 'https://kriosa.com/api.php/v1/protect';
    }

    private function getDefaultCacheDir(): ?string
    {
        $tempDir = sys_get_temp_dir() . '/kriosa_cache';
        if (is_writable(dirname($tempDir))) return $tempDir;
        if (is_writable(__DIR__)) return __DIR__ . '/.kriosa_cache';
        return null;
    }

    private function generateRequestId(): string { return 'req_' . uniqid() . '_' . bin2hex(random_bytes(4)); }

    private function log(string $message, array $context = []): void
    {
        if (!$this->debug) return;
        $log = date('Y-m-d H:i:s') . " [KRIOSA] " . $message;
        if (!empty($context)) $log .= " " . json_encode($context);
        error_log($log);
    }

    // ==================== AUTO-BADGE INJECTION METHODS ====================

    /**
     * Output buffer callback to inject the badge before </body>
     */
    public function injectBadgeCallback($buffer)
    {
        if (!self::$isProtected) return $buffer;
        if ($this->isAdminPage()) return $buffer;
        
        // Skip injection for AJAX, API, or JSON responses
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
        $isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        
        if ($isAjax || $isApi || $isJson) return $buffer;
        if (self::$badgeInjected) return $buffer;

        self::$badgeInjected = true;

        $badgePosition = $this->config['badge_position'] ?? 'bottom-right';
        $badgeSize = $this->config['badge_size'] ?? 'small';
        $badgeHtml = $this->getBadgeHtml($badgePosition, $badgeSize);

        // Inject before closing body tag
        if (stripos($buffer, '</body>') !== false) {
            return str_ireplace('</body>', $badgeHtml . "\n</body>", $buffer);
        } elseif (stripos($buffer, '</html>') !== false) {
            return str_ireplace('</html>', $badgeHtml . "\n</html>", $buffer);
        }
        
        return $buffer . "\n" . $badgeHtml;
    }

    private function isAdminPage(): bool
    {
        $adminPaths = ['/admin', '/wp-admin', '/administrator', '/login', '/register'];
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($adminPaths as $path) {
            if (strpos($uri, $path) !== false) return true;
        }
        return false;
    }

    /**
     * Allow users to manually disable the badge if needed
     */
    public static function disableBadge(): void
    {
        self::$badgeInjected = true;
    }

//     private function getBadgeHtml($position, $size): string
//     {
//         $positions = [
//             'bottom-right' => 'bottom: 20px; right: 20px;',
//             'bottom-left' => 'bottom: 20px; left: 20px;',
//             'top-right' => 'top: 20px; right: 20px;',
//             'top-left' => 'top: 20px; left: 20px;',
//             'footer' => 'position: relative; text-align: center; margin: 20px 0;'
//         ];
        
//         $positionStyle = $positions[$position] ?? $positions['bottom-right'];
//         $isFixed = !in_array($position, ['footer']);
//         $positionType = $isFixed ? 'fixed' : 'relative';
        
//         $sizes = [
//             'small' => ['width' => 120, 'height' => 35, 'font-size' => 9, 'icon-size' => 14],
//             'medium' => ['width' => 150, 'height' => 45, 'font-size' => 11, 'icon-size' => 18],
//             'large' => ['width' => 200, 'height' => 55, 'font-size' => 13, 'icon-size' => 22]
//         ];
        
//         $s = $sizes[$size] ?? $sizes['small'];
        
//         return '
// <style>
// .kriosa-auto-badge { position: ' . $positionType . '; ' . $positionStyle . ' z-index: 999999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; animation: kriosaFadeIn 0.5s ease; }
// .kriosa-auto-badge a { display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: 40px; padding: 6px 14px; text-decoration: none; border: 1px solid rgba(255, 51, 102, 0.3); transition: all 0.3s ease; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
// .kriosa-auto-badge a:hover { transform: translateY(-2px); border-color: #ff3366; box-shadow: 0 4px 15px rgba(255,51,102,0.2); }
// .kriosa-auto-badge-icon { width: ' . $s['icon-size'] . 'px; height: ' . $s['icon-size'] . 'px; background: #ff3366; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: ' . ($s['icon-size'] - 4) . 'px; color: white; }
// .kriosa-auto-badge-text { display: flex; flex-direction: column; }
// .kriosa-auto-badge-title { font-size: ' . $s['font-size'] . 'px; font-weight: 600; color: #ffffff; line-height: 1.2; }
// .kriosa-auto-badge-subtitle { font-size: ' . ($s['font-size'] - 2) . 'px; color: #8b949e; }
// @keyframes kriosaFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
// @media (max-width: 768px) { .kriosa-auto-badge { transform: scale(0.9); } }
// </style>
// <div class="kriosa-auto-badge">
//     <a href="https://kriosa.com" target="_blank" rel="noopener noreferrer">
//         <div class="kriosa-auto-badge-icon"><i class="kriosa-icon">🛡️</i></div>
//         <div class="kriosa-auto-badge-text">
//             <span class="kriosa-auto-badge-title">Protected by Kriosa</span>
//             <span class="kriosa-auto-badge-subtitle">AI Security</span>
//         </div>
//     </a>
// </div>';
//     }

    private function getBadgeHtml($position, $size): string
    {
        return '
<!-- Protected by Kriosa -->
<style>
    #kriosa-security-badge {
        position: fixed; 
        bottom: 16px; 
        right: 16px; 
        z-index: 99999; 
        font-size: 11px; 
        font-weight: 600; 
        letter-spacing: 0.3px;
        color: #ffffff; 
        text-decoration: none; 
        font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
        background: #064E3B; 
        padding: 6px 14px; 
        border-radius: 20px; 
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    #kriosa-security-badge:hover {
        background: #047857;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        color: #ffffff;
        text-decoration: none;
    }
</style>
<a id="kriosa-security-badge" href="https://kriosa.com" target="_blank" rel="noopener">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
    </svg>
    <span>Protected by Kriosa</span>
</a>
<!-- End Kriosa Badge -->';
    }
}

// ==================== GLOBAL HELPER FUNCTIONS ====================

function kriosa_protect(string $apiKey): bool { return Kriosa::quick($apiKey); }

function kriosa_check(string $apiKey): array {
    static $instance = null;
    if ($instance === null) $instance = new Kriosa($apiKey);
    return $instance->check();
}

// ==================== AUTO-EXECUTION ====================
if (defined('KRIOSA_API_KEY') && constant('KRIOSA_API_KEY') && !defined('KRIOSA_SKIP_AUTO')) {
    if (!kriosa_protect(constant('KRIOSA_API_KEY'))) {
        http_response_code(403);
        die('Request blocked by Kriosa Security');
    }
}