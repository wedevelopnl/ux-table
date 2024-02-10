<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use WeDevelop\UXTable\Table\TableInterface;

#[AsTwigComponent(name: 'UXTable:PageSize', template: '@WeDevelopUXTable/components/PageSize.html.twig')]
final class PageSize
{
    public TableInterface $table;

    public array $variables = [];
}
