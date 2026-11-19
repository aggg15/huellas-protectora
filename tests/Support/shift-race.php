<?php
// Proceso auxiliar de Spec007Test: se apunta a un turno o cambia su máximo desde una conexión independiente,
// para comprobar CA-07-10.
// Uso: signup <turno> <voluntario> <inicio> | max <turno> <nuevo-máximo> <inicio>.
// Las entidades se cargan antes de esperar al inicio, como una página abierta con un estado ya obsoleto.
declare(strict_types=1);

use App\Entity\{Shift, User};
use App\Kernel;
use App\Service\{ShiftRefused, ShiftService};
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
$service = $container->get(ShiftService::class);

if ($mode === 'signup') {
    [, , $shiftId, $volunteerId, $startAt] = $argv;
    $shift = $em->find(Shift::class, $shiftId);
    $volunteer = $em->find(User::class, $volunteerId);
    $operation = static fn() => $service->signUp($shift, $volunteer);
} else {
    [, , $shiftId, $max, $startAt] = $argv;
    $shift = $em->find(Shift::class, $shiftId);
    $zone = new DateTimeZone(ShiftService::ZONE);
    $data = [
        'kind' => $shift->kind,
        'date' => $shift->startsAt->setTimezone($zone)->format('Y-m-d'),
        'startTime' => $shift->startsAt->setTimezone($zone)->format('H:i'),
        'endTime' => $shift->endsAt->setTimezone($zone)->format('H:i'),
        'minVolunteers' => (string) min($shift->minVolunteers, (int) $max),
        'maxVolunteers' => $max,
    ];
    $operation = static fn() => $service->update($shift, $data);
}

while (microtime(true) < (float) $startAt) { usleep(1000); }
try {
    $operation();
    echo $mode.':ok';
} catch (ShiftRefused) {
    echo $mode.':rechazada';
}
