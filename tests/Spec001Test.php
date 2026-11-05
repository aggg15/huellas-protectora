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
        $animal=$this->animal();$p=new AnimalPhoto($animal,'no-existe.png','image/png');$this->em->persist($p);$this->em->flush();
        $this->client->request('GET','/admin/animales/'.$animal->id);self::assertResponseRedirects('/acceso');
        $this->client->request('GET','/animales/'.$animal->id);self::assertResponseStatusCodeSame(404);
        $this->client->request('GET','/fotos/'.$p->id);self::assertResponseStatusCodeSame(404);
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
        $form=$crawler->selectButton('Guardar revisión')->form([
            'reviewedAt'=>(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Madrid')))->format('Y-m-d\TH:i'),'notes'=>'Revisión ficticia sin incidencias.',
        ]);$this->client->submit($form);self::assertResponseRedirects(null,303);
        self::assertCount(1,$this->refreshEm()->getRepository(VeterinaryReview::class)->findAll());
        $volunteer=$this->user('ROLE_VOLUNTEER','voluntario@example.test');$this->client->loginUser($volunteer);
        $this->client->request('POST','/admin/animales/'.$animal->id.'/revision',['notes'=>'No permitido']);self::assertResponseStatusCodeSame(403);
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
    public function testMissingOrTooManyPhotosAndMalformedFieldsAreRejected(): void
    {
        $this->client->loginUser($this->user('ROLE_VOLUNTEER'));
        $crawler=$this->client->request('GET','/admin/animales/nuevo');$token=$crawler->filter('input[name="_token"]')->attr('value');
        $data=['_token'=>$token,'species'=>'dog','ageMonths'=>'3','size'=>'small','origin'=>'Procedencia ficticia','receivedAt'=>'2026-01-01T10:00'];
        foreach ([[],array_map(fn()=>$this->photo(),range(1,7))] as $files) {
            $this->client->request('POST','/admin/animales/nuevo',$data,['photos'=>$files]);self::assertResponseStatusCodeSame(422);
            self::assertCount(0,$this->refreshEm()->getRepository(Animal::class)->findAll());
        }
        $data['origin']=['texto inesperado'];
        $this->client->request('POST','/admin/animales/nuevo',$data,['photos'=>[$this->photo()]]);self::assertResponseStatusCodeSame(422);
        self::assertCount(0,$this->refreshEm()->getRepository(Animal::class)->findAll());
    }
}
