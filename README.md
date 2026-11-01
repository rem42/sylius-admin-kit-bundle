# Sylius Admin Kit

Opt-in admin features for [Sylius Stack](https://stack.sylius.com) admin UIs, themed for
[`rem42/shadcn-admin-ui`](https://github.com/rem42/shadcn-admin-ui) (Tailwind) and
[`sylius/bootstrap-admin-ui`](https://github.com/Sylius/BootstrapAdminUi) (Tabler).

| Feature | What it does |
|---|---|
| [Quick filter](docs/quick-filter.md) | Tabs above a grid, built from an `enum` filter, with optional counts and coloured dots. |
| [Active filters](docs/active-filters.md) | Removable chips listing the criteria that filter a grid, visible even when the filters are collapsed. |
| [Environment indicator](docs/environment.md) | Coloured strip on every admin page, badge in the navbar and on the login page. |
| [Entity filter autocomplete](docs/entity-filter-autocomplete.md) | `entity` grid filters become searchable Tom Select comboboxes. |

Everything is disabled by default: installing the bundle changes nothing until you enable a feature. A disabled
feature registers no service, no Twig function and no hook.

## Quick start

```bash
composer require rem42/sylius-admin-kit-bundle
```

```php
// config/bundles.php
return [
    // ...
    Rem42\SyliusAdminKit\Rem42SyliusAdminKitBundle::class => ['all' => true],
];
```

```yaml
# config/packages/rem42_sylius_admin_kit.yaml
rem42_sylius_admin_kit:
    quick_filter:
        enabled: true
    active_filters:
        enabled: true
    environment:
        enabled: true
    entity_filter_autocomplete:
        enabled: true
```

The theme is detected (`shadcn` when `Rem42ShadcnAdminUiBundle` is loaded, `bootstrap` otherwise) and can be forced
or replaced by your own, see [Themes](docs/themes.md). With the shadcn theme, add one line to your Tailwind CSS,
see [Installation](docs/installation.md).

## Documentation

* [Installation](docs/installation.md)
* [Configuration reference](docs/configuration.md)
* [Quick filter](docs/quick-filter.md)
* [Active filters](docs/active-filters.md)
* [Environment indicator](docs/environment.md)
* [Entity filter autocomplete](docs/entity-filter-autocomplete.md)
* [Themes](docs/themes.md) (built-in and custom themes, file contract)

## Development

```bash
make up        # demo application showing the features, in both themes (login: admin / admin):
               # http://localhost:8080/admin (bootstrap)
               # http://localhost:8080/shadcn/admin (shadcn)
make ci        # php-cs-fixer + twig-cs-fixer + PHPStan (level max) + PHPUnit
make install   # composer install
```

The demo application lives in `tests/Application`. Each theme runs in its own kernel (`App\Kernel`): bundles flagged
with its name in `config/bundles.php`, configuration of `config/packages/{theme}/`. The shadcn theme needs its assets:
`make assets`, or `APP_THEME=shadcn php tests/Application/bin/console importmap:install` (the Docker entrypoint does
it) followed by `tailwind:build`.

## License

This bundle is released under the [MIT License](LICENSE).
