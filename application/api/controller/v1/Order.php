<?php

namespace app\api\controller\v1;

use app\api\service\Token as TokenService;
use app\api\service\Order as OrderService;
use app\api\model\Order as OrderModel;
use app\api\validate\OrderPlace;
use app\api\validate\IDMustBePostiveInt;
use app\api\validate\PagingParameter;
use app\lib\exception\TokenException;
use app\lib\exception\OrderException;
use app\lib\exception\ForbiddenException;
use app\lib\enum\ScopeEnum;
use app\api\controller\BaseController;

class Order extends BaseController{
	//用户在选择商品后，向api提交包含他所选商品的相关信息
	//api在接收到信息后，需要检查订单相关的库存
	//有库存，把订单数据存入数据库中　＝　下单成功，返回客户端消息，客户端可以支付
	//调用支付接口，进行支付
	//还需要进行库存量的检测
	//服务器调用微信的支付接口进行支付
	//根据微信返回的支付结果，返回给服务器和小程序客户端，判断支付是否成功(异步)
	//成功：库存量的检测
	//成功进行库存量的扣除

	// 前置操作
	protected $beforeActionList = [
	 	'checkExclusiveScope' => ['only' => 'placeOrder'],
	 	'checkPrimaryScope' => ['only' => 'getDetail, getSummaryByUser']
	];

	/**
	* 根据用户返回用户订单的概要信息，进行分页
	*/
	public function getSummaryByUser($page=1, $size=15){
		(new PagingParameter())->goCheck();
		// 获取用户uid
		$uid = TokenService::getCurrentUid();
		$pagingOrders = OrderModel::getSummaryByUser($uid, $page, $size);
		if ($pagingOrders->isEmpty()){
			// 分页对象为空，返回空数组，将当前分页数返回，调用对象的getCurrentPage
			//　方法
			return [
				'data' => [],
				'current_page' => $pagingOrders->getCurrentPage()
			];
		}
		//　判断通过，将对象转换成数组
		$data = $pagingOrders->hidden(['snap_items', 'snap_address', 'prepay_id'])->toArray();
		return [
			'data' => $data,
			'current_page' => $pagingOrders->getCurrentPage()
		];
	}

	/**
	* 获取订单详细信息
	*　＠param $id 订单id
	*/
	public function getDetail($id){
		(new IDMustBePostiveInt())->goCheck();
		$orderDetail = OrderModel::get($id);
		if (!$orderDetail){
			throw new OrderException();
		}
		return $orderDetail->hidden(['prepay_id']);
	}
	
	/**
	* 创建订单，返回订单信息
	*/
	public function placeOrder(){
		(new OrderPlace())->goCheck();
		$products = input('post.products/a');
		
		$uid = TokenService::getCurrentUid();

		$order = new OrderService();
		$status = $order->place($uid, $products);
		return $status;
	}
}