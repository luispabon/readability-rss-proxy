<?php
declare(strict_types=1);

namespace App\Services;

class PasswordStrengthValidator
{
    /**
     * @todo We're gonna want to implement an actual password strenght validator here.
     */
    public function validate(string $password): bool
    {
        return trim($password) !== '';
    }
}
