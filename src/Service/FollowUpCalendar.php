<?php
declare(strict_types=1);
namespace App\Service;

/**
 * Fechas de los seguimientos de una adopción (HU-05, D16). Suma meses naturales al día de la entrega
 * en Europe/Madrid; si ese día no existe en el mes de destino, usa el último día del mes. No se usa
 * `modify('+N month')` porque desborda: 31 de enero + 1 mes daría 3 de marzo.
 */
final class FollowUpCalendar
{
    private const ZONE = 'Europe/Madrid';

    public static function dueDate(\DateTimeImmutable $deliveredAt, int $months): \DateTimeImmutable
    {
        if ($months < 1) { throw new \InvalidArgumentException('El seguimiento debe ser al menos un mes después de la entrega.'); }
        $local = $deliveredAt->setTimezone(new \DateTimeZone(self::ZONE));
        $monthIndex = (int) $local->format('Y') * 12 + (int) $local->format('n') - 1 + $months;
        $year = intdiv($monthIndex, 12);
        $month = $monthIndex % 12 + 1;
        $lastDay = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), new \DateTimeZone('UTC')))->format('t');
        $day = min((int) $local->format('j'), $lastDay);
        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day), new \DateTimeZone('UTC'));
    }
}
