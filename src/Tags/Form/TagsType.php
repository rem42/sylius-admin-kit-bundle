<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\Tags\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * List of strings typed as tags: block "rem42_admin_kit_tags_widget" of {theme}/tags/form_theme.html.twig and the
 * Stimulus controller "admin-kit--tags".
 *
 * Model data: list<string> (trimmed, without empty entries, without duplicates when "unique"). Submitted value: a
 * JSON list written by the controller, or any text split by "split_pattern" (e.g. "a, b").
 *
 * It validates nothing on the server: add the constraints of the tags, e.g. new All([new Email()]).
 *
 * @extends AbstractType<list<string>>
 */
final class TagsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var string $splitPattern */
        $splitPattern = $options['split_pattern'];
        $unique = true === $options['unique'];

        $builder->addModelTransformer(new CallbackTransformer(
            static fn (mixed $tags): string => json_encode(self::normalize(\is_array($tags) ? $tags : [], $unique), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES),
            static fn (mixed $value): array => self::normalize(self::decode(\is_string($value) ? $value : '', $splitPattern), $unique),
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var string $splitPattern */
        $splitPattern = $options['split_pattern'];
        $value = $view->vars['value'];

        $view->vars['tags'] = self::normalize(self::decode(\is_string($value) ? $value : '', $splitPattern), true === $options['unique']);
        $view->vars['split_pattern'] = $splitPattern;
        $view->vars['tag_pattern'] = $options['tag_pattern'];
        $view->vars['unique'] = $options['unique'];
        $view->vars['input_type'] = $options['input_type'];
        $view->vars['entry_placeholder'] = $options['entry_placeholder'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'empty_data' => '',
            // JavaScript/PCRE-compatible regex, without delimiters: what separates the tags, typed or pasted.
            'split_pattern' => '[,;\n]+',
            // JavaScript regex, without delimiters, a tag must match to be added (client side only); null = anything.
            'tag_pattern' => null,
            // Ignores a tag already in the list (case-insensitive).
            'unique' => true,
            // Type of the text input that types the next tag ("email" brings the email keyboard on mobile).
            'input_type' => 'text',
            'entry_placeholder' => 'rem42_sylius_admin_kit.ui.tags.placeholder',
        ]);

        $resolver->setAllowedTypes('split_pattern', 'string');
        $resolver->setAllowedTypes('tag_pattern', ['null', 'string']);
        $resolver->setAllowedTypes('unique', 'bool');
        $resolver->setAllowedValues('input_type', ['text', 'email', 'url', 'tel', 'search']);
        $resolver->setAllowedTypes('entry_placeholder', ['null', 'string']);
        $resolver->setAllowedValues('split_pattern', static fn (string $pattern): bool => '' !== $pattern && false !== @preg_match('/' . str_replace('/', '\/', $pattern) . '/u', ''));
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'rem42_admin_kit_tags';
    }

    /**
     * @return list<string>
     */
    private static function decode(string $value, string $splitPattern): array
    {
        $value = trim($value);

        if ('' === $value) {
            return [];
        }

        if (str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);

            if (\is_array($decoded) && array_is_list($decoded)) {
                return array_values(array_filter($decoded, \is_string(...)));
            }
        }

        return preg_split('/' . str_replace('/', '\/', $splitPattern) . '/u', $value) ?: [];
    }

    /**
     * @param array<mixed> $tags
     *
     * @return list<string>
     */
    private static function normalize(array $tags, bool $unique): array
    {
        $normalized = [];
        $seen = [];

        foreach ($tags as $tag) {
            $tag = \is_string($tag) ? trim($tag) : '';
            $key = mb_strtolower($tag);

            if ('' === $tag || ($unique && isset($seen[$key]))) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = $tag;
        }

        return $normalized;
    }
}
