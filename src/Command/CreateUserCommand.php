<?php
declare(strict_types=1);
namespace App\Command;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Crea una cuenta interna; pide la contraseña sin mostrarla.')]
class CreateUserCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED)->addArgument('name', InputArgument::REQUIRED)
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'ROLE_USER / ROLE_VOLUNTEER / ROLE_VET / ROLE_COORDINATOR', 'ROLE_USER');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $role = $input->getOption('role');
        $email = mb_strtolower(trim($input->getArgument('email')));
        if (!in_array($role, ['ROLE_USER','ROLE_VOLUNTEER','ROLE_VET','ROLE_COORDINATOR'], true) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>180 || trim($input->getArgument('name')) === '') {
            $output->writeln('<error>Datos o rol no válidos.</error>'); return Command::INVALID;
        }
        if ($this->em->getRepository(User::class)->findOneBy(['email'=>$email])) { $output->writeln('<error>El email ya existe.</error>'); return Command::FAILURE; }
        $question = new Question('Contraseña (mínimo 12 caracteres): '); $question->setHidden(true); $question->setHiddenFallback(false);
        $password = $this->getHelper('question')->ask($input, $output, $question);
        if (!is_string($password) || mb_strlen($password)<12 || strlen($password)>200) { $output->writeln('<error>Contraseña no válida.</error>'); return Command::INVALID; }
        $user = new User($email, $input->getArgument('name')); $user->roles=[$role];
        $user->password=$this->hasher->hashPassword($user, $password); $this->em->persist($user); $this->em->flush();
        $output->writeln('<info>Cuenta creada.</info>'); return Command::SUCCESS;
    }
}
