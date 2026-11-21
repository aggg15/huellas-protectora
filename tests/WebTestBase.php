<?php
declare(strict_types=1);
namespace App\Tests;
use App\Entity\{Animal, AnimalPhoto, User};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class WebTestBase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $connection = $this->em->getConnection();
        if (!str_ends_with($connection->getDatabase(), '_test')) { throw new \RuntimeException('Solo se pueden limpiar bases terminadas en _test.'); }
        $tables = array_filter($connection->createSchemaManager()->listTableNames(), fn($t)=>$t !== 'doctrine_migration_versions');
        if ($tables) { $connection->executeStatement('TRUNCATE '.implode(', ',array_map($connection->quoteIdentifier(...),$tables)).' CASCADE'); }
    }
    protected function tearDown(): void
    {
        // Solo el almacén de pruebas: nunca var/uploads.
        $dir = dirname(__DIR__).'/var/uploads_test';
        if (is_dir($dir)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
            @rmdir($dir);
        }
        parent::tearDown();
    }
    /** Guarda un PNG real en el almacén de pruebas y lo asocia al animal. */
    protected function storedPhoto(Animal $animal): AnimalPhoto
    {
        $storage = self::getContainer()->get(\App\Service\PhotoStorage::class);
        $filename = 'prueba-'.bin2hex(random_bytes(6)).'.png';
        $path = $storage->path($filename);
        if (!is_dir(dirname($path))) { mkdir(dirname($path), 0700, true); }
        $image = imagecreatetruecolor(10, 10); imagepng($image, $path); imagedestroy($image);
        $photo = new AnimalPhoto($animal, $filename, 'image/png');
        $this->em->persist($photo); $this->em->flush();
        return $photo;
    }
    protected function user(string $role='ROLE_USER', string $email='persona@example.test'): User
    {
        $user = new User($email, 'Persona Ficticia'); $user->roles=[$role];
        $user->password=self::getContainer()->get('security.user_password_hasher')->hashPassword($user, 'Ficticia-Prueba-2026');
        $this->em->persist($user); $this->em->flush(); return $user;
    }
    protected function animal(int $days=0, string $status='quarantine', string $species='dog', int $age=24, string $size='medium'): Animal
    {
        $animal = new Animal($species, $age, $size, 'Recogido en un escenario ficticio.', new \DateTimeImmutable('-'.$days.' days', new \DateTimeZone('UTC')));
        $animal->status=$status; $this->em->persist($animal); $this->em->flush(); return $animal;
    }
    protected function photo(): UploadedFile
    {
        $path=tempnam(sys_get_temp_dir(),'huellas-');
        $image=imagecreatetruecolor(20,20);imagepng($image,$path);imagedestroy($image);
        return new UploadedFile($path,'prueba.png','image/png',null,true);
    }
    protected function refreshEm(): EntityManagerInterface
    {
        return $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }
}
