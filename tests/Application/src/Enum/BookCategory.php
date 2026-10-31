<?php

declare(strict_types=1);

namespace App\Enum;

enum BookCategory: string
{
    case FICTION = 'fiction';
    case MYSTERY = 'mystery';
    case SCIENCE_FICTION = 'science-fiction';
    case FANTASY = 'fantasy';
    case HISTORY = 'history';
    case TECHNOLOGY = 'technology';

    public function color(): string
    {
        return match ($this) {
            self::FICTION => 'blue',
            self::MYSTERY => 'green',
            self::SCIENCE_FICTION => 'purple',
            self::FANTASY => 'orange',
            self::HISTORY => 'yellow',
            self::TECHNOLOGY => 'red',
        };
    }
}
