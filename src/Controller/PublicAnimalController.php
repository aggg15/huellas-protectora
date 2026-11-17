<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{Animal, AnimalPhoto};
use App\Service\AnimalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class PublicAnimalController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private AnimalService $animals) {}

    #[Route('/animales', name: 'public_catalog', methods: ['GET'])]
    public function catalog(Request $request): Response
    {
        $species = $request->query->getString('species');
        $size = $request->query->getString('size');
        $age = $request->query->getString('age');

        $filters = array_filter([
            'species' => $species,
            'size' => $size,
            'age' => $age,
        ], static fn(string $v): bool => $v !== '');

        $animalsList = $this->animals->findAvailableAnimals($filters);

        $primaryPhotos = [];
        if ($animalsList !== []) {
            $photos = $this->em->getRepository(AnimalPhoto::class)->findBy(['animal' => $animalsList]);
            foreach ($photos as $photo) {
                if (!isset($primaryPhotos[$photo->animal->id])) {
                    $primaryPhotos[$photo->animal->id] = $photo;
                }
            }
        }

        return $this->render('public/catalog.html.twig', [
            'animals' => $animalsList,
            'primaryPhotos' => $primaryPhotos,
            'filters' => [
                'species' => $species,
                'size' => $size,
                'age' => $age,
            ],
        ]);
    }

    #[Route('/animales/{id}', name: 'public_animal_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $animal = $this->animals->getPublicAnimal($id);
        if ($animal === null) {
            throw $this->createNotFoundException('El animal no está disponible para adopción.');
        }

        $photos = $this->em->getRepository(AnimalPhoto::class)->findBy(['animal' => $animal]);

        return $this->render('public/show.html.twig', [
            'animal' => $animal,
            'photos' => $photos,
        ]);
    }
}
