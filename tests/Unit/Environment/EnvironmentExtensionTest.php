<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\Environment;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\Environment\Twig\EnvironmentExtension;

/**
 * @internal
 */
final class EnvironmentExtensionTest extends TestCase
{
    private const ENVIRONMENTS = [
        'dev' => ['label' => null, 'color' => 'red', 'foreground' => '#fff'],
        'staging' => ['label' => 'Recette', 'color' => 'blue', 'foreground' => '#000'],
    ];

    public function testTheLabelDefaultsToTheUpperCasedName(): void
    {
        self::assertSame(
            ['name' => 'dev', 'label' => 'DEV', 'color' => 'red', 'foreground' => '#fff'],
            (new EnvironmentExtension('dev', self::ENVIRONMENTS))->getEnvironment(),
        );
    }

    public function testAnExplicitLabelIsKept(): void
    {
        self::assertSame(
            ['name' => 'staging', 'label' => 'Recette', 'color' => 'blue', 'foreground' => '#000'],
            (new EnvironmentExtension('staging', self::ENVIRONMENTS))->getEnvironment(),
        );
    }

    public function testAnUnlistedEnvironmentShowsNothing(): void
    {
        self::assertNull((new EnvironmentExtension('prod', self::ENVIRONMENTS))->getEnvironment());
    }

    public function testTheNameIsCaseSensitive(): void
    {
        self::assertNull((new EnvironmentExtension('Dev', self::ENVIRONMENTS))->getEnvironment());
    }

    #[DataProvider('unsafeColors')]
    public function testAnUnsafeColorOrForegroundIsRejected(string $key, string $value): void
    {
        $environments = ['dev' => [
            'label' => null,
            'color' => 'color' === $key ? $value : 'red',
            'foreground' => 'foreground' === $key ? $value : '#fff',
        ]];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"dev"');

        (new EnvironmentExtension('dev', $environments))->getEnvironment();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsafeColors(): iterable
    {
        yield 'color with a semicolon' => ['color', 'red; background: url(x)'];
        yield 'foreground with a brace' => ['foreground', '#fff}</style><script>'];
        yield 'color with a quote' => ['color', 'red"'];
    }

    public function testItExposesTheTwigFunction(): void
    {
        $functions = (new EnvironmentExtension('dev', self::ENVIRONMENTS))->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('rem42_admin_kit_environment', $functions[0]->getName());
    }
}
