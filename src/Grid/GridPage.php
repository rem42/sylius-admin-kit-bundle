<?php

declare(strict_types=1);

namespace Rem42\SyliusAdminKit\Grid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The grid page of the current request: the criteria that filter it, and its URL with other criteria.
 */
final class GridPage
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>|null filter name => criterion, null when there is no page: no request, or no route
     */
    public function criteria(): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || null === self::route($request)) {
            return null;
        }

        $criteria = $request->query->all()['criteria'] ?? [];

        /** @var array<string, mixed> */
        return \is_array($criteria) ? $criteria : [];
    }

    /**
     * URL of the page with the given criteria. Other query parameters (limit, sorting...) are kept, the page number
     * is dropped: it may not exist anymore.
     *
     * @param array<string, mixed> $criteria
     */
    public function url(array $criteria): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $route = null === $request ? null : self::route($request);

        if (null === $request || null === $route) {
            throw new \LogicException('There is no grid page to link to: check that criteria() is not null first.');
        }

        /** @var array<string, mixed> $routeParams */
        $routeParams = $request->attributes->get('_route_params', []);
        $query = array_diff_key($request->query->all(), ['page' => true, 'criteria' => true]);

        return $this->urlGenerator->generate($route, $routeParams + $query + ([] === $criteria ? [] : ['criteria' => $criteria]));
    }

    private static function route(Request $request): ?string
    {
        $route = $request->attributes->get('_route');

        return \is_string($route) ? $route : null;
    }
}
