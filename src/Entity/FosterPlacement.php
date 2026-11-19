<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Acogida temporal de un animal en una casa (HU-06, D21, D22). Activa mientras `endedOn` es nulo.
 * Un animal tiene como máximo una acogida activa: índice único parcial, también en la migración.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_foster_active_animal', columns: ['animal_id'], options: ['where' => '(endedon IS NULL)'])]
class FosterPlacement
{
    public const END_REASON_LABELS = ['returned' => 'Regreso al refugio', 'adopted' => 'Adopción'];

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public Animal $animal;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public FosterHome $home;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $assignedBy;
    /** Días del calendario de Europe/Madrid, sin hora. */
    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $startedOn;
    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $plannedEndOn;
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    public ?\DateTimeImmutable $endedOn = null;
    #[ORM\Column(length: 20, nullable: true)]
    public ?string $endReason = null;
    /** @var Collection<int, FosterNote> */
    #[ORM\OneToMany(targetEntity: FosterNote::class, mappedBy: 'placement')]
    #[ORM\OrderBy(['writtenAt' => 'DESC'])]
    private Collection $notes;

    public function __construct(Animal $animal, FosterHome $home, User $assignedBy, \DateTimeImmutable $startedOn, \DateTimeImmutable $plannedEndOn)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->animal = $animal;
        $this->home = $home;
        $this->assignedBy = $assignedBy;
        $this->startedOn = $startedOn;
        $this->plannedEndOn = $plannedEndOn;
        $this->notes = new ArrayCollection();
    }

    public function isActive(): bool { return $this->endedOn === null; }

    /** Activa y con el fin previsto ya pasado (D22): sigue activa hasta que se finaliza. */
    public function isOverdue(\DateTimeImmutable $today): bool
    {
        return $this->isActive() && $this->plannedEndOn->format('Y-m-d') < $today->format('Y-m-d');
    }

    public function endReasonLabel(): ?string
    {
        return $this->endReason === null ? null : self::END_REASON_LABELS[$this->endReason];
    }

    /** @return list<FosterNote> de la más reciente a la más antigua */
    public function notes(): array
    {
        $notes = $this->notes->toArray();
        usort($notes, static fn(FosterNote $a, FosterNote $b) => $b->writtenAt <=> $a->writtenAt);
        return $notes;
    }
}
