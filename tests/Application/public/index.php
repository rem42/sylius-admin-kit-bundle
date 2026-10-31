<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../../../vendor/autoload.php';

$env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
$env = is_string($env) ? $env : 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $env);

if ($debug) {
    umask(0000);
    Debug::enable();
}

// "/shadcn/..." is served by the shadcn kernel, anything else by the bootstrap one.
$request = Request::createFromGlobals();
$kernel = new Kernel($env, $debug, Kernel::getThemeForPath($request->getPathInfo()));
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
