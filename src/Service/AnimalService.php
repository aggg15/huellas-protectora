<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\{Animal, AnimalPhoto, User, VeterinaryReview};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

class AnimalService
{
    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock, private PhotoStorage $photos) {}

    public function releaseDueAnimals(): int
    {
        return $this->em->getConnection()->executeStatement(
            "UPDATE animal SET status = 'available' WHERE status = 'quarantine' AND receivedAt <= :cutoff",
            ['cutoff' => $this->clock->now()->setTimezone(new \DateTimeZone('UTC'))->modify('-240 hours')->format('Y-m-d H:i:s')]
        );
    }
    public function create(array $data, array $files): Animal
    {
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $data);
        $errors = [];
        $species = $data['species'] ?? ''; $size = $data['size'] ?? '';
        $age = filter_var($data['ageMonths'] ?? null, FILTER_VALIDATE_INT);
        $origin = trim($data['origin'] ?? '');
        if (!in_array($species, ['dog', 'cat'], true)) { $errors['species'] = 'Selecciona perro o gato.'; }
        if (!in_array($size, ['small', 'medium', 'large'], true)) { $errors['size'] = 'Selecciona un tamaño válido.'; }
        if ($age === false || $age < 0 || $age > 600) { $errors['ageMonths'] = 'La edad debe ser un número de meses entre 0 y 600.'; }
        if ($origin === '' || mb_strlen($origin) > 1000) { $errors['origin'] = 'Indica la procedencia (máximo 1000 caracteres).'; }
        $received = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $data['receivedAt'] ?? '', new \DateTimeZone('Europe/Madrid'));
        if (!$received || $received->format('Y-m-d\TH:i') !== ($data['receivedAt'] ?? '') || $received > $this->clock->now()) {
            $errors['receivedAt'] = 'Indica una fecha de recepción real que no sea futura.';
        }
        if ($errors) { throw new ValidationError($errors); }
        $this->photos->validate($files);
        $animal = new Animal($species, $age, $size, $origin, $received->setTimezone(new \DateTimeZone('UTC')));
        $animal->releaseIfDue($this->clock->now());
        $saved = [];
        try {
            $this->em->wrapInTransaction(function () use ($animal, $files, &$saved): void {
                $this->em->persist($animal);
                foreach ($files as $file) {
                    [$name, $mime] = $this->photos->store($file); $saved[] = $name;
                    $this->em->persist(new AnimalPhoto($animal, $name, $mime));
                }
            });
        } catch (\Throwable $error) {
            foreach ($saved as $name) { $this->photos->remove($name); }
            throw $error;
        }
        return $animal;
    }
    public function review(Animal $animal, User $vet, array $data): void
    {
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $data);
        $notes = trim($data['notes'] ?? '');
        $at = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $data['reviewedAt'] ?? '', new \DateTimeZone('Europe/Madrid'));
        if ($notes === '' || mb_strlen($notes) > 5000) { throw new ValidationError(['notes' => 'Escribe las observaciones (máximo 5000 caracteres).']); }
        if (!$at || $at->format('Y-m-d\TH:i') !== ($data['reviewedAt'] ?? '') || $at > $this->clock->now() || $at < $animal->receivedAt) {
            throw new ValidationError(['reviewedAt' => 'La revisión debe estar entre la recepción y el momento actual.']);
        }
        $this->em->persist(new VeterinaryReview($animal, $vet, $at->setTimezone(new \DateTimeZone('UTC')), $notes));
        $this->em->flush();
    }
}
