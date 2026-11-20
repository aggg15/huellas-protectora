<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{Animal, User};
use App\Service\{SponsorshipRefused, SponsorshipService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Apadrinamientos activos y novedades para padrinos, a cargo de la coordinadora (HU-08, D37, D40). */
#[IsGranted('ROLE_COORDINATOR')]
class SponsorshipAdminController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private SponsorshipService $sponsorships) {}

    #[Route('/admin/apadrinamientos', name: 'admin_sponsorships', methods: ['GET'])]
    public function index(array $errors = [], ?string $animalId = null, array $formData = [], int $errorStatus = 422): Response
    {
        return $this->render('sponsorship/admin_index.html.twig', [
            'groups' => $this->sponsorships->overview(),
            'errors' => $errors,
            'animalId' => $animalId,
            'formData' => $formData,
        ], new Response(status: $errors ? $errorStatus : 200));
    }

    #[Route('/admin/apadrinamientos/{id}/novedades', name: 'admin_sponsor_update_add', methods: ['POST'])]
    public function addUpdate(string $id, Request $request): Response
    {
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $request->request->all());
        if (!$this->isCsrfTokenValid('sponsor-update-'.$id, $data['_token'] ?? null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
        $animal = $this->em->find(Animal::class, $id) ?? throw $this->createNotFoundException();
        $coordinator = $this->getUser();
        if (!$coordinator instanceof User) { throw $this->createAccessDeniedException(); }
        try {
            $this->sponsorships->addUpdate($animal, $coordinator, $data);
        } catch (ValidationError $error) {
            return $this->index($error->errors, $id, $data);
        } catch (SponsorshipRefused $refused) {
            return $this->index(['body' => $refused->getMessage()], $id, $data, 409);
        }
        $this->addFlash('success', sprintf('Novedad de %s publicada para sus padrinos.', $animal->code));
        return $this->redirectToRoute('admin_sponsorships', status: 303);
    }
}
