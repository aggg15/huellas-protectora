<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{Shift, ShiftSignup, User};
use App\Service\{ShiftCoverage, ShiftRefused, ShiftService, ValidationError};
use Symfony\Component\Clock\{MockClock, NativeClock};

/** Criterios de aceptación de HU-07 (CA-07-01 a CA-07-11). */
class Spec007Test extends WebTestBase
{
    private function volunteer(string $name = 'Voluntaria Ficticia', string $role = 'ROLE_VOLUNTEER'): User
    {
        $user = $this->user($role, 'voluntario_'.bin2hex(random_bytes(4)).'@example.test');
        $user->name = $name;
        $this->em->flush();
        return $user;
    }

    private function coordinator(): User
    {
        return $this->volunteer('Coordinadora Ficticia', 'ROLE_COORDINATOR');
    }

    /** Instante UTC relativo a ahora, p. ej. '+30 hours'. */
    private function at(string $modifier): \DateTimeImmutable
    {
        return new \DateTimeImmutable($modifier, new \DateTimeZone('UTC'));
    }

    /** Instante de una hora de Madrid ('Y-m-d H:i'). */
    private function madrid(string $value): \DateTimeImmutable
    {
        return new \DateTimeImmutable($value, new \DateTimeZone(ShiftService::ZONE));
    }

    /** Día de Madrid dentro de `$days` días, en formato Y-m-d. */
    private function madridDay(int $days): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone(ShiftService::ZONE)))->modify('+'.$days.' days')->format('Y-m-d');
    }

    /** Turno creado directamente (sin la validación del servicio), con las personas inscritas indicadas. */
    private function shift(\DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt, int $min = 2, int $max = 4, array $volunteers = [], string $kind = 'walk'): Shift
    {
        $shift = new Shift($kind, $startsAt, $endsAt, $min, $max, $this->coordinator(), $this->at('-1 day'));
        $this->em->persist($shift);
        foreach ($volunteers as $volunteer) {
            $this->em->persist(new ShiftSignup($shift, $volunteer, $this->at('-1 hour')));
        }
        $this->em->flush();
        return $shift;
    }

    /** Servicio sobre el EntityManager de la prueba, con el reloj indicado. */
    private function service(?\DateTimeImmutable $now = null): ShiftService
    {
        return new ShiftService($this->em, $now === null ? new NativeClock() : new MockClock($now));
    }

    private function signups(Shift $shift, ?User $volunteer = null): int
    {
        $connection = $this->em->getConnection();
        return $volunteer === null
            ? (int) $connection->fetchOne('SELECT COUNT(*) FROM shiftsignup WHERE shift_id = ?', [$shift->id])
            : (int) $connection->fetchOne('SELECT COUNT(*) FROM shiftsignup WHERE shift_id = ? AND volunteer_id = ?', [$shift->id, $volunteer->id]);
    }

    private function shiftRow(Shift $shift): array
    {
        return $this->em->getConnection()->fetchAssociative('SELECT kind, startsAt, endsAt, minVolunteers, maxVolunteers FROM shift WHERE id = ?', [$shift->id]);
    }

    /** Token del formulario de inscripción o baja de un turno en el listado, cargado ahora. */
    private function listToken(Shift $shift): string
    {
        $crawler = $this->client->request('GET', '/admin/turnos');
        self::assertResponseIsSuccessful();
        return $crawler->filter('#shift-'.$shift->id.' input[name="_token"]')->attr('value');
    }

    private function editShift(Shift $shift, array $changes): void
    {
        $crawler = $this->client->request('GET', '/admin/turnos/'.$shift->id);
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('#form-shift-edit');
        $fields = ['_token' => $form->filter('input[name="_token"]')->attr('value')];
        foreach (['date', 'startTime', 'endTime', 'minVolunteers', 'maxVolunteers'] as $name) {
            $fields[$name] = $form->filter('input[name="'.$name.'"]')->attr('value');
        }
        $fields['kind'] = $form->filter('select[name="kind"] option[selected]')->attr('value');
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/editar', $changes + $fields);
    }

    private function newShiftForm(): string
    {
        $crawler = $this->client->request('GET', '/admin/turnos/nuevo');
        self::assertResponseIsSuccessful();
        return $crawler->filter('#form-shift input[name="_token"]')->attr('value');
    }

    // CA-07-01
    public function testShiftShowsScheduleLimitsAndSignups(): void
    {
        $day = $this->madridDay(7);
        $inscribed = $this->volunteer('Lucía Ficticia');
        $shift = $this->shift($this->madrid($day.' 10:00'), $this->madrid($day.' 12:00'), 2, 4, [$inscribed]);
        $this->client->loginUser($this->volunteer('Mario Ficticio'));

        $crawler = $this->client->request('GET', '/admin/turnos');
        self::assertResponseIsSuccessful();
        $row = $crawler->filter('#shift-'.$shift->id);
        self::assertSame('Paseo', $row->filter('td[data-label="Turno"]')->text());
        self::assertSame((new \DateTimeImmutable($day))->format('d/m/Y').', 10:00–12:00', $row->filter('td[data-label="Fecha y hora"]')->text());
        self::assertSame('2', $row->filter('td[data-label="Mínimo"]')->text());
        self::assertSame('4', $row->filter('td[data-label="Máximo"]')->text());
        self::assertSame('1 (3 plazas libres)', $row->filter('td[data-label="Inscritas"]')->text());
        self::assertSame(1, $row->filter('button:contains("Apuntarme")')->count());

        $this->client->request('GET', '/admin/turnos/'.$shift->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Paseo del '.(new \DateTimeImmutable($day))->format('d/m/Y'));
        self::assertSelectorTextContains('.subtitle', 'De 10:00 a 12:00');
        $facts = $this->client->getCrawler()->filter('#shift-facts dd')->each(static fn($dd) => $dd->text());
        self::assertSame(['2 personas', '4 personas', '1', '3'], $facts);
        self::assertSelectorTextContains('#signups', 'Lucía Ficticia');
    }

    // CA-07-02
    public function testVolunteerSignsUpForShiftWithPlaces(): void
    {
        $shift = $this->shift($this->at('+5 days'), $this->at('+5 days 2 hours'), 1, 3, [$this->volunteer()]);
        $volunteer = $this->volunteer('Nuevo Voluntario Ficticio');
        $this->client->loginUser($volunteer);

        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/inscripcion', ['_token' => $this->listToken($shift)]);
        self::assertResponseRedirects('/admin/turnos', 303);
        self::assertSame(1, $this->signups($shift, $volunteer));
        $signedUpAt = $this->em->getConnection()->fetchOne('SELECT signedUpAt FROM shiftsignup WHERE volunteer_id = ?', [$volunteer->id]);
        self::assertEqualsWithDelta(time(), (new \DateTimeImmutable($signedUpAt, new \DateTimeZone('UTC')))->getTimestamp(), 60);

        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.notification.is-success', 'Te has apuntado al turno de paseo');
        $row = $crawler->filter('#shift-'.$shift->id);
        self::assertSame('2 (1 plaza libre)', $row->filter('td[data-label="Inscritas"]')->text());
        self::assertStringContainsString('Te has apuntado', $row->text());
        self::assertSame(1, $row->filter('button:contains("Darme de baja")')->count());
    }

    // CA-07-03
    public function testFullShiftRejectsAnotherSignup(): void
    {
        $shift = $this->shift($this->at('+5 days'), $this->at('+5 days 2 hours'), 1, 2, [$this->volunteer()]);
        $late = $this->volunteer('Voluntario Tardío Ficticio');
        $this->client->loginUser($late);
        $token = $this->listToken($shift);
        // Mientras tenía la página abierta, otra persona ocupa la última plaza.
        $this->em->persist(new ShiftSignup($shift, $this->volunteer(), $this->at('now')));
        $this->em->flush();

        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/inscripcion', ['_token' => $token]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#shift-refusal', 'ya tiene cubierto su máximo de 2 personas');
        self::assertSame(2, $this->signups($shift));
        self::assertSame(0, $this->signups($shift, $late));

        $crawler = $this->client->request('GET', '/admin/turnos');
        $row = $crawler->filter('#shift-'.$shift->id);
        self::assertStringContainsString('Completo', $row->text());
        self::assertSame(0, $row->filter('button')->count());
    }

    // CA-07-04
    public function testUndercoveredShiftIsHighlightedWithin48Hours(): void
    {
        $soon = $this->shift($this->at('+30 hours'), $this->at('+32 hours'), 3, 5, [$this->volunteer()]);
        $later = $this->shift($this->at('+72 hours'), $this->at('+74 hours'), 3, 5, [], 'cleaning');
        $started = $this->shift($this->at('-10 minutes'), $this->at('+2 hours'), 3, 5);

        foreach ([$this->volunteer(), $this->coordinator()] as $viewer) {
            $this->client->loginUser($viewer);
            $crawler = $this->client->request('GET', '/admin/turnos');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('#undercovered-notice', '1 turno de las próximas 48 horas no cubre su mínimo');
            $row = $crawler->filter('#shift-'.$soon->id);
            self::assertSame('shift-undercovered', $row->attr('class'));
            self::assertStringContainsString('Faltan voluntarios: 2 para el mínimo', $row->text());

            $row = $crawler->filter('#shift-'.$later->id);
            self::assertNull($row->attr('class'));
            self::assertStringContainsString('3 para el mínimo', $row->text());
            self::assertStringNotContainsString('Faltan voluntarios', $row->text());

            $row = $crawler->filter('#shift-'.$started->id);
            self::assertNull($row->attr('class'));
            self::assertStringContainsString('En curso', $row->text());
            self::assertSame(0, $row->filter('button')->count());
        }
    }

    // CA-07-04: ventana exacta de 48 horas reales, también con el cambio de hora.
    public function testHighlightWindowIsExactAndUsesRealHours(): void
    {
        $shift = $this->shift($this->at('+10 days'), $this->at('+10 days 2 hours'), 2, 3);
        $windowStart = $shift->startsAt->modify('-48 hours');
        self::assertFalse($this->service($windowStart->modify('-1 minute'))->coverage($shift, 1)['undercovered']);
        self::assertTrue($this->service($windowStart)->coverage($shift, 1)['undercovered']);
        self::assertTrue($this->service($shift->startsAt->modify('-1 second'))->coverage($shift, 1)['undercovered']);
        self::assertFalse($this->service($shift->startsAt)->coverage($shift, 1)['undercovered']);
        self::assertTrue($this->service($shift->startsAt)->coverage($shift, 1)['started']);

        // Domingo 28-mar-2027, 10:00 en Madrid (horario de verano, 08:00 UTC). 48 horas reales antes son las
        // 08:00 UTC del viernes, 09:00 en el reloj de Madrid (horario de invierno): 49 horas aparentes.
        $sunday = $this->madrid('2027-03-28 10:00');
        self::assertSame('2027-03-28 08:00', $sunday->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i'));
        self::assertTrue(ShiftCoverage::isUndercovered($sunday, 0, 1, $this->madrid('2027-03-26 09:00')));
        self::assertFalse(ShiftCoverage::isUndercovered($sunday, 0, 1, $this->madrid('2027-03-26 08:59')));

        // Coincidencias: los contiguos no coinciden.
        [$ten, $eleven, $twelve, $one] = [$this->madrid('2027-01-10 10:00'), $this->madrid('2027-01-10 11:00'), $this->madrid('2027-01-10 12:00'), $this->madrid('2027-01-10 13:00')];
        self::assertTrue(ShiftCoverage::overlaps($ten, $twelve, $eleven, $one));
        self::assertFalse(ShiftCoverage::overlaps($ten, $twelve, $twelve, $one));
    }

    // CA-07-05
    public function testShiftReachingMinimumIsNoLongerHighlighted(): void
    {
        $shift = $this->shift($this->at('+20 hours'), $this->at('+22 hours'), 2, 3, [$this->volunteer()]);
        $this->client->loginUser($this->volunteer('Segunda Persona Ficticia'));
        $crawler = $this->client->request('GET', '/admin/turnos');
        self::assertSame('shift-undercovered', $crawler->filter('#shift-'.$shift->id)->attr('class'));
        $token = $crawler->filter('#shift-'.$shift->id.' input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/inscripcion', ['_token' => $token]);
        self::assertResponseRedirects('/admin/turnos', 303);
        $crawler = $this->client->followRedirect();
        $row = $crawler->filter('#shift-'.$shift->id);
        self::assertNull($row->attr('class'));
        self::assertStringContainsString('Mínimo cubierto', $row->text());
        self::assertStringNotContainsString('Faltan voluntarios', $row->text());
        self::assertSelectorNotExists('#undercovered-notice');
    }

    // CA-07-06: alta por la coordinadora.
    public function testCoordinatorCreatesShift(): void
    {
        $this->client->loginUser($this->coordinator());
        $day = $this->madridDay(5);
        $this->client->request('POST', '/admin/turnos/nuevo', [
            '_token' => $this->newShiftForm(), 'kind' => 'cleaning', 'date' => $day,
            'startTime' => '09:30', 'endTime' => '11:00', 'minVolunteers' => '2', 'maxVolunteers' => '5',
        ]);
        self::assertResponseRedirects('/admin/turnos', 303);

        $row = $this->em->getConnection()->fetchAssociative('SELECT kind, startsAt, endsAt, minVolunteers, maxVolunteers FROM shift');
        $utc = new \DateTimeZone('UTC');
        self::assertSame([
            'kind' => 'cleaning',
            'startsat' => $this->madrid($day.' 09:30')->setTimezone($utc)->format('Y-m-d H:i:s'),
            'endsat' => $this->madrid($day.' 11:00')->setTimezone($utc)->format('Y-m-d H:i:s'),
            'minvolunteers' => 2, 'maxvolunteers' => 5,
        ], $row);
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.notification.is-success', 'Turno creado: Limpieza del '.(new \DateTimeImmutable($day))->format('d/m/Y').' de 09:30 a 11:00');
        self::assertCount(1, $crawler->filter('#shifts tbody tr'));
        self::assertStringContainsString('09:30–11:00', $crawler->filter('#shifts tbody tr')->text());
    }

    // CA-07-06: cambios permitidos y rechazados.
    public function testCoordinatorChangesLimitsWithinRules(): void
    {
        $shift = $this->shift($this->at('+4 days'), $this->at('+4 days 2 hours'), 1, 4, [$this->volunteer(), $this->volunteer()]);
        $this->client->loginUser($this->coordinator());

        $this->editShift($shift, ['minVolunteers' => '2', 'maxVolunteers' => '2']);
        self::assertResponseRedirects('/admin/turnos/'.$shift->id, 303);
        self::assertSame([2, 2], [$this->shiftRow($shift)['minvolunteers'], $this->shiftRow($shift)['maxvolunteers']]);
        $crawler = $this->client->request('GET', '/admin/turnos');
        self::assertStringContainsString('Completo', $crawler->filter('#shift-'.$shift->id)->text());

        $before = $this->shiftRow($shift);
        $this->editShift($shift, ['minVolunteers' => '1', 'maxVolunteers' => '1']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('.notification.is-danger', 'Hay 2 personas inscritas en el turno: el máximo no puede ser menor');
        self::assertSame($before, $this->shiftRow($shift));

        $this->editShift($shift, ['startTime' => '06:00']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('.notification.is-danger', 'no se pueden cambiar su tipo, su fecha ni sus horas');
        self::assertSame($before, $this->shiftRow($shift));
        $this->editShift($shift, ['kind' => 'cleaning']);
        self::assertResponseStatusCodeSame(409);
        self::assertSame($before, $this->shiftRow($shift));

        $empty = $this->shift($this->at('+6 days'), $this->at('+6 days 1 hour'), 1, 2);
        $day = $this->madridDay(8);
        $this->editShift($empty, ['kind' => 'cleaning', 'date' => $day, 'startTime' => '17:00', 'endTime' => '18:30']);
        self::assertResponseRedirects('/admin/turnos/'.$empty->id, 303);
        $row = $this->shiftRow($empty);
        self::assertSame('cleaning', $row['kind']);
        self::assertSame($this->madrid($day.' 17:00')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'), $row['startsat']);
    }

    // CA-07-07
    public function testInvalidShiftDataIsRejected(): void
    {
        $this->client->loginUser($this->coordinator());
        $day = $this->madridDay(5);
        $valid = ['kind' => 'walk', 'date' => $day, 'startTime' => '10:00', 'endTime' => '12:00', 'minVolunteers' => '2', 'maxVolunteers' => '4'];
        $springForward = $this->nextLastSunday(3);
        $cases = [
            'kind' => [['kind' => 'paseo'], 'Elige el tipo de turno'],
            'date' => [['date' => '2027-02-30'], 'Indica una fecha real'],
            'startTime' => [['startTime' => '25:00'], 'hora de inicio real'],
            'endTime' => [['endTime' => '09:00'], 'posterior a la de inicio'],
            'past' => [['date' => $this->madridDay(-1)], 'después de este momento'],
            'far' => [['date' => $this->madridDay(400)], 'como máximo dentro de un año'],
            'dst' => [['date' => $springForward, 'startTime' => '02:30', 'endTime' => '04:00'], 'no existe ese día por el cambio de hora'],
            'min' => [['minVolunteers' => '0'], 'El mínimo es un número entero'],
            'minText' => [['minVolunteers' => 'dos'], 'El mínimo es un número entero'],
            'maxBelowMin' => [['maxVolunteers' => '1'], 'no menor que el mínimo'],
            'maxHigh' => [['maxVolunteers' => '21'], 'de 1 a 20'],
        ];
        foreach ($cases as $case => [$changes, $message]) {
            $this->client->request('POST', '/admin/turnos/nuevo', ['_token' => $this->newShiftForm()] + $changes + $valid);
            self::assertResponseStatusCodeSame(422, $case);
            self::assertSelectorTextContains('.notification.is-danger', $message, $case);
            $field = array_key_first($changes);
            if ($field !== 'kind') {
                self::assertSelectorExists('#'.$field.'[value="'.$changes[$field].'"]', $case);
            }
        }
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM shift'));

        // Hora repetida al volver al horario de invierno: se toma la primera (horario de verano, UTC+2).
        $fallBack = $this->nextLastSunday(10);
        $this->client->request('POST', '/admin/turnos/nuevo', ['_token' => $this->newShiftForm(), 'date' => $fallBack, 'startTime' => '02:30', 'endTime' => '04:00'] + $valid);
        self::assertResponseRedirects('/admin/turnos', 303);
        self::assertSame($fallBack.' 00:30:00', (string) $this->em->getConnection()->fetchOne('SELECT startsAt FROM shift'));
    }

    /** Último domingo del mes indicado que todavía no ha pasado (cambios de hora en Madrid). */
    private function nextLastSunday(int $month): string
    {
        $year = (int) date('Y');
        do {
            $sunday = new \DateTimeImmutable(sprintf('last sunday of %d-%02d', $year, $month), new \DateTimeZone(ShiftService::ZONE));
            $year++;
        } while ($sunday <= new \DateTimeImmutable('+1 day'));
        return $sunday->format('Y-m-d');
    }

    // CA-07-08: baja y vuelta del destacado.
    public function testVolunteerLeavesShiftAndHighlightReturns(): void
    {
        $volunteer = $this->volunteer('Persona que se da de baja');
        $shift = $this->shift($this->at('+20 hours'), $this->at('+22 hours'), 2, 3, [$volunteer, $this->volunteer()]);
        $this->client->loginUser($volunteer);
        $crawler = $this->client->request('GET', '/admin/turnos');
        self::assertNull($crawler->filter('#shift-'.$shift->id)->attr('class'));
        $token = $crawler->filter('#shift-'.$shift->id.' input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/baja', ['_token' => $token]);
        self::assertResponseRedirects('/admin/turnos', 303);
        self::assertSame(0, $this->signups($shift, $volunteer));
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.notification.is-success', 'Te has dado de baja');
        $row = $crawler->filter('#shift-'.$shift->id);
        self::assertSame('shift-undercovered', $row->attr('class'));
        self::assertStringContainsString('Faltan voluntarios: 1 para el mínimo', $row->text());
        self::assertSame('1 (2 plazas libres)', $row->filter('td[data-label="Inscritas"]')->text());

        // Segundo envío del mismo formulario: ya no figura.
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/baja', ['_token' => $token]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#shift-refusal', 'No figuras en este turno');
        self::assertSame(1, $this->signups($shift));
    }

    // CA-07-08: un turno empezado no admite inscripciones, bajas ni cambios.
    public function testStartedShiftRejectsSignupLeaveAndChanges(): void
    {
        $inscribed = $this->volunteer('Persona inscrita');
        $shift = $this->shift($this->at('+1 hour'), $this->at('+3 hours'), 1, 3, [$inscribed]);
        $other = $this->volunteer('Persona que llega tarde');
        $this->client->loginUser($other);
        $signupToken = $this->listToken($shift);
        $this->client->loginUser($inscribed);
        $leaveToken = $this->listToken($shift);
        $this->client->loginUser($coordinator = $this->coordinator());
        $crawler = $this->client->request('GET', '/admin/turnos/'.$shift->id);
        $editToken = $crawler->filter('#form-shift-edit input[name="_token"]')->attr('value');

        // El turno empieza mientras las páginas siguen abiertas.
        $this->em->getConnection()->executeStatement('UPDATE shift SET startsAt = ? WHERE id = ?', [$this->at('-5 minutes')->format('Y-m-d H:i:s'), $shift->id]);
        $before = $this->shiftRow($shift);

        $this->client->loginUser($other);
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/inscripcion', ['_token' => $signupToken]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#shift-refusal', 'ya ha empezado');
        $this->client->loginUser($inscribed);
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/baja', ['_token' => $leaveToken]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#shift-refusal', 'ya no puedes darte de baja');
        // Fecha y horas sin cambios: el turno empezado es un rechazo de estado (409), no un dato no válido (analyze A4).
        $zone = new \DateTimeZone(ShiftService::ZONE);
        $startsAt = (new \DateTimeImmutable($before['startsat'], new \DateTimeZone('UTC')))->setTimezone($zone);
        $endsAt = (new \DateTimeImmutable($before['endsat'], new \DateTimeZone('UTC')))->setTimezone($zone);
        $this->client->loginUser($coordinator);
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/editar', ['_token' => $editToken, 'kind' => 'walk', 'date' => $startsAt->format('Y-m-d'),
            'startTime' => $startsAt->format('H:i'), 'endTime' => $endsAt->format('H:i'), 'minVolunteers' => '1', 'maxVolunteers' => '5']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('.notification.is-danger', 'ya ha empezado; no se puede modificar');
        self::assertSame($before, $this->shiftRow($shift));
        self::assertSame([1, 0], [$this->signups($shift, $inscribed), $this->signups($shift, $other)]);

        $crawler = $this->client->request('GET', '/admin/turnos');
        $row = $crawler->filter('#shift-'.$shift->id);
        self::assertStringContainsString('En curso', $row->text());
        self::assertSame(0, $row->filter('button')->count());
    }

    // CA-07-09
    public function testDuplicateAndOverlappingSignupsAreRejected(): void
    {
        $day = $this->madridDay(6);
        $volunteer = $this->volunteer('Persona con varios turnos');
        $morning = $this->shift($this->madrid($day.' 10:00'), $this->madrid($day.' 12:00'), 1, 3, [$volunteer]);
        $overlapping = $this->shift($this->madrid($day.' 11:00'), $this->madrid($day.' 13:00'), 1, 3, [], 'cleaning');
        $contiguous = $this->shift($this->madrid($day.' 12:00'), $this->madrid($day.' 13:00'), 1, 3);
        $this->client->loginUser($volunteer);

        $crawler = $this->client->request('GET', '/admin/turnos');
        $row = $crawler->filter('#shift-'.$overlapping->id);
        self::assertStringContainsString('Coincide con otro de tus turnos', $row->text());
        self::assertSame(0, $row->filter('button')->count());
        self::assertSame(1, $crawler->filter('#shift-'.$contiguous->id.' button:contains("Apuntarme")')->count());

        // Mismo turno otra vez (p. ej., un segundo envío del formulario): se rechaza.
        try {
            $this->service()->signUp($morning, $volunteer);
            self::fail('Se ha admitido una segunda inscripción en el mismo turno.');
        } catch (ShiftRefused $refused) {
            self::assertSame('Ya figuras en este turno.', $refused->getMessage());
        }
        $this->em->clear();
        try {
            $this->service()->signUp($this->em->find(Shift::class, $overlapping->id), $this->em->find(User::class, $volunteer->id));
            self::fail('Se ha admitido un turno coincidente.');
        } catch (ShiftRefused $refused) {
            self::assertSame('Coincide con otro turno en el que figuras: Paseo del '.(new \DateTimeImmutable($day))->format('d/m/Y').' de 10:00 a 12:00.', $refused->getMessage());
        }
        self::assertSame([1, 0], [$this->signups($morning), $this->signups($overlapping)]);

        $this->client->request('POST', '/admin/turnos/'.$contiguous->id.'/inscripcion', ['_token' => $crawler->filter('#shift-'.$contiguous->id.' input[name="_token"]')->attr('value')]);
        self::assertResponseRedirects('/admin/turnos', 303);
        self::assertSame(1, $this->signups($contiguous, $volunteer));
    }

    /** @return list<string> resultados ordenados de los procesos */
    private function race(array $jobs): array
    {
        $processes = [];
        foreach ($jobs as $arguments) {
            $command = [PHP_BINARY, dirname(__DIR__).'/tests/Support/shift-race.php', ...$arguments];
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

    /** Inicio sincronizado con margen para que cada proceso arranque su kernel. */
    private function startAt(): string
    {
        return sprintf('%.6F', microtime(true) + 4);
    }

    // CA-07-10: dos personas compiten por la última plaza.
    public function testConcurrentSignupsDoNotExceedMaximum(): void
    {
        $shift = $this->shift($this->at('+3 days'), $this->at('+3 days 2 hours'), 1, 2, [$this->volunteer()]);
        [$first, $second] = [$this->volunteer(), $this->volunteer()];
        $startAt = $this->startAt();

        $results = $this->race([
            ['signup', $shift->id, $first->id, $startAt],
            ['signup', $shift->id, $second->id, $startAt],
        ]);

        self::assertSame(['signup:ok', 'signup:rechazada'], $results);
        self::assertSame(2, $this->signups($shift));
    }

    // CA-07-10: la misma persona se apunta a la vez a dos turnos coincidentes.
    public function testConcurrentOverlappingSignupsCreateOne(): void
    {
        $day = $this->madridDay(4);
        $a = $this->shift($this->madrid($day.' 10:00'), $this->madrid($day.' 12:00'), 1, 3);
        $b = $this->shift($this->madrid($day.' 11:00'), $this->madrid($day.' 13:00'), 1, 3);
        $volunteer = $this->volunteer();
        $startAt = $this->startAt();

        $results = $this->race([
            ['signup', $a->id, $volunteer->id, $startAt],
            ['signup', $b->id, $volunteer->id, $startAt],
        ]);

        self::assertSame(['signup:ok', 'signup:rechazada'], $results);
        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM shiftsignup WHERE volunteer_id = ?', [$volunteer->id]));
    }

    // CA-07-10: la coordinadora baja el máximo mientras alguien se apunta.
    public function testConcurrentMaximumChangeAndSignupStayWithinLimit(): void
    {
        $shift = $this->shift($this->at('+3 days'), $this->at('+3 days 2 hours'), 1, 3, [$this->volunteer(), $this->volunteer()]);
        $volunteer = $this->volunteer();
        $startAt = $this->startAt();

        $results = $this->race([
            ['max', $shift->id, '2', $startAt],
            ['signup', $shift->id, $volunteer->id, $startAt],
        ]);

        self::assertContains($results, [['max:ok', 'signup:rechazada'], ['max:rechazada', 'signup:ok']]);
        $row = $this->shiftRow($shift);
        self::assertLessThanOrEqual($row['maxvolunteers'], $this->signups($shift));
    }

    // CA-07-11
    public function testOnlyVolunteersSeeAndJoinShifts(): void
    {
        $shift = $this->shift($this->at('+3 days'), $this->at('+3 days 2 hours'));
        $this->client->request('GET', '/admin/turnos');
        self::assertResponseRedirects('/acceso');

        foreach (['ROLE_USER', 'ROLE_FOSTER', 'ROLE_VET'] as $role) {
            $this->client->loginUser($this->volunteer('Cuenta '.$role, $role));
            foreach (['/admin/turnos', '/admin/turnos/'.$shift->id] as $page) {
                $this->client->request('GET', $page);
                self::assertResponseStatusCodeSame(403, $role.' '.$page);
            }
            foreach (['inscripcion', 'baja'] as $action) {
                $this->client->request('POST', '/admin/turnos/'.$shift->id.'/'.$action, ['_token' => 'x']);
                self::assertResponseStatusCodeSame(403, $role.' '.$action);
            }
            $crawler = $this->client->request('GET', '/');
            self::assertSame(0, $crawler->filter('nav a:contains("Turnos")')->count(), $role);
        }
        self::assertSame(0, $this->signups($shift));

        $this->client->loginUser($this->volunteer());
        $crawler = $this->client->request('GET', '/');
        self::assertSame(1, $crawler->filter('nav a:contains("Turnos")')->count());
    }

    // CA-07-11
    public function testOnlyCoordinatorCreatesAndEditsShifts(): void
    {
        $shift = $this->shift($this->at('+3 days'), $this->at('+3 days 2 hours'), 1, 4);
        $before = $this->shiftRow($shift);
        $this->client->loginUser($this->volunteer());

        $crawler = $this->client->request('GET', '/admin/turnos');
        self::assertSame(0, $crawler->filter('a:contains("Nuevo turno")')->count());
        $this->client->request('GET', '/admin/turnos/'.$shift->id);
        self::assertSelectorNotExists('#form-shift-edit');
        $this->client->request('GET', '/admin/turnos/nuevo');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/admin/turnos/nuevo', ['kind' => 'walk', 'date' => $this->madridDay(3), 'startTime' => '10:00', 'endTime' => '11:00', 'minVolunteers' => '1', 'maxVolunteers' => '2']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/editar', ['maxVolunteers' => '1']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM shift'));
        self::assertSame($before, $this->shiftRow($shift));
    }

    // CA-07-11 y caso límite: CSRF y nombres escapados.
    public function testShiftFormsRequireCsrf(): void
    {
        $inscribed = $this->volunteer('<script>alert(1)</script>');
        $shift = $this->shift($this->at('+3 days'), $this->at('+3 days 2 hours'), 1, 4, [$inscribed]);
        $before = $this->shiftRow($shift);

        $this->client->loginUser($this->coordinator());
        $this->client->request('POST', '/admin/turnos/nuevo', ['_token' => 'caducado', 'kind' => 'walk', 'date' => $this->madridDay(3), 'startTime' => '10:00', 'endTime' => '11:00', 'minVolunteers' => '1', 'maxVolunteers' => '2']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/editar', ['_token' => 'caducado', 'maxVolunteers' => '1']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/inscripcion', ['_token' => 'caducado']);
        self::assertResponseStatusCodeSame(403);
        $this->client->loginUser($inscribed);
        $this->client->request('POST', '/admin/turnos/'.$shift->id.'/baja', ['_token' => 'caducado']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame(1, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM shift'));
        self::assertSame($before, $this->shiftRow($shift));
        self::assertSame(1, $this->signups($shift));

        $this->client->request('GET', '/admin/turnos/'.$shift->id);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', (string) $this->client->getResponse()->getContent());
        self::assertSelectorNotExists('#signups script');
    }
}
