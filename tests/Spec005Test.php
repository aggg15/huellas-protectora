<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{Adoption, AdoptionApplication, Animal, AnimalPhoto, FollowUp, User};
use App\Service\{AdoptionService, ApplicationRefused, ApplicationService, FollowUpCalendar, PhotoStorage, ValidationError};
use Symfony\Component\Clock\MockClock;

/** Criterios de aceptación de HU-05 (CA-05-01 a CA-05-11). */
class Spec005Test extends WebTestBase
{
    private const QUESTIONNAIRE = [
        'housingType' => 'house_outdoor',
        'housing' => 'Casa ficticia con patio vallado y dos personas adultas.',
        'experienceType' => 'dogs',
        'experience' => 'Experiencia ficticia con dos perros adoptados.',
        'dailyTime' => 'gt4',
        'availability' => 'Teletrabajo ficticio; un familiar lo cuida en las ausencias.',
    ];

    private function application(Animal $animal, string $status, ?User $applicant = null): AdoptionApplication
    {
        $applicant ??= $this->user('ROLE_USER', 'adoptante_'.bin2hex(random_bytes(4)).'@example.test');
        $application = new AdoptionApplication($animal, $applicant, self::QUESTIONNAIRE, new \DateTimeImmutable('-3 days', new \DateTimeZone('UTC')));
        $application->status = $status;
        if ($status === 'approved') {
            $application->interviewAt = new \DateTimeImmutable('-1 day', new \DateTimeZone('UTC'));
            $application->interviewNotes = 'Entrevista ficticia favorable.';
            $application->decisionAt = new \DateTimeImmutable('-1 day', new \DateTimeZone('UTC'));
        }
        $this->em->persist($application);
        $this->em->flush();
        return $application;
    }

    /** Animal reservado por una solicitud aprobada, como lo deja HU-04. */
    private function reservation(?User $applicant = null, bool $fostered = false): AdoptionApplication
    {
        $animal = $this->animal(20, 'reserved');
        $animal->fostered = $fostered;
        $this->em->flush();
        return $this->application($animal, 'approved', $applicant);
    }

    private function coordinator(): User
    {
        return $this->user('ROLE_COORDINATOR', 'coordinadora_'.bin2hex(random_bytes(4)).'@example.test');
    }

    private function serviceAt(string $now): AdoptionService
    {
        return new AdoptionService($this->em, new MockClock($now));
    }

    private function formalizeByHttp(AdoptionApplication $application, array $fields): void
    {
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('#form-formalize input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/formalizar', ['_token' => $token] + $fields);
    }

    private function adoptionCount(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM adoption');
    }

    private function states(AdoptionApplication $application): array
    {
        $connection = $this->em->getConnection();
        return [
            $connection->fetchOne('SELECT status FROM adoptionapplication WHERE id = ?', [$application->id]),
            $connection->fetchOne('SELECT status FROM animal WHERE id = ?', [$application->animal->id]),
        ];
    }

    // CA-05-01
    public function testFormalizationGeneratesContractLinkedToAnimalAndAdopter(): void
    {
        $adopter = $this->user('ROLE_USER', 'familia@example.test');
        $application = $this->reservation($adopter);
        $this->client->loginUser($this->coordinator());

        $this->formalizeByHttp($application, ['fee' => '120,00', 'feePaid' => '1']);

        $adoption = $this->refreshEm()->getRepository(Adoption::class)->findOneBy(['application' => $application->id]);
        self::assertNotNull($adoption);
        self::assertResponseRedirects('/admin/adopciones/'.$adoption->id, 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Adopción formalizada');
        self::assertSelectorTextContains('main', $adoption->contractNumber);

        self::assertSame('CTR-'.substr($application->animal->code, 4), $adoption->contractNumber);
        self::assertSame($application->animal->id, $adoption->animal->id);
        self::assertSame($adopter->id, $adoption->adopter->id);

        $this->client->request('GET', '/adopciones/'.$adoption->id.'/contrato');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Contrato de adopción '.$adoption->contractNumber);
        foreach ([$application->animal->code, 'Persona Ficticia', 'familia@example.test', '120,00 €'] as $text) {
            self::assertSelectorTextContains('main', $text);
        }
        self::assertCount(1, $this->client->getCrawler()->filter('[data-controller="print"]'));
    }

    // CA-05-02
    public function testFormalizationRecordsFeeAndMarksAnimalAdopted(): void
    {
        $application = $this->reservation();
        $adoption = $this->serviceAt('2027-03-10 12:15:00 Europe/Madrid')->formalize($application, $this->coordinator(), ['fee' => '120,00', 'feePaid' => '1']);

        $em = $this->refreshEm();
        $em->clear();
        $stored = $em->find(Adoption::class, $adoption->id);
        self::assertSame(12000, $stored->feeCents);
        self::assertSame('120,00 €', $stored->feeLabel());
        self::assertSame('2027-03-10 11:15:00', $stored->deliveredAt->format('Y-m-d H:i:s'), 'Entrega en UTC en el momento de formalizar.');
        self::assertSame('adopted', $em->find(Animal::class, $application->animal->id)->status);
        self::assertSame('formalized', $em->find(AdoptionApplication::class, $application->id)->status);
    }

    // CA-05-02 (D15)
    public function testFosteredAnimalLeavesFosterWhenAdopted(): void
    {
        $application = $this->reservation(fostered: true);
        $this->serviceAt('2027-03-10 12:00:00 Europe/Madrid')->formalize($application, $this->coordinator(), ['fee' => '80', 'feePaid' => '1']);

        $em = $this->refreshEm();
        $em->clear();
        $animal = $em->find(Animal::class, $application->animal->id);
        self::assertSame('adopted', $animal->status);
        self::assertFalse($animal->fostered);
    }

    // CA-05-03
    public function testTwoFollowUpsAreScheduledAtOneAndSixMonths(): void
    {
        $application = $this->reservation();
        $coordinator = $this->coordinator();
        $adoption = $this->serviceAt('2027-03-10 18:00:00 Europe/Madrid')->formalize($application, $coordinator, ['fee' => '120', 'feePaid' => '1']);

        $em = $this->refreshEm();
        $em->clear();
        $followUps = $em->getRepository(FollowUp::class)->findBy(['adoption' => $adoption->id], ['dueOn' => 'ASC']);
        self::assertCount(2, $followUps);
        self::assertSame(['one_month', '2027-04-10', 'scheduled'], [$followUps[0]->kind, $followUps[0]->dueOn->format('Y-m-d'), $followUps[0]->status]);
        self::assertSame(['six_months', '2027-09-10', 'scheduled'], [$followUps[1]->kind, $followUps[1]->dueOn->format('Y-m-d'), $followUps[1]->status]);

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/adopciones/'.$adoption->id);
        self::assertResponseIsSuccessful();
        $rows = $crawler->filter('#follow-ups tbody tr');
        self::assertCount(2, $rows);
        self::assertStringContainsString('10/04/2027', $rows->eq(0)->text());
        self::assertStringContainsString('10/09/2027', $rows->eq(1)->text());
    }

    // CA-05-03
    public function testFollowUpListIsOrderedByDate(): void
    {
        $coordinator = $this->coordinator();
        $this->client->loginUser($coordinator);
        $this->client->request('GET', '/admin/seguimientos');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'No hay seguimientos programados');

        $later = $this->reservation();
        $earlier = $this->reservation();
        $this->serviceAt('2027-05-20 10:00:00 Europe/Madrid')->formalize($later, $coordinator, ['fee' => '100', 'feePaid' => '1']);
        $this->serviceAt('2027-03-10 10:00:00 Europe/Madrid')->formalize($earlier, $coordinator, ['fee' => '100', 'feePaid' => '1']);

        $crawler = $this->client->request('GET', '/admin/seguimientos');
        $dates = $crawler->filter('#follow-ups tbody tr td:first-child')->each(static fn($cell) => trim($cell->text()));
        self::assertSame(['10/04/2027', '20/06/2027', '10/09/2027', '20/11/2027'], $dates);
    }

    // CA-05-04
    public function testAdoptedAnimalDisappearsFromPublicCatalogDetailAndPhotos(): void
    {
        $application = $this->reservation();
        $animal = $application->animal;
        $storage = self::getContainer()->get(PhotoStorage::class);
        $filename = 'spec005-'.bin2hex(random_bytes(6)).'.png';
        $image = imagecreatetruecolor(10, 10);
        if (!is_dir(dirname($storage->path($filename)))) { mkdir(dirname($storage->path($filename)), 0700, true); }
        imagepng($image, $storage->path($filename));
        $photo = new AnimalPhoto($animal, $filename, 'image/png');
        $this->em->persist($photo);
        $this->em->flush();

        try {
            $coordinator = $this->coordinator();
            $this->client->loginUser($coordinator);
            $this->formalizeByHttp($application, ['fee' => '95,50', 'feePaid' => '1']);
            self::assertResponseStatusCodeSame(303);

            $this->client->request('GET', '/fotos/'.$photo->id);
            self::assertResponseIsSuccessful('El fichero existe: el personal sigue viéndolo.');

            $this->client->getCookieJar()->clear(); // vuelve a ser una visita anónima
            $this->client->request('GET', '/animales');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextNotContains('main', $animal->code);
            $this->client->request('GET', '/animales/'.$animal->id);
            self::assertResponseStatusCodeSame(404);
            $this->client->request('GET', '/fotos/'.$photo->id);
            self::assertResponseStatusCodeSame(404);
        } finally {
            $storage->remove($filename);
        }
    }

    // CA-05-05
    public function testAdoptionIsIdentifiableFromTheAnimal(): void
    {
        $application = $this->reservation();
        $service = self::getContainer()->get(AdoptionService::class);
        self::assertNull($service->findByAnimal($application->animal));

        $adoption = $service->formalize($application, $this->coordinator(), ['fee' => '120', 'feePaid' => '1']);
        $found = $service->findByAnimal($application->animal);
        self::assertNotNull($found);
        self::assertSame($adoption->id, $found->id);
        self::assertSame($application->applicant->id, $found->adopter->id);
        self::assertSame(1, $this->adoptionCount());
    }

    // CA-05-06
    public function testFormalizationRequiresApprovedApplicationAndReservedAnimal(): void
    {
        $coordinator = $this->coordinator();
        // Estados de partida incompatibles; el último es incoherente: aprobada con el animal disponible.
        $cases = [];
        foreach ([['pending', 'available'], ['waiting', 'reserved'], ['rejected', 'available'], ['cancelled', 'available'], ['approved', 'available']] as [$status, $animalStatus]) {
            $cases[] = [$this->application($this->animal(20, $animalStatus), $status)->id, $status, $animalStatus];
        }
        $application = $this->reservation();
        $doctrine = self::getContainer()->get('doctrine');

        foreach ($cases as [$id, $status, $animalStatus]) {
            // Un rechazo dentro de la transacción cierra el EntityManager: cada intento usa uno nuevo.
            $doctrine->resetManager();
            $em = $this->refreshEm();
            $candidate = $em->find(AdoptionApplication::class, $id);
            try {
                self::getContainer()->get(AdoptionService::class)->formalize($candidate, $em->find(User::class, $coordinator->id), ['fee' => '120', 'feePaid' => '1']);
                self::fail('Se ha formalizado una solicitud en estado '.$status.' con el animal '.$animalStatus);
            } catch (ApplicationRefused) {
            }
            self::assertSame([$status, $animalStatus], $this->states($candidate));
        }
        self::assertSame(0, $this->adoptionCount());

        // Ya formalizada: la segunda formalización se rechaza con 409 y no duplica nada.
        $doctrine->resetManager();
        $this->refreshEm();
        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        $token = $crawler->filter('#form-formalize input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/formalizar', ['_token' => $token, 'fee' => '120', 'feePaid' => '1']);
        self::assertResponseStatusCodeSame(303);
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/formalizar', ['_token' => $token, 'fee' => '120', 'feePaid' => '1']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('main', 'Solo se puede formalizar una solicitud aprobada');
        self::assertSame(1, $this->adoptionCount());
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM followup'));
    }

    // CA-05-07
    public function testFormalizationRequiresValidFeeAndPaymentConfirmation(): void
    {
        $service = self::getContainer()->get(AdoptionService::class);
        foreach (['120,5' => 12050, '120.50' => 12050, ' 80 ' => 8000, '0,01' => 1, '9999,99' => 999999] as $input => $cents) {
            self::assertSame($cents, $service->validate(['fee' => $input, 'feePaid' => '1']), 'Importe '.$input);
        }
        foreach (['', '0', '0,00', '-5', 'abc', '12,345', '10000', '1.234,56'] as $input) {
            try {
                $service->validate(['fee' => $input, 'feePaid' => '1']);
                self::fail('Importe aceptado: '.$input);
            } catch (ValidationError $error) {
                self::assertArrayHasKey('fee', $error->errors);
            }
        }

        $application = $this->reservation();
        $this->client->loginUser($this->coordinator());
        $this->formalizeByHttp($application, ['fee' => '120,00']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-feePaid', 'Confirma que se ha abonado la cuota');
        self::assertSame('120,00', $this->client->getCrawler()->filter('#fee')->attr('value'), 'Se conserva el importe escrito.');

        $this->formalizeByHttp($application, ['fee' => '-5', 'feePaid' => '1']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('#fee[aria-invalid="true"]');

        self::assertSame(['approved', 'reserved'], $this->states($application));
        self::assertSame(0, $this->adoptionCount());
    }

    // CA-05-08
    public function testFormalizationClosesOtherApplicationsAndFreesOpenSlots(): void
    {
        $adopter = $this->user('ROLE_USER', 'familia@example.test');
        $application = $this->reservation($adopter);
        $otherAnimalApplication = $this->application($this->animal(20, 'available'), 'pending', $adopter);
        $waitingApplicant = $this->user('ROLE_USER', 'espera@example.test');
        $waiting = $this->application($application->animal, 'waiting', $waitingApplicant);
        $applications = self::getContainer()->get(ApplicationService::class);
        self::assertSame(2, $applications->openCount($adopter));

        self::getContainer()->get(AdoptionService::class)->formalize($application, $this->coordinator(), ['fee' => '120', 'feePaid' => '1']);

        $em = $this->refreshEm();
        $em->clear();
        $waitingStored = $em->find(AdoptionApplication::class, $waiting->id);
        self::assertSame('cancelled', $waitingStored->status);
        self::assertSame('El animal ha sido adoptado', $waitingStored->cancellationReason);
        self::assertSame('pending', $em->find(AdoptionApplication::class, $otherAnimalApplication->id)->status);
        $adopter = $em->find(User::class, $adopter->id);
        self::assertSame(1, $applications->openCount($adopter));
        self::assertSame(0, $applications->openCount($em->find(User::class, $waitingApplicant->id)));

        $third = $this->animal(20, 'available');
        $applications->submit($adopter, $third, self::QUESTIONNAIRE);
        self::assertSame(2, $applications->openCount($adopter), 'La formalizada deja libre un hueco del máximo de dos.');

        $this->client->loginUser($em->find(User::class, $waitingApplicant->id));
        $this->client->request('GET', '/mis-solicitudes');
        self::assertSelectorTextContains('main', 'Cancelada');
        self::assertSelectorTextContains('main', 'Motivo: El animal ha sido adoptado');

        $this->client->loginUser($adopter);
        $this->client->request('GET', '/mis-solicitudes');
        self::assertSelectorTextContains('main', 'Adopción formalizada');
        self::assertSelectorExists('a[href$="/contrato"]');
    }

    // CA-05-08 (D11 ya no aplica)
    public function testFormalizedAdoptionCannotBeReleased(): void
    {
        $application = $this->reservation();
        $coordinator = $this->coordinator();
        self::getContainer()->get(AdoptionService::class)->formalize($application, $coordinator, ['fee' => '120', 'feePaid' => '1']);

        $em = $this->refreshEm();
        $em->clear();
        $stored = $em->find(AdoptionApplication::class, $application->id);
        try {
            self::getContainer()->get(ApplicationService::class)->cancelReservation($stored, ['cancellationReason' => 'Intento posterior.']);
            self::fail('Se ha liberado la reserva de una adopción formalizada.');
        } catch (ValidationError) {
        }
        self::assertSame(['formalized', 'adopted'], $this->states($application));

        $this->client->loginUser($coordinator);
        $crawler = $this->client->request('GET', '/admin/solicitudes/'.$application->id);
        self::assertCount(0, $crawler->filter('form[action$="/cancelar-reserva"]'));
        self::assertCount(0, $crawler->filter('#form-formalize'));
        self::assertSelectorTextContains('main', 'Adopción formalizada');
    }

    // CA-05-09
    public function testFollowUpDatesUseLastDayOfShorterMonths(): void
    {
        $madrid = new \DateTimeZone('Europe/Madrid');
        $august = new \DateTimeImmutable('2027-08-31 10:00', $madrid);
        self::assertSame('2027-09-30', FollowUpCalendar::dueDate($august, 1)->format('Y-m-d'));
        self::assertSame('2028-02-29', FollowUpCalendar::dueDate($august, 6)->format('Y-m-d'));
        $january = new \DateTimeImmutable('2027-01-31 10:00', $madrid);
        self::assertSame('2027-02-28', FollowUpCalendar::dueDate($january, 1)->format('Y-m-d'));
        self::assertSame('2027-07-31', FollowUpCalendar::dueDate($january, 6)->format('Y-m-d'));
        self::assertSame('2028-01-15', FollowUpCalendar::dueDate(new \DateTimeImmutable('2027-07-15 10:00', $madrid), 6)->format('Y-m-d'), 'Cambio de año.');

        $application = $this->reservation();
        $adoption = $this->serviceAt('2027-08-31 12:00:00 Europe/Madrid')->formalize($application, $this->coordinator(), ['fee' => '120', 'feePaid' => '1']);
        self::assertSame(['2027-09-30', '2028-02-29'], array_map(static fn(FollowUp $f) => $f->dueOn->format('Y-m-d'), $adoption->followUps()));
    }

    // CA-05-09
    public function testFollowUpDatesUseMadridCalendarDay(): void
    {
        // 23:30 UTC del 31 de marzo es ya 1 de abril en Madrid (horario de verano, UTC+2).
        $application = $this->reservation();
        $adoption = $this->serviceAt('2027-03-31 23:30:00 UTC')->formalize($application, $this->coordinator(), ['fee' => '120', 'feePaid' => '1']);
        self::assertSame(['2027-05-01', '2027-10-01'], array_map(static fn(FollowUp $f) => $f->dueOn->format('Y-m-d'), $adoption->followUps()));
    }

    /** @return list<string> salida de cada proceso */
    private function race(array $jobs): array
    {
        $processes = [];
        foreach ($jobs as [$mode, $application, $coordinator, $startAt]) {
            $command = [PHP_BINARY, dirname(__DIR__).'/tests/Support/adoption-race.php', $mode, $application->id, $coordinator->id, $startAt];
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

    // CA-05-10
    public function testConcurrentFormalizationsCreateOneAdoption(): void
    {
        $application = $this->reservation();
        $coordinator = $this->coordinator();
        $startAt = sprintf('%.6F', microtime(true) + 3);

        $results = $this->race([['formalize', $application, $coordinator, $startAt], ['formalize', $application, $coordinator, $startAt]]);

        self::assertSame(['formalize:ok', 'formalize:rechazada'], $results);
        self::assertSame(1, $this->adoptionCount());
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM followup'));
        self::assertSame(['formalized', 'adopted'], $this->states($application));
    }

    // CA-05-10: la liberación parte de una página cargada antes de formalizar (estado obsoleto).
    public function testReleaseAfterConcurrentFormalizationDoesNotUndoAdoption(): void
    {
        $application = $this->reservation();
        $coordinator = $this->coordinator();
        $startAt = microtime(true) + 3;

        $results = $this->race([
            ['formalize', $application, $coordinator, sprintf('%.6F', $startAt)],
            ['release', $application, $coordinator, sprintf('%.6F', $startAt + 0.5)],
        ]);

        self::assertSame(['formalize:ok', 'release:rechazada'], $results);
        self::assertSame(1, $this->adoptionCount());
        self::assertSame(['formalized', 'adopted'], $this->states($application));
    }

    // CA-05-11
    public function testOnlyCoordinatorFormalizesAndListsFollowUps(): void
    {
        $application = $this->reservation();
        $coordinator = $this->coordinator();
        $adoption = self::getContainer()->get(AdoptionService::class)->formalize($this->reservation(), $coordinator, ['fee' => '120', 'feePaid' => '1']);

        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/formalizar', ['fee' => '120', 'feePaid' => '1']);
        self::assertResponseRedirects('/acceso');
        $this->client->request('GET', '/admin/seguimientos');
        self::assertResponseRedirects('/acceso');

        foreach (['ROLE_USER', 'ROLE_VOLUNTEER', 'ROLE_VET'] as $role) {
            $this->client->loginUser($this->user($role, strtolower($role).'@example.test'));
            $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/formalizar', ['fee' => '120', 'feePaid' => '1']);
            self::assertResponseStatusCodeSame(403, $role.' no formaliza');
            $this->client->request('GET', '/admin/seguimientos');
            self::assertResponseStatusCodeSame(403, $role.' no lista seguimientos');
            $this->client->request('GET', '/admin/adopciones/'.$adoption->id);
            self::assertResponseStatusCodeSame(403, $role.' no consulta la adopción');
        }
        self::assertSame(['approved', 'reserved'], $this->states($application));
        self::assertSame(1, $this->adoptionCount());
    }

    // CA-05-11
    public function testContractVisibleOnlyToCoordinatorAndAdopter(): void
    {
        $adopter = $this->user('ROLE_USER', 'familia@example.test');
        $coordinator = $this->coordinator();
        $adoption = self::getContainer()->get(AdoptionService::class)->formalize($this->reservation($adopter), $coordinator, ['fee' => '120', 'feePaid' => '1']);
        $url = '/adopciones/'.$adoption->id.'/contrato';

        $this->client->request('GET', $url);
        self::assertResponseRedirects('/acceso');

        $this->client->loginUser($adopter);
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();

        foreach ([$this->user('ROLE_USER', 'otra@example.test'), $this->user('ROLE_VOLUNTEER', 'voluntario@example.test')] as $other) {
            $this->client->loginUser($other);
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(404);
        }

        $this->client->loginUser($coordinator);
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
    }

    // CA-05-11
    public function testFormalizationRequiresCsrf(): void
    {
        $application = $this->reservation();
        $this->client->loginUser($this->coordinator());
        $this->client->request('POST', '/admin/solicitudes/'.$application->id.'/formalizar', ['_token' => 'falso', 'fee' => '120', 'feePaid' => '1']);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(['approved', 'reserved'], $this->states($application));
        self::assertSame(0, $this->adoptionCount());
    }
}
