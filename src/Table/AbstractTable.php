<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Table;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use WeDevelop\UXTable\DataProvider\DataProviderInterface;
use WeDevelop\UXTable\Form\PageSizeBuilder;

abstract class AbstractTable implements TableInterface
{
    private ?FormInterface $form = null;
    private ?FormView $formView = null;
    private ?PaginationInterface $pagination = null;

    public function __construct(
        #[TaggedLocator(DataProviderInterface::class)]
        private readonly ServiceLocator $dataProviders,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    public function process(Request $request, array $additionalFilters = [], array $options = []): void
    {
        $this->getForm()->handleRequest($request);

        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->dataProviders->get($this->getDataProvider());

        $this->pagination = $dataProvider->search(
            filters: $additionalFilters + $this->getFilters(),
            sort: $this->getSort($request),
            page: $this->getPage($request),
            pageSize: $this->getPageSize(),
            options: $options + $this->getDefaultOptions(),
        );
        $this->pagination->setPaginatorOptions([PaginatorInterface::PAGE_PARAMETER_NAME => sprintf('%s[page]', $this->getName())]);
    }

    public function getForm(): FormInterface
    {
        if (!isset($this->form)) {
            $builder = $this->buildBaseForm();

            $this->addPageSize($builder);

            $filterFormBuilder = $builder->create('filter', FormType::class, ['label' => false]);

            $this->buildFilterForm($filterFormBuilder);
            $builder->add($filterFormBuilder);

            $this->form = $builder->getForm();
        }

        return $this->form;
    }

    public function getFormView(): FormView
    {
        if (!isset($this->formView)) {
            $this->formView = $this->getForm()->createView();
        }

        return $this->formView;
    }

    public function getResults(): PaginationInterface
    {
        if (!isset($this->pagination)) {
            throw new \LogicException('First execute the ->process() method to generate results');
        }

        return $this->pagination;
    }

    abstract public function getName(): string;
    abstract protected function buildFilterForm(FormBuilderInterface $builder): void;
    /** @return class-string<DataProviderInterface> */
    abstract protected function getDataProvider(): string;
    abstract protected function getSortableFields(): array;

    protected function getDefaultOptions(): array
    {
        return [];
    }

    protected function stimulusSearch(?string $event = 'input'): string
    {
        return $event . '->wedevelopnl--ux-table--ux-table#search';
    }

    protected function stimulusSearchAttributes(?string $event = 'input'): array
    {
        return [
            'data-action' => $this->stimulusSearch($event),
            'data-turbo-permanent' => 'true',
        ];
    }

    private function buildBaseForm(): FormBuilderInterface
    {
        $builder = $this->formFactory->createNamedBuilder($this->getName());

        $builder
            ->setMethod('GET')
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $filter = array_filter($data['filter'] ?? [], fn($v) => $v !== null);
                $data['filter'] = $filter;
                $event->setData($data);
            });

        return $builder;
    }

    private function addPageSize(FormBuilderInterface $builder): void
    {
        $pageSizeBuilder = new PageSizeBuilder($builder);
        $pageSizeBuilder->build($builder, [
            'attr' => ['data-action' => $this->stimulusSearch('change')],
        ]);
    }

    private function getFilters(): array
    {
        return $this->getForm()->getData()['filter'] ?? [];
    }

    private function getSort(Request $request): array
    {
        $sort = $request->query->all($this->getName())['sort'] ?? [];

        return array_filter($sort, fn($key) => in_array($key, $this->getSortableFields()), ARRAY_FILTER_USE_KEY);
    }

    private function getPage(Request $request): int
    {
        return (int)($request->query->all($this->getName())['page'] ?? 1);
    }

    private function getPageSize(): int
    {
        return PageSizeBuilder::getCalculatedPageSize($this->getForm());
    }
}
