<?php
declare(strict_types=1);
namespace App\Tests;
use App\Entity\{Animal, AnimalPhoto, VeterinaryReview};
use App\Service\AnimalService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Spec001Test extends WebTestBase
{
    public function testVolunteerCreatesIdentityAndPhotoInQuarantine(): void
    {
        $user=$this->user('ROLE_VOLUNTEER');$this->client->loginUser($user);
        $crawler=$this->client->request('GET','/admin/animales/nuevo'); self::assertResponseIsSuccessful();
        $token=$crawler->filter('input[name="_token"]')->attr('value');
        $this->client->request('POST','/admin/animales/nuevo',[
            '_token'=>$token,'species'=>'dog','ageMonths'=>'8','size'=>'small','origin'=>'Calle de prueba',
            'receivedAt'=>(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i'),
        ],['photos'=>[$this->photo()]]);
        self::assertResponseRedirects(null,303);$this->client->followRedirect();
        self::assertSelectorTextContains('h1','HUE-');self::assertSelectorTextContains('main','En cuarentena');
        $em=$this->refreshEm();$animal=$em->getRepository(Animal::class)->findOneBy([]);
        self::assertSame('quarantine',$animal->status);self::assertCount(1,$em->getRepository(AnimalPhoto::class)->findAll());
        $id=$animal->id;$em->clear();self::assertSame('Calle de prueba',$em->find(Animal::class,$id)->origin);
    }
    public function testAnonymousCannotSeeQuarantineOrPhotos(): void
    {
        $animal=$this->animal();
        $this->client->request('GET','/admin/animales/'.$animal->id);self::assertResponseRedirects('/acceso');
        $this->client->request('GET','/animales/'.$animal->id);self::assertResponseStatusCodeSame(404);
    }
    // C1-05: con un fichero real en el almacén, el 404 solo puede deberse a la comprobación de estado.
    public function testPrivatePhotosAreOnlyServedForAvailableAnimalsOrStaff(): void
    {
        $quarantine=$this->animal(1);$reserved=$this->animal(5,'reserved');$available=$this->animal(0,'available');
        $pq=$this->storedPhoto($quarantine);$pr=$this->storedPhoto($reserved);$pa=$this->storedPhoto($available);
        foreach ([$pq,$pr] as $photo) {
            $this->client->request('GET','/fotos/'.$photo->id);self::assertResponseStatusCodeSame(404,'Anónimo no ve fotos no disponibles.');
        }
        $this->client->request('GET','/fotos/'.$pa->id);self::assertResponseIsSuccessful();
        self::assertSame('image/png',$this->client->getResponse()->headers->get('Content-Type'));
        $this->client->loginUser($this->user('ROLE_USER'));
        foreach ([$pq,$pr] as $photo) {
            $this->client->request('GET','/fotos/'.$photo->id);self::assertResponseStatusCodeSame(404,'ROLE_USER no ve fotos no disponibles.');
        }
        $this->client->request('GET','/fotos/'.$pa->id);self::assertResponseIsSuccessful();
        $this->client->loginUser($this->user('ROLE_VOLUNTEER','voluntario@example.test'));
        foreach ([$pq,$pr] as $photo) {
            $this->client->request('GET','/fotos/'.$photo->id);self::assertResponseIsSuccessful('El personal sí ve las fotos.');
        }
    }
    // C1-20a: límite de 240 h en la consulta SQL de liberación (márgenes de 30 s para no depender de la lentitud de la prueba).
    public function testReleaseDueAnimalsSqlBoundaryAt240Hours(): void
    {
        $utc=new \DateTimeZone('UTC');$now=new \DateTimeImmutable('now',$utc);
        $before=$this->animal(0);$before->receivedAt=$now->modify('-240 hours')->modify('+30 seconds');
        $after=$this->animal(0);$after->receivedAt=$now->modify('-240 hours')->modify('-30 seconds');
        $this->em->flush();
        self::assertSame(1,self::getContainer()->get(AnimalService::class)->releaseDueAnimals());
        $this->em->clear();
        self::assertSame('quarantine',$this->em->find(Animal::class,$before->id)->status);
        self::assertSame('available',$this->em->find(Animal::class,$after->id)->status);
    }
    // C1-14: las fechas se guardan en UTC y se muestran en Europe/Madrid.
    public function testPhpTimezoneIsUtcAndStoredDatesAreUtc(): void
    {
        self::assertSame('UTC',date_default_timezone_get());
        $animal=$this->animal(0,'available');$animal->receivedAt=new \DateTimeImmutable('2026-07-01 22:30:00',new \DateTimeZone('UTC'));$this->em->flush();
        $this->client->request('GET','/animales/'.$animal->id);
        self::assertSelectorTextContains('main','02/07/2026');
    }
    public function testBoundaryIsExactlyTenPeriodsOf24Hours(): void
    {
        $received=new \DateTimeImmutable('2026-10-23T12:00:00+00:00');
        $animal=new Animal('dog',12,'small','Prueba',$received);
        self::assertFalse($animal->releaseIfDue($received->modify('+239 hours 59 minutes 59 seconds')));
        self::assertTrue($animal->releaseIfDue($received->modify('+240 hours')));
        self::assertSame('available',$animal->status);
        $animal->status='reserved';self::assertFalse($animal->releaseIfDue($received->modify('+300 hours')));
    }
    public function testExpiredQuarantineReleasesAutomaticallyWithoutChangingReservations(): void
    {
        $a=$this->animal(11);$r=$this->animal(12,'reserved');
        self::getContainer()->get(AnimalService::class)->releaseDueAnimals();$this->em->clear();
        self::assertSame('available',$this->em->find(Animal::class,$a->id)->status);
        self::assertSame('reserved',$this->em->find(Animal::class,$r->id)->status);
    }
    public function testVeterinaryReviewAndRoleValidation(): void
    {
        $vet=$this->user('ROLE_VET');$animal=$this->animal(1);$this->client->loginUser($vet);
        $crawler=$this->client->request('GET','/admin/animales/'.$animal->id);
        $madrid=new \DateTimeZone('Europe/Madrid');
        $at=(new \DateTimeImmutable('-1 hour',$madrid))->format('Y-m-d\TH:i');
        $form=$crawler->selectButton('Guardar revisión')->form(['reviewedAt'=>$at,'notes'=>'Revisión ficticia sin incidencias.']);
        $token=$form['_token']->getValue();
        $this->client->submit($form);self::assertResponseRedirects(null,303);
        $reviews=$this->refreshEm()->getRepository(VeterinaryReview::class)->findAll();
        self::assertCount(1,$reviews);
        self::assertSame($vet->id,$reviews[0]->veterinarian->id);
        self::assertSame('Revisión ficticia sin incidencias.',$reviews[0]->notes);
        self::assertSame($at,$reviews[0]->reviewedAt->setTimezone($madrid)->format('Y-m-d\TH:i'));
        // Ramas de error: ninguna debe escribir una revisión nueva.
        $animal=$this->em->find(Animal::class,$animal->id);
        $valid=['_token'=>$token,'reviewedAt'=>$at,'notes'=>'Notas válidas.'];
        $received=$animal->receivedAt->setTimezone($madrid);
        $cases=[
            'fecha futura'=>[['reviewedAt'=>(new \DateTimeImmutable('+2 days',$madrid))->format('Y-m-d\TH:i')],'reviewedAt',422],
            'anterior a la recepción'=>[['reviewedAt'=>$received->modify('-1 day')->format('Y-m-d\TH:i')],'reviewedAt',422],
            'notas vacías'=>[['notes'=>'   '],'notes',422],
            'notas de 5001 caracteres'=>[['notes'=>str_repeat('a',5001)],'notes',422],
        ];
        foreach ($cases as $label=>[$override,$field,$status]) {
            $this->client->request('POST','/admin/animales/'.$animal->id.'/revision',$override+$valid);
            self::assertResponseStatusCodeSame($status,$label);self::assertSelectorExists('#error-'.$field,$label);
            self::assertCount(1,$this->refreshEm()->getRepository(VeterinaryReview::class)->findAll(),$label);
        }
        $this->client->request('POST','/admin/animales/'.$animal->id.'/revision',['_token'=>str_repeat('x',32)]+$valid);
        self::assertResponseStatusCodeSame(403,'CSRF inválido');
        self::assertCount(1,$this->refreshEm()->getRepository(VeterinaryReview::class)->findAll());
        // Límite exacto de 5000 caracteres: válido.
        $this->client->request('POST','/admin/animales/'.$animal->id.'/revision',['notes'=>str_repeat('a',5000)]+$valid);
        self::assertResponseRedirects(null,303);
        self::assertCount(2,$this->refreshEm()->getRepository(VeterinaryReview::class)->findAll());
        $volunteer=$this->user('ROLE_VOLUNTEER','voluntario@example.test');$this->client->loginUser($volunteer);
        $this->client->request('POST','/admin/animales/'.$animal->id.'/revision',['notes'=>'No permitido']);self::assertResponseStatusCodeSame(403);
    }
    public function testVetCannotCreateAnimals(): void
    {
        $this->client->loginUser($this->user('ROLE_VET'));
        $this->client->request('GET','/admin/animales/nuevo');self::assertResponseStatusCodeSame(403);
        $this->client->request('POST','/admin/animales/nuevo',['species'=>'dog']);self::assertResponseStatusCodeSame(403);
        self::assertCount(0,$this->refreshEm()->getRepository(Animal::class)->findAll());
    }
    public function testInvalidAnimalAndSpoofedPhotoLeaveNoPartialWrites(): void
    {
        $this->client->loginUser($this->user('ROLE_VOLUNTEER'));
        $crawler=$this->client->request('GET','/admin/animales/nuevo');$token=$crawler->filter('input[name="_token"]')->attr('value');
        $data=['_token'=>$token,'species'=>'bird','ageMonths'=>'-1','size'=>'small','origin'=>'Conservar este texto','receivedAt'=>'2099-01-01T10:00'];
        $this->client->request('POST','/admin/animales/nuevo',$data,['photos'=>[$this->photo()]]);
        self::assertResponseStatusCodeSame(422);self::assertSelectorTextContains('#origin','Conservar este texto');
        self::assertCount(0,$this->refreshEm()->getRepository(Animal::class)->findAll());
        $data['species']='cat';$data['ageMonths']='2';$data['receivedAt']='2026-01-01T10:00';
        $path=tempnam(sys_get_temp_dir(),'huellas-fake');file_put_contents($path,'<?php echo "not an image";');
        $this->client->request('POST','/admin/animales/nuevo',$data,['photos'=>[new UploadedFile($path,'falso.jpg','image/jpeg',null,true)]]);
        self::assertResponseStatusCodeSame(422);self::assertCount(0,$this->refreshEm()->getRepository(Animal::class)->findAll());
    }
    public function testCsrfRequiredAndApplicantCannotCreateAnimals(): void
    {
        $this->client->loginUser($this->user('ROLE_VOLUNTEER'));
        $this->client->request('POST','/admin/animales/nuevo',[]);self::assertResponseStatusCodeSame(403);
        $this->client->loginUser($this->user('ROLE_USER','solicitante@example.test'));
        $this->client->request('GET','/admin/animales/nuevo');self::assertResponseStatusCodeSame(403);
    }
}
