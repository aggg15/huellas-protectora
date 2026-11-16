<?php
declare(strict_types=1);

// Preparación local sin dependencias de Composer ni sobrescritura de secretos.
$root = dirname(__DIR__);
$arguments = array_slice($argv, 1);
if (array_diff($arguments, ['--test'])) {
    fwrite(STDERR, "Uso: php tools/configure.php [--test]\n");
    exit(1);
}

function createLocalFile(string $path, string $contents): void
{
    if (file_exists($path)) {
        echo basename($path).": existente, se conserva.\n";
        return;
    }
    $handle = @fopen($path, 'x');
    if ($handle === false) {
        throw new RuntimeException('No se pudo crear '.basename($path));
    }
    try {
        chmod($path, 0600);
        if (fwrite($handle, $contents) !== strlen($contents)) {
            throw new RuntimeException('Escritura incompleta de '.basename($path));
        }
    } finally {
        fclose($handle);
    }
    echo basename($path).": creado; secretos no mostrados.\n";
}

try {
    $example = file_get_contents($root.'/.env.example');
    if ($example === false) {
        throw new RuntimeException('Falta .env.example.');
    }
    createLocalFile($root.'/.env', $example);
    $local = str_replace(
        ['change-this-local-secret', 'change-this-local-password'],
        [bin2hex(random_bytes(24)), bin2hex(random_bytes(20))],
        $example,
    );
    createLocalFile($root.'/.env.local', $local);

    if (in_array('--test', $arguments, true) && !file_exists($root.'/.env.test.local')) {
        $configuration = file_get_contents($root.'/.env.local');
        if ($configuration === false || !preg_match('/^DATABASE_URL=(.*)$/m', $configuration, $match)) {
            throw new RuntimeException('Configura DATABASE_URL en .env.local antes de preparar las pruebas.');
        }
        $url = trim(trim($match[1]), "\"'");
        $parts = parse_url($url);
        $database = is_array($parts) ? ltrim($parts['path'] ?? '', '/') : '';
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['postgresql', 'postgres'], true)
            || !preg_match('/^[a-zA-Z0-9_]+$/', $database) || str_contains($url, '$')) {
            throw new RuntimeException('DATABASE_URL personalizada: configura .env.test.local manualmente con una base separada terminada en _test.');
        }
        $testDatabase = str_ends_with($database, '_test') ? $database : $database.'_test';
        $testUrl = preg_replace('~/'.preg_quote($database, '~').'(?=\?|$)~', '/'.$testDatabase, $url, 1);
        createLocalFile($root.'/.env.test.local',
            "# Base de pruebas separada; no compartir credenciales.\nAPP_SECRET=".bin2hex(random_bytes(24))."\nDATABASE_URL=\"".$testUrl."\"\n");
    } elseif (in_array('--test', $arguments, true)) {
        echo ".env.test.local: existente, se conserva.\n";
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
}
