<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\{Shift, ShiftSignup, User};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Turnos de paseo y limpieza (HU-07, D26 a D33). Los datos no válidos lanzan ValidationError (422) y las reglas de
 * estado, plazas o coincidencias, ShiftRefused (409). Las decisiones se toman con lo leído después del bloqueo.
 * Orden de bloqueo: cuenta del voluntario → turno (inscripción); solo el turno al modificar o dar de baja.
 */
class ShiftService
{
    public const ZONE = 'Europe/Madrid';

    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock) {}

    public function now(): \DateTimeImmutable
    {
        return $this->clock->now();
    }

    /** Crea un turno (D27). */
    public function create(User $coordinator, array $data): Shift
    {
        $values = $this->validate($data, null);
        return $this->em->wrapInTransaction(function () use ($coordinator, $values): Shift {
            $shift = new Shift($values['kind'], $values['startsAt'], $values['endsAt'], $values['min'], $values['max'], $coordinator, $this->now());
            $this->em->persist($shift);
            $this->em->flush();
            return $shift;
        });
    }

    /** Modifica un turno que no ha empezado (D28). */
    public function update(Shift $shift, array $data): void
    {
        $values = $this->validate($data, $shift);
        $this->em->wrapInTransaction(function () use ($shift, $values): void {
            $connection = $this->em->getConnection();
            $row = $connection->fetchAssociative('SELECT kind, startsAt, endsAt FROM shift WHERE id = ? FOR UPDATE', [$shift->id]);
            if ($row === false) { throw new ShiftRefused('El turno ya no existe.'); }
            if (ShiftCoverage::hasStarted(self::utc($row['startsat']), $this->now())) {
                throw new ShiftRefused('El turno ya ha empezado; no se puede modificar.');
            }
            $signups = $this->countSignups($shift->id);
            if ($values['max'] < $signups) {
                throw new ShiftRefused(sprintf('Hay %d %s en el turno: el máximo no puede ser menor.', $signups, $signups === 1 ? 'persona inscrita' : 'personas inscritas'));
            }
            $sameTimes = self::sameMinute($values['startsAt'], self::utc($row['startsat'])) && self::sameMinute($values['endsAt'], self::utc($row['endsat']));
            if (($values['kind'] !== $row['kind'] || !$sameTimes) && $signups > 0) {
                throw new ShiftRefused('El turno ya tiene inscripciones: no se pueden cambiar su tipo, su fecha ni sus horas. Solo el mínimo y el máximo.');
            }
            if ($sameTimes) {
                // El formulario trabaja con minutos: si las horas no cambian, se conservan las guardadas.
                [$values['startsAt'], $values['endsAt']] = [self::utc($row['startsat']), self::utc($row['endsat'])];
            }
            // Por SQL y con todos los campos: una entidad cargada antes de otro cambio no escribiría los valores que coinciden con su copia.
            $connection->executeStatement(
                'UPDATE shift SET kind = ?, startsAt = ?, endsAt = ?, minVolunteers = ?, maxVolunteers = ? WHERE id = ?',
                [$values['kind'], self::db($values['startsAt']), self::db($values['endsAt']), $values['min'], $values['max'], $shift->id],
            );
            $this->em->refresh($shift);
        });
    }

    /** Inscribe al voluntario: el turno no ha empezado, tiene plazas, no figura ya y no coincide con otro suyo (D29, D33). */
    public function signUp(Shift $shift, User $volunteer): ShiftSignup
    {
        try {
            return $this->em->wrapInTransaction(function () use ($shift, $volunteer): ShiftSignup {
                $connection = $this->em->getConnection();
                $connection->fetchOne('SELECT id FROM app_user WHERE id = ? FOR UPDATE', [$volunteer->id]);
                $row = $connection->fetchAssociative('SELECT startsAt, endsAt, maxVolunteers FROM shift WHERE id = ? FOR UPDATE', [$shift->id]);
                if ($row === false) { throw new ShiftRefused('El turno ya no existe.'); }
                $startsAt = self::utc($row['startsat']);
                $endsAt = self::utc($row['endsat']);
                if (ShiftCoverage::hasStarted($startsAt, $this->now())) {
                    throw new ShiftRefused('El turno ya ha empezado: ya no admite inscripciones.');
                }
                if ((int) $connection->fetchOne('SELECT COUNT(*) FROM shiftsignup WHERE shift_id = ? AND volunteer_id = ?', [$shift->id, $volunteer->id]) > 0) {
                    throw new ShiftRefused('Ya figuras en este turno.');
                }
                $max = (int) $row['maxvolunteers'];
                if ($this->countSignups($shift->id) >= $max) {
                    throw new ShiftRefused(sprintf('El turno ya tiene cubierto su máximo de %d %s.', $max, $max === 1 ? 'persona' : 'personas'));
                }
                $clash = $connection->fetchAssociative(
                    'SELECT s.kind, s.startsAt, s.endsAt FROM shiftsignup su JOIN shift s ON s.id = su.shift_id
                     WHERE su.volunteer_id = ? AND s.id <> ? AND s.startsAt < ? AND s.endsAt > ? ORDER BY s.startsAt LIMIT 1',
                    [$volunteer->id, $shift->id, self::db($endsAt), self::db($startsAt)],
                );
                if ($clash !== false) {
                    throw new ShiftRefused('Coincide con otro turno en el que figuras: '.self::describe($clash['kind'], self::utc($clash['startsat']), self::utc($clash['endsat'])).'.');
                }
                $signup = new ShiftSignup($shift, $volunteer, $this->now());
                $this->em->persist($signup);
                $this->em->flush();
                return $signup;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ShiftRefused('Ya figuras en este turno.');
        }
    }

    /** Da de baja al voluntario hasta el inicio del turno; la plaza queda libre (D30). */
    public function leave(Shift $shift, User $volunteer): void
    {
        $this->em->wrapInTransaction(function () use ($shift, $volunteer): void {
            $connection = $this->em->getConnection();
            $startsAt = $connection->fetchOne('SELECT startsAt FROM shift WHERE id = ? FOR UPDATE', [$shift->id]);
            if ($startsAt === false) { throw new ShiftRefused('El turno ya no existe.'); }
            if (ShiftCoverage::hasStarted(self::utc($startsAt), $this->now())) {
                throw new ShiftRefused('El turno ya ha empezado: ya no puedes darte de baja.');
            }
            if ($connection->executeStatement('DELETE FROM shiftsignup WHERE shift_id = ? AND volunteer_id = ?', [$shift->id, $volunteer->id]) === 0) {
                throw new ShiftRefused('No figuras en este turno.');
            }
        });
    }

    /**
     * Turnos que no han terminado, por inicio, con su cobertura para quien consulta (D31, D32).
     *
     * @return array{rows: list<array<string, mixed>>, undercovered: int}
     */
    public function board(User $viewer): array
    {
        $now = $this->now();
        $items = $this->em->createQueryBuilder()
            ->select('s', '(SELECT COUNT(su.id) FROM '.ShiftSignup::class.' su WHERE su.shift = s) AS signups')
            ->from(Shift::class, 's')
            ->where('s.endsAt > :now')->setParameter('now', $now->setTimezone(new \DateTimeZone('UTC')))
            ->orderBy('s.startsAt', 'ASC')->addOrderBy('s.id', 'ASC')
            ->getQuery()->getResult();
        $mine = array_flip($this->em->getConnection()->fetchFirstColumn('SELECT shift_id FROM shiftsignup WHERE volunteer_id = ?', [$viewer->id]));
        $mineShifts = array_values(array_filter(array_map(static fn(array $item) => $item[0], $items), static fn(Shift $s) => isset($mine[$s->id])));

        $rows = [];
        $undercovered = 0;
        foreach ($items as $item) {
            /** @var Shift $shift */
            $shift = $item[0];
            $row = $this->coverage($shift, (int) $item['signups'], $now);
            $row['signedUp'] = isset($mine[$shift->id]);
            $row['clash'] = !$row['signedUp'] && !$row['started'] && array_filter(
                $mineShifts,
                static fn(Shift $other) => ShiftCoverage::overlaps($shift->startsAt, $shift->endsAt, $other->startsAt, $other->endsAt),
            ) !== [];
            $row['canSignUp'] = !$row['started'] && !$row['signedUp'] && !$row['full'] && !$row['clash'];
            $row['canLeave'] = $row['signedUp'] && !$row['started'];
            $undercovered += $row['undercovered'] ? 1 : 0;
            $rows[] = $row;
        }
        return ['rows' => $rows, 'undercovered' => $undercovered];
    }

    /** @return array<string, mixed> cobertura de un turno: inscripciones, plazas y estado */
    public function coverage(Shift $shift, int $signups, ?\DateTimeImmutable $now = null): array
    {
        $now ??= $this->now();
        $started = ShiftCoverage::hasStarted($shift->startsAt, $now);
        return [
            'shift' => $shift,
            'signups' => $signups,
            'free' => max(0, $shift->maxVolunteers - $signups),
            'missing' => max(0, $shift->minVolunteers - $signups),
            'started' => $started,
            'full' => $signups >= $shift->maxVolunteers,
            'undercovered' => ShiftCoverage::isUndercovered($shift->startsAt, $signups, $shift->minVolunteers, $now),
        ];
    }

    /** @return list<ShiftSignup> personas inscritas, por orden de inscripción */
    public function signupsOf(Shift $shift): array
    {
        return $this->em->createQueryBuilder()
            ->select('su', 'v')->from(ShiftSignup::class, 'su')->join('su.volunteer', 'v')
            ->where('su.shift = :shift')->setParameter('shift', $shift)
            ->orderBy('su.signedUpAt', 'ASC')->addOrderBy('su.id', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * Valida el formulario de un turno. Las horas son de Europe/Madrid y se guardan en UTC. Las condiciones
     * temporales (después de ahora, hasta un año) solo se exigen al crear o si cambian la fecha o las horas.
     *
     * @return array{kind: string, startsAt: \DateTimeImmutable, endsAt: \DateTimeImmutable, min: int, max: int}
     */
    private function validate(array $data, ?Shift $current): array
    {
        $data = array_map(static fn($value) => is_string($value) ? trim($value) : '', $data);
        $errors = [];
        $kind = $data['kind'] ?? '';
        if (!array_key_exists($kind, Shift::KIND_LABELS)) { $errors['kind'] = 'Elige el tipo de turno: paseo o limpieza.'; }

        $date = $data['date'] ?? '';
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone(self::ZONE));
        $validDate = $day !== false && $day->format('Y-m-d') === $date;
        if (!$validDate) { $errors['date'] = 'Indica una fecha real.'; }
        $startsAt = $validDate ? $this->instant($date, $data['startTime'] ?? '', 'startTime', 'inicio', $errors) : null;
        $endsAt = $validDate ? $this->instant($date, $data['endTime'] ?? '', 'endTime', 'fin', $errors) : null;
        if ($startsAt !== null && $endsAt !== null && $endsAt <= $startsAt) {
            $errors['endTime'] = 'La hora de fin debe ser posterior a la de inicio, el mismo día.';
        }
        $scheduleChanged = $current === null || $startsAt === null || $endsAt === null
            || !self::sameMinute($startsAt, $current->startsAt) || !self::sameMinute($endsAt, $current->endsAt);
        if ($startsAt !== null && $scheduleChanged && !isset($errors['endTime'])) {
            $now = $this->now();
            if ($startsAt <= $now) {
                $errors['date'] = 'El turno debe empezar después de este momento.';
            } elseif ($startsAt > $now->modify('+1 year')) {
                $errors['date'] = 'El turno puede empezar como máximo dentro de un año.';
            }
        }

        $min = filter_var($data['minVolunteers'] ?? '', FILTER_VALIDATE_INT);
        $max = filter_var($data['maxVolunteers'] ?? '', FILTER_VALIDATE_INT);
        if ($min === false || $min < Shift::MIN_PEOPLE || $min > Shift::MAX_PEOPLE) {
            $errors['minVolunteers'] = 'El mínimo es un número entero de personas de 1 a 20.';
        }
        if ($max === false || $max < Shift::MIN_PEOPLE || $max > Shift::MAX_PEOPLE || ($min !== false && $max < $min)) {
            $errors['maxVolunteers'] = 'El máximo es un número entero de personas de 1 a 20, no menor que el mínimo.';
        }
        if ($errors) { throw new ValidationError($errors); }
        return ['kind' => $kind, 'startsAt' => $startsAt, 'endsAt' => $endsAt, 'min' => $min, 'max' => $max];
    }

    /** Instante UTC de una hora HH:MM del día indicado en Madrid, o null con el error del campo. */
    private function instant(string $date, string $time, string $field, string $label, array &$errors): ?\DateTimeImmutable
    {
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            $errors[$field] = sprintf('Indica una hora de %s real, con el formato HH:MM.', $label);
            return null;
        }
        $instant = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$time, new \DateTimeZone(self::ZONE));
        if ($instant === false || $instant->format('Y-m-d H:i') !== $date.' '.$time) {
            $errors[$field] = sprintf('La hora de %s no existe ese día por el cambio de hora.', $label);
            return null;
        }
        // Hora repetida al volver al horario de invierno: PHP elige la segunda; la spec fija la primera (horario de verano).
        $earlier = (new \DateTimeImmutable('@'.($instant->getTimestamp() - 3600)))->setTimezone(new \DateTimeZone(self::ZONE));
        if ($earlier->format('Y-m-d H:i') === $date.' '.$time) { $instant = $earlier; }
        return $instant->setTimezone(new \DateTimeZone('UTC'));
    }

    private function countSignups(string $shiftId): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM shiftsignup WHERE shift_id = ?', [$shiftId]);
    }

    /** «Paseo del 05/12/2026 de 10:00 a 12:00», en hora de Madrid. */
    public static function describe(string $kind, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt): string
    {
        $zone = new \DateTimeZone(self::ZONE);
        return sprintf('%s del %s de %s a %s', Shift::KIND_LABELS[$kind], $startsAt->setTimezone($zone)->format('d/m/Y'), $startsAt->setTimezone($zone)->format('H:i'), $endsAt->setTimezone($zone)->format('H:i'));
    }

    /** Mismo minuto: el formulario no tiene segundos. */
    private static function sameMinute(\DateTimeImmutable $a, \DateTimeImmutable $b): bool
    {
        return intdiv($a->getTimestamp(), 60) === intdiv($b->getTimestamp(), 60);
    }

    private static function utc(string $value): \DateTimeImmutable
    {
        return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
    }

    private static function db(\DateTimeImmutable $instant): string
    {
        return $instant->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
