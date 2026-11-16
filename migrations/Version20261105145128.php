<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261105145128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Animal (id VARCHAR(36) NOT NULL, code VARCHAR(20) NOT NULL, species VARCHAR(10) NOT NULL, ageMonths INT NOT NULL, size VARCHAR(10) NOT NULL, origin TEXT NOT NULL, receivedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, fostered BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6D07262977153098 ON Animal (code)');
        $this->addSql('CREATE TABLE AnimalPhoto (id VARCHAR(36) NOT NULL, filename VARCHAR(64) NOT NULL, mime VARCHAR(30) NOT NULL, animal_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_49E215E48E962C16 ON AnimalPhoto (animal_id)');
        $this->addSql('CREATE TABLE VeterinaryReview (id VARCHAR(36) NOT NULL, reviewedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, notes TEXT NOT NULL, animal_id VARCHAR(36) NOT NULL, veterinarian_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D9A3741A8E962C16 ON VeterinaryReview (animal_id)');
        $this->addSql('CREATE INDEX IDX_D9A3741A804C8213 ON VeterinaryReview (veterinarian_id)');
        $this->addSql('CREATE TABLE app_user (id VARCHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)');
        $this->addSql('ALTER TABLE AnimalPhoto ADD CONSTRAINT FK_49E215E48E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE VeterinaryReview ADD CONSTRAINT FK_D9A3741A8E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE VeterinaryReview ADD CONSTRAINT FK_D9A3741A804C8213 FOREIGN KEY (veterinarian_id) REFERENCES app_user (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE AnimalPhoto DROP CONSTRAINT FK_49E215E48E962C16');
        $this->addSql('ALTER TABLE VeterinaryReview DROP CONSTRAINT FK_D9A3741A8E962C16');
        $this->addSql('ALTER TABLE VeterinaryReview DROP CONSTRAINT FK_D9A3741A804C8213');
        $this->addSql('DROP TABLE Animal');
        $this->addSql('DROP TABLE AnimalPhoto');
        $this->addSql('DROP TABLE VeterinaryReview');
        $this->addSql('DROP TABLE app_user');
    }
}
