<?php

namespace app\api\model;

use think\Model;

class Theme extends BaseModel
{
    protected $hidden = ['delete_time', 'update_time', 
    						'topic_img_id', 'head_img_id'];

    //一对一是有从属关系　主键为当前模型的主键
    public function topicImg(){
    	return $this->belongsTo('Image', 'topic_img_id', 'id');
    }

    public function headImg(){
    	return $this->belongsTo('Image', 'head_img_id', 'id');
    }

    // 多对多
    public function products(){
    	return $this->belongsToMany('Product', 'theme_product', 'product_id', 'theme_id');
    }

    public static function getThemeById($ids){
    	$result = self::with('topicImg,headImg')
            ->select($ids);
        return $result;
    }

    public static function getThemeWithProducts($id){
    	$theme = self::with('products,topicImg,headImg')
    		->find($id);
    	return $theme;
    }
}
