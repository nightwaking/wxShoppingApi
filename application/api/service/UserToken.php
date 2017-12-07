<?php

namespace app\api\service;

use app\lib\exception\WechatException;
use app\lib\exception\TokenException;
use app\api\model\User as UserModel;
use app\lib\enum\ScopeEnum;

class UserToken extends Token{

	protected $code;
	protected $wxAppID;
	protected $wxAppSecret;
	protected $wxLoginUrl;

	//构造方法初始化参数
	public function __construct($code){
		$this->code = $code;
		$this->wxAppID = config('wxSetting.app_id');
		$this->wxAppSecret = config('wxSetting.app_secret');
		$this->wxLoginUrl = sprintf(config('wxSetting.login_url'),
			$this->wxAppID, $this->wxAppSecret, $this->code);
	}

	//获取openid
	public function get(){
		$result = curl_get($this->wxLoginUrl);
		$wxResult = json_decode($result, true);
		if (empty($wxResult)){
			throw new Exception('获取异常，微信内部错误');
		}else{
			$loginFail = array_key_exists('errorcode', $wxResult);
			if ($loginFail){
				$this->processLoginError();
			}else{
				return $this->grantToken($wxResult);
			}
		}
	}

	//生成令牌
	private function grantToken($wxResult){
		/**
		* 拿到openid
		* 对比数据库中数据，openid是否存在
		*　若存在不做处理，若不存在新增一条user记录
		* 生成令牌，准备缓存数据，写入缓存
		* 把令牌返回到客户端
		* key:令牌
		* value:wxResult,uid,scope决定用户身份权限问题
		*/
		$openid = $wxResult['openid'];
		$user = UserModel::getByOpenId($openid);
		if (!$user){
			$uid = $this->newUser($openid);
		}else{
			$uid = $user->id;
		}
		$cacheValue = $this->prepareCacheValue($wxResult, $uid);
		$token = $this->saveCache($cacheValue);
		return $token;
	}

	//保存至缓存
	private function saveCache($cacheValue){
		$key = self::generateToken();
		$value = json_encode($cacheValue);
		$expire_in = config('setting.token_expire_in');
		$request = cache($key, $value, $expire_in);
		if (!$request){
			throw new TokenException([
				'msg' => '服务器缓存异常',
				'errorCode' => 10005
			]);
		}
		return $key;
	}

	//准备缓存数据
	private function prepareCacheValue($wxResult, $uid){
		$cacheValue = $wxResult;
		$cacheValue['uid'] = $uid;

		//scope = 16 代表普通用户,不同组用户有不同的权限
		$cacheValue['scope'] = ScopeEnum::User;
		return $cacheValue;
	}

	//用户不存在创建新用户
	private function newUser($openid){
		$user = UserModel::create([
			'openid' => $openid
		]);
		return $user->id;
	}

	//抛出异常
	private function processLoginError($wxResult){
		throw new WechatException([
			'msg' => $wxResult['errmsg'],
			'errorCode' => $wxResult['errqcode']
		]);
	}
}