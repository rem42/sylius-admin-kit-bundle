<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\Grid;

use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\Grid\GridPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
final class GridPageTest extends TestCase
{
    public function testCriteriaOfTheRequest(): void
    {
        self::assertSame(['search' => 'foo'], $this->page('/admin/shelves/3/books?criteria[search]=foo')->criteria());
        self::assertSame([], $this->page('/admin/shelves/3/books')->criteria());
        self::assertSame([], $this->page('/admin/shelves/3/books?criteria=bogus')->criteria());
    }

    public function testThereIsNoPageWithoutARequestOrARoute(): void
    {
        self::assertNull($this->page(null)->criteria());
        self::assertNull($this->page('/admin/shelves/3/books?criteria[search]=foo', withRoute: false)->criteria());
    }

    public function testUrlKeepsTheRouteAndQueryParametersButThePage(): void
    {
        $page = $this->page('/admin/shelves/3/books?limit=25&page=4&criteria[search]=foo');

        self::assertSame('/admin/shelves/3/books?limit=25&criteria%5Bauthor%5D=1', $page->url(['author' => '1']));
        self::assertSame('/admin/shelves/3/books?limit=25', $page->url([]));
    }

    public function testUrlFailsWithoutAPage(): void
    {
        $this->expectException(\LogicException::class);

        $this->page(null)->url([]);
    }

    private function page(?string $url, bool $withRoute = true): GridPage
    {
        $requests = new RequestStack();

        if (null !== $url) {
            $request = Request::create($url);
            $request->attributes->set('_route_params', ['shelf' => '3']);

            if ($withRoute) {
                $request->attributes->set('_route', 'app_book_index');
            }

            $requests->push($request);
        }

        $routes = new RouteCollection();
        $routes->add('app_book_index', new Route('/admin/shelves/{shelf}/books'));

        return new GridPage($requests, new UrlGenerator($routes, new RequestContext()));
    }
}
