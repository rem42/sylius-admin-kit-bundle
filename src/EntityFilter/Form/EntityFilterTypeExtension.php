<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\EntityFilter\Form;

use Sylius\Bundle\GridBundle\Form\Type\Filter\EntityFilterType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Renders the grid "entity" filters as a searchable Tom Select combobox (Symfony UX Autocomplete, options loaded
 * with the page, no Ajax) instead of a native select.
 *
 * Only registered when symfony/ux-autocomplete is installed: it provides the "autocomplete" option.
 */
final class EntityFilterTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('autocomplete', true);
    }

    public static function getExtendedTypes(): iterable
    {
        return [EntityFilterType::class];
    }
}
