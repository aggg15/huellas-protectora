<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\AdoptionApplication;
use App\Service\ApplicationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Consulta de las candidaturas recibidas por la coordinadora (CA-03-04). Las decisiones corresponden a HU-04. */
#[IsGranted('ROLE_COORDINATOR')]
class ApplicationReviewController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/admin/solicitudes', name: 'admin_applications', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('application/admin_index.html.twig', [
            'applications' => $this->em->getRepository(AdoptionApplication::class)->findBy([], ['submittedAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/solicitudes/{id}', name: 'admin_application_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $application = $this->em->find(AdoptionApplication::class, $id) ?? throw $this->createNotFoundException();
        return $this->render('application/admin_show.html.twig', [
            'application' => $application,
            'housingTypes' => ApplicationService::HOUSING_TYPES,
            'experienceTypes' => ApplicationService::EXPERIENCE_TYPES,
            'dailyTimes' => ApplicationService::DAILY_TIMES,
        ]);
    }
}
