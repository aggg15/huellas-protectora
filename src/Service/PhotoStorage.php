<?php
declare(strict_types=1);
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoStorage
{
    public function __construct(private string $directory) {}
    public function validate(array $files): void
    {
        if (count($files) < 1 || count($files) > 6) { throw new ValidationError(['photos' => 'Adjunta entre una y seis fotografías.']); }
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
                throw new ValidationError(['photos' => 'Cada fotografía debe ser válida y ocupar como máximo 5 MiB.']);
            }
            $image = @getimagesize($file->getPathname());
            if (!$image || !in_array($image['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new ValidationError(['photos' => 'Solo se admiten imágenes reales JPEG, PNG o WebP.']);
            }
        }
    }
    public function store(UploadedFile $file): array
    {
        if (!is_dir($this->directory)) { mkdir($this->directory, 0700, true); }
        $mime = getimagesize($file->getPathname())['mime'];
        $name = bin2hex(random_bytes(24)).'.'.['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$mime];
        $file->move($this->directory, $name);
        return [$name, $mime];
    }
    public function path(string $filename): string { return $this->directory.'/'.$filename; }
    public function remove(string $filename): void { if (is_file($this->path($filename))) { unlink($this->path($filename)); } }
}
