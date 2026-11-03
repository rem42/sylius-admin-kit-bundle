<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Functional;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
final class TagsTest extends AdminKitWebTestCase
{
    private const WIDGET = '[data-admin-kit="tags"]';

    private const VALUE = 'input[type="hidden"][name="sylius_resource[tags]"]';

    #[DataProvider('themes')]
    public function testTheFieldIsATagsWidget(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $crawler = $client->request('GET', \sprintf('%s/admin/books/%d/edit', $prefix, $this->dune($client)->getId()));

        self::assertResponseIsSuccessful();
        $widget = $crawler->filter(self::WIDGET);
        self::assertCount(1, $widget);
        self::assertSame('admin-kit--tags', $widget->attr('data-controller'));
        self::assertSame('["classic","desert","saga"]', $widget->filter(self::VALUE)->attr('value'));
        self::assertSame(['classic', 'desert', 'saga'], $widget->filter('[data-admin-kit--tags-target="list"] > [data-admin-kit--tags-target="tag"]')->each(static fn ($tag): ?string => $tag->attr('data-admin-kit-tag')));
        // The label targets the input typing the next tag, and the controller clones its tags from the template.
        self::assertCount(1, $widget->filter('input[data-admin-kit-tags-entry][id="sylius_resource_tags"]'));
        self::assertCount(1, $widget->filter('template[data-admin-kit--tags-target="template"]'));
    }

    #[DataProvider('themes')]
    public function testSubmittingSavesTheList(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme);
        self::logIn($client);
        $id = $this->dune($client)->getId();
        $crawler = $client->request('GET', \sprintf('%s/admin/books/%d/edit', $prefix, $id));

        $form = $crawler->filter('form[name="sylius_resource"]')->form();
        $form['sylius_resource[tags]'] = '["epic"," Desert ","epic"]';
        $client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(['epic', 'Desert'], $this->dune($client)->getTags());
    }

    #[DataProvider('themes')]
    public function testPlainTextFieldWhenDisabled(string $theme, string $prefix): void
    {
        $client = self::bootClient($theme, 'disabled');
        self::logIn($client);
        $crawler = $client->request('GET', \sprintf('%s/admin/books/%d/edit', $prefix, $this->dune($client)->getId()));

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter(self::WIDGET));
        self::assertCount(1, $crawler->filter('input[name="sylius_resource[tags]"]'));
    }

    private function dune(KernelBrowser $client): Book
    {
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $em->clear();
        $book = $em->getRepository(Book::class)->findOneBy(['title' => 'Dune']);
        self::assertInstanceOf(Book::class, $book);

        return $book;
    }
}
