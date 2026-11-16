<?php
declare(strict_types=1);
namespace App\Command;
use App\Service\AnimalService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:release-quarantine', description: 'Libera las cuarentenas de diez días ya completadas.')]
class ReleaseQuarantineCommand extends Command
{
    public function __construct(private AnimalService $animals) { parent::__construct(); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Animales disponibles: '.$this->animals->releaseDueAnimals()); return Command::SUCCESS;
    }
}
