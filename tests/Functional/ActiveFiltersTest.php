<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class ActiveFiltersTest extends AdminKitWebTestCase
{
    #[DataProvider('themes')]
    public function testNothingWithoutCriteria(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-admin-kit="active-filters"]'));
    }

    #[DataProvider('themes')]
    public function testOneItemPerCriterionWithReadableValues(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $client->request('GET', $prefix . '/admin/books');
        $authorId = $client->getCrawler()->filter('select[name="criteria[author]"] option')->eq(1)->attr('value');
        $authorName = trim($client->getCrawler()->filter('select[name="criteria[author]"] option')->eq(1)->text());

        $crawler = $client->request('GET', $prefix . '/admin/books?limit=25&criteria[category]=science-fiction&criteria[available]=true&criteria[author]=' . $authorId . '&criteria[search][value]=dune');

        self::assertResponseIsSuccessful();
        $bar = $crawler->filter('[data-admin-kit="active-filters"]');
        self::assertCount(1, $bar);

        self::assertStringContainsString('Science-fiction', $bar->filter('[data-admin-kit-filter="category"]')->text(), 'enum: choice_label of the filter');
        self::assertStringContainsString($authorName, $bar->filter('[data-admin-kit-filter="author"]')->text(), 'entity: choice_label of the filter');
        self::assertStringContainsString('dune', $bar->filter('[data-admin-kit-filter="search"]')->text());
        self::assertCount(1, $bar->filter('[data-admin-kit-filter="available"]'));

        $remove = (string) $bar->filter('[data-admin-kit-filter="category"] a')->attr('href');
        self::assertStringNotContainsString('criteria%5Bcategory%5D', $remove);
        self::assertStringContainsString('criteria%5Bavailable%5D=true', $remove);
        self::assertStringContainsString('limit=25', $remove);

        $clear = $bar->filter('a')->last()->attr('href');
        self::assertSame($prefix . '/admin/books?limit=25', $clear);
    }

    public function testShadcnShowsTheListAndTheCountInsideTheCollapsedFiltersPanel(): void
    {
        $client = self::bootClient('shadcn');
        self::logIn($client);
        $crawler = $client->request('GET', '/shadcn/admin/books?criteria[category]=fiction&criteria[available]=true');

        self::assertResponseIsSuccessful();
        $panel = $crawler->filter('[data-slot="card"][data-controller="shadcn--collapsible"]');
        self::assertSame('false', $panel->attr('data-shadcn--collapsible-open-value'), 'collapsed: the list shows the criteria');
        self::assertCount(1, $panel->filter('[data-admin-kit="active-filters"]'));
        self::assertStringStartsWith('2', trim($panel->filter('button [data-admin-kit="active-filters-count"]')->text()));
        self::assertCount(0, $panel->filter('button .size-1\.5.rounded-full'), 'the count replaces the dot');
    }

    #[DataProvider('themes')]
    public function testNothingWhenDisabled(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme, 'disabled');
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books?criteria[category]=fiction');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('[data-admin-kit="active-filters"]'));
    }
}
