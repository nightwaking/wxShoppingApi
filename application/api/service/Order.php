<?php

namespace app\api\service;

use app\api\model\Product;
use app\api\model\Order as OrderModel;
use app\api\model\UserAddress;
use app\api\model\OrderProduct;
use app\lib\exception\OrderException;
use think\Db;

class Order{
	//订单的商品列表，用户传递回来的products参数
	protected $oProducts;
	//真实的商品信息(包括库存量)从数据库中查询
	protected $products;

	protected $uid;

	public function place($uid, $oProducts){
		//oProducts和product做对比
		$this->oProducts = $oProducts;
		$this->products = $this->getProductsByOrder($oProducts);
		$this->uid = $uid;
		$status = $this->getOrderStatus();
		if (!$status['pass']){
			$status['order_id'] = -1;
			return $status;
		}
		//开始创建订单
		$orderSnap = $this->snapOrder($status);
		$order = $this->createOrder($orderSnap);
		$order['pass'] = true;
		return $order;
	}

	// 生成订单
	private function createOrder($snap){
		//事务防止保存数据不同步
		Db::startTrans();
		try{
			$orderNo = $this->makeOrderNo();
			$order = new OrderModel();
			$order->user_id = $this->uid;
			$order->order_no = $orderNo;
			$order->total_price = $snap['orderPrice'];
			$order->total_count = $snap['totalCount'];
			$order->snap_img = $snap['snapImg'];
			$order->snap_name = $snap['snapName'];
			$order->snap_address = $snap['snapAddress'];
			//pStatus为数组，需要序列化为json字符串
			$order->snap_items = json_encode($snap['pStatus']);

			$order->save();
			
			$orderID = $order->id;
			$create_time = $order->create_time;
			foreach ($this->oProducts as &$p) {
				$p['order_id'] = $orderID;
			}
			$orderProduct = new OrderProduct();
			$orderProduct->saveAll($this->oProducts);
			Db::commit();
			return [
				'order_no' => $orderNo,
				'order_id' => $orderID,
				'create_time' => $create_time
			];
		}
		catch(Exception $ex){
			Db::rollback();
			throw $ex;
		}
	}

	// 订单号的定义
	public static function makeOrderNo(){
		$yCode = array('A','B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
		//dechex十进制转换成十六进制 intval获取目标整数值
		$orderSn = 
			$yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
		return $orderSn;
	}

	//生成订单快照
	private function snapOrder($status){
		$snap = [
			'orderPrice' => 0,
			'totalCount' => 0,
			'pStatus' => [],
			'snapAddress' => null,
			'snapName' => '',
			'snapImg' => ''
		];
	
		$snap['orderPrice'] = $status['orderPrice'];
		$snap['totalCount'] = $status['totalCount'];
		$snap['pStatus'] = $status['pStatusArray'];
		$snap['snapAddress'] = json_encode($this->getUserAddress());
		$snap['snapName'] = $this->products[0]['name'];
		$snap['snapImg'] = $this->products[0]['main_img_url'];
		if (count($this->products)>1){
			$snap['snapName'] .= '等';
		}
		return $snap;
	}

	// 获取用户地址，比对当前下单用户的id是否与数据库中相同
	private function getUserAddress(){
		$userAddress = UserAddress::where('user_id', '=', $this->uid)
			->find();
		if (!$userAddress){
			throw new UserException([
				'msg' => '用户收货地址不存在，下单失败',
				'errorCode' => 60001
			]);
		}
		return $userAddress->toArray();
	}

	// 根据订单号获取订单数据，主要用于库存检测
	public function checkOrderStock($orderID){
		$oProducts = OrderProduct::where('order_id', '=', $orderID)
			->select();
		$this->oProducts = $oProducts;
		$this->products = $this->getProductsByOrder($oProducts);
		$status = $this->getOrderStatus();
		return $status;
	}

	// 获取订单的基本状态，库存是否通过，一种商品未通过表示库存未通过
	private function getOrderStatus(){
		$status = [
			'pass' => true,
			'orderPrice' => 0,
			'totalCount' => 0,
			//保存订单所有商品的详细信息
			'pStatusArray' => [] 
		];

		foreach ($this->oProducts as $oProduct){
			$pStatus = $this->getProductStatus(
				$oProduct['product_id'], $oProduct['count'], $this->products
			);
			if(!$pStatus['haveStock']){
				$status['pass'] = false;
			}
			$status['orderPrice'] += $pStatus['totalPrice'];
			$status['totalCount'] += $pStatus['count'];
			array_push($status['pStatusArray'], $pStatus);
		}
		return $status;
	}

	// 获取各个商品的基本状态，用于为订单状态提供单位的数据
	private function getProductStatus($oPID, $oCount, $products){
		$pIndex = -1;
		//定义某一商品的信息
		$pStatus = [
			'id' => null,
			'main_img_url' => null,
			'price' => 0,
			'haveStock' => false,
			'count' => 0,
			'name' => '',
			'totalPrice' => 0
		];
		for ($i=0;$i<count($products); $i++){
			if($oPID == $products[$i]['id']){
				$pIndex = $i;
			}
		}
		if ($pIndex == -1){
			throw new OrderException([
				'msg' => 'id为'.$oPID .'商品不存在，创建订单失败'
			]);
		}else{
			$product = $products[$pIndex];
			$pStatus['id'] = $product['id'];
			$pStatus['count'] = $oCount;
			$pStatus['name'] = $product['name'];
			$pStatus['main_img_url'] = $product['main_img_url'];
			$pStatus['price'] = $product['price'];
			$pStatus['totalPrice'] = $product['price'] * $oCount;
			if ($product['stock'] - $oCount >= 0){
				$pStatus['haveStock'] = true;
			}
		}
		return $pStatus;
	}

	//根据订单信息从数据库中查询
	private function getProductsByOrder($oProducts){
		//定义一个数组，将用户id循环添加到数组中，使用数组进行查询
		$oPIDs = [];
		foreach ($oProducts as $items) {
			//避免循环查询数据库
			array_push($oPIDs, $items['product_id']);
		}
		$products = Product::all($oPIDs)
			->visible(['id', 'price', 'stock', 'name', 'main_img_url'])
			->toArray();
		return $products;
	}
}