<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/** Inscripción de una cuenta de voluntario en un turno (HU-07, D29, D30). Una por cuenta y turno; la baja la elimina. */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_shift_signup_volunteer', columns: ['shift_id', 'volunteer_id'])]
class ShiftSignup
{
    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public Shift $shift;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $volunteer;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $signedUpAt;

    public function __construct(Shift $shift, User $volunteer, \DateTimeImmutable $signedUpAt)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->shift = $shift;
        $this->volunteer = $volunteer;
        $this->signedUpAt = $signedUpAt->setTimezone(new \DateTimeZone('UTC'));
    }
}
