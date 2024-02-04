<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'UXTable:SortLink', template: '@WeDevelopUXTable/components/SortLink.html.twig')]
final class SortLink
{
    public string $title;

    public string $field;
}
