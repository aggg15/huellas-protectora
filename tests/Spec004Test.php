<?php
declare(strict_types=1);
namespace App\Tests;
use App\Entity\{AdoptionApplication, Animal, User};

class Spec004Test extends WebTestBase
{
    private function application(Animal $animal, User $user, bool $interview=true): AdoptionApplication
    {
        $application=new AdoptionApplication($animal,$user,['housing'=>'Vivienda ficticia','experience'=>'Cuidados previos','availability'=>'Mañanas y tardes'],new \DateTimeImmutable('-2 days'));
        if ($interview) { $application->interviewAt=new \DateTimeImmutable('-1 hour');$application->interviewNotes='Entrevista ficticia realizada.'; }
        $this->em->persist($application);$this->em->flush();return $application;
    }
    private function decide(string $id, string $action, array $data=[]): void
    {
        $c=$this->client->request('GET','/admin/solicitudes/'.$id);
        $token=$c->filter('input[name="_token"]')->first()->attr('value');$version=$c->filter('input[name="version"]')->first()->attr('value');
        $this->client->request('POST','/admin/solicitudes/'.$id.'/decision',['_token'=>$token,'version'=>$version,'action'=>$action,...$data]);
    }
    public function testCoordinatorRecordsInterviewAndApplicantSeesAppointment(): void
    {
        $user=$this->user();$coord=$this->user('ROLE_COORDINATOR','coord@example.test');$a=$this->animal(11,'available');$app=$this->application($a,$user,false);
        $this->client->loginUser($coord);$at=(new \DateTimeImmutable('+1 day',new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i');
        $this->decide($app->id,'interview',['interviewAt'=>$at,'interviewNotes'=>'En el refugio, sala de entrevistas.']);self::assertResponseStatusCodeSame(303);
        $this->client->loginUser($user);$this->client->request('GET','/mis-solicitudes');self::assertSelectorTextContains('main','Entrevista:');
        $em=$this->refreshEm();$em->clear();self::assertSame('En el refugio, sala de entrevistas.',$em->find(AdoptionApplication::class,$app->id)->interviewNotes);
    }
    public function testRejectionRequiresReasonAndPersistsIt(): void
    {
        $user=$this->user();$coord=$this->user('ROLE_COORDINATOR','coord@example.test');$app=$this->application($this->animal(11,'available'),$user);
        $this->client->loginUser($coord);$this->decide($app->id,'reject',['reason'=>'   ']);self::assertResponseStatusCodeSame(422);
        self::assertSame('pending',$this->refreshEm()->find(AdoptionApplication::class,$app->id)->status);
        $this->decide($app->id,'reject',['reason'=>'Disponibilidad insuficiente para este animal.']);self::assertResponseStatusCodeSame(303);
        $em=$this->refreshEm();$em->clear();$stored=$em->find(AdoptionApplication::class,$app->id);
        self::assertSame('rejected',$stored->status);self::assertSame('Disponibilidad insuficiente para este animal.',$stored->reason);self::assertSame($coord->id,$stored->decidedBy->id);
    }
    public function testApprovalAtomicallyReservesAndWaitsOtherOpenRequests(): void
    {
        $u=$this->user();$other=$this->user('ROLE_USER','otra@example.test');$third=$this->user('ROLE_USER','tercera@example.test');$coord=$this->user('ROLE_COORDINATOR','coord@example.test');
        $a=$this->animal(11,'available');$app=$this->application($a,$u);$wait=$this->application($a,$other);$rejected=$this->application($a,$third);$rejected->status='rejected';$rejected->reason='Motivo anterior.';$this->em->flush();
        $this->client->loginUser($coord);$this->decide($app->id,'approve');self::assertResponseStatusCodeSame(303);
        $em=$this->refreshEm();$em->clear();self::assertSame('reserved',$em->find(Animal::class,$a->id)->status);
        self::assertSame('approved',$em->find(AdoptionApplication::class,$app->id)->status);self::assertSame('waiting',$em->find(AdoptionApplication::class,$wait->id)->status);self::assertSame('rejected',$em->find(AdoptionApplication::class,$rejected->id)->status);
        $this->client->request('GET','/');self::assertStringNotContainsString($a->code,$this->client->getResponse()->getContent());
        $this->client->request('GET','/animales/'.$a->id);self::assertResponseStatusCodeSame(404);
    }
    public function testApprovalWithoutPastInterviewDoesNotPartiallyReserve(): void
    {
        $u=$this->user();$coord=$this->user('ROLE_COORDINATOR','coord@example.test');$a=$this->animal(11,'available');$app=$this->application($a,$u,false);
        $this->client->loginUser($coord);$this->decide($app->id,'approve');self::assertResponseStatusCodeSame(422);
        $em=$this->refreshEm();$stored=$em->find(AdoptionApplication::class,$app->id);self::assertSame('pending',$stored->status);self::assertSame('available',$stored->animal->status);
        $stored->interviewAt=new \DateTimeImmutable('+1 day');$stored->interviewNotes='Futura';$em->flush();
        $this->decide($app->id,'approve');self::assertResponseStatusCodeSame(422);
    }
    public function testStaleVersionDoesNotOverwriteDecision(): void
    {
        $u=$this->user();$coord=$this->user('ROLE_COORDINATOR','coord@example.test');$app=$this->application($this->animal(11,'available'),$u);
        $this->client->loginUser($coord);$c=$this->client->request('GET','/admin/solicitudes/'.$app->id);$token=$c->filter('input[name="_token"]')->first()->attr('value');
        $version=$c->filter('input[name="version"]')->first()->attr('value');$this->decide($app->id,'approve');self::assertResponseStatusCodeSame(303);
        $this->client->request('POST','/admin/solicitudes/'.$app->id.'/decision',['_token'=>$token,'version'=>$version,'action'=>'reject','reason'=>'Ventana antigua']);self::assertResponseStatusCodeSame(409);
        self::assertSame('approved',$this->refreshEm()->find(AdoptionApplication::class,$app->id)->status);
    }
    public function testReleasingFailedReservationReactivatesWaitingApplications(): void
    {
        $u=$this->user();$other=$this->user('ROLE_USER','otra@example.test');$coord=$this->user('ROLE_COORDINATOR','coord@example.test');$a=$this->animal(11,'available');$app=$this->application($a,$u);$wait=$this->application($a,$other);
        $this->client->loginUser($coord);$this->decide($app->id,'approve');self::assertResponseStatusCodeSame(303);
        $this->decide($app->id,'release',['reason'=>'La familia no puede continuar.']);self::assertResponseStatusCodeSame(303);
        $em=$this->refreshEm();$em->clear();self::assertSame('available',$em->find(Animal::class,$a->id)->status);self::assertSame('cancelled',$em->find(AdoptionApplication::class,$app->id)->status);self::assertSame('pending',$em->find(AdoptionApplication::class,$wait->id)->status);
    }
    public function testOnlyCoordinatorWithCsrfCanDecide(): void
    {
        $u=$this->user();$coord=$this->user('ROLE_COORDINATOR','coord@example.test');$vol=$this->user('ROLE_VOLUNTEER','vol@example.test');$app=$this->application($this->animal(11,'available'),$u);
        $this->client->loginUser($vol);$this->client->request('POST','/admin/solicitudes/'.$app->id.'/decision',['action'=>'approve','version'=>'1']);self::assertResponseStatusCodeSame(403);
        $this->client->loginUser($coord);$this->client->request('POST','/admin/solicitudes/'.$app->id.'/decision',['action'=>'approve','version'=>'1']);self::assertResponseStatusCodeSame(403);
    }
    public function testTwoConcurrentApprovalsHaveOneWinnerAndOneWaitingCandidate(): void
    {
        $u=$this->user();$other=$this->user('ROLE_USER','otra@example.test');$coord=$this->user('ROLE_COORDINATOR','coord@example.test');$a=$this->animal(11,'available');$apps=[$this->application($a,$u),$this->application($a,$other)];$processes=[];
        foreach ($apps as $app) { $p=proc_open([PHP_BINARY,dirname(__DIR__).'/tools/application-worker.php','approve',$coord->id,$app->id],[1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));$processes[]=[$p,$pipes]; }
        $codes=[];foreach ($processes as [$p,$pipes]) { $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);self::assertContains($code,[0,2],$out.$err);$codes[]=$code; }
        sort($codes);self::assertSame([0,2],$codes);$this->em->clear();$repo=$this->em->getRepository(AdoptionApplication::class);
        self::assertSame(1,$repo->count(['status'=>'approved']));self::assertSame(1,$repo->count(['status'=>'waiting']));self::assertSame('reserved',$this->em->find(Animal::class,$a->id)->status);
    }
}
