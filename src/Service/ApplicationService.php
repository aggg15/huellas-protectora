<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\{AdoptionApplication, Animal, User};
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/** Presentación de solicitudes de adopción (HU-03, D06 a D08 y D10). */
class ApplicationService
{
    public const MAX_OPEN = 2;
    public const HOUSING_TYPES = [
        'flat' => 'Piso o apartamento',
        'house_outdoor' => 'Casa con patio o jardín',
        'house' => 'Casa sin espacio exterior',
        'other' => 'Otro tipo de vivienda',
    ];
    public const EXPERIENCE_TYPES = [
        'none' => 'Ninguna experiencia previa',
        'dogs' => 'Con perros',
        'cats' => 'Con gatos',
        'both' => 'Con perros y gatos',
    ];
    public const DAILY_TIMES = [
        'lt2' => 'Menos de 2 horas al día',
        '2to4' => 'Entre 2 y 4 horas al día',
        'gt4' => 'Más de 4 horas al día',
    ];
    private const TEXT_LIMIT = 2000;
    private const TEXT_FIELDS = ['housing', 'experience', 'availability'];
    private const ANIMAL_STATUS_LABELS = ['quarantine' => 'en cuarentena', 'available' => 'disponible', 'reserved' => 'reservado', 'adopted' => 'adoptado'];

    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock) {}

    public function openCount(User $applicant): int
    {
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM adoptionapplication WHERE applicant_id = ? AND status IN (?)',
            [$applicant->id, AdoptionApplication::OPEN_STATUSES],
            [1 => ArrayParameterType::STRING],
        );
    }

    /** Motivo por el que no se puede presentar la solicitud ahora mismo, o null. Solo informa: submit() vuelve a comprobarlo bajo bloqueo. */
    public function refusalReason(User $applicant, Animal $animal): ?string
    {
        return $this->refusal($applicant->id, $animal->id, $animal->status);
    }

    /** @return array<string, string> cuestionario normalizado */
    public function validate(array $data): array
    {
        $errors = TextNormalizer::invalidFields($data, self::TEXT_FIELDS);
        $data = TextNormalizer::normalizeAll($data);
        $housingType = $data['housingType'] ?? '';
        $experienceType = $data['experienceType'] ?? '';
        $dailyTime = $data['dailyTime'] ?? '';
        if (!array_key_exists($housingType, self::HOUSING_TYPES)) { $errors['housingType'] = 'Indica el tipo de vivienda.'; }
        if (!isset($errors['housing']) && !$this->validText($data['housing'] ?? '')) { $errors['housing'] = 'Describe la vivienda y quién convive en ella (máximo 2000 caracteres).'; }
        if (!array_key_exists($experienceType, self::EXPERIENCE_TYPES)) { $errors['experienceType'] = 'Indica tu experiencia previa con animales, aunque sea ninguna.'; }
        $experience = $data['experience'] ?? '';
        if (isset($errors['experience'])) {
            // Ya consta el error de codificación del texto.
        } elseif ($experienceType === 'none') {
            if (mb_strlen($experience) > self::TEXT_LIMIT) { $errors['experience'] = 'El comentario sobre la experiencia admite como máximo 2000 caracteres.'; }
        } elseif (!$this->validText($experience)) {
            $errors['experience'] = 'Explica tu experiencia previa con animales (máximo 2000 caracteres).';
        }
        if (!array_key_exists($dailyTime, self::DAILY_TIMES)) { $errors['dailyTime'] = 'Indica el tiempo diario que podrás dedicar al animal.'; }
        if (!isset($errors['availability']) && !$this->validText($data['availability'] ?? '')) { $errors['availability'] = 'Describe tu disponibilidad diaria y quién lo cuidará en tus ausencias (máximo 2000 caracteres).'; }
        if ($errors) { throw new ValidationError($errors); }
        return [
            'housingType' => $housingType, 'housing' => $data['housing'],
            'experienceType' => $experienceType, 'experience' => $experience,
            'dailyTime' => $dailyTime, 'availability' => $data['availability'],
        ];
    }

    /**
     * Registra la solicitud en una única transacción. Se bloquean la fila de la cuenta (FOR NO KEY UPDATE: serializa
     * los envíos de la misma cuenta sin chocar con la inserción con clave foránea de la formalización) y la del animal
     * para que dos envíos simultáneos no superen el máximo de dos abiertas ni lleguen a un animal reservado.
     */
    public function submit(User $applicant, Animal $animal, array $data): AdoptionApplication
    {
        $questionnaire = $this->validate($data);
        return $this->em->wrapInTransaction(function () use ($applicant, $animal, $questionnaire): AdoptionApplication {
            $connection = $this->em->getConnection();
            $connection->executeQuery('SELECT id FROM app_user WHERE id = ? FOR NO KEY UPDATE', [$applicant->id]);
            $status = $connection->fetchOne('SELECT status FROM animal WHERE id = ? FOR UPDATE', [$animal->id]);
            $reason = $this->refusal($applicant->id, $animal->id, is_string($status) ? $status : '');
            if ($reason !== null) { throw new ApplicationRefused($reason); }
            $application = new AdoptionApplication($animal, $applicant, $questionnaire, $this->clock->now()->setTimezone(new \DateTimeZone('UTC')));
            $this->em->persist($application);
            return $application;
        });
    }

    private function refusal(string $applicantId, string $animalId, string $animalStatus): ?string
    {
        if ($animalStatus === 'reserved') { return 'Este animal ya no está disponible: está reservado para otra candidatura.'; }
        if ($animalStatus === 'adopted') { return 'Este animal ya no está disponible: ya ha sido adoptado.'; }
        if ($animalStatus !== 'available') { return 'Este animal ya no está disponible para recibir solicitudes.'; }
        $connection = $this->em->getConnection();
        $open = $connection->fetchAllAssociative(
            'SELECT animal_id FROM adoptionapplication WHERE applicant_id = ? AND status IN (?)',
            [$applicantId, AdoptionApplication::OPEN_STATUSES],
            [1 => ArrayParameterType::STRING],
        );
        if (in_array($animalId, array_column($open, 'animal_id'), true)) { return 'Ya tienes una solicitud abierta para este animal.'; }
        if (count($open) >= self::MAX_OPEN) {
            return 'Ya tienes dos solicitudes abiertas. Solo podrás presentar otra cuando alguna se resuelva o se cancele.';
        }
        return null;
    }

    private function validText(string $value): bool { return $value !== '' && mb_strlen($value) <= self::TEXT_LIMIT; }

    private function assertUndecided(AdoptionApplication $application): void
    {
        if (!$application->isUndecided()) {
            throw new ApplicationRefused('La solicitud ya está resuelta (' . mb_strtolower($application->statusLabel()) . ') y no admite cambios.');
        }
    }

    /**
     * Ejecuta una decisión sobre una solicitud (entrevista, rechazo o aprobación) en una única transacción.
     * Bloquea primero el animal y después la solicitud, como formalize() y cancelReservation(), y recarga ambos
     * bajo bloqueo: una decisión simultánea sobre la misma solicitud ya ha podido cambiar su estado desde que
     * la coordinadora abrió la ficha. `$work` recibe el estado del animal leído bajo bloqueo y valida antes de mutar.
     * Los rechazos se lanzan al terminar la transacción para no dejar cerrado el gestor de entidades.
     */
    private function decide(AdoptionApplication $application, callable $work): void
    {
        $failure = $this->em->wrapInTransaction(function () use ($application, $work): ?\DomainException {
            $connection = $this->em->getConnection();
            $animalStatus = $connection->fetchOne('SELECT status FROM animal WHERE id = ? FOR UPDATE', [$application->animal->id]);
            $exists = $connection->fetchOne('SELECT id FROM adoptionapplication WHERE id = ? FOR UPDATE', [$application->id]);
            if ($exists === false) { return new ApplicationRefused('La solicitud no existe.'); }
            $this->em->refresh($application->animal);
            $this->em->refresh($application);
            try {
                $work(is_string($animalStatus) ? $animalStatus : '');
            } catch (ValidationError|ApplicationRefused $error) {
                return $error;
            }
            $this->em->flush();
            return null;
        });
        if ($failure !== null) { throw $failure; }
    }

    public function recordInterview(AdoptionApplication $application, array $data): void
    {
        $this->decide($application, function () use ($application, $data): void {
            $this->assertUndecided($application);
            $errors = TextNormalizer::invalidFields($data, ['interviewAt', 'interviewNotes']);
            $data = TextNormalizer::normalizeAll($data);
            $madrid = new \DateTimeZone('Europe/Madrid');
            $rawDate = $data['interviewAt'] ?? '';
            $interviewAt = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $rawDate, $madrid);
            if (isset($errors['interviewAt'])) {
                // Ya consta el error de codificación del texto.
            } elseif (!$interviewAt || $interviewAt->format('Y-m-d\TH:i') !== $rawDate) {
                $errors['interviewAt'] = 'Indica una fecha y hora válidas para la entrevista.';
            } elseif ($interviewAt->getTimestamp() < intdiv($application->submittedAt->getTimestamp(), 60) * 60) {
                // El formulario trabaja al minuto: una entrevista en el minuto del envío es válida.
                $errors['interviewAt'] = 'La entrevista no puede ser anterior al envío de la solicitud ('
                    . $application->submittedAt->setTimezone($madrid)->format('d/m/Y H:i') . ').';
            }
            $notes = $data['interviewNotes'] ?? '';
            if (!isset($errors['interviewNotes']) && ($notes === '' || mb_strlen($notes) > 5000)) {
                $errors['interviewNotes'] = 'Escribe las observaciones de la entrevista (máximo 5000 caracteres).';
            }
            if ($errors) {
                throw new ValidationError($errors);
            }
            $application->interviewAt = $interviewAt->setTimezone(new \DateTimeZone('UTC'));
            $application->interviewNotes = $notes;
        });
    }

    public function reject(AdoptionApplication $application, array $data): void
    {
        $this->decide($application, function () use ($application, $data): void {
            $this->assertUndecided($application);
            $invalid = TextNormalizer::invalidFields($data, ['rejectionReason']);
            if ($invalid) { throw new ValidationError($invalid); }
            $reason = TextNormalizer::normalize($data['rejectionReason'] ?? '');
            if ($reason === '' || mb_strlen($reason) > 2000) {
                throw new ValidationError(['rejectionReason' => 'Indica el motivo justificado del rechazo (máximo 2000 caracteres).']);
            }
            $application->status = 'rejected';
            $application->rejectionReason = $reason;
            $application->decisionAt = $this->clock->now()->setTimezone(new \DateTimeZone('UTC'));
        });
    }

    public function approve(AdoptionApplication $application): void
    {
        $this->decide($application, function (string $animalStatus) use ($application): void {
            $this->assertUndecided($application);
            $now = $this->clock->now();
            if (!$application->hasValidInterview($now)) {
                throw new ValidationError([
                    'interview' => 'Para aprobar la solicitud es imprescindible haber realizado y registrado previamente la entrevista (con fecha no futura).',
                ]);
            }
            if ($animalStatus !== 'available') {
                throw new ApplicationRefused($animalStatus === 'reserved'
                    ? 'El animal ya está reservado para otra candidatura.'
                    : 'El animal ya no está disponible para reserva (estado actual: ' . (self::ANIMAL_STATUS_LABELS[$animalStatus] ?? 'desconocido') . ').');
            }
            $connection = $this->em->getConnection();
            $alreadyApproved = (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM adoptionapplication WHERE animal_id = ? AND status = 'approved' AND id <> ?",
                [$application->animal->id, $application->id]
            );
            if ($alreadyApproved > 0) {
                throw new ApplicationRefused('Ya existe otra candidatura aprobada para este animal.');
            }

            $application->status = 'approved';
            $application->decisionAt = $now->setTimezone(new \DateTimeZone('UTC'));
            $application->animal->status = 'reserved';

            $connection->executeStatement(
                "UPDATE adoptionapplication SET status = 'waiting' WHERE animal_id = ? AND status = 'pending' AND id <> ?",
                [$application->animal->id, $application->id]
            );
        });
    }

    public function cancelReservation(AdoptionApplication $application, array $data): void
    {
        if ($application->status !== 'approved') {
            throw new ValidationError(['cancellationReason' => 'Solo se puede liberar la reserva de una solicitud previamente aprobada.']);
        }
        $invalid = TextNormalizer::invalidFields($data, ['cancellationReason']);
        if ($invalid) { throw new ValidationError($invalid); }
        $reason = TextNormalizer::normalize($data['cancellationReason'] ?? '');
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new ValidationError(['cancellationReason' => 'Indica el motivo de la cancelación de la reserva (máximo 2000 caracteres).']);
        }

        $this->em->wrapInTransaction(function () use ($application, $reason): void {
            $connection = $this->em->getConnection();
            // Se decide con los estados leídos bajo bloqueo (HU-05, D17): una formalización simultánea
            // puede haber cerrado la reserva después de la comprobación previa.
            $animalStatus = $connection->fetchOne('SELECT status FROM animal WHERE id = ? FOR UPDATE', [$application->animal->id]);
            $applicationStatus = $connection->fetchOne('SELECT status FROM adoptionapplication WHERE id = ? FOR UPDATE', [$application->id]);
            if ($applicationStatus !== 'approved' || $animalStatus !== 'reserved') {
                throw new ApplicationRefused('La reserva ya no está activa (la adopción puede haberse formalizado); no se ha liberado.');
            }

            $application->status = 'cancelled';
            $application->cancellationReason = $reason;
            $application->animal->status = 'available';

            $connection->executeStatement(
                "UPDATE adoptionapplication SET status = 'pending' WHERE animal_id = ? AND status = 'waiting'",
                [$application->animal->id]
            );

            $this->em->flush();
        });
    }
}
