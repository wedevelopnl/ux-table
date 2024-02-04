<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Table;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

interface TableInterface
{
    public function process(Request $request, array $additionalFilters = [], array $options = []): void;
    public function getForm(): FormInterface;
    public function getFormView(): FormView;
    public function getName(): string;
    public function getResults(): PaginationInterface;
}
