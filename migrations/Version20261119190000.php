<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * HU-08: apadrinamientos y novedades para padrinos. Sentencias del diff de Doctrine (solo tablas de HU-08),
 * completadas con los CHECK de importe, motivo de fin y coherencia de fin y motivo (D36, D39).
 */
final class Version20261119190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HU-08: tablas Sponsorship y SponsorUpdate.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE SponsorUpdate (id VARCHAR(36) NOT NULL, writtenAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, body TEXT NOT NULL, animal_id VARCHAR(36) NOT NULL, author_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_sponsor_update_animal_written ON SponsorUpdate (animal_id, writtenAt)');
        $this->addSql('CREATE INDEX IDX_625D71C8E962C16 ON SponsorUpdate (animal_id)');
        $this->addSql('CREATE INDEX IDX_625D71CF675F31B ON SponsorUpdate (author_id)');
        $this->addSql('CREATE TABLE Sponsorship (id VARCHAR(36) NOT NULL, monthlyCents INT NOT NULL, startedAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, endedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, endReason VARCHAR(20) DEFAULT NULL, animal_id VARCHAR(36) NOT NULL, sponsor_id VARCHAR(36) NOT NULL, previous_id VARCHAR(36) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_466D83032DE62210 ON Sponsorship (previous_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_sponsorship_active ON Sponsorship (animal_id, sponsor_id) WHERE (endedat IS NULL)');
        $this->addSql('CREATE INDEX IDX_466D83038E962C16 ON Sponsorship (animal_id)');
        $this->addSql('CREATE INDEX IDX_466D830312F7FB51 ON Sponsorship (sponsor_id)');
        $this->addSql('ALTER TABLE SponsorUpdate ADD CONSTRAINT FK_625D71C8E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE SponsorUpdate ADD CONSTRAINT FK_625D71CF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Sponsorship ADD CONSTRAINT FK_466D83038E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Sponsorship ADD CONSTRAINT FK_466D830312F7FB51 FOREIGN KEY (sponsor_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Sponsorship ADD CONSTRAINT FK_466D83032DE62210 FOREIGN KEY (previous_id) REFERENCES Sponsorship (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE Sponsorship ADD CONSTRAINT sponsorship_amount CHECK (monthlyCents BETWEEN 1000 AND 999999)');
        $this->addSql("ALTER TABLE Sponsorship ADD CONSTRAINT sponsorship_end CHECK ((endedAt IS NULL AND endReason IS NULL) OR (endedAt IS NOT NULL AND endReason IN ('finished','moved')))");
        $this->addSql('ALTER TABLE Sponsorship ADD CONSTRAINT sponsorship_dates CHECK (endedAt IS NULL OR endedAt >= startedAt)');
        $this->addSql("ALTER TABLE SponsorUpdate ADD CONSTRAINT sponsor_update_body CHECK (char_length(body) BETWEEN 1 AND 2000)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE SponsorUpdate');
        $this->addSql('DROP TABLE Sponsorship');
    }
}
