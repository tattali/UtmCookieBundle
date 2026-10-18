<?php

declare(strict_types=1);

namespace UtmCookieBundle\Tests\UtmCookie;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use UtmCookieBundle\UtmCookie\UtmCookie;

final class UtmCookieTest extends TestCase
{
    private const EMPTY_VALUES = [
        'utm_campaign' => null,
        'utm_medium' => null,
        'utm_source' => null,
        'utm_term' => null,
        'utm_content' => null,
    ];

    /**
     * clear() calls setcookie(), which needs a process that has not sent any output yet.
     */
    #[RunInSeparateProcess]
    public function testGetReturnsNullValuesAfterClear(): void
    {
        $utmCookie = new UtmCookie();
        $utmCookie->setName('utm');
        $utmCookie->setLifetime(604800);
        $utmCookie->setOverwrite(true);
        // the values init() keeps after reading the query string and the cookie
        $values = new \ReflectionProperty(UtmCookie::class, 'utmCookie');
        $values->setValue($utmCookie, ['utm_source' => 'ads', 'utm_campaign' => 'spring'] + self::EMPTY_VALUES);
        self::assertSame('ads', $utmCookie->get('source'));

        $utmCookie->clear();

        self::assertSame(self::EMPTY_VALUES, $utmCookie->get());
    }
}
