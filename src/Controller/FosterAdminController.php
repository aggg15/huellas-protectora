<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{Animal, FosterPlacement, User};
use App\Service\{FosterRefused, FosterService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Gestión de acogidas por la coordinadora: casas, registro y finalización (HU-06, D21, D22). */
#[IsGranted('ROLE_COORDINATOR')]
class FosterAdminController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private FosterService $fosters) {}

    #[Route('/admin/acogidas', name: 'admin_fosters', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('foster/admin_index.html.twig', [
            'homes' => $this->fosters->homesOverview(),
            'placements' => $this->fosters->activePlacements(),
            'today' => $this->fosters->today(),
        ]);
    }

    #[Route('/admin/animales/{id}/acogida', name: 'admin_foster_place', methods: ['POST'])]
    public function place(string $id, Request $request): Response
    {
        $data = $this->data($request, 'foster-place-'.$id);
        $animal = $this->em->find(Animal::class, $id) ?? throw $this->createNotFoundException();
        try {
            $placement = $this->fosters->place($animal, $this->coordinator(), $data);
        } catch (ValidationError $error) {
            return $this->animalPage($id, $error->errors, $data, 422);
        } catch (FosterRefused $refused) {
            return $this->animalPage($id, ['placement' => $refused->getMessage()], $data, 409);
        }
        $this->addFlash('success', 'Acogida registrada en la casa de '.$placement->home->family->name.'.');
        return $this->redirectToRoute('animal_show', ['id' => $id], 303);
    }

    #[Route('/admin/acogidas/{id}/finalizar', name: 'admin_foster_end', methods: ['POST'])]
    public function end(string $id, Request $request): Response
    {
        $this->data($request, 'foster-end-'.$id);
        $placement = $this->em->find(FosterPlacement::class, $id) ?? throw $this->createNotFoundException();
        $animalId = $placement->animal->id;
        try {
            $this->fosters->end($placement);
        } catch (FosterRefused $refused) {
            return $this->animalPage($animalId, ['placement' => $refused->getMessage()], [], 409);
        }
        $this->addFlash('success', 'Acogida finalizada: el animal regresa al refugio.');
        return $this->redirectToRoute('animal_show', ['id' => $animalId], 303);
    }

    /** Vuelve a mostrar la ficha interna del animal (HU-01) con los errores de acogida y los valores escritos. */
    private function animalPage(string $animalId, array $errors, array $data, int $status): Response
    {
        // Un rechazo cierra el EntityManager, pero la ficha solo lee; los rechazos se lanzan antes de modificar entidades.
        $response = $this->forward(AnimalController::class.'::show', ['id' => $animalId, 'fosterErrors' => $errors, 'fosterData' => $data]);
        $response->setStatusCode($status);
        return $response;
    }

    private function coordinator(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) { throw $this->createAccessDeniedException(); }
        return $user;
    }

    /** @return array<string, string> */
    private function data(Request $request, string $tokenId): array
    {
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $request->request->all());
        if (!$this->isCsrfTokenValid($tokenId, $data['_token'] ?? null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
        unset($data['_token']);
        return $data;
    }
}
