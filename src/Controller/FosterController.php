<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{FosterAvailability, FosterHome, FosterPlacement, User};
use App\Service\{FosterRefused, FosterService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Página de la familia de acogida: condiciones, periodos, animales acogidos y novedades (HU-06). */
#[IsGranted('ROLE_FOSTER')]
class FosterController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private FosterService $fosters) {}

    #[Route('/acogida', name: 'foster_home', methods: ['GET'])]
    public function home(array $errors = [], string $form = '', array $formData = [], int $errorStatus = 422): Response
    {
        $home = $this->fosters->homeOf($this->family());
        $today = $this->fosters->today();
        $periods = $home?->periods() ?? [];
        // Vigentes primero y terminados al final (analyze A2).
        usort($periods, static fn(FosterAvailability $a, FosterAvailability $b) => [$a->isOver($today), $a->startsOn] <=> [$b->isOver($today), $b->startsOn]);
        return $this->render('foster/home.html.twig', [
            'home' => $home,
            'periods' => $periods,
            'placements' => $home ? $this->fosters->activePlacementsOf($home) : [],
            'today' => $today,
            'errors' => $errors,
            'form' => $form,
            'formData' => $formData,
        ], new Response(status: $errors ? $errorStatus : 200));
    }

    #[Route('/acogida/capacidad', name: 'foster_capacity', methods: ['POST'])]
    public function capacity(Request $request): Response
    {
        $data = $this->data($request, 'foster-capacity');
        try {
            $this->fosters->saveCapacity($this->family(), $data);
        } catch (ValidationError $error) {
            return $this->home($error->errors, 'capacity', $data);
        } catch (FosterRefused $refused) {
            return $this->home(['capacity' => $refused->getMessage()], 'capacity', $data, 409);
        }
        $this->addFlash('success', 'Capacidad guardada.');
        return $this->redirectToRoute('foster_home', status: 303);
    }

    #[Route('/acogida/periodos', name: 'foster_period_add', methods: ['POST'])]
    public function addPeriod(Request $request): Response
    {
        $data = $this->data($request, 'foster-period');
        $home = $this->fosters->homeOf($this->family());
        if ($home === null) {
            return $this->home(['startsOn' => 'Indica primero cuántos animales puedes atender.'], 'period', $data, 409);
        }
        try {
            $this->fosters->addPeriod($home, $data);
        } catch (ValidationError $error) {
            return $this->home($error->errors, 'period', $data);
        }
        $this->addFlash('success', 'Periodo de disponibilidad añadido.');
        return $this->redirectToRoute('foster_home', status: 303);
    }

    #[Route('/acogida/periodos/{id}/eliminar', name: 'foster_period_remove', methods: ['POST'])]
    public function removePeriod(string $id, Request $request): Response
    {
        // Primero la propiedad (404, sin revelar periodos ajenos) y después el token CSRF.
        $home = $this->ownHome();
        $period = $this->em->find(FosterAvailability::class, $id);
        if ($period === null || $period->home->id !== $home->id) { throw $this->createNotFoundException(); }
        $this->data($request, 'foster-period-remove-'.$id);
        try {
            $this->fosters->removePeriod($home, $period);
        } catch (FosterRefused $refused) {
            return $this->home(['periods' => $refused->getMessage()], 'periods', [], 409);
        }
        $this->addFlash('success', 'Periodo de disponibilidad retirado.');
        return $this->redirectToRoute('foster_home', status: 303);
    }

    #[Route('/acogida/acogidas/{id}/novedades', name: 'foster_note_add', methods: ['POST'])]
    public function addNote(string $id, Request $request): Response
    {
        $home = $this->ownHome();
        $placement = $this->em->find(FosterPlacement::class, $id);
        if ($placement === null || $placement->home->id !== $home->id) { throw $this->createNotFoundException(); }
        $data = $this->data($request, 'foster-note-'.$id);
        try {
            $this->fosters->addNote($placement, $this->family(), $data);
        } catch (ValidationError $error) {
            return $this->home($error->errors, 'note-'.$id, $data);
        } catch (FosterRefused $refused) {
            return $this->home(['body' => $refused->getMessage()], 'note-'.$id, $data, 409);
        }
        $this->addFlash('success', 'Novedad anotada en la ficha de '.$placement->animal->code.'.');
        return $this->redirectToRoute('foster_home', status: 303);
    }

    private function family(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) { throw $this->createAccessDeniedException(); }
        return $user;
    }

    /** La casa de la cuenta; sin casa no hay periodos ni acogidas propios (404). */
    private function ownHome(): FosterHome
    {
        return $this->fosters->homeOf($this->family()) ?? throw $this->createNotFoundException();
    }

    /** @return array<string, string> campos de texto del formulario, tras comprobar el token CSRF */
    private function data(Request $request, string $tokenId): array
    {
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $request->request->all());
        if (!$this->isCsrfTokenValid($tokenId, $data['_token'] ?? null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
        unset($data['_token']);
        return $data;
    }
}
