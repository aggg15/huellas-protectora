<?php
// Datos ficticios para la verificación manual de HU-05 (T525). Base: huellas_manual_spec005_test.
declare(strict_types=1);
use App\Entity\{AdoptionApplication, Animal, AnimalPhoto, User};
use App\Kernel;
use App\Service\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;
$root = $argv[1];
require $root.'/vendor/autoload.php';
(new Dotenv())->bootEnv($root.'/.env');
$kernel = new Kernel('dev', true); $kernel->boot();
$c = $kernel->getContainer();
$em = $c->get('doctrine')->getManager();
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
// Contraseñas bcrypt, admitidas por el hasher 'auto' de la aplicación.
$out = [];
$mk = function (string $email, string $name, string $role) use ($em, &$out) {
    $u = new User($email, $name); $u->roles = $role === 'ROLE_USER' ? [] : [$role];
    $pw = 'Ficticia-'.bin2hex(random_bytes(6)); $u->password = password_hash($pw, PASSWORD_BCRYPT);
    $em->persist($u); $out['users'][$email] = $pw; return $u;
};
$coord = $mk('coordinadora@example.test', 'Coordinadora Ficticia', 'ROLE_COORDINATOR');
$other = $mk('otra.familia@example.test', 'Otra Familia Ficticia', 'ROLE_USER');
$storageDir = $root.'/var/uploads';
$photo = function (Animal $a) use ($em, $storageDir, &$out) {
    if (!is_dir($storageDir)) { mkdir($storageDir, 0700, true); }
    $name = 'manual-spec005-'.bin2hex(random_bytes(6)).'.png';
    $img = imagecreatetruecolor(400, 300); imagefill($img, 0, 0, imagecolorallocate($img, 120, 160, 130)); imagepng($img, $storageDir.'/'.$name);
    $em->persist(new AnimalPhoto($a, $name, 'image/png')); $out['files'][] = $storageDir.'/'.$name;
};
// A: disponible, para el recorrido satisfactorio.
$a = new Animal('dog', 30, 'medium', 'Entregado por una familia ficticia.', new DateTimeImmutable('-12 days')); $a->status = 'available'; $a->fostered = true;
$em->persist($a); $photo($a);
// B: reservado con solicitud aprobada, para el recorrido con error.
$b = new Animal('cat', 8, 'small', 'Recogido en la calle (escenario ficticio).', new DateTimeImmutable('-15 days')); $b->status = 'reserved';
$em->persist($b); $photo($b);
$q = ['housingType'=>'flat','housing'=>'Piso ficticio con terraza.','experienceType'=>'cats','experience'=>'Dos gatos anteriores (ficticio).','dailyTime'=>'2to4','availability'=>'Tardes libres (ficticio).'];
$app = new AdoptionApplication($b, $other, $q, new DateTimeImmutable('-5 days')); $app->status='approved';
$app->interviewAt = new DateTimeImmutable('-2 days'); $app->interviewNotes = 'Entrevista ficticia favorable.'; $app->decisionAt = new DateTimeImmutable('-1 day');
$em->persist($app);
$em->flush();
$out['animalA'] = ['id'=>$a->id,'code'=>$a->code]; $out['animalB'] = ['id'=>$b->id,'code'=>$b->code,'application'=>$app->id];
echo json_encode($out, JSON_PRETTY_PRINT);
