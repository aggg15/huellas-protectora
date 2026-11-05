<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\AdoptionApplication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
    public function show(string $id, array $errors=[]): Response
    {
        $application=$this->em->find(AdoptionApplication::class,$id)??throw $this->createNotFoundException();
        return $this->render('review/show.html.twig',['application'=>$application,'errors'=>$errors],new Response(status:$errors?422:200));
    }
}
