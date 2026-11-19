<?php
declare(strict_types=1);
namespace App\Service;

/**
 * Cobertura de los turnos (HU-07, D29, D31). Trabaja con instantes: la ventana de 48 horas son 48 horas reales
 * aunque haya un cambio de hora en Europe/Madrid, como la cuarentena de 240 horas (D01).
 */
final class ShiftCoverage
{
    public const WINDOW_HOURS = 48;

    public static function hasStarted(\DateTimeInterface $startsAt, \DateTimeInterface $now): bool
    {
        return $now->getTimestamp() >= $startsAt->getTimestamp();
    }

    /** No ha empezado, no cubre su mínimo y faltan 48 horas o menos para el inicio (R8). */
    public static function isUndercovered(\DateTimeInterface $startsAt, int $signups, int $minVolunteers, \DateTimeInterface $now): bool
    {
        $remaining = $startsAt->getTimestamp() - $now->getTimestamp();
        return $remaining > 0 && $signups < $minVolunteers && $remaining <= self::WINDOW_HOURS * 3600;
    }

    /** Cada intervalo [inicio, fin) empieza antes de que termine el otro; los contiguos no coinciden (R6). */
    public static function overlaps(\DateTimeInterface $startsA, \DateTimeInterface $endsA, \DateTimeInterface $startsB, \DateTimeInterface $endsB): bool
    {
        return $startsA->getTimestamp() < $endsB->getTimestamp() && $startsB->getTimestamp() < $endsA->getTimestamp();
    }
}
