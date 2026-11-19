<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{Adoption, User};
use App\Service\AdoptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Consulta de adopciones formalizadas, contratos y seguimientos (HU-05). */
class AdoptionController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private AdoptionService $adoptions) {}

    #[Route('/admin/adopciones/{id}', name: 'admin_adoption_show', methods: ['GET'])]
    #[IsGranted('ROLE_COORDINATOR')]
    public function show(string $id): Response
    {
        return $this->render('adoption/show.html.twig', ['adoption' => $this->find($id)]);
    }

    #[Route('/admin/seguimientos', name: 'admin_follow_ups', methods: ['GET'])]
    #[IsGranted('ROLE_COORDINATOR')]
    public function followUps(): Response
    {
        return $this->render('adoption/follow_ups.html.twig', ['followUps' => $this->adoptions->scheduledFollowUps()]);
    }

    /** Contrato imprimible: solo la coordinadora o el adoptante titular; cualquier otra cuenta, 404 (D13). */
    #[Route('/adopciones/{id}/contrato', name: 'adoption_contract', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function contract(string $id): Response
    {
        $adoption = $this->find($id);
        $user = $this->getUser();
        $isAdopter = $user instanceof User && $user->id === $adoption->adopter->id;
        if (!$isAdopter && !$this->isGranted('ROLE_COORDINATOR')) { throw $this->createNotFoundException(); }
        return $this->render('adoption/contract.html.twig', ['adoption' => $adoption]);
    }

    private function find(string $id): Adoption
    {
        return $this->em->find(Adoption::class, $id) ?? throw $this->createNotFoundException();
    }
}
