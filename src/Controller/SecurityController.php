<?php
declare(strict_types=1);
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/acceso', name: 'login')]
    public function login(AuthenticationUtils $auth): Response
    {
        return $this->render('security/login.html.twig', ['last_email'=>$auth->getLastUsername(), 'error'=>$auth->getLastAuthenticationError()]);
    }
    #[Route('/salir', name: 'logout', methods: ['POST'])]
    public function logout(): never { throw new \LogicException('Gestionado por Symfony Security.'); }
    #[Route('/acceso/completado', name: 'after_login', methods: ['GET'])]
    public function afterLogin(): Response
    {
        return $this->redirectToRoute($this->isGranted('ROLE_STAFF') ? 'admin_animals' : 'my_applications');
    }
}
