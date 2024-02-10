<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use WeDevelop\UXTable\Table\TableInterface;

#[AsTwigComponent(name: 'UXTable:Filter', template: '@WeDevelopUXTable/components/Filter.html.twig')]
final class Filter
{
    public TableInterface $table;

    public string $filter;

    public array $variables = [];
}
