<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\DataProvider;

use Knp\Component\Pager\Pagination\PaginationInterface;

interface DataProviderInterface
{
    public function search(
        array $filters = [],
        array $sort = [],
        int $page = 1,
        int $pageSize = 50,
        array $options = []
    ): PaginationInterface;
}