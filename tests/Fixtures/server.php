<?php

declare(strict_types=1);

/*
 * Router script for PHP's built-in web server, used by UtmCookieRequestTest.
 *
 * UtmCookie reads the query string and the cookie with filter_input(), which only sees the data
 * of a real request, so the tests send real requests to this script.
 *
 * Path: /{overwrite|merge}/{get|get/<key>|clear}. The response is the JSON of what get() returns,
 * or {"error": "..."} with a 500 status when anything fails, PHP deprecations included.
 */

use UtmCookieBundle\UtmCookie\UtmCookie;

require \dirname(__DIR__, 2).'/vendor/autoload.php';

error_reporting(\E_ALL);
set_error_handler(static function (int $type, string $message, string $file, int $line): bool {
    throw new \ErrorException($message, 0, $type, $file, $line);
});

try {
    $path = explode('/', trim((string) parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH), '/'));

    $utmCookie = new UtmCookie();
    $utmCookie->setName('utm');
    $utmCookie->setLifetime(604800);
    $utmCookie->setOverwrite('merge' !== $path[0]);

    switch ($path[1] ?? '') {
        case 'get':
            $result = $utmCookie->get($path[2] ?? null);
            break;
        case 'clear':
            $utmCookie->init();
            $utmCookie->clear();
            $result = $utmCookie->get();
            break;
        default:
            throw new \LogicException('Unknown path '.$_SERVER['REQUEST_URI']);
    }

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => \get_class($e).': '.$e->getMessage()]);
}
