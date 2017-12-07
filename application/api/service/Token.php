<?php

namespace app\api\service;

use think\Request;
use think\Exception;
use think\Cache;
use app\lib\enum\ScopeEnum;
use app\lib\exception\TokenException;
use app\lib\exception\ForbiddenException;

class Token{
	//　定义生成的令牌形式
	public static function generateToken(){
		//32个字符组成随机字符串
		$randChars = getRandChars(32);
		$timeStamp = $_SERVER['REQUEST_TIME_FLOAT'];
		$salt = config('secure.token_salt');

		return md5($randChars.$timeStamp.$salt);
	}

	// 从缓存中获取用户数据
	public static function getCurrentTokenVal($key){
		$token = Request::instance()
			->header('token');
		$vars = Cache::get($token);
		if (!$vars){
			throw new TokenException();
		}else{
			if(!is_array($vars)){
				$vars = json_decode($vars, true);
			}
			if(array_key_exists($key, $vars)){
				return $vars[$key];
			}else{
				return new Exception('尝试获取的Token变量不存在');
			}
		}
	}

	// 获取当前用户的id
	public static function getCurrentUid(){
		$uid = self::getCurrentTokenVal('uid');
		return $uid;
	}

	//用户和cms管理员权限
	public static function needPrimaryScope(){
		$scope = self::getCurrentTokenVal('scope');
		if ($scope){
			if ($scope >= ScopeEnum::User){
				return true;
			}else{
				throw new ForbiddenException();
			}
		}else{
			throw new TokenException();
		}
	}

	//只有用户能访问
	public static function needExclusiveScope(){
		$scope = self::getCurrentTokenVal('scope');
		if ($scope){
			if ($scope == ScopeEnum::User){
				return true;
			}else{
				throw new ForbiddenException();
			}
		}else{
			throw new TokenException();
		}
	}

	//是否为合法操作
	public static function isValidOperate($checkedUID){
		if(!$checkedUID){
			throw new Exception('检测的UID必须存在');
		}
		$currentOperateUID = self::getCurrentUid();
		if ($currentOperateUID == $checkedUID){
			return true;
		}
	}

	// 验证token是否存在
	public static function verifyToken($token){
		$exist = Cache::get($token);
		if ($exist){
			return true;
		}else{
			return flase;
		}
	}
}