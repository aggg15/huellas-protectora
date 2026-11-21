<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{Adoption, AdoptionApplication, Animal, AnimalPhoto, User};
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
        $user = $this->applicant();
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $request->request->all());
        $animal = $this->animals->getPublicAnimal($id);
        if ($animal === null) {
            // Un animal que se reserva o se adopta mientras la persona rellena el formulario da un 409 claro, pero solo
            // con un token CSRF válido (solo lo obtuvo quien abrió el formulario): sin él, o en cuarentena o inexistente, 404.
            $closed = $request->isMethod('POST') ? $this->em->find(Animal::class, $id) : null;
            if ($closed === null || !in_array($closed->status, ['reserved', 'adopted'], true)
                || !$this->isCsrfTokenValid('application-'.$closed->id, $data['_token'] ?? null)) {
                throw $this->createNotFoundException('El animal no está disponible para adopción.');
            }
            return $this->render('application/new.html.twig', [
                'animal' => $closed,
                'photo' => null,
                'data' => $data,
                'errors' => [],
                'refusal' => $this->applications->refusalReason($user, $closed),
                'openCount' => $this->applications->openCount($user),
                'maxOpen' => ApplicationService::MAX_OPEN,
                'housingTypes' => ApplicationService::HOUSING_TYPES,
                'experienceTypes' => ApplicationService::EXPERIENCE_TYPES,
                'dailyTimes' => ApplicationService::DAILY_TIMES,
            ], new Response(status: 409));
        }
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
        $adoptions = [];
        foreach ($this->em->getRepository(Adoption::class)->findBy(['adopter' => $user]) as $adoption) {
            $adoptions[$adoption->application->id] = $adoption;
        }
        return $this->render('application/mine.html.twig', [
            'applications' => $this->em->getRepository(AdoptionApplication::class)->findBy(['applicant' => $user], ['submittedAt' => 'DESC']),
            'adoptions' => $adoptions,
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
