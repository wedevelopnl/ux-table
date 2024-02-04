<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Twig\Component;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use WeDevelop\UXTable\Table\TableInterface;

#[AsTwigComponent(name: 'UXTable:Table', template: '@WeDevelopUXTable/components/Table.html.twig')]
final class Table
{
    public TableInterface $table;
    public string $id;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getPreservationForm()
    {
        $request = $this->requestStack->getCurrentRequest();
        $builder = $this->formFactory->createNamedBuilder('', options: ['csrf_protection' => false]);

        $this->buildPreservationForm($builder, $request->query->all(), null);

        return $builder->getForm()->createView();
    }

    public function buildPreservationForm(FormBuilderInterface $builder, array $params, ?string $parentKey)
    {
        foreach ($params as $key => $value) {
            if ($key === $this->table->getName() && $parentKey === null) {
                continue;
            }

            if (is_array($value)) {
                $nestedBuilder = $builder->create(
                    $key,
                    FormType::class,
                    ['label' => false, 'csrf_protection' => false]
                );
                $this->buildPreservationForm($nestedBuilder, $value, $key);
                $builder->add($nestedBuilder);
                continue;
            }

            $builder->add($key, HiddenType::class, ['data' => urldecode($value)]);
        }
    }
}
