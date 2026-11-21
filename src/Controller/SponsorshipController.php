<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\{Animal, Sponsorship, User};
use App\Service\{AnimalService, SponsorshipRefused, SponsorshipService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Apadrinamiento desde la ficha pública y página «Mis apadrinamientos» del padrino (HU-08, D34 a D39).
 * Solo cuentas públicas: el personal y las familias de acogida no apadrinan con su cuenta de trabajo (D34).
 */
#[IsGranted('ROLE_USER')]
class SponsorshipController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private SponsorshipService $sponsorships, private AnimalService $animals) {}

    #[Route('/animales/{id}/apadrinar', name: 'sponsorship_new', methods: ['GET', 'POST'])]
    public function create(string $id, Request $request): Response
    {
        $sponsor = $this->sponsor();
        $data = ['amount' => ''];
        $errors = [];
        $status = 200;
        if ($request->isMethod('POST')) {
            $data = $this->data($request, 'sponsorship-new-'.$id) + ['amount' => ''];
            // Promociona la cuarentena vencida (D01); un animal que ha dejado de estar disponible responde 409.
            $animal = $this->animals->getPublicAnimal($id) ?? $this->em->find(Animal::class, $id) ?? throw $this->createNotFoundException();
            try {
                $sponsorship = $this->sponsorships->sponsor($animal, $sponsor, $data);
                $this->addFlash('success', sprintf('Ya apadrinas a %s con %s al mes. ¡Gracias!', $animal->code, $sponsorship->amountLabel()));
                return $this->redirectToRoute('sponsorship_mine', status: 303);
            } catch (ValidationError $error) {
                [$errors, $status] = [$error->errors, 422];
            } catch (SponsorshipRefused $refused) {
                [$errors, $status] = [['sponsorship' => $refused->getMessage()], 409];
            }
        } else {
            // Como la ficha pública: solo animales disponibles (también promociona la cuarentena vencida).
            $animal = $this->animals->getPublicAnimal($id) ?? throw $this->createNotFoundException('El animal no está disponible.');
        }
        return $this->render('sponsorship/new.html.twig', ['animal' => $animal, 'data' => $data, 'errors' => $errors], new Response(status: $status));
    }

    #[Route('/mis-apadrinamientos', name: 'sponsorship_mine', methods: ['GET'])]
    public function mine(array $errors = [], ?string $formId = null, int $errorStatus = 409): Response
    {
        $sponsor = $this->sponsor();
        $this->animals->releaseDueAnimals(); // la cuarentena vencida (D01) hace disponibles a los destinos posibles
        return $this->render('sponsorship/mine.html.twig', [
            'rows' => $this->sponsorships->mine($sponsor),
            'targets' => $this->sponsorships->availableFor($sponsor),
            'errors' => $errors,
            'formId' => $formId,
        ], new Response(status: $errors ? $errorStatus : 200));
    }

    #[Route('/mis-apadrinamientos/{id}/terminar', name: 'sponsorship_finish', methods: ['POST'])]
    public function finish(string $id, Request $request): Response
    {
        $sponsorship = $this->own($id);
        $this->data($request, 'sponsorship-finish-'.$id);
        try {
            $this->sponsorships->finish($sponsorship, $this->sponsor());
        } catch (SponsorshipRefused $refused) {
            return $this->mine(['sponsorship' => $refused->getMessage()], $id);
        }
        $this->addFlash('success', sprintf('Has terminado tu apadrinamiento de %s. Gracias por tu ayuda.', $sponsorship->animal->code));
        return $this->redirectToRoute('sponsorship_mine', status: 303);
    }

    #[Route('/mis-apadrinamientos/{id}/cambiar', name: 'sponsorship_move', methods: ['POST'])]
    public function move(string $id, Request $request): Response
    {
        $sponsorship = $this->own($id);
        $data = $this->data($request, 'sponsorship-move-'.$id);
        $this->animals->releaseDueAnimals(); // un animal con la cuarentena recién cumplida ya es un destino válido
        $target = ($data['animal'] ?? '') === '' ? null : $this->em->find(Animal::class, $data['animal']);
        if ($target === null) {
            return $this->mine(['animal' => 'Elige uno de los animales disponibles para seguir apadrinando.'], $id, 422);
        }
        try {
            $next = $this->sponsorships->moveTo($sponsorship, $target, $this->sponsor());
        } catch (SponsorshipRefused $refused) {
            return $this->mine(['animal' => $refused->getMessage()], $id);
        }
        $this->addFlash('success', sprintf('Ahora apadrinas a %s con %s al mes.', $next->animal->code, $next->amountLabel()));
        return $this->redirectToRoute('sponsorship_mine', status: 303);
    }

    /** Cuenta pública que apadrina (D34); el personal y las familias de acogida reciben 403. */
    private function sponsor(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User || $this->isGranted('ROLE_STAFF') || $this->isGranted('ROLE_FOSTER')) {
            throw $this->createAccessDeniedException('Solo las cuentas de padrinos apadrinan animales.');
        }
        return $user;
    }

    /** Apadrinamiento de la cuenta; uno ajeno no se revela (404). */
    private function own(string $id): Sponsorship
    {
        $sponsor = $this->sponsor();
        $sponsorship = $this->em->find(Sponsorship::class, $id);
        if ($sponsorship === null || $sponsorship->sponsor->id !== $sponsor->id) { throw $this->createNotFoundException(); }
        return $sponsorship;
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
