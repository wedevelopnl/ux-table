<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DoctrineORMProvider implements DataProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatorInterface $paginator
    ) {
    }

    public function search(
        array $filters = [],
        array $sort = [],
        int $page = 1,
        int $pageSize = 50,
        array $options = []
    ): PaginationInterface {
        $class = $options['data_class'];

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('e');
        $qb->from($class, 'e');

        foreach ($filters as $field => $value) {
            if(is_object($value)){
                $qb->andWhere($qb->expr()->eq('e.' . $field, ':' . $field));
                $qb->setParameter($field, $value);
                continue;
            }
            $qb->andWhere($qb->expr()->like('e.' . $field, ':' . $field));
            $qb->setParameter($field, "%$value%");
        }

        if (is_callable($options['hydrator'] ?? null)) {
            $arrayResult = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
            $results = array_map($options['hydrator'], $arrayResult);
        } else {
            $results = $qb->getQuery();
        }

        return $this->paginator->paginate(
            $results,
            $page,
            $pageSize,
            [PaginatorInterface::PAGE_OUT_OF_RANGE => PaginatorInterface::PAGE_OUT_OF_RANGE_FIX]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('data_class')->allowedTypes('string')->required()
            ->define('hydrator')->allowedTypes('callable')
        ;
    }
}
