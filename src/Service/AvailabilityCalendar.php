<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\FosterAvailability;

/**
 * Días de disponibilidad de las casas de acogida (HU-06, D20, D21). Los periodos y las acogidas son días
 * completos del calendario de Europe/Madrid, representados como fechas a medianoche UTC (mismo convenio
 * que FollowUpCalendar). Se comparan como cadenas Y-m-d para que ninguna zona horaria desplace el día.
 */
final class AvailabilityCalendar
{
    private const ZONE = 'Europe/Madrid';

    /** Día de Europe/Madrid del instante, como fecha sin hora. */
    public static function madridDay(\DateTimeImmutable $instant): \DateTimeImmutable
    {
        return new \DateTimeImmutable($instant->setTimezone(new \DateTimeZone(self::ZONE))->format('Y-m-d'), new \DateTimeZone('UTC'));
    }

    /**
     * ¿Están disponibles todos los días de [from, to]? Une los periodos que se solapan o son consecutivos
     * (inicio ≤ fin anterior + 1 día) y busca un tramo que contenga el intervalo completo.
     *
     * @param iterable<FosterAvailability|array{0: \DateTimeInterface, 1: \DateTimeInterface}> $periods
     */
    public static function covers(iterable $periods, \DateTimeInterface $from, \DateTimeInterface $to): bool
    {
        $ranges = [];
        foreach ($periods as $period) {
            [$start, $end] = $period instanceof FosterAvailability ? [$period->startsOn, $period->endsOn] : $period;
            $ranges[] = [$start->format('Y-m-d'), $end->format('Y-m-d')];
        }
        sort($ranges);
        $from = $from->format('Y-m-d');
        $to = $to->format('Y-m-d');
        $merged = null;
        foreach ($ranges as [$start, $end]) {
            if ($merged !== null && $start <= self::nextDay($merged[1])) {
                $merged[1] = max($merged[1], $end);
                continue;
            }
            if ($merged !== null && $merged[0] <= $from && $to <= $merged[1]) { return true; }
            $merged = [$start, $end];
        }
        return $merged !== null && $merged[0] <= $from && $to <= $merged[1];
    }

    private static function nextDay(string $day): string
    {
        return (new \DateTimeImmutable($day, new \DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d');
    }
}
