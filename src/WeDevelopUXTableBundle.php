<?php

declare(strict_types=1);

namespace WeDevelop\UXTable;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class WeDevelopUXTableBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
