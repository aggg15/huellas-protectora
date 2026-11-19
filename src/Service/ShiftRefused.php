<?php
declare(strict_types=1);
namespace App\Service;

/** Operación sobre un turno que no se admite por su estado, sus plazas o una coincidencia (HU-07, 409). */
class ShiftRefused extends \DomainException
{
}
