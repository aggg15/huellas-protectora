<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class VeterinaryReview
{
    #[ORM\Id, ORM\Column(length: 36)] public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)] public Animal $animal;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)] public User $veterinarian;
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $reviewedAt;
    #[ORM\Column(type: 'text')] public string $notes;
    public function __construct(Animal $animal, User $vet, \DateTimeImmutable $at, string $notes)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->animal = $animal; $this->veterinarian = $vet; $this->reviewedAt = $at; $this->notes = $notes;
    }
}
