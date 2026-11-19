<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/** Casa de acogida de una cuenta ROLE_FOSTER: capacidad y periodos de disponibilidad (HU-06, D19, D20). */
#[ORM\Entity]
class FosterHome
{
    public const MIN_CAPACITY = 1;
    public const MAX_CAPACITY = 10;

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    /** Una casa por cuenta (D19): la relación uno a uno crea el índice único. */
    #[ORM\OneToOne, ORM\JoinColumn(nullable: false)]
    public User $family;
    #[ORM\Column]
    public int $capacity;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;
    /** @var Collection<int, FosterAvailability> */
    #[ORM\OneToMany(targetEntity: FosterAvailability::class, mappedBy: 'home', cascade: ['persist'])]
    #[ORM\OrderBy(['startsOn' => 'ASC'])]
    private Collection $periods;

    public function __construct(User $family, int $capacity, \DateTimeImmutable $createdAt)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->family = $family;
        $this->capacity = $capacity;
        $this->createdAt = $createdAt->setTimezone(new \DateTimeZone('UTC'));
        $this->periods = new ArrayCollection();
    }

    /** @return list<FosterAvailability> por fecha de inicio */
    public function periods(): array
    {
        $periods = $this->periods->toArray();
        usort($periods, static fn(FosterAvailability $a, FosterAvailability $b) => $a->startsOn <=> $b->startsOn);
        return $periods;
    }

    public function addPeriod(\DateTimeImmutable $startsOn, \DateTimeImmutable $endsOn): FosterAvailability
    {
        $period = new FosterAvailability($this, $startsOn, $endsOn);
        $this->periods->add($period);
        return $period;
    }

    public function removePeriod(FosterAvailability $period): void
    {
        $this->periods->removeElement($period);
    }
}
