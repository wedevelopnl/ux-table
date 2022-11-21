<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Security;
final class Opener
{
    private string $url;
    private string $source;
    private string $signature;

    private function __construct(string $url, string $source, string $signature)
    {
        $this->url = $url;
        $this->source = $source;
        $this->signature = $signature;
    }

    public static function generate(string $url, string $source, OpenerSigner $openerSigner)
    {
        return new self($url, $source, $openerSigner->sign($url));
    }

    public static function fromBase64(string $base64): self
    {
        [$url, $source, $signature] = explode(':', base64_decode($base64));
        return new self($url, $source, $signature);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function isValid(OpenerSigner $openerSigner): bool
    {
        return $openerSigner->verify($this->url, $this->signature);
    }

    public function toBase64(): string
    {
        return base64_encode($this->url . ':' . $this->source . ':' . $this->signature);
    }
}
