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
        $data = array_map(static fn($value) => is_string($value) ? trim($value) : '', $data);
        $errors = [];
        $housingType = $data['housingType'] ?? '';
        $experienceType = $data['experienceType'] ?? '';
        $dailyTime = $data['dailyTime'] ?? '';
        if (!array_key_exists($housingType, self::HOUSING_TYPES)) { $errors['housingType'] = 'Indica el tipo de vivienda.'; }
        if (!$this->validText($data['housing'] ?? '')) { $errors['housing'] = 'Describe la vivienda y quién convive en ella (máximo 2000 caracteres).'; }
        if (!array_key_exists($experienceType, self::EXPERIENCE_TYPES)) { $errors['experienceType'] = 'Indica tu experiencia previa con animales, aunque sea ninguna.'; }
        $experience = $data['experience'] ?? '';
        if ($experienceType === 'none') {
            if (mb_strlen($experience) > self::TEXT_LIMIT) { $errors['experience'] = 'El comentario sobre la experiencia admite como máximo 2000 caracteres.'; }
        } elseif (!$this->validText($experience)) {
            $errors['experience'] = 'Explica tu experiencia previa con animales (máximo 2000 caracteres).';
        }
        if (!array_key_exists($dailyTime, self::DAILY_TIMES)) { $errors['dailyTime'] = 'Indica el tiempo diario que podrás dedicar al animal.'; }
        if (!$this->validText($data['availability'] ?? '')) { $errors['availability'] = 'Describe tu disponibilidad diaria y quién lo cuidará en tus ausencias (máximo 2000 caracteres).'; }
        if ($errors) { throw new ValidationError($errors); }
        return [
            'housingType' => $housingType, 'housing' => $data['housing'],
            'experienceType' => $experienceType, 'experience' => $experience,
            'dailyTime' => $dailyTime, 'availability' => $data['availability'],
        ];
    }

    /**
     * Registra la solicitud en una única transacción. Se bloquean la fila de la cuenta y la del animal
     * para que dos envíos simultáneos no superen el máximo de dos abiertas ni lleguen a un animal reservado.
     */
    public function submit(User $applicant, Animal $animal, array $data): AdoptionApplication
    {
        $questionnaire = $this->validate($data);
        return $this->em->wrapInTransaction(function () use ($applicant, $animal, $questionnaire): AdoptionApplication {
            $connection = $this->em->getConnection();
            $connection->executeQuery('SELECT id FROM app_user WHERE id = ? FOR UPDATE', [$applicant->id]);
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

    public function recordInterview(AdoptionApplication $application, array $data): void
    {
        $data = array_map(static fn($value) => is_string($value) ? trim($value) : '', $data);
        $errors = [];
        $rawDate = $data['interviewAt'] ?? '';
        $interviewAt = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $rawDate, new \DateTimeZone('Europe/Madrid'));
        if (!$interviewAt || $interviewAt->format('Y-m-d\TH:i') !== $rawDate) {
            $errors['interviewAt'] = 'Indica una fecha y hora válidas para la entrevista.';
        }
        $notes = $data['interviewNotes'] ?? '';
        if ($notes === '' || mb_strlen($notes) > 5000) {
            $errors['interviewNotes'] = 'Escribe las observaciones de la entrevista (máximo 5000 caracteres).';
        }
        if ($errors) {
            throw new ValidationError($errors);
        }
        $application->interviewAt = $interviewAt->setTimezone(new \DateTimeZone('UTC'));
        $application->interviewNotes = $notes;
        $this->em->flush();
    }

    public function reject(AdoptionApplication $application, array $data): void
    {
        $reason = trim((string)($data['rejectionReason'] ?? ''));
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new ValidationError(['rejectionReason' => 'Indica el motivo justificado del rechazo (máximo 2000 caracteres).']);
        }
        $application->status = 'rejected';
        $application->rejectionReason = $reason;
        $application->decisionAt = $this->clock->now()->setTimezone(new \DateTimeZone('UTC'));
        $this->em->flush();
    }

    public function approve(AdoptionApplication $application): void
    {
        $now = $this->clock->now();
        if (!$application->hasValidInterview($now)) {
            throw new ValidationError([
                'interview' => 'Para aprobar la solicitud es imprescindible haber realizado y registrado previamente la entrevista (con fecha no futura).',
            ]);
        }

        $this->em->wrapInTransaction(function () use ($application, $now): void {
            $connection = $this->em->getConnection();
            $animalStatus = $connection->fetchOne('SELECT status FROM animal WHERE id = ? FOR UPDATE', [$application->animal->id]);
            if ($animalStatus !== 'available') {
                throw new ApplicationRefused('El animal ya no está disponible para reserva (estado actual: ' . $animalStatus . ').');
            }

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

            $this->em->flush();
        });
    }

    public function cancelReservation(AdoptionApplication $application, array $data): void
    {
        if ($application->status !== 'approved') {
            throw new ValidationError(['cancellationReason' => 'Solo se puede liberar la reserva de una solicitud previamente aprobada.']);
        }
        $reason = trim((string)($data['cancellationReason'] ?? ''));
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new ValidationError(['cancellationReason' => 'Indica el motivo de la cancelación de la reserva (máximo 2000 caracteres).']);
        }

        $this->em->wrapInTransaction(function () use ($application, $reason): void {
            $connection = $this->em->getConnection();
            $connection->executeQuery('SELECT id FROM animal WHERE id = ? FOR UPDATE', [$application->animal->id]);

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
