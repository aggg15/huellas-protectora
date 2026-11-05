<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\AnimalPhoto;
use App\Service\{AnimalService, PhotoStorage};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    #[Route('/fotos/{id}', name: 'photo', methods: ['GET'])]
    public function photo(string $id, EntityManagerInterface $em, AnimalService $animals, PhotoStorage $storage): BinaryFileResponse
    {
        $animals->releaseDueAnimals();
        $photo = $em->find(AnimalPhoto::class, $id) ?? throw $this->createNotFoundException();
        if ($photo->animal->status !== 'available' && !$this->isGranted('ROLE_STAFF')) { throw $this->createNotFoundException(); }
        if (!is_file($storage->path($photo->filename))) { throw $this->createNotFoundException(); }
        $response = new BinaryFileResponse($storage->path($photo->filename));
        $response->headers->set('Content-Type', $photo->mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
