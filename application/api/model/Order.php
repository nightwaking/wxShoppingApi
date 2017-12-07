<?php

namespace app\api\model;

class Order extends BaseModel{
	protected $hidden = ['user_id', 'delete_time', 'update_time'];
	protected $autoWriteTimestamp = true;

	/**
	* 定义读取器，将SnapItem字段序列化
	*/
	public function getSnapItemsAttr($value){
		if (empty($value)){
			return null;
		}
		return json_decode($value);
	}

	/**
	* 定义读取器，将SnapAddress字段序列化
	*/
	public function getSnapAddressAttr($value){
		// 判空
		if (empty($value)){
			return null;
		}
		return json_decode($value);
	}


	public static function getSummaryByUser($uid, $page=1, $size=15){
		// paginate 返回的式一个Paginator对象,进行了查询操作
		$pagingDate = self::where('user_id', '=', $uid)
			->order('create_time desc')
			->paginate($size,true,['page' => $page]);
		return $pagingDate;
	}
}