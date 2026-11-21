<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Revisión previa a la entrega, hallazgo C1-10: una persona no puede tener dos solicitudes abiertas para el mismo animal. */
final class Version20261121100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Índice único parcial (solicitante, animal) para las solicitudes abiertas (pendiente, en espera o aprobada).';
    }

    public function up(Schema $schema): void
    {
        $duplicates = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM (SELECT 1 FROM AdoptionApplication WHERE status IN ('pending','waiting','approved') GROUP BY applicant_id, animal_id HAVING COUNT(*) > 1) d"
        );
        $this->abortIf($duplicates > 0, 'Hay solicitantes con más de una solicitud abierta para el mismo animal: resuélvelas antes de aplicar esta migración.');
        // PostgreSQL normaliza el predicado; el mapeo de AdoptionApplication declara la forma normalizada que lee Doctrine.
        $this->addSql("CREATE UNIQUE INDEX uniq_application_open_applicant_animal ON AdoptionApplication (applicant_id, animal_id) WHERE status IN ('pending', 'waiting', 'approved')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_application_open_applicant_animal');
    }
}
