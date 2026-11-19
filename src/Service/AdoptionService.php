<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\{Adoption, AdoptionApplication, Animal, FollowUp, User};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/** Formalización de la adopción el día de la entrega y programación de los seguimientos (HU-05). */
class AdoptionService
{
    public const MAX_FEE_CENTS = 999999;
    public const ADOPTED_REASON = 'El animal ha sido adoptado';

    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock) {}

    /**
     * Cuota abonada en céntimos (D14): hasta cuatro cifras enteras y dos decimales, con coma o punto,
     * mayor que cero. Se convierte sin coma flotante.
     *
     * @return int céntimos
     */
    public function validate(array $data): int
    {
        $errors = [];
        $fee = is_string($data['fee'] ?? null) ? trim($data['fee']) : '';
        $cents = null;
        if (preg_match('/^(\d{1,4})(?:[.,](\d{1,2}))?$/', $fee, $match) === 1) {
            $cents = (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
        }
        if ($cents === null || $cents < 1 || $cents > self::MAX_FEE_CENTS) {
            $errors['fee'] = 'Indica el importe abonado de la cuota: mayor que 0 y hasta 9 999,99 €, con dos decimales como máximo.';
        }
        if (($data['feePaid'] ?? null) !== '1') {
            $errors['feePaid'] = 'Confirma que se ha abonado la cuota de adopción.';
        }
        if ($errors) { throw new ValidationError($errors); }
        return $cents;
    }

    /**
     * Formaliza la entrega en una única transacción (D12, D15, D17). Bloquea primero el animal y después
     * la solicitud, como approve() y cancelReservation(), y decide con los estados leídos bajo bloqueo.
     */
    public function formalize(AdoptionApplication $application, User $coordinator, array $data): Adoption
    {
        $feeCents = $this->validate($data);
        try {
            return $this->em->wrapInTransaction(function () use ($application, $coordinator, $feeCents): Adoption {
                $connection = $this->em->getConnection();
                $animal = $application->animal;
                $animalStatus = $connection->fetchOne('SELECT status FROM animal WHERE id = ? FOR UPDATE', [$animal->id]);
                $row = $connection->fetchAssociative('SELECT status, animal_id FROM adoptionapplication WHERE id = ? FOR UPDATE', [$application->id]);
                if ($row === false || $row['animal_id'] !== $animal->id) { throw new ApplicationRefused('La solicitud no existe.'); }
                if ($row['status'] !== 'approved') {
                    throw new ApplicationRefused('Solo se puede formalizar una solicitud aprobada (estado actual: '.mb_strtolower(AdoptionApplication::STATUS_LABELS[$row['status']] ?? $row['status']).').');
                }
                if ($animalStatus !== 'reserved') {
                    throw new ApplicationRefused('El animal no está reservado para esta solicitud; no se puede formalizar la entrega.');
                }

                $adoption = new Adoption($application, $coordinator, $this->clock->now(), $feeCents);
                foreach (FollowUp::KINDS as $kind => $months) {
                    $adoption->scheduleFollowUp($kind, FollowUpCalendar::dueDate($adoption->deliveredAt, $months));
                }
                $this->em->persist($adoption);

                $application->status = 'formalized';
                $animal->status = 'adopted';
                $animal->fostered = false;
                $connection->executeStatement(
                    "UPDATE adoptionapplication SET status = 'cancelled', cancellationReason = ? WHERE animal_id = ? AND status IN ('pending','waiting') AND id <> ?",
                    [self::ADOPTED_REASON, $animal->id, $application->id],
                );
                // HU-06 (D25): la acogida activa termina el día de la entrega y libera la plaza de la casa. El
                // indicador se escribe por SQL porque la entidad en memoria puede tenerlo ya a false sin estarlo en la base.
                $connection->executeStatement(
                    "UPDATE fosterplacement SET endedOn = ?, endReason = 'adopted' WHERE animal_id = ? AND endedOn IS NULL",
                    [AvailabilityCalendar::madridDay($adoption->deliveredAt)->format('Y-m-d'), $animal->id],
                );
                $connection->executeStatement('UPDATE animal SET fostered = FALSE WHERE id = ?', [$animal->id]);
                $this->em->flush();
                return $adoption;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ApplicationRefused('Este animal ya tiene una adopción formalizada.');
        }
    }

    /** Adopción única del animal, o null (D18): punto de consulta para HU-08. */
    public function findByAnimal(Animal $animal): ?Adoption
    {
        return $this->em->getRepository(Adoption::class)->findOneBy(['animal' => $animal]);
    }

    public function findForApplication(AdoptionApplication $application): ?Adoption
    {
        return $this->em->getRepository(Adoption::class)->findOneBy(['application' => $application]);
    }

    /** @return list<FollowUp> seguimientos programados, por fecha */
    public function scheduledFollowUps(): array
    {
        return $this->em->createQueryBuilder()
            ->select('f', 'a')
            ->from(FollowUp::class, 'f')
            ->join('f.adoption', 'a')
            ->where("f.status = 'scheduled'")
            ->orderBy('f.dueOn', 'ASC')
            ->addOrderBy('a.animalCode', 'ASC')
            ->getQuery()->getResult();
    }
}
