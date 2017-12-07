<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\validate\IDMustBePostiveInt;
use app\api\service\Pay as PayService;
use app\api\service\WxNotify;

class Pay extends BaseController{
	
	protected $beforeActionList = [
		'checkExclusiveScope' => ['only' => 'getPreOrder']
	];
	
	//请求预订单信息
	public function getPreOrder($id=""){
		(new IDMustBePostiveInt())->goCheck();
		$pay = new PayService($id);
		return $pay->pay();
	}

	// 接收微信通知，微信支付结果 post请求 xml格式 不能在路由中携带查询参数
	public function receiveNotify(){
		// 调用回调接口的频率　15/15/30/180/1800/1800/1800/3600 秒
		// 前提为未调用成功后间隔发送请求，微信不能保证每次的回调都能成功
		
		// 检测库存量

		// 更新订单状态，更改数据库中的status值

		// 更新库存

		// 返回成功处理
		$notfiy = new WxNotify();
		// 调用Handle方法,NotifyProcess方法的$data参数未知，调用Handle方法
		// 内部调用NotifyProcess
		$notfiy->Handle();
	}
}