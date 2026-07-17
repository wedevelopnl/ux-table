<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Tests\Security;

use PHPUnit\Framework\TestCase;
use WeDevelop\UXTable\Security\OpenerSigner;

class OpenerSignerTest extends TestCase
{
    private const SECRET = "secret";
    private const TEST_URI = '/uri/path';

    public function testSign(): void
    {
        $openerSigner = new OpenerSigner(self::SECRET);
        $expectedSignature = hash_hmac('sha256', self::TEST_URI, self::SECRET);
        $this->assertSame($expectedSignature ,$openerSigner->sign(self::TEST_URI), "opener signature mismatch");
    }

    public function testVerify(): void
    {
        $openerSigner = new OpenerSigner(self::SECRET);
        $expectedSignature = hash_hmac('sha256', self::TEST_URI, self::SECRET);

        $this->assertTrue($openerSigner->verify(self::TEST_URI,  $expectedSignature), "signatures should match");
        $this->assertFalse($openerSigner->verify(self::TEST_URI,  "wrong signature"), "signatures should mismatch");

    }
}