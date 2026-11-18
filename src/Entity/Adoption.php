<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Adopción formalizada el día de la entrega (HU-05, D12 a D17). Guarda una copia de los datos del
 * contrato para que este no cambie aunque cambien después la cuenta o la ficha del animal (D13).
 */
#[ORM\Entity]
class Adoption
{
    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    /** Una sola adopción por animal (D17): la relación uno a uno crea el índice único. */
    #[ORM\OneToOne, ORM\JoinColumn(nullable: false)]
    public Animal $animal;
    /** Una sola adopción por solicitud (D17). */
    #[ORM\OneToOne, ORM\JoinColumn(nullable: false)]
    public AdoptionApplication $application;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $adopter;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $formalizedBy;
    #[ORM\Column(length: 30, unique: true)]
    public string $contractNumber;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $deliveredAt;
    #[ORM\Column]
    public int $feeCents;
    #[ORM\Column(length: 100)]
    public string $adopterName;
    #[ORM\Column(length: 180)]
    public string $adopterEmail;
    #[ORM\Column(length: 20)]
    public string $animalCode;
    #[ORM\Column(length: 10)]
    public string $animalSpecies;
    #[ORM\Column]
    public int $animalAgeMonths;
    #[ORM\Column(length: 10)]
    public string $animalSize;
    /** @var Collection<int, FollowUp> */
    #[ORM\OneToMany(targetEntity: FollowUp::class, mappedBy: 'adoption', cascade: ['persist'])]
    #[ORM\OrderBy(['dueOn' => 'ASC'])]
    private Collection $followUps;

    public function __construct(AdoptionApplication $application, User $formalizedBy, \DateTimeImmutable $deliveredAt, int $feeCents)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->application = $application;
        $this->animal = $application->animal;
        $this->adopter = $application->applicant;
        $this->formalizedBy = $formalizedBy;
        $this->deliveredAt = $deliveredAt->setTimezone(new \DateTimeZone('UTC'));
        $this->feeCents = $feeCents;
        $this->contractNumber = 'CTR-'.substr($this->animal->code, strlen('HUE-'));
        $this->adopterName = $this->adopter->name;
        $this->adopterEmail = $this->adopter->email;
        $this->animalCode = $this->animal->code;
        $this->animalSpecies = $this->animal->species;
        $this->animalAgeMonths = $this->animal->ageMonths;
        $this->animalSize = $this->animal->size;
        $this->followUps = new ArrayCollection();
    }

    public function scheduleFollowUp(string $kind, \DateTimeImmutable $dueOn): FollowUp
    {
        $followUp = new FollowUp($this, $kind, $dueOn);
        $this->followUps->add($followUp);
        return $followUp;
    }

    /** @return list<FollowUp> */
    public function followUps(): array
    {
        $followUps = $this->followUps->toArray();
        usort($followUps, static fn(FollowUp $a, FollowUp $b) => $a->dueOn <=> $b->dueOn);
        return $followUps;
    }

    /** Importe con formato español, p. ej. «120,00 €». */
    public function feeLabel(): string
    {
        return number_format($this->feeCents / 100, 2, ',', '.').' €';
    }
}
