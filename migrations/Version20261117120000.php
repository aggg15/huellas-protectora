<?php
declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261117120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HU-04: entrevista, motivos de rechazo/cancelación, decisiones y exclusión de doble reserva.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE AdoptionApplication ADD interviewAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE AdoptionApplication ADD interviewNotes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE AdoptionApplication ADD rejectionReason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE AdoptionApplication ADD cancellationReason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE AdoptionApplication ADD decisionAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("CREATE UNIQUE INDEX uniq_application_approved_animal ON AdoptionApplication (animal_id) WHERE status = 'approved'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_application_approved_animal');
        $this->addSql('ALTER TABLE AdoptionApplication DROP interviewAt');
        $this->addSql('ALTER TABLE AdoptionApplication DROP interviewNotes');
        $this->addSql('ALTER TABLE AdoptionApplication DROP rejectionReason');
        $this->addSql('ALTER TABLE AdoptionApplication DROP cancellationReason');
        $this->addSql('ALTER TABLE AdoptionApplication DROP decisionAt');
    }
}
