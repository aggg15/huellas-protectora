<?php
declare(strict_types=1);
namespace App\Service;
use App\Entity\{AdoptionApplication, Animal, User};
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

class ApplicationService
{
    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock, private AnimalService $animals) {}
    public function submit(User $user, string $animalId, array $data): AdoptionApplication
    {
        $answers=[];$errors=[];
        foreach (['housing'=>'vivienda','experience'=>'experiencia','availability'=>'disponibilidad'] as $key=>$label) {
            $value=is_string($data[$key]??null)?trim($data[$key]):'';
            if ($value==='' || mb_strlen($value)>4000) { $errors[$key]='Completa '.$label.' (máximo 4000 caracteres).'; }
            $answers[$key]=$value;
        }
        if ($errors) { throw new ValidationError($errors); }
        $this->animals->releaseDueAnimals();
        return $this->em->wrapInTransaction(function () use ($user,$animalId,$answers): AdoptionApplication {
            $applicant=$this->em->find(User::class,$user->id,LockMode::PESSIMISTIC_WRITE);
            $animal=$this->em->find(Animal::class,$animalId,LockMode::PESSIMISTIC_WRITE);
            if ($animal) { $this->em->refresh($animal,LockMode::PESSIMISTIC_WRITE); }
            if (!$animal || $animal->status!=='available') { throw new ValidationError(['conflict'=>'El animal ya no está disponible. Vuelve al catálogo.']); }
            $repo=$this->em->getRepository(AdoptionApplication::class);
            if ($repo->count(['applicant'=>$applicant,'status'=>AdoptionApplication::OPEN])>=2) {
                throw new ValidationError(['limit'=>'Ya tienes dos solicitudes abiertas, incluidas las que están en espera o aprobadas.']);
            }
            if ($repo->count(['applicant'=>$applicant,'animal'=>$animal,'status'=>AdoptionApplication::OPEN])) {
                throw new ValidationError(['duplicate'=>'Ya tienes una solicitud abierta para este animal.']);
            }
            $application=new AdoptionApplication($animal,$applicant,$answers,$this->clock->now()->setTimezone(new \DateTimeZone('UTC')));
            $this->em->persist($application);return $application;
        });
    }
}
