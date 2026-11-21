<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{AdoptionApplication, Animal, User, VeterinaryReview};
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Service\{ApplicationRefused, ApplicationService};

class Spec003Test extends WebTestBase
{
    private const ANSWERS = [
        'housingType' => 'house_outdoor',
        'housing' => 'Casa ficticia con jardín vallado; convivimos dos personas adultas.',
        'experienceType' => 'dogs',
        'experience' => 'Hemos convivido con un perro mestizo durante doce años.',
        'dailyTime' => 'gt4',
        'availability' => 'Teletrabajo por las mañanas; una vecina ficticia lo cuidaría en vacaciones.',
    ];

    public function testApplicantSubmitsCompleteQuestionnaireForAvailableAnimal(): void
    {
        $user = $this->user();
        $animal = $this->animal(0, 'available');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/animales/'.$animal->id);
        self::assertResponseIsSuccessful();
        $crawler = $this->client->click($crawler->selectLink('Iniciar solicitud de adopción')->link());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $animal->code);
        $this->client->submit($crawler->selectButton('Enviar solicitud')->form(self::ANSWERS));

        self::assertResponseRedirects('/mis-solicitudes', 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Solicitud enviada');
        self::assertSelectorTextContains('main', $animal->code);
        self::assertSelectorTextContains('main', 'Pendiente de valoración');
        self::assertSelectorTextContains('main', '1 de 2 solicitudes abiertas');

        $em = $this->refreshEm();
        $em->clear();
        $applications = $em->getRepository(AdoptionApplication::class)->findAll();
        self::assertCount(1, $applications);
        self::assertSame($user->id, $applications[0]->applicant->id);
        self::assertSame($animal->id, $applications[0]->animal->id);
        self::assertSame('pending', $applications[0]->status);
        self::assertSame(self::ANSWERS['availability'], $applications[0]->availability);
        self::assertSame('available', $em->find(Animal::class, $animal->id)->status);
    }

    public function testIncompleteQuestionnaireIsNotAdmittedAndListsPendingData(): void
    {
        $animal = $this->animal(0, 'available');
        $this->client->loginUser($this->user());
        $token = $this->formToken($animal);

        $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', [
            '_token' => $token, 'housingType' => 'flat', 'housing' => '   ',
            'experienceType' => 'cats', 'experience' => '', 'dailyTime' => 'siempre', 'availability' => 'Conservar esta respuesta',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-housing', 'Describe la vivienda');
        self::assertSelectorTextContains('#error-experience', 'Explica tu experiencia');
        self::assertSelectorTextContains('#error-dailyTime', 'tiempo diario');
        self::assertSelectorNotExists('#error-housingType');
        self::assertSelectorNotExists('#error-availability');
        self::assertSelectorTextContains('#availability', 'Conservar esta respuesta');
        self::assertCount(0, $this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());

        $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', ['_token' => $token]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorCount(6, '.notification.is-danger li');
        self::assertCount(0, $this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }

    public function testNoPreviousExperienceIsAValidAnswer(): void
    {
        $animal = $this->animal(0, 'available', 'cat');
        $this->client->loginUser($this->user());
        $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', [
            '_token' => $this->formToken($animal), ...self::ANSWERS, 'experienceType' => 'none', 'experience' => '',
        ]);
        self::assertResponseRedirects('/mis-solicitudes', 303);
        $application = $this->refreshEm()->getRepository(AdoptionApplication::class)->findOneBy([]);
        self::assertSame('none', $application->experienceType);
        self::assertSame('', $application->experience);
    }

    public function testThirdOpenApplicationIsRejectedAndExistingOnesAreKept(): void
    {
        $user = $this->user();
        $third = $this->animal(0, 'available', 'cat');
        $this->client->loginUser($user);
        $token = $this->formToken($third);
        $first = $this->application($user, $this->animal(0, 'available'), 'pending');
        $second = $this->application($user, $this->animal(0, 'available'), 'waiting');

        $this->client->request('GET', '/animales/'.$third->id.'/solicitud');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[role="alert"]', 'Ya tienes dos solicitudes abiertas');
        self::assertSelectorNotExists('main form');

        $this->client->request('POST', '/animales/'.$third->id.'/solicitud', ['_token' => $token, ...self::ANSWERS]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('[role="alert"]', 'Ya tienes dos solicitudes abiertas');

        $em = $this->refreshEm();
        $em->clear();
        self::assertCount(2, $em->getRepository(AdoptionApplication::class)->findAll());
        self::assertSame('pending', $em->find(AdoptionApplication::class, $first->id)->status);
        self::assertSame('waiting', $em->find(AdoptionApplication::class, $second->id)->status);
    }

    public function testApprovedCountsAsOpenWhileRejectedAndCancelledDoNot(): void
    {
        $user = $this->user();
        $this->application($user, $this->animal(0, 'reserved'), 'approved');
        $this->application($user, $this->animal(0, 'available'), 'rejected');
        $this->application($user, $this->animal(0, 'available'), 'cancelled');
        $service = self::getContainer()->get(ApplicationService::class);
        self::assertSame(1, $service->openCount($user));

        $this->client->loginUser($user);
        $next = $this->animal(0, 'available');
        $last = $this->animal(0, 'available');
        $nextToken = $this->formToken($next);
        $lastToken = $this->formToken($last);
        $this->client->request('POST', '/animales/'.$next->id.'/solicitud', ['_token' => $nextToken, ...self::ANSWERS]);
        self::assertResponseRedirects('/mis-solicitudes', 303);

        $this->client->request('POST', '/animales/'.$last->id.'/solicitud', ['_token' => $lastToken, ...self::ANSWERS]);
        self::assertResponseStatusCodeSame(409);
        self::assertSame(2, $service->openCount($user));
    }

    public function testDuplicateOpenApplicationForSameAnimalIsRejected(): void
    {
        $user = $this->user();
        $animal = $this->animal(0, 'available');
        $this->client->loginUser($user);
        $token = $this->formToken($animal);
        $this->application($user, $animal, 'pending');

        $this->client->request('GET', '/animales/'.$animal->id.'/solicitud');
        self::assertSelectorTextContains('[role="alert"]', 'Ya tienes una solicitud abierta para este animal');
        $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', ['_token' => $token, ...self::ANSWERS]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('[role="alert"]', 'Ya tienes una solicitud abierta para este animal');
        self::assertCount(1, $this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }

    public function testAnimalsThatAreNotAvailableDoNotReceiveApplications(): void
    {
        $user = $this->user();
        $this->client->loginUser($user);
        foreach (['quarantine', 'reserved', 'adopted'] as $status) {
            $animal = $this->animal(1, $status);
            $this->client->request('GET', '/animales/'.$animal->id.'/solicitud');
            self::assertResponseStatusCodeSame(404);
            $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', self::ANSWERS);
            self::assertResponseStatusCodeSame(404);
        }

        // El animal se reserva después de cargar la ficha: la comprobación bajo bloqueo lo detecta.
        $animal = $this->animal(0, 'available');
        $this->em->getConnection()->executeStatement("UPDATE animal SET status = 'reserved' WHERE id = ?", [$animal->id]);
        try {
            self::getContainer()->get(ApplicationService::class)->submit($user, $animal, self::ANSWERS);
            self::fail('Se esperaba el rechazo de la solicitud.');
        } catch (ApplicationRefused $error) {
            self::assertStringContainsString('ya no está disponible', $error->getMessage());
        }
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM adoptionapplication'));
    }

    public function testConcurrentSubmissionsNeverExceedTwoOpenApplications(): void
    {
        $user = $this->user();
        $animals = [];
        for ($i = 0; $i < 4; ++$i) { $animals[] = $this->animal(0, 'available'); }

        $startAt = sprintf('%.6F', microtime(true) + 3);
        $processes = [];
        foreach ($animals as $animal) {
            $command = [PHP_BINARY, dirname(__DIR__).'/tests/Support/submit-application.php', $user->id, $animal->id, $startAt];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
            self::assertIsResource($process);
            $processes[] = [$process, $pipes];
        }
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $results[] = trim(stream_get_contents($pipes[1]));
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $errors);
        }

        sort($results);
        self::assertSame(['admitida', 'admitida', 'rechazada', 'rechazada'], $results);
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM adoptionapplication WHERE applicant_id = ? AND status IN ('pending','waiting','approved')", [$user->id],
        ));
    }

    public function testCoordinatorAccessesReceivedApplicationWithCompleteQuestionnaire(): void
    {
        $applicant = $this->user('ROLE_USER', 'solicitante@example.test');
        $animal = $this->animal(0, 'available');
        $application = $this->application($applicant, $animal, 'pending');
        $this->client->loginUser($this->user('ROLE_COORDINATOR', 'coordinadora@example.test'));

        $crawler = $this->client->request('GET', '/admin/solicitudes');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $animal->code);
        self::assertSelectorTextContains('main', 'Persona Ficticia');
        $this->client->click($crawler->filter('a[href="/admin/solicitudes/'.$application->id.'"]')->link());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'solicitante@example.test');
        self::assertSelectorTextContains('main', 'Casa con patio o jardín');
        self::assertSelectorTextContains('main', self::ANSWERS['housing']);
        self::assertSelectorTextContains('main', 'Con perros');
        self::assertSelectorTextContains('main', self::ANSWERS['experience']);
        self::assertSelectorTextContains('main', 'Más de 4 horas al día');
        self::assertSelectorTextContains('main', self::ANSWERS['availability']);
        self::assertSelectorTextContains('main', 'Pendiente de valoración');
    }

    public function testPermissionsAndCsrfProtectApplications(): void
    {
        $animal = $this->animal(0, 'available');
        $this->client->request('GET', '/animales/'.$animal->id.'/solicitud');
        self::assertResponseRedirects('/acceso');
        $this->client->request('GET', '/admin/solicitudes');
        self::assertResponseRedirects('/acceso');

        $applicant = $this->user();
        $this->client->loginUser($applicant);
        $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', self::ANSWERS);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/admin/solicitudes');
        self::assertResponseStatusCodeSame(403);

        $application = $this->application($applicant, $animal, 'pending');
        foreach (['ROLE_VOLUNTEER' => 'voluntario@example.test', 'ROLE_VET' => 'veterinario@example.test'] as $role => $email) {
            $this->client->loginUser($this->user($role, $email));
            $this->client->request('GET', '/admin/solicitudes/'.$application->id);
            self::assertResponseStatusCodeSame(403);
        }
        self::assertCount(1, $this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }

    public function testPublicRegistrationCreatesApplicantAccountAndReturnsToApplication(): void
    {
        $animal = $this->animal(0, 'available');
        $this->client->request('GET', '/animales/'.$animal->id.'/solicitud');
        self::assertResponseRedirects('/acceso');

        $crawler = $this->client->request('GET', '/registro');
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        $this->client->request('POST', '/registro', [
            '_token' => $token, 'name' => 'Persona Registrada', 'email' => ' Nueva@Example.test ',
            'password' => 'Ficticia-Registro-2026', 'passwordConfirmation' => 'Ficticia-Registro-2026', 'roles' => ['ROLE_COORDINATOR'],
        ]);
        self::assertResponseRedirects('http://localhost/animales/'.$animal->id.'/solicitud', 303);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('main form button');

        $user = $this->refreshEm()->getRepository(User::class)->findOneBy(['email' => 'nueva@example.test']);
        self::assertSame([], $user->roles);
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $this->client->restart();
        $crawler = $this->client->request('GET', '/registro');
        $this->client->request('POST', '/registro', [
            '_token' => $crawler->filter('input[name="_token"]')->attr('value'), 'name' => 'Otra persona', 'email' => 'nueva@example.test',
            'password' => 'corta', 'passwordConfirmation' => 'corta',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-email', 'Ya existe una cuenta');
        self::assertSelectorTextContains('#error-password', 'al menos 12 caracteres');
        self::assertCount(1, $this->refreshEm()->getRepository(User::class)->findAll());
    }

    private function application(User $user, Animal $animal, string $status): AdoptionApplication
    {
        // El cliente reinicia el kernel entre peticiones: se trabaja con entidades gestionadas por el EM actual.
        $em = $this->refreshEm();
        $application = new AdoptionApplication($em->find(Animal::class, $animal->id), $em->find(User::class, $user->id), self::ANSWERS, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $application->status = $status;
        $em->persist($application);
        $em->flush();
        return $application;
    }

    private function formToken(Animal $animal): string
    {
        return $this->client->request('GET', '/animales/'.$animal->id.'/solicitud')->filter('input[name="_token"]')->attr('value');
    }

    // ---- Revisión previa a la entrega (2026-11-21) ----

    // C1-04: reservado o adoptado entre abrir y enviar el formulario da 409; en cuarentena o sin token válido, 404.
    public function testAnimalReservedOrAdoptedWhileTheFormIsOpenAnswersConflict(): void
    {
        $this->client->loginUser($this->user());
        foreach (['reserved' => 'reservado para otra candidatura', 'adopted' => 'ya ha sido adoptado'] as $status => $expected) {
            $animal = $this->animal(0, 'available');
            $token = $this->formToken($animal);
            $this->refreshEm()->getConnection()->executeStatement('UPDATE animal SET status = ? WHERE id = ?', [$status, $animal->id]);

            $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', ['_token' => $token, ...self::ANSWERS]);
            self::assertResponseStatusCodeSame(409, $status);
            self::assertSelectorTextContains('[role="alert"]', 'ya no está disponible');
            self::assertSelectorTextContains('[role="alert"]', $expected);
            self::assertSelectorNotExists('main form');

            // Sin el token del formulario no se revela nada: 404.
            $this->client->request('POST', '/animales/'.$animal->id.'/solicitud', ['_token' => 'no-es-valido', ...self::ANSWERS]);
            self::assertResponseStatusCodeSame(404);
        }
        $quarantined = $this->animal(0, 'available');
        $token = $this->formToken($quarantined);
        $this->refreshEm()->getConnection()->executeStatement("UPDATE animal SET status = 'quarantine' WHERE id = ?", [$quarantined->id]);
        $this->client->request('POST', '/animales/'.$quarantined->id.'/solicitud', ['_token' => $token, ...self::ANSWERS]);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('POST', '/animales/00000000-0000-4000-8000-000000000000/solicitud', ['_token' => $token, ...self::ANSWERS]);
        self::assertResponseStatusCodeSame(404);
        self::assertCount(0, $this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }

    // C1-09: espacios Unicode recortados; NUL y UTF-8 inválido dan 422, no 500.
    public function testQuestionnaireTextsAreNormalizedAndRejectNulAndInvalidUtf8(): void
    {
        $user = $this->user();
        $this->client->loginUser($user);
        $space = "\u{00A0}\u{2003}\u{3000} ";
        $first = $this->animal(0, 'available');
        $this->client->request('POST', '/animales/'.$first->id.'/solicitud', [
            '_token' => $this->formToken($first), ...self::ANSWERS,
            'housing' => $space.'Casa con jardín'.$space, 'experience' => $space.'Perros'.$space, 'availability' => $space.'Por las tardes'.$space,
        ]);
        self::assertResponseRedirects('/mis-solicitudes', 303);
        $application = $this->refreshEm()->getRepository(AdoptionApplication::class)->findOneBy([]);
        self::assertSame(['Casa con jardín', 'Perros', 'Por las tardes'], [$application->housing, $application->experience, $application->availability]);

        $second = $this->animal(0, 'available');
        $token = $this->formToken($second);
        $this->client->request('POST', '/animales/'.$second->id.'/solicitud', ['_token' => $token, ...self::ANSWERS, 'housing' => $space]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-housing', 'Describe la vivienda');

        foreach (['housing', 'experience', 'availability'] as $field) {
            foreach (["con\0nulo", "inv\xC3\x28alido"] as $bad) {
                $this->client->request('POST', '/animales/'.$second->id.'/solicitud', ['_token' => $token, ...self::ANSWERS, $field => $bad]);
                self::assertResponseStatusCodeSame(422, $field);
                self::assertSelectorTextContains('#error-'.$field, 'caracteres no admitidos');
            }
        }
        self::assertCount(1, $this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }

    // C1-20c: límite de 2000 caracteres de los campos de texto de la solicitud.
    public function testQuestionnaireTextsAreLimitedToTwoThousandCharacters(): void
    {
        $this->client->loginUser($this->user());
        $exact = str_repeat('ñ', 2000);
        $first = $this->animal(0, 'available');
        $this->client->request('POST', '/animales/'.$first->id.'/solicitud', [
            '_token' => $this->formToken($first), ...self::ANSWERS, 'housing' => $exact, 'experience' => $exact, 'availability' => $exact,
        ]);
        self::assertResponseRedirects('/mis-solicitudes', 303);
        $stored = $this->refreshEm()->getRepository(AdoptionApplication::class)->findOneBy([]);
        self::assertSame(2000, mb_strlen($stored->housing));

        $second = $this->animal(0, 'available');
        $token = $this->formToken($second);
        $tooLong = str_repeat('ñ', 2001);
        foreach (['housing', 'experience', 'availability'] as $field) {
            $this->client->request('POST', '/animales/'.$second->id.'/solicitud', ['_token' => $token, ...self::ANSWERS, $field => $tooLong]);
            self::assertResponseStatusCodeSame(422, $field);
            self::assertSelectorExists('#error-'.$field);
            self::assertSame($tooLong, $this->client->getCrawler()->filter('#'.$field)->text(), 'Se conserva lo escrito en '.$field);
        }
        // Sin experiencia el comentario sigue limitado.
        $this->client->request('POST', '/animales/'.$second->id.'/solicitud', ['_token' => $token, ...self::ANSWERS, 'experienceType' => 'none', 'experience' => $tooLong]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('#error-experience');
        self::assertCount(1, $this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }

    // C1-10: la base de datos impide dos solicitudes abiertas de la misma persona para el mismo animal.
    public function testDatabaseRejectsTwoOpenApplicationsOfTheSameApplicantForTheSameAnimal(): void
    {
        $user = $this->user();
        $animal = $this->animal(0, 'available');
        $this->application($user, $animal, 'pending');
        // Las cerradas no cuentan: puede volver a solicitar después de un rechazo o una cancelación.
        $rejected = $this->application($user, $animal, 'rejected');
        $this->application($user, $animal, 'cancelled');
        $connection = $this->refreshEm()->getConnection();

        foreach (['waiting', 'approved', 'pending'] as $open) {
            try {
                $connection->executeStatement('UPDATE adoptionapplication SET status = ? WHERE id = ?', [$open, $rejected->id]);
                self::fail('Se esperaba la violación del índice único parcial (estado '.$open.').');
            } catch (UniqueConstraintViolationException $error) {
                self::assertStringContainsString('uniq_application_open_applicant_animal', $error->getMessage());
            }
        }
        // Otra persona sí puede solicitar el mismo animal.
        $this->application($this->user('ROLE_USER', 'otra@example.test'), $animal, 'pending');
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM adoptionapplication WHERE applicant_id = ? AND status = 'pending'", [$user->id]));
    }

    // C2-03: enviar una solicitud no espera a una inserción con clave foránea sobre la misma cuenta
    // (FOR NO KEY UPDATE no choca con el FOR KEY SHARE de la formalización), aunque sigue serializando los envíos de la cuenta.
    public function testSubmissionDoesNotWaitForForeignKeyInsertsOnTheSameAccount(): void
    {
        $user = $this->user();
        $animal = $this->animal(0, 'available');
        $other = $this->animal(0, 'available');
        $connection = $this->em->getConnection();

        // Otra conexión inserta una fila con clave foránea hacia la cuenta y la deja sin confirmar (toma FOR KEY SHARE sobre ella).
        $holder = \Doctrine\DBAL\DriverManager::getConnection($connection->getParams());
        $holder->beginTransaction();
        try {
            $holder->executeStatement(
                "INSERT INTO adoptionapplication (id, animal_id, applicant_id, housingtype, housing, experiencetype, experience, dailytime, availability, status, submittedat) VALUES (?, ?, ?, 'flat', 'x', 'none', '', 'lt2', 'x', 'rejected', now())",
                ['00000000-0000-4000-8000-0000000000a1', $other->id, $user->id],
            );
            $connection->executeStatement("SET lock_timeout = '2s'");
            $application = self::getContainer()->get(ApplicationService::class)->submit($user, $animal, self::ANSWERS);
            self::assertSame('pending', $application->status);
        } finally {
            $connection->executeStatement('SET lock_timeout = 0');
            $holder->rollBack();
            $holder->close();
        }
    }

    // ---- HU-01 (revisión previa a la entrega): los textos de la ficha y de la revisión veterinaria ----

    // C1-09
    public function testAnimalOriginIsNormalizedAndRejectsNulAndInvalidUtf8(): void
    {
        $this->client->loginUser($this->user('ROLE_VOLUNTEER', 'voluntaria@example.test'));
        $crawler = $this->client->request('GET', '/admin/animales/nuevo');
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        $data = ['_token' => $token, 'species' => 'cat', 'ageMonths' => '6', 'size' => 'small', 'origin' => '', 'receivedAt' => '2026-01-01T10:00'];

        foreach (["con\0nulo", "inv\xC3\x28alido"] as $bad) {
            $this->client->request('POST', '/admin/animales/nuevo', ['origin' => $bad] + $data, ['photos' => [$this->photo()]]);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#error-origin', 'caracteres no admitidos');
        }
        self::assertCount(0, $this->refreshEm()->getRepository(Animal::class)->findAll());

        $this->client->request('POST', '/admin/animales/nuevo', ['origin' => "\u{00A0}\u{3000}Hallado en la calle\u{2003}"] + $data, ['photos' => [$this->photo()]]);
        self::assertResponseRedirects(null, 303);
        self::assertSame('Hallado en la calle', $this->refreshEm()->getRepository(Animal::class)->findOneBy([])->origin);
    }

    // C1-09 y C1-18
    public function testVeterinaryReviewKeepsTypedValuesAndRejectsNulAndInvalidUtf8(): void
    {
        $animal = $this->animal(3);
        $this->client->loginUser($this->user('ROLE_VET', 'veterinaria@example.test'));
        $crawler = $this->client->request('GET', '/admin/animales/'.$animal->id);
        $token = $crawler->filter('form[action$="/revision"] input[name="_token"]')->attr('value');
        $reviewedAt = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i');

        $this->client->request('POST', '/admin/animales/'.$animal->id.'/revision', ['_token' => $token, 'reviewedAt' => '2099-01-01T10:00', 'notes' => 'Texto que no debe perderse']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#notes', 'Texto que no debe perderse');
        self::assertSame('2099-01-01T10:00', $this->client->getCrawler()->filter('#reviewedAt')->attr('value'));

        foreach (["con\0nulo", "inv\xC3\x28alido"] as $bad) {
            $this->client->request('POST', '/admin/animales/'.$animal->id.'/revision', ['_token' => $token, 'reviewedAt' => $reviewedAt, 'notes' => $bad]);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#error-notes', 'caracteres no admitidos');
        }
        self::assertCount(0, $this->refreshEm()->getRepository(VeterinaryReview::class)->findAll());

        $this->client->request('POST', '/admin/animales/'.$animal->id.'/revision', ['_token' => $token, 'reviewedAt' => $reviewedAt, 'notes' => "\u{00A0} Todo bien\u{3000}"]);
        self::assertResponseRedirects(null, 303);
        self::assertSame('Todo bien', $this->refreshEm()->getRepository(VeterinaryReview::class)->findOneBy([])->notes);
    }
}
