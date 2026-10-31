<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\ActiveFilters;

use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\ActiveFilters\CriterionFormatter;
use Rem42\SyliusAdminKit\ActiveFilters\Twig\ActiveFiltersExtension;
use Sylius\Component\Grid\Definition\Filter;
use Sylius\Component\Grid\Definition\Grid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tests\Rem42\SyliusAdminKit\Unit\ActiveFilters\Fixture\EchoTranslator;

/**
 * @internal
 */
final class ActiveFiltersExtensionTest extends TestCase
{
    public function testNullWithoutGridRequestOrRoute(): void
    {
        self::assertNull($this->extension('/admin/books?criteria[search]=foo')->getActiveFilters(null));
        self::assertNull($this->extension(null)->getActiveFilters($this->grid()));
        self::assertNull($this->extension('/admin/books?criteria[search]=foo', withRoute: false)->getActiveFilters($this->grid()));
    }

    public function testNullWhenNoCriterionFilters(): void
    {
        self::assertNull($this->extension('/admin/books')->getActiveFilters($this->grid()));
        self::assertNull($this->extension('/admin/books?criteria[search][value]=&criteria[author]=')->getActiveFilters($this->grid()));
    }

    public function testOneItemPerCriterion(): void
    {
        $result = $this->extension('/admin/books?criteria[search][value]=foo&criteria[isAvailable]=true')->getActiveFilters($this->grid());

        self::assertNotNull($result);
        self::assertSame(['search', 'isAvailable'], array_column($result['filters'], 'name'));
        self::assertSame('app.ui.search', $result['filters'][0]['label']);
        self::assertSame('foo', $result['filters'][0]['value']);
        self::assertSame('Is available', $result['filters'][1]['label'], 'humanized name when the label is hidden');
        self::assertSame('sylius.ui.yes_label', $result['filters'][1]['value']);
    }

    public function testCriteriaOfUnknownOrDisabledFiltersAreIgnored(): void
    {
        $result = $this->extension('/admin/books?criteria[search]=foo&criteria[bogus]=x&criteria[hidden]=y')->getActiveFilters($this->grid());

        self::assertNotNull($result);
        self::assertSame(['search'], array_column($result['filters'], 'name'));
    }

    public function testRemoveUrlKeepsOtherCriteriaAndQueryButDropsThePage(): void
    {
        $result = $this->extension('/admin/books?criteria[search]=foo&criteria[isAvailable]=true&page=3&limit=25')->getActiveFilters($this->grid());

        self::assertNotNull($result);
        self::assertSame('/admin/books?limit=25&criteria%5BisAvailable%5D=true', $result['filters'][0]['remove_url']);
        self::assertSame('/admin/books?limit=25&criteria%5Bsearch%5D=foo', $result['filters'][1]['remove_url']);
        self::assertSame('/admin/books?limit=25', $result['clear_url']);
    }

    public function testMultipleCriterionGivesOneItemPerValue(): void
    {
        $result = $this->extension('/admin/books?criteria[author][]=1&criteria[author][]=2')->getActiveFilters($this->grid());

        self::assertNotNull($result);
        self::assertSame(['1', '2'], array_column($result['filters'], 'value'));
        self::assertSame('/admin/books?criteria%5Bauthor%5D%5B0%5D=2', $result['filters'][0]['remove_url']);
        self::assertSame('/admin/books?criteria%5Bauthor%5D%5B0%5D=1', $result['filters'][1]['remove_url']);
    }

    public function testRemovingTheLastValueOfAMultipleCriterionRemovesTheCriterion(): void
    {
        $result = $this->extension('/admin/books?criteria[author][]=1&criteria[search]=foo')->getActiveFilters($this->grid());

        self::assertNotNull($result);
        self::assertSame('author', $result['filters'][1]['name']);
        self::assertSame('/admin/books?criteria%5Bsearch%5D=foo', $result['filters'][1]['remove_url']);
    }

    private function grid(): Grid
    {
        $grid = Grid::fromCodeAndDriverConfiguration('app_book', 'doctrine/orm', []);

        $search = Filter::fromNameAndType('search', 'string');
        $search->setLabel('app.ui.search');

        $hidden = Filter::fromNameAndType('hidden', 'string');
        $hidden->setEnabled(false);

        // The label of a filter is its name by default, "false" hides it.
        $isAvailable = Filter::fromNameAndType('isAvailable', 'boolean');
        $isAvailable->setLabel(false);

        foreach ([$search, Filter::fromNameAndType('author', 'entity'), $isAvailable, $hidden] as $filter) {
            $grid->addFilter($filter);
        }

        return $grid;
    }

    private function extension(?string $url, bool $withRoute = true): ActiveFiltersExtension
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
        $translator = new EchoTranslator();

        return new ActiveFiltersExtension(
            new CriterionFormatter($translator),
            $translator,
            $requests,
            new UrlGenerator($routes, new RequestContext()),
        );
    }
}
