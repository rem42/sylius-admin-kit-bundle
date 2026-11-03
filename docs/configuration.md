# Configuration reference

```yaml
# config/packages/rem42_sylius_admin_kit.yaml
rem42_sylius_admin_kit:
    # Theme whose templates are used. null = auto: "shadcn" if Rem42ShadcnAdminUiBundle is loaded, "bootstrap"
    # otherwise. Values: "shadcn", "bootstrap" or a key of "themes".
    theme: ~

    # Custom themes: name => templates directory holding the same files as templates/{shadcn,bootstrap}/.
    themes: {}
        # acme: '@AcmeAdmin/kit'

    quick_filter:
        enabled: false

    # Bar listing the criteria that filter the grid, each removable on its own.
    active_filters:
        enabled: false

    environment:
        enabled: false
        # Current environment, e.g. '%env(default:kernel.environment:APP_STAGE)%' on a preprod running in "prod".
        name: '%kernel.environment%'
        # Environments that show the indicator. Any other one (e.g. "prod") shows nothing.
        environments:
            dev:     { label: ~, color: 'oklch(55.3% 0.195 38.402)',  foreground: '#fff' }
            test:    { label: ~, color: 'oklch(54.1% 0.281 293.009)', foreground: '#fff' }
            staging: { label: ~, color: 'oklch(54.6% 0.245 262.881)', foreground: '#fff' }
            preprod: { label: ~, color: 'oklch(54.6% 0.245 262.881)', foreground: '#fff' }
        strip: true    # strip on top of every admin page
        navbar: true   # badge in the navbar
        login: true    # badge on the login page

    entity_filter_autocomplete:
        enabled: false

    tags:
        enabled: false
```

## Rules

* `theme` must be `shadcn`, `bootstrap` or a key of `themes`, otherwise the container build fails with an
  `InvalidArgumentException` listing the possible values.
* `environment.environments` replaces the default list, it is not merged with it.
* `environment.environments.*.label` defaults to the upper-cased environment name.
* `environment.environments.*.color` is required and not empty. It accepts any CSS color. `color` and `foreground`
  reject the characters `; { } < > " ' \` (they are written in an inline `<style>`), also when the value comes from an `%env()%` placeholder (checked at render time).
* Parameters such as `enabled: '%app.flag%'` are resolved before the hooks are registered.
* `environment.name` is matched case-sensitively against the keys of `environments`.
* `entity_filter_autocomplete.enabled: true` without the `AutocompleteBundle` of `symfony/ux-autocomplete` fails at
  container compilation: `The "entity_filter_autocomplete" feature of rem42_sylius_admin_kit requires
  symfony/ux-autocomplete: install it and enable its AutocompleteBundle.`
* `tags.enabled: true` without the `StimulusBundle` of `symfony/stimulus-bundle` fails at container compilation:
  `The "tags" feature of rem42_sylius_admin_kit requires symfony/stimulus-bundle: install it and enable its
  StimulusBundle.`
* `tags` prepends its form theme to `twig.form_themes`: a form theme of your application that defines
  `rem42_admin_kit_tags_widget` comes after it and wins.

## Overriding or disabling a hookable

The kit registers its hooks with `sylius_twig_hooks` in *prepend* mode, so the configuration of your application
wins. Use the standard `sylius_twig_hooks` options on the hookable names below:

| Feature | Hook | Hookable | Priority |
|---|---|---|---|
| `quick_filter` | `sylius_admin.common.index.content.grid` | `rem42_admin_kit_quick_filters` | 100 |
| `active_filters` (shadcn-admin-ui) | `sylius_admin.common.index.content.grid.filters#trigger` and `...filters#summary` | `rem42_admin_kit_active_filters` | |
| `active_filters` (other UIs) | `sylius_admin.common.index.content.grid` | `rem42_admin_kit_active_filters` | 50 |
| `environment.strip` | `sylius_admin.base#stylesheets` | `rem42_admin_kit_environment` | |
| `environment.navbar` | `sylius_admin.common.component.navbar.items` | `rem42_admin_kit_environment` | 100 |
| `environment.login` | `sylius_admin.security.login.page` | `rem42_admin_kit_environment` | 1000 |

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.common.index.content.grid':
            rem42_admin_kit_quick_filters:
                template: 'admin/my_quick_filters.html.twig'   # replace the template
                priority: 200                                   # or move it
        'sylius_admin.common.component.navbar.items':
            rem42_admin_kit_environment:
                enabled: false                                  # or disable it
```

To disable a whole feature rather than one hookable, set its `enabled` flag to `false`.
