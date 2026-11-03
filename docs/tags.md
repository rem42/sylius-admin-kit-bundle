# Tags

`TagsType` is a form type for a list of strings (emails, keywords, codes…) typed as removable tags: Enter or a
separator adds the typed tag, the × button or Backspace in an empty input removes one, pasting a list adds a tag per
item.

```yaml
rem42_sylius_admin_kit:
    tags:
        enabled: true
```

```php
use Rem42\SyliusAdminKit\Tags\Form\TagsType;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Email;

$builder->add('recipients', TagsType::class, [
    'split_pattern' => '[,;\s]+',
    'tag_pattern' => '[^@\s]+@[^@\s]+\.[^@\s]+',
    'input_type' => 'email',
    'constraints' => [new All([new Email()])],
]);
```

Store the property as a list, e.g. a Doctrine `json` column:

```php
/** @var list<string> */
#[ORM\Column(type: Types::JSON)]
private array $recipients = [];
```

## Options

| Option | Default | Description |
|---|---|---|
| `split_pattern` | `'[,;\n]+'` | Regex without delimiters (JavaScript and PCRE compatible) separating the tags, typed or pasted. Validated when the form is built. |
| `tag_pattern` | `null` | Regex without delimiters a tag must fully match to be added, **in the browser only**. `null` accepts anything. |
| `unique` | `true` | Ignores a tag already in the list (case-insensitive). |
| `input_type` | `'text'` | Type of the input typing the next tag: `text`, `email`, `url`, `tel` or `search` (mobile keyboard). |
| `entry_placeholder` | `'rem42_sylius_admin_kit.ui.tags.placeholder'` | Placeholder of that input, translated with the form `translation_domain`. `null` for none. |

The model data is a `list<string>`: trimmed, without empty entries, without duplicates when `unique`. The type
validates nothing on the server: add the constraints of the tags (`All([...])`, `Count`…).

The submitted value is the JSON list written by the controller in a hidden input. Any other text is split with
`split_pattern`, so the field still works without JavaScript (`a, b`) and in functional tests:

```php
$form['book[tags]'] = '["a","b"]';
```

## Requirements

`symfony/stimulus-bundle` must be installed and its `StimulusBundle` enabled. Otherwise the container build fails
with:

> The "tags" feature of rem42_sylius_admin_kit requires symfony/stimulus-bundle: install it and enable its
> StimulusBundle.

## How it works

* The kit registers the form type and prepends its form theme, `{theme}/tags/form_theme.html.twig`, to
  `twig.form_themes`.
* With the AssetMapper enabled, the kit adds its `assets/` directory to the AssetMapper paths
  (`@rem42/sylius-admin-kit`) and its `assets/controllers/` directory to `stimulus.controller_paths`, next to
  `%kernel.project_dir%/assets/controllers`: the controller `admin_kit/tags_controller.js` is loaded with the
  application ones under the identifier `admin-kit--tags`. Nothing to add to `controllers.json` or the importmap.
* With Webpack Encore (or the AssetMapper disabled, as the precompiled assets of `sylius/bootstrap-admin-ui`), register
  the controller yourself:

  ```js
  import TagsController from '../../vendor/rem42/sylius-admin-kit-bundle/assets/controllers/admin_kit/tags_controller.js';

  app.register('admin-kit--tags', TagsController);
  ```

* With the shadcn theme, the tag classes are in `templates/shadcn`: keep the `@source` line of the
  [installation](installation.md#shadcn-tailwind-source) and rebuild your CSS.

Without the feature enabled, a `TagsType` field still builds (it is a plain form type) but renders as a text input
holding the JSON list.
