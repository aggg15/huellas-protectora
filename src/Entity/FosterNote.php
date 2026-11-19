<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/** Novedad sobre el carácter o la evolución de un animal acogido, escrita por su familia (HU-06, D24). Inmutable. */
#[ORM\Entity]
class FosterNote
{
    public const MAX_LENGTH = 2000;

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne(inversedBy: 'notes'), ORM\JoinColumn(nullable: false)]
    public FosterPlacement $placement;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $author;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $writtenAt;
    #[ORM\Column(type: 'text')]
    public string $body;

    public function __construct(FosterPlacement $placement, User $author, \DateTimeImmutable $writtenAt, string $body)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->placement = $placement;
        $this->author = $author;
        $this->writtenAt = $writtenAt->setTimezone(new \DateTimeZone('UTC'));
        $this->body = $body;
    }
}
