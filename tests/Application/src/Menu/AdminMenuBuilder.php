<?php

declare(strict_types=1);

namespace App\Menu;

use Knp\Menu\ItemInterface;
use Sylius\AdminUi\Knp\Menu\MenuBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * Same menu in every theme, with the icon set of the theme: lucide for shadcn, tabler for bootstrap.
 */
#[AsDecorator(decorates: 'sylius_admin_ui.knp.menu_builder')]
final class AdminMenuBuilder implements MenuBuilderInterface
{
    private const ICONS = [
        'shadcn' => ['dashboard' => 'lucide:layout-dashboard', 'library' => 'lucide:library', 'submenu' => 'lucide:menu', 'docs' => 'lucide:book-open'],
        'bootstrap' => ['dashboard' => 'tabler:dashboard', 'library' => 'tabler:books', 'submenu' => 'tabler:menu-2', 'docs' => 'tabler:book'],
    ];

    public function __construct(
        private readonly MenuBuilderInterface $menuBuilder,
        private readonly string $theme,
    ) {}

    /**
     * @param array<mixed> $options
     */
    public function createMenu(array $options): ItemInterface
    {
        $menu = $this->menuBuilder->createMenu($options);

        $menu->addChild('dashboard', ['route' => 'sylius_admin_ui_dashboard'])
            ->setLabel('sylius.ui.dashboard')
            ->setLabelAttribute('icon', $this->icon('dashboard'))
        ;

        $library = $menu->addChild('library')
            ->setLabel('app.ui.library')
            ->setLabelAttribute('icon', $this->icon('library'))
        ;
        $library->addChild('books', ['route' => 'app_admin_book_index'])->setLabel('app.ui.books');
        $library->addChild('new_book', ['route' => 'app_admin_book_create'])->setLabel('app.ui.new_book');

        $menu->addChild('section', ['extras' => ['section' => true]])
            ->setLabel('app.ui.section')
        ;
        $subMenu = $menu->addChild('submenu')
            ->setLabel('app.ui.submenu')
            ->setLabelAttribute('icon', $this->icon('submenu'))
        ;
        $subMenu->addChild('section_1', ['extras' => ['header' => true]])->setLabel('app.ui.section_1');
        $subMenu->addChild('item_1', ['route' => 'app_admin_book_index'])->setLabel('app.ui.item_1');

        $menu->addChild('docs', ['uri' => 'https://stack.sylius.com', 'linkAttributes' => ['target' => '_blank']])
            ->setLabel('sylius.ui.documentation')
            ->setLabelAttribute('icon', $this->icon('docs'))
        ;

        return $menu;
    }

    private function icon(string $name): string
    {
        return self::ICONS[$this->theme][$name] ?? self::ICONS['shadcn'][$name];
    }
}
