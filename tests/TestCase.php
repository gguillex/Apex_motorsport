<?php

declare(strict_types=1);

namespace Tests;

/**
 * Mini framework de tests sin dependencias externas.
 * Cada método público que empieza por "test" es un caso de prueba.
 */
abstract class TestCase
{
    public int $assertions = 0;

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

    protected function assertTrue(mixed $value, string $message = ''): void
    {
        $this->assertions++;
        if ($value !== true) {
            throw new AssertionFailed($message ?: 'Se esperaba true, se obtuvo ' . var_export($value, true));
        }
    }

    protected function assertFalse(mixed $value, string $message = ''): void
    {
        $this->assertions++;
        if ($value !== false) {
            throw new AssertionFailed($message ?: 'Se esperaba false, se obtuvo ' . var_export($value, true));
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new AssertionFailed(($message ? "$message\n" : '') .
                'Esperado: ' . var_export($expected, true) . "\nObtenido: " . var_export($actual, true));
        }
    }

    protected function assertNull(mixed $value, string $message = ''): void
    {
        $this->assertSame(null, $value, $message);
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->assertions++;
        if (!array_key_exists($key, $array)) {
            throw new AssertionFailed($message ?: "Falta la clave '$key' en " . json_encode($array, JSON_UNESCAPED_UNICODE));
        }
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailed($message ?: "No se encontró «{$needle}» en la respuesta.");
        }
    }

    protected function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new AssertionFailed($message ?: "No debería aparecer «{$needle}» en la respuesta.");
        }
    }

    /**
     * @param class-string<\Throwable> $class
     */
    protected function assertThrows(string $class, callable $fn, ?string $messageContains = null): void
    {
        $this->assertions++;
        try {
            $fn();
        } catch (\Throwable $e) {
            if (!$e instanceof $class) {
                throw new AssertionFailed("Se esperaba $class, se lanzó " . get_class($e) . ': ' . $e->getMessage());
            }
            if ($messageContains !== null && !str_contains($e->getMessage(), $messageContains)) {
                throw new AssertionFailed("El mensaje «{$e->getMessage()}» no contiene «{$messageContains}».");
            }
            return;
        }
        throw new AssertionFailed("Se esperaba la excepción $class y no se lanzó ninguna.");
    }
}

final class AssertionFailed extends \Exception
{
}
