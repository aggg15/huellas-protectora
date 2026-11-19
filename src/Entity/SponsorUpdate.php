<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Novedad de un animal para sus padrinos, redactada por la coordinadora (HU-08, D37). Inmutable. Cada padrino ve
 * las escritas mientras su apadrinamiento estaba activo. Las notas de acogida (FosterNote) siguen siendo internas.
 */
#[ORM\Entity]
#[ORM\Index(name: 'idx_sponsor_update_animal_written', columns: ['animal_id', 'writtenAt'])]
class SponsorUpdate
{
    public const MAX_LENGTH = 2000;

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public Animal $animal;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $author;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $writtenAt;
    #[ORM\Column(type: 'text')]
    public string $body;

    public function __construct(Animal $animal, User $author, \DateTimeImmutable $writtenAt, string $body)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->animal = $animal;
        $this->author = $author;
        $this->writtenAt = $writtenAt->setTimezone(new \DateTimeZone('UTC'));
        $this->body = $body;
    }
}
