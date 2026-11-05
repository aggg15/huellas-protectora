<?php
declare(strict_types=1);
namespace App\Tests;
use App\Entity\{Animal, AnimalPhoto};

class Spec002Test extends WebTestBase
{
    public function testAnonymousCatalogContainsOnlyRealAvailability(): void
    {
        $available=$this->animal(11,'available');$quarantine=$this->animal(1);$reserved=$this->animal(12,'reserved');$adopted=$this->animal(12,'adopted');
        $this->client->request('GET','/');self::assertResponseIsSuccessful();self::assertSelectorCount(1,'.animal-card');
        self::assertSelectorTextContains('.animal-card',$available->code);
        foreach ([$quarantine,$reserved,$adopted] as $a) { self::assertStringNotContainsString($a->code,$this->client->getResponse()->getContent()); }
        foreach ([$quarantine,$reserved,$adopted] as $a) { $this->client->request('GET','/animales/'.$a->id);self::assertResponseStatusCodeSame(404); }
    }
    public function testFiltersCombineWithAndAndHandleAgeBoundaries(): void
    {
        $young=$this->animal(11,'available','dog',11,'small');$adult=$this->animal(11,'available','dog',12,'medium');
        $senior=$this->animal(11,'available','cat',84,'small');$this->animal(11,'available','cat',83,'large');
        foreach ([['species=dog&size=small&age=young',$young],['species=dog&age=adult',$adult],['age=senior',$senior]] as [$query,$a]) {
            $this->client->request('GET','/?'.$query);self::assertResponseIsSuccessful();self::assertSelectorCount(1,'.animal-card');self::assertSelectorTextContains('.animal-card',$a->code);
        }
        $this->client->request('GET','/?species=dog&size=large');self::assertSelectorCount(0,'.animal-card');self::assertSelectorTextContains('main','Hoy no hay coincidencias');
    }
    public function testFosteredAnimalRemainsVisibleAndMarked(): void
    {
        $a=$this->animal(11,'available');$a->fostered=true;$this->em->flush();
        $this->client->request('GET','/');self::assertSelectorTextContains('.foster-tag','En acogida');
        $this->client->request('GET','/animales/'.$a->id);self::assertResponseIsSuccessful();self::assertSelectorTextContains('main','En acogida');
    }
    public function testInvalidFiltersAreRejectedAndQuarantineAutomaticallyExpires(): void
    {
        $a=$this->animal(11);
        $this->client->request('GET','/?species=bird');self::assertResponseStatusCodeSame(422);self::assertSelectorCount(0,'.animal-card');
        $this->client->request('GET','/?age[]=young');self::assertResponseStatusCodeSame(422);
        $this->client->request('GET','/');self::assertSelectorTextContains('.animal-card',$a->code);
    }
    public function testPublicPhotoAndUnknownAnimal(): void
    {
        $a=$this->animal(11,'available');$file=$this->photo();$name='public-test-'.bin2hex(random_bytes(6)).'.png';
        $dir=dirname(__DIR__).'/var/uploads';if (!is_dir($dir)) { mkdir($dir,0700,true); }$file->move($dir,$name);
        $photo=new AnimalPhoto($a,$name,'image/png');$this->em->persist($photo);$this->em->flush();
        $this->client->request('GET','/fotos/'.$photo->id);self::assertResponseIsSuccessful();self::assertResponseHeaderSame('Content-Type','image/png');
        $this->client->request('GET','/animales/ninguno');self::assertResponseStatusCodeSame(404);
    }
}
