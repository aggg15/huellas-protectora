<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\AdoptionApplication;
use App\Entity\User;
use App\Service\{AdoptionService, ApplicationRefused, ApplicationService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Consulta y evaluación de solicitudes por la coordinadora (HU-03 / HU-04). */
#[IsGranted('ROLE_COORDINATOR')]
class ApplicationReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicationService $applications,
        private AdoptionService $adoptions,
    ) {}

    #[Route('/admin/solicitudes', name: 'admin_applications', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('application/admin_index.html.twig', [
            'applications' => $this->em->getRepository(AdoptionApplication::class)->findBy([], ['submittedAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/solicitudes/{id}', name: 'admin_application_show', methods: ['GET'])]
    public function show(string $id, array $errors = [], int $errorStatus = 422, array $formData = []): Response
    {
        $application = $this->em->find(AdoptionApplication::class, $id) ?? throw $this->createNotFoundException();
        $otherApplications = $this->em->getRepository(AdoptionApplication::class)->findBy(
            ['animal' => $application->animal],
            ['submittedAt' => 'DESC']
        );

        return $this->render('application/admin_show.html.twig', [
            'application' => $application,
            'otherApplications' => array_filter($otherApplications, static fn(AdoptionApplication $a) => $a->id !== $application->id),
            'housingTypes' => ApplicationService::HOUSING_TYPES,
            'experienceTypes' => ApplicationService::EXPERIENCE_TYPES,
            'dailyTimes' => ApplicationService::DAILY_TIMES,
            'errors' => $errors,
            'adoption' => $this->adoptions->findForApplication($application),
            'formData' => array_map(static fn($value) => is_string($value) ? $value : '', $formData),
        ], new Response(status: $errors ? $errorStatus : 200));
    }

    #[Route('/admin/solicitudes/{id}/entrevista', name: 'admin_application_interview', methods: ['POST'])]
    public function interview(string $id, Request $request): Response
    {
        $data = $request->request->all();
        if (!$this->isCsrfTokenValid('interview-' . $id, is_string($data['_token'] ?? null) ? $data['_token'] : null)) {
            throw $this->createAccessDeniedException('Formulario caducado.');
        }

        $application = $this->em->find(AdoptionApplication::class, $id) ?? throw $this->createNotFoundException();
        try {
            $this->applications->recordInterview($application, $data);
            $this->addFlash('success', 'Entrevista registrada correctamente.');
            return $this->redirectToRoute('admin_application_show', ['id' => $id], 303);
        } catch (ValidationError $error) {
            return $this->show($id, $error->errors);
        } catch (ApplicationRefused $refused) {
            return $this->show($id, ['general' => $refused->getMessage()]);
        }
    }

    #[Route('/admin/solicitudes/{id}/aprobar', name: 'admin_application_approve', methods: ['POST'])]
    public function approve(string $id, Request $request): Response
    {
        $data = $request->request->all();
        if (!$this->isCsrfTokenValid('approve-' . $id, is_string($data['_token'] ?? null) ? $data['_token'] : null)) {
            throw $this->createAccessDeniedException('Formulario caducado.');
        }

        $application = $this->em->find(AdoptionApplication::class, $id) ?? throw $this->createNotFoundException();
        try {
            $this->applications->approve($application);
            $this->addFlash('success', 'Candidatura aprobada. El animal ha quedado reservado y las demás solicitudes han pasado a espera.');
            return $this->redirectToRoute('admin_application_show', ['id' => $id], 303);
        } catch (ValidationError $error) {
            return $this->show($id, $error->errors);
        } catch (ApplicationRefused $refused) {
            return $this->show($id, ['general' => $refused->getMessage()]);
        }
    }

    #[Route('/admin/solicitudes/{id}/rechazar', name: 'admin_application_reject', methods: ['POST'])]
    public function reject(string $id, Request $request): Response
    {
        $data = $request->request->all();
        if (!$this->isCsrfTokenValid('reject-' . $id, is_string($data['_token'] ?? null) ? $data['_token'] : null)) {
            throw $this->createAccessDeniedException('Formulario caducado.');
        }

        $application = $this->em->find(AdoptionApplication::class, $id) ?? throw $this->createNotFoundException();
        try {
            $this->applications->reject($application, $data);
            $this->addFlash('success', 'Candidatura rechazada con motivo registrado.');
            return $this->redirectToRoute('admin_application_show', ['id' => $id], 303);
        } catch (ValidationError $error) {
            return $this->show($id, $error->errors);
        } catch (ApplicationRefused $refused) {
            return $this->show($id, ['general' => $refused->getMessage()]);
        }
    }

    #[Route('/admin/solicitudes/{id}/cancelar-reserva', name: 'admin_application_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(string $id, Request $request): Response
    {
        $data = $request->request->all();
        if (!$this->isCsrfTokenValid('cancel-' . $id, is_string($data['_token'] ?? null) ? $data['_token'] : null)) {
            throw $this->createAccessDeniedException('Formulario caducado.');
        }

        $application = $this->em->find(AdoptionApplication::class, $id) ?? throw $this->createNotFoundException();
        try {
            $this->applications->cancelReservation($application, $data);
            $this->addFlash('success', 'Reserva liberada. El animal vuelve a estar disponible y las solicitudes en espera han vuelto a pendientes.');
            return $this->redirectToRoute('admin_application_show', ['id' => $id], 303);
        } catch (ValidationError $error) {
            return $this->show($id, $error->errors);
        } catch (ApplicationRefused $refused) {
            return $this->show($id, ['general' => $refused->getMessage()], 409);
        }
    }

    /** Formaliza la entrega de una solicitud aprobada (HU-05, D12 a D17). */
    #[Route('/admin/solicitudes/{id}/formalizar', name: 'admin_application_formalize', methods: ['POST'])]
    public function formalize(string $id, Request $request): Response
    {
        $data = $request->request->all();
        if (!$this->isCsrfTokenValid('formalize-' . $id, is_string($data['_token'] ?? null) ? $data['_token'] : null)) {
            throw $this->createAccessDeniedException('Formulario caducado.');
        }

        $application = $this->em->find(AdoptionApplication::class, $id) ?? throw $this->createNotFoundException();
        $coordinator = $this->getUser();
        if (!$coordinator instanceof User) { throw $this->createAccessDeniedException(); }
        try {
            $adoption = $this->adoptions->formalize($application, $coordinator, $data);
            $this->addFlash('success', 'Adopción formalizada. Se ha generado el contrato ' . $adoption->contractNumber . ' y se han programado los dos seguimientos.');
            return $this->redirectToRoute('admin_adoption_show', ['id' => $adoption->id], 303);
        } catch (ValidationError $error) {
            return $this->show($id, $error->errors, 422, $data);
        } catch (ApplicationRefused $refused) {
            return $this->show($id, ['general' => $refused->getMessage()], 409);
        }
    }
}
