<?php
declare(strict_types=1);
namespace App\Tests;
use App\Entity\{AdoptionApplication, User};

class Spec003Test extends WebTestBase
{
    private function answers(): array { return ['housing'=>'Piso ficticio con espacio seguro.','experience'=>'Sin experiencia previa.','availability'=>'Mañanas y tardes, con apoyo familiar.']; }
    private function send(string $id, array $changes=[]): void
    {
        $c=$this->client->request('GET','/animales/'.$id.'/solicitud');
        $token=$c->filter('input[name="_token"]')->attr('value');
        $this->client->request('POST','/animales/'.$id.'/solicitud',['_token'=>$token,...$this->answers(),...$changes]);
    }
    public function testCompleteQuestionnaireRetainsApplicantAndAnimal(): void
    {
        $user=$this->user();$animal=$this->animal(11,'available');$this->client->loginUser($user);$this->send($animal->id);
        self::assertResponseRedirects('/mis-solicitudes',303);$this->client->followRedirect();self::assertSelectorTextContains('main',$animal->code);
        $application=$this->refreshEm()->getRepository(AdoptionApplication::class)->findOneBy([]);
        self::assertSame($user->id,$application->applicant->id);self::assertSame($animal->id,$application->animal->id);self::assertSame('pending',$application->status);
        self::assertSame($this->answers()['housing'],$application->housing);
    }
    public function testIncompleteQuestionnaireIsNotAccepted(): void
    {
        $user=$this->user();$animal=$this->animal(11,'available');$this->client->loginUser($user);$this->send($animal->id,['experience'=>'']);
        self::assertResponseStatusCodeSame(422);self::assertSelectorTextContains('#housing',$this->answers()['housing']);
        self::assertCount(0,$this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }
    public function testTwoOpenLimitAndDuplicateApplication(): void
    {
        $user=$this->user();$a=$this->animal(11,'available');$b=$this->animal(11,'available');$c=$this->animal(11,'available');
        $this->client->loginUser($user);$this->send($a->id);self::assertResponseStatusCodeSame(303);
        $this->send($a->id);self::assertResponseStatusCodeSame(409);
        $this->send($b->id);self::assertResponseStatusCodeSame(303);$this->send($c->id);self::assertResponseStatusCodeSame(409);
        self::assertCount(2,$this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
    }
    public function testWaitingAndApprovedCountButRejectedDoesNot(): void
    {
        $user=$this->user();$a=$this->animal(11,'available');$b=$this->animal(11,'reserved');$c=$this->animal(11,'available');
        foreach ([[$a,'waiting'],[$b,'approved']] as [$animal,$state]) { $app=new AdoptionApplication($animal,$user,$this->answers(),new \DateTimeImmutable());$app->status=$state;$this->em->persist($app); }
        $this->em->flush();$this->client->loginUser($user);$this->send($c->id);self::assertResponseStatusCodeSame(409);
        $em=$this->refreshEm();$em->getConnection()->executeStatement("UPDATE AdoptionApplication SET status='rejected' WHERE status='waiting'");
        $this->send($c->id);self::assertResponseStatusCodeSame(303);
    }
    public function testAnonymousCannotSubmitAndUsersSeeOnlyTheirApplications(): void
    {
        $user=$this->user();$other=$this->user('ROLE_USER','otra@example.test');$a=$this->animal(11,'available');
        $app=new AdoptionApplication($a,$other,$this->answers(),new \DateTimeImmutable());$this->em->persist($app);$this->em->flush();
        $this->client->request('GET','/animales/'.$a->id.'/solicitud');self::assertResponseRedirects('/acceso');
        $this->client->loginUser($user);$this->client->request('GET','/mis-solicitudes?applicant='.$other->id);self::assertSelectorTextContains('main','Todavía no has enviado');
    }
    public function testRegistrationNormalizesEmailAndCannotEscalateRole(): void
    {
        $c=$this->client->request('GET','/registro');$token=$c->filter('input[name="_token"]')->attr('value');
        $data=['_token'=>$token,'name'=>'Ana Ficticia','email'=>'  ANA@EXAMPLE.TEST ','password'=>'Ficticia-Prueba-2026','confirmation'=>'Ficticia-Prueba-2026','roles'=>['ROLE_COORDINATOR']];
        $this->client->request('POST','/registro',$data);self::assertResponseRedirects('/acceso',303);
        $u=$this->refreshEm()->getRepository(User::class)->findOneBy(['email'=>'ana@example.test']);self::assertNotNull($u);self::assertSame(['ROLE_USER'],$u->getRoles());
        self::assertNotSame($data['password'],$u->password);
        $this->client->request('POST','/registro',$data);self::assertResponseStatusCodeSame(422);
    }
    public function testStaleAvailabilityAndCsrfDoNotCreateApplications(): void
    {
        $user=$this->user();$a=$this->animal(11,'available');$this->client->loginUser($user);
        $c=$this->client->request('GET','/animales/'.$a->id.'/solicitud');$token=$c->filter('input[name="_token"]')->attr('value');
        $this->refreshEm()->getConnection()->executeStatement("UPDATE animal SET status='reserved' WHERE id=?",[$a->id]);
        $this->client->request('POST','/animales/'.$a->id.'/solicitud',['_token'=>$token,...$this->answers()]);self::assertResponseStatusCodeSame(409);
        self::assertCount(0,$this->refreshEm()->getRepository(AdoptionApplication::class)->findAll());
        $this->client->request('POST','/animales/'.$a->id.'/solicitud',$this->answers());self::assertResponseStatusCodeSame(403);
    }
    public function testConcurrentSubmissionsCannotExceedTwo(): void
    {
        $user=$this->user();$animals=[$this->animal(11,'available'),$this->animal(11,'available'),$this->animal(11,'available')];
        $processes=[];
        foreach ($animals as $a) {
            $process=proc_open([PHP_BINARY,dirname(__DIR__).'/tools/application-worker.php','submit',$user->id,$a->id],[1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));
            $processes[]=[$process,$pipes];
        }
        $codes=[];
        foreach ($processes as [$process,$pipes]) { $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$codes[]=proc_close($process);self::assertContains(end($codes),[0,2],$out.$err); }
        sort($codes);self::assertSame([0,0,2],$codes);$this->em->clear();self::assertCount(2,$this->em->getRepository(AdoptionApplication::class)->findAll());
    }
    public function testCoordinatorReceivesCompleteQuestionnaireButVolunteerCannotReadIt(): void
    {
        $applicant=$this->user();$coordinator=$this->user('ROLE_COORDINATOR','coord@example.test');$volunteer=$this->user('ROLE_VOLUNTEER','vol@example.test');
        $application=new AdoptionApplication($this->animal(11,'available'),$applicant,$this->answers(),new \DateTimeImmutable());$this->em->persist($application);$this->em->flush();
        $this->client->loginUser($coordinator);$this->client->request('GET','/admin/solicitudes');self::assertResponseIsSuccessful();
        $this->client->request('GET','/admin/solicitudes/'.$application->id);self::assertResponseIsSuccessful();
        foreach ($this->answers() as $answer) { self::assertSelectorTextContains('main',$answer); }
        $this->client->loginUser($volunteer);$this->client->request('GET','/admin/solicitudes/'.$application->id);self::assertResponseStatusCodeSame(403);
    }
    public function testRealLoginAcceptsNormalizedEmailAndRoutesApplicantCorrectly(): void
    {
        $this->user();$c=$this->client->request('GET','/acceso');
        $form=$c->selectButton('Acceder')->form(['_username'=>' PERSONA@EXAMPLE.TEST ','_password'=>'Ficticia-Prueba-2026']);
        $this->client->submit($form);$error=$this->client->getRequest()->getSession()->get('_security.last_error');self::assertNull($error,$error?get_class($error).': '.$error->getMessage():'');self::assertResponseRedirects('/acceso/completado');$this->client->followRedirect();
        self::assertResponseRedirects('/mis-solicitudes');$this->client->followRedirect();self::assertResponseIsSuccessful();self::assertSelectorTextContains('h1','Mis solicitudes');
    }
}
