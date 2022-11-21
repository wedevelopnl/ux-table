<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
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

        $builder->setMethod('GET');
        $builder->add('search', SearchType::class, ['required' => false, 'attr' => ['data-action' => 'input->wedevelopnl--ux-table--ux-table#search', 'data-turbo-permanent' => null]]);

        unset($queryParams['search']);

        $this->buildSubForm($builder, $options, $queryParams, null);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'sort_whitelist' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    private function buildSubForm(FormBuilderInterface $builder, array $options, array $params, ?string $parentKey)
    {
        foreach ($params as $key => $value) {
            // Prevent abuse with nested array in sort or filter
            if ($parentKey === 'sort' && is_array($value)) {
                continue;
            }

            if (is_array($value)) {
                $nestedBuilder = $builder->create($key, FormType::class, ['label' => false, 'csrf_protection' => false]);
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
}
