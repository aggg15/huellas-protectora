<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * HU-05: adopción formalizada, contrato y seguimientos. Generada con el diff de Doctrine y completada
 * con los CHECK de cuota, tipo y estado y con el nuevo estado `formalized` de las solicitudes (D14 a D17).
 */
final class Version20261118230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HU-05: tablas Adoption y FollowUp y estado formalized de las solicitudes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE Adoption (id VARCHAR(36) NOT NULL, contractNumber VARCHAR(30) NOT NULL, deliveredAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, feeCents INT NOT NULL, adopterName VARCHAR(100) NOT NULL, adopterEmail VARCHAR(180) NOT NULL, animalCode VARCHAR(20) NOT NULL, animalSpecies VARCHAR(10) NOT NULL, animalAgeMonths INT NOT NULL, animalSize VARCHAR(10) NOT NULL, animal_id VARCHAR(36) NOT NULL, application_id VARCHAR(36) NOT NULL, adopter_id VARCHAR(36) NOT NULL, formalizedBy_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14A8D4FF58056ADA ON Adoption (contractNumber)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14A8D4FF8E962C16 ON Adoption (animal_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14A8D4FF3E030ACD ON Adoption (application_id)');
        $this->addSql('CREATE INDEX IDX_14A8D4FFA0D47673 ON Adoption (adopter_id)');
        $this->addSql('CREATE INDEX IDX_14A8D4FF7135CCB2 ON Adoption (formalizedBy_id)');
        $this->addSql('ALTER TABLE Adoption ADD CONSTRAINT FK_14A8D4FF8E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Adoption ADD CONSTRAINT FK_14A8D4FF3E030ACD FOREIGN KEY (application_id) REFERENCES AdoptionApplication (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Adoption ADD CONSTRAINT FK_14A8D4FFA0D47673 FOREIGN KEY (adopter_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Adoption ADD CONSTRAINT FK_14A8D4FF7135CCB2 FOREIGN KEY (formalizedBy_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Adoption ADD CONSTRAINT adoption_fee CHECK (feeCents BETWEEN 1 AND 999999)');
        $this->addSql("ALTER TABLE Adoption ADD CONSTRAINT adoption_contract_copy CHECK (btrim(adopterName) <> '' AND btrim(adopterEmail) <> '' AND btrim(animalCode) <> '' AND animalAgeMonths >= 0)");

        $this->addSql('CREATE TABLE FollowUp (id VARCHAR(36) NOT NULL, kind VARCHAR(20) NOT NULL, dueOn DATE NOT NULL, status VARCHAR(20) NOT NULL, adoption_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_follow_up_adoption_kind ON FollowUp (adoption_id, kind)');
        $this->addSql('CREATE INDEX IDX_71E83CCF631C55DF ON FollowUp (adoption_id)');
        $this->addSql('ALTER TABLE FollowUp ADD CONSTRAINT FK_71E83CCF631C55DF FOREIGN KEY (adoption_id) REFERENCES Adoption (id) NOT DEFERRABLE');
        $this->addSql("ALTER TABLE FollowUp ADD CONSTRAINT follow_up_kind CHECK (kind IN ('one_month','six_months'))");
        $this->addSql("ALTER TABLE FollowUp ADD CONSTRAINT follow_up_status CHECK (status IN ('scheduled'))");

        $this->addSql('ALTER TABLE AdoptionApplication DROP CONSTRAINT adoption_application_status');
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT adoption_application_status CHECK (status IN ('pending','waiting','approved','rejected','cancelled','formalized'))");
    }

    public function down(Schema $schema): void
    {
        // Falla a propósito si ya hay solicitudes formalizadas: revertir no debe borrar adopciones en silencio.
        $this->addSql('ALTER TABLE AdoptionApplication DROP CONSTRAINT adoption_application_status');
        $this->addSql("ALTER TABLE AdoptionApplication ADD CONSTRAINT adoption_application_status CHECK (status IN ('pending','waiting','approved','rejected','cancelled'))");
        $this->addSql('DROP TABLE FollowUp');
        $this->addSql('DROP TABLE Adoption');
    }
}
