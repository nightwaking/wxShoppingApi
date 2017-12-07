<?php

namespace app\api\validate;

class TokenGet extends BaseValidate{
	protected $rule = [
		'code' => 'require|isNotEmpty'
	];

	protected $message = [
		'code' => '必须传入code才能获取token'
	];
}