<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\QuickFilter\Twig;

use Psr\Container\ContainerInterface;
use Rem42\SyliusAdminKit\QuickFilter\QuickFilterProviderInterface;
use Rem42\SyliusAdminKit\QuickFilter\Tone;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Turns the enum filter of a grid that has a "quick_filter" option into tabs above the grid.
 *
 *     EnumFilter::create('state', StateEnum::class)
 *         ->addOption('quick_filter', [
 *             'cases' => [StateEnum::New, StateEnum::Shipped], // optional, every case by default
 *             'provider' => StateQuickFilterProvider::class,   // optional, see QuickFilterProviderInterface
 *             'all_label' => 'app.ui.all_orders',              // optional, "sylius.ui.all" by default
 *         ]),
 */
final class QuickFilterExtension extends AbstractExtension
{
    public function __construct(
        private readonly ContainerInterface $providers,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rem42_admin_kit_quick_filter', $this->getQuickFilter(...)),
        ];
    }

    /**
     * @return array{criterion: string, label: string, tabs: list<array{label: string, url: string, active: bool, count: ?int, tone: ?string}>}|null null when there is nothing to show
     */
    public function getQuickFilter(?Grid $grid): ?array
    {
        $request = $this->requestStack->getCurrentRequest();
        $filter = $this->findFilter($grid);

        $route = $request?->attributes->get('_route');

        if (null === $request || !\is_string($route) || null === $filter) {
            return null;
        }

        /** @var array{cases?: list<\BackedEnum|int|string>, provider?: string, all_label?: string} $options */
        $options = $filter->getOptions()['quick_filter'];

        $enumClass = $filter->getFormOptions()['class'] ?? null;

        if (!\is_string($enumClass) || !is_subclass_of($enumClass, \BackedEnum::class)) {
            throw new \LogicException(\sprintf('The enum filter "%s" has a "quick_filter" option but no backed enum "class" form option.', $filter->getName()));
        }

        $provider = isset($options['provider']) ? $this->provider($options['provider']) : null;
        $counts = $provider?->getCounts();

        $criterion = $filter->getName();

        $rawCriteria = $request->query->all()['criteria'] ?? [];

        /** @var array<string, mixed> $criteria */
        $criteria = \is_array($rawCriteria) ? $rawCriteria : [];
        $rawCurrent = $criteria[$criterion] ?? null;
        // A non-scalar criterion (criteria[x][]=...) still filters the grid, but matches no tab: nothing is active.
        $nonScalar = null !== $rawCurrent && !\is_scalar($rawCurrent);
        $current = \is_scalar($rawCurrent) ? (string) $rawCurrent : null;

        /** @var array<string, mixed> $routeParams */
        $routeParams = $request->attributes->get('_route_params', []);

        $base = $routeParams + array_diff_key($request->query->all(), ['page' => true, 'criteria' => true]);
        $others = array_diff_key($criteria, [$criterion => true]);

        $url = fn (?string $value): string => $this->urlGenerator->generate($route, $base + ([] !== $others || null !== $value
            ? ['criteria' => $others + (null !== $value ? [$criterion => $value] : [])]
            : []));

        $tabs = [[
            'label' => $this->translator->trans($options['all_label'] ?? 'sylius.ui.all'),
            'url' => $url(null),
            'active' => !$nonScalar && (null === $current || '' === $current),
            'count' => null === $counts ? null : array_sum($counts),
            'tone' => null,
        ]];

        foreach ($options['cases'] ?? $enumClass::cases() as $case) {
            $case = $case instanceof \BackedEnum ? $case : $enumClass::from($case);
            $value = (string) $case->value;
            $active = !$nonScalar && $value === $current;
            $count = null === $counts ? null : $counts[$case->value] ?? 0;

            if (0 === $count && !$active) {
                continue;
            }

            $tabs[] = [
                'label' => $case instanceof TranslatableInterface ? $case->trans($this->translator) : $this->translator->trans($value),
                'url' => $url($value),
                'active' => $active,
                'count' => $count,
                'tone' => Tone::normalize($provider?->getColor($case)),
            ];
        }

        $label = $filter->getLabel();

        return [
            'criterion' => $criterion,
            'label' => \is_string($label) && '' !== $label ? $this->translator->trans($label) : $criterion,
            'tabs' => $tabs,
        ];
    }

    private function provider(string $id): QuickFilterProviderInterface
    {
        $provider = $this->providers->has($id) ? $this->providers->get($id) : null;

        if (!$provider instanceof QuickFilterProviderInterface) {
            throw new \LogicException(\sprintf('Quick filter provider "%s" must be a service implementing %s.', $id, QuickFilterProviderInterface::class));
        }

        return $provider;
    }

    private function findFilter(?Grid $grid): ?Filter
    {
        foreach ($grid?->getEnabledFilters() ?? [] as $filter) {
            if ('enum' === $filter->getType() && isset($filter->getOptions()['quick_filter'])) {
                return $filter;
            }
        }

        return null;
    }
}
