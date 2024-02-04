<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'UXTable:Table', template: '@WeDevelopUXTable/components/Table.html.twig')]
final class Table
{
    public string $id;
}
