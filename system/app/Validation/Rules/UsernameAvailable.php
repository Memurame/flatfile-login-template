<?php

namespace App\Validation\Rules;

use App\Controllers\Controller;
use Respect\Validation\Rules\AbstractRule;

class UsernameAvailable extends AbstractRule
{

	public function validate($input)
	{
    $u = new \App\Models\Accounts();
		return $u->where(['username' => $input])->count() === 0;
	}
}
