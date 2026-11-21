<?php
declare(strict_types=1);
namespace App\Tests;

use App\Entity\{Animal, AnimalPhoto};

class Spec002Test extends WebTestBase
{
    public function testAnonymousCanViewPublicCatalogWithAvailableAnimals(): void
    {
        $dog = $this->animal(0, 'available', 'dog', 14, 'medium');
        $cat = $this->animal(0, 'available', 'cat', 8, 'small');
        $photo = new AnimalPhoto($dog, 'foto-publica.png', 'image/png');
        $this->em->persist($photo);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/animales');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Animales en adopción');
        self::assertSelectorTextContains('main', $dog->code);
        self::assertSelectorTextContains('main', $cat->code);
        self::assertCount(1, $crawler->filter('img[src="/fotos/'.$photo->id.'"]'));
    }

    public function testFilterBySpecies(): void
    {
        $dog = $this->animal(0, 'available', 'dog', 24, 'medium');
        $cat = $this->animal(0, 'available', 'cat', 10, 'small');

        $this->client->request('GET', '/animales?species=cat');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $cat->code);
        self::assertSelectorTextNotContains('main', $dog->code);

        $this->client->request('GET', '/animales?species=dog');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $dog->code);
        self::assertSelectorTextNotContains('main', $cat->code);
    }

    public function testFilterBySizeAndAgeCombined(): void
    {
        $puppySmall = $this->animal(0, 'available', 'dog', 6, 'small');
        $adultMedium = $this->animal(0, 'available', 'dog', 24, 'medium');
        $seniorSmall = $this->animal(0, 'available', 'dog', 96, 'small');

        // Cachorro pequeño (<12 meses)
        $this->client->request('GET', '/animales?species=dog&size=small&age=<12');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $puppySmall->code);
        self::assertSelectorTextNotContains('main', $adultMedium->code);
        self::assertSelectorTextNotContains('main', $seniorSmall->code);

        // Senior pequeño (>=84 meses)
        $this->client->request('GET', '/animales?species=dog&size=small&age=>=84');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $seniorSmall->code);
        self::assertSelectorTextNotContains('main', $puppySmall->code);
        self::assertSelectorTextNotContains('main', $adultMedium->code);

        // Adulto mediano (12-83 meses)
        $this->client->request('GET', '/animales?species=dog&size=medium&age=12-83');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $adultMedium->code);
        self::assertSelectorTextNotContains('main', $puppySmall->code);
        self::assertSelectorTextNotContains('main', $seniorSmall->code);
    }

    public function testFilterWithoutMatchesShowsEmptyState(): void
    {
        $this->animal(0, 'available', 'dog', 24, 'medium');

        $this->client->request('GET', '/animales?species=cat&size=large');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'No se encontraron animales');
        self::assertSelectorTextContains('main', 'No hay animales disponibles para adopción');
        self::assertSelectorExists('a[href="/animales"]');
    }

    public function testQuarantineReservedAndAdoptedAnimalsExcludedFromCatalog(): void
    {
        $avail = $this->animal(0, 'available', 'dog', 18, 'medium');
        $quarantine = $this->animal(1, 'quarantine', 'dog', 18, 'medium');
        $reserved = $this->animal(5, 'reserved', 'cat', 12, 'small');
        $adopted = $this->animal(15, 'adopted', 'dog', 36, 'large');

        $this->client->request('GET', '/animales');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $avail->code);
        self::assertSelectorTextNotContains('main', $quarantine->code);
        self::assertSelectorTextNotContains('main', $reserved->code);
        self::assertSelectorTextNotContains('main', $adopted->code);
    }

    public function testNonAvailableAnimalDetailReturns404(): void
    {
        $avail = $this->animal(0, 'available', 'dog', 18, 'medium');
        $quarantine = $this->animal(1, 'quarantine', 'dog', 18, 'medium');
        $reserved = $this->animal(5, 'reserved', 'cat', 12, 'small');
        $adopted = $this->animal(15, 'adopted', 'dog', 36, 'large');

        // Disponible responde 200
        $this->client->request('GET', '/animales/'.$avail->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $avail->code);

        // Cuarentena, reservado, adoptado e inexistente responden 404
        $this->client->request('GET', '/animales/'.$quarantine->id);
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/animales/'.$reserved->id);
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/animales/'.$adopted->id);
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/animales/0193339c-8500-7000-8000-000000000099');
        self::assertResponseStatusCodeSame(404);
    }

    public function testFosteredAnimalShowsFosterBadgeInCatalogAndDetail(): void
    {
        $fostered = $this->animal(0, 'available', 'dog', 20, 'medium');
        $fostered->fostered = true;
        $this->em->flush();

        $normal = $this->animal(0, 'available', 'cat', 15, 'small');

        // En catálogo
        $this->client->request('GET', '/animales');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'En casa de acogida');

        // En ficha pública de detalle
        $this->client->request('GET', '/animales/'.$fostered->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'En casa de acogida');
        self::assertSelectorTextContains('main', 'Cuidado temporal en familia');

        // Ficha del animal no acogido no muestra el aviso de acogida
        $this->client->request('GET', '/animales/'.$normal->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextNotContains('main', 'Cuidado temporal en familia');
    }

    public function testExpiredQuarantinePromotesAutomaticallyOnCatalogAccess(): void
    {
        $expired = $this->animal(11, 'quarantine', 'cat', 12, 'small');

        $this->client->request('GET', '/animales');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $expired->code);

        $em = $this->refreshEm();
        $em->clear();
        $refreshed = $em->find(Animal::class, $expired->id);
        self::assertSame('available', $refreshed->status);
    }

    // C1-20b: límites de edad 11/12 y 83/84 meses.
    public function testAgeBoundariesAt12And84Months(): void
    {
        $a11 = $this->animal(0, 'available', 'dog', 11);
        $a12 = $this->animal(0, 'available', 'dog', 12);
        $a83 = $this->animal(0, 'available', 'dog', 83);
        $a84 = $this->animal(0, 'available', 'dog', 84);
        $expected = ['<12' => [$a11], '12-83' => [$a12, $a83], '>=84' => [$a84]];
        $all = [$a11, $a12, $a83, $a84];
        foreach ($expected as $age => $inside) {
            $this->client->request('GET', '/animales', ['age' => $age]);
            self::assertResponseIsSuccessful();
            $insideCodes = array_map(static fn(Animal $a) => $a->code, $inside);
            foreach ($all as $animal) {
                in_array($animal->code, $insideCodes, true)
                    ? self::assertSelectorTextContains('main', $animal->code, $age)
                    : self::assertSelectorTextNotContains('main', $animal->code, $age);
            }
        }
    }

    // C1-20b: filtros inválidos se ignoran (no filtran ni fallan).
    public function testInvalidFiltersAreIgnored(): void
    {
        $dog = $this->animal(0, 'available', 'dog', 24, 'medium');
        $cat = $this->animal(0, 'available', 'cat', 8, 'small');
        $this->client->request('GET', '/animales', ['species' => 'bird', 'size' => 'huge', 'age' => 'abc']);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $dog->code);
        self::assertSelectorTextContains('main', $cat->code);
    }

    // C1-20d: un animal no acogido no muestra el distintivo.
    public function testNonFosteredAnimalShowsNoFosterBadge(): void
    {
        $normal = $this->animal(0, 'available', 'cat', 15, 'small');
        $this->client->request('GET', '/animales');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', $normal->code);
        self::assertSelectorNotExists('.foster-badge');
        self::assertSelectorTextNotContains('main', 'En casa de acogida');
        $this->client->request('GET', '/animales/'.$normal->id);
        self::assertSelectorNotExists('.foster-badge');
        self::assertSelectorTextNotContains('main', 'En casa de acogida');
    }

    // C1-15: la foto principal es la de menor id, también con orden de inserción inverso.
    public function testPrimaryPhotoIsStableByIdAscending(): void
    {
        $dog = $this->animal(0, 'available', 'dog', 24, 'medium');
        $first = new AnimalPhoto($dog, 'a.png', 'image/png');
        $second = new AnimalPhoto($dog, 'b.png', 'image/png');
        [$low, $high] = strcmp($first->id, $second->id) < 0 ? [$first, $second] : [$second, $first];
        $this->em->persist($high);
        $this->em->flush();
        $this->em->persist($low);
        $this->em->flush();
        $crawler = $this->client->request('GET', '/animales');
        self::assertCount(1, $crawler->filter('img[src="/fotos/'.$low->id.'"]'));
        self::assertCount(0, $crawler->filter('img[src="/fotos/'.$high->id.'"]'));
        $crawler = $this->client->request('GET', '/animales/'.$dog->id);
        $sources = $crawler->filter('.photo-gallery img')->each(static fn($img) => $img->attr('src'));
        self::assertSame(['/fotos/'.$low->id, '/fotos/'.$high->id], $sources);
    }

    // C1-06 (transversal): páginas de error propias en español.
    public function testCustomErrorTemplatesRenderInSpanish(): void
    {
        $twig = self::getContainer()->get('twig');
        foreach ([403 => 'Acceso no permitido', 404 => 'Página no encontrada', 500 => 'Algo ha ido mal'] as $code => $title) {
            $template = in_array($code, [403, 404], true) ? "@Twig/Exception/error$code.html.twig" : '@Twig/Exception/error.html.twig';
            $html = $twig->render($template, ['status_code' => $code, 'status_text' => 'x', 'exception' => new \RuntimeException('detalle interno secreto')]);
            self::assertStringContainsString($title, $html);
            self::assertStringContainsString('<html lang="es"', $html);
            self::assertStringNotContainsString('detalle interno secreto', $html);
        }
    }

    public function testErrorPagesAreServedInSpanishWithoutDebug(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient(['debug' => false]);
        $client->request('GET', '/pagina-que-no-existe');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'Página no encontrada');
        self::assertStringNotContainsString('No route found', $client->getResponse()->getContent());
        $user = new \App\Entity\User('error403@example.test', 'Persona Ficticia');
        $user->roles = ['ROLE_USER'];
        $user->password = 'x';
        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);
        $client->request('GET', '/admin/animales/nuevo');
        self::assertResponseStatusCodeSame(403);
        self::assertSelectorTextContains('h1', 'Acceso no permitido');
    }
}
