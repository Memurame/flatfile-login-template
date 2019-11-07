<?php

namespace App\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class StrengthPasswordException extends ValidationException
{

	public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '',
        ]
    ];
}
