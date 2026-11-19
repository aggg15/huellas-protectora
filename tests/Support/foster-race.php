<?php
// Proceso auxiliar de Spec006Test: registra una acogida o formaliza una adopción desde una conexión
// independiente, para comprobar CA-06-09.
// Uso: place <animal> <casa> <coordinadora> <fin-previsto> <inicio> | formalize <solicitud> <coordinadora> <inicio>.
// Las entidades se cargan antes de esperar al inicio, como una página abierta con un estado ya obsoleto.
declare(strict_types=1);

use App\Entity\{AdoptionApplication, Animal, User};
use App\Kernel;
use App\Service\{AdoptionService, ApplicationRefused, FosterRefused, FosterService};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
$mode = $argv[1];

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get(EntityManagerInterface::class);
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }

if ($mode === 'place') {
    [, , $animalId, $homeId, $coordinatorId, $plannedEndOn, $startAt] = $argv;
    $animal = $em->find(Animal::class, $animalId);
    $coordinator = $em->find(User::class, $coordinatorId);
    $operation = static fn() => $container->get(FosterService::class)->place($animal, $coordinator, ['home' => $homeId, 'plannedEndOn' => $plannedEndOn]);
} else {
    [, , $applicationId, $coordinatorId, $startAt] = $argv;
    $application = $em->find(AdoptionApplication::class, $applicationId);
    // Se inicializa el animal, como al mostrar la ficha de la solicitud: su indicador de acogida queda obsoleto.
    $application->animal->fostered;
    $coordinator = $em->find(User::class, $coordinatorId);
    $operation = static fn() => $container->get(AdoptionService::class)->formalize($application, $coordinator, ['fee' => '120,00', 'feePaid' => '1']);
}

while (microtime(true) < (float) $startAt) { usleep(1000); }
try {
    $operation();
    echo $mode.':ok';
} catch (FosterRefused|ApplicationRefused) {
    echo $mode.':rechazada';
}
