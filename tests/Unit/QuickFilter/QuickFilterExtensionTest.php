<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\QuickFilter;

use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\QuickFilter\QuickFilterProviderInterface;
use Rem42\SyliusAdminKit\QuickFilter\Twig\QuickFilterExtension;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Rem42\SyliusAdminKit\Unit\QuickFilter\Fixture\Status;
use Tests\Rem42\SyliusAdminKit\Unit\QuickFilter\Fixture\TranslatableStatus;

/**
 * @internal
 */
final class QuickFilterExtensionTest extends TestCase
{
    private const URL = '/admin/books?criteria[search]=foo&page=3&sorting[title]=asc';

    public function testNullWhenTheGridHasNoQuickFilter(): void
    {
        $extension = $this->extension(self::URL);

        self::assertNull($extension->getQuickFilter(null));
        self::assertNull($extension->getQuickFilter($this->grid(null)));
    }

    public function testNullWithoutCurrentRequest(): void
    {
        $extension = $this->extension(null);

        self::assertNull($extension->getQuickFilter($this->grid([])));
    }

    public function testOneTabPerCasePlusAll(): void
    {
        $result = $this->extension(self::URL)->getQuickFilter($this->grid([]));

        self::assertNotNull($result);
        self::assertSame('category', $result['criterion']);
        self::assertCount(4, $result['tabs']);
        self::assertTrue($result['tabs'][0]['active']);
        self::assertSame('sylius.ui.all', $result['tabs'][0]['label']);
        self::assertFalse($result['tabs'][1]['active']);
        self::assertSame('new', $result['tabs'][1]['label']);
    }

    public function testCasesOptionRestrictsTabs(): void
    {
        $result = $this->extension(self::URL)->getQuickFilter($this->grid(['cases' => [Status::New]]));

        self::assertNotNull($result);
        self::assertCount(2, $result['tabs']);
    }

    public function testUrlsKeepOtherCriteriaAndDropPage(): void
    {
        $result = $this->extension(self::URL)->getQuickFilter($this->grid([]));

        self::assertNotNull($result);
        $url = $result['tabs'][1]['url'];
        self::assertStringContainsString('criteria%5Bsearch%5D=foo', $url);
        self::assertStringContainsString('criteria%5Bcategory%5D=new', $url);
        self::assertStringContainsString('sorting%5Btitle%5D=asc', $url);
        self::assertStringNotContainsString('page=', $url);
        self::assertStringNotContainsString('criteria%5Bcategory%5D', $result['tabs'][0]['url']);
        self::assertStringContainsString('criteria%5Bsearch%5D=foo', $result['tabs'][0]['url']);
    }

    public function testActiveTabFollowsTheCriterion(): void
    {
        $result = $this->extension('/admin/books?criteria[category]=done')->getQuickFilter($this->grid([]));

        self::assertNotNull($result);
        self::assertSame([false, false, true, false], array_column($result['tabs'], 'active'));
    }

    public function testUnknownValueActivatesNothing(): void
    {
        $result = $this->extension('/admin/books?criteria[category]=bogus')->getQuickFilter($this->grid([]));

        self::assertNotNull($result);
        self::assertSame([false, false, false, false], array_column($result['tabs'], 'active'));
    }

    public function testArrayCriterionActivatesNothing(): void
    {
        $result = $this->extension('/admin/books?criteria[category][]=new')->getQuickFilter($this->grid([]));

        self::assertNotNull($result);
        // The grid is still filtered by the array: not even "All" is active.
        self::assertSame([false, false, false, false], array_column($result['tabs'], 'active'));
        self::assertStringContainsString('criteria%5Bcategory%5D=done', $result['tabs'][2]['url']);
    }

    public function testScalarCriteriaIsIgnored(): void
    {
        $result = $this->extension('/admin/books?criteria=foo')->getQuickFilter($this->grid([]));

        self::assertNotNull($result);
        self::assertSame([true, false, false, false], array_column($result['tabs'], 'active'));
    }

    public function testNullWithoutRoute(): void
    {
        $extension = $this->extension(self::URL, [], false);

        self::assertNull($extension->getQuickFilter($this->grid([])));
    }

    public function testCountsTotalAndEmptyTabsHidden(): void
    {
        $provider = $this->provider(['new' => 2, 'done' => 0, 'archived' => 5]);

        $result = $this->extension(self::URL, ['p' => $provider])->getQuickFilter($this->grid(['provider' => 'p']));
        self::assertNotNull($result);
        self::assertSame('sylius.ui.all', $result['tabs'][0]['label']);
        self::assertSame(7, $result['tabs'][0]['count']);
        self::assertSame(['new', 'archived'], array_column(\array_slice($result['tabs'], 1), 'label'));
        self::assertSame(2, $result['tabs'][1]['count']);

        $result = $this->extension('/admin/books?criteria[category]=done', ['p' => $provider])->getQuickFilter($this->grid(['provider' => 'p']));
        self::assertNotNull($result);
        self::assertContains('done', array_column($result['tabs'], 'label'));
    }

    public function testNullCountsHideCounts(): void
    {
        $result = $this->extension(self::URL, ['p' => $this->provider(null)])->getQuickFilter($this->grid(['provider' => 'p']));

        self::assertNotNull($result);
        self::assertCount(4, $result['tabs']);
        self::assertSame([null, null, null, null], array_column($result['tabs'], 'count'));
    }

    public function testUnknownToneIsDropped(): void
    {
        $result = $this->extension(self::URL, ['p' => $this->provider(null, 'fuchsia')])->getQuickFilter($this->grid(['provider' => 'p']));
        self::assertNotNull($result);
        self::assertNull($result['tabs'][1]['tone']);

        $result = $this->extension(self::URL, ['p' => $this->provider(null, 'blue')])->getQuickFilter($this->grid(['provider' => 'p']));
        self::assertNotNull($result);
        self::assertSame('blue', $result['tabs'][1]['tone']);
    }

    public function testTranslatableEnumLabel(): void
    {
        $result = $this->extension(self::URL)->getQuickFilter($this->grid([], TranslatableStatus::class));

        self::assertNotNull($result);
        self::assertSame('translatable:new', $result['tabs'][1]['label']);
    }

    public function testEnumFilterWithoutClassThrows(): void
    {
        $grid = Grid::fromCodeAndDriverConfiguration('app_book', 'doctrine/orm', []);
        $filter = Filter::fromNameAndType('category', 'enum');
        $filter->setOptions(['quick_filter' => []]);
        $grid->addFilter($filter);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"category"');

        $this->extension(self::URL)->getQuickFilter($grid);
    }

    public function testUnknownProviderThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/missing.*QuickFilterProviderInterface/');

        $this->extension(self::URL)->getQuickFilter($this->grid(['provider' => 'missing']));
    }

    /**
     * @param array<string, mixed>|null $options null = no quick_filter option
     * @param class-string<\BackedEnum> $enum
     */
    private function grid(?array $options, string $enum = Status::class): Grid
    {
        $grid = Grid::fromCodeAndDriverConfiguration('app_book', 'doctrine/orm', []);
        $filter = Filter::fromNameAndType('category', 'enum');
        $filter->setFormOptions(['class' => $enum]);

        if (null !== $options) {
            $filter->setOptions(['quick_filter' => $options]);
        } else {
            $filter->setOptions([]);
        }

        $grid->addFilter($filter);

        return $grid;
    }

    /**
     * @param array<string, QuickFilterProviderInterface> $providers
     */
    private function extension(?string $url, array $providers = [], bool $withRoute = true): QuickFilterExtension
    {
        $requests = new RequestStack();

        if (null !== $url) {
            $request = Request::create($url);

            if ($withRoute) {
                $request->attributes->set('_route', 'app_book_index');
            }

            $request->attributes->set('_route_params', []);
            $requests->push($request);
        }

        $routes = new RouteCollection();
        $routes->add('app_book_index', new Route('/admin/books'));

        $translator = new class implements TranslatorInterface {
            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id;
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };

        return new QuickFilterExtension(
            new ServiceLocator(array_map(static fn (QuickFilterProviderInterface $p): \Closure => static fn (): QuickFilterProviderInterface => $p, $providers)),
            $translator,
            $requests,
            new UrlGenerator($routes, new RequestContext()),
        );
    }

    /**
     * @param array<int|string, int>|null $counts
     */
    private function provider(?array $counts, ?string $color = null): QuickFilterProviderInterface
    {
        return new readonly class($counts, $color) implements QuickFilterProviderInterface {
            /**
             * @param array<int|string, int>|null $counts
             */
            public function __construct(
                private ?array $counts,
                private ?string $color,
            ) {}

            public function getCounts(): ?array
            {
                return $this->counts;
            }

            public function getColor(\BackedEnum $case): ?string
            {
                return $this->color;
            }
        };
    }
}
