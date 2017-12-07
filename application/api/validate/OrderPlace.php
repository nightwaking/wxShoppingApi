<?php

namespace app\api\validate;

use app\api\validate\BaseValidate;
use app\lib\exception\ParameterException;

class OrderPlace extends BaseValidate{
	
	protected $rule = [
		'products' => 'checkProducts'
	];

	protected $singleRule = [
		'product_id' => 'require|isPostiveInteger',
		'count' => 'require|isPostiveInteger'
	];

	protected function checkProducts($values){
		if(!is_array($values)){
			throw new ParameterException([
				'msg' => '参数形式不正确'
			]);
		}

		if (empty($values)){
			throw new ParameterException([
				'msg' => '商品列表不能为空'
			]);
		}

		foreach($values as $value){
			$this->checkProduct($value);
		}
		return true;
	}

	protected function checkProduct($value){
		$validate = new BaseValidate($this->singleRule);
		$result = $validate->check($value);
		if (!$result){
			throw new ParameterException([
				'msg' => '商品列表参数错误'
			]);
		}
	}
}