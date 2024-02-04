<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class UXTableFormType extends AbstractType
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryParams = $request->query->all();

        $this->buildSubForm($builder, $options, $queryParams, null);

        $builder
            ->setMethod('GET')
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $filter = array_filter($data['filter'] ?? [], fn($v) => $v !== null);
                $data['filter'] = $filter;
                $event->setData($data);
            });
    }

    public function createFilterForm(FormBuilderInterface $builder, array $options = ['label' => false])
    {
        return $builder->create('filter', FormType::class, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        PageSizeBuilder::addFormOptions($resolver);

        $resolver->setDefaults([
            'csrf_protection' => false,
            'sort_whitelist' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    protected function addSearch(FormBuilderInterface $builder): self
    {
        $builder->add(
            'search',
            SearchType::class,
            ['required' => false, 'attr' => ['data-action' => $this->stimulusSearch(), 'data-turbo-permanent' => null]]
        );

        return $this;
    }

    protected function addPageSize(FormBuilderInterface $builder): self
    {
        $pageSizeBuilder = new PageSizeBuilder($builder);
        $pageSizeBuilder->build($builder, [
            'attr' => ['data-action' => $this->stimulusSearch('change')],
        ]);

        return $this;
    }

    private function buildSubForm(FormBuilderInterface $builder, array $options, array $params, ?string $parentKey)
    {
        foreach ($params as $key => $value) {
            // Prevent abuse with nested array in sort or filter
            if ($parentKey === 'sort' && is_array($value)) {
                continue;
            }

            if ($builder->has($key)) {
                continue;
            }

            if (is_array($value)) {
                $nestedBuilder = $builder->create(
                    $key,
                    FormType::class,
                    ['label' => false, 'csrf_protection' => false]
                );
                $this->buildSubForm($nestedBuilder, $options, $value, $key);
                $builder->add($nestedBuilder);
                continue;
            }

            if ($parentKey === 'sort' && !in_array($key, $options['sort_whitelist'])) {
                continue; // Ignore sort field not in whitelist
            }

            $builder->add($key, HiddenType::class, ['data' => urldecode($value)]);
        }
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
}
