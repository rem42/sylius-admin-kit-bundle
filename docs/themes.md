# Themes

A theme is a templates directory. The kit hooks `{prefix}/{feature}/{file}.html.twig` into the admin UI.

## Built-in themes

| Theme | Prefix | Selected when |
|---|---|---|
| `shadcn` | `@Rem42SyliusAdminKit/shadcn` | `Rem42ShadcnAdminUiBundle` is loaded (auto) |
| `bootstrap` | `@Rem42SyliusAdminKit/bootstrap` | otherwise (auto) |

Force one with `rem42_sylius_admin_kit.theme`. Both admin UIs use the hook names of `sylius/admin-ui`, so the
features hook into the same places.

## Custom theme

Declare it, and select it:

```yaml
rem42_sylius_admin_kit:
    theme: acme
    themes:
        acme: '@AcmeAdmin/kit'   # any Twig namespace or path holding the files below
```

A custom theme cannot be named `shadcn` or `bootstrap`: those names are reserved and fail at container build with an
`InvalidArgumentException`.

A `theme` that is neither `shadcn`, `bootstrap` nor a key of `themes` fails at container build, listing the
possible values.

## File contract

A theme provides the files of the features it supports. A missing file raises the standard Twig error, only for a feature you enabled.

| File | Variables received |
|---|---|
| `quick_filter/tabs.html.twig` | `hookable_metadata`: context of the index page (`hookable_metadata.context.resources.definition` is the grid) |
| `active_filters/count.html.twig`, `active_filters/summary.html.twig` | used with shadcn-admin-ui; `hookable_metadata`: context of the index page (`hookable_metadata.context.resources.definition` is the grid) |
| `active_filters/bar.html.twig` | used with any other admin UI; same variables |
| `environment/strip.html.twig` | none, calls `rem42_admin_kit_environment()` |
| `environment/badge.html.twig` | `hookable_metadata.configuration.placement` (hookable configuration): `navbar` or `login`, fall back to `navbar` when unset |

Conventions:

* Every root element carries `data-admin-kit="<name>"`: `quick-filter`, `active-filters`, `active-filters-count`, `environment-strip`, `environment-badge`.
* Use the Twig functions `rem42_admin_kit_quick_filter(grid)`, `rem42_admin_kit_active_filters(grid)` and
  `rem42_admin_kit_environment()`, see [Quick filter](quick-filter.md), [Active filters](active-filters.md) and
  [Environment indicator](environment.md) for what they return.
* Translations live in the `messages` domain, under `rem42_sylius_admin_kit.ui.*`.
* Use the shipped `templates/bootstrap/` and `templates/shadcn/` as starting points.
