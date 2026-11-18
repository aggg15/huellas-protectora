<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118211500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sincroniza el predicado del índice de solicitudes aprobadas con el mapeo Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_application_approved_animal');
        $this->addSql("CREATE UNIQUE INDEX uniq_application_approved_animal ON AdoptionApplication (animal_id) WHERE ((status)::text = 'approved'::text)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_application_approved_animal');
        $this->addSql("CREATE UNIQUE INDEX uniq_application_approved_animal ON AdoptionApplication (animal_id) WHERE status = 'approved'");
    }
}
