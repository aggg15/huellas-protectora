<?php
// Datos ficticios y reproducibles para la demostración en vídeo de Huellas.
//
// Uso:  php tools/demo/seed.php [--reset] [--json] [--release-in=SEGUNDOS]
//   --reset            vacía las tablas de la aplicación (y las fotografías que ellas referencian) y vuelve a cargar los datos.
//   --json             escribe en la salida estándar un JSON con identificadores, códigos y contraseña.
//   --release-in=N     añade un animal en cuarentena cuyas 240 horas terminan dentro de N segundos (vídeo de HU-01).
//
// Solo funciona con APP_ENV=dev y sobre una base cuyo nombre contenga «demo» (p. ej. huellas_demo). No toca src/.
declare(strict_types=1);

use App\Entity\{Adoption, AdoptionApplication, Animal, AnimalPhoto, FollowUp, FosterHome, FosterNote, FosterPlacement,
    Shift, ShiftSignup, SponsorUpdate, Sponsorship, User, VeterinaryReview};
use App\Kernel;
use App\Service\FollowUpCalendar;
use Symfony\Component\Dotenv\Dotenv;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
require __DIR__.'/lib.php';

$options = getopt('', ['reset', 'json', 'release-in::']);
$reset = isset($options['reset']);
$asJson = isset($options['json']);
$releaseIn = isset($options['release-in']) ? max(5, (int) $options['release-in']) : null;

if (is_file($root.'/.env')) { (new Dotenv())->bootEnv($root.'/.env'); }
$appEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
if ($appEnv !== 'dev') { fwrite(STDERR, "Este script solo se ejecuta con APP_ENV=dev (actual: $appEnv).\n"); exit(2); }

$kernel = new Kernel('dev', false);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$db = $em->getConnection();
$dbName = (string) $db->getDatabase();
if (!str_contains($dbName, 'demo')) {
    fwrite(STDERR, "Base «$dbName» no permitida: el nombre debe contener «demo» (p. ej. huellas_demo).\n");
    exit(2);
}

$uploads = $root.'/var/uploads';
if ($reset) {
    foreach ($db->fetchFirstColumn('SELECT filename FROM animalphoto') as $file) {
        if (is_file($uploads.'/'.$file)) { unlink($uploads.'/'.$file); }
    }
    $tables = $db->fetchFirstColumn("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename <> 'doctrine_migration_versions'");
    $db->executeStatement('TRUNCATE '.implode(', ', array_map(static fn(string $t) => '"'.$t.'"', $tables)).' RESTART IDENTITY CASCADE');
} elseif ((int) $db->fetchOne('SELECT COUNT(*) FROM app_user') > 0) {
    fwrite(STDERR, "La base ya tiene cuentas; usa --reset para volver a cargarla.\n");
    exit(1);
}
if (!is_dir($uploads)) { mkdir($uploads, 0775, true); }

const DEMO_PASSWORD = 'Huellas-Demo-2026';
$utc = new DateTimeZone('UTC');
$madrid = new DateTimeZone('Europe/Madrid');
$now = new DateTimeImmutable('now', $utc);
$ago = static fn(string $spec): DateTimeImmutable => $now->modify($spec);
/** Día de Europe/Madrid (hoy + $offset) como fecha a medianoche UTC, convenio de la aplicación. */
$day = static fn(int $offset): DateTimeImmutable => new DateTimeImmutable(
    (new DateTimeImmutable('now', $madrid))->modify(($offset >= 0 ? '+' : '').$offset.' days')->format('Y-m-d'), $utc);
/** Instante de Madrid: día (hoy + $offset) a la hora indicada. */
$at = static fn(int $offset, string $time): DateTimeImmutable => (new DateTimeImmutable(
    (new DateTimeImmutable('now', $madrid))->modify(($offset >= 0 ? '+' : '').$offset.' days')->format('Y-m-d').' '.$time, $madrid));

$hash = password_hash(DEMO_PASSWORD, PASSWORD_BCRYPT);
$users = [];
$mkUser = function (string $key, string $name, ?string $role) use ($em, $hash, &$users): User {
    $u = new User($key.'@example.org', $name);
    $u->roles = $role === null ? [] : [$role];
    $u->password = $hash;
    $em->persist($u);
    return $users[$key] = $u;
};
$mkUser('voluntaria', 'Vera Voluntaria', 'ROLE_VOLUNTEER');
$mkUser('victor', 'Víctor Voluntario', 'ROLE_VOLUNTEER');
$mkUser('valeria', 'Valeria Voluntaria', 'ROLE_VOLUNTEER');
$mkUser('veterinario', 'Vicente Veterinario', 'ROLE_VET');
$mkUser('coordinadora', 'Coral Coordinadora', 'ROLE_COORDINATOR');
$mkUser('brisa', 'Familia Brisa', 'ROLE_FOSTER');
$mkUser('nube', 'Familia Nube', 'ROLE_FOSTER');
foreach ([
    'sara' => 'Sara Solicitante', 'samuel' => 'Samuel Solicitante', 'silvia' => 'Silvia Solicitante', 'sergio' => 'Sergio Solicitante',
    'sonia' => 'Sonia Solicitante', 'samanta' => 'Samanta Solicitante', 'sandra' => 'Sandra Solicitante', 'adriana' => 'Adriana Adoptante',
    'paula' => 'Paula Padrina', 'pablo' => 'Pablo Padrino',
] as $key => $name) { $mkUser($key, $name, null); }
$coordinator = $users['coordinadora'];

/** @var array<string, Animal> $animals */
$animals = [];
$photoIds = [];
$variant = 0;
$mkAnimal = function (string $key, string $suffix, string $species, string $size, int $months, DateTimeImmutable $received, string $status, string $origin, int $photos = 2, bool $fostered = false)
    use ($em, &$animals, &$photoIds, &$variant, $uploads): Animal {
    $a = new Animal($species, $months, $size, $origin, $received);
    $a->code = 'HUE-'.$suffix;
    $a->status = $status;
    $a->fostered = $fostered;
    $em->persist($a);
    for ($i = 0; $i < $photos; $i++) {
        $name = bin2hex(random_bytes(24)).'.png';
        file_put_contents($uploads.'/'.$name, demoPetPng($species, $variant++));
        $photo = new AnimalPhoto($a, $name, 'image/png');
        $em->persist($photo);
        $photoIds[$key][] = $photo->id;
    }
    return $animals[$key] = $a;
};

$mkAnimal('cuarentena', 'CUARENT0001', 'dog', 'medium', 8, $ago('-2 days'), 'quarantine', 'Encontrado en el polígono ficticio «Las Acacias»; llegó con collar sin chip.');
$mkAnimal('cumplida', 'CUARENT0002', 'cat', 'small', 24, $ago('-11 days'), 'quarantine', 'Entregada por un vecino ficticio de la calle del Olivo. Ya ha pasado diez días en cuarentena.');
$mkAnimal('cachorro', 'DISPON000001', 'dog', 'small', 6, $ago('-20 days'), 'available', 'Camada nacida en una nave ficticia de «Los Almendros».');
$mkAnimal('adulto', 'DISPON000002', 'dog', 'medium', 24, $ago('-25 days'), 'available', 'Cedido por una familia ficticia que se mudaba al extranjero.');
$mkAnimal('senior', 'DISPON000003', 'dog', 'small', 96, $ago('-40 days'), 'available', 'Hallado deambulando cerca de una estación ficticia. Tranquilo y cariñoso.');
$mkAnimal('gatito', 'DISPON000004', 'cat', 'small', 10, $ago('-18 days'), 'available', 'Rescatada de un jardín ficticio durante una tormenta.');
$mkAnimal('gato', 'DISPON000005', 'cat', 'medium', 36, $ago('-30 days'), 'available', 'Abandonado en una gasolinera ficticia de la carretera comarcal.');
$mkAnimal('grande', 'DISPON000006', 'dog', 'large', 60, $ago('-35 days'), 'available', 'Procedente de una finca ficticia cerrada; sociable con otros perros.');
$mkAnimal('acogido', 'ACOGIDA00001', 'cat', 'small', 18, $ago('-27 days'), 'available', 'Gata tímida que mejora en un hogar tranquilo.', 2, true);
$mkAnimal('reservado', 'RESERVA00001', 'dog', 'medium', 30, $ago('-45 days'), 'reserved', 'Perro juguetón recogido en un parque ficticio.');
$mkAnimal('adoptado', 'ADOPTAD00001', 'cat', 'medium', 48, $ago('-90 days'), 'adopted', 'Gato casero devuelto por una protectora ficticia vecina.');
if ($releaseIn !== null) {
    $mkAnimal('liberable', 'CUMPLE000001', 'dog', 'small', 14, $ago('-240 hours')->modify('+'.$releaseIn.' seconds'), 'quarantine', 'Recibida hace casi diez días en una protectora ficticia vecina; su cuarentena termina en unos instantes.');
}

// Revisión veterinaria previa del gato en acogida y del reservado.
$em->persist(new VeterinaryReview($animals['reservado'], $users['veterinario'], $ago('-44 days'), 'Vacunas al día. Sin hallazgos. Apto para adopción.'));
$em->persist(new VeterinaryReview($animals['cuarentena'], $users['veterinario'], $ago('-1 day'), 'Revisión inicial: peso adecuado, sin parásitos visibles. Vigilar apetito.'));

$questionnaire = static fn(string $housingType, string $housing, string $experienceType, string $experience, string $dailyTime, string $availability): array => [
    'housingType' => $housingType, 'housing' => $housing, 'experienceType' => $experienceType, 'experience' => $experience,
    'dailyTime' => $dailyTime, 'availability' => $availability,
];
$mkApplication = function (string $applicant, string $animal, string $status, DateTimeImmutable $submitted, ?array $q = null) use ($em, &$users, &$animals, $questionnaire): AdoptionApplication {
    $q ??= $questionnaire('flat', 'Piso de 80 m² con balcón, dos adultos y sin otros animales. Se permiten mascotas en el contrato.',
        'dogs', 'Hemos convivido con un perro durante doce años.', '2to4', 'Trabajamos por turnos y siempre hay una persona en casa por las tardes.');
    $a = new AdoptionApplication($animals[$animal], $users[$applicant], $q, $submitted);
    $a->status = $status;
    $em->persist($a);
    return $a;
};

// Samuel ya tiene dos solicitudes abiertas (límite de HU-03).
$mkApplication('samuel', 'gato', 'pending', $ago('-3 days'));
$mkApplication('samuel', 'grande', 'pending', $ago('-2 days'));
// Tres candidaturas pendientes para el mismo animal (HU-04).
$mkApplication('silvia', 'gatito', 'pending', $ago('-4 days'), $questionnaire('house_outdoor', 'Casa con patio cerrado de 40 m², dos adultos y un niño de nueve años.', 'cats', 'He tenido dos gatos, el último hasta hace un año.', 'gt4', 'Teletrabajo cuatro días por semana; mi madre vive cerca y puede ayudar.'));
$mkApplication('sergio', 'gatito', 'pending', $ago('-3 days'), $questionnaire('flat', 'Piso de 55 m², vivo solo, propietario.', 'none', '', 'lt2', 'Salgo a las 8:00 y vuelvo a las 18:00; un vecino puede pasar al mediodía.'));
$mkApplication('sonia', 'gatito', 'pending', $ago('-2 days'), $questionnaire('house', 'Casa adosada sin jardín, familia de cuatro personas.', 'both', 'Hemos tenido gatos y perros durante toda la vida.', '2to4', 'Siempre hay alguien en casa; en vacaciones nos lo quedamos todos.'));
// Reserva vigente (HU-05): una aprobada y una en espera, y la solicitud formalizada del animal adoptado.
$approved = $mkApplication('samanta', 'reservado', 'approved', $ago('-6 days'), $questionnaire('house_outdoor', 'Casa con jardín vallado de 120 m², pareja sin hijos.', 'dogs', 'Dos perros adoptados a lo largo de veinte años.', 'gt4', 'Uno de los dos trabaja desde casa; paseos al amanecer y al atardecer.'));
$approved->interviewAt = $ago('-3 days');
$approved->interviewNotes = 'Entrevista favorable: la familia conoce las necesidades de un perro joven y ha visitado al animal dos veces.';
$approved->decisionAt = $ago('-2 days');
$mkApplication('sandra', 'reservado', 'waiting', $ago('-5 days'), $questionnaire('flat', 'Piso con terraza de 70 m², una persona.', 'dogs', 'Un perro mediano durante diez años.', '2to4', 'Trabajo en horario de mañana y paseo por las tardes.'));
$formalized = $mkApplication('adriana', 'adoptado', 'formalized', $ago('-30 days'));
$formalized->interviewAt = $ago('-26 days');
$formalized->interviewNotes = 'Entrevista favorable.';
$formalized->decisionAt = $ago('-25 days');
$deliveredAt = $ago('-20 days');
$adoption = new Adoption($formalized, $coordinator, $deliveredAt, 9500);
foreach (FollowUp::KINDS as $kind => $months) { $adoption->scheduleFollowUp($kind, FollowUpCalendar::dueDate($deliveredAt, $months)); }
$em->persist($adoption);

// Apadrinamientos del animal reservado (HU-08), con una novedad publicada.
$paulaSponsorship = new Sponsorship($animals['reservado'], $users['paula'], 1550, $ago('-60 days'));
$pabloSponsorship = new Sponsorship($animals['reservado'], $users['pablo'], 1000, $ago('-30 days'));
$em->persist($paulaSponsorship);
$em->persist($pabloSponsorship);
$em->persist(new SponsorUpdate($animals['reservado'], $coordinator, $ago('-20 days'), 'Esta semana ha aprendido a pasear con correa y ya reconoce su nombre. ¡Gracias por vuestro apoyo!'));

// Casa de acogida de la Familia Brisa con el gato en acogida y una novedad.
$brisaHome = new FosterHome($users['brisa'], 2, $ago('-10 days'));
$brisaHome->addPeriod($day(0), $day(60));
$em->persist($brisaHome);
$placement = new FosterPlacement($animals['acogido'], $brisaHome, $coordinator, $day(-3), $day(20));
$em->persist($placement);
$em->persist(new FosterNote($placement, $users['brisa'], $ago('-2 days'), 'Come bien y se deja acariciar. Todavía se esconde cuando suena el timbre.'));

// Turnos de voluntariado (HU-07), relativos al momento de la carga.
$mkShift = function (string $key, string $kind, DateTimeImmutable $start, DateTimeImmutable $end, int $min, int $max, array $signups) use ($em, $coordinator, $now, &$users, &$shifts): Shift {
    $s = new Shift($kind, $start, $end, $min, $max, $coordinator, $now);
    $em->persist($s);
    foreach ($signups as $who) { $em->persist(new ShiftSignup($s, $users[$who], $now->modify('-1 day'))); }
    return $shifts[$key] = $s;
};
$shifts = [];
$mkShift('paseoManana', 'walk', $at(1, '10:00'), $at(1, '12:00'), 2, 5, ['victor']);
$mkShift('limpiezaCompleta', 'cleaning', $at(1, '16:00'), $at(1, '18:00'), 2, 2, ['victor', 'valeria']);
$mkShift('limpiezaCoincide', 'cleaning', $at(1, '11:00'), $at(1, '13:00'), 2, 3, []);
$mkShift('paseoSemana', 'walk', $at(5, '10:00'), $at(5, '12:00'), 2, 4, ['victor']);
$mkShift('paseoTresDias', 'walk', $at(3, '09:00'), $at(3, '11:00'), 1, 3, []);
$madridNow = $now->setTimezone($madrid);
if ((int) $madridNow->format('G') >= 1 && (int) $madridNow->format('G') < 22) {
    $mkShift('enCurso', 'walk', $now->modify('-30 minutes'), $now->modify('+90 minutes'), 1, 3, ['valeria']);
}

$em->flush();

$out = [
    'password' => DEMO_PASSWORD,
    'generado' => $now->format(DATE_ATOM),
    'users' => array_map(static fn(User $u) => ['id' => $u->id, 'email' => $u->email, 'name' => $u->name], $users),
    'animals' => array_map(static fn(Animal $a) => ['id' => $a->id, 'code' => $a->code, 'status' => $a->status], $animals),
    'photos' => $photoIds,
    'shifts' => array_map(static fn(Shift $s) => $s->id, $shifts),
    'adoptions' => ['adoptado' => $adoption->id],
];
if ($asJson) {
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
} else {
    fwrite(STDERR, sprintf("Base «%s» cargada: %d cuentas, %d animales. Contraseña de todas las cuentas: %s\n", $dbName, count($users), count($animals), DEMO_PASSWORD));
    foreach ($users as $key => $u) { fwrite(STDERR, sprintf("  %-14s %-28s %s\n", $key, $u->email, implode(',', $u->roles) ?: 'ROLE_USER'));  }
}
