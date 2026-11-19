<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Turno de paseo o de limpieza del refugio (HU-07, D27, D28). Inicio y fin son instantes UTC del mismo día
 * de Europe/Madrid. Las inscripciones (ShiftSignup) no superan el máximo: se comprueba bajo bloqueo del turno.
 */
#[ORM\Entity]
#[ORM\Index(name: 'idx_shift_starts_at', columns: ['startsAt'])]
class Shift
{
    public const KIND_LABELS = ['walk' => 'Paseo', 'cleaning' => 'Limpieza'];
    public const MIN_PEOPLE = 1;
    public const MAX_PEOPLE = 20;

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\Column(length: 20)]
    public string $kind;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $startsAt;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $endsAt;
    #[ORM\Column]
    public int $minVolunteers;
    #[ORM\Column]
    public int $maxVolunteers;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $createdBy;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    public function __construct(string $kind, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt, int $minVolunteers, int $maxVolunteers, User $createdBy, \DateTimeImmutable $createdAt)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->kind = $kind;
        $this->startsAt = $startsAt->setTimezone(new \DateTimeZone('UTC'));
        $this->endsAt = $endsAt->setTimezone(new \DateTimeZone('UTC'));
        $this->minVolunteers = $minVolunteers;
        $this->maxVolunteers = $maxVolunteers;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt->setTimezone(new \DateTimeZone('UTC'));
    }

    public function kindLabel(): string
    {
        return self::KIND_LABELS[$this->kind];
    }
}
