<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\Environment\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the environment indicator ("rem42_sylius_admin_kit.environment" option) to the templates.
 *
 * The environment name is matched case-sensitively against the configured environments.
 */
final class EnvironmentExtension extends AbstractExtension
{
    /** Characters that would let a color escape the CSS declaration it is printed in. */
    public const string FORBIDDEN_COLOR_CHARACTERS = '/[;{}<>"\'\\\]/';

    /**
     * @param array<string, array{label: ?string, color: string, foreground: string}> $environments
     */
    public function __construct(
        private readonly string $name,
        private readonly array $environments,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('rem42_admin_kit_environment', $this->getEnvironment(...)),
        ];
    }

    /**
     * @return array{name: string, label: string, color: string, foreground: string}|null null when nothing must be shown
     */
    public function getEnvironment(): ?array
    {
        if (!isset($this->environments[$this->name])) {
            return null;
        }

        $environment = $this->environments[$this->name];

        // The configuration validates colors too, but not those coming from an %env()% placeholder.
        foreach (['color', 'foreground'] as $key) {
            if (1 === preg_match(self::FORBIDDEN_COLOR_CHARACTERS, $environment[$key])) {
                throw new \InvalidArgumentException(\sprintf('Invalid CSS %s for the environment "%s" of rem42_sylius_admin_kit.', $key, $this->name));
            }
        }

        return [
            'name' => $this->name,
            'label' => $environment['label'] ?? mb_strtoupper($this->name),
            'color' => $environment['color'],
            'foreground' => $environment['foreground'],
        ];
    }
}
