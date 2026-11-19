<?php
// Proceso auxiliar de Spec005Test: formaliza una adopción o libera su reserva desde una conexión
// independiente, para comprobar CA-05-10. Uso: <formalize|release> <solicitud> <coordinadora> <inicio>.
// La solicitud se carga antes de esperar al inicio, como una página abierta con un estado ya obsoleto.
declare(strict_types=1);

use App\Entity\{AdoptionApplication, User};
use App\Kernel;
use App\Service\{AdoptionService, ApplicationRefused, ApplicationService};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
[, $mode, $applicationId, $coordinatorId, $startAt] = $argv;

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get(EntityManagerInterface::class);
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$application = $em->find(AdoptionApplication::class, $applicationId);
$coordinator = $em->find(User::class, $coordinatorId);

while (microtime(true) < (float) $startAt) { usleep(1000); }
try {
    if ($mode === 'formalize') {
        $container->get(AdoptionService::class)->formalize($application, $coordinator, ['fee' => '120,00', 'feePaid' => '1']);
    } else {
        $container->get(ApplicationService::class)->cancelReservation($application, ['cancellationReason' => 'Liberación simultánea de la prueba.']);
    }
    echo $mode.':ok';
} catch (ApplicationRefused) {
    echo $mode.':rechazada';
}
