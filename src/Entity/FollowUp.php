<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/** Seguimiento programado de una adopción, al mes o a los seis meses de la entrega (HU-05, D16). */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_follow_up_adoption_kind', columns: ['adoption_id', 'kind'])]
class FollowUp
{
    /** Meses naturales desde la entrega para cada seguimiento. */
    public const KINDS = ['one_month' => 1, 'six_months' => 6];
    public const KIND_LABELS = ['one_month' => 'Seguimiento al mes', 'six_months' => 'Seguimiento a los seis meses'];
    public const STATUS_LABELS = ['scheduled' => 'Programado'];

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne(inversedBy: 'followUps'), ORM\JoinColumn(nullable: false)]
    public Adoption $adoption;
    #[ORM\Column(length: 20)]
    public string $kind;
    /** Fecha del calendario de Europe/Madrid, sin hora. */
    #[ORM\Column(type: 'date_immutable')]
    public \DateTimeImmutable $dueOn;
    #[ORM\Column(length: 20)]
    public string $status = 'scheduled';

    public function __construct(Adoption $adoption, string $kind, \DateTimeImmutable $dueOn)
    {
        if (!array_key_exists($kind, self::KINDS)) { throw new \InvalidArgumentException('Tipo de seguimiento desconocido: '.$kind); }
        $this->id = Uuid::v7()->toRfc4122();
        $this->adoption = $adoption;
        $this->kind = $kind;
        $this->dueOn = $dueOn;
    }

    public function kindLabel(): string { return self::KIND_LABELS[$this->kind]; }
    public function statusLabel(): string { return self::STATUS_LABELS[$this->status]; }
}
