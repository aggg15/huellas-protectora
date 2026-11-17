<?php
// Proceso auxiliar de Spec003Test: presenta una solicitud desde una conexión independiente
// para comprobar el límite de dos abiertas ante envíos simultáneos. Uso: <usuario> <animal> <inicio>.
declare(strict_types=1);

use App\Entity\{Animal, User};
use App\Kernel;
use App\Service\{ApplicationRefused, ApplicationService};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
[, $userId, $animalId, $startAt] = $argv;

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get(EntityManagerInterface::class);
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$user = $em->find(User::class, $userId);
$animal = $em->find(Animal::class, $animalId);

while (microtime(true) < (float) $startAt) { usleep(1000); }
try {
    $container->get(ApplicationService::class)->submit($user, $animal, [
        'housingType' => 'flat', 'housing' => 'Piso ficticio para la prueba de concurrencia.',
        'experienceType' => 'none', 'experience' => '',
        'dailyTime' => '2to4', 'availability' => 'Disponibilidad ficticia por las tardes.',
    ]);
    echo 'admitida';
} catch (ApplicationRefused) {
    echo 'rechazada';
}
