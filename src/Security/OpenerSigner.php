<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Security;
final class OpenerSigner
{
    public function __construct(private string $secret)
    {
    }

    public function sign(string $openerUrl)
    {
        return hash_hmac('sha256', $openerUrl, $this->secret);
    }

    public function verify(string $openerUrl, string $signature): bool
    {
        return hash_equals($this->sign($openerUrl), $signature);
    }
}
