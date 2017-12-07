<?php

namespace app\api\service;

use think\Loader;
use app\api\model\Order as OrderModel;
use app\api\service\Order as OrderService;
use app\lib\enum\OrderEnum;
use think\Db;
use think\Log;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

// 继承WxPayNotify, 重写回调方法
class WxNotify extends \WxPayNotify{
	// 可以将一个变量通过引用传递给函数，这样该函数就可以修改其参数的值
	// function foo(&$var)
	// {
 	//    $var++;
	// }

	// $a=5;
	// foo($a);
	// $a is 6 here
	public function NotifyProcess($data, &$msg){
		// 判断支付是否成功
		if ($data['result_code'] == 'SUCCESS'){
			$orderNo = $data['out_trade_no'];
			// 使用事务，防止操作中多次减少库存　相当于数据库锁
			Db::startTrans();
			try{
				// 查询订单信息,lock(true)数据库查询锁，不能代替事物锁
				$order = OrderModel::where('order_no', '=', $orderNo)
					->lock(true)
					->find();
					// 订单未被处理
					if ($order->status == OrderEnum::UNPAID){
						// 进行库存量检测
						$orderService = new OrderService();
						$stockStatus = $orderService->checkOrderStock($order->id);
						// 库存量检测通过
						if ($stockStatus['pass']){
							$this->updateOrderStatus($order->id, true);
							$this->reduceStock($stockStatus);
						}else{
							//已经支付但库存不足
							$this->updateOrderStatus($order->id, false);
						}
					}
					Db::commit();
					return true;
			}catch(Exception $ex){
				Db::rollback();
				Log::error($ex);
				return false;
			}
		}else{
			// 失败也返回true防止微信重复发送信息
			return true;
		}
	}

	// 改变订单状态　status的值改变
	private function updateOrderStatus($orderID, $success){
		// 如果成功状态改变为已成功，否则为支付库存不足
		$status = $success?
			OrderEnum::PAID : 
			OrderEnum::PAID_BUT_OUT_OF;
		OrderModel::where('id', '=', $orderID)
			->update(['status' => $status]);
	}

	// 库存量减少
	private function reduceStock($stockStatus){
		foreach ($stockStatus['pStatusArray'] as $singleStatus){
			Product::where('id', '=', $singleStatus['id'])
				->setDec('stock', $singleStatus['count']);
		}
	}
}