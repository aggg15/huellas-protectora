<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\{AdoptionApplication, Animal};
use App\Service\{AnimalService, ApplicationService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApplicationController extends AbstractController
{
    #[Route('/mis-solicitudes', name:'my_applications', methods:['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $applications=$em->getRepository(AdoptionApplication::class)->findBy(['applicant'=>$this->getUser()],['submittedAt'=>'DESC']);
        return $this->render('application/mine.html.twig',['applications'=>$applications]);
    }
    #[Route('/animales/{id}/solicitud', name:'application_new', methods:['GET','POST'])]
    public function create(string $id, Request $request, EntityManagerInterface $em, AnimalService $animals, ApplicationService $applications): Response
    {
        $animals->releaseDueAnimals();$animal=$em->find(Animal::class,$id)??throw $this->createNotFoundException();
        if ($animal->status!=='available' && !$request->isMethod('POST')) { throw $this->createNotFoundException(); }
        $data=array_map(static fn($v)=>is_string($v)?$v:'',$request->request->all());$errors=[];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('application-'.$id,$data['_token']??null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
            try { $applications->submit($this->getUser(),$id,$data);$this->addFlash('success','Solicitud enviada. La coordinadora revisará tu cuestionario.');return $this->redirectToRoute('my_applications',status:303); }
            catch (ValidationError $error) { $errors=$error->errors; }
        }
        return $this->render('application/new.html.twig',['animal'=>$animal,'data'=>$data,'errors'=>$errors],new Response(status:isset($errors['conflict'])||isset($errors['limit'])||isset($errors['duplicate'])?409:($errors?422:200)));
    }
}
