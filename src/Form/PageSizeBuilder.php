<?php

declare(strict_types=1);

namespace WeDevelop\UXTable\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PageSizeBuilder
{
    public const DEFAULT_PAGE_SIZES = [10, 20, 50, 100];
    public const DEFAULT_PAGE_SIZE = 50;
    public const DEFAULT_FIELD_NAME = 'pageSize';

    public function __construct(
        private readonly FormBuilderInterface $builder,
        private readonly string $fieldName = self::DEFAULT_FIELD_NAME,
    ) {}

    public static function getCalculatedPageSize(FormInterface $form, string $fieldName = self::DEFAULT_FIELD_NAME): int
    {
        if (!is_a($form->getConfig()->getType()->getInnerType(), UXTableFormType::class, false)) {
            throw new \RuntimeException(sprintf('Form type must extend from "%s" to calculate page size.', self::class));
        }

        $choiceField = $form->get($fieldName);
        if (!is_a($choiceField->getConfig()->getType()->getInnerType(), ChoiceType::class, false)) {
            throw new \RuntimeException(sprintf('Form field "%s" must be of type "%s" to calculate page size.', $fieldName, ChoiceType::class));
        }

        // From the query string if it's a valid choice, then the form options
        // if it's a valid choice, otherwise the first choice available.
        // If there are no valid options to choose from default to the default
        // page size defined in this class.
        return (int)($choiceField->getNormData()
            ?? ($choiceField->getConfig()->getData() ?: null)
            ?? ($choiceField->getConfig()->getEmptyData() ?: null)
            ?? array_key_first($choiceField->getConfig()->getOption('choices'))
            ?? throw new \RuntimeException(sprintf('There must be at least one page size configured for field "%s".', $fieldName)));
    }

    public static function addFormOptions(OptionsResolver $resolver): OptionsResolver
    {
        $resolver->define('pageSize')->allowedTypes('int')->default(PageSizeBuilder::DEFAULT_PAGE_SIZE);
        $resolver->define('pageSizes')->allowedTypes('int[]')->default(PageSizeBuilder::DEFAULT_PAGE_SIZES);

        return $resolver;
    }

    public function build(
        FormBuilderInterface $builder,
        array $additionalOptions = [],
    ): FormBuilderInterface {
        // Default raw value coming from query string (expects to be string).
        $default = (string)$this->getDefaultPageSize();
        $builder->add($this->fieldName, ChoiceType::class, array_merge($additionalOptions, [
            'required' => false,
            'placeholder' => false,
            'choices' => $this->getChoices(),
            'data' => $default,
            'empty_data' => $default,
        ]));

        $this->addEventListener($builder);

        return $builder;
    }

    /** @return array<int, int> */
    private function getChoices(): array
    {
        $choices = array_values($this->validatePageSizes(
            $this->builder->getOptions()['pageSizes'] ?? null,
        ) ?? self::DEFAULT_PAGE_SIZES);

        return array_combine($choices, $choices);
    }

    private function getDefaultPageSize(): int
    {
        $choices = $this->getChoices();
        $defaults = [$this->builder->getOptions()['pageSize'] ?? null, self::DEFAULT_PAGE_SIZE, array_key_first($choices)];
        foreach ($defaults as $default) {
            if (array_key_exists($default, $choices)) {
                return $default;
            }
        }

        // Unreachable statement (see getChoices/validatePageSizes). Keep PHPStan happy.
        return 0;
    }

    private function addEventListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $pageSize = $data[$this->fieldName] ?? null;
            if (null !== $pageSize && '' !== $pageSize && !array_key_exists((int) $pageSize, $this->getChoices())) {
                $data[$this->fieldName] = $this->getDefaultPageSize();
                $event->setData($data);
            }
        });
    }

    /**
     * @return array<int>|null
     */
    private function validatePageSizes(mixed $choices): ?array
    {
        return is_array($choices) && count($choices) > 0 && array_reduce(
            $choices,
            fn($carry, $item) => $carry && is_int($item),
            true,
        ) ? $choices : null;
    }
}
