<?php
declare(strict_types=1);
namespace App\Controller;
use App\Service\{AccountService, ValidationError};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/registro', name:'register', methods:['GET','POST'])]
    public function register(Request $request, AccountService $accounts): Response
    {
        $data=array_map(static fn($v)=>is_string($v)?$v:'',$request->request->all());$errors=[];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register',$data['_token']??null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
            try { $accounts->register($data);$this->addFlash('success','Cuenta creada. Ya puedes acceder y presentar tu solicitud.');return $this->redirectToRoute('login',status:303); }
            catch (ValidationError $error) { $errors=$error->errors; }
        }
        return $this->render('security/register.html.twig',['data'=>$data,'errors'=>$errors],new Response(status:$errors?422:200));
    }
}
