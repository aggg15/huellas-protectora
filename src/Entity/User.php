<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\Column(length: 180, unique: true)]
    public string $email;
    #[ORM\Column(length: 100)]
    public string $name;
    #[ORM\Column]
    public string $password = '';
    #[ORM\Column(type: 'json')]
    public array $roles = [];

    public function __construct(string $email, string $name)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->email = mb_strtolower(trim($email));
        $this->name = trim($name);
    }
    public function getUserIdentifier(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRoles(): array { return array_values(array_unique([...$this->roles, 'ROLE_USER'])); }
    public function eraseCredentials(): void {}
}
