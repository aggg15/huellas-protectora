<?php
// Pequeño servidor de control para la grabación (solo red interna de Docker, puerto 8001):
//   GET /reset[?release_in=SEGUNDOS]  vuelve a cargar los datos de demo (seed.php --reset --json) y devuelve el JSON.
//   GET /photo.png?kind=dog|cat&n=3   ilustración PNG para subirla en el alta de un animal.
//   GET /health                       «ok».
// Uso:  php -S 0.0.0.0:8001 tools/demo/ctl.php
// Delega en seed.php, que se niega a trabajar si la base no contiene «demo» en su nombre.
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/health') { header('Content-Type: text/plain'); echo 'ok'; return; }
if ($path === '/photo.png') {
    require __DIR__.'/lib.php';
    $kind = ($_GET['kind'] ?? 'dog') === 'cat' ? 'cat' : 'dog';
    header('Content-Type: image/png');
    echo demoPetPng($kind, (int) ($_GET['n'] ?? 0));
    return;
}
if ($path === '/reset') {
    $cmd = [PHP_BINARY, __DIR__.'/seed.php', '--reset', '--json'];
    if (isset($_GET['release_in'])) { $cmd[] = '--release-in='.(int) $_GET['release_in']; }
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    $code = proc_close($proc);
    http_response_code($code === 0 ? 200 : 500);
    header('Content-Type: application/json; charset=utf-8');
    echo $code === 0 ? $stdout : json_encode(['error' => $stderr]);
    return;
}
http_response_code(404);
echo 'No encontrado';
