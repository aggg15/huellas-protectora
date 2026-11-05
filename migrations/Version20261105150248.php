<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261105150248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Solicitudes con cuestionario, relación de solicitante y exclusividad de reserva.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE AdoptionApplication (id VARCHAR(36) NOT NULL, housing TEXT NOT NULL, experience TEXT NOT NULL, availability TEXT NOT NULL, status VARCHAR(20) NOT NULL, submittedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, interviewAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, interviewNotes TEXT NOT NULL, reason TEXT NOT NULL, version INT DEFAULT 1 NOT NULL, animal_id VARCHAR(36) NOT NULL, applicant_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX one_approved_per_animal ON AdoptionApplication (animal_id) WHERE status = \'approved\'');
        $this->addSql('CREATE UNIQUE INDEX one_open_per_applicant_animal ON AdoptionApplication (applicant_id, animal_id) WHERE status IN (\'pending\',\'waiting\',\'approved\')');
        $this->addSql('CREATE INDEX IDX_C98BF1AA8E962C16 ON AdoptionApplication (animal_id)');
        $this->addSql('CREATE INDEX IDX_C98BF1AA97139001 ON AdoptionApplication (applicant_id)');
        $this->addSql('ALTER TABLE AdoptionApplication ADD CONSTRAINT FK_C98BF1AA8E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE AdoptionApplication ADD CONSTRAINT FK_C98BF1AA97139001 FOREIGN KEY (applicant_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT application_status CHECK (status IN ('pending','waiting','approved','rejected','cancelled'))");
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT complete_questionnaire CHECK (length(trim(housing)) > 0 AND length(trim(experience)) > 0 AND length(trim(availability)) > 0)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE AdoptionApplication DROP CONSTRAINT FK_C98BF1AA8E962C16');
        $this->addSql('ALTER TABLE AdoptionApplication DROP CONSTRAINT FK_C98BF1AA97139001');
        $this->addSql('DROP TABLE AdoptionApplication');
    }
}
