<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[AsTaggedItem('twig.extension')]
class SortExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_table_sort_link', [$this, 'sortLink']),
            new TwigFunction('ux_table_sort_state', [$this, 'sortState']),
        ];
    }

    public function sortLink(string $uxTableName, string $field, string $defaultDirection = 'asc', bool $multiSort = true): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryParams = $request->query->all($uxTableName);

        $direction = $defaultDirection;
        if (($queryParams['sort'][$field] ?? null) === 'desc') {
            $direction = null;
        } elseif (($queryParams['sort'][$field] ?? null) === 'asc') {
            $direction = 'desc';
        }

        if ($multiSort) {
            $queryParams['sort'] = [];
        }

        $queryParams['sort'][$field] = $direction;

        return $this->urlGenerator->generate(
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params') + [$uxTableName => $queryParams] + $request->query->all()
        );
    }

    public function sortState(string $uxTableName, string $field): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryParams = $request->query->all($uxTableName);

        return $queryParams['sort'][$field] ?? 'none';
    }
}
