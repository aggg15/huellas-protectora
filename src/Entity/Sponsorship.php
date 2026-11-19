<?php
declare(strict_types=1);
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Apadrinamiento de un animal por una cuenta pública con una aportación mensual (HU-08, D34 a D41).
 * Activo mientras `endedAt` es nulo; una cuenta tiene como máximo un apadrinamiento activo por animal
 * (índice único parcial, también en la migración). Si el animal se adopta, sigue activo pero queda pendiente
 * de la decisión del padrino, con la aportación en pausa (D38).
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_sponsorship_active', columns: ['animal_id', 'sponsor_id'], options: ['where' => '(endedat IS NULL)'])]
class Sponsorship
{
    public const MIN_CENTS = 1000;
    public const MAX_CENTS = 999999;
    public const END_REASON_LABELS = ['finished' => 'Fin de la colaboración', 'moved' => 'Cambio a otro animal'];

    #[ORM\Id, ORM\Column(length: 36)]
    public string $id;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public Animal $animal;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false)]
    public User $sponsor;
    #[ORM\Column]
    public int $monthlyCents;
    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $startedAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $endedAt = null;
    #[ORM\Column(length: 20, nullable: true)]
    public ?string $endReason = null;
    /** Apadrinamiento anterior cuando el padrino eligió este animal tras la adopción del otro (D39). */
    #[ORM\OneToOne, ORM\JoinColumn(nullable: true)]
    public ?Sponsorship $previous = null;

    public function __construct(Animal $animal, User $sponsor, int $monthlyCents, \DateTimeImmutable $startedAt, ?Sponsorship $previous = null)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->animal = $animal;
        $this->sponsor = $sponsor;
        $this->monthlyCents = $monthlyCents;
        $this->startedAt = $startedAt->setTimezone(new \DateTimeZone('UTC'));
        $this->previous = $previous;
    }

    public function isActive(): bool { return $this->endedAt === null; }

    /** Activo y con el animal adoptado: pendiente de la decisión del padrino (D38). */
    public function isPendingDecision(): bool
    {
        return $this->isActive() && $this->animal->status === 'adopted';
    }

    public function endReasonLabel(): ?string
    {
        return $this->endReason === null ? null : self::END_REASON_LABELS[$this->endReason];
    }

    /** Importe con formato español, p. ej. «15,50 €». */
    public function amountLabel(): string
    {
        return self::formatCents($this->monthlyCents);
    }

    public static function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }
}
