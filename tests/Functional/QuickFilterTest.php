<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class QuickFilterTest extends AdminKitWebTestCase
{
    #[DataProvider('themes')]
    public function testTabsAreRendered(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');

        self::assertResponseIsSuccessful();
        $links = $crawler->filter('[data-admin-kit="quick-filter"] a');
        self::assertGreaterThanOrEqual(2, $links->count());
        self::assertSame('page', $links->first()->attr('aria-current'));
    }

    #[DataProvider('themes')]
    public function testActiveTabAndOtherCriteriaKept(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books?criteria[category]=fiction&criteria[search][value]=a');

        self::assertResponseIsSuccessful();
        $active = $crawler->filter('[data-admin-kit="quick-filter"] a[aria-current="page"]');
        self::assertCount(1, $active);
        self::assertStringContainsString('Fiction', $active->text());

        $links = $crawler->filter('[data-admin-kit="quick-filter"] a');

        foreach (range(0, $links->count() - 1) as $index) {
            self::assertStringContainsString('criteria%5Bsearch%5D%5Bvalue%5D=a', (string) $links->eq($index)->attr('href'));
        }
    }

    #[DataProvider('themes')]
    public function testUnknownCriterionRenders(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $client->request('GET', $prefix . '/admin/books?criteria[category]=bogus');

        self::assertResponseIsSuccessful();
    }

    #[DataProvider('themes')]
    public function testArrayCriterionActivatesNoTab(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books?criteria[category][]=fiction');

        self::assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(2, $crawler->filter('[data-admin-kit="quick-filter"] a')->count());
        self::assertCount(0, $crawler->filter('[data-admin-kit="quick-filter"] [aria-current="page"]'));
    }

    #[DataProvider('themes')]
    public function testNothingWhenDisabled(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme, 'disabled');
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-admin-kit="quick-filter"]'));
    }
}
