<?php

namespace app\api\controller\v1;

use app\api\validate\Count;
use app\api\model\Product as ProductModel;
use app\lib\exception\ProductException;
use app\api\validate\IDMustBePostiveInt;

class product{
	public function getRecent($count=15){
		(new Count())->goCheck();
		$products = ProductModel::getMostRecent($count);
		if ($products->isEmpty()){
			throw new ProductException();
		}
		//转换成数剧集，方便调用对象的方法
		$products = $products->hidden(['summary']);
		return $products;
	}

	public function getAllCategory($id){
		(new IDMustBePostiveInt())->goCheck();
		$products = ProductModel::getProductsByCategory($id);
		if ($products->isEmpty()){
			throw new ProductException();
		}
		return $products;
	}

	public function getOne($id){
		(new IDMustBePostiveInt())->goCheck();
		$product = ProductModel::getOne($id);
		if(!$product){
			throw new ProductException();
		}
		return $product;
	}
}