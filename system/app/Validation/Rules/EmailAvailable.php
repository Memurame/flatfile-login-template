<?php

namespace App\Validation\Rules;

use App\Controllers\Controller;
use Respect\Validation\Rules\AbstractRule;

class EmailAvailable extends AbstractRule
{

	public function validate($input)
	{
    $u = new \App\Models\Accounts();
		return $u->where(['email' => $input])->count() === 0;
	}
}
