<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\Theme;

final readonly class ResolvedTheme
{
    public function __construct(
        public string $name,
        public string $templatePrefix,
    ) {}
}
