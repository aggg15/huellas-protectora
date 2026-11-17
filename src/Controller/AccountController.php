<?php
declare(strict_types=1);
namespace App\Controller;

use App\Service\{AccountService, ValidationError};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/registro', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, AccountService $accounts, Security $security): Response
    {
        if ($this->getUser()) { return $this->redirectToRoute('dashboard'); }
        $data = array_map(static fn($value) => is_string($value) ? $value : '', $request->request->all());
        $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', $data['_token'] ?? null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
            try {
                $user = $accounts->register($data);
                $this->addFlash('success', 'Cuenta creada. Ya puedes presentar tus solicitudes de adopción.');
                // El gestor de éxito del formulario de acceso resuelve la página pendiente (p. ej. la solicitud) o el panel.
                $location = $security->login($user, 'form_login', 'main')?->headers->get('Location');
                return $location !== null ? $this->redirect($location, 303) : $this->redirectToRoute('dashboard', status: 303);
            } catch (ValidationError $error) { $errors = $error->errors; }
        }
        unset($data['password'], $data['passwordConfirmation']);
        return $this->render('security/register.html.twig', ['data' => $data, 'errors' => $errors], new Response(status: $errors ? 422 : 200));
    }
}
