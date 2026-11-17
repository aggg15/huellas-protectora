<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{AdoptionApplication, AnimalPhoto, User};
use App\Service\{AnimalService, ApplicationRefused, ApplicationService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Solicitudes de adopción de la persona con cuenta (HU-03). */
#[IsGranted('ROLE_USER')]
class ApplicationController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private AnimalService $animals, private ApplicationService $applications) {}

    #[Route('/animales/{id}/solicitud', name: 'application_new', methods: ['GET', 'POST'])]
    public function create(string $id, Request $request): Response
    {
        $animal = $this->animals->getPublicAnimal($id) ?? throw $this->createNotFoundException('El animal no está disponible para adopción.');
        $user = $this->applicant();
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $request->request->all());
        $errors = [];
        $refusal = null;
        $status = 200;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('application-'.$animal->id, $data['_token'] ?? null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
            try {
                $this->applications->submit($user, $animal, $data);
                $this->addFlash('success', 'Solicitud enviada. La coordinadora valorará tu candidatura para '.$animal->code.'.');
                return $this->redirectToRoute('application_mine', status: 303);
            } catch (ValidationError $error) {
                $errors = $error->errors;
                $status = 422;
            } catch (ApplicationRefused $error) {
                $refusal = $error->getMessage();
                $status = 409;
            }
        } else {
            $refusal = $this->applications->refusalReason($user, $animal);
        }
        return $this->render('application/new.html.twig', [
            'animal' => $animal,
            'photo' => $this->em->getRepository(AnimalPhoto::class)->findOneBy(['animal' => $animal]),
            'data' => $data,
            'errors' => $errors,
            'refusal' => $refusal,
            'openCount' => $this->applications->openCount($user),
            'maxOpen' => ApplicationService::MAX_OPEN,
            'housingTypes' => ApplicationService::HOUSING_TYPES,
            'experienceTypes' => ApplicationService::EXPERIENCE_TYPES,
            'dailyTimes' => ApplicationService::DAILY_TIMES,
        ], new Response(status: $status));
    }

    #[Route('/mis-solicitudes', name: 'application_mine', methods: ['GET'])]
    public function mine(): Response
    {
        $user = $this->applicant();
        return $this->render('application/mine.html.twig', [
            'applications' => $this->em->getRepository(AdoptionApplication::class)->findBy(['applicant' => $user], ['submittedAt' => 'DESC']),
            'openCount' => $this->applications->openCount($user),
            'maxOpen' => ApplicationService::MAX_OPEN,
        ]);
    }

    private function applicant(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) { throw $this->createAccessDeniedException(); }
        return $user;
    }
}
