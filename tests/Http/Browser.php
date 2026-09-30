<?php

declare(strict_types=1);

namespace Tests\Http;

/**
 * Cliente HTTP con cookies propias que simula a un visitante.
 */
final class Browser
{
    private string $cookieFile;

    public int $status = 0;
    public string $body = '';
    public ?string $location = null;
    public string $contentType = '';

    public function __construct(private readonly string $baseUrl)
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'apex-cookies');
    }

    public function __destruct()
    {
        @unlink($this->cookieFile);
    }

    public function get(string $path): self
    {
        return $this->request('GET', $path);
    }

    /** @param array<string, mixed> $fields */
    public function post(string $path, array $fields = []): self
    {
        return $this->request('POST', $path, $fields);
    }

    /** Envía un POST incluyendo automáticamente el token CSRF de la página indicada. */
    public function submit(string $formPage, string $action, array $fields = []): self
    {
        $token = $this->get($formPage)->csrfToken();
        return $this->post($action, $fields + ['_token' => $token]);
    }

    /** Sigue la redirección de la última respuesta. */
    public function follow(): self
    {
        if ($this->location === null) {
            throw new \LogicException('La última respuesta no es una redirección.');
        }
        return $this->get(preg_replace('#^https?://[^/]+/#', '', $this->location));
    }

    public function csrfToken(): string
    {
        if (!preg_match('/name="_token" value="([a-f0-9]{64})"/', $this->body, $m)) {
            throw new \RuntimeException('No se encontró el token CSRF en la página.');
        }
        return $m[1];
    }

    public function redirectsTo(): ?string
    {
        return $this->location === null ? null : preg_replace('#^https?://[^/]+/#', '', $this->location);
    }

    private function request(string $method, string $path, array $fields = []): self
    {
        $ch = curl_init($this->baseUrl . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_TIMEOUT        => 15,
        ]);
        if ($method === 'POST') {
            $hasFiles = (bool) array_filter($fields, static fn ($v) => $v instanceof \CURLFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $hasFiles ? $fields : http_build_query($fields));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException('Error HTTP: ' . curl_error($ch));
        }
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->location = curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: null;
        $this->contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->body = substr($response, $headerSize);
        curl_close($ch);

        // Guarda las cookies en disco para la siguiente petición.
        return $this;
    }
}
