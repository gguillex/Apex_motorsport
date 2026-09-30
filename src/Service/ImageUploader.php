<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\DomainError;

/**
 * Guarda las fotos de los coches subidas desde el panel de administración.
 *
 * El tipo se comprueba por contenido (no por extensión) y el nombre final se
 * genera aleatoriamente, por lo que no es posible subir scripts ejecutables.
 */
final class ImageUploader
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const PREFIX = 'upload-';

    public function __construct(
        private readonly string $publicPath,
        private readonly string $directory,
        private readonly int $maxBytes,
    ) {
    }

    /** Indica si el formulario incluye un archivo. */
    public static function hasFile(?array $file): bool
    {
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @param array{error:int, size:int, tmp_name:string} $file Entrada de $_FILES.
     * @return string Ruta relativa a public/ de la imagen guardada.
     */
    public function store(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new DomainError(match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo permitido.',
                default => 'No se ha podido subir la imagen.',
            });
        }
        if ($file['size'] > $this->maxBytes) {
            throw new DomainError(sprintf('La imagen no puede superar %d MB.', intdiv($this->maxBytes, 1024 * 1024)));
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;
        if ($extension === null || @getimagesize($file['tmp_name']) === false) {
            throw new DomainError('Formato de imagen no válido. Usa JPG, PNG o WebP.');
        }

        $relative = trim($this->directory, '/') . '/' . self::PREFIX . bin2hex(random_bytes(8)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $this->publicPath . '/' . $relative)) {
            throw new DomainError('No se ha podido guardar la imagen en el servidor.');
        }

        return $relative;
    }

    /** Borra una imagen solo si fue subida por este servicio (nunca las de serie). */
    public function delete(string $relativePath): void
    {
        $directory = trim($this->directory, '/');
        $name = basename($relativePath);

        if (dirname($relativePath) === $directory && str_starts_with($name, self::PREFIX)) {
            $path = $this->publicPath . '/' . $directory . '/' . $name;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
