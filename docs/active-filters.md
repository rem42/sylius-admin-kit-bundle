# Active filters

The criteria that filter a grid, each with a link removing it, plus a link removing them all, visible even when the
filters panel is collapsed. With shadcn-admin-ui they sit inside its filters panel, with their number in its trigger.

```yaml
rem42_sylius_admin_kit:
    active_filters:
        enabled: true
```

Nothing to configure on the grids: every enabled filter is supported.

## What is listed

One item per criterion of the request (`criteria[<filter>]`) that filters the grid:

* Criteria of unknown or disabled filters are ignored, like the grid ignores them. So are empty ones (`criteria[search][value]=`).
* A multiple criterion (`criteria[author][]=1&criteria[author][]=2`) gives one item per value. Removing the last one removes the criterion.
* The label is the translated label of the filter (its name by default), or its humanized name when the label is
  hidden (`false`): `createdAt` gives `Created at`.

The value is written as the filter shows it:

| Filter type | Value |
|---|---|
| `string` | the value, preceded by the translated comparison when the request sets it (`Starts with "dun"`); `Empty` / `Not empty` alone |
| `boolean`, `exists` | `sylius.ui.yes_label` / `sylius.ui.no_label` |
| `enum` | `choice_label` form option (callable), else the `trans()` of an enum implementing `TranslatableInterface`, else the translated case name |
| `entity` | the entity loaded by id (requires `doctrine/persistence`), labelled with the `choice_label` form option (property path or callable), else `__toString()`, else the id |
| `select` | the translated label of the value in the `choices` form option |
| `date` | `From 1/2/26`, `Until 3/4/26` or `From 1/2/26 to 3/4/26`, dates (and times when given) formatted for the request locale with `ext-intl`, `Y-m-d` otherwise |
| `numeric_range`, `money` | `≥ 10 ≤ 20` (`>` and `<` for `money` and `inclusive_from/to: false`), followed by the currency for `money` |
| any other type | its non-empty scalar values, comma separated |

## Links

Each item links to the current page without its criterion (or without its value, for a multiple criterion). The
"clear all" link removes every criterion. Both keep the other query parameters (`limit`, `sorting`...) and drop
`page`.

## Rendering

Where it goes depends on the admin UI:

| Admin UI | Hook | Template | What |
|---|---|---|---|
| shadcn-admin-ui | `sylius_admin.common.index.content.grid.filters#trigger` | `active_filters/count.html.twig` | number of items, in the trigger of the filters panel |
| shadcn-admin-ui | `sylius_admin.common.index.content.grid.filters#summary` | `active_filters/summary.html.twig` | the items, between the trigger and the form |
| any other | `sylius_admin.common.index.content.grid`, priority 50 | `active_filters/bar.html.twig` | the items, in a bar above the filters panel (below the [quick filter](quick-filter.md) tabs, 100) |

With shadcn-admin-ui the kit also sets the configuration of its `filters` hookable: `open: false` (the panel stays
collapsed on load, the items show the criteria) and `indicator: false` (the count replaces the dot). Set them back on
the `filters` hookable of `sylius_admin.common.index.content.grid` in your configuration, it wins.

The hookables are all named `rem42_admin_kit_active_filters` (see [Configuration](configuration.md) to override or
disable them). The list carries `data-admin-kit="active-filters"`, each item `data-admin-kit-filter="<filter name>"`,
the count `data-admin-kit="active-filters-count"`. The templates call the Twig function
`rem42_admin_kit_active_filters(grid)`, which returns `null` when no criterion filters the grid, or:

```php
[
    'filters' => [
        ['name' => 'category', 'label' => 'Category', 'value' => 'Science-fiction', 'remove_url' => '...'],
        ['name' => 'author', 'label' => 'Author', 'value' => 'Frank Herbert', 'remove_url' => '...'],
    ],
    'clear_url' => '...',
]
```

* shadcn: bordered chips with an `x` link, count in a rounded pill.
* bootstrap: Tabler `.tags-list` of `.tag` with a `.btn-close` link.

To render the items somewhere else, e.g. inside your own copy of the filters panel, disable the hookable and call the
Twig function from your template.

Translations: `rem42_sylius_admin_kit.ui.active_filters.*` (`title`, `label`, `remove`, `clear`, `range.between`,
`range.from`, `range.to`).
