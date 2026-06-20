# Changelog

## [1.0.0] - 2026-04-25

### Added
- Initial release
- PHP 7.4+ support
- API key validation
- Auto-protection with `KRIOSA_API_KEY` constant
- Global helper function `kriosa_protect()`
- Cache support for repeated requests
- cURL with timeout and SSL verification
- Fallback mode when API unreachable

### Security
- API key format validation
- IP address validation
- Request data sanitization