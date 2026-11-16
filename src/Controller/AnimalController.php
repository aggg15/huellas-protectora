<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\{Animal, AnimalPhoto, VeterinaryReview};
use App\Service\{AnimalService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
class AnimalController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private AnimalService $animals) {}
    #[Route('/admin/animales', name: 'admin_animals', methods: ['GET'])]
    public function index(): Response
    {
        $this->animals->releaseDueAnimals();
        return $this->render('animal/index.html.twig', ['animals'=>$this->em->getRepository(Animal::class)->findBy([], ['receivedAt'=>'DESC'])]);
    }
    #[Route('/admin/animales/nuevo', name: 'animal_new', priority: 10, methods: ['GET','POST'])]
    #[IsGranted('ROLE_VOLUNTEER')]
    public function create(Request $request): Response
    {
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $request->request->all()); $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('animal_new', is_string($data['_token'] ?? null) ? $data['_token'] : null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
            try {
                $animal = $this->animals->create($data, $request->files->all('photos'));
                $this->addFlash('success', 'Ficha creada. Su disponibilidad respeta los diez días de cuarentena.');
                return $this->redirectToRoute('animal_show', ['id'=>$animal->id], 303);
            } catch (ValidationError $error) { $errors = $error->errors; }
        }
        return $this->render('animal/new.html.twig', ['data'=>$data, 'errors'=>$errors], new Response(status: $errors ? 422 : 200));
    }
    #[Route('/admin/animales/{id}', name: 'animal_show', methods: ['GET'])]
    public function show(string $id, array $errors = []): Response
    {
        $this->animals->releaseDueAnimals();
        $animal = $this->em->find(Animal::class, $id) ?? throw $this->createNotFoundException();
        return $this->render('animal/show.html.twig', [
            'animal'=>$animal, 'errors'=>$errors,
            'photos'=>$this->em->getRepository(AnimalPhoto::class)->findBy(['animal'=>$animal]),
            'reviews'=>$this->em->getRepository(VeterinaryReview::class)->findBy(['animal'=>$animal], ['reviewedAt'=>'DESC']),
        ], new Response(status: $errors ? 422 : 200));
    }
    #[Route('/admin/animales/{id}/revision', name: 'animal_review', methods: ['POST'])]
    #[IsGranted('ROLE_VET')]
    public function review(string $id, Request $request): Response
    {
        $data = $request->request->all();
        if (!$this->isCsrfTokenValid('review-'.$id, is_string($data['_token'] ?? null) ? $data['_token'] : null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
        $animal = $this->em->find(Animal::class, $id) ?? throw $this->createNotFoundException();
        try { $this->animals->review($animal, $this->getUser(), $data); }
        catch (ValidationError $error) { return $this->show($id, $error->errors); }
        $this->addFlash('success', 'Revisión veterinaria registrada.');
        return $this->redirectToRoute('animal_show', ['id'=>$id], 303);
    }
}
