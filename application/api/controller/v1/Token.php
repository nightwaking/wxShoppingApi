<?php

namespace app\api\controller\v1;

use app\api\validate\TokenGet;
use app\api\service\UserToken;
use app\api\service\Token as TokenService;
use app\lib\exception\ParamterException;

class Token{
	/**
	* 获取token值
	*/
	public function getToken($code = ''){
		(new TokenGet())->goCheck();
		$userToken = new UserToken($code);
		$token = $userToken->get();
		return [
			'token' => $token
		];
	}

	/**
	* 验证token 是否存在
	*/
	public function verifyToken($token=''){
		if (!$token){
			throw new ParamterException([
				'token不允许为空'
			]);
		}

		$valid = TokenService::verifyToken($token);
		return [
			'isValidate' => $valid
		];
	}
}