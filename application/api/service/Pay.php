<?php

namespace app\api\service;

use app\api\service\Order as OrderService;
use app\api\service\Token;
use app\api\model\Order as OrderModel;
use app\lib\exception\OrderException;
use app\lib\exception\TokenException;
use app\lib\enum\OrderEnum;
use think\Exception;
use think\Log;
use think\Loader;

// extend/WxPay/WxPay.Api.php
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

class Pay{
	private $orderID;
	private $orderNO;

	function __construct($orderID){
		if (!$orderID){
			throw new Exception('订单号不能为空');
		}
		$this->orderID = $orderID;
	}

	public function pay(){
		//订单号可能不存在
		//订单号存在和当前用户不匹配
		//订单可能已经被支付
		$this->checkOrderValid();
		//进行库存检测
		$orderService = new OrderService();
		$status = $orderService->checkOrderStock($this->orderID);
		if (!$status['pass']){
			return $status;
		}
		return $this->makePreOrder($status['orderPrice']);
	}

	// 检测订单的状态是否符合上面给出的条件
	private function checkOrderValid(){
		$order = OrderModel::where('id', '=', $this->orderID)
			->find();
		if (!$order){
			throw new OrderException();
		}
		if (!Token::isValidOperate($order->user_id)){
			throw new TokenException([
				'msg' => '用户与订单不匹配',
				'errorCode' => 10003
			]);
		}
		// 订单状态必须为未支付状态
		if($order->status != OrderEnum::UNPAID){
			throw new OrderException([
				'msg' => '订单可能已经被支付',
				'errorCode' => 80003,
				'code' => 400
			]);
		}
		$this->orderNO = $order->order_no;
		return true;
	}

	// 生成预订单
	private function makePreOrder($totalPrice){
		// openid微信可识别的标识	
		$openid = Token::getCurrentTokenVal('openid');
		if (!$openid){
			throw new TokenException();
		}
		//　调用微信统一下单接口
		$wxOrderData = new \WxPayUnifiedOrder();
		// 订单号
		$wxOrderData->SetOut_trade_no($this->orderNO);
		// 订单种类 小程序默认JSAPI
		$wxOrderData->SetTrade_type('JSAPI');
		// 订单总价
		$wxOrderData->SetTotal_fee($totalPrice * 100);
		// 商品简单描述
		$wxOrderData->SetBody('小吃');
		// 用户标识
		$wxOrderData->SetOpenid($openid);
		// 接收微信结果的接口
		$wxOrderData->SetNotify_url(config('secure.pay_back_url'));
		return $this->getSignature($wxOrderData);
	}

	//　调用支付接口
	private function getSignature($wxOrderData){
		// unifiedOrder 为静态方法
		$wxOrder = \WxPayApi::unifiedOrder($wxOrderData);
		if ($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] != 'SUCCESS')
		{
			//　支付失败　将失败结果记录到日志
			Log::record($wxOrder, 'error');
			Log::record('获取预支付订单失败', 'error');
		}
		// 微信返回prepay_id
		$this->recordPreOrder($wxOrder);
		// 发起微信支付需要的参数
		// 签名意义，防止参数篡改，微信服务器根据传入的参数，以相同的算法计算出一个签名
		//　与传入的签名进行比较，是否相等，相等未被篡改
		$signature = $this->sign($wxOrder);
		return $signature;
	}

	// 生成签名
	private function sign($wxOrder){
		// 调用微信SDK中的方法
		$jsApiPayData = new \WxPayJsApiPay();
		$jsApiPayData->SetAppid(config('wx_app_id'));
		// 时间戳必须转换成String类型
		$jsApiPayData->SetTimeStamp((string)time());
		$rand = md5(time() . mt_rand(0, 1000));
		// 传入随机字符串，小于32位
		$jsApiPayData->SetNonceStr($rand);
		// 统一下单接口的prepay_id
		$jsApiPayData->SetPackage('prepay_id=' . $wxOrder['prepay_id']);
		// 生成签名
		$jsApiPayData->SetSignType('md5');
		$sign = $jsApiPayData->MakeSign();
		// 获取原始数据,包括时间戳等，不包含签名
		$rawValues = $jsApiPayData->GetValues();
		$rawValues['paySign'] = $sign;
		// 不返回appId
		unset($rawValues['appId']);
		return $rawValues;
	}

	// 处理微信返回的prepay_id
	private function recordPreOrder($wxOrder){
		//　对表中的数据进行更新操作
		OrderModel::where('id', '=', $this->orderID)
			->update(['prepay_id' => $wxOrder['prepay_id']]);
	}
}