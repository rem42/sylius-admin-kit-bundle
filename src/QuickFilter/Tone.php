<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\QuickFilter;

/**
 * Tones available for the dot of a quick filter tab: the colors of the shadcn-admin-ui palette, aliases included.
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
    public const string GREY = 'grey';
    public const string BROWN = 'brown';
    public const string SUCCESS = 'success';
    public const string DANGER = 'danger';
    public const string WARNING = 'warning';
    public const string INFO = 'info';
    public const string PRIMARY = 'primary';
    public const string SECONDARY = 'secondary';
    public const string MUTED = 'muted';
    public const string DARK = 'dark';
    public const string BODY = 'body';
    public const string BLACK = 'black';

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
        self::GREY,
        self::BROWN,
        self::SUCCESS,
        self::DANGER,
        self::WARNING,
        self::INFO,
        self::PRIMARY,
        self::SECONDARY,
        self::MUTED,
        self::DARK,
        self::BODY,
        self::BLACK,
    ];

    /**
     * The tone itself, null when it is unknown.
     */
    public static function normalize(?string $tone): ?string
    {
        return null !== $tone && \in_array($tone, self::ALL, true) ? $tone : null;
    }
}
