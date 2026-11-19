<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\{Animal, FosterAvailability, FosterHome, FosterNote, FosterPlacement, User};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Casas de acogida, acogidas y novedades (HU-06, D19 a D25). Los datos no válidos lanzan ValidationError
 * (422) y las reglas de estado, disponibilidad o capacidad, FosterRefused (409). Las decisiones se toman
 * con lo leído después del bloqueo. Orden de bloqueo: animal → casa → acogida, como la formalización.
 */
class FosterService
{
    public const MAX_PERIOD_YEARS = 2;

    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock) {}

    public function today(): \DateTimeImmutable
    {
        return AvailabilityCalendar::madridDay($this->clock->now());
    }

    public function homeOf(User $family): ?FosterHome
    {
        return $this->em->getRepository(FosterHome::class)->findOneBy(['family' => $family]);
    }

    /** Crea la casa o cambia su capacidad; nunca por debajo de sus acogidas activas (D20, D23). */
    public function saveCapacity(User $family, array $data): FosterHome
    {
        $raw = is_string($data['capacity'] ?? null) ? trim($data['capacity']) : '';
        $capacity = filter_var($raw, FILTER_VALIDATE_INT);
        if ($capacity === false || $capacity < FosterHome::MIN_CAPACITY || $capacity > FosterHome::MAX_CAPACITY) {
            throw new ValidationError(['capacity' => 'Indica cuántos animales puedes atender a la vez: un número entero de 1 a 10.']);
        }
        try {
            return $this->em->wrapInTransaction(function () use ($family, $capacity): FosterHome {
                $home = $this->homeOf($family);
                if ($home === null) {
                    $home = new FosterHome($family, $capacity, $this->clock->now());
                    $this->em->persist($home);
                    $this->em->flush();
                    return $home;
                }
                $connection = $this->em->getConnection();
                $connection->fetchOne('SELECT id FROM fosterhome WHERE id = ? FOR UPDATE', [$home->id]);
                $active = $this->activeCount($home);
                if ($capacity < $active) {
                    throw new FosterRefused(sprintf('Ahora atiendes %d %s: la capacidad no puede ser menor. Pide a la coordinadora que finalice antes alguna acogida.', $active, $active === 1 ? 'animal' : 'animales'));
                }
                $home->capacity = $capacity;
                $this->em->flush();
                return $home;
            });
        } catch (UniqueConstraintViolationException) {
            throw new FosterRefused('Tu casa ya se estaba registrando en otra petición. Vuelve a cargar la página.');
        }
    }

    /** Añade un periodo de disponibilidad sin solapes (D20). */
    public function addPeriod(FosterHome $home, array $data): FosterAvailability
    {
        $today = $this->today();
        $errors = [];
        $startsOn = self::parseDay($data['startsOn'] ?? null);
        $endsOn = self::parseDay($data['endsOn'] ?? null);
        $limit = $today->modify('+'.self::MAX_PERIOD_YEARS.' years');
        if ($startsOn === null || $startsOn < $today) {
            $errors['startsOn'] = 'Indica una fecha de inicio real, de hoy en adelante.';
        }
        if ($endsOn === null || ($startsOn !== null && $endsOn < $startsOn) || $endsOn > $limit) {
            $errors['endsOn'] = 'Indica una fecha de fin real, no anterior al inicio y como máximo dentro de dos años.';
        }
        if ($errors) { throw new ValidationError($errors); }

        return $this->em->wrapInTransaction(function () use ($home, $startsOn, $endsOn): FosterAvailability {
            $connection = $this->em->getConnection();
            $connection->fetchOne('SELECT id FROM fosterhome WHERE id = ? FOR UPDATE', [$home->id]);
            $overlap = $connection->fetchOne(
                'SELECT COUNT(*) FROM fosteravailability WHERE home_id = ? AND startsOn <= ? AND endsOn >= ?',
                [$home->id, $endsOn->format('Y-m-d'), $startsOn->format('Y-m-d')],
            );
            if ((int) $overlap > 0) {
                throw new ValidationError(['startsOn' => 'El periodo se solapa con otro periodo de disponibilidad de tu casa.']);
            }
            $period = $home->addPeriod($startsOn, $endsOn);
            $this->em->persist($period);
            $this->em->flush();
            return $period;
        });
    }

    /** Retira un periodo si las acogidas activas siguen cubiertas hasta su fin previsto (D23). */
    public function removePeriod(FosterHome $home, FosterAvailability $period): void
    {
        $today = $this->today();
        $this->em->wrapInTransaction(function () use ($home, $period, $today): void {
            $connection = $this->em->getConnection();
            $connection->fetchOne('SELECT id FROM fosterhome WHERE id = ? FOR UPDATE', [$home->id]);
            $remaining = array_map(
                static fn(array $row) => [new \DateTimeImmutable($row['startson']), new \DateTimeImmutable($row['endson'])],
                $connection->fetchAllAssociative('SELECT startsOn, endsOn FROM fosteravailability WHERE home_id = ? AND id <> ?', [$home->id, $period->id]),
            );
            $plannedEnds = $connection->fetchFirstColumn(
                'SELECT plannedEndOn FROM fosterplacement WHERE home_id = ? AND endedOn IS NULL AND plannedEndOn >= ?',
                [$home->id, $today->format('Y-m-d')],
            );
            foreach ($plannedEnds as $plannedEnd) {
                if (!AvailabilityCalendar::covers($remaining, $today, new \DateTimeImmutable($plannedEnd))) {
                    throw new FosterRefused('No puedes retirar este periodo: lo necesita una acogida en curso hasta el '.(new \DateTimeImmutable($plannedEnd))->format('d/m/Y').'. Pide antes a la coordinadora que la finalice.');
                }
            }
            $home->removePeriod($period);
            $this->em->remove($period);
            $this->em->flush();
        });
    }

    /** Registra la acogida de un animal: empieza hoy y termina, como previsto, en `plannedEndOn` (D21, D25). */
    public function place(Animal $animal, User $coordinator, array $data): FosterPlacement
    {
        $today = $this->today();
        $errors = [];
        $homeId = is_string($data['home'] ?? null) ? $data['home'] : '';
        $home = $homeId === '' ? null : $this->em->find(FosterHome::class, $homeId);
        $plannedEndOn = self::parseDay($data['plannedEndOn'] ?? null);
        if ($home === null) { $errors['home'] = 'Selecciona una casa de acogida.'; }
        if ($plannedEndOn === null || $plannedEndOn < $today) { $errors['plannedEndOn'] = 'Indica una fecha de fin prevista real, de hoy en adelante.'; }
        if ($errors) { throw new ValidationError($errors); }

        try {
            return $this->em->wrapInTransaction(function () use ($animal, $home, $coordinator, $today, $plannedEndOn): FosterPlacement {
                $connection = $this->em->getConnection();
                $status = $connection->fetchOne('SELECT status FROM animal WHERE id = ? FOR UPDATE', [$animal->id]);
                $capacity = (int) $connection->fetchOne('SELECT capacity FROM fosterhome WHERE id = ? FOR UPDATE', [$home->id]);
                if ($status === 'adopted') {
                    throw new FosterRefused('El animal ya está adoptado; no se puede registrar una acogida.');
                }
                if ((int) $connection->fetchOne('SELECT COUNT(*) FROM fosterplacement WHERE animal_id = ? AND endedOn IS NULL', [$animal->id]) > 0) {
                    throw new FosterRefused('El animal ya está en una casa de acogida. Finaliza antes esa acogida.');
                }
                $periods = array_map(
                    static fn(array $row) => [new \DateTimeImmutable($row['startson']), new \DateTimeImmutable($row['endson'])],
                    $connection->fetchAllAssociative('SELECT startsOn, endsOn FROM fosteravailability WHERE home_id = ?', [$home->id]),
                );
                if (!AvailabilityCalendar::covers($periods, $today, $plannedEndOn)) {
                    throw new FosterRefused(sprintf('La casa de %s no está disponible todos los días del %s al %s.', $home->family->name, $today->format('d/m/Y'), $plannedEndOn->format('d/m/Y')));
                }
                if ($this->activeCount($home) >= $capacity) {
                    throw new FosterRefused(sprintf('La casa de %s ya atiende su máximo de %d %s.', $home->family->name, $capacity, $capacity === 1 ? 'animal' : 'animales'));
                }
                $placement = new FosterPlacement($animal, $home, $coordinator, $today, $plannedEndOn);
                $this->em->persist($placement);
                // Por SQL: si la entidad en memoria ya tuviera el mismo valor, Doctrine no escribiría el indicador.
                $connection->executeStatement('UPDATE animal SET fostered = TRUE WHERE id = ?', [$animal->id]);
                $this->em->flush();
                $animal->fostered = true;
                return $placement;
            });
        } catch (UniqueConstraintViolationException) {
            throw new FosterRefused('El animal ya está en una casa de acogida. Finaliza antes esa acogida.');
        }
    }

    /** Finaliza hoy una acogida activa; el animal vuelve al refugio (D22). */
    public function end(FosterPlacement $placement): void
    {
        $today = $this->today();
        $this->em->wrapInTransaction(function () use ($placement, $today): void {
            $connection = $this->em->getConnection();
            $connection->fetchOne('SELECT id FROM animal WHERE id = ? FOR UPDATE', [$placement->animal->id]);
            $endedOn = $connection->fetchOne('SELECT endedOn FROM fosterplacement WHERE id = ? FOR UPDATE', [$placement->id]);
            if ($endedOn !== null) { throw new FosterRefused('Esta acogida ya estaba finalizada.'); }
            $placement->endedOn = $today;
            $placement->endReason = 'returned';
            $connection->executeStatement('UPDATE animal SET fostered = FALSE WHERE id = ?', [$placement->animal->id]);
            $this->em->flush();
            $placement->animal->fostered = false;
        });
    }

    /** Novedad de la familia sobre un animal que acoge ahora (D24). */
    public function addNote(FosterPlacement $placement, User $family, array $data): FosterNote
    {
        $body = is_string($data['body'] ?? null) ? trim(str_replace("\r\n", "\n", $data['body'])) : '';
        if ($body === '' || mb_strlen($body) > FosterNote::MAX_LENGTH) {
            throw new ValidationError(['body' => 'Escribe la novedad (máximo 2000 caracteres).']);
        }
        return $this->em->wrapInTransaction(function () use ($placement, $family, $body): FosterNote {
            $endedOn = $this->em->getConnection()->fetchOne('SELECT endedOn FROM fosterplacement WHERE id = ? FOR UPDATE', [$placement->id]);
            if ($endedOn !== null) { throw new FosterRefused('Esta acogida ha finalizado; ya no se pueden anotar novedades.'); }
            $note = new FosterNote($placement, $family, $this->clock->now(), $body);
            $this->em->persist($note);
            $this->em->flush();
            return $note;
        });
    }

    public function activePlacementOf(Animal $animal): ?FosterPlacement
    {
        return $this->em->getRepository(FosterPlacement::class)->findOneBy(['animal' => $animal, 'endedOn' => null]);
    }

    /** @return list<FosterPlacement> acogidas del animal, de la más reciente a la más antigua, con sus novedades */
    public function historyOf(Animal $animal): array
    {
        return $this->em->createQueryBuilder()
            ->select('p', 'h', 'f')->from(FosterPlacement::class, 'p')
            ->join('p.home', 'h')->join('h.family', 'f')
            ->where('p.animal = :animal')->setParameter('animal', $animal)
            ->orderBy('p.startedOn', 'DESC')->addOrderBy('p.id', 'DESC')
            ->getQuery()->getResult();
    }

    /** @return list<FosterPlacement> */
    public function activePlacementsOf(FosterHome $home): array
    {
        return $this->em->createQueryBuilder()
            ->select('p', 'a')->from(FosterPlacement::class, 'p')->join('p.animal', 'a')
            ->where('p.home = :home')->andWhere('p.endedOn IS NULL')->setParameter('home', $home)
            ->orderBy('p.startedOn', 'ASC')->addOrderBy('a.code', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return list<array{home: FosterHome, occupied: int}> casas con sus acogidas activas, por nombre de la familia */
    public function homesOverview(): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('h', 'f', '(SELECT COUNT(p.id) FROM '.FosterPlacement::class.' p WHERE p.home = h AND p.endedOn IS NULL) AS occupied')
            ->from(FosterHome::class, 'h')->join('h.family', 'f')
            ->orderBy('f.name', 'ASC')->addOrderBy('h.id', 'ASC')
            ->getQuery()->getResult();
        return array_map(static fn(array $row) => ['home' => $row[0], 'occupied' => (int) $row['occupied']], $rows);
    }

    /** @return list<FosterPlacement> todas las acogidas activas, por fin previsto */
    public function activePlacements(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p', 'a', 'h', 'f')->from(FosterPlacement::class, 'p')
            ->join('p.animal', 'a')->join('p.home', 'h')->join('h.family', 'f')
            ->where('p.endedOn IS NULL')
            ->orderBy('p.plannedEndOn', 'ASC')->addOrderBy('a.code', 'ASC')
            ->getQuery()->getResult();
    }

    public function activeCount(FosterHome $home): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM fosterplacement WHERE home_id = ? AND endedOn IS NULL', [$home->id]);
    }

    /** Fecha Y-m-d real (sin desbordes como 2026-02-30) o null. */
    private static function parseDay(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value)) { return null; }
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        return $day !== false && $day->format('Y-m-d') === $value ? $day : null;
    }
}
