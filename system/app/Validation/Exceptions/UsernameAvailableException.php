<?php

namespace App\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class UsernameAvailableException extends ValidationException
{

	public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Benutzername existiert bereits',
        ]
    ];
}
