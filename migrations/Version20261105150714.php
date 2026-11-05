<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261105150714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX one_approved_per_animal');
        $this->addSql('DROP INDEX one_open_per_applicant_animal');
        $this->addSql('ALTER TABLE adoptionapplication ADD decidedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE adoptionapplication ADD decidedBy_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE adoptionapplication ADD CONSTRAINT FK_C98BF1AA85C5BBC FOREIGN KEY (decidedBy_id) REFERENCES app_user (id)');
        $this->addSql('CREATE INDEX IDX_C98BF1AA85C5BBC ON adoptionapplication (decidedBy_id)');
        $this->addSql('CREATE UNIQUE INDEX one_approved_per_animal ON adoptionapplication (animal_id) WHERE status = \'approved\'');
        $this->addSql('CREATE UNIQUE INDEX one_open_per_applicant_animal ON adoptionapplication (applicant_id, animal_id) WHERE status IN (\'pending\',\'waiting\',\'approved\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE AdoptionApplication DROP CONSTRAINT FK_C98BF1AA85C5BBC');
        $this->addSql('DROP INDEX one_approved_per_animal');
        $this->addSql('DROP INDEX one_open_per_applicant_animal');
        $this->addSql('DROP INDEX IDX_C98BF1AA85C5BBC');
        $this->addSql('ALTER TABLE AdoptionApplication DROP decidedAt');
        $this->addSql('ALTER TABLE AdoptionApplication DROP decidedBy_id');
        $this->addSql('CREATE UNIQUE INDEX one_approved_per_animal ON AdoptionApplication (animal_id) WHERE ((status)::text = \'approved\'::text)');
        $this->addSql('CREATE UNIQUE INDEX one_open_per_applicant_animal ON AdoptionApplication (applicant_id, animal_id) WHERE ((status)::text = ANY ((ARRAY[\'pending\'::character varying, \'waiting\'::character varying, \'approved\'::character varying])::text[]))');
    }
}
