<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'one_approved_per_animal', columns: ['animal_id'], options: ['where' => "((status)::text = 'approved'::text)"])]
#[ORM\UniqueConstraint(name: 'one_open_per_applicant_animal', columns: ['applicant_id','animal_id'], options: ['where' => "((status)::text = ANY ((ARRAY['pending'::character varying, 'waiting'::character varying, 'approved'::character varying])::text[]))"])]
class AdoptionApplication
{
    public const OPEN = ['pending','waiting','approved'];
    public const LABELS = ['pending'=>'Pendiente','waiting'=>'En espera','approved'=>'Aprobada','rejected'=>'Rechazada','cancelled'=>'Cancelada'];
    #[ORM\Id, ORM\Column(length: 36)] public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)] public Animal $animal;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)] public User $applicant;
    #[ORM\Column(type: 'text')] public string $housing;
    #[ORM\Column(type: 'text')] public string $experience;
    #[ORM\Column(type: 'text')] public string $availability;
    #[ORM\Column(length: 20)] public string $status = 'pending';
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $submittedAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] public ?\DateTimeImmutable $interviewAt = null;
    #[ORM\Column(type: 'text')] public string $interviewNotes = '';
    #[ORM\Column(type: 'text')] public string $reason = '';
    #[ORM\ManyToOne] public ?User $decidedBy = null;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] public ?\DateTimeImmutable $decidedAt = null;
    #[ORM\Column(type: 'integer'), ORM\Version] public int $version = 1;
    public function __construct(Animal $animal, User $applicant, array $answers, \DateTimeImmutable $now)
    {
        $this->id=Uuid::v7()->toRfc4122(); $this->animal=$animal; $this->applicant=$applicant;
        $this->housing=$answers['housing']; $this->experience=$answers['experience'];
        $this->availability=$answers['availability']; $this->submittedAt=$now;
    }
    public function label(): string { return self::LABELS[$this->status]; }
}
