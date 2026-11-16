<?php
declare(strict_types=1);
namespace App\Service;

class ValidationError extends \DomainException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}
