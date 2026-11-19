<?php
// Datos ficticios para la verificación manual de HU-08 (T818). Base: huellas_manual_spec008_test.
// Uso: php datos-ficticios.php <raíz-del-proyecto>                    → JSON con identificadores y contraseñas aleatorias.
//      php datos-ficticios.php <raíz-del-proyecto> adoptar <animal>   → formaliza con HU-05 la adopción del animal
//                                                                        (solicitud aprobada de otra cuenta ficticia).
declare(strict_types=1);
use App\Entity\{AdoptionApplication, Animal, User};
use App\Kernel;
use App\Service\AdoptionService;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Dotenv\Dotenv;
$root = $argv[1];
require $root.'/vendor/autoload.php';
(new Dotenv())->bootEnv($root.'/.env');
$kernel = new Kernel('dev', true); $kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
if (!str_ends_with($em->getConnection()->getDatabase(), '_test')) { fwrite(STDERR, "Base no permitida\n"); exit(2); }
$utc = new DateTimeZone('UTC');
$mk = function (string $email, string $name, string $role, ?array &$out = null) use ($em) {
    $u = new User($email, $name); $u->roles = [$role];
    $pw = 'Ficticia-'.bin2hex(random_bytes(6)); $u->password = password_hash($pw, PASSWORD_BCRYPT);
    $em->persist($u);
    if ($out !== null) { $out['users'][$email] = $pw; }
    return $u;
};

if (($argv[2] ?? '') === 'adoptar') {
    $animal = $em->find(Animal::class, $argv[3]);
    $animal->status = 'reserved';
    $adopter = $mk('adoptante.'.bin2hex(random_bytes(3)).'@example.test', 'Adoptante Ficticia', 'ROLE_USER');
    $application = new AdoptionApplication($animal, $adopter, [
        'housingType' => 'house_outdoor', 'housing' => 'Casa ficticia.', 'experienceType' => 'dogs',
        'experience' => 'Experiencia ficticia.', 'dailyTime' => 'gt4', 'availability' => 'Disponibilidad ficticia.',
    ], new DateTimeImmutable('-3 days', $utc));
    $application->status = 'approved';
    $application->interviewAt = $application->decisionAt = new DateTimeImmutable('-1 day', $utc);
    $application->interviewNotes = 'Entrevista ficticia favorable.';
    $em->persist($application);
    $em->flush();
    $coordinator = $em->getRepository(User::class)->findOneBy(['email' => 'coordinadora@example.test']);
    (new AdoptionService($em, new NativeClock()))->formalize($application, $coordinator, ['fee' => '120', 'feePaid' => '1']);
    echo 'adoptado';
    exit(0);
}

$out = [];
$mk('coordinadora@example.test', 'Coordinadora Ficticia', 'ROLE_COORDINATOR', $out);
$mk('lucia@example.test', 'Lucía Madrina Ficticia', 'ROLE_USER', $out);
$mk('voluntario@example.test', 'Voluntario Ficticio', 'ROLE_VOLUNTEER', $out);
$animals = [];
foreach ([['dog', 30, 'medium', true], ['cat', 14, 'small', false], ['dog', 70, 'large', false]] as [$species, $age, $size, $fostered]) {
    $a = new Animal($species, $age, $size, 'Recogido en un escenario ficticio.', new DateTimeImmutable('-20 days', $utc));
    $a->status = 'available'; $a->fostered = $fostered;
    $em->persist($a); $animals[] = $a;
}
$em->flush();
$out['animals'] = array_map(static fn(Animal $a) => ['id' => $a->id, 'code' => $a->code], $animals);
echo json_encode($out, JSON_PRETTY_PRINT);
