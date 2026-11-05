<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\AdoptionApplication;
use App\Service\{ReviewService, ValidationError};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COORDINATOR')]
class ReviewController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}
    #[Route('/admin/solicitudes', name:'review_list', methods:['GET'])]
    public function index(): Response
    {
        return $this->render('review/index.html.twig',['applications'=>$this->em->getRepository(AdoptionApplication::class)->findBy([],['submittedAt'=>'DESC'])]);
    }
    #[Route('/admin/solicitudes/{id}', name:'review_show', methods:['GET'])]
    public function show(string $id, array $errors=[], array $data=[]): Response
    {
        $application=$this->em->find(AdoptionApplication::class,$id)??throw $this->createNotFoundException();
        return $this->render('review/show.html.twig',['application'=>$application,'errors'=>$errors,'data'=>$data],new Response(status:isset($errors['conflict'])?409:($errors?422:200)));
    }
    #[Route('/admin/solicitudes/{id}/decision', name:'review_decide', methods:['POST'])]
    public function decide(string $id, Request $request, ReviewService $reviews): Response
    {
        $data=array_map(static fn($v)=>is_string($v)?$v:'',$request->request->all());
        if (!$this->isCsrfTokenValid('decision-'.$id,$data['_token']??null)) { throw $this->createAccessDeniedException('Formulario caducado.'); }
        try { $reviews->decide($id,$this->getUser(),$data); }
        catch (ValidationError $error) { return $this->show($id,$error->errors,$data); }
        $this->addFlash('success','Decisión guardada. La reserva y la disponibilidad se han actualizado.');
        return $this->redirectToRoute('review_show',['id'=>$id],303);
    }
}
