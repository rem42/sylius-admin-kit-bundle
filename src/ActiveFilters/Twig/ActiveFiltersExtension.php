<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\ActiveFilters\Twig;

use Rem42\SyliusAdminKit\ActiveFilters\CriterionFormatter;
use Rem42\SyliusAdminKit\Grid\GridPage;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Lists the criteria of the current request that filter the grid, each with a link removing it.
 *
 * A multiple criterion (criteria[author][]=1&criteria[author][]=2) gives one item per value. Criteria of unknown or
 * disabled filters are ignored: the grid ignores them too.
 */
final class ActiveFiltersExtension extends AbstractExtension
{
    public function __construct(
        private readonly CriterionFormatter $formatter,
        private readonly TranslatorInterface $translator,
        private readonly GridPage $page,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rem42_admin_kit_active_filters', $this->getActiveFilters(...)),
        ];
    }

    /**
     * @return array{filters: list<array{name: string, label: string, value: string, remove_url: string}>, clear_url: string}|null null when no criterion filters the grid
     */
    public function getActiveFilters(?Grid $grid): ?array
    {
        $criteria = $this->page->criteria();

        if (null === $grid || null === $criteria) {
            return null;
        }

        $items = [];

        foreach ($grid->getEnabledFilters() as $filter) {
            $name = $filter->getName();

            if (!\array_key_exists($name, $criteria)) {
                continue;
            }

            $criterion = $criteria[$name];
            $multiple = self::isMultiple($criterion);

            foreach ($multiple ? $criterion : [$criterion] as $index => $value) {
                $text = $this->formatter->format($filter, $value, $this->translator->getLocale());

                if (null === $text) {
                    continue;
                }

                $items[] = [
                    'name' => $name,
                    'label' => $this->label($filter),
                    'value' => $text,
                    'remove_url' => $this->page->url(self::without($criteria, $name, $multiple ? $index : null)),
                ];
            }
        }

        if ([] === $items) {
            return null;
        }

        return [
            'filters' => $items,
            'clear_url' => $this->page->url([]),
        ];
    }

    /**
     * The translated label of the filter (its name by default), or its humanized name when the label is hidden
     * (false): "createdAt" gives "Created at", like the label of a form field.
     */
    private function label(Filter $filter): string
    {
        $label = $filter->getLabel();

        if (\is_string($label) && '' !== $label) {
            return $this->translator->trans($label);
        }

        return ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $filter->getName()))));
    }

    /**
     * Whether the criterion is a list of values (multiple entity, enum or select filter), each removable on its own.
     *
     * @phpstan-assert-if-true list<mixed> $criterion
     */
    private static function isMultiple(mixed $criterion): bool
    {
        return \is_array($criterion) && [] !== $criterion && array_is_list($criterion)
            && [] === array_filter($criterion, static fn (mixed $value): bool => !\is_scalar($value));
    }

    /**
     * The criteria without the filter, or without one value of its multiple criterion.
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<string, mixed>
     */
    private static function without(array $criteria, string $name, ?int $index): array
    {
        if (null !== $index && \is_array($criteria[$name])) {
            $values = $criteria[$name];
            unset($values[$index]);

            if ([] !== $values) {
                $criteria[$name] = array_values($values);

                return $criteria;
            }
        }

        unset($criteria[$name]);

        return $criteria;
    }
}
