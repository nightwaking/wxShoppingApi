<?php

namespace app\api\validate;

class PagingParameter extends BaseValidate{
	protected $rule = [
		'page' => 'isPostiveInteger',
		'size' => 'isPostiveInteger'
	];

	protected $message = [
		'page' => '分页参数必须为正整数',
		'size' => '分页参数必须为正整数'
	];
}