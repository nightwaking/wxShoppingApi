<?php

namespace app\api\model;

use think\Model;

class Category extends BaseModel
{
    public function Img(){
    	return $this->belongsTo('Image', 'topic_img_id', 'id');
    }

    protected $hidden = ['delete_time', 'update_time'];
}
