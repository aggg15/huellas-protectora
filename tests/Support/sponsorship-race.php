<?php
// Proceso auxiliar de Spec008Test: apadrina, cambia de animal o termina desde una conexión independiente,
// para comprobar CA-08-09.
// Uso: sponsor <animal> <cuenta> <inicio> | move <apadrinamiento> <animal> <inicio> | finish <apadrinamiento> - <inicio>.
// Las entidades se cargan antes de esperar al inicio, como una página abierta con un estado ya obsoleto.
declare(strict_types=1);

use App\Entity\{Animal, Sponsorship, User};
use App\Kernel;
use App\Service\{SponsorshipRefused, SponsorshipService};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
[, $mode, $first, $second, $startAt] = $argv;

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get(EntityManagerInterface::class);
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$service = $container->get(SponsorshipService::class);

if ($mode === 'sponsor') {
    $animal = $em->find(Animal::class, $first);
    $sponsor = $em->find(User::class, $second);
    $operation = static fn() => $service->sponsor($animal, $sponsor, ['amount' => '10']);
} else {
    $sponsorship = $em->find(Sponsorship::class, $first);
    $sponsor = $sponsorship->sponsor;
    $target = $mode === 'move' ? $em->find(Animal::class, $second) : null;
    $operation = $mode === 'move'
        ? static fn() => $service->moveTo($sponsorship, $target, $sponsor)
        : static fn() => $service->finish($sponsorship, $sponsor);
}

while (microtime(true) < (float) $startAt) { usleep(1000); }
try {
    $operation();
    echo $mode.':ok';
} catch (SponsorshipRefused) {
    echo $mode.':rechazada';
}
