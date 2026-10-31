<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class EntityFilterTest extends AdminKitWebTestCase
{
    private const SELECT = 'select[name="criteria[author]"]';

    #[DataProvider('themes')]
    public function testAuthorFilterIsAnAutocomplete(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');

        self::assertResponseIsSuccessful();
        $select = $crawler->filter(self::SELECT);
        self::assertCount(1, $select);
        self::assertStringContainsString('autocomplete', (string) $select->attr('data-controller'));
    }

    #[DataProvider('themes')]
    public function testNativeSelectWhenDisabled(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme, 'disabled');
        self::logIn($client);
        $crawler = $client->request('GET', $prefix . '/admin/books');

        self::assertResponseIsSuccessful();
        $select = $crawler->filter(self::SELECT);
        self::assertCount(1, $select);
        self::assertStringNotContainsString('autocomplete', (string) $select->attr('data-controller'));
    }
}
