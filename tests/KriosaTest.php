<?php

use PHPUnit\Framework\TestCase;

// Since Kriosa class is in global namespace (no namespace declaration in kriosa.php),
// we need to require the file first
require_once __DIR__ . '/../kriosa.php';

class KriosaTest extends TestCase
{
    private string $testApiKey = 'sk_test_12345678901234567890';
    
    public function testApiKeyValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Kriosa('invalid_key');
    }
    
    public function testValidApiKey(): void
    {
        $kriosa = new Kriosa($this->testApiKey);
        $this->assertInstanceOf(Kriosa::class, $kriosa);
    }
    
    public function testProtectReturnsBool(): void
    {
        $kriosa = new Kriosa($this->testApiKey);
        $result = $kriosa->protect();
        $this->assertIsBool($result);
    }
    
    public function testQuickProtect(): void
    {
        $result = kriosa_protect($this->testApiKey);
        $this->assertIsBool($result);
    }
    
    public function testApiKeyStartsWithSk(): void
    {
        $this->assertStringStartsWith('sk_', $this->testApiKey);
    }
    
    public function testApiKeyMinLength(): void
    {
        $this->assertGreaterThanOrEqual(20, strlen($this->testApiKey));
    }
}