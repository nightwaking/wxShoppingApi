<?php

namespace app\api\model;

use think\Db;
use think\Model;


class Banner extends BaseModel
{
	protected $hidden = ['update_time', 'delete_time'];

	public function items(){
		//关联表，　关联的字段，当前模型主键
		return $this->hasMany('BannerItem', 'banner_id', 'id');
	}


	public static function getBannerByID($id)
	{
		//返回的$banner为对象  返回一个对象　模型get Db find 返回一组　模型all Db select
		$banner = self::with(['items','items.img'])->find($id);
		return $banner;
	}
}