<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$_SERVER['APP_ENV']=$_ENV['APP_ENV']='test';putenv('APP_ENV=test');
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel=new App\Kernel('test',false);$kernel->boot();
$container=$kernel->getContainer()->get('test.service_container');
$em=$container->get(Doctrine\ORM\EntityManagerInterface::class);
if (!str_ends_with($em->getConnection()->getDatabase(),'_test')) { exit(1); }
try {
    $user=$em->find(App\Entity\User::class,$argv[2]);
    $container->get(App\Service\ApplicationService::class)->submit($user,$argv[3],['housing'=>'Vivienda ficticia','experience'=>'Ninguna','availability'=>'Todos los días']);
    echo "created\n";exit(0);
} catch (App\Service\ValidationError $error) { echo $error->getMessage()."\n";exit(2); }
catch (\Throwable $error) { echo get_class($error).': '.$error->getMessage()."\n";exit(1); }
