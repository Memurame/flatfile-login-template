<?php

namespace App\Validation\Rules;

use App\Controllers\Controller;
use Respect\Validation\Rules\AbstractRule;

class StrengthPassword extends AbstractRule
{

	public function validate($input)
	{
    if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $input )){
      return true;
    } else {
      return false;
    }
	}
}
