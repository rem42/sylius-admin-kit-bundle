<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class EnvironmentTest extends AdminKitWebTestCase
{
    #[DataProvider('themes')]
    public function testTheStripIsRendered(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        $crawler = $client->request('GET', $prefix . '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('style[data-admin-kit="environment-strip"]'));
        self::assertStringContainsString('--admin-kit-env:', (string) $client->getResponse()->getContent());
    }

    #[DataProvider('themes')]
    public function testTheBadgeIsShownOnTheLoginPage(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        $crawler = $client->request('GET', $prefix . '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-admin-kit="environment-badge"]'));
        self::assertStringContainsString('TEST', $crawler->filter('[data-admin-kit="environment-badge"]')->text());
    }

    #[DataProvider('themes')]
    public function testTheBadgeIsShownInTheNavbar(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-admin-kit="environment-badge"]'));
    }

    #[DataProvider('themes')]
    public function testTheLoginBadgeCarriesTheLoginPlacementButNotTheNavbarOne(string $theme, string $prefix): void
    {
        $marker = 'bootstrap' === $theme
            ? '.text-center > [data-admin-kit="environment-badge"]'
            : '[data-admin-kit="environment-badge"].self-center';

        $client = self::bootClient($theme);
        $crawler = $client->request('GET', $prefix . '/admin/login');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter($marker));

        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-admin-kit="environment-badge"]'));
        self::assertCount(0, $crawler->filter($marker));
    }

    #[DataProvider('themes')]
    public function testTheBadgeKeepsItsColoursWithoutTheStrip(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme, 'no_strip');
        $crawler = $client->request('GET', $prefix . '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-admin-kit="environment-strip"]'));

        $badge = $crawler->filter('[data-admin-kit="environment-badge"]');
        self::assertCount(1, $badge);
        self::assertStringContainsString('--admin-kit-env: oklch(54.1% 0.281 293.009)', (string) $badge->attr('style'));
        self::assertStringContainsString('--admin-kit-env-foreground: #fff', (string) $badge->attr('style'));
    }

    #[DataProvider('themes')]
    public function testNothingIsRenderedWhenDisabled(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme, 'disabled');

        $crawler = $client->request('GET', $prefix . '/admin/login');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-admin-kit^="environment"]'));

        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-admin-kit^="environment"]'));
    }
}
