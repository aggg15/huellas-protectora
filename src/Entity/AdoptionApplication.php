<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class AdoptionApplication
{
    public const OPEN_STATUSES = ['pending', 'waiting', 'approved'];
    public const STATUS_LABELS = [
        'pending' => 'Pendiente de valoración',
        'waiting' => 'En espera',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'cancelled' => 'Cancelada',
    ];

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public Animal $animal;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $applicant;
    #[ORM\Column(length: 20)]
    public string $housingType;
    #[ORM\Column(type: 'text')]
    public string $housing;
    #[ORM\Column(length: 20)]
    public string $experienceType;
    #[ORM\Column(type: 'text')]
    public string $experience;
    #[ORM\Column(length: 20)]
    public string $dailyTime;
    #[ORM\Column(type: 'text')]
    public string $availability;
    #[ORM\Column(length: 20)]
    public string $status = 'pending';
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $submittedAt;

    public function __construct(Animal $animal, User $applicant, array $questionnaire, \DateTimeImmutable $submittedAt)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->animal = $animal;
        $this->applicant = $applicant;
        $this->housingType = $questionnaire['housingType'];
        $this->housing = $questionnaire['housing'];
        $this->experienceType = $questionnaire['experienceType'];
        $this->experience = $questionnaire['experience'];
        $this->dailyTime = $questionnaire['dailyTime'];
        $this->availability = $questionnaire['availability'];
        $this->submittedAt = $submittedAt;
    }

    public function isOpen(): bool { return in_array($this->status, self::OPEN_STATUSES, true); }
    public function statusLabel(): string { return self::STATUS_LABELS[$this->status]; }
}
