<?php
declare(strict_types=1);
namespace App\Service;

/**
 * Normalización de los textos libres que escribe una persona (HU-01, HU-03 y HU-04): se quitan los espacios
 * Unicode del principio y del final y se rechaza lo que PostgreSQL no puede guardar (byte NUL y UTF-8 inválido),
 * para devolver un error de validación (422) en lugar de un error interno.
 */
final class TextNormalizer
{
    public const INVALID = 'El texto contiene caracteres no admitidos (byte nulo o codificación no válida).';

    /** Texto apto para guardar: UTF-8 válido y sin byte NUL. */
    public static function isClean(string $value): bool
    {
        return mb_check_encoding($value, 'UTF-8') && !str_contains($value, "\0");
    }

    /** Quita los espacios Unicode (`[\s\p{Z}]`) del principio y del final; lo que no es texto cuenta como vacío. */
    public static function normalize(mixed $value): string
    {
        if (!is_string($value)) { return ''; }
        // Para repoblar el formulario o contar longitudes se eliminan antes el NUL y los bytes inválidos.
        $value = str_replace("\0", '', mb_convert_encoding($value, 'UTF-8', 'UTF-8'));
        return preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', $value) ?? '';
    }

    /** @return array<string, string> `campo => mensaje` de los campos de texto con NUL o UTF-8 inválido */
    public static function invalidFields(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (is_string($data[$field] ?? null) && !self::isClean($data[$field])) { $errors[$field] = self::INVALID; }
        }
        return $errors;
    }

    /** Normaliza todos los valores de texto del formulario; lo que no es texto se descarta. */
    public static function normalizeAll(array $data): array
    {
        return array_map(static fn($value) => self::normalize($value), $data);
    }
}
