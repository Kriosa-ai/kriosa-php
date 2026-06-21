<?php

use PHPUnit\Framework\TestCase;

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
        $kriosa = new Kriosa($this->testApiKey, [
            'fail_closed' => false
        ]);
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

    public function testCheckReturnsArray(): void
    {
        $kriosa = new Kriosa($this->testApiKey);
        $result = $kriosa->check();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
    }

    public function testInvalidApiKeyTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Kriosa('sk_short');
    }
}