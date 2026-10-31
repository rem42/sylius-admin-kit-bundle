<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\QuickFilter;

/**
 * Tones available for the dot of a quick filter tab.
 */
final class Tone
{
    public const string BLUE = 'blue';
    public const string AZURE = 'azure';
    public const string INDIGO = 'indigo';
    public const string PURPLE = 'purple';
    public const string PINK = 'pink';
    public const string RED = 'red';
    public const string ORANGE = 'orange';
    public const string YELLOW = 'yellow';
    public const string LIME = 'lime';
    public const string GREEN = 'green';
    public const string TEAL = 'teal';
    public const string CYAN = 'cyan';
    public const string GRAY = 'gray';

    public const array ALL = [
        self::BLUE,
        self::AZURE,
        self::INDIGO,
        self::PURPLE,
        self::PINK,
        self::RED,
        self::ORANGE,
        self::YELLOW,
        self::LIME,
        self::GREEN,
        self::TEAL,
        self::CYAN,
        self::GRAY,
    ];

    /**
     * The tone itself, null when it is unknown.
     */
    public static function normalize(?string $tone): ?string
    {
        return null !== $tone && \in_array($tone, self::ALL, true) ? $tone : null;
    }
}
