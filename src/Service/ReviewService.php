<?php
declare(strict_types=1);
namespace App\Service;
use App\Entity\{AdoptionApplication, Animal, User};
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

class ReviewService
{
    public function __construct(private EntityManagerInterface $em, private ClockInterface $clock) {}
    public function decide(string $id, User $actor, array $data): void
    {
        if (!in_array('ROLE_COORDINATOR',$actor->getRoles(),true)) { throw new ValidationError(['permission'=>'Solo la coordinadora puede decidir.']); }
        $data=array_map(static fn($v)=>is_string($v)?$v:'',$data);
        $version=filter_var($data['version']??null,FILTER_VALIDATE_INT);
        if ($version===false || $version<1) { throw new ValidationError(['conflict'=>'La versión de la solicitud no es válida. Recarga la ficha.']); }
        $action=$data['action']??'';
        if (!in_array($action,['interview','approve','reject','release'],true)) { throw new ValidationError(['action'=>'Acción no válida.']); }
        $this->em->getConnection()->transactional(function () use ($id,$actor,$data,$version,$action): void {
            $application=$this->em->find(AdoptionApplication::class,$id);
            if (!$application) { throw new ValidationError(['conflict'=>'No existe la solicitud.']); }
            $animal=$this->em->find(Animal::class,$application->animal->id,LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($animal,LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($application,LockMode::PESSIMISTIC_WRITE);
            if ($application->version!==$version) { throw new ValidationError(['conflict'=>'La solicitud cambió en otra ventana. Recarga antes de decidir.']); }
            $now=$this->clock->now()->setTimezone(new \DateTimeZone('UTC'));
            if ($action==='interview') {
                if (!in_array($application->status,['pending','waiting'],true)) { throw new ValidationError(['conflict'=>'La solicitud ya no admite entrevistas.']); }
                $at=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$data['interviewAt']??'',new \DateTimeZone('Europe/Madrid'));
                $notes=trim($data['interviewNotes']??'');
                $submittedMinute=$application->submittedAt->setTime((int)$application->submittedAt->format('H'),(int)$application->submittedAt->format('i'));
                if (!$at || $at->format('Y-m-d\TH:i')!==($data['interviewAt']??'') || $at<$submittedMinute) { throw new ValidationError(['interviewAt'=>'La entrevista debe tener una fecha válida desde la presentación de la solicitud.']); }
                if ($notes==='' || mb_strlen($notes)>4000) { throw new ValidationError(['interviewNotes'=>'Indica lugar, canal u observaciones de la entrevista (máximo 4000 caracteres).']); }
                $application->interviewAt=$at->setTimezone(new \DateTimeZone('UTC'));$application->interviewNotes=$notes;
            } elseif ($action==='approve') {
                if ($animal->status!=='available' || $application->status!=='pending') { throw new ValidationError(['conflict'=>'El animal o la candidatura ya no están disponibles para aprobar.']); }
                if (!$application->interviewAt || $application->interviewAt>$now) { throw new ValidationError(['interviewAt'=>'Registra primero la entrevista y espera a su realización antes de aprobar.']); }
                $others=$this->em->getRepository(AdoptionApplication::class)->findBy(['animal'=>$animal,'status'=>['pending','waiting']]);
                foreach ($others as $other) { if ($other->id!==$application->id) { $other->status='waiting'; } }
                $application->status='approved';$animal->status='reserved';$application->decidedBy=$actor;$application->decidedAt=$now;
            } elseif ($action==='reject') {
                $reason=$this->reason($data);
                if (!in_array($application->status,['pending','waiting'],true)) { throw new ValidationError(['conflict'=>'Esta solicitud no puede rechazarse. Una reserva aprobada debe liberarse.']); }
                $application->status='rejected';$application->reason=$reason;$application->decidedBy=$actor;$application->decidedAt=$now;
            } else {
                $reason=$this->reason($data);
                if ($application->status!=='approved' || $animal->status!=='reserved') { throw new ValidationError(['conflict'=>'No existe una reserva vigente para liberar.']); }
                $others=$this->em->getRepository(AdoptionApplication::class)->findBy(['animal'=>$animal,'status'=>'waiting']);
                foreach ($others as $other) { $other->status='pending'; }
                $application->status='cancelled';$application->reason=$reason;$application->decidedBy=$actor;$application->decidedAt=$now;$animal->status='available';
            }
            $this->em->flush();
        });
    }
    private function reason(array $data): string
    {
        $reason=trim($data['reason']??'');
        if ($reason==='' || mb_strlen($reason)>4000) { throw new ValidationError(['reason'=>'Escribe el motivo de la decisión (máximo 4000 caracteres).']); }
        return $reason;
    }
}
