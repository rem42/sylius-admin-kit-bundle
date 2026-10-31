<?php

declare(strict_types=1);

namespace App\Grid;

use App\Entity\Book;
use App\Enum\BookCategory;
use Doctrine\ORM\EntityManagerInterface;
use Rem42\SyliusAdminKit\QuickFilter\QuickFilterProviderInterface;

final class CategoryQuickFilterProvider implements QuickFilterProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function getCounts(): array
    {
        /** @var list<array{category: BookCategory|string, count: int|string}> $counts */
        $counts = $this->em->createQueryBuilder()
            ->select('b.category, COUNT(b.id) AS count')
            ->from(Book::class, 'b')
            ->groupBy('b.category')
            ->getQuery()
            ->getScalarResult()
        ;

        $result = [];

        foreach ($counts as $count) {
            $result[$count['category'] instanceof BookCategory ? $count['category']->value : $count['category']] = (int) $count['count'];
        }

        return $result;
    }

    public function getColor(\BackedEnum $case): ?string
    {
        return $case instanceof BookCategory ? $case->color() : null;
    }
}
