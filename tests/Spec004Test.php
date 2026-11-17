<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{AdoptionApplication, Animal, User};
use App\Service\ApplicationService;

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

    private function createApplication(Animal $animal, ?User $applicant = null): AdoptionApplication
    {
        $applicant ??= $this->user('ROLE_USER', 'adoptante_' . uniqid() . '@example.test');
        $app = new AdoptionApplication($animal, $applicant, self::QUESTIONNAIRE, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
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

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', 'no está disponible para reserva');

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
}
