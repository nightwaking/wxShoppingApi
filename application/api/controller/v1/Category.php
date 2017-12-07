<?php

namespace app\api\controller\v1;

use app\api\model\Category as CategoryModel;
use app\lib\exception\CategoryException;

class Category{
	public function getAllCategories(){
		//all = with() ->select
		$categories = CategoryModel::all([], 'Img');
		if ($categories->isEmpty()){
			throw new CategoryException();
		}
		return $categories;
	}
}