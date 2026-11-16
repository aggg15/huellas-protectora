<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Animal
{
    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\Column(length: 20, unique: true)]
    public string $code;
    #[ORM\Column(length: 10)]
    public string $species;
    #[ORM\Column]
    public int $ageMonths;
    #[ORM\Column(length: 10)]
    public string $size;
    #[ORM\Column(type: 'text')]
    public string $origin;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $receivedAt;
    #[ORM\Column(length: 20)]
    public string $status = 'quarantine';
    #[ORM\Column]
    public bool $fostered = false;

    public function __construct(string $species, int $ageMonths, string $size, string $origin, \DateTimeImmutable $receivedAt)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->code = 'HUE-'.strtoupper(substr(str_replace('-', '', $this->id), -12));
        $this->species = $species;
        $this->ageMonths = $ageMonths;
        $this->size = $size;
        $this->origin = $origin;
        $this->receivedAt = $receivedAt;
    }
    public function releaseAt(): \DateTimeImmutable { return $this->receivedAt->modify('+240 hours'); }
    public function releaseIfDue(\DateTimeImmutable $now): bool
    {
        if ($this->status !== 'quarantine' || $this->releaseAt() > $now) { return false; }
        $this->status = 'available';
        return true;
    }
}
