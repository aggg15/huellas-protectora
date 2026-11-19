<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * HU-06: casas de acogida, periodos de disponibilidad, acogidas y novedades. Generada con el diff de
 * Doctrine y completada con los CHECK de capacidad, fechas, motivo de fin y texto de la novedad (D20 a D24).
 */
final class Version20261119120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HU-06: tablas FosterHome, FosterAvailability, FosterPlacement y FosterNote.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE FosterHome (id VARCHAR(36) NOT NULL, capacity INT NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, family_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E287621BC35E566A ON FosterHome (family_id)');
        $this->addSql('ALTER TABLE FosterHome ADD CONSTRAINT FK_E287621BC35E566A FOREIGN KEY (family_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE FosterHome ADD CONSTRAINT foster_home_capacity CHECK (capacity BETWEEN 1 AND 10)');

        $this->addSql('CREATE TABLE FosterAvailability (id VARCHAR(36) NOT NULL, startsOn DATE NOT NULL, endsOn DATE NOT NULL, home_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_928648F428CDC89C ON FosterAvailability (home_id)');
        $this->addSql('ALTER TABLE FosterAvailability ADD CONSTRAINT FK_928648F428CDC89C FOREIGN KEY (home_id) REFERENCES FosterHome (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE FosterAvailability ADD CONSTRAINT foster_availability_dates CHECK (endsOn >= startsOn)');

        $this->addSql('CREATE TABLE FosterPlacement (id VARCHAR(36) NOT NULL, startedOn DATE NOT NULL, plannedEndOn DATE NOT NULL, endedOn DATE DEFAULT NULL, endReason VARCHAR(20) DEFAULT NULL, animal_id VARCHAR(36) NOT NULL, home_id VARCHAR(36) NOT NULL, assignedBy_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_foster_active_animal ON FosterPlacement (animal_id) WHERE (endedon IS NULL)');
        $this->addSql('CREATE INDEX IDX_3AF5B1AA8E962C16 ON FosterPlacement (animal_id)');
        $this->addSql('CREATE INDEX IDX_3AF5B1AA28CDC89C ON FosterPlacement (home_id)');
        $this->addSql('CREATE INDEX IDX_3AF5B1AADF7B85E2 ON FosterPlacement (assignedBy_id)');
        $this->addSql('ALTER TABLE FosterPlacement ADD CONSTRAINT FK_3AF5B1AA8E962C16 FOREIGN KEY (animal_id) REFERENCES Animal (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE FosterPlacement ADD CONSTRAINT FK_3AF5B1AA28CDC89C FOREIGN KEY (home_id) REFERENCES FosterHome (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE FosterPlacement ADD CONSTRAINT FK_3AF5B1AADF7B85E2 FOREIGN KEY (assignedBy_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE FosterPlacement ADD CONSTRAINT foster_placement_dates CHECK (plannedEndOn >= startedOn AND (endedOn IS NULL OR endedOn >= startedOn))');
        $this->addSql("ALTER TABLE FosterPlacement ADD CONSTRAINT foster_placement_end CHECK ((endedOn IS NULL AND endReason IS NULL) OR (endedOn IS NOT NULL AND endReason IN ('returned','adopted')))");

        $this->addSql('CREATE TABLE FosterNote (id VARCHAR(36) NOT NULL, writtenAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, body TEXT NOT NULL, placement_id VARCHAR(36) NOT NULL, author_id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5CEC94DF2F966E9D ON FosterNote (placement_id)');
        $this->addSql('CREATE INDEX IDX_5CEC94DFF675F31B ON FosterNote (author_id)');
        $this->addSql('ALTER TABLE FosterNote ADD CONSTRAINT FK_5CEC94DF2F966E9D FOREIGN KEY (placement_id) REFERENCES FosterPlacement (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE FosterNote ADD CONSTRAINT FK_5CEC94DFF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) NOT DEFERRABLE');
        $this->addSql("ALTER TABLE FosterNote ADD CONSTRAINT foster_note_body CHECK (btrim(body) <> '' AND char_length(body) <= 2000)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE FosterNote');
        $this->addSql('DROP TABLE FosterPlacement');
        $this->addSql('DROP TABLE FosterAvailability');
        $this->addSql('DROP TABLE FosterHome');
    }
}
