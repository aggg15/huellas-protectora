<?php
use App\Kernel;
// PHP -S puede omitir el entorno del proceso en $_SERVER si variables_order no incluye E.
// Respetar explícitamente APP_ENV/APP_DEBUG antes de cargar Symfony Runtime.
foreach (['APP_ENV','APP_DEBUG'] as $key) {
    if (false !== ($value=getenv($key))) { $_SERVER[$key]=$_ENV[$key]=$value; }
}
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
return static fn(array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
