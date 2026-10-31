# Installation

## Requirements

* PHP 8.4+, Symfony 7.4 or 8.0
* A Sylius Stack admin UI: [`rem42/shadcn-admin-ui`](https://github.com/rem42/shadcn-admin-ui) or
  [`sylius/bootstrap-admin-ui`](https://github.com/Sylius/BootstrapAdminUi) (`sylius/admin-ui` ^0.14), or your own
  theme (see [Themes](themes.md))
* `symfony/ux-icons` (required by the package, used by the templates)
* `symfony/ux-autocomplete` for the [entity filter autocomplete](entity-filter-autocomplete.md) only

## Install

```bash
composer require rem42/sylius-admin-kit-bundle
```

Without Symfony Flex, register the bundle:

```php
// config/bundles.php
return [
    // ...
    Rem42\SyliusAdminKit\Rem42SyliusAdminKitBundle::class => ['all' => true],
];
```

## Minimal configuration

Everything is opt-in. Enable the features you want:

```yaml
# config/packages/rem42_sylius_admin_kit.yaml
rem42_sylius_admin_kit:
    quick_filter:
        enabled: true
    environment:
        enabled: true
    entity_filter_autocomplete:
        enabled: true
```

The theme is auto-detected: `shadcn` when `Rem42ShadcnAdminUiBundle` is loaded, `bootstrap` otherwise. See
[Configuration](configuration.md) to force it.

## shadcn: Tailwind source

The shadcn templates of the kit use Tailwind classes. Tell Tailwind to scan them, in the CSS file of your
application (adjust the relative path):

```css
@import "tailwindcss";
@import "../../vendor/rem42/shadcn-admin-ui/assets/styles/theme.css";

@source "../../vendor/rem42/sylius-admin-kit-bundle/templates/shadcn";
```

Then rebuild your CSS (`php bin/console tailwind:build`). The bootstrap theme needs nothing more.

## Icons and translations

* The environment badge uses the icons `lucide:server` (shadcn) and `tabler:server` (bootstrap). They must be resolvable
  in production: lock them with `php bin/console ux:icons:lock lucide:server` (or `tabler:server`), or make sure
  Iconify is reachable.
* Quick filter tabs of an enum that is not translatable (does not implement `TranslatableInterface`) use the backed
  value as the translation key (`messages` domain, e.g. `new`).

## Entity filter autocomplete

The Tom Select rendering is provided by the admin UI, not by the kit. Install `symfony/ux-autocomplete`, enable its
`AutocompleteBundle`, and follow the installation documentation of your admin UI (Stimulus controllers, importmap
and styles) so that the `autocomplete` form option of `entity` filters is rendered. Details in
[Entity filter autocomplete](entity-filter-autocomplete.md).
