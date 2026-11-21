<?php
declare(strict_types=1);
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        // Las fechas se guardan en UTC y las vistas las convierten a Europe/Madrid:
        // la zona de PHP no puede depender del servidor (web, consola y pruebas).
        date_default_timezone_set('UTC');
        parent::__construct($environment, $debug);
    }
}
