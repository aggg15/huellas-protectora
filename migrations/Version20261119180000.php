<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * HU-07: turnos de paseo y limpieza e inscripciones de voluntarios. Sentencias de las tablas Shift y ShiftSignup
 * del diff de Doctrine, completadas con los CHECK de tipo, horas y límites de personas (D27, D29).
 */
final class Version20261119180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HU-07: tablas Shift y ShiftSignup.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE Shift (id VARCHAR(36) NOT NULL, kind VARCHAR(20) NOT NULL, startsAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, endsAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, minVolunteers INT NOT NULL, maxVolunteers INT NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, createdBy_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_shift_starts_at ON Shift (startsAt)');
        $this->addSql('CREATE INDEX IDX_64CA14413174800F ON Shift (createdBy_id)');
        $this->addSql('ALTER TABLE Shift ADD CONSTRAINT FK_64CA14413174800F FOREIGN KEY (createdBy_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql("ALTER TABLE Shift ADD CONSTRAINT shift_kind CHECK (kind IN ('walk','cleaning'))");
        $this->addSql('ALTER TABLE Shift ADD CONSTRAINT shift_times CHECK (endsAt > startsAt)');
        $this->addSql('ALTER TABLE Shift ADD CONSTRAINT shift_people CHECK (minVolunteers BETWEEN 1 AND 20 AND maxVolunteers BETWEEN minVolunteers AND 20)');

        $this->addSql('CREATE TABLE ShiftSignup (id VARCHAR(36) NOT NULL, signedUpAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, shift_id VARCHAR(36) NOT NULL, volunteer_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_shift_signup_volunteer ON ShiftSignup (shift_id, volunteer_id)');
        $this->addSql('CREATE INDEX IDX_4E459ECCBB70BC0E ON ShiftSignup (shift_id)');
        $this->addSql('CREATE INDEX IDX_4E459ECC8EFAB6B1 ON ShiftSignup (volunteer_id)');
        $this->addSql('ALTER TABLE ShiftSignup ADD CONSTRAINT FK_4E459ECCBB70BC0E FOREIGN KEY (shift_id) REFERENCES Shift (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE ShiftSignup ADD CONSTRAINT FK_4E459ECC8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES app_user (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ShiftSignup');
        $this->addSql('DROP TABLE Shift');
    }
}
