<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{AdoptionApplication, Animal, User};
use App\Service\{ApplicationRefused, ApplicationService};

class Spec004Test extends WebTestBase
{
    private const QUESTIONNAIRE = [
        'housingType' => 'flat',
        'housing' => 'Piso de 80 metros cuadrados con terraza protegida.',
        'experienceType' => 'cats',
        'experience' => 'Diez años conviviendo con gatos.',
        'dailyTime' => '2to4',
        'availability' => 'Trabajo cerca de casa y puedo dedicarle tiempo suficiente.',
    ];

    private function createApplication(Animal $animal, ?User $applicant = null, string $submittedAt = '-3 days'): AdoptionApplication
    {
        $applicant ??= $this->user('ROLE_USER', 'adoptante_' . uniqid() . '@example.test');
        $app = new AdoptionApplication($animal, $applicant, self::QUESTIONNAIRE, new \DateTimeImmutable($submittedAt, new \DateTimeZone('UTC')));
        $this->em->persist($app);
        $this->em->flush();
        return $app;
    }

    private function coordinator(): User
    {
        return $this->user('ROLE_COORDINATOR', 'coordinadora@example.test');
    }

    public function testCoordinatorCanViewApplicationAndRecordInterview(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $application->animal->code);
        self::assertSelectorTextContains('main', 'Piso de 80 metros cuadrados');

        $token = $crawler->filter('form[action$="/entrevista"] input[name="_token"]')->attr('value');
        $interviewDate = (new \DateTimeImmutable('-1 hour', new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i');
        $notes = 'Entrevista realizada por videollamada. Familia muy adecuada y responsable.';

        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/entrevista', [
            '_token' => $token,
            'interviewAt' => $interviewDate,
            'interviewNotes' => $notes,
        ]);

        self::assertResponseRedirects('/admin/solicitudes/' . $application->id, 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Entrevista registrada correctamente');
        self::assertSelectorTextContains('main', $notes);

        $em = $this->refreshEm();
        $em->clear();
        $refreshed = $em->find(AdoptionApplication::class, $application->id);
        self::assertNotNull($refreshed->interviewAt);
        self::assertSame($notes, $refreshed->interviewNotes);
    }

    public function testInterviewRequiresValidDateAndNotes(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        $token = $crawler->filter('form[action$="/entrevista"] input[name="_token"]')->attr('value');

        // Notas vacías
        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/entrevista', [
            '_token' => $token,
            'interviewAt' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i'),
            'interviewNotes' => '   ',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-interviewNotes', 'Escribe las observaciones');

        // Fecha inválida
        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/entrevista', [
            '_token' => $token,
            'interviewAt' => 'fecha-invalida',
            'interviewNotes' => 'Notas válidas',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-interviewAt', 'Indica una fecha y hora');

        $em = $this->refreshEm();
        $em->clear();
        $refreshed = $em->find(AdoptionApplication::class, $application->id);
        self::assertNull($refreshed->interviewAt);
    }

    public function testRejectionWithoutReasonIsNotAdmitted(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        $token = $crawler->filter('form[action$="/rechazar"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/rechazar', [
            '_token' => $token,
            'rejectionReason' => '   ',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-rejectionReason', 'Indica el motivo justificado');

        $em = $this->refreshEm();
        $em->clear();
        $refreshed = $em->find(AdoptionApplication::class, $application->id);
        self::assertSame('pending', $refreshed->status);
        self::assertNull($refreshed->rejectionReason);
    }

    public function testRejectionWithReasonIsRecordedAndDoesNotAlterAnimalAvailability(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        $token = $crawler->filter('form[action$="/rechazar"] input[name="_token"]')->attr('value');

        $reason = 'La vivienda no reúne las condiciones de seguridad necesarias para este perro.';
        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/rechazar', [
            '_token' => $token,
            'rejectionReason' => $reason,
        ]);

        self::assertResponseRedirects('/admin/solicitudes/' . $application->id, 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Candidatura rechazada');
        self::assertSelectorTextContains('main', $reason);

        $em = $this->refreshEm();
        $em->clear();
        $refreshedApp = $em->find(AdoptionApplication::class, $application->id);
        self::assertSame('rejected', $refreshedApp->status);
        self::assertSame($reason, $refreshedApp->rejectionReason);
        self::assertNotNull($refreshedApp->decisionAt);

        $refreshedAnimal = $em->find(Animal::class, $animal->id);
        self::assertSame('available', $refreshedAnimal->status);
    }

    public function testApprovalWithoutInterviewIsNotAdmitted(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        $token = $crawler->filter('form[action$="/aprobar"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/aprobar', [
            '_token' => $token,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', 'imprescindible haber realizado y registrado previamente la entrevista');

        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('pending', $em->find(AdoptionApplication::class, $application->id)->status);
        self::assertSame('available', $em->find(Animal::class, $animal->id)->status);
    }

    public function testApprovalWithFutureInterviewIsNotAdmitted(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);
        $application->interviewAt = new \DateTimeImmutable('+2 days', new \DateTimeZone('UTC'));
        $application->interviewNotes = 'Entrevista planificada para pasado mañana.';
        $this->em->flush();

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        $token = $crawler->filter('form[action$="/aprobar"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/aprobar', [
            '_token' => $token,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', 'con fecha no futura');

        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('pending', $em->find(AdoptionApplication::class, $application->id)->status);
        self::assertSame('available', $em->find(Animal::class, $animal->id)->status);
    }

    public function testApprovalAfterValidInterviewReservesAnimalAndMovesOtherApplicationsToWaiting(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');

        $app1 = $this->createApplication($animal);
        $app2 = $this->createApplication($animal);
        $app3 = $this->createApplication($animal);

        // app1 tiene entrevista pasada
        $app1->interviewAt = new \DateTimeImmutable('-2 hours', new \DateTimeZone('UTC'));
        $app1->interviewNotes = 'Entrevista presencial muy satisfactoria.';
        $this->em->flush();

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $app1->id);
        $token = $crawler->filter('form[action$="/aprobar"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/solicitudes/' . $app1->id . '/aprobar', [
            '_token' => $token,
        ]);

        self::assertResponseRedirects('/admin/solicitudes/' . $app1->id, 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Candidatura aprobada');
        self::assertSelectorTextContains('main', 'animal se encuentra formalmente reservado');

        $em = $this->refreshEm();
        $em->clear();

        // app1 -> approved
        $ref1 = $em->find(AdoptionApplication::class, $app1->id);
        self::assertSame('approved', $ref1->status);
        self::assertNotNull($ref1->decisionAt);

        // animal -> reserved
        $refAnimal = $em->find(Animal::class, $animal->id);
        self::assertSame('reserved', $refAnimal->status);

        // app2 y app3 -> waiting
        $ref2 = $em->find(AdoptionApplication::class, $app2->id);
        $ref3 = $em->find(AdoptionApplication::class, $app3->id);
        self::assertSame('waiting', $ref2->status);
        self::assertSame('waiting', $ref3->status);
    }

    public function testCannotApproveApplicationForAlreadyReservedAnimal(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');

        $app1 = $this->createApplication($animal);
        $app2 = $this->createApplication($animal);

        $app1->interviewAt = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));
        $app1->interviewNotes = 'Entrevista 1.';
        $app2->interviewAt = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));
        $app2->interviewNotes = 'Entrevista 2.';
        $this->em->flush();

        // Aprobamos app1 directamente con el servicio
        $service = self::getContainer()->get(ApplicationService::class);
        $service->approve($app1);

        // Intentamos aprobar app2 mediante HTTP
        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $app2->id);
        $token = $crawler->filter('form[action$="/aprobar"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/solicitudes/' . $app2->id . '/aprobar', [
            '_token' => $token,
        ]);

        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('main', 'ya está reservado para otra candidatura');

        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('approved', $em->find(AdoptionApplication::class, $app1->id)->status);
        self::assertSame('waiting', $em->find(AdoptionApplication::class, $app2->id)->status);
        self::assertSame('reserved', $em->find(Animal::class, $animal->id)->status);
    }

    public function testReleaseReservationRestoresAnimalToAvailableAndWaitingToPending(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');

        $app1 = $this->createApplication($animal);
        $app2 = $this->createApplication($animal);

        $app1->interviewAt = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));
        $app1->interviewNotes = 'Entrevista lista.';
        $this->em->flush();

        $service = self::getContainer()->get(ApplicationService::class);
        $service->approve($app1);

        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('reserved', $em->find(Animal::class, $animal->id)->status);
        self::assertSame('waiting', $em->find(AdoptionApplication::class, $app2->id)->status);

        // Coordinadora libera la reserva
        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $app1->id);
        $token = $crawler->filter('form[action$="/cancelar-reserva"] input[name="_token"]')->attr('value');

        $cancellationReason = 'El adoptante cancela su solicitud por traslado laboral.';
        $this->client->request('POST', '/admin/solicitudes/' . $app1->id . '/cancelar-reserva', [
            '_token' => $token,
            'cancellationReason' => $cancellationReason,
        ]);

        self::assertResponseRedirects('/admin/solicitudes/' . $app1->id, 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Reserva liberada');
        self::assertSelectorTextContains('main', $cancellationReason);

        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('cancelled', $em->find(AdoptionApplication::class, $app1->id)->status);
        self::assertSame($cancellationReason, $em->find(AdoptionApplication::class, $app1->id)->cancellationReason);
        self::assertSame('available', $em->find(Animal::class, $animal->id)->status);
        self::assertSame('pending', $em->find(AdoptionApplication::class, $app2->id)->status);
    }

    public function testDecidedApplicationAdmitsNoFurtherDecisions(): void
    {
        $coordinator = $this->coordinator();
        $animal = $this->animal(0, 'available');
        $approved = $this->createApplication($animal);
        $approved->interviewAt = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));
        $approved->interviewNotes = 'Entrevista realizada.';
        $this->em->flush();

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $approved->id);
        $rejectToken = $crawler->filter('form[action$="/rechazar"] input[name="_token"]')->attr('value');
        self::getContainer()->get(ApplicationService::class)->approve($approved);

        // La ficha de una solicitud aprobada ya no ofrece aprobar, rechazar ni modificar la entrevista
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $approved->id);
        self::assertCount(0, $crawler->filter('form[action$="/aprobar"], form[action$="/rechazar"], form[action$="/entrevista"]'));

        // Un rechazo forzado sobre la solicitud aprobada no deja el animal reservado sin candidatura
        $this->client->request('POST', '/admin/solicitudes/' . $approved->id . '/rechazar', [
            '_token' => $rejectToken,
            'rejectionReason' => 'Intento de rechazo tras la aprobación.',
        ]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('main', 'ya está resuelta');

        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('approved', $em->find(AdoptionApplication::class, $approved->id)->status);
        self::assertNull($em->find(AdoptionApplication::class, $approved->id)->rejectionReason);
        self::assertSame('reserved', $em->find(Animal::class, $animal->id)->status);
    }

    public function testDatabaseRejectsTwoApprovedApplicationsForSameAnimal(): void
    {
        $animal = $this->animal(0, 'available');
        $app1 = $this->createApplication($animal);
        $app2 = $this->createApplication($animal);
        $connection = $this->em->getConnection();
        $connection->executeStatement("UPDATE adoptionapplication SET status = 'approved' WHERE id = ?", [$app1->id]);

        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $connection->executeStatement("UPDATE adoptionapplication SET status = 'approved' WHERE id = ?", [$app2->id]);
    }

    public function testReleaseReservationRequiresReason(): void
    {
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);
        $application->interviewAt = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));
        $application->interviewNotes = 'Entrevista realizada.';
        $this->em->flush();
        self::getContainer()->get(ApplicationService::class)->approve($application);

        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        $token = $crawler->filter('form[action$="/cancelar-reserva"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/solicitudes/' . $application->id . '/cancelar-reserva', [
            '_token' => $token,
            'cancellationReason' => '  ',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-cancellationReason', 'Indica el motivo');

        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('approved', $em->find(AdoptionApplication::class, $application->id)->status);
        self::assertSame('reserved', $em->find(Animal::class, $animal->id)->status);
    }

    public function testRoleAccessControl(): void
    {
        $animal = $this->animal(0, 'available');
        $application = $this->createApplication($animal);

        // Usuario solicitante recibe 403
        $this->client->loginUser($this->user('ROLE_USER', 'solicitante@example.test'));
        $this->client->request('GET', '/admin/solicitudes');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/admin/solicitudes/' . $application->id);
        self::assertResponseStatusCodeSame(403);

        // Voluntario sin rol de coordinadora recibe 403
        $this->client->loginUser($this->user('ROLE_VOLUNTEER', 'voluntario@example.test'));
        $this->client->request('GET', '/admin/solicitudes');
        self::assertResponseStatusCodeSame(403);
    }

    // ---- Revisión previa a la entrega (2026-11-21) ----

    /** Solicitud leída de la base con un gestor de entidades limpio. */
    private function fresh(string $id): AdoptionApplication
    {
        $em = $this->refreshEm();
        $em->clear();
        return $em->find(AdoptionApplication::class, $id);
    }

    private function withInterview(AdoptionApplication $application, string $when = '-1 hour'): AdoptionApplication
    {
        $application->interviewAt = new \DateTimeImmutable($when, new \DateTimeZone('UTC'));
        $application->interviewNotes = 'Entrevista realizada.';
        $this->em->flush();
        return $application;
    }

    /** @return list<string> salida ordenada de cada proceso */
    private function race(array $jobs): array
    {
        $processes = [];
        foreach ($jobs as [$mode, $application, $startAt]) {
            $command = [PHP_BINARY, dirname(__DIR__).'/tests/Support/application-race.php', $mode, $application->id, $startAt];
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
        return $results;
    }

    // C1-01: aprobar y rechazar a la vez la misma solicitud nunca deja una mezcla (aprobada con animal disponible o rechazada con animal reservado).
    public function testSimultaneousApproveAndRejectOfTheSameApplicationNeverMix(): void
    {
        for ($round = 0; $round < 2; ++$round) {
            $animal = $this->animal(0, 'available');
            $application = $this->withInterview($this->createApplication($animal));
            $other = $this->createApplication($animal);
            $startAt = sprintf('%.6F', microtime(true) + 3);

            $results = $this->race([['approve', $application, $startAt], ['reject', $application, $startAt]]);

            $connection = $this->refreshEm()->getConnection();
            $state = [
                $connection->fetchOne('SELECT status FROM adoptionapplication WHERE id = ?', [$application->id]),
                $connection->fetchOne('SELECT status FROM animal WHERE id = ?', [$animal->id]),
                $connection->fetchOne('SELECT status FROM adoptionapplication WHERE id = ?', [$other->id]),
            ];
            if ($results === ['approve:ok', 'reject:rechazada']) {
                self::assertSame(['approved', 'reserved', 'waiting'], $state, 'Ronda '.$round);
            } else {
                self::assertSame(['approve:rechazada', 'reject:ok'], $results, 'Ronda '.$round);
                self::assertSame(['rejected', 'available', 'pending'], $state, 'Ronda '.$round);
            }
        }
    }

    // C1-01: con una página obsoleta (estado en memoria desfasado) ninguna decisión pisa la que ya consta en la base.
    public function testDecisionsReReadTheStateUnderLock(): void
    {
        $service = self::getContainer()->get(ApplicationService::class);
        $animal = $this->animal(0, 'available');
        $application = $this->withInterview($this->createApplication($animal));
        $this->em->getConnection()->executeStatement("UPDATE adoptionapplication SET status = 'rejected', rejectionReason = 'Rechazada en otra pestaña.' WHERE id = ?", [$application->id]);
        self::assertSame('pending', $application->status, 'El objeto en memoria sigue desfasado.');

        foreach ([
            static fn() => $service->approve($application),
            static fn() => $service->reject($application, ['rejectionReason' => 'Segundo rechazo.']),
            static fn() => $service->recordInterview($application, ['interviewAt' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i'), 'interviewNotes' => 'Tarde.']),
        ] as $decision) {
            try {
                $decision();
                self::fail('Se esperaba el rechazo de la decisión sobre una solicitud ya resuelta.');
            } catch (ApplicationRefused $error) {
                self::assertStringContainsString('ya está resuelta (rechazada)', $error->getMessage());
            }
        }
        $connection = $this->refreshEm()->getConnection();
        self::assertSame('rejected', $connection->fetchOne('SELECT status FROM adoptionapplication WHERE id = ?', [$application->id]));
        self::assertSame('Rechazada en otra pestaña.', $connection->fetchOne('SELECT rejectionReason FROM adoptionapplication WHERE id = ?', [$application->id]));
        self::assertSame('available', $connection->fetchOne('SELECT status FROM animal WHERE id = ?', [$animal->id]));
    }

    // C1-01: el conflicto de estado de entrevista, aprobación y rechazo responde 409, como la liberación y la formalización.
    public function testStateConflictsAnswerConflict(): void
    {
        $application = $this->withInterview($this->createApplication($this->animal(0, 'available')));
        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        $tokens = [];
        foreach (['entrevista', 'aprobar', 'rechazar'] as $action) {
            $tokens[$action] = $crawler->filter('form[action$="/'.$action.'"] input[name="_token"]')->attr('value');
        }
        $this->refreshEm()->getConnection()->executeStatement("UPDATE adoptionapplication SET status = 'rejected' WHERE id = ?", [$application->id]);

        $interviewAt = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i');
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/entrevista', ['_token' => $tokens['entrevista'], 'interviewAt' => $interviewAt, 'interviewNotes' => 'Tarde.']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('main', 'ya está resuelta');
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/aprobar', ['_token' => $tokens['aprobar']]);
        self::assertResponseStatusCodeSame(409);
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/rechazar', ['_token' => $tokens['rechazar'], 'rejectionReason' => 'Otra vez.']);
        self::assertResponseStatusCodeSame(409);
    }

    // C1-02 / C2-06: el estado del animal sale en español, nunca el código interno.
    public function testApprovalRefusalNamesTheAnimalStateInSpanish(): void
    {
        $service = self::getContainer()->get(ApplicationService::class);
        foreach (['reserved' => 'ya está reservado para otra candidatura', 'quarantine' => 'estado actual: en cuarentena', 'adopted' => 'estado actual: adoptado'] as $status => $expected) {
            $application = $this->withInterview($this->createApplication($this->animal(0, 'available')));
            $this->em->getConnection()->executeStatement('UPDATE animal SET status = ? WHERE id = ?', [$status, $application->animal->id]);
            try {
                $service->approve($application);
                self::fail('Se esperaba el rechazo de la aprobación.');
            } catch (ApplicationRefused $error) {
                self::assertStringContainsString($expected, $error->getMessage());
                self::assertStringNotContainsString($status, $error->getMessage());
            }
            $this->refreshEm();
            $service = self::getContainer()->get(ApplicationService::class);
        }
    }

    // C1-03: tras un 422 los formularios plegables salen abiertos con el error y con lo escrito.
    public function testInterviewFormStaysOpenWithTypedValuesAfterValidationError(): void
    {
        $application = $this->createApplication($this->animal(0, 'available'));
        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        self::assertSelectorNotExists('details[open]');
        $token = $crawler->filter('form[action$="/entrevista"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/entrevista', [
            '_token' => $token, 'interviewAt' => '2026-11-20T10:30', 'interviewNotes' => str_repeat('ñ', 5001),
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('details[open] form[action$="/entrevista"]');
        self::assertSelectorExists('#error-interviewNotes');
        $crawler = $this->client->getCrawler();
        self::assertSame('2026-11-20T10:30', $crawler->filter('#interviewAt')->attr('value'));
        self::assertSame(str_repeat('ñ', 5001), $crawler->filter('#interviewNotes')->text());
    }

    public function testRejectionAndReleaseFormsKeepTypedValuesAfterValidationError(): void
    {
        $application = $this->createApplication($this->animal(0, 'available'));
        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        $token = $crawler->filter('form[action$="/rechazar"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/rechazar', ['_token' => $token, 'rejectionReason' => str_repeat('ñ', 2001)]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame(str_repeat('ñ', 2001), $this->client->getCrawler()->filter('#rejectionReason')->text());

        $this->refreshEm();
        $reserved = $this->withInterview($this->createApplication($this->animal(0, 'available')));
        self::getContainer()->get(ApplicationService::class)->approve($reserved);
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$reserved->id);
        self::assertSelectorNotExists('details[open]');
        self::assertStringNotContainsString('(D11)', $this->client->getResponse()->getContent());
        $token = $crawler->filter('form[action$="/cancelar-reserva"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/solicitudes/'.$reserved->id.'/cancelar-reserva', ['_token' => $token, 'cancellationReason' => str_repeat('ñ', 2001)]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('details[open] form[action$="/cancelar-reserva"]');
        self::assertSelectorExists('#error-cancellationReason');
        self::assertSame(str_repeat('ñ', 2001), $this->client->getCrawler()->filter('#cancellationReason')->text());
    }

    // C1-12: la entrevista no puede ser anterior al envío de la solicitud.
    public function testInterviewCannotPrecedeTheSubmission(): void
    {
        $application = $this->createApplication($this->animal(0, 'available'), null, '-1 hour');
        $madrid = new \DateTimeZone('Europe/Madrid');
        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        $token = $crawler->filter('form[action$="/entrevista"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/entrevista', [
            '_token' => $token, 'interviewAt' => (new \DateTimeImmutable('-3 hours', $madrid))->format('Y-m-d\TH:i'), 'interviewNotes' => 'Antes de la solicitud.',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-interviewAt', 'anterior al envío');
        self::assertNull($this->fresh($application->id)->interviewAt);

        // En el mismo minuto del envío sí es válida (el formulario trabaja al minuto).
        $sameMinute = $this->fresh($application->id)->submittedAt->setTimezone($madrid)->format('Y-m-d\TH:i');
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/entrevista', [
            '_token' => $token, 'interviewAt' => $sameMinute, 'interviewNotes' => 'En el minuto del envío.',
        ]);
        self::assertResponseRedirects('/admin/solicitudes/'.$application->id, 303);
    }

    // C1-08: CSRF y rol de las cuatro acciones de revisión.
    public function testReviewActionsRequireValidCsrfTokenAndCoordinatorRole(): void
    {
        $application = $this->withInterview($this->createApplication($this->animal(0, 'available')));
        $payloads = [
            'entrevista' => ['interviewAt' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i'), 'interviewNotes' => 'Notas.'],
            'aprobar' => [],
            'rechazar' => ['rejectionReason' => 'Motivo.'],
            'cancelar-reserva' => ['cancellationReason' => 'Motivo.'],
        ];
        $this->client->loginUser($this->coordinator());
        foreach ($payloads as $action => $payload) {
            $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/'.$action, ['_token' => 'no-es-valido'] + $payload);
            self::assertResponseStatusCodeSame(403, 'Token inválido en '.$action);
            $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/'.$action, $payload);
            self::assertResponseStatusCodeSame(403, 'Sin token en '.$action);
        }

        foreach (['ROLE_USER' => 'solicitante@example.test', 'ROLE_VOLUNTEER' => 'voluntario@example.test', 'ROLE_VET' => 'veterinario@example.test'] as $role => $email) {
            $this->client->loginUser($this->user($role, $email));
            foreach ($payloads as $action => $payload) {
                $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/'.$action, ['_token' => 'da-igual'] + $payload);
                self::assertResponseStatusCodeSame(403, $role.' en '.$action);
            }
        }
        $em = $this->refreshEm();
        $em->clear();
        self::assertSame('pending', $em->find(AdoptionApplication::class, $application->id)->status);
        self::assertNull($em->find(AdoptionApplication::class, $application->id)->rejectionReason);
    }

    // C1-09: los textos de la revisión se normalizan y el NUL o el UTF-8 inválido dan 422, no 500.
    public function testReviewTextsAreNormalizedAndRejectNulAndInvalidUtf8(): void
    {
        $this->client->loginUser($this->coordinator());
        $space = "\u{00A0}\u{2003}\u{3000} ";

        $application = $this->createApplication($this->animal(0, 'available'));
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        $token = $crawler->filter('form[action$="/entrevista"] input[name="_token"]')->attr('value');
        foreach (["con\0nulo", "inv\xC3\x28alido"] as $bad) {
            $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/entrevista', [
                '_token' => $token, 'interviewAt' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i'), 'interviewNotes' => $bad,
            ]);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#error-interviewNotes', 'caracteres no admitidos');
        }
        $rejectToken = $this->client->request('GET', '/admin/solicitudes/'.$application->id)->filter('form[action$="/rechazar"] input[name="_token"]')->attr('value');
        foreach (["con\0nulo", "inv\xC3\x28alido"] as $bad) {
            $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/rechazar', ['_token' => $rejectToken, 'rejectionReason' => $bad]);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#error-rejectionReason', 'caracteres no admitidos');
        }
        self::assertSame('pending', $this->fresh($application->id)->status);

        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/rechazar', ['_token' => $rejectToken, 'rejectionReason' => $space.'Sin espacio para mascotas'.$space]);
        self::assertResponseRedirects('/admin/solicitudes/'.$application->id, 303);
        self::assertSame('Sin espacio para mascotas', $this->fresh($application->id)->rejectionReason);

        $this->refreshEm();
        $reserved = $this->withInterview($this->createApplication($this->animal(0, 'available')));
        self::getContainer()->get(ApplicationService::class)->approve($reserved);
        $cancelToken = $this->client->request('GET', '/admin/solicitudes/'.$reserved->id)->filter('form[action$="/cancelar-reserva"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/solicitudes/'.$reserved->id.'/cancelar-reserva', ['_token' => $cancelToken, 'cancellationReason' => "con\0nulo"]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-cancellationReason', 'caracteres no admitidos');
        $this->client->request('POST', '/admin/solicitudes/'.$reserved->id.'/cancelar-reserva', ['_token' => $cancelToken, 'cancellationReason' => $space.'Cambio de domicilio'.$space]);
        self::assertResponseRedirects('/admin/solicitudes/'.$reserved->id, 303);
        self::assertSame('Cambio de domicilio', $this->fresh($reserved->id)->cancellationReason);
    }

    // C1-18: el solicitante ve «realizada» cuando la fecha de la entrevista ya ha pasado.
    public function testMyApplicationsShowsPastInterviewAsDone(): void
    {
        $applicant = $this->user();
        $past = $this->createApplication($this->animal(0, 'available'), $applicant, '-5 days');
        $past->interviewAt = new \DateTimeImmutable('-1 day', new \DateTimeZone('UTC'));
        $past->interviewNotes = 'Hecha.';
        $future = $this->createApplication($this->animal(0, 'available'), $applicant, '-5 days');
        $future->interviewAt = new \DateTimeImmutable('+2 days', new \DateTimeZone('UTC'));
        $future->interviewNotes = 'Prevista.';
        $this->em->flush();

        $this->client->loginUser($applicant);
        $this->client->request('GET', '/mis-solicitudes');
        self::assertResponseIsSuccessful();
        $text = $this->client->getCrawler()->filter('main')->text();
        self::assertStringContainsString('Entrevista realizada: '.$past->interviewAt->setTimezone(new \DateTimeZone('Europe/Madrid'))->format('d/m/Y H:i'), $text);
        self::assertStringContainsString('Entrevista programada: '.$future->interviewAt->setTimezone(new \DateTimeZone('Europe/Madrid'))->format('d/m/Y H:i'), $text);
    }
}
