<?php

namespace app\api\model;

use app\api\model\BaseModel;

class Product extends BaseModel
{
	//pivot 多对多关系的中间表
    protected $hidden = [
    	'delete_time', 'from', 'category_id',
    	'create_time', 'update_time', 'pivot'
    ];

    public function imgs(){
        return $this->hasMany('ProductImage', 'product_id', 'id');
    }

    public function properties(){
        return $this->hasMany('ProductProperty', 'product_id', 'id');
    }

    public function getMainImgUrlAttr($value,$data){
    	return $this->prefixImg($value, $data);
    }

    public static function getMostRecent($count){
    	$products = self::limit($count)
    			->order('create_time desc')
    			->select();
    	return $products;
    }

    public static function getProductsByCategory($categoryID){
        $products = self::where('category_id', '=', $categoryID)
            ->select();
        return $products;
    }

    public static function getOne($id){
        //闭包进行关联表的排序
        $product = self::with([
            'imgs' => function($query){
                $query->with('imgUrl')
                ->order('order', 'asc');
            }
        ])
            ->with('properties')
            ->find($id);
        return $product;
    }
}
