<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/** Periodo de disponibilidad de una casa de acogida: días de Europe/Madrid, inicio y fin incluidos (D20). */
#[ORM\Entity]
class FosterAvailability
{
    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne(inversedBy: 'periods'), ORM\JoinColumn(nullable: false)]
    public FosterHome $home;
    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $startsOn;
    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $endsOn;

    public function __construct(FosterHome $home, \DateTimeImmutable $startsOn, \DateTimeImmutable $endsOn)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->home = $home;
        $this->startsOn = $startsOn;
        $this->endsOn = $endsOn;
    }

    /** Terminado si su último día es anterior a hoy (analyze A2). */
    public function isOver(\DateTimeImmutable $today): bool
    {
        return $this->endsOn->format('Y-m-d') < $today->format('Y-m-d');
    }
}
