<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Functional;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Boots the demo application (tests/Application) in one of its themes.
 *
 * Options of createKernel()/createClient(): "environment", "debug", "theme" (see App\Kernel::THEMES) and
 * "variant" (config/variants/{variant}.yaml, loaded on top of the theme configuration).
 */
abstract class AdminKitWebTestCase extends WebTestCase
{
    /**
     * Data provider: theme => [theme, prefix of its URLs].
     *
     * @return iterable<string, array{string, string}>
     */
    public static function themes(): iterable
    {
        return [
            'bootstrap' => ['bootstrap', ''],
            'shadcn' => ['shadcn', '/shadcn'],
        ];
    }

    /**
     * @param array<mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        $environment = $options['environment'] ?? 'test';
        $debug = $options['debug'] ?? true;
        $theme = $options['theme'] ?? Kernel::DEFAULT_THEME;
        $variant = $options['variant'] ?? null;

        if (!\is_string($environment) || !\is_bool($debug) || !\is_string($theme) || (null !== $variant && !\is_string($variant))) {
            throw new \InvalidArgumentException('Invalid kernel options.');
        }

        return new Kernel($environment, $debug, $theme, $variant);
    }

    /**
     * Client of the given theme, on a fresh database holding the demo data set.
     */
    protected static function bootClient(string $theme, ?string $variant = null): KernelBrowser
    {
        $client = static::createClient(['theme' => $theme, 'variant' => $variant]);

        $application = new Application($client->getKernel());
        $application->setAutoExit(false);

        foreach ([['doctrine:schema:drop', '--force' => true], ['doctrine:schema:create'], ['app:load-books']] as $command) {
            $output = new BufferedOutput();
            self::assertSame(0, $application->run(new ArrayInput(['command' => array_shift($command)] + $command), $output), $output->fetch());
        }

        return $client;
    }

    protected static function logIn(KernelBrowser $client): void
    {
        $client->loginUser(new InMemoryUser('admin', 'admin', ['ROLE_ADMIN']));
    }
}
