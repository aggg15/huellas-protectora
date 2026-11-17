<?php
declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261117090718 extends AbstractMigration
{
    public function getDescription(): string { return 'HU-03: solicitudes de adopción con cuestionario completo y estados comunes.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE AdoptionApplication (id VARCHAR(36) NOT NULL, housingType VARCHAR(20) NOT NULL, housing TEXT NOT NULL, experienceType VARCHAR(20) NOT NULL, experience TEXT NOT NULL, dailyTime VARCHAR(20) NOT NULL, availability TEXT NOT NULL, status VARCHAR(20) NOT NULL, submittedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, animal_id VARCHAR(36) NOT NULL, applicant_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_C98BF1AA8E962C16 ON AdoptionApplication (animal_id)');
        $this->addSql('CREATE INDEX IDX_C98BF1AA97139001 ON AdoptionApplication (applicant_id)');
        $this->addSql('ALTER TABLE AdoptionApplication ADD CONSTRAINT FK_C98BF1AA8E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE AdoptionApplication ADD CONSTRAINT FK_C98BF1AA97139001 FOREIGN KEY (applicant_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT adoption_application_status CHECK (status IN ('pending','waiting','approved','rejected','cancelled'))");
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT adoption_application_housing CHECK (housingType IN ('flat','house_outdoor','house','other') AND btrim(housing) <> '')");
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT adoption_application_experience CHECK (experienceType IN ('none','dogs','cats','both') AND (experienceType = 'none' OR btrim(experience) <> ''))");
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT adoption_application_availability CHECK (dailyTime IN ('lt2','2to4','gt4') AND btrim(availability) <> '')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE AdoptionApplication');
    }
}
