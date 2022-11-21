<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SortExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
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

    public function sortLink(string $field): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryParams = $request->query->all();
        $direction = 'asc';
        if (($queryParams['sort'][$field] ?? null) === 'desc') {
            $direction = null;
        } elseif (($queryParams['sort'][$field] ?? null) === 'asc') {
            $direction = 'desc';
        }

        $queryParams['sort'][$field] = $direction;

        return $this->urlGenerator->generate($request->attributes->get('_route'), $queryParams);
    }

    public function sortState(string $field): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryParams = $request->query->all();

        return $queryParams['sort'][$field] ?? 'none';
    }
}
