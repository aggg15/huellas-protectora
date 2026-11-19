<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{AdoptionApplication, Animal, FosterHome, FosterPlacement, User};
use App\Service\{AdoptionService, ApplicationService, AvailabilityCalendar, FosterRefused, FosterService, ValidationError};
use Symfony\Component\Clock\{MockClock, NativeClock};

/** Criterios de aceptación de HU-06 (CA-06-01 a CA-06-10). */
class Spec006Test extends WebTestBase
{
    private const QUESTIONNAIRE = [
        'housingType' => 'house_outdoor',
        'housing' => 'Casa ficticia con patio vallado y dos personas adultas.',
        'experienceType' => 'dogs',
        'experience' => 'Experiencia ficticia con dos perros adoptados.',
        'dailyTime' => 'gt4',
        'availability' => 'Teletrabajo ficticio; un familiar lo cuida en las ausencias.',
    ];

    private function today(): \DateTimeImmutable
    {
        return AvailabilityCalendar::madridDay(new \DateTimeImmutable());
    }

    /** Día relativo a hoy (Europe/Madrid) en formato Y-m-d. */
    private function day(int $offset): string
    {
        return $this->today()->modify(($offset >= 0 ? '+' : '').$offset.' days')->format('Y-m-d');
    }

    private function family(string $name = 'Familia Ficticia'): User
    {
        $user = $this->user('ROLE_FOSTER', 'familia_'.bin2hex(random_bytes(4)).'@example.test');
        $user->name = $name;
        $this->em->flush();
        return $user;
    }

    private function coordinator(): User
    {
        return $this->user('ROLE_COORDINATOR', 'coordinadora_'.bin2hex(random_bytes(4)).'@example.test');
    }

    /** Casa con capacidad y periodos [inicio, fin] en días relativos a hoy, creada directamente. */
    private function home(int $capacity, array $periods, ?User $family = null): FosterHome
    {
        $home = new FosterHome($family ?? $this->family(), $capacity, new \DateTimeImmutable());
        foreach ($periods as [$from, $to]) {
            $home->addPeriod(new \DateTimeImmutable($this->day($from)), new \DateTimeImmutable($this->day($to)));
        }
        $this->em->persist($home);
        foreach ($home->periods() as $period) { $this->em->persist($period); }
        $this->em->flush();
        return $home;
    }

    /**
     * Servicio sobre el EntityManager de la prueba. Tras una petición HTTP el kernel se reinicia y el contenedor
     * cambia: mezclar entidades de un EntityManager con un servicio de otro dejaría cambios sin guardar.
     */
    private function fosters(): FosterService
    {
        return new FosterService($this->em, new NativeClock());
    }

    private function place(Animal $animal, FosterHome $home, int $plannedEnd = 10, ?User $coordinator = null): FosterPlacement
    {
        return $this->fosters()->place($animal, $coordinator ?? $this->coordinator(), ['home' => $home->id, 'plannedEndOn' => $this->day($plannedEnd)]);
    }

    private function placeByHttp(Animal $animal, array $fields): void
    {
        $crawler = $this->client->request('GET', '/admin/animales/'.$animal->id);
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('#form-foster-place input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/animales/'.$animal->id.'/acogida', ['_token' => $token] + $fields);
    }

    private function familyPost(string $formSelector, string $action, array $fields): void
    {
        $crawler = $this->client->request('GET', '/acogida');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter($formSelector.' input[name="_token"]')->attr('value');
        $this->client->request('POST', $action, ['_token' => $token] + $fields);
    }

    private function rows(string $table, string $where = 'TRUE', array $params = []): int
    {
        return (int) $this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    private function animalRow(Animal $animal): array
    {
        return $this->em->getConnection()->fetchAssociative('SELECT status, fostered FROM animal WHERE id = ?', [$animal->id]);
    }

    private function approvedApplication(Animal $animal): AdoptionApplication
    {
        $applicant = $this->user('ROLE_USER', 'adoptante_'.bin2hex(random_bytes(4)).'@example.test');
        $application = new AdoptionApplication($animal, $applicant, self::QUESTIONNAIRE, new \DateTimeImmutable('-3 days', new \DateTimeZone('UTC')));
        $application->status = 'approved';
        $application->interviewAt = new \DateTimeImmutable('-1 day', new \DateTimeZone('UTC'));
        $application->interviewNotes = 'Entrevista ficticia favorable.';
        $application->decisionAt = new \DateTimeImmutable('-1 day', new \DateTimeZone('UTC'));
        $this->em->persist($application);
        $this->em->flush();
        return $application;
    }

    // CA-06-01
    public function testFamilyRecordsCapacityAndAvailabilityPeriods(): void
    {
        $family = $this->family('Familia Olmo Ficticia');
        $this->client->loginUser($family);
        $this->client->request('GET', '/acogida');
        self::assertSelectorTextContains('main', 'Todavía no has indicado tus condiciones');

        $this->familyPost('#form-capacity', '/acogida/capacidad', ['capacity' => '2']);
        self::assertResponseRedirects('/acogida', 303);
        $this->familyPost('#form-period', '/acogida/periodos', ['startsOn' => $this->day(52), 'endsOn' => $this->day(73)]);
        self::assertResponseRedirects('/acogida', 303);
        $this->familyPost('#form-period', '/acogida/periodos', ['startsOn' => $this->day(12), 'endsOn' => $this->day(26)]);
        self::assertResponseRedirects('/acogida', 303);

        $connection = $this->em->getConnection();
        self::assertSame(2, (int) $connection->fetchOne('SELECT capacity FROM fosterhome WHERE family_id = ?', [$family->id]));
        self::assertSame(
            [[$this->day(12), $this->day(26)], [$this->day(52), $this->day(73)]],
            array_map(static fn(array $r) => [$r['startson'], $r['endson']], $connection->fetchAllAssociative('SELECT startsOn, endsOn FROM fosteravailability ORDER BY startsOn')),
        );

        $crawler = $this->client->request('GET', '/acogida');
        self::assertSelectorTextContains('#occupancy', '0 de 2 plazas ocupadas');
        $rows = $crawler->filter('#periods tbody tr');
        self::assertCount(2, $rows);
        self::assertStringContainsString((new \DateTimeImmutable($this->day(12)))->format('d/m/Y'), $rows->eq(0)->text());

        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/acogidas');
        self::assertResponseIsSuccessful();
        $home = $crawler->filter('#homes tbody tr')->eq(0)->text();
        self::assertStringContainsString('Familia Olmo Ficticia', $home);
        self::assertStringContainsString('0 de 2', $home);
        self::assertStringContainsString((new \DateTimeImmutable($this->day(52)))->format('d/m/Y'), $home);
    }

    // CA-06-02
    public function testCoordinatorPlacesAnimalInAvailableHomeWithCapacity(): void
    {
        $family = $this->family('Familia Pino Ficticia');
        $home = $this->home(2, [[0, 30]], $family);
        $animal = $this->animal(20, 'available');

        $this->client->loginUser($this->coordinator());
        $this->placeByHttp($animal, ['home' => $home->id, 'plannedEndOn' => $this->day(20)]);
        self::assertResponseRedirects('/admin/animales/'.$animal->id, 303);

        $row = $this->em->getConnection()->fetchAssociative('SELECT home_id, startedOn, plannedEndOn, endedOn FROM fosterplacement WHERE animal_id = ?', [$animal->id]);
        self::assertSame([$home->id, $this->day(0), $this->day(20), null], [$row['home_id'], $row['startedon'], $row['plannedendon'], $row['endedon']]);
        self::assertSame(['status' => 'available', 'fostered' => true], $this->animalRow($animal));

        $this->client->followRedirect();
        self::assertSelectorTextContains('.notification.is-success', 'Acogida registrada en la casa de Familia Pino Ficticia');
        self::assertSelectorTextContains('#foster-active', 'Familia Pino Ficticia');
        self::assertSelectorTextContains('#foster-panel', 'En casa de acogida');

        $this->client->loginUser($family);
        $this->client->request('GET', '/acogida');
        self::assertSelectorTextContains('#animals-title + article', $animal->code);
        self::assertSelectorTextContains('#occupancy', '1 de 2 plazas ocupadas');
    }

    // CA-06-02: la acogida empieza el día de Madrid, no el de UTC.
    public function testPlacementStartsOnMadridCalendarDay(): void
    {
        $home = new FosterHome($this->family(), 2, new \DateTimeImmutable());
        $this->em->persist($home);
        $this->em->persist($home->addPeriod(new \DateTimeImmutable('2027-03-25'), new \DateTimeImmutable('2027-04-30')));
        $this->em->flush();
        $service = new FosterService($this->em, new MockClock('2027-03-31 22:30:00 UTC')); // 00:30 del 1 de abril en Madrid

        try {
            $service->place($this->animal(20, 'available'), $this->coordinator(), ['home' => $home->id, 'plannedEndOn' => '2027-03-31']);
            self::fail('El 31 de marzo ya es pasado en Madrid.');
        } catch (ValidationError $error) {
            self::assertArrayHasKey('plannedEndOn', $error->errors);
        }
        $placement = $service->place($this->animal(20, 'available'), $this->coordinator(), ['home' => $home->id, 'plannedEndOn' => '2027-04-10']);
        self::assertSame('2027-04-01', $placement->startedOn->format('Y-m-d'));
    }

    // CA-06-03
    public function testPlacementRefusedWhenHomeIsNotAvailableForThePeriod(): void
    {
        $animal = $this->animal(20, 'available');
        $this->client->loginUser($this->coordinator());
        $cases = [
            'disponible solo diez días' => [$this->home(3, [[0, 10]]), 15],
            'disponible desde mañana' => [$this->home(3, [[1, 20]]), 5],
            'con un día de hueco' => [$this->home(3, [[0, 5], [7, 20]]), 10],
        ];
        foreach ($cases as $label => [$home, $plannedEnd]) {
            $this->placeByHttp($animal, ['home' => $home->id, 'plannedEndOn' => $this->day($plannedEnd)]);
            self::assertResponseStatusCodeSame(409, $label);
            self::assertSelectorTextContains('#foster-panel .notification', 'no está disponible todos los días', $label);
        }
        self::assertSame(0, $this->rows('fosterplacement'));
        self::assertSame(['status' => 'available', 'fostered' => false], $this->animalRow($animal));
    }

    // CA-06-03
    public function testPlacementRefusedWhenCapacityWouldBeExceeded(): void
    {
        $home = $this->home(1, [[0, 30]]);
        $this->place($this->animal(20, 'available'), $home);
        $second = $this->animal(20, 'available');

        $this->client->loginUser($this->coordinator());
        $this->placeByHttp($second, ['home' => $home->id, 'plannedEndOn' => $this->day(10)]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#foster-panel .notification', 'ya atiende su máximo de 1 animal');
        self::assertSame(1, $this->rows('fosterplacement'));
        self::assertSame(['status' => 'available', 'fostered' => false], $this->animalRow($second));
        // El formulario conserva lo escrito.
        self::assertSame($this->day(10), $this->client->getCrawler()->filter('#foster-planned-end')->attr('value'));
    }

    // CA-06-03: los periodos consecutivos o solapados se unen.
    public function testConsecutivePeriodsCoverThePlacement(): void
    {
        $placement = $this->place($this->animal(20, 'available'), $this->home(1, [[0, 5], [6, 20]]), 15);
        self::assertTrue($placement->isActive());

        $d = static fn(string $day) => new \DateTimeImmutable($day);
        $periods = [[$d('2027-01-10'), $d('2027-01-20')], [$d('2027-01-01'), $d('2027-01-12')], [$d('2027-01-21'), $d('2027-01-31')]];
        self::assertTrue(AvailabilityCalendar::covers($periods, $d('2027-01-02'), $d('2027-01-31')), 'desordenados, solapados y consecutivos');
        self::assertFalse(AvailabilityCalendar::covers($periods, $d('2026-12-31'), $d('2027-01-05')), 'empieza antes');
        self::assertFalse(AvailabilityCalendar::covers([[$d('2027-01-01'), $d('2027-01-05')], [$d('2027-01-07'), $d('2027-01-09')]], $d('2027-01-02'), $d('2027-01-08')), 'hueco');
        self::assertFalse(AvailabilityCalendar::covers([], $d('2027-01-01'), $d('2027-01-01')), 'sin periodos');
    }

    // CA-06-04
    public function testFosteredAvailableAnimalStaysPublicWithFosterBadge(): void
    {
        $animal = $this->animal(20, 'available');
        $this->place($animal, $this->home(1, [[0, 30]]));

        $crawler = $this->client->request('GET', '/animales');
        self::assertResponseIsSuccessful();
        $card = $crawler->filter('.animal-card')->reduce(static fn($node) => str_contains($node->text(), $animal->code));
        self::assertCount(1, $card);
        self::assertStringContainsString('En casa de acogida', $card->text());
        $this->client->request('GET', '/animales/'.$animal->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.foster-badge', 'En casa de acogida');
    }

    // CA-06-04: la acogida no elimina la cuarentena ni cambia la reserva.
    public function testPlacementDoesNotChangeQuarantineOrReservation(): void
    {
        $home = $this->home(3, [[0, 30]]);
        $quarantined = $this->animal(2, 'quarantine');
        $this->place($quarantined, $home);
        self::assertSame(['status' => 'quarantine', 'fostered' => true], $this->animalRow($quarantined));

        $reserved = $this->animal(20, 'reserved');
        $application = $this->approvedApplication($reserved);
        $placement = $this->place($reserved, $home);
        self::assertSame(['status' => 'reserved', 'fostered' => true], $this->animalRow($reserved));
        self::assertSame('approved', $this->em->getConnection()->fetchOne('SELECT status FROM adoptionapplication WHERE id = ?', [$application->id]));

        (new ApplicationService($this->em, new NativeClock()))->cancelReservation($application, ['cancellationReason' => 'Reserva ficticia que no sigue adelante.']);
        self::assertSame(['status' => 'available', 'fostered' => true], $this->animalRow($reserved), 'liberar la reserva no finaliza la acogida');
        self::assertNull($this->em->getConnection()->fetchOne('SELECT endedOn FROM fosterplacement WHERE id = ?', [$placement->id]));

        $this->fosters()->end($placement);
        self::assertSame(['status' => 'available', 'fostered' => false], $this->animalRow($reserved), 'finalizar la acogida no cambia el estado');

        // El animal en cuarentena sigue fuera de la web pública aunque esté en acogida.
        $crawler = $this->client->request('GET', '/animales');
        self::assertStringNotContainsString($quarantined->code, $crawler->filter('main')->text());
        $this->client->request('GET', '/animales/'.$quarantined->id);
        self::assertResponseStatusCodeSame(404);
    }

    // CA-06-04: la adopción finaliza la acogida; un animal adoptado no admite acogida.
    public function testAdoptionEndsActivePlacement(): void
    {
        $home = $this->home(1, [[0, 30]]);
        $animal = $this->animal(20, 'reserved');
        $application = $this->approvedApplication($animal);
        $placement = $this->place($animal, $home);

        (new AdoptionService($this->em, new NativeClock()))->formalize($application, $this->coordinator(), ['fee' => '120', 'feePaid' => '1']);

        $row = $this->em->getConnection()->fetchAssociative('SELECT endedOn, endReason FROM fosterplacement WHERE id = ?', [$placement->id]);
        self::assertSame(['endedon' => $this->day(0), 'endreason' => 'adopted'], $row);
        self::assertSame(['status' => 'adopted', 'fostered' => false], $this->animalRow($animal));
        self::assertSame(0, $this->fosters()->activeCount($home), 'la plaza queda libre');

        $this->expectException(FosterRefused::class);
        $this->fosters()->place($animal, $this->coordinator(), ['home' => $home->id, 'plannedEndOn' => $this->day(5)]);
    }

    // CA-06-05
    public function testFamilyNotesAreAssociatedWithTheAnimal(): void
    {
        $family = $this->family('Familia Roble Ficticia');
        $animal = $this->animal(20, 'available');
        $placement = $this->place($animal, $this->home(1, [[0, 30]], $family));

        $this->client->loginUser($family);
        $this->familyPost('#form-note-'.$placement->id, '/acogida/acogidas/'.$placement->id.'/novedades', ['body' => "Duerme toda la noche y ya pasea sin miedo.\nCome bien."]);
        self::assertResponseRedirects('/acogida', 303);

        $note = $this->em->getConnection()->fetchAssociative('SELECT placement_id, author_id, body FROM fosternote');
        self::assertSame([$placement->id, $family->id, "Duerme toda la noche y ya pasea sin miedo.\nCome bien."], [$note['placement_id'], $note['author_id'], $note['body']]);
        $this->client->followRedirect();
        self::assertSelectorTextContains('#placement-'.$placement->id, 'Duerme toda la noche y ya pasea sin miedo.');

        $this->client->loginUser($this->user('ROLE_VOLUNTEER', 'voluntario@example.test'));
        $this->client->request('GET', '/admin/animales/'.$animal->id);
        self::assertSelectorTextContains('#foster-notes', 'Duerme toda la noche y ya pasea sin miedo.');
        self::assertSelectorTextContains('#foster-notes', 'Familia Roble Ficticia');
        self::assertSelectorNotExists('#form-foster-end', 'el voluntario no finaliza acogidas');
    }

    // CA-06-06
    public function testInvalidConditionsAreRejected(): void
    {
        $family = $this->family();
        $this->client->loginUser($family);
        foreach (['', 'abc', '0', '11', '2.5', '-1'] as $capacity) {
            $this->familyPost('#form-capacity', '/acogida/capacidad', ['capacity' => $capacity]);
            self::assertResponseStatusCodeSame(422, 'capacidad «'.$capacity.'»');
            self::assertSelectorExists('#capacity[aria-invalid="true"]');
        }
        self::assertSame(0, $this->rows('fosterhome'));

        $this->familyPost('#form-capacity', '/acogida/capacidad', ['capacity' => '2']);
        $this->familyPost('#form-period', '/acogida/periodos', ['startsOn' => $this->day(10), 'endsOn' => $this->day(20)]);
        $invalid = [
            'fecha inexistente' => ['2027-02-30', '2027-03-10'],
            'fin anterior al inicio' => [$this->day(5), $this->day(4)],
            'inicio pasado' => [$this->day(-1), $this->day(4)],
            'más de dos años' => [$this->day(1), $this->today()->modify('+2 years +1 day')->format('Y-m-d')],
            'solapado' => [$this->day(20), $this->day(25)],
            'vacío' => ['', ''],
        ];
        foreach ($invalid as $label => [$startsOn, $endsOn]) {
            $this->familyPost('#form-period', '/acogida/periodos', ['startsOn' => $startsOn, 'endsOn' => $endsOn]);
            self::assertResponseStatusCodeSame(422, $label);
            self::assertSame($startsOn, $this->client->getCrawler()->filter('#startsOn')->attr('value'), $label.': conserva lo escrito');
        }
        self::assertSelectorTextContains('#form-period .notification', 'Revisa los datos');
        self::assertSame(1, $this->rows('fosteravailability'));
    }

    // CA-06-06
    public function testInvalidPlacementAndNoteAreRejected(): void
    {
        $family = $this->family();
        $home = $this->home(2, [[0, 30]], $family);
        $animal = $this->animal(20, 'available');
        $placement = $this->place($this->animal(20, 'available'), $home);
        $this->client->loginUser($this->coordinator());
        foreach ([['', $this->day(5)], [$home->id, ''], [$home->id, 'mañana'], [$home->id, $this->day(-1)], ['no-existe', $this->day(5)]] as [$homeId, $plannedEnd]) {
            $this->placeByHttp($animal, ['home' => $homeId, 'plannedEndOn' => $plannedEnd]);
            self::assertResponseStatusCodeSame(422, $homeId.' / '.$plannedEnd);
        }
        self::assertSame(0, $this->rows('fosterplacement', 'animal_id = ?', [$animal->id]));

        $this->client->loginUser($family);
        foreach (['', "   \n ", str_repeat('a', 2001)] as $body) {
            $this->familyPost('#form-note-'.$placement->id, '/acogida/acogidas/'.$placement->id.'/novedades', ['body' => $body]);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorExists('#body-'.$placement->id.'[aria-invalid="true"]');
        }
        self::assertSame(0, $this->rows('fosternote'));
    }

    // CA-06-07
    public function testConditionChangesRespectActivePlacements(): void
    {
        $family = $this->family();
        $home = $this->home(2, [[0, 10], [11, 30]], $family);
        $this->place($this->animal(20, 'available'), $home, 20);
        $this->place($this->animal(20, 'available'), $home, 5);
        $later = $home->periods()[1];

        $this->client->loginUser($family);
        $this->familyPost('#form-capacity', '/acogida/capacidad', ['capacity' => '1']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#form-capacity .notification', 'Ahora atiendes 2 animales');
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT capacity FROM fosterhome WHERE id = ?', [$home->id]));

        // El token del formulario de retirada es propio de cada periodo.
        $token = $this->client->request('GET', '/acogida')->filter('form[action="/acogida/periodos/'.$later->id.'/eliminar"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/acogida/periodos/'.$later->id.'/eliminar', ['_token' => $token]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('main', 'lo necesita una acogida en curso hasta el '.(new \DateTimeImmutable($this->day(20)))->format('d/m/Y'));
        self::assertSame(2, $this->rows('fosteravailability'));

        $this->familyPost('#form-capacity', '/acogida/capacidad', ['capacity' => '3']);
        self::assertResponseRedirects('/acogida', 303);
        $this->familyPost('#form-period', '/acogida/periodos', ['startsOn' => $this->day(40), 'endsOn' => $this->day(50)]);
        $extra = $this->em->getConnection()->fetchOne('SELECT id FROM fosteravailability WHERE startsOn = ?', [$this->day(40)]);
        $token = $this->client->request('GET', '/acogida')->filter('form[action="/acogida/periodos/'.$extra.'/eliminar"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/acogida/periodos/'.$extra.'/eliminar', ['_token' => $token]);
        self::assertResponseRedirects('/acogida', 303);
        self::assertSame(2, $this->rows('fosterplacement', 'endedOn IS NULL'), 'los cambios no finalizan acogidas');
    }

    // CA-06-08
    public function testCoordinatorEndsPlacement(): void
    {
        $family = $this->family('Familia Haya Ficticia');
        $home = $this->home(1, [[0, 30]], $family);
        $animal = $this->animal(20, 'available');
        $placement = $this->place($animal, $home);
        $this->fosters()->addNote($placement, $family, ['body' => 'Novedad ficticia anterior al fin.']);

        $this->client->loginUser($this->coordinator());
        $crawler = $this->client->request('GET', '/admin/animales/'.$animal->id);
        $token = $crawler->filter('#form-foster-end input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/acogidas/'.$placement->id.'/finalizar', ['_token' => $token]);
        self::assertResponseRedirects('/admin/animales/'.$animal->id, 303);

        $row = $this->em->getConnection()->fetchAssociative('SELECT endedOn, endReason FROM fosterplacement WHERE id = ?', [$placement->id]);
        self::assertSame(['endedon' => $this->day(0), 'endreason' => 'returned'], $row);
        self::assertSame(['status' => 'available', 'fostered' => false], $this->animalRow($animal));
        self::assertSame(0, $this->fosters()->activeCount($home));

        $this->client->followRedirect();
        self::assertSelectorTextContains('#foster-notes', 'Novedad ficticia anterior al fin.');
        self::assertSelectorTextContains('#foster-past', 'Regreso al refugio');
        self::assertSelectorExists('#form-foster-place', 'se puede registrar otra acogida');

        // Repetir el envío del formulario ya obsoleto: la acogida estaba finalizada.
        $this->client->request('POST', '/admin/acogidas/'.$placement->id.'/finalizar', ['_token' => $token]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#foster-panel .notification', 'ya estaba finalizada');

        $this->client->loginUser($family);
        $this->client->request('GET', '/acogida');
        self::assertSelectorTextContains('main', 'Ahora mismo no acoges ningún animal');
        $this->refreshEm();
        $this->expectException(FosterRefused::class);
        $this->fosters()->addNote($this->em->find(FosterPlacement::class, $placement->id), $family, ['body' => 'Después del fin.']);
    }

    // CA-06-08: el fin previsto superado no cierra la acogida.
    public function testOverduePlacementStaysActiveAndIsFlagged(): void
    {
        $family = $this->family('Familia Sauce Ficticia');
        $home = $this->home(1, [[0, 30]], $family);
        $animal = $this->animal(20, 'available');
        $animal->fostered = true;
        $this->em->persist(new FosterPlacement($animal, $home, $this->coordinator(), new \DateTimeImmutable($this->day(-10)), new \DateTimeImmutable($this->day(-1))));
        $this->em->flush();

        $this->client->loginUser($this->coordinator());
        $this->client->request('GET', '/admin/acogidas');
        self::assertSelectorTextContains('#placements', 'Fin previsto superado');
        $this->client->request('GET', '/admin/animales/'.$animal->id);
        self::assertSelectorTextContains('#foster-panel', 'Fin previsto superado');
        $this->placeByHttp($this->animal(20, 'available'), ['home' => $home->id, 'plannedEndOn' => $this->day(5)]);
        self::assertResponseStatusCodeSame(409, 'sigue ocupando la plaza');

        $this->client->loginUser($family);
        $this->client->request('GET', '/acogida');
        self::assertSelectorTextContains('main', 'Fin previsto superado');
        self::assertSelectorTextContains('#occupancy', '1 de 1 plaza ocupada');
    }

    /** @return list<string> resultados ordenados de los procesos */
    private function race(array $jobs): array
    {
        $processes = [];
        foreach ($jobs as $arguments) {
            $command = [PHP_BINARY, dirname(__DIR__).'/tests/Support/foster-race.php', ...$arguments];
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

    // CA-06-09: dos animales distintos compiten por la última plaza.
    public function testConcurrentPlacementsDoNotExceedCapacity(): void
    {
        $home = $this->home(1, [[0, 30]]);
        $coordinator = $this->coordinator();
        [$first, $second] = [$this->animal(20, 'available'), $this->animal(20, 'available')];
        $startAt = sprintf('%.6F', microtime(true) + 3);

        $results = $this->race([
            ['place', $first->id, $home->id, $coordinator->id, $this->day(10), $startAt],
            ['place', $second->id, $home->id, $coordinator->id, $this->day(10), $startAt],
        ]);

        self::assertSame(['place:ok', 'place:rechazada'], $results);
        self::assertSame(1, $this->rows('fosterplacement', 'home_id = ? AND endedOn IS NULL', [$home->id]));
        self::assertSame(1, $this->rows('animal', 'fostered'));
    }

    // CA-06-09: el mismo animal en dos casas a la vez.
    public function testConcurrentPlacementsOfOneAnimalCreateOne(): void
    {
        [$homeA, $homeB] = [$this->home(2, [[0, 30]]), $this->home(2, [[0, 30]])];
        $coordinator = $this->coordinator();
        $animal = $this->animal(20, 'available');
        $startAt = sprintf('%.6F', microtime(true) + 3);

        $results = $this->race([
            ['place', $animal->id, $homeA->id, $coordinator->id, $this->day(10), $startAt],
            ['place', $animal->id, $homeB->id, $coordinator->id, $this->day(10), $startAt],
        ]);

        self::assertSame(['place:ok', 'place:rechazada'], $results);
        self::assertSame(1, $this->rows('fosterplacement', 'animal_id = ?', [$animal->id]));
    }

    // CA-06-09: la formalización parte de una página cargada antes de registrar la acogida (estado obsoleto:
    // en memoria el animal no está en acogida). Debe cerrar la acogida y el indicador igualmente.
    public function testConcurrentAdoptionAndPlacementLeaveNoActivePlacement(): void
    {
        $home = $this->home(1, [[0, 30]]);
        $coordinator = $this->coordinator();
        $animal = $this->animal(20, 'reserved');
        $application = $this->approvedApplication($animal);
        // Márgenes amplios: el arranque del kernel puede recompilar el contenedor en cada proceso.
        $startAt = microtime(true) + 5;

        $results = $this->race([
            ['place', $animal->id, $home->id, $coordinator->id, $this->day(10), sprintf('%.6F', $startAt)],
            ['formalize', $application->id, $coordinator->id, sprintf('%.6F', $startAt + 1.5)],
        ]);

        self::assertSame(['formalize:ok', 'place:ok'], $results);
        self::assertSame(['status' => 'adopted', 'fostered' => false], $this->animalRow($animal));
        self::assertSame('adopted', $this->em->getConnection()->fetchOne('SELECT endReason FROM fosterplacement WHERE animal_id = ?', [$animal->id]));
        self::assertSame(0, $this->rows('fosterplacement', 'endedOn IS NULL'));
        self::assertSame(0, $this->fosters()->activeCount($home));
    }

    // CA-06-10
    public function testOnlyFosterFamiliesManageConditionsAndNotes(): void
    {
        $this->client->request('GET', '/acogida');
        self::assertResponseRedirects('/acceso');
        $this->client->request('POST', '/acogida/capacidad', ['capacity' => '2']);
        self::assertResponseRedirects('/acceso');

        foreach (['ROLE_USER', 'ROLE_VOLUNTEER', 'ROLE_VET', 'ROLE_COORDINATOR'] as $role) {
            $this->client->loginUser($this->user($role, strtolower($role).'@example.test'));
            $this->client->request('GET', '/acogida');
            self::assertResponseStatusCodeSame(403, $role.' no ve la página de la familia');
            $this->client->request('POST', '/acogida/capacidad', ['capacity' => '2']);
            self::assertResponseStatusCodeSame(403, $role.' no registra condiciones');
        }
        self::assertSame(0, $this->rows('fosterhome'));

        $this->client->loginUser($this->family());
        $this->client->request('GET', '/panel');
        self::assertResponseRedirects('/acogida');
        $this->client->request('GET', '/admin/acogidas');
        self::assertResponseStatusCodeSame(403, 'la familia no es personal');
    }

    // CA-06-10
    public function testOnlyCoordinatorPlacesEndsAndListsPlacements(): void
    {
        $home = $this->home(2, [[0, 30]]);
        $animal = $this->animal(20, 'available');
        $placement = $this->place($this->animal(20, 'available'), $home);
        $fields = ['home' => $home->id, 'plannedEndOn' => $this->day(5)];

        $this->client->request('GET', '/admin/acogidas');
        self::assertResponseRedirects('/acceso');
        $this->client->request('POST', '/admin/animales/'.$animal->id.'/acogida', $fields);
        self::assertResponseRedirects('/acceso');

        foreach (['ROLE_USER', 'ROLE_FOSTER', 'ROLE_VOLUNTEER', 'ROLE_VET'] as $role) {
            $this->client->loginUser($this->user($role, strtolower($role).'@example.test'));
            $this->client->request('GET', '/admin/acogidas');
            self::assertResponseStatusCodeSame(403, $role.' no lista acogidas');
            $this->client->request('POST', '/admin/animales/'.$animal->id.'/acogida', $fields);
            self::assertResponseStatusCodeSame(403, $role.' no registra acogidas');
            $this->client->request('POST', '/admin/acogidas/'.$placement->id.'/finalizar');
            self::assertResponseStatusCodeSame(403, $role.' no finaliza acogidas');
        }
        $this->client->request('GET', '/admin/animales/'.$animal->id); // veterinario: ve la ficha sin formularios de acogida
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('#form-foster-place');
        self::assertSame(1, $this->rows('fosterplacement'));
        self::assertSame(1, $this->rows('fosterplacement', 'endedOn IS NULL'));
    }

    // CA-06-10: otra familia recibe «no encontrado» y no cambia nada.
    public function testFamilyCannotTouchAnotherHome(): void
    {
        $owner = $this->family();
        $home = $this->home(1, [[0, 30]], $owner);
        $placement = $this->place($this->animal(20, 'available'), $home);
        $period = $home->periods()[0];
        $other = $this->family();
        $this->home(1, [[0, 30]], $other);

        $this->client->loginUser($other);
        $this->client->request('POST', '/acogida/periodos/'.$period->id.'/eliminar', ['_token' => 'x']);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('POST', '/acogida/acogidas/'.$placement->id.'/novedades', ['_token' => 'x', 'body' => 'Intento ajeno.']);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/acogida');
        self::assertSelectorTextNotContains('main', $placement->animal->code);
        self::assertSame(2, $this->rows('fosteravailability'));
        self::assertSame(0, $this->rows('fosternote'));
    }

    // CA-06-10 y caso límite: las novedades son internas y se muestran escapadas.
    public function testNotesAreNotPublic(): void
    {
        $family = $this->family();
        $animal = $this->animal(20, 'available');
        $placement = $this->place($animal, $this->home(1, [[0, 30]], $family));
        $this->fosters()->addNote($placement, $family, ['body' => '<script>alert(1)</script> Le gusta tomar el sol.']);

        $this->client->request('GET', '/animales');
        self::assertStringNotContainsString('Le gusta tomar el sol', (string) $this->client->getResponse()->getContent());
        $this->client->request('GET', '/animales/'.$animal->id);
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Le gusta tomar el sol', (string) $this->client->getResponse()->getContent());

        $this->client->loginUser($this->user('ROLE_VET', 'veterinaria@example.test'));
        $this->client->request('GET', '/admin/animales/'.$animal->id);
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; Le gusta tomar el sol.', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    // CA-06-10
    public function testFosterFormsRequireCsrf(): void
    {
        $newFamily = $this->family();
        $family = $this->family();
        $home = $this->home(1, [[0, 30]], $family);
        $placement = $this->place($this->animal(20, 'available'), $home);
        $animal = $this->animal(20, 'available');

        $this->client->loginUser($newFamily);
        $this->client->request('POST', '/acogida/capacidad', ['_token' => 'caducado', 'capacity' => '2']);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(1, $this->rows('fosterhome'));

        $this->client->loginUser($family);
        $this->client->request('POST', '/acogida/acogidas/'.$placement->id.'/novedades', ['_token' => 'caducado', 'body' => 'Sin token.']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/acogida/periodos/'.$home->periods()[0]->id.'/eliminar', ['_token' => 'caducado']);
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->coordinator());
        $this->client->request('POST', '/admin/animales/'.$animal->id.'/acogida', ['_token' => 'caducado', 'home' => $home->id, 'plannedEndOn' => $this->day(5)]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/admin/acogidas/'.$placement->id.'/finalizar', ['_token' => 'caducado']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(0, $this->rows('fosternote'));
        self::assertSame(1, $this->rows('fosteravailability'));
        self::assertSame(1, $this->rows('fosterplacement', 'endedOn IS NULL'));
        self::assertSame(['status' => 'available', 'fostered' => false], $this->animalRow($animal));
    }
}
