<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\QuickFilter\Fixture;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TranslatableStatus: string implements TranslatableInterface
{
    case New = 'new';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return 'translatable:' . $this->value;
    }
}
