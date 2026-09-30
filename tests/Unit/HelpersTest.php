<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Csrf;
use Tests\TestCase;

final class HelpersTest extends TestCase
{
    public function testEscapesHtml(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
        $this->assertSame('O&#039;Brien', e("O'Brien"));
    }

    public function testFormatsPricesInSpanishStyle(): void
    {
        $this->assertSame("245.000\u{00A0}€", format_price('245000.00'));
        $this->assertSame("2.950.000\u{00A0}€", format_price(2950000));
        $this->assertSame("1.234,50\u{00A0}€", format_price(1234.5));
    }

    public function testFormatsDates(): void
    {
        $this->assertSame('08/06/2026 17:27', format_datetime('2026-06-08 17:27:31'));
    }

    public function testInputStringIgnoresNonScalarValues(): void
    {
        $this->assertSame('hola', input_string(['a' => 'hola'], 'a'));
        $this->assertSame('5', input_string(['a' => 5], 'a'));
        $this->assertSame('', input_string(['a' => ['x']], 'a'));
        $this->assertSame('', input_string([], 'a'));
    }

    public function testFieldErrorIsEscaped(): void
    {
        $html = field_error(['x' => '<b>mal</b>'], 'x');
        $this->assertStringContains('&lt;b&gt;mal&lt;/b&gt;', $html);
        $this->assertSame('', field_error([], 'x'));
    }

    public function testCsrfTokenValidation(): void
    {
        $_SESSION = [];
        $token = Csrf::token();

        $this->assertSame(64, strlen($token));
        $this->assertSame($token, Csrf::token(), 'El token debe ser estable durante la sesión');
        $this->assertTrue(Csrf::isValid($token));
        $this->assertFalse(Csrf::isValid('otro'));
        $this->assertFalse(Csrf::isValid(null));
    }
}
