<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105150000 extends AbstractMigration
{
    public function getDescription(): string { return 'Restricciones de dominio de la ficha del animal.'; }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE animal ADD CONSTRAINT animal_species CHECK (species IN ('dog','cat'))");
        $this->addSql("ALTER TABLE animal ADD CONSTRAINT animal_size CHECK (size IN ('small','medium','large'))");
        $this->addSql("ALTER TABLE animal ADD CONSTRAINT animal_age CHECK (ageMonths BETWEEN 0 AND 600)");
        $this->addSql("ALTER TABLE animal ADD CONSTRAINT animal_status CHECK (status IN ('quarantine','available','reserved','adopted'))");
    }
    public function down(Schema $schema): void
    {
        foreach (['animal_species','animal_size','animal_age','animal_status'] as $name) { $this->addSql('ALTER TABLE animal DROP CONSTRAINT '.$name); }
    }
}
