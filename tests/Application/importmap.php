<?php

declare(strict_types=1);

/*
 * Importmap of the shadcn theme of the demo (the bootstrap theme ships its own precompiled assets):
 * APP_THEME=shadcn php tests/Application/bin/console importmap:install
 */
return [
    'admin' => ['path' => './assets/admin.js', 'entrypoint' => true],
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    '@symfony/stimulus-bundle' => ['path' => '../../vendor/symfony/stimulus-bundle/assets/dist/loader.js'],
    // Symfony UX Autocomplete (its Tom Select stylesheet is replaced by the theme, see assets/controllers.json)
    '@symfony/ux-autocomplete' => ['path' => '../../vendor/symfony/ux-autocomplete/assets/dist/controller.js'],
    'tom-select' => ['version' => '2.4.3'],
    // Dependencies of tom-select (imported by its ESM build)
    '@orchidjs/sifter' => ['version' => '1.1.0'],
    '@orchidjs/unicode-variants' => ['version' => '1.2.2'],
];
