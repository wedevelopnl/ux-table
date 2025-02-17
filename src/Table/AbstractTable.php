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
use Symfony\Component\OptionsResolver\OptionsResolver;
use WeDevelop\UXTable\DataProvider\DataProviderInterface;
use WeDevelop\UXTable\DataProvider\DoctrineORMProvider;
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
        /** @var DataProviderInterface $dataProvider */
        $dataProvider = $this->dataProviders->get($this->getDataProvider());

        $options = $this->resolveOptions($options, $dataProvider);

        // Make sure default values are set even if form isn't submitted
        if (!$request->query->has($this->getName())) {
            $request->query->set($this->getName(), null);
        }

        $this->form = $this->buildForm($options);
        $this->form->handleRequest($request);

        $this->pagination = $dataProvider->search(
            filters: $additionalFilters + $this->getFilters(),
            sort: $this->getSort($request, $options['sortable_fields']),
            page: $this->getPage($request),
            pageSize: $this->getPageSize(),
            options: $options,
        );

        $this->pagination->setPaginatorOptions([PaginatorInterface::PAGE_PARAMETER_NAME => sprintf('%s[page]', $this->getName())]);
    }

    public function getForm(): FormInterface
    {
        if (!isset($this->form)) {
            throw new \LogicException(sprintf('Execute %s::process() before retrieving the form', $this::class));
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

    abstract protected function buildFilterForm(FormBuilderInterface $builder, array $options): void;

    protected function getDataProvider(): string
    {
        return DoctrineORMProvider::class;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
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

    /** @param array{pageSize?: int, pageSizes?: array<int>} $options */
    private function buildForm(array $options): FormInterface
    {
        $builder = $this->buildBaseForm();

        $this->addPageSize($builder, $options);

        $filterFormBuilder = $builder->create('filter', FormType::class, ['label' => false]);

        $this->buildFilterForm($filterFormBuilder, $options);
        $builder->add($filterFormBuilder);

        return $builder->getForm();
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

    /** @param array{pageSize?: int, pageSizes?: array<int>} $options */
    private function addPageSize(FormBuilderInterface $builder, array $options): void
    {
        $pageSizeBuilder = new PageSizeBuilder($options);
        $pageSizeBuilder->build($builder, [
            'attr' => ['data-action' => $this->stimulusSearch('change')],
        ]);
    }

    private function getFilters(): array
    {
        return $this->getForm()->getData()['filter'] ?? [];
    }

    private function getSort(Request $request, array $sortableFields): array
    {
        $sort = $request->query->all($this->getName())['sort'] ?? [];

        return array_filter($sort, fn($key) => in_array($key, $sortableFields), ARRAY_FILTER_USE_KEY);
    }

    private function getPage(Request $request): int
    {
        return (int)($request->query->all($this->getName())['page'] ?? 1);
    }

    private function getPageSize(): int
    {
        return PageSizeBuilder::getCalculatedPageSize($this->getForm());
    }

    private function resolveOptions(array $options, DataProviderInterface $dataProvider): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->define('sortable_fields')->allowedTypes('array')->default([])->required()
        ;

        $dataProvider->configureOptions($resolver);
        PageSizeBuilder::addFormOptions($resolver);
        $this->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
