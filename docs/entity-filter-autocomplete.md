# Entity filter autocomplete

The `entity` filters of your grids become searchable Tom Select comboboxes (Symfony UX Autocomplete, options loaded
with the page, no Ajax) instead of native selects.

```yaml
rem42_sylius_admin_kit:
    entity_filter_autocomplete:
        enabled: true
```

## Requirements

`symfony/ux-autocomplete` must be installed and its `AutocompleteBundle` enabled. Otherwise the container build
fails with:

> The "entity_filter_autocomplete" feature of rem42_sylius_admin_kit requires symfony/ux-autocomplete: install it
> and enable its AutocompleteBundle.

## How it works

The kit registers a form type extension (`rem42_sylius_admin_kit.form.extension.entity_filter`) on Sylius Grid's
`EntityFilterType` that sets the `autocomplete` option to `true` by default. The kit ships no template: the
rendering, the Stimulus controller and the styles come from your admin UI, so follow its installation
documentation for the Tom Select assets.
