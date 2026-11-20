<?php
declare(strict_types=1);
namespace App\Service;

/** Operación de apadrinamiento que no se admite por el estado del animal o del apadrinamiento (HU-08, 409). */
class SponsorshipRefused extends \DomainException
{
}
