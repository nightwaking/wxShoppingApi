<?php

namespace app\sample\validate;

use think\Validate;

class TestValidate extends Validate
{
	protected $rule = [
		'name' => 'require|max:10',
		'email' => 'email'
	];
}