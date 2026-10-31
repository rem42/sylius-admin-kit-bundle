<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit;

use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\Rem42SyliusAdminKitBundle;

/**
 * @internal
 */
final class BundleTest extends TestCase
{
    public function testPathIsThePackageRoot(): void
    {
        self::assertFileExists((new Rem42SyliusAdminKitBundle())->getPath() . '/composer.json');
    }
}
