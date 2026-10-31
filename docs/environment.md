# Environment indicator

A coloured strip on top of every admin page, and a badge in the navbar and on the login page, so nobody mistakes
staging for production.

```yaml
rem42_sylius_admin_kit:
    environment:
        enabled: true
        name: '%env(default:kernel.environment:APP_STAGE)%'
        environments:
            dev:     { color: 'oklch(55.3% 0.195 38.402)' }
            staging: { label: 'Staging', color: '#2563eb', foreground: '#fff' }
            preprod: { color: '#2563eb' }
        strip: true
        navbar: true
        login: true
```

* `name` is the current environment. It defaults to `%kernel.environment%`; use an env var when a pre-production
  runs with `APP_ENV=prod`.
* `environments` lists the environments that show the indicator. Any other one (typically `prod`) shows nothing.
  Defining it replaces the default list (`dev`, `test`, `staging`, `preprod`).
* The name is matched case-sensitively. `label` defaults to the upper-cased name, `foreground` to `#fff`.
* `color` and `foreground` accept any CSS color but not the characters `; { } < > " ' \`. Colors that come from an
  `%env()%` placeholder skip the configuration check, so they are validated again when rendered (an
  `InvalidArgumentException` names the environment).
* `strip`, `navbar` and `login` enable each hook separately. A disabled one registers no hook.

## Rendering

| Flag | Hook | Hookable | Template | Root element |
|---|---|---|---|---|
| `strip` | `sylius_admin.base#stylesheets` | `rem42_admin_kit_environment` | `environment/strip.html.twig` | `data-admin-kit="environment-strip"` |
| `navbar` | `sylius_admin.common.component.navbar.items` (priority 100) | `rem42_admin_kit_environment` | `environment/badge.html.twig` | `data-admin-kit="environment-badge"` |
| `login` | `sylius_admin.security.login.page` (priority 1000) | `rem42_admin_kit_environment` | `environment/badge.html.twig` | `data-admin-kit="environment-badge"` |

The strip is a self-contained inline `<style>` (no dependency on the theme CSS): a 4px bar at the top of the
viewport, hidden on print, which defines the CSS variables `--admin-kit-env` and
`--admin-kit-env-foreground` on `:root`. The badge defines the same variables on its own `style` attribute, so it keeps
its colours when `strip` is off, and uses them for its background and text, shows a server icon (shadcn:
`lucide:server`, bootstrap: `tabler:server`, from `symfony/ux-icons`) and the label, with a translated `title` and
screen-reader text (`rem42_sylius_admin_kit.ui.environment`, "Environment: %label%").

The badge template reads its placement from `hookable_metadata.configuration.placement` (`navbar` or `login`, `navbar` when unset): the login placement centres it.

The templates use the Twig function `rem42_admin_kit_environment()`, which returns
`{name, label, color, foreground}`, or `null` when the current environment is not listed. See
[Configuration](configuration.md) to override or disable a hookable.
