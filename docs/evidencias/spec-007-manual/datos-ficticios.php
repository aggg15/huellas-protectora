<?php
// Datos ficticios para la verificación manual de HU-07 (T718). Base: huellas_manual_spec007_test.
// Uso: php datos-ficticios.php <raíz-del-proyecto>                 → JSON con identificadores y contraseñas aleatorias.
//      php datos-ficticios.php <raíz-del-proyecto> ocupar <turno>   → otra voluntaria ocupa una plaza del turno
//                                                                      (simula a otra persona mientras la página sigue abierta).
declare(strict_types=1);
use App\Entity\{Shift, ShiftSignup, User};
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
$root = $argv[1];
require $root.'/vendor/autoload.php';
(new Dotenv())->bootEnv($root.'/.env');
$kernel = new Kernel('dev', true); $kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$utc = new DateTimeZone('UTC');
$madrid = new DateTimeZone('Europe/Madrid');

if (($argv[2] ?? '') === 'ocupar') {
    $shift = $em->find(Shift::class, $argv[3]);
    $other = new User('otra.voluntaria.'.bin2hex(random_bytes(3)).'@example.test', 'Otra Voluntaria Ficticia');
    $other->roles = ['ROLE_VOLUNTEER']; $other->password = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
    $em->persist($other);
    $em->persist(new ShiftSignup($shift, $other, new DateTimeImmutable('now', $utc)));
    $em->flush();
    echo 'ocupada';
    exit(0);
}

$out = [];
$mk = function (string $email, string $name, string $role) use ($em, &$out) {
    $u = new User($email, $name); $u->roles = [$role];
    $pw = 'Ficticia-'.bin2hex(random_bytes(6)); $u->password = password_hash($pw, PASSWORD_BCRYPT);
    $em->persist($u); $out['users'][$email] = $pw; return $u;
};
$coord = $mk('coordinadora@example.test', 'Coordinadora Ficticia', 'ROLE_COORDINATOR');
$mk('ana@example.test', 'Ana Voluntaria Ficticia', 'ROLE_VOLUNTEER');
$bruno = $mk('bruno@example.test', 'Bruno Voluntario Ficticio', 'ROLE_VOLUNTEER');
$mk('veterinaria@example.test', 'Veterinaria Ficticia', 'ROLE_VET');

$at = fn(int $days, string $time) => new DateTimeImmutable((new DateTimeImmutable('now', $madrid))->modify("+$days days")->format('Y-m-d').' '.$time, $madrid);
// Limpieza dentro de 3 días con una plaza libre (máximo 2, una persona): para el error de turno completo.
$last = new Shift('cleaning', $at(3, '10:00'), $at(3, '12:00'), 1, 2, $coord, new DateTimeImmutable('now', $utc));
$em->persist($last);
$filler = new User('carla@example.test', 'Carla Voluntaria Ficticia'); $filler->roles = ['ROLE_VOLUNTEER']; $filler->password = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
$em->persist($filler);
$em->persist(new ShiftSignup($last, $filler, new DateTimeImmutable('now', $utc)));
// Paseo dentro de 4 días de 10:00 a 12:00 con Bruno, y un paseo coincidente de 11:00 a 13:00.
$morning = new Shift('walk', $at(4, '10:00'), $at(4, '12:00'), 1, 3, $coord, new DateTimeImmutable('now', $utc));
$overlap = new Shift('walk', $at(4, '11:00'), $at(4, '13:00'), 1, 3, $coord, new DateTimeImmutable('now', $utc));
$em->persist($morning); $em->persist($overlap);
$em->persist(new ShiftSignup($morning, $bruno, new DateTimeImmutable('now', $utc)));
$em->flush();

// Turno que creará la coordinadora en el recorrido: hora en punto dentro de unas 30 horas (ventana de 48 horas).
$new = (new DateTimeImmutable('+30 hours', $madrid));
$new = $new->setTime((int) $new->format('H'), 0);
if ((int) $new->format('H') > 21) { $new = $new->setTime(21, 0); }
$out['newShift'] = ['date' => $new->format('Y-m-d'), 'start' => $new->format('H:i'), 'end' => $new->modify('+2 hours')->format('H:i')];
$out['lastPlace'] = $last->id;
$out['morning'] = $morning->id;
$out['overlap'] = $overlap->id;
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
