<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{Shift, User};
use App\Service\{ShiftRefused, ShiftService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Turnos de paseo y limpieza (HU-07): consulta, inscripción y baja para los voluntarios (D26, D29 a D32);
 * creación y modificación para la coordinadora (D27, D28). Bajo /admin, que ya exige ROLE_STAFF.
 */
#[IsGranted('ROLE_VOLUNTEER')]
class ShiftController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private ShiftService $shifts) {}

    #[Route('/admin/turnos', name: 'shifts', methods: ['GET'])]
    public function index(?string $refusal = null): Response
    {
        $board = $this->shifts->board($this->volunteer());
        return $this->render('shift/index.html.twig', $board + ['refusal' => $refusal], new Response(status: $refusal === null ? 200 : 409));
    }

    #[Route('/admin/turnos/nuevo', name: 'shift_new', priority: 10, methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COORDINATOR')]
    public function create(Request $request): Response
    {
        $data = ['kind' => '', 'date' => '', 'startTime' => '', 'endTime' => '', 'minVolunteers' => '', 'maxVolunteers' => ''];
        $errors = [];
        if ($request->isMethod('POST')) {
            $data = $this->data($request, 'shift-new');
            try {
                $shift = $this->shifts->create($this->volunteer(), $data);
                $this->addFlash('success', 'Turno creado: '.ShiftService::describe($shift->kind, $shift->startsAt, $shift->endsAt).'.');
                return $this->redirectToRoute('shifts', status: 303);
            } catch (ValidationError $error) {
                $errors = $error->errors;
            }
        }
        return $this->render('shift/new.html.twig', ['data' => $data, 'errors' => $errors], new Response(status: $errors ? 422 : 200));
    }

    #[Route('/admin/turnos/{id}', name: 'shift_show', methods: ['GET'])]
    public function show(string $id, array $errors = [], ?array $formData = null, int $errorStatus = 422): Response
    {
        $shift = $this->find($id);
        $signups = $this->shifts->signupsOf($shift);
        return $this->render('shift/show.html.twig', [
            'row' => $this->shifts->coverage($shift, count($signups)),
            'signups' => $signups,
            'errors' => $errors,
            'data' => $formData ?? self::formValues($shift),
        ], new Response(status: $errors ? $errorStatus : 200));
    }

    #[Route('/admin/turnos/{id}/editar', name: 'shift_edit', methods: ['POST'])]
    #[IsGranted('ROLE_COORDINATOR')]
    public function edit(string $id, Request $request): Response
    {
        $data = $this->data($request, 'shift-edit-'.$id);
        $shift = $this->find($id);
        try {
            $this->shifts->update($shift, $data);
        } catch (ValidationError $error) {
            return $this->show($id, $error->errors, $data);
        } catch (ShiftRefused $refused) {
            return $this->show($id, ['shift' => $refused->getMessage()], $data, 409);
        }
        $this->addFlash('success', 'Turno modificado.');
        return $this->redirectToRoute('shift_show', ['id' => $id], 303);
    }

    #[Route('/admin/turnos/{id}/inscripcion', name: 'shift_signup', methods: ['POST'])]
    public function signUp(string $id, Request $request): Response
    {
        $this->data($request, 'shift-signup-'.$id);
        $shift = $this->find($id);
        try {
            $this->shifts->signUp($shift, $this->volunteer());
        } catch (ShiftRefused $refused) {
            return $this->index('No te has podido apuntar al turno de '.mb_strtolower(ShiftService::describe($shift->kind, $shift->startsAt, $shift->endsAt)).'. '.$refused->getMessage());
        }
        $this->addFlash('success', 'Te has apuntado al turno de '.mb_strtolower(ShiftService::describe($shift->kind, $shift->startsAt, $shift->endsAt)).'.');
        return $this->redirectToRoute('shifts', status: 303);
    }

    #[Route('/admin/turnos/{id}/baja', name: 'shift_leave', methods: ['POST'])]
    public function leave(string $id, Request $request): Response
    {
        $this->data($request, 'shift-leave-'.$id);
        $shift = $this->find($id);
        try {
            $this->shifts->leave($shift, $this->volunteer());
        } catch (ShiftRefused $refused) {
            return $this->index('No te has podido dar de baja del turno de '.mb_strtolower(ShiftService::describe($shift->kind, $shift->startsAt, $shift->endsAt)).'. '.$refused->getMessage());
        }
        $this->addFlash('success', 'Te has dado de baja del turno de '.mb_strtolower(ShiftService::describe($shift->kind, $shift->startsAt, $shift->endsAt)).'.');
        return $this->redirectToRoute('shifts', status: 303);
    }

    private function find(string $id): Shift
    {
        return $this->em->find(Shift::class, $id) ?? throw $this->createNotFoundException();
    }

    private function volunteer(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) { throw $this->createAccessDeniedException(); }
        return $user;
    }

    /** @return array<string, string> valores actuales del turno en hora de Madrid, para el formulario */
    private static function formValues(Shift $shift): array
    {
        $zone = new \DateTimeZone(ShiftService::ZONE);
        return [
            'kind' => $shift->kind,
            'date' => $shift->startsAt->setTimezone($zone)->format('Y-m-d'),
            'startTime' => $shift->startsAt->setTimezone($zone)->format('H:i'),
            'endTime' => $shift->endsAt->setTimezone($zone)->format('H:i'),
            'minVolunteers' => (string) $shift->minVolunteers,
            'maxVolunteers' => (string) $shift->maxVolunteers,
        ];
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
