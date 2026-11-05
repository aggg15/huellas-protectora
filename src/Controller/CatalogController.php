<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\{Animal, AnimalPhoto};
use App\Service\AnimalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

class CatalogController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private AnimalService $animals) {}
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->animals->releaseDueAnimals();
        $filters=[];$errors=[];
        foreach (['species'=>['dog','cat'],'size'=>['small','medium','large'],'age'=>['young','adult','senior']] as $key=>$allowed) {
            $value=$request->query->all()[$key] ?? '';
            if (!is_string($value) || ($value!=='' && !in_array($value,$allowed,true))) { $errors[$key]='El filtro de '.['species'=>'especie','size'=>'tamaño','age'=>'edad'][$key].' no es válido.'; $value=''; }
            $filters[$key]=$value;
        }
        $qb=$this->em->createQueryBuilder()->select('a')->from(Animal::class,'a')->where('a.status = :status')->setParameter('status','available')->orderBy('a.receivedAt','ASC');
        foreach (['species','size'] as $key) { if ($filters[$key]!=='') { $qb->andWhere('a.'.$key.' = :'.$key)->setParameter($key,$filters[$key]); } }
        if ($filters['age']==='young') { $qb->andWhere('a.ageMonths < 12'); }
        if ($filters['age']==='adult') { $qb->andWhere('a.ageMonths >= 12 AND a.ageMonths < 84'); }
        if ($filters['age']==='senior') { $qb->andWhere('a.ageMonths >= 84'); }
        $items=[];
        foreach ($errors ? [] : $qb->getQuery()->getResult() as $animal) {
            $items[]=['animal'=>$animal,'photo'=>$this->em->getRepository(AnimalPhoto::class)->findOneBy(['animal'=>$animal])];
        }
        return $this->render('catalog/index.html.twig',['items'=>$items,'filters'=>$filters,'errors'=>$errors],new Response(status:$errors?422:200));
    }
    #[Route('/animales/{id}', name: 'public_animal', methods: ['GET'])]
    public function show(string $id): Response
    {
        $this->animals->releaseDueAnimals();
        $animal=$this->em->find(Animal::class,$id);
        if (!$animal || $animal->status!=='available') { throw $this->createNotFoundException(); }
        return $this->render('catalog/show.html.twig',['animal'=>$animal,'photos'=>$this->em->getRepository(AnimalPhoto::class)->findBy(['animal'=>$animal])]);
    }
}
