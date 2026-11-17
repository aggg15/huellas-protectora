<?php
declare(strict_types=1);
namespace App\Service;

/** Solicitud completa que no se admite por una regla de estado: límite de abiertas, duplicado o animal no disponible. */
class ApplicationRefused extends \DomainException
{
}
