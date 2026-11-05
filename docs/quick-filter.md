# Quick filter

Tabs above a grid, one per value of an `enum` filter, plus an "All" tab. Counts and coloured dots are optional.

```yaml
rem42_sylius_admin_kit:
    quick_filter:
        enabled: true
```

## Usage

Add the `quick_filter` option to an `enum` filter of your grid:

```php
EnumFilter::create('category', BookCategory::class)
    ->addOption('quick_filter', [
        'cases' => [BookCategory::Novel, BookCategory::Essay], // optional, every case by default
        'provider' => CategoryQuickFilterProvider::class,      // optional, service id
        'all_label' => 'app.ui.all_books',                     // optional, "sylius.ui.all" by default
    ]);
```

Only the first enabled `enum` filter that carries the option is rendered. Tab labels are translated; if the enum
implements `Symfony\Contracts\Translation\TranslatableInterface` its `trans()` is used.

Each tab links to the current page with `criteria[<filter>]` replaced (removed for "All"). Other query parameters
and other criteria are kept, `page` is dropped.

## Counts and colours: the provider

A provider is optional. Without one, tabs only show their label. To get counts and dots, implement
`Rem42\SyliusAdminKit\QuickFilter\QuickFilterProviderInterface`:

```php
use Rem42\SyliusAdminKit\QuickFilter\QuickFilterProviderInterface;
use Rem42\SyliusAdminKit\QuickFilter\Tone;

final class CategoryQuickFilterProvider implements QuickFilterProviderInterface
{
    public function getCounts(): ?array
    {
        // number of resources per enum value, for every value; null hides the counts
        return [BookCategory::Novel->value => 12, BookCategory::Essay->value => 3];
    }

    public function getColor(\BackedEnum $case): ?string
    {
        return match ($case) {
            BookCategory::Novel => Tone::BLUE, // 'blue'
            default => null,                   // no dot
        };
    }
}
```

The provider must be a service implementing the interface, referenced by its service id in the `provider` option.
With autoconfiguration it is tagged `rem42_sylius_admin_kit.quick_filter_provider` automatically. Anything else
throws a `LogicException`. So does a filter with a `quick_filter` option but no backed enum `class` form option.

Behaviour to know:

* The "All" tab keeps its translated label; its `count` is the sum of all the counts returned by the provider (`null`
  without a provider or when it returns `null`), rendered by the themes like any other count.
* When counts are available, tabs at 0 are hidden, unless they are the active tab.
* An unknown tone is ignored (no dot). Tones are the constants of `Tone` (`Tone::BLUE`, ..., listed in `Tone::ALL`): `blue`, `azure`, `indigo`, `purple`,
  `pink`, `red`, `orange`, `yellow`, `lime`, `green`, `teal`, `cyan`, `gray`, `grey`, `brown`, `success`, `danger`, `warning`, `info`,
  `primary`, `secondary`, `muted`, `dark`, `body`, `black`.
* If the criterion of the request is an array (`criteria[category][]=...`) or an unknown value, the grid is
  filtered as usual but no tab is active.

## Rendering

The kit hooks `rem42_admin_kit_quick_filters` into `sylius_admin.common.index.content.grid` with priority 100
(see [Configuration](configuration.md) to override or disable it). The root element carries
`data-admin-kit="quick-filter"`. The template calls the Twig function `rem42_admin_kit_quick_filter(grid)`, which
returns `null` or:

```php
[
    'criterion' => 'category',
    'label' => 'Category',        // translated, used as aria-label
    'tabs' => [                   // the first tab is "All"
        ['label' => 'All', 'url' => '...', 'active' => false, 'count' => 15, 'tone' => null],
        ['label' => 'Novel', 'url' => '...', 'active' => true, 'count' => 12, 'tone' => 'blue'],
    ],
]
```

* shadcn: underlined tabs, dot colored by the `tone` macro of the shadcn-admin-ui palette, count in a rounded pill.
* bootstrap: Tabler underlined `ul.nav.nav-bordered` (scrolls sideways on narrow screens), `span.status-dot.status-{tone}` (`gray` and `grey` use `secondary`, `brown` uses `orange`, `body` and `black` use `dark`), count in `span.badge`.
