# Kriosa PHP SDK

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://packagist.org/packages/kriosa-ai/kriosa-php)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Version](https://img.shields.io/badge/version-3.0.0-green.svg)](https://github.com/kriosa-ai/kriosa-php)
Kriosa is an intelligent security middleware for PHP applications that acts as a smart layer between users and your application, detecting, filtering, and logging malicious activities before they reach your core application.
AI-powered Web Application Middleware built for  developers and businesses. One file. Zero dependencies. Instant protection.

## Features

- 🛡️ **AI-Powered Threat Detection** — Hybrid ML engine combining Random Forest and Neural Networks
- 🔍 **Attack Detection** — SQL Injection, XSS, Path Traversal, Bot Detection, Rate Limiting
- 🌍 **Built for Africa** — Optimized for African web infrastructure and threat landscape
- 🚀 **Framework Agnostic** — Works with Laravel, WordPress, Symfony, and vanilla PHP
- ⚡ **High Performance** — Minimal overhead with 5ms average response time
- 📊 **Explainable AI** — XAI dashboard shows exactly why a request was blocked
- 📝 **Real-time Analytics** — Live threat scoring, confidence levels, and attack metrics

## Installation

```bash
composer require kriosa-ai/kriosa-php
```

## Quick Start

```php
require 'vendor/autoload.php';

$kriosa = new Kriosa('sk_your_api_key');
$kriosa->protect();
```

## One-Line Protection

```php
kriosa_protect('sk_your_api_key');
```

## Laravel Integration

```php
// In app/Http/Middleware/KriosaMiddleware.php
$kriosa = new Kriosa(config('services.kriosa.key'));
$kriosa->protect();
```

## WordPress Integration

```php
// In wp-config.php or functions.php
require_once 'kriosa.php';
kriosa_protect('sk_your_api_key');
```

## Configuration

```php
$kriosa = new Kriosa('sk_your_api_key', [
    'timeout'      => 5,        // API timeout in seconds
    'debug'        => false,    // Enable debug logging
    'fail_closed'  => false,    // Block requests if API unreachable
    'show_badge'   => true,     // Show "Protected by Kriosa" badge
    'badge_position' => 'bottom-right' // Badge position
]);
```

## Get Your API Key

1. Sign up at [kriosa.com](https://kriosa.com)
2. Go to the API key section
3. Copy your API key

## Documentation

Full documentation at [kriosa.com/docs](https://kriosa.com/documentation.php)

## Support

- 📧 Email: support@kriosa.com
- 🌐 Website: [kriosa.com](https://kriosa.com)

## License

MIT License — see [LICENSE](LICENSE) for details.

---

*Built in Cameroon 🇨🇲 for Africa 🌍*