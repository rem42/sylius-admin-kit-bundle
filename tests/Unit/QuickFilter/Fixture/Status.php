<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\QuickFilter\Fixture;

enum Status: string
{
    case New = 'new';
    case Done = 'done';
    case Archived = 'archived';
}
