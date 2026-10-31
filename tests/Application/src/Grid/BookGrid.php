<?php

declare(strict_types=1);

namespace App\Grid;

use App\Entity\Author;
use App\Entity\Book;
use App\Enum\BookCategory;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\ShowAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\Field\DateTimeField;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Bundle\GridBundle\Builder\Filter\BooleanFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\DateFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\EntityFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\EnumFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Sylius\Component\Grid\Attribute\AsGrid;

/**
 * Its fields only use templates of the application (templates/book/grid/), never the ones of a theme:
 * the same grid is rendered by every theme.
 */
#[AsGrid(resourceClass: Book::class, name: 'app_book')]
final class BookGrid
{
    public function __invoke(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder
            ->orderBy('title', 'asc')
            ->setLimits([10, 25, 50])
            ->withFilters(
                StringFilter::create('search', ['title', 'authorName'])->setLabel('sylius.ui.search'),
                EnumFilter::create(name: 'category', enumClass: BookCategory::class, field: 'category')
                    ->addFormOption('choice_label', static fn (BookCategory $choice): string => ucfirst($choice->value))
                    ->addOption('quick_filter', ['provider' => CategoryQuickFilterProvider::class])
                    ->setLabel('app.ui.category'),
                EntityFilter::create('author', Author::class)
                    ->addFormOption('choice_label', 'name')
                    ->setLabel('app.ui.author'),
                BooleanFilter::create('available')->setLabel('app.ui.available'),
                DateFilter::create('createdAt')->setLabel('app.ui.created_at'),
            )
            ->withFields(
                StringField::create('title')->setLabel('app.ui.title')->setSortable(true),
                StringField::create('author')->setPath('author?.name')->setLabel('app.ui.author'),
                TwigField::create('category', 'book/grid/category.html.twig')->setLabel('app.ui.category'),
                TwigField::create('available', 'book/grid/boolean.html.twig')->setLabel('app.ui.available'),
                DateTimeField::create('createdAt', 'd/m/Y')->setLabel('app.ui.created_at')->setSortable(true),
            )
            ->withMainActions(CreateAction::create())
            ->withItemActions(ShowAction::create(), UpdateAction::create(), DeleteAction::create())
            ->withBulkActions(DeleteAction::create())
        ;
    }
}
