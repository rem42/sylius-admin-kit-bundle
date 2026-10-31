<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\ActiveFilters\Fixture;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Returns the id, followed by its parameters: "id{%a%=1,%b%=2}".
 */
final class EchoTranslator implements TranslatorInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if ([] === $parameters) {
            return $id;
        }

        $pairs = array_map(static fn (string $key, mixed $value): string => $key . '=' . (\is_scalar($value) ? $value : ''), array_keys($parameters), $parameters);

        return $id . '{' . implode(',', $pairs) . '}';
    }

    public function getLocale(): string
    {
        return 'en';
    }
}
