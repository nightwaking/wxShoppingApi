<?php

namespace app\api\controller\v1;

use app\api\validate\AddressNew;
use app\api\service\Token as TokenService;
use app\api\model\User as UserModel;
use app\api\model\UserAddress;
use app\lib\exception\UserException;
use app\lib\exception\SuccessMessage;
use app\api\controller\BaseController;

class Address extends BaseController
{
	protected $beforeActionList = [
		'checkPrimaryScope' => ['only' => 'createOrUpdateAddress, getUserAddress']
	];

	public function createOrUpdateAddress(){
		$validate = new AddressNew();
		$validate->goCheck();
		//根据token获取UID
		//根据UID查找用户数据，判断用户是否存在，不存在抛出异常
		//获取客户端提交的地址信息
		//判断是添加地址还是修改
		$uid = TokenService::getCurrentUid();
		$user = UserModel::get($uid);
		if (!$user){
			throw new UserException();
		}
		
		//post.获取post所有的数据
		$dataArray = $validate->getDataByRule(input('post.'));
		
		$userAddress = $user->address;
		if (!$userAddress){
			$user->address()->save($dataArray);
		}else{
			$user->address->save($dataArray);
		}

		return json(new SuccessMessage(), 201);
	}

	/**
	* 获取用户地址信息
	*/
	public function getUserAddress(){
		$uid = TokenService::getCurrentUid();
		$userAddress = UserAddress::where('user_id', $uid)
			->find();
		if (!$userAddress){
			throw new UserException([
				'msg' => '用户地址不存在',
				'errorCode' => 60001
			]);
		}
		return $userAddress;
	}
}