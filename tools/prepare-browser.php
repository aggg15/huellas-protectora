<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$_SERVER['APP_ENV']=$_ENV['APP_ENV']='test';putenv('APP_ENV=test');
$_SERVER['SHELL_VERBOSITY']='-1';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel=new App\Kernel('test',true);$kernel->boot();$container=$kernel->getContainer()->get('test.service_container');
$em=$container->get(Doctrine\ORM\EntityManagerInterface::class);$db=$em->getConnection();
if (!str_ends_with($db->getDatabase(),'_test')) { throw new \RuntimeException('Solo base _test.'); }
$tables=array_filter($db->createSchemaManager()->listTableNames(),fn($t)=>$t!=='doctrine_migration_versions');
$db->executeStatement('TRUNCATE '.implode(', ',array_map($db->quoteIdentifier(...),$tables)).' CASCADE');
$hasher=$container->get('security.user_password_hasher');$users=[];
foreach (['voluntario'=>'ROLE_VOLUNTEER','veterinario'=>'ROLE_VET','coordinadora'=>'ROLE_COORDINATOR','solicitante'=>'ROLE_USER'] as $name=>$role) {
    $user=new App\Entity\User($name.'@example.test',ucfirst($name).' Ficticio');$user->roles=[$role];$user->password=$hasher->hashPassword($user,'Ficticia-Prueba-2026');$em->persist($user);$users[$name]=$user;
}
$dir=dirname(__DIR__).'/var/uploads';if (!is_dir($dir)) { mkdir($dir,0700,true); }
$animals=[];
foreach ([['dog','small',8,false],['cat','medium',24,true],['dog','large',96,false]] as $i=>[$species,$size,$age,$fostered]) {
    $animal=new App\Entity\Animal($species,$age,$size,'Escenario ficticio de navegador.',new \DateTimeImmutable('-12 days'));
    $animal->status='available';$animal->fostered=$fostered;$em->persist($animal);$animals[]=$animal;
    $image=imagecreatetruecolor(800,600);$background=imagecolorallocate($image,226-$i*8,232-$i*5,210+$i*10);imagefill($image,0,0,$background);
    $fur=imagecolorallocate($image,165+$i*15,123+$i*10,83+$i*20);$cream=imagecolorallocate($image,249,234,207);$ink=imagecolorallocate($image,58,55,45);
    imagefilledellipse($image,400,435,290,360,$fur);imagefilledellipse($image,400,280,300,280,$fur);
    imagefilledellipse($image,273,255,80,210,$fur);imagefilledellipse($image,527,255,80,210,$fur);
    imagefilledellipse($image,400,320,150,105,$cream);imagefilledellipse($image,345,270,15,20,$ink);imagefilledellipse($image,455,270,15,20,$ink);imagefilledellipse($image,400,310,25,17,$ink);
    $filename='browser-fixture-'.$i.'.png';imagepng($image,$dir.'/'.$filename);imagedestroy($image);
    $em->persist(new App\Entity\AnimalPhoto($animal,$filename,'image/png'));
}
$em->flush();
file_put_contents(dirname(__DIR__).'/var/browser-data.json',json_encode(['animals'=>array_map(fn($a)=>['id'=>$a->id,'code'=>$a->code],$animals),'photo'=>$dir.'/browser-fixture-0.png'],JSON_PRETTY_PRINT));
echo "Datos ficticios de navegador preparados únicamente en la base _test.\n";
