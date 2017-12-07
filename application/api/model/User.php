<?php

namespace app\api\model;

use think\Model;

class User extends BaseModel
{
    public function address(){
    	return $this->hasOne('UserAddress', 'user_id', 'id');
    }

    public static function getByOpenId($openid){
    	$openId = self::where('openid', '=', $openid)
    		->find();

    	return $openId;
    }
}
