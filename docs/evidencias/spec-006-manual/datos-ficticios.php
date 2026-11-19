<?php
// Datos ficticios para la verificación manual de HU-06 (T623). Base: huellas_manual_spec006_test.
// Uso: php datos-ficticios.php <raíz-del-proyecto>  → JSON con identificadores y contraseñas aleatorias.
declare(strict_types=1);
use App\Entity\{Animal, AnimalPhoto, FosterHome, FosterPlacement, User};
use App\Kernel;
use App\Service\AvailabilityCalendar;
use Symfony\Component\Dotenv\Dotenv;
$root = $argv[1];
require $root.'/vendor/autoload.php';
(new Dotenv())->bootEnv($root.'/.env');
$kernel = new Kernel('dev', true); $kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$out = [];
$mk = function (string $email, string $name, string $role) use ($em, &$out) {
    $u = new User($email, $name); $u->roles = [$role];
    $pw = 'Ficticia-'.bin2hex(random_bytes(6)); $u->password = password_hash($pw, PASSWORD_BCRYPT);
    $em->persist($u); $out['users'][$email] = $pw; return $u;
};
$coord = $mk('coordinadora@example.test', 'Coordinadora Ficticia', 'ROLE_COORDINATOR');
$mk('familia.encina@example.test', 'Familia Encina Ficticia', 'ROLE_FOSTER');
$alamo = $mk('familia.alamo@example.test', 'Familia Álamo Ficticia', 'ROLE_FOSTER');
$dir = $root.'/var/uploads';
if (!is_dir($dir)) { mkdir($dir, 0700, true); }
$photo = function (Animal $a, array $rgb) use ($em, $dir, &$out) {
    $name = 'manual-spec006-'.bin2hex(random_bytes(6)).'.png';
    $img = imagecreatetruecolor(400, 300); imagefill($img, 0, 0, imagecolorallocate($img, ...$rgb)); imagepng($img, $dir.'/'.$name);
    $em->persist(new AnimalPhoto($a, $name, 'image/png')); $out['files'][] = $dir.'/'.$name;
};
$today = AvailabilityCalendar::madridDay(new DateTimeImmutable());
// A: disponible, para el recorrido satisfactorio. B: disponible, para el error. C: ya acogido por la familia Álamo (capacidad 1).
$a = new Animal('dog', 30, 'medium', 'Entregado por una familia ficticia.', new DateTimeImmutable('-12 days')); $a->status = 'available'; $em->persist($a); $photo($a, [120, 160, 130]);
$b = new Animal('cat', 8, 'small', 'Recogido en la calle (escenario ficticio).', new DateTimeImmutable('-15 days')); $b->status = 'available'; $em->persist($b); $photo($b, [170, 140, 110]);
$c = new Animal('dog', 70, 'large', 'Rescatado de un abandono (ficticio).', new DateTimeImmutable('-20 days')); $c->status = 'available'; $c->fostered = true; $em->persist($c);
$home = new FosterHome($alamo, 1, new DateTimeImmutable()); $em->persist($home);
$em->persist($home->addPeriod($today, $today->modify('+60 days')));
$em->persist(new FosterPlacement($c, $home, $coord, $today, $today->modify('+30 days')));
$em->flush();
$out['animalA'] = ['id' => $a->id, 'code' => $a->code];
$out['animalB'] = ['id' => $b->id, 'code' => $b->code];
$out['animalC'] = ['id' => $c->id, 'code' => $c->code];
$out['today'] = $today->format('Y-m-d');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
