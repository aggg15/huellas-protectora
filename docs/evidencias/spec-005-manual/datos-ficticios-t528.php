<?php
// T528: añade a «Otra Familia Ficticia» una solicitud cancelada con motivo (datos ficticios).
declare(strict_types=1);
use App\Entity\{AdoptionApplication, Animal, User};
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
$root = $argv[1];
require $root.'/vendor/autoload.php';
(new Dotenv())->bootEnv($root.'/.env');
$kernel = new Kernel('dev', true); $kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$user = $em->getRepository(User::class)->findOneBy(['email' => 'otra.familia@example.test']);
$animal = new Animal('dog', 50, 'large', 'Escenario ficticio.', new DateTimeImmutable('-30 days')); $animal->status = 'adopted';
$em->persist($animal);
$q = ['housingType'=>'flat','housing'=>'Piso ficticio.','experienceType'=>'none','experience'=>'','dailyTime'=>'lt2','availability'=>'Mañanas (ficticio).'];
$app = new AdoptionApplication($animal, $user, $q, new DateTimeImmutable('-10 days')); $app->status = 'cancelled'; $app->cancellationReason = 'El animal ha sido adoptado';
$em->persist($app); $em->flush();
echo "ok\n";
