<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{AdoptionApplication, Animal, FosterHome, FosterNote, FosterPlacement, Sponsorship, SponsorUpdate, User};
use App\Service\{AdoptionService, SponsorshipRefused, SponsorshipService};
use Symfony\Component\Clock\NativeClock;

/** Criterios de aceptación de HU-08 (CA-08-01 a CA-08-10). */
class Spec008Test extends WebTestBase
{
    private function sponsorAccount(string $name = 'Madrina Ficticia', string $role = 'ROLE_USER'): User
    {
        $user = $this->user($role, 'cuenta_'.bin2hex(random_bytes(4)).'@example.test');
        $user->name = $name;
        $this->em->flush();
        return $user;
    }

    private function available(bool $fostered = false): Animal
    {
        $animal = $this->animal(20, 'available');
        $animal->fostered = $fostered;
        $this->em->flush();
        return $animal;
    }

    private function utc(string $modifier): \DateTimeImmutable
    {
        return new \DateTimeImmutable($modifier, new \DateTimeZone('UTC'));
    }

    /** Apadrinamiento creado directamente, sin pasar por el servicio. */
    private function sponsorship(Animal $animal, User $sponsor, int $cents = 1500, string $since = '-10 days'): Sponsorship
    {
        $sponsorship = new Sponsorship($animal, $sponsor, $cents, $this->utc($since));
        $this->em->persist($sponsorship);
        $this->em->flush();
        return $sponsorship;
    }

    private function update(Animal $animal, string $body, string $when): SponsorUpdate
    {
        $update = new SponsorUpdate($animal, $this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR'), $this->utc($when), $body);
        $this->em->persist($update);
        $this->em->flush();
        return $update;
    }

    private function service(): SponsorshipService
    {
        return new SponsorshipService($this->em, new NativeClock(), new AdoptionService($this->em, new NativeClock()));
    }

    /** Adopción formalizada con HU-05 a partir de una solicitud aprobada, como la deja HU-04. */
    private function adopt(Animal $animal): void
    {
        $animal->status = 'reserved';
        $application = new AdoptionApplication($animal, $this->sponsorAccount('Adoptante Ficticia'), [
            'housingType' => 'house_outdoor', 'housing' => 'Casa ficticia.', 'experienceType' => 'dogs',
            'experience' => 'Experiencia ficticia.', 'dailyTime' => 'gt4', 'availability' => 'Disponibilidad ficticia.',
        ], $this->utc('-3 days'));
        $application->status = 'approved';
        $application->interviewAt = $application->decisionAt = $this->utc('-1 day');
        $application->interviewNotes = 'Entrevista ficticia favorable.';
        $this->em->persist($application);
        $this->em->flush();
        (new AdoptionService($this->em, new NativeClock()))->formalize($application, $this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR'), ['fee' => '120', 'feePaid' => '1']);
    }

    private function rows(?User $sponsor = null): array
    {
        $connection = $this->em->getConnection();
        return $sponsor === null
            ? $connection->fetchAllAssociative('SELECT * FROM sponsorship ORDER BY startedAt, id')
            : $connection->fetchAllAssociative('SELECT * FROM sponsorship WHERE sponsor_id = ? ORDER BY startedAt, id', [$sponsor->id]);
    }

    private function newFormToken(Animal $animal): string
    {
        $crawler = $this->client->request('GET', '/animales/'.$animal->id.'/apadrinar');
        self::assertResponseIsSuccessful();
        return $crawler->filter('#form-sponsorship input[name="_token"]')->attr('value');
    }

    private function sponsorByHttp(Animal $animal, string $amount): void
    {
        $this->client->request('POST', '/animales/'.$animal->id.'/apadrinar', ['_token' => $this->newFormToken($animal), 'amount' => $amount]);
    }

    /** Token de un formulario de «Mis apadrinamientos» cargado ahora. */
    private function mineToken(string $selector): string
    {
        $crawler = $this->client->request('GET', '/mis-apadrinamientos');
        self::assertResponseIsSuccessful();
        return $crawler->filter($selector.' input[name="_token"]')->attr('value');
    }

    private function updateToken(Animal $animal): string
    {
        $crawler = $this->client->request('GET', '/admin/apadrinamientos');
        self::assertResponseIsSuccessful();
        return $crawler->filter('#form-update-'.$animal->id.' input[name="_token"]')->attr('value');
    }

    // Constitution V: estados de ausencia de resultados (converge C2, T823)
    public function testEmptyStatesAreExplained(): void
    {
        $this->client->loginUser($this->sponsorAccount());
        $this->client->request('GET', '/mis-apadrinamientos');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.empty-state', 'Todavía no apadrinas a ningún animal');
        self::assertSelectorExists('main a[href="/animales"]');

        // Un apadrinamiento terminado deja de figurar en la página de la coordinadora.
        $ended = $this->sponsorship($this->available(), $this->sponsorAccount('Padrino Ficticio'));
        $this->em->getConnection()->executeStatement("UPDATE sponsorship SET endedAt = startedAt, endReason = 'finished' WHERE id = ?", [$ended->id]);
        $this->client->loginUser($this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR'));
        $this->client->request('GET', '/admin/apadrinamientos');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.empty-state', 'No hay apadrinamientos activos');
        self::assertSelectorNotExists('#sponsored-'.$ended->animal->id);
    }

    // CA-08-01
    public function testSponsorRegistersMonthlyContributionForAnimal(): void
    {
        $animal = $this->available(fostered: true);
        $sponsor = $this->sponsorAccount('Marta Ficticia');
        $this->client->loginUser($sponsor);

        $crawler = $this->client->request('GET', '/animales/'.$animal->id);
        self::assertResponseIsSuccessful();
        self::assertSame('/animales/'.$animal->id.'/apadrinar', $crawler->filter('#sponsor-box a')->attr('href'));

        $this->sponsorByHttp($animal, '15,50');
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        $rows = $this->rows($sponsor);
        self::assertCount(1, $rows);
        self::assertSame($animal->id, $rows[0]['animal_id']);
        self::assertSame(1550, (int) $rows[0]['monthlycents']);
        self::assertNull($rows[0]['endedat']);
        self::assertEqualsWithDelta(time(), (new \DateTimeImmutable($rows[0]['startedat'], new \DateTimeZone('UTC')))->getTimestamp(), 60);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.notification.is-success', 'Ya apadrinas a '.$animal->code.' con 15,50 € al mes.');
        $card = $this->client->getCrawler()->filter('#sponsorship-'.$rows[0]['id']);
        self::assertStringContainsString($animal->code, $card->filter('h2')->text());
        self::assertSame('Activo', $card->filter('.tag')->text());
        self::assertSame(['15,50 €', (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid')))->format('d/m/Y')], $card->filter('dd')->each(static fn($dd) => $dd->text()));

        // El mínimo exacto se admite.
        $other = $this->available();
        $this->sponsorByHttp($other, '10');
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        self::assertSame([1550, 1000], array_map(static fn(array $r) => (int) $r['monthlycents'], $this->rows($sponsor)));
    }

    // CA-08-02
    public function testContributionBelowTenEurosIsRejected(): void
    {
        $animal = $this->available();
        $this->client->loginUser($this->sponsorAccount());

        foreach (['9,99', '0', '', 'diez', '10,555', '-10', '10000', '1e3', '12,5 €'] as $amount) {
            $this->sponsorByHttp($animal, $amount);
            self::assertResponseStatusCodeSame(422, 'Importe: '.$amount);
            self::assertSelectorTextContains('#error-amount', 'al menos 10,00 €');
            self::assertSame($amount, $this->client->getCrawler()->filter('#amount')->attr('value'));
            self::assertSame('true', $this->client->getCrawler()->filter('#amount')->attr('aria-invalid'));
        }
        self::assertSame([], $this->rows());
    }

    // CA-08-03
    public function testSponsorReceivesUpdatesPublishedByCoordinator(): void
    {
        $animal = $this->available(fostered: true);
        // Nota interna de la familia de acogida (HU-06, D24): no llega a los padrinos (analyze A1).
        $family = $this->sponsorAccount('Familia Ficticia', 'ROLE_FOSTER');
        $home = new FosterHome($family, 2, $this->utc('-30 days'));
        $placement = new FosterPlacement($animal, $home, $this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR'), $this->utc('-8 days'), $this->utc('+20 days'));
        $this->em->persist($home);
        $this->em->persist($placement);
        $this->em->persist(new FosterNote($placement, $family, $this->utc('-1 day'), 'Nota interna de acogida.'));
        $this->em->flush();
        $sponsor = $this->sponsorAccount();
        $sponsorship = $this->sponsorship($animal, $sponsor, 2000, '-5 days');
        $this->update($animal, 'Novedad anterior al apadrinamiento.', '-6 days');
        $outsider = $this->sponsorship($this->available(), $this->sponsorAccount('Otro Padrino Ficticio'));

        $this->client->loginUser($this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR'));
        $this->client->request('POST', '/admin/apadrinamientos/'.$animal->id.'/novedades', ['_token' => $this->updateToken($animal), 'body' => '  Ha aprendido a pasear con correa. <b>¡Muy bien!</b>  ']);
        self::assertResponseRedirects('/admin/apadrinamientos', 303);
        $stored = $this->em->getConnection()->fetchAssociative('SELECT body, author_id FROM sponsorupdate WHERE body LIKE ?', ['Ha aprendido%']);
        self::assertSame('Ha aprendido a pasear con correa. <b>¡Muy bien!</b>', $stored['body']);

        $this->client->loginUser($sponsor);
        $crawler = $this->client->request('GET', '/mis-apadrinamientos');
        $updates = $crawler->filter('#sponsorship-'.$sponsorship->id.' .sponsor-updates li .answer')->each(static fn($p) => $p->text());
        self::assertSame(['Ha aprendido a pasear con correa. <b>¡Muy bien!</b>'], $updates, 'Solo la novedad escrita durante el apadrinamiento, escapada.');
        self::assertSame(0, $crawler->filter('.sponsor-updates b')->count());
        self::assertStringNotContainsString('Nota interna de acogida', $crawler->filter('main')->text());

        // Otra cuenta, con otro animal, no la recibe.
        $this->client->loginUser($outsider->sponsor);
        $this->client->request('GET', '/mis-apadrinamientos');
        self::assertSelectorTextNotContains('main', 'Ha aprendido a pasear');
    }

    // CA-08-03 (validación, estado y periodo)
    public function testUpdatesAreValidatedAndLimitedToTheSponsorshipPeriod(): void
    {
        $animal = $this->available();
        $alone = $this->available();
        $sponsor = $this->sponsorAccount();
        $this->sponsorship($animal, $sponsor);
        $coordinator = $this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR');
        $this->client->loginUser($coordinator);

        foreach (['', '   ', str_repeat('a', 2001)] as $body) {
            $this->client->request('POST', '/admin/apadrinamientos/'.$animal->id.'/novedades', ['_token' => $this->updateToken($animal), 'body' => $body]);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#error-body', 'de 1 a 2000 caracteres');
        }
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM sponsorupdate'));
        // Un animal sin padrinos activos no recibe novedades (y no aparece en la página).
        self::assertSame(0, $this->client->getCrawler()->filter('#sponsored-'.$alone->id)->count());
        // Las peticiones anteriores han vaciado el EntityManager de la prueba: se vuelven a cargar las entidades.
        [$animal, $alone, $coordinator] = [$this->em->find(Animal::class, $animal->id), $this->em->find(Animal::class, $alone->id), $this->em->find(User::class, $coordinator->id)];
        self::assertSame(2000, mb_strlen($this->service()->addUpdate($animal, $coordinator, ['body' => str_repeat('b', 2000)])->body));

        // Un apadrinamiento terminado conserva las novedades de su periodo y no recibe las posteriores.
        $ended = $this->sponsorship($this->available(), $this->sponsorAccount('Padrino Anterior Ficticio'), 1500, '-20 days');
        $this->update($ended->animal, 'Durante el apadrinamiento.', '-15 days');
        $this->em->getConnection()->executeStatement("UPDATE sponsorship SET endedAt = ?, endReason = 'finished' WHERE id = ?", [$this->utc('-10 days')->format('Y-m-d H:i:s'), $ended->id]);
        $this->sponsorship($ended->animal, $this->sponsorAccount('Padrina Nueva Ficticia'));
        $this->update($ended->animal, 'Después del fin.', '-1 day');
        $this->em->refresh($ended);
        self::assertSame(['Durante el apadrinamiento.'], array_map(static fn(SponsorUpdate $u) => $u->body, $this->service()->updatesFor($ended)));
        // Una novedad escrita en el mismo segundo en que empieza el apadrinamiento la ve ese padrino (caso límite).
        $sameSecond = $this->sponsorship($this->available(), $this->sponsorAccount('Padrina Puntual Ficticia'), 1500, '-3 days');
        $this->em->persist(new SponsorUpdate($sameSecond->animal, $coordinator, $sameSecond->startedAt, 'Mismo segundo.'));
        $this->em->flush();
        self::assertSame(['Mismo segundo.'], array_map(static fn(SponsorUpdate $u) => $u->body, $this->service()->updatesFor($sameSecond)));

        // Al final: el rechazo cierra el EntityManager de la prueba, como en HU-05 a HU-07.
        try {
            $this->service()->addUpdate($alone, $coordinator, ['body' => 'Sin destinatarios.']);
            self::fail('Se esperaba el rechazo.');
        } catch (SponsorshipRefused $refused) {
            self::assertStringContainsString('no tiene padrinos activos', $refused->getMessage());
        }
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM sponsorupdate WHERE animal_id = ?', [$ended->animal->id]));
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM sponsorupdate WHERE animal_id = ?', [$alone->id]));
    }

    // CA-08-04
    public function testAdoptedAnimalOffersSponsorToContinueOrFinish(): void
    {
        $animal = $this->available();
        $sponsor = $this->sponsorAccount();
        $sponsorship = $this->sponsorship($animal, $sponsor, 1800);
        $target = $this->available();

        // Reservado (HU-04): el apadrinamiento sigue activo, sin decisión pendiente (caso límite, analyze A2).
        $this->em->getConnection()->executeStatement("UPDATE animal SET status = 'reserved' WHERE id = ?", [$animal->id]);
        $this->client->loginUser($sponsor);
        $this->client->request('GET', '/mis-apadrinamientos');
        self::assertSelectorTextContains('#sponsorship-'.$sponsorship->id.' .tag', 'Activo');
        self::assertSelectorNotExists('#form-move-'.$sponsorship->id);
        $this->em->refresh($animal);
        $this->adopt($animal);

        // HU-05 no toca el apadrinamiento: sigue activo, pendiente de decisión, y no se crea otro.
        self::assertCount(1, $this->rows());
        self::assertNull($this->rows()[0]['endedat']);

        $this->client->loginUser($sponsor);
        $crawler = $this->client->request('GET', '/mis-apadrinamientos');
        $card = $crawler->filter('#sponsorship-'.$sponsorship->id);
        self::assertSame('Pendiente de tu decisión', $card->filter('.tag')->text());
        $deliveredAt = (new \DateTimeImmutable($this->em->getConnection()->fetchOne('SELECT deliveredAt FROM adoption'), new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Europe/Madrid'));
        self::assertStringContainsString('¡'.$animal->code.' ha encontrado familia! Fue adoptado el '.$deliveredAt->format('d/m/Y').'.', $card->filter('.notification')->text());
        self::assertStringContainsString('Tu aportación está en pausa desde la adopción', $card->filter('.notification')->text());
        self::assertSame(['', $target->id], $card->filter('#animal-'.$sponsorship->id.' option')->each(static fn($o) => $o->attr('value')));
        self::assertSame(1, $card->filter('button:contains("Apadrinar al animal elegido")')->count());
        self::assertSame(1, $card->filter('button:contains("Terminar mi colaboración")')->count());

        // La coordinadora ve el apadrinamiento en pausa.
        $this->client->loginUser($this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR'));
        $this->client->request('GET', '/admin/apadrinamientos');
        self::assertSelectorTextContains('#sponsored-'.$animal->id, 'Adoptado · 1 apadrinamiento pendiente de decisión');
        self::assertSelectorTextContains('#sponsored-'.$animal->id.' .sponsors', 'En pausa');
    }

    // CA-08-05
    public function testSponsorChoiceIsRecordedWithoutUnilateralAssignment(): void
    {
        $sponsor = $this->sponsorAccount();
        $adopted = $this->available();
        $sponsorship = $this->sponsorship($adopted, $sponsor, 1800);
        $target = $this->available();
        $this->adopt($adopted);
        $this->client->loginUser($sponsor);

        // Otro animal elegido por el padrino: la misma aportación, enlazada con la anterior.
        $this->client->request('POST', '/mis-apadrinamientos/'.$sponsorship->id.'/cambiar', ['_token' => $this->mineToken('#form-move-'.$sponsorship->id), 'animal' => $target->id]);
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        $rows = $this->rows($sponsor);
        self::assertCount(2, $rows);
        [$old, $new] = $rows[0]['id'] === $sponsorship->id ? $rows : array_reverse($rows);
        self::assertSame('moved', $old['endreason']);
        self::assertNotNull($old['endedat']);
        self::assertSame([$target->id, 1800, $sponsorship->id, null], [$new['animal_id'], (int) $new['monthlycents'], $new['previous_id'], $new['endedat']]);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.notification.is-success', 'Ahora apadrinas a '.$target->code.' con 18,00 € al mes.');
        self::assertSelectorTextContains('#sponsorship-'.$sponsorship->id, 'Cambio a otro animal ('.$target->code.')');

        // Terminar la colaboración tras otra adopción.
        $second = $this->sponsorship($other = $this->available(), $this->sponsorAccount('Segundo Padrino Ficticio'));
        $this->adopt($other);
        $this->client->loginUser($second->sponsor);
        $this->client->request('POST', '/mis-apadrinamientos/'.$second->id.'/terminar', ['_token' => $this->mineToken('#sponsorship-'.$second->id.' form[action$="/terminar"]')]);
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        $row = $this->rows($second->sponsor);
        self::assertCount(1, $row);
        self::assertSame('finished', $row[0]['endreason']);
        $this->client->followRedirect();
        self::assertSelectorTextContains('#sponsorship-'.$second->id, 'Fin de la colaboración');
        self::assertSelectorTextContains('#sponsorship-'.$second->id.' .tag', 'Terminado');
    }

    // CA-08-06
    public function testSponsorshipCardinalityAndVoluntaryEnd(): void
    {
        $first = $this->available();
        $second = $this->available();
        $sponsor = $this->sponsorAccount();
        $other = $this->sponsorAccount('Otra Madrina Ficticia');
        $this->sponsorship($first, $other);
        $this->client->loginUser($sponsor);

        // Varios padrinos por animal y varios animales por padrino.
        $this->sponsorByHttp($first, '12');
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        $this->sponsorByHttp($second, '12');
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        self::assertCount(2, $this->rows($sponsor));
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM sponsorship WHERE animal_id = ? AND endedAt IS NULL', [$first->id]));

        // Un único apadrinamiento activo por cuenta y animal, aunque se envíe otra vez el formulario.
        $this->sponsorByHttp($first, '20');
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#error-sponsorship', 'Ya apadrinas a '.$first->code.'.');
        self::assertCount(2, $this->rows($sponsor));

        // El padrino puede terminar en cualquier momento y volver a apadrinar después.
        $mine = $this->em->getRepository(Sponsorship::class)->findOneBy(['animal' => $first, 'sponsor' => $sponsor]);
        $this->client->request('POST', '/mis-apadrinamientos/'.$mine->id.'/terminar', ['_token' => $this->mineToken('#sponsorship-'.$mine->id.' form[action$="/terminar"]')]);
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        self::assertSame('finished', $this->em->getConnection()->fetchOne('SELECT endReason FROM sponsorship WHERE id = ?', [$mine->id]));
        $crawler = $this->client->request('GET', '/mis-apadrinamientos');
        self::assertSame(0, $crawler->filter('#sponsorship-'.$mine->id.' form')->count(), 'El terminado se lista sin formularios.');
        $this->sponsorByHttp($first, '25');
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM sponsorship WHERE animal_id = ? AND sponsor_id = ?', [$first->id, $sponsor->id]));
    }

    // CA-08-07
    public function testOnlyAvailableAnimalsCanBeSponsored(): void
    {
        $sponsor = $this->sponsorAccount();
        $this->client->loginUser($sponsor);
        foreach (['quarantine', 'reserved', 'adopted'] as $status) {
            $animal = $this->animal(1, $status);
            $this->client->request('GET', '/animales/'.$animal->id.'/apadrinar');
            self::assertResponseStatusCodeSame(404, $status);
        }

        // Página abierta cuando el animal estaba disponible y reservado después: 409 sin cambios.
        $animal = $this->available();
        $token = $this->newFormToken($animal);
        $this->em->getConnection()->executeStatement("UPDATE animal SET status = 'reserved' WHERE id = ?", [$animal->id]);
        $this->client->request('POST', '/animales/'.$animal->id.'/apadrinar', ['_token' => $token, 'amount' => '15']);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#error-sponsorship', 'ya no está disponible para apadrinar');
        self::assertSame([], $this->rows());

        // La cuarentena vencida se promociona antes de apadrinar (D01).
        $due = $this->animal(11, 'quarantine');
        $this->sponsorByHttp($due, '10');
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
    }

    // CA-08-08
    public function testInvalidContinuityChoicesAreRejected(): void
    {
        $sponsor = $this->sponsorAccount();
        $active = $this->sponsorship($this->available(), $sponsor);
        $adopted = $this->available();
        $pending = $this->sponsorship($adopted, $sponsor, 1500, '-9 days');
        $spare = $this->available();
        $reserved = $this->animal(20, 'reserved');
        $this->adopt($adopted);
        $this->client->loginUser($sponsor);

        // El formulario ofrece solo los disponibles que todavía no apadrina; sin elegir (o uno inexistente): 422.
        $crawler = $this->client->request('GET', '/mis-apadrinamientos');
        self::assertSame(['', $spare->id], $crawler->filter('#animal-'.$pending->id.' option')->each(static fn($o) => $o->attr('value')));
        foreach (['', 'no-existe'] as $choice) {
            $this->client->request('POST', '/mis-apadrinamientos/'.$pending->id.'/cambiar', ['_token' => $this->mineToken('#form-move-'.$pending->id), 'animal' => $choice]);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorTextContains('#error-animal', 'Elige uno de los animales disponibles');
        }
        // Página abierta con un animal que se reserva antes de enviar: 409 sin cambios.
        $token = $this->mineToken('#form-move-'.$pending->id);
        $this->em->getConnection()->executeStatement("UPDATE animal SET status = 'reserved' WHERE id = ?", [$spare->id]);
        $this->client->request('POST', '/mis-apadrinamientos/'.$pending->id.'/cambiar', ['_token' => $token, 'animal' => $spare->id]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#error-animal', 'ya no está disponible para apadrinar');
        // Sin disponibles, la página lo indica y no muestra el formulario de cambio.
        self::assertSelectorNotExists('#form-move-'.$pending->id);
        self::assertSelectorTextContains('#sponsorship-'.$pending->id, 'no hay otros animales disponibles');

        // El mismo animal adoptado, uno que ya apadrina o un apadrinamiento cuyo animal no se ha adoptado: 409.
        $cases = [
            [$pending, $adopted, 'distinto del que ha sido adoptado'],
            [$pending, $active->animal, 'Ya apadrinas a'],
            [$pending, $reserved, 'ya no está disponible'],
            [$active, $this->available(), 'cuando el que apadrinas ha sido adoptado'],
        ];
        foreach ($cases as [$sponsorship, $target, $message]) {
            try {
                $this->service()->moveTo($sponsorship, $target, $sponsor);
                self::fail('Se esperaba el rechazo: '.$message);
            } catch (SponsorshipRefused $refused) {
                self::assertStringContainsString($message, $refused->getMessage());
            }
        }
        $connection = $this->em->getConnection();
        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM sponsorship WHERE endedAt IS NOT NULL'));
        self::assertCount(2, $this->rows($sponsor));

        // Terminar uno ya terminado: 409 (con un EntityManager nuevo, porque el rechazo cierra el anterior).
        $this->client->request('POST', '/mis-apadrinamientos/'.$active->id.'/terminar', ['_token' => $this->mineToken('#sponsorship-'.$active->id.' form[action$="/terminar"]')]);
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        self::getContainer()->get('doctrine')->resetManager();
        $this->refreshEm();
        try {
            $this->service()->finish($this->em->find(Sponsorship::class, $active->id), $this->em->find(User::class, $sponsor->id));
            self::fail('Se esperaba el rechazo.');
        } catch (SponsorshipRefused $refused) {
            self::assertStringContainsString('ya ha terminado', $refused->getMessage());
        }
        self::assertSame(['finished', null], [
            $connection->fetchOne('SELECT endReason FROM sponsorship WHERE id = ?', [$active->id]),
            $connection->fetchOne('SELECT endReason FROM sponsorship WHERE id = ?', [$pending->id]),
        ]);
    }

    /** @return list<string> resultados de procesos simultáneos de tests/Support/sponsorship-race.php */
    private function race(array $jobs): array
    {
        $processes = [];
        foreach ($jobs as $arguments) {
            $command = [PHP_BINARY, dirname(__DIR__).'/tests/Support/sponsorship-race.php', ...$arguments];
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

    // CA-08-09
    public function testConcurrentSponsorshipsOfSameAnimalCreateOne(): void
    {
        $animal = $this->available();
        $sponsor = $this->sponsorAccount();
        $start = (string) (microtime(true) + 2.0);
        $results = $this->race([['sponsor', $animal->id, $sponsor->id, $start], ['sponsor', $animal->id, $sponsor->id, $start]]);
        self::assertSame(['sponsor:ok', 'sponsor:rechazada'], $results);
        self::assertCount(1, $this->rows($sponsor));
    }

    // CA-08-09
    public function testConcurrentContinuityChoicesApplyOnlyOne(): void
    {
        $sponsor = $this->sponsorAccount();
        $adopted = $this->available();
        $sponsorship = $this->sponsorship($adopted, $sponsor);
        $first = $this->available();
        $second = $this->available();
        $this->adopt($adopted);
        $start = (string) (microtime(true) + 2.0);
        $results = $this->race([
            ['move', $sponsorship->id, $first->id, $start],
            ['move', $sponsorship->id, $second->id, $start],
            ['finish', $sponsorship->id, '-', $start],
        ]);
        self::assertSame(1, count(array_filter($results, static fn(string $r) => str_ends_with($r, ':ok'))), implode(', ', $results));
        $active = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM sponsorship WHERE sponsor_id = ? AND endedAt IS NULL', [$sponsor->id]);
        $total = count($this->rows($sponsor));
        // O se trasladó a uno solo de los dos animales (2 filas, 1 activa) o terminó (1 fila, 0 activas).
        self::assertContains([$total, $active], [[2, 1], [1, 0]]);
    }

    // CA-08-10
    public function testOnlyPublicAccountsSponsorAndOnlyCoordinatorPublishes(): void
    {
        $animal = $this->available();
        $sponsor = $this->sponsorAccount();
        $sponsorship = $this->sponsorship($animal, $sponsor);

        $this->client->request('GET', '/animales/'.$animal->id.'/apadrinar');
        self::assertResponseRedirects('/acceso');
        $this->client->request('GET', '/mis-apadrinamientos');
        self::assertResponseRedirects('/acceso');

        foreach (['ROLE_VOLUNTEER', 'ROLE_VET', 'ROLE_COORDINATOR', 'ROLE_FOSTER'] as $role) {
            $this->client->loginUser($account = $this->sponsorAccount('Cuenta de trabajo', $role));
            $this->client->request('GET', '/animales/'.$animal->id);
            self::assertSelectorNotExists('#sponsor-box');
            $this->client->request('GET', '/animales/'.$animal->id.'/apadrinar');
            self::assertResponseStatusCodeSame(403, $role);
            $this->client->request('GET', '/mis-apadrinamientos');
            self::assertResponseStatusCodeSame(403, $role);
            try {
                $this->service()->sponsor($animal, $account, ['amount' => '10']);
                self::fail('Se esperaba el rechazo del servicio para '.$role);
            } catch (SponsorshipRefused $refused) {
                self::assertStringContainsString('Solo las cuentas de padrinos', $refused->getMessage());
            }
        }

        // Una cuenta pública no gestiona apadrinamientos ajenos (404) ni publica novedades.
        $this->client->loginUser($this->sponsorAccount('Persona Ajena Ficticia'));
        $this->client->request('POST', '/mis-apadrinamientos/'.$sponsorship->id.'/terminar', ['_token' => 'x']);
        self::assertResponseStatusCodeSame(404);
        $this->client->request('POST', '/mis-apadrinamientos/'.$sponsorship->id.'/cambiar', ['_token' => 'x', 'animal' => $this->available()->id]);
        self::assertResponseStatusCodeSame(404, 'Cambiar un apadrinamiento ajeno (converge C1, T822).');
        $this->client->request('GET', '/admin/apadrinamientos');
        self::assertResponseStatusCodeSame(403);
        $this->client->loginUser($this->sponsorAccount('Voluntaria Ficticia', 'ROLE_VOLUNTEER'));
        $this->client->request('GET', '/admin/apadrinamientos');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/admin/apadrinamientos/'.$animal->id.'/novedades', ['_token' => 'x', 'body' => 'No autorizada.']);
        self::assertResponseStatusCodeSame(403);

        // CSRF no válido: sin cambios.
        $this->client->loginUser($sponsor);
        $this->client->request('POST', '/animales/'.$this->available()->id.'/apadrinar', ['_token' => 'no-valido', 'amount' => '10']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/mis-apadrinamientos/'.$sponsorship->id.'/terminar', ['_token' => 'no-valido']);
        self::assertResponseStatusCodeSame(403);
        $this->client->loginUser($this->sponsorAccount('Coordinadora Ficticia', 'ROLE_COORDINATOR'));
        $this->client->request('POST', '/admin/apadrinamientos/'.$animal->id.'/novedades', ['_token' => 'no-valido', 'body' => 'Sin token.']);
        self::assertResponseStatusCodeSame(403);

        self::assertCount(1, $this->rows(), 'Solo el apadrinamiento inicial.');
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM sponsorupdate'));
        self::assertNull($this->em->getConnection()->fetchOne('SELECT endedAt FROM sponsorship WHERE id = ?', [$sponsorship->id]));
    }

    // Revisión previa a la entrega, C2-01: un campo ausente del POST es un 422 y no un 500 (twig.strict_variables).
    public function testSponsorFormWithoutAmountFieldIsRejectedWith422(): void
    {
        $animal = $this->available();
        $this->client->loginUser($this->sponsorAccount());
        $this->client->request('POST', '/animales/'.$animal->id.'/apadrinar', ['_token' => $this->newFormToken($animal)]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-amount', 'al menos 10,00 €');
        self::assertSame('', $this->client->getCrawler()->filter('#amount')->attr('value'));
        self::assertSame([], $this->rows());
    }

    // Revisión previa a la entrega, C2-04: terminar un apadrinamiento ya terminado explica el rechazo.
    public function testFinishingAnEndedSponsorshipShowsTheRefusal(): void
    {
        $sponsor = $this->sponsorAccount();
        $sponsorship = $this->sponsorship($this->available(), $sponsor);
        $this->client->loginUser($sponsor);
        $token = $this->mineToken('#sponsorship-'.$sponsorship->id.' form[action$="/terminar"]');
        $this->client->request('POST', '/mis-apadrinamientos/'.$sponsorship->id.'/terminar', ['_token' => $token]);
        self::assertResponseRedirects('/mis-apadrinamientos', 303);

        // Segundo envío del mismo formulario: ya terminado, 409 con mensaje dentro de la tarjeta.
        $this->client->request('POST', '/mis-apadrinamientos/'.$sponsorship->id.'/terminar', ['_token' => $token]);
        self::assertResponseStatusCodeSame(409);
        self::assertSelectorTextContains('#sponsorship-'.$sponsorship->id.' #error-sponsorship', 'Este apadrinamiento ya ha terminado.');
    }

    // Revisión previa a la entrega, C2-05: un animal con la cuarentena recién cumplida es destino válido.
    public function testAnimalWhoseQuarantineJustEndedIsOfferedAsTarget(): void
    {
        $sponsor = $this->sponsorAccount();
        $adopted = $this->available();
        $sponsorship = $this->sponsorship($adopted, $sponsor, 1800);
        $this->adopt($adopted);
        $this->client->loginUser($sponsor);

        $due = $this->animal(11, 'quarantine');
        $crawler = $this->client->request('GET', '/mis-apadrinamientos');
        self::assertSame(['', $due->id], $crawler->filter('#animal-'.$sponsorship->id.' option')->each(static fn($o) => $o->attr('value')));
        $token = $crawler->filter('#form-move-'.$sponsorship->id.' input[name="_token"]')->attr('value');

        // También al elegirlo directamente: la cuarentena vence después de cargar la página.
        $this->em->getConnection()->executeStatement("UPDATE animal SET status = 'quarantine' WHERE id = ?", [$due->id]);
        $this->client->request('POST', '/mis-apadrinamientos/'.$sponsorship->id.'/cambiar', ['_token' => $token, 'animal' => $due->id]);
        self::assertResponseRedirects('/mis-apadrinamientos', 303);
        self::assertSame('available', $this->em->getConnection()->fetchOne('SELECT status FROM animal WHERE id = ?', [$due->id]));
        self::assertCount(2, $this->rows($sponsor));
    }
}
