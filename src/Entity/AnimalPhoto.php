<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class AnimalPhoto
{
    #[ORM\Id, ORM\Column(length: 36)] public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] public Animal $animal;
    #[ORM\Column(length: 64)] public string $filename;
    #[ORM\Column(length: 30)] public string $mime;
    public function __construct(Animal $animal, string $filename, string $mime)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->animal = $animal; $this->filename = $filename; $this->mime = $mime;
    }
}
