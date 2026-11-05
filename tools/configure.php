<?php
declare(strict_types=1);
// No necesita vendor/. Solo crea configuración local ausente y no imprime secretos.
$root=dirname(__DIR__);$example=file_get_contents($root.'/.env.example');
if (!is_file($root.'/.env')) { file_put_contents($root.'/.env',$example);chmod($root.'/.env',0600); }
if (!is_file($root.'/.env.local')) {
    $local=str_replace(['change-this-local-secret','change-this-local-password'],[bin2hex(random_bytes(24)),bin2hex(random_bytes(18))],$example);
    file_put_contents($root.'/.env.local',$local);chmod($root.'/.env.local',0600);
}
if (in_array('--test',$argv,true) && !is_file($root.'/.env.test.local')) {
    $local=file_get_contents($root.'/.env.local');
    $test=str_replace('APP_ENV=dev','APP_ENV=test',$local);
    $test=preg_replace('~(postgresql://[^/]+/)([^?"\s]+)~','$1$2_test',$test);
    file_put_contents($root.'/.env.test.local',$test);chmod($root.'/.env.test.local',0600);
}
if (in_array('--test',$argv,true) && !is_file($root.'/.env.e2e.local')) {
    file_put_contents($root.'/.env.e2e.local',str_replace('APP_ENV=test','APP_ENV=e2e',file_get_contents($root.'/.env.test.local')));
    chmod($root.'/.env.e2e.local',0600);
}
echo "Configuración local preparada; los archivos existentes se han conservado y los secretos no se versionan.\n";
