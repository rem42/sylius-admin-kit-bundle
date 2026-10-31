<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\ActiveFilters;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Grid\Definition\Filter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Turns the criterion of one grid filter, as sent in the query string, into the text shown to the user.
 *
 * Knows the criteria shapes of the Sylius filter types; any other type shows its scalar values as they are.
 */
final class CriterionFormatter
{
    private const array STRING_TYPES_WITHOUT_VALUE = ['empty', 'not_empty'];

    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ?ManagerRegistry $doctrine = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
    ) {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param mixed $criterion one value of a multiple criterion, or the whole criterion
     *
     * @return string|null null when the criterion does not filter anything
     */
    public function format(Filter $filter, mixed $criterion, string $locale): ?string
    {
        if (\is_array($criterion)) {
            return match ($filter->getType()) {
                'string' => $this->formatString($criterion),
                'date' => $this->formatDateRange($criterion, $locale),
                'money', 'numeric_range' => $this->formatNumericRange($filter, $criterion),
                default => $this->formatScalars($criterion),
            };
        }

        $value = self::scalar($criterion);

        if ('' === $value) {
            return null;
        }

        return match ($filter->getType()) {
            'boolean' => $this->formatYesNo('true' === $value),
            'exists' => $this->formatYesNo('1' === $value),
            'entity' => $this->formatEntity($filter, $value),
            'enum' => $this->formatEnum($filter, $value),
            'select' => $this->formatChoice($filter, $value),
            default => $value,
        };
    }

    /**
     * @param array<mixed> $criterion ["type" => ..., "value" => ...], "type" being absent when the filter sets it
     */
    private function formatString(array $criterion): ?string
    {
        $type = self::scalar($criterion['type'] ?? null);
        $value = self::scalar($criterion['value'] ?? null);

        if (\in_array($type, self::STRING_TYPES_WITHOUT_VALUE, true)) {
            return $this->translator->trans('sylius.ui.' . $type);
        }

        if ('' === $value) {
            return null;
        }

        return '' === $type ? $value : \sprintf('%s "%s"', $this->translator->trans('sylius.ui.' . $type), $value);
    }

    /**
     * @param array<mixed> $criterion ["from" => ["date" => ..., "time" => ...], "to" => [...]], either bound optional
     */
    private function formatDateRange(array $criterion, string $locale): ?string
    {
        return $this->formatRange(
            $this->formatDate($criterion['from'] ?? null, $locale),
            $this->formatDate($criterion['to'] ?? null, $locale),
        );
    }

    /**
     * @param array<mixed> $criterion ["greaterThan" => ..., "lessThan" => ..., "currency" => ...] (currency: money only)
     */
    private function formatNumericRange(Filter $filter, array $criterion): ?string
    {
        // The money filter excludes its bounds, the numeric range filter includes them unless told otherwise.
        $options = $filter->getOptions();
        $isNumericRange = 'numeric_range' === $filter->getType();
        $parts = [];

        if ('' !== $from = self::scalar($criterion['greaterThan'] ?? null)) {
            $parts[] = ($isNumericRange && (bool) ($options['inclusive_from'] ?? true) ? '≥ ' : '> ') . $from;
        }

        if ('' !== $to = self::scalar($criterion['lessThan'] ?? null)) {
            $parts[] = ($isNumericRange && (bool) ($options['inclusive_to'] ?? true) ? '≤ ' : '< ') . $to;
        }

        if ('' !== $currency = self::scalar($criterion['currency'] ?? null)) {
            $parts[] = $currency;
        }

        return [] === $parts ? null : implode(' ', $parts);
    }

    /**
     * Fallback of the filter types the kit does not know: their non-empty scalar values, comma separated.
     *
     * @param array<mixed> $criterion
     */
    private function formatScalars(array $criterion): ?string
    {
        $values = [];

        array_walk_recursive($criterion, static function (mixed $value) use (&$values): void {
            if ('' !== $value = self::scalar($value)) {
                $values[] = $value;
            }
        });

        return [] === $values ? null : implode(', ', $values);
    }

    private function formatRange(?string $from, ?string $to): ?string
    {
        return match (true) {
            null !== $from && null !== $to => $this->translator->trans('rem42_sylius_admin_kit.ui.active_filters.range.between', ['%from%' => $from, '%to%' => $to]),
            null !== $from => $this->translator->trans('rem42_sylius_admin_kit.ui.active_filters.range.from', ['%from%' => $from]),
            null !== $to => $this->translator->trans('rem42_sylius_admin_kit.ui.active_filters.range.to', ['%to%' => $to]),
            default => null,
        };
    }

    /**
     * @param mixed $bound ["date" => "2026-10-31", "time" => "13:37"] (date and time inputs) or a date string
     */
    private function formatDate(mixed $bound, string $locale): ?string
    {
        $date = self::scalar(\is_array($bound) ? $bound['date'] ?? null : $bound);
        $time = self::scalar(\is_array($bound) ? $bound['time'] ?? null : null);

        if ('' === $date) {
            return null;
        }

        try {
            $dateTime = new \DateTimeImmutable(trim($date . ' ' . $time));
        } catch (\Exception) {
            return $date;
        }

        if (!class_exists(\IntlDateFormatter::class)) {
            return $dateTime->format('' === $time ? 'Y-m-d' : 'Y-m-d H:i');
        }

        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, '' === $time ? \IntlDateFormatter::NONE : \IntlDateFormatter::SHORT);
        $formatted = $formatter->format($dateTime);

        return false === $formatted ? $date : $formatted;
    }

    private function formatYesNo(bool $yes): string
    {
        return $this->translator->trans($yes ? 'sylius.ui.yes_label' : 'sylius.ui.no_label');
    }

    /**
     * Label of the entity, as in the filter: "choice_label" form option (property path or callable), then __toString().
     */
    private function formatEntity(Filter $filter, string $id): string
    {
        $formOptions = $filter->getFormOptions();
        $class = $formOptions['class'] ?? null;

        if (null === $this->doctrine || !\is_string($class) || !class_exists($class)) {
            return $id;
        }

        $entity = $this->doctrine->getManagerForClass($class)?->find($class, $id);

        if (null === $entity) {
            return $id;
        }

        $choiceLabel = $formOptions['choice_label'] ?? null;

        return match (true) {
            \is_callable($choiceLabel) => self::scalar($choiceLabel($entity)),
            \is_string($choiceLabel) => self::scalar($this->propertyAccessor->getValue($entity, $choiceLabel)),
            $entity instanceof \Stringable => (string) $entity,
            default => $id,
        };
    }

    /**
     * Label of the case, as in the filter: "choice_label" form option (callable), then TranslatableInterface,
     * then the translated case name (the default of the Symfony EnumType).
     */
    private function formatEnum(Filter $filter, string $value): string
    {
        $formOptions = $filter->getFormOptions();
        $class = $formOptions['class'] ?? null;

        if (!\is_string($class) || !is_subclass_of($class, \BackedEnum::class)) {
            return $value;
        }

        if ('int' === (string) (new \ReflectionEnum($class))->getBackingType()) {
            $int = filter_var($value, \FILTER_VALIDATE_INT);
            $case = false === $int ? null : $class::tryFrom($int);
        } else {
            $case = $class::tryFrom($value);
        }

        if (null === $case) {
            return $value;
        }

        $choiceLabel = $formOptions['choice_label'] ?? null;

        return match (true) {
            \is_callable($choiceLabel) => $this->translator->trans(self::scalar($choiceLabel($case))),
            $case instanceof TranslatableInterface => $case->trans($this->translator),
            default => $this->translator->trans($case->name),
        };
    }

    /**
     * Label of the value in the "choices" form option (label => value) of a select filter.
     */
    private function formatChoice(Filter $filter, string $value): string
    {
        $choices = $filter->getFormOptions()['choices'] ?? [];

        foreach (\is_array($choices) ? $choices : [] as $label => $choice) {
            if (self::scalar($choice) === $value) {
                return $this->translator->trans((string) $label);
            }
        }

        return $value;
    }

    /**
     * The trimmed string of a scalar or Stringable, '' for anything else.
     */
    private static function scalar(mixed $value): string
    {
        return \is_scalar($value) || $value instanceof \Stringable ? trim((string) $value) : '';
    }
}
