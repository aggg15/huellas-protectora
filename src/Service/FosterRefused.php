<?php
declare(strict_types=1);
namespace App\Service;

/** Operación de acogida que no se admite por una regla de estado, disponibilidad o capacidad (HU-06, 409). */
class FosterRefused extends \DomainException
{
}
