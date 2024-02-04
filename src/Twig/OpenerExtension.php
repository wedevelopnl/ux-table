<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use WeDevelop\UXTable\Security\Opener;
use WeDevelop\UXTable\Security\OpenerSigner;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

#[AsTaggedItem('twig.extension')]
class OpenerExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly OpenerSigner $openerSigner
    ) {
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_table_generate_opener', [$this, 'generateOpener']),
            new TwigFunction('ux_table_retrieve_opener', [$this, 'retrieveOpenerRaw']),
            new TwigFunction('ux_table_retrieve_opener_url', [$this, 'retrieveOpenerUrl']),
            new TwigFunction('ux_table_retrieve_opener_source', [$this, 'retrieveOpenerSource']),
        ];
    }

    public function generateOpener(string $source): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $openerUrl = $request->getRequestUri();

        return Opener::generate($openerUrl, $source, $this->openerSigner)->toBase64();
    }

    public function retrieveOpenerRaw(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request->query->get('_opener');
    }

    public function retrieveOpenerUrl(): ?string
    {
        $opener = $this->retrieveOpener();

        if (!$opener) {
            return null;
        }

        if (!$opener->isValid($this->openerSigner)) {
            return null;
        }

        return $opener->getUrl();
    }

    public function retrieveOpenerSource(): ?string
    {
        return $this->retrieveOpener()?->getSource();
    }

    private function retrieveOpener(): ?Opener
    {
        $openerRaw = $this->retrieveOpenerRaw();

        if (!$openerRaw) {
            return null;
        }

        return Opener::fromBase64($openerRaw);
    }
}
