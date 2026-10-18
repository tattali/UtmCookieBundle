<?php

declare(strict_types=1);

namespace UtmCookieBundle\Tests\UtmCookie;

use PHPUnit\Framework\TestCase;

/**
 * Sends real HTTP requests to tests/Fixtures/server.php, run by PHP's built-in web server,
 * because UtmCookie reads the query string and the cookie with filter_input().
 */
final class UtmCookieRequestTest extends TestCase
{
    private const EMPTY_VALUES = [
        'utm_campaign' => null,
        'utm_medium' => null,
        'utm_source' => null,
        'utm_term' => null,
        'utm_content' => null,
    ];

    /**
     * @var resource|null
     */
    private static $server;

    /**
     * @var int
     */
    private static $port = 0;

    public static function setUpBeforeClass(): void
    {
        // ask the system for a free port
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($socket);
        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);
        self::$port = (int) substr($name, (int) strrpos($name, ':') + 1);

        $command = 'exec '.escapeshellarg(\PHP_BINARY).' -S 127.0.0.1:'.self::$port.' '.escapeshellarg(\dirname(__DIR__).'/Fixtures/server.php');
        $server = proc_open($command, [['file', '/dev/null', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        self::assertIsResource($server);
        self::$server = $server;

        for ($i = 0; $i < 100; ++$i) {
            $connection = @fsockopen('127.0.0.1', self::$port);
            if (false !== $connection) {
                fclose($connection);

                return;
            }
            usleep(50000);
        }

        self::fail('The built-in web server did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (\is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        self::$server = null;
    }

    public function testReadsTheValuesFromTheQueryStringAndSavesThemInTheCookie(): void
    {
        $response = $this->request('/overwrite/get?utm_source=google&utm_medium=cpc&page=2');

        $expected = array_merge(self::EMPTY_VALUES, ['utm_medium' => 'cpc', 'utm_source' => 'google']);
        self::assertSame($expected, $response['body']);
        self::assertCount(1, $response['cookies']);
        self::assertStringStartsWith('utm='.rawurlencode((string) json_encode($expected)).'; ', $response['cookies'][0]);
        self::assertStringContainsString('; Max-Age=604800; path=/', $response['cookies'][0]);
    }

    public function testReadsTheValuesFromTheCookieWithoutSendingItAgain(): void
    {
        $response = $this->request('/overwrite/get', ['utm_source' => 'newsletter', 'utm_campaign' => 'spring']);

        self::assertSame(array_merge(self::EMPTY_VALUES, ['utm_campaign' => 'spring', 'utm_source' => 'newsletter']), $response['body']);
        self::assertSame([], $response['cookies']);
    }

    public function testQueryStringValuesReplaceAllCookieValuesWhenOverwriteIsOn(): void
    {
        $response = $this->request('/overwrite/get?utm_medium=email', ['utm_source' => 'newsletter', 'utm_campaign' => 'spring']);

        $expected = array_merge(self::EMPTY_VALUES, ['utm_medium' => 'email']);
        self::assertSame($expected, $response['body']);
        self::assertStringStartsWith('utm='.rawurlencode((string) json_encode($expected)).'; ', $response['cookies'][0]);
    }

    public function testQueryStringValuesAreMergedIntoTheCookieValuesWhenOverwriteIsOff(): void
    {
        $response = $this->request('/merge/get?utm_source=ads', ['utm_source' => 'newsletter', 'utm_campaign' => 'spring']);

        $expected = array_merge(self::EMPTY_VALUES, ['utm_campaign' => 'spring', 'utm_source' => 'ads']);
        self::assertSame($expected, $response['body']);
        self::assertStringStartsWith('utm='.rawurlencode((string) json_encode($expected)).'; ', $response['cookies'][0]);
    }

    public function testStripsTagsAndEncodesQuotes(): void
    {
        $query = http_build_query([
            'utm_campaign' => '<b>spring</b> "sale"',
            'utm_term' => "it's 1 < 2",
            'utm_content' => '< b>x',
        ]);
        $response = $this->request('/merge/get?'.$query, ['utm_source' => '<script>alert("x")</script>news', 'utm_medium' => "<a title='>'>mail"]);

        self::assertSame([
            'utm_campaign' => 'spring &#34;sale&#34;',
            'utm_medium' => '&#39;>mail',
            'utm_source' => 'alert(&#34;x&#34;)news',
            'utm_term' => 'it&#39;s 1 ',
            'utm_content' => 'x',
        ], $response['body']);
    }

    public function testKeepsTheCookieValuesTheFilterTurnsIntoStrings(): void
    {
        // the cookie is JSON written by the bundle, but a client can send anything
        $response = $this->request('/overwrite/get', ['utm_source' => 5, 'utm_term' => true, 'utm_content' => null, 'other' => ['a' => '<i>b</i>']]);

        self::assertSame([
            'utm_campaign' => null,
            'utm_medium' => null,
            'utm_source' => '5',
            'utm_term' => '1',
            'utm_content' => '',
            'other' => ['a' => 'b'],
        ], $response['body']);
    }

    public function testAQueryStringArrayIsReadAsFalse(): void
    {
        $response = $this->request('/overwrite/get?utm_source[]=google');

        self::assertSame(array_merge(self::EMPTY_VALUES, ['utm_source' => false]), $response['body']);
    }

    public function testGetReturnsOneValueByItsKeyWithOrWithoutThePrefix(): void
    {
        self::assertSame('google', $this->request('/overwrite/get/source?utm_source=google')['body']);
        self::assertSame('google', $this->request('/overwrite/get/utm_source?utm_source=google')['body']);
        self::assertNull($this->request('/overwrite/get/medium?utm_source=google')['body']);
    }

    public function testGetThrowsForAnUnknownKey(): void
    {
        $response = $this->request('/overwrite/get/unknown', null, true);

        self::assertSame(500, $response['status']);
        self::assertSame(['error' => 'UnexpectedValueException: Argument $key has unexpected value "utm_unknown". Utm value with key "utm_unknown" does not exists.'], $response['body']);
    }

    public function testClearExpiresTheCookieAndForgetsTheValues(): void
    {
        $response = $this->request('/overwrite/clear', ['utm_source' => 'newsletter']);

        self::assertSame(self::EMPTY_VALUES, $response['body']);
        self::assertCount(1, $response['cookies']);
        self::assertStringStartsWith('utm=deleted; expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; path=/', $response['cookies'][0]);
    }

    /**
     * @param array<string, mixed>|null $cookie      the utm cookie, sent as JSON
     * @param bool                      $expectError whether the request must fail with a 500 status
     *
     * @return array{status: int, body: mixed, cookies: list<string>}
     */
    private function request(string $uri, ?array $cookie = null, bool $expectError = false): array
    {
        $connection = fsockopen('127.0.0.1', self::$port);
        self::assertIsResource($connection);
        $request = "GET $uri HTTP/1.0\r\nHost: 127.0.0.1\r\n";
        if (null !== $cookie) {
            $request .= 'Cookie: utm='.rawurlencode((string) json_encode($cookie))."\r\n";
        }
        fwrite($connection, $request."\r\n");
        $response = (string) stream_get_contents($connection);
        fclose($connection);

        [$head, $body] = explode("\r\n\r\n", $response, 2);
        $lines = explode("\r\n", $head);
        $status = (int) explode(' ', $lines[0])[1];
        $cookies = [];
        foreach ($lines as $line) {
            if (0 === stripos($line, 'Set-Cookie: ')) {
                $cookies[] = substr($line, 12);
            }
        }

        if (200 !== $status && !$expectError) {
            self::fail($body);
        }
        $decoded = json_decode($body, true);
        self::assertSame(\JSON_ERROR_NONE, json_last_error(), $body);

        return ['status' => $status, 'body' => $decoded, 'cookies' => $cookies];
    }
}
