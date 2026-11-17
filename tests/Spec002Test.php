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
}
