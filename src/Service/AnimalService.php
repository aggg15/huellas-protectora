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
        $errors = TextNormalizer::invalidFields($data, ['origin']);
        $species = $data['species'] ?? ''; $size = $data['size'] ?? '';
        $age = filter_var($data['ageMonths'] ?? null, FILTER_VALIDATE_INT);
        $origin = TextNormalizer::normalize($data['origin'] ?? '');
        if (!in_array($species, ['dog', 'cat'], true)) { $errors['species'] = 'Selecciona perro o gato.'; }
        if (!in_array($size, ['small', 'medium', 'large'], true)) { $errors['size'] = 'Selecciona un tamaño válido.'; }
        if ($age === false || $age < 0 || $age > 600) { $errors['ageMonths'] = 'La edad debe ser un número de meses entre 0 y 600.'; }
        if (!isset($errors['origin']) && ($origin === '' || mb_strlen($origin) > 1000)) { $errors['origin'] = 'Indica la procedencia (máximo 1000 caracteres).'; }
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
        $invalid = TextNormalizer::invalidFields($data, ['notes']);
        if ($invalid) { throw new ValidationError($invalid); }
        $notes = TextNormalizer::normalize($data['notes'] ?? '');
        $at = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $data['reviewedAt'] ?? '', new \DateTimeZone('Europe/Madrid'));
        if ($notes === '' || mb_strlen($notes) > 5000) { throw new ValidationError(['notes' => 'Escribe las observaciones (máximo 5000 caracteres).']); }
        if (!$at || $at->format('Y-m-d\TH:i') !== ($data['reviewedAt'] ?? '') || $at > $this->clock->now() || $at < $animal->receivedAt) {
            throw new ValidationError(['reviewedAt' => 'La revisión debe estar entre la recepción y el momento actual.']);
        }
        $this->em->persist(new VeterinaryReview($animal, $vet, $at->setTimezone(new \DateTimeZone('UTC')), $notes));
        $this->em->flush();
    }

    public function findAvailableAnimals(array $filters = []): array
    {
        $this->releaseDueAnimals();
        $qb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(Animal::class, 'a')
            ->where("a.status = 'available'")
            ->orderBy('a.receivedAt', 'DESC');

        $species = $filters['species'] ?? null;
        if (in_array($species, ['dog', 'cat'], true)) {
            $qb->andWhere('a.species = :species')->setParameter('species', $species);
        }

        $size = $filters['size'] ?? null;
        if (in_array($size, ['small', 'medium', 'large'], true)) {
            $qb->andWhere('a.size = :size')->setParameter('size', $size);
        }

        $age = $filters['age'] ?? null;
        if ($age === '<12') {
            $qb->andWhere('a.ageMonths < 12');
        } elseif ($age === '12-83') {
            $qb->andWhere('a.ageMonths BETWEEN 12 AND 83');
        } elseif ($age === '>=84') {
            $qb->andWhere('a.ageMonths >= 84');
        }

        return $qb->getQuery()->getResult();
    }

    public function getPublicAnimal(string $id): ?Animal
    {
        $this->releaseDueAnimals();
        $animal = $this->em->find(Animal::class, $id);
        if ($animal === null || $animal->status !== 'available') {
            return null;
        }
        return $animal;
    }
}
