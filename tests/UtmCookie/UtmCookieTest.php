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

    public function testGetReturnsNullValuesWithoutUtmInTheRequest(): void
    {
        $utmCookie = new UtmCookie();
        $utmCookie->setName('utm');
        $utmCookie->setLifetime(604800);
        $utmCookie->setOverwrite(true);

        self::assertSame(self::EMPTY_VALUES, $utmCookie->get());
        self::assertNull($utmCookie->get('source'));
    }

    /**
     * The values are sanitized as FILTER_SANITIZE_STRING did before PHP 8.1 deprecated it:
     * compare both on many generated strings, wherever PHP still has the filter.
     */
    public function testSanitizeGivesTheSameResultAsTheDeprecatedFilter(): void
    {
        if (!\defined('FILTER_SANITIZE_STRING')) {
            self::markTestSkipped('This PHP version no longer has FILTER_SANITIZE_STRING.');
        }
        // silenced: the constant is deprecated since PHP 8.1
        $filter = @\constant('FILTER_SANITIZE_STRING');
        $sanitize = \Closure::bind(static function (string $value): string {
            return UtmCookie::sanitize($value);
        }, null, UtmCookie::class);

        $pieces = ['<', '>', ' ', "\t", "\n", "\x0B", "\f", "\r", "\0", "\xA0", '"', "'", '!', '?', '-', '/', '=', '&', '\\', 'a', 'é', '<!--', '-->', '<?', '?>', '<b>', '</b>', '<script>'];
        mt_srand(42);
        for ($i = 0; $i < 20000; ++$i) {
            $value = '';
            for ($length = mt_rand(0, 12); $length > 0; --$length) {
                $value .= $pieces[mt_rand(0, \count($pieces) - 1)];
            }

            self::assertSame(@filter_var($value, $filter), $sanitize($value), 'Input: '.json_encode(bin2hex($value)));
        }
    }
}
