<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\{Adoption, Animal, Sponsorship, SponsorUpdate, User};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Apadrinamientos y novedades para padrinos (HU-08, D34 a D41). Los datos no válidos lanzan ValidationError (422)
 * y las reglas de estado, SponsorshipRefused (409). Las decisiones se toman con lo leído después del bloqueo.
 * Orden de bloqueo: cuenta del padrino → apadrinamiento → animal (alta, cambio y fin); solo el animal al
 * publicar una novedad. La formalización de la adopción (HU-05) bloquea el animal y no la cuenta: sin ciclos. La cuenta se bloquea con
 * FOR NO KEY UPDATE para no chocar con la clave foránea de Adoption hacia ella, sin perder la serialización entre
 * operaciones de la misma cuenta.
 */
class SponsorshipService
{
    /** Roles de las cuentas de trabajo, que no apadrinan (D34). La coordinadora y el resto del personal los tienen asignados directamente. */
    public const WORK_ROLES = ['ROLE_STAFF', 'ROLE_VOLUNTEER', 'ROLE_VET', 'ROLE_COORDINATOR', 'ROLE_FOSTER'];

    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock, private AdoptionService $adoptions) {}

    public function now(): \DateTimeImmutable
    {
        return $this->clock->now();
    }

    /**
     * Aportación mensual en céntimos (D36): euros con hasta cuatro cifras y dos decimales, con coma o punto,
     * de 10,00 € a 9 999,99 €. Se convierte sin coma flotante, como la cuota de HU-05.
     */
    public function amountCents(array $data): int
    {
        $amount = is_string($data['amount'] ?? null) ? trim($data['amount']) : '';
        $cents = null;
        if (preg_match('/^(\d{1,4})(?:[.,](\d{1,2}))?$/', $amount, $match) === 1) {
            $cents = (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
        }
        if ($cents === null || $cents < Sponsorship::MIN_CENTS || $cents > Sponsorship::MAX_CENTS) {
            throw new ValidationError(['amount' => 'Indica una aportación mensual de al menos 10,00 € y hasta 9 999,99 €, con dos decimales como máximo.']);
        }
        return $cents;
    }

    /** Apadrina un animal disponible (D35, D36). Bloquea la cuenta y después el animal. */
    public function sponsor(Animal $animal, User $sponsor, array $data): Sponsorship
    {
        if (array_intersect($sponsor->roles, self::WORK_ROLES) !== []) {
            throw new SponsorshipRefused('Solo las cuentas de padrinos apadrinan animales: el personal y las familias de acogida no lo hacen con su cuenta de trabajo.');
        }
        $cents = $this->amountCents($data);
        try {
            return $this->em->wrapInTransaction(function () use ($animal, $sponsor, $cents): Sponsorship {
                $connection = $this->em->getConnection();
                $connection->fetchOne('SELECT id FROM app_user WHERE id = ? FOR NO KEY UPDATE', [$sponsor->id]);
                $this->lockAvailableAnimal($animal);
                $this->refuseDuplicate($animal, $sponsor);
                $sponsorship = new Sponsorship($animal, $sponsor, $cents, $this->now());
                $this->em->persist($sponsorship);
                $this->em->flush();
                return $sponsorship;
            });
        } catch (UniqueConstraintViolationException) {
            throw new SponsorshipRefused(sprintf('Ya apadrinas a %s.', $animal->code));
        }
    }

    /** Termina un apadrinamiento activo por decisión del padrino, en cualquier momento (D39). */
    public function finish(Sponsorship $sponsorship, User $sponsor): void
    {
        $this->em->wrapInTransaction(function () use ($sponsorship, $sponsor): void {
            $this->lockActive($sponsorship, $sponsor);
            $this->em->getConnection()->executeStatement(
                "UPDATE sponsorship SET endedAt = ?, endReason = 'finished' WHERE id = ?",
                [self::db($this->now()), $sponsorship->id],
            );
            $this->em->refresh($sponsorship);
        });
    }

    /**
     * Traslada la aportación a otro animal disponible elegido por el padrino cuando el suyo ha sido adoptado (D39).
     * En una transacción: termina el actual con «cambio a otro animal» y crea el nuevo, enlazado y con el mismo importe.
     */
    public function moveTo(Sponsorship $sponsorship, Animal $target, User $sponsor): Sponsorship
    {
        try {
            return $this->em->wrapInTransaction(function () use ($sponsorship, $target, $sponsor): Sponsorship {
                $connection = $this->em->getConnection();
                $row = $this->lockActive($sponsorship, $sponsor);
                // La adopción es definitiva (HU-05 no la deshace): basta leer el estado del animal adoptado.
                if ($connection->fetchOne('SELECT status FROM animal WHERE id = ?', [$row['animal_id']]) !== 'adopted') {
                    throw new SponsorshipRefused('Solo puedes cambiar de animal cuando el que apadrinas ha sido adoptado.');
                }
                if ($target->id === $row['animal_id']) {
                    throw new SponsorshipRefused('Elige un animal distinto del que ha sido adoptado.');
                }
                $this->lockAvailableAnimal($target);
                $this->refuseDuplicate($target, $sponsor);
                $now = $this->now();
                $connection->executeStatement(
                    "UPDATE sponsorship SET endedAt = ?, endReason = 'moved' WHERE id = ?",
                    [self::db($now), $sponsorship->id],
                );
                $this->em->refresh($sponsorship);
                $next = new Sponsorship($target, $sponsor, (int) $row['monthlycents'], $now, $sponsorship);
                $this->em->persist($next);
                $this->em->flush();
                return $next;
            });
        } catch (UniqueConstraintViolationException) {
            throw new SponsorshipRefused(sprintf('Ya apadrinas a %s.', $target->code));
        }
    }

    /** Publica una novedad de un animal con padrinos activos (D37). Bloquea el animal. */
    public function addUpdate(Animal $animal, User $coordinator, array $data): SponsorUpdate
    {
        $body = is_string($data['body'] ?? null) ? trim($data['body']) : '';
        if ($body === '' || mb_strlen($body) > SponsorUpdate::MAX_LENGTH) {
            throw new ValidationError(['body' => 'Escribe la novedad para los padrinos (de 1 a 2000 caracteres).']);
        }
        return $this->em->wrapInTransaction(function () use ($animal, $coordinator, $body): SponsorUpdate {
            $connection = $this->em->getConnection();
            if ($connection->fetchOne('SELECT id FROM animal WHERE id = ? FOR UPDATE', [$animal->id]) === false) {
                throw new SponsorshipRefused('El animal ya no existe.');
            }
            if ((int) $connection->fetchOne('SELECT COUNT(*) FROM sponsorship WHERE animal_id = ? AND endedAt IS NULL', [$animal->id]) === 0) {
                throw new SponsorshipRefused(sprintf('%s no tiene padrinos activos: la novedad no llegaría a nadie.', $animal->code));
            }
            $update = new SponsorUpdate($animal, $coordinator, $this->now(), $body);
            $this->em->persist($update);
            $this->em->flush();
            return $update;
        });
    }

    /**
     * Apadrinamientos de la cuenta: activos y pendientes de decisión primero y después los terminados, cada uno
     * con su estado, la adopción si está pendiente y las novedades recibidas mientras estaba activo (D37, D38).
     *
     * @return list<array{sponsorship: Sponsorship, state: string, adoption: ?Adoption, updates: list<SponsorUpdate>, next: ?Sponsorship}>
     */
    public function mine(User $sponsor): array
    {
        /** @var list<Sponsorship> $sponsorships */
        $sponsorships = $this->em->createQueryBuilder()
            ->select('s', 'a')->from(Sponsorship::class, 's')->join('s.animal', 'a')
            ->where('s.sponsor = :sponsor')->setParameter('sponsor', $sponsor)
            ->orderBy('s.startedAt', 'DESC')->addOrderBy('s.id', 'DESC')
            ->getQuery()->getResult();
        $next = [];
        foreach ($sponsorships as $sponsorship) {
            if ($sponsorship->previous !== null) { $next[$sponsorship->previous->id] = $sponsorship; }
        }
        $rows = [];
        foreach ($sponsorships as $sponsorship) {
            $state = self::state($sponsorship);
            $rows[] = [
                'sponsorship' => $sponsorship,
                'state' => $state,
                'adoption' => $state === 'pending' ? $this->adoptions->findByAnimal($sponsorship->animal) : null,
                'updates' => $this->updatesFor($sponsorship),
                'next' => $next[$sponsorship->id] ?? null,
            ];
        }
        usort($rows, static fn(array $a, array $b) => ($a['state'] === 'ended') <=> ($b['state'] === 'ended'));
        return $rows;
    }

    /** «active», «pending» (animal adoptado, a la espera de la decisión) o «ended». */
    public static function state(Sponsorship $sponsorship): string
    {
        return !$sponsorship->isActive() ? 'ended' : ($sponsorship->isPendingDecision() ? 'pending' : 'active');
    }

    /** @return list<SponsorUpdate> novedades del animal escritas mientras el apadrinamiento estaba activo, de la más reciente a la más antigua */
    public function updatesFor(Sponsorship $sponsorship): array
    {
        $query = $this->em->createQueryBuilder()
            ->select('u')->from(SponsorUpdate::class, 'u')
            ->where('u.animal = :animal')->setParameter('animal', $sponsorship->animal)
            ->andWhere('u.writtenAt >= :start')->setParameter('start', $sponsorship->startedAt)
            ->orderBy('u.writtenAt', 'DESC')->addOrderBy('u.id', 'DESC');
        if ($sponsorship->endedAt !== null) {
            $query->andWhere('u.writtenAt <= :end')->setParameter('end', $sponsorship->endedAt);
        }
        return $query->getQuery()->getResult();
    }

    /** @return list<Animal> animales disponibles que la cuenta todavía no apadrina, por código (destinos de D39) */
    public function availableFor(User $sponsor): array
    {
        return $this->em->createQueryBuilder()
            ->select('a')->from(Animal::class, 'a')
            ->where("a.status = 'available'")
            ->andWhere('NOT EXISTS (SELECT s.id FROM '.Sponsorship::class.' s WHERE s.animal = a AND s.sponsor = :sponsor AND s.endedAt IS NULL)')
            ->setParameter('sponsor', $sponsor)
            ->orderBy('a.code', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * Animales con apadrinamientos activos, por código, con sus padrinos y todas sus novedades (D40).
     *
     * @return list<array{animal: Animal, sponsorships: list<Sponsorship>, pending: int, updates: list<SponsorUpdate>}>
     */
    public function overview(): array
    {
        /** @var list<Sponsorship> $active */
        $active = $this->em->createQueryBuilder()
            ->select('s', 'a', 'p')->from(Sponsorship::class, 's')->join('s.animal', 'a')->join('s.sponsor', 'p')
            ->where('s.endedAt IS NULL')
            ->orderBy('a.code', 'ASC')->addOrderBy('s.startedAt', 'ASC')->addOrderBy('s.id', 'ASC')
            ->getQuery()->getResult();
        $groups = [];
        foreach ($active as $sponsorship) {
            $id = $sponsorship->animal->id;
            $groups[$id] ??= ['animal' => $sponsorship->animal, 'sponsorships' => [], 'pending' => 0, 'updates' => []];
            $groups[$id]['sponsorships'][] = $sponsorship;
            $groups[$id]['pending'] += $sponsorship->isPendingDecision() ? 1 : 0;
        }
        if ($groups !== []) {
            $updates = $this->em->createQueryBuilder()
                ->select('u')->from(SponsorUpdate::class, 'u')
                ->where('IDENTITY(u.animal) IN (:animals)')->setParameter('animals', array_keys($groups))
                ->orderBy('u.writtenAt', 'DESC')->addOrderBy('u.id', 'DESC')
                ->getQuery()->getResult();
            foreach ($updates as $update) { $groups[$update->animal->id]['updates'][] = $update; }
        }
        return array_values($groups);
    }

    /** Bloquea el animal y comprueba que está disponible (D35). */
    private function lockAvailableAnimal(Animal $animal): void
    {
        $status = $this->em->getConnection()->fetchOne('SELECT status FROM animal WHERE id = ? FOR UPDATE', [$animal->id]);
        if ($status !== 'available') {
            throw new SponsorshipRefused(sprintf('%s ya no está disponible para apadrinar: solo se apadrinan animales disponibles para adopción.', $animal->code));
        }
    }

    private function refuseDuplicate(Animal $animal, User $sponsor): void
    {
        $count = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM sponsorship WHERE animal_id = ? AND sponsor_id = ? AND endedAt IS NULL',
            [$animal->id, $sponsor->id],
        );
        if ($count > 0) { throw new SponsorshipRefused(sprintf('Ya apadrinas a %s.', $animal->code)); }
    }

    /**
     * Bloquea la cuenta y después el apadrinamiento, y comprueba que es de la cuenta y sigue activo.
     *
     * @return array<string, mixed> fila leída bajo bloqueo
     */
    private function lockActive(Sponsorship $sponsorship, User $sponsor): array
    {
        $connection = $this->em->getConnection();
        $connection->fetchOne('SELECT id FROM app_user WHERE id = ? FOR NO KEY UPDATE', [$sponsor->id]);
        $row = $connection->fetchAssociative('SELECT sponsor_id, animal_id, monthlyCents, endedAt FROM sponsorship WHERE id = ? FOR UPDATE', [$sponsorship->id]);
        if ($row === false || $row['sponsor_id'] !== $sponsor->id) { throw new SponsorshipRefused('El apadrinamiento no existe.'); }
        if ($row['endedat'] !== null) { throw new SponsorshipRefused('Este apadrinamiento ya ha terminado.'); }
        return $row;
    }

    private static function db(\DateTimeImmutable $instant): string
    {
        return $instant->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
