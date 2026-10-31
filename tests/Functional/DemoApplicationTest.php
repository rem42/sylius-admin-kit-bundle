<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Smoke-tests the demo application (tests/Application) in every theme.
 *
 * @internal
 */
final class DemoApplicationTest extends AdminKitWebTestCase
{
    #[DataProvider('themes')]
    public function testTheLoginPageRenders(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        $client->request('GET', $prefix . '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_csrf_admin_security_token"]');
    }

    #[DataProvider('themes')]
    public function testTheAdminPanelRequiresALogin(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        $client->request('GET', $prefix . '/admin/');

        self::assertResponseRedirects($prefix . '/admin/login');
    }

    #[DataProvider('themes')]
    public function testTheBookGridRenders(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $client->request('GET', $prefix . '/admin/books');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Clean Code', (string) $client->getResponse()->getContent());
    }

    #[DataProvider('themes')]
    public function testTheThemeIsTheExpectedOne(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        $client->request('GET', $prefix . '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'bootstrap' === $theme ? 'bundles/syliusbootstrapadminui/app.css' : '<script type="importmap"',
            (string) $client->getResponse()->getContent(),
        );
    }
}
