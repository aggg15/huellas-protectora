<?php
// Proceso auxiliar de Spec004Test: aprueba o rechaza una solicitud desde una conexión
// independiente, para comprobar que dos decisiones simultáneas sobre la misma solicitud no se mezclan.
// Uso: <approve|reject> <solicitud> <inicio>. La solicitud se carga antes de esperar al inicio,
// como una página abierta con un estado ya obsoleto.
declare(strict_types=1);

use App\Entity\AdoptionApplication;
use App\Kernel;
use App\Service\{ApplicationRefused, ApplicationService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
[, $mode, $applicationId, $startAt] = $argv;

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get(EntityManagerInterface::class);
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$application = $em->find(AdoptionApplication::class, $applicationId);
$service = $container->get(ApplicationService::class);

while (microtime(true) < (float) $startAt) { usleep(1000); }
try {
    match ($mode) {
        'approve' => $service->approve($application),
        default => $service->reject($application, ['rejectionReason' => 'Rechazo simultáneo de la prueba.']),
    };
    echo $mode.':ok';
} catch (ApplicationRefused|ValidationError) {
    echo $mode.':rechazada';
}
