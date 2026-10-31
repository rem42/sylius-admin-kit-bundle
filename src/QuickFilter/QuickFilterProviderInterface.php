<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\QuickFilter;

/**
 * Counts and colors of the quick filter tabs of one enum grid filter.
 *
 * Optional: referenced by its service id in the "quick_filter.provider" option of the filter.
 * Without one, the tabs show only their label.
 * Services implementing it are tagged "rem42_sylius_admin_kit.quick_filter_provider" automatically.
 */
interface QuickFilterProviderInterface
{
    /**
     * Number of resources per enum value, for every value (the "All" tab shows their sum).
     * Null hides the counts, e.g. when the current user must not see them.
     *
     * @return array<int|string, int>|null
     */
    public function getCounts(): ?array;

    /**
     * Name of the tone of the tab dot (see Tone::ALL), null for no dot. Unknown tones are ignored.
     */
    public function getColor(\BackedEnum $case): ?string;
}
