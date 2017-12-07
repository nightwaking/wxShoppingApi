<?php

namespace app\api\controller;

use think\Controller;
use app\api\service\Token as TokenService;

class BaseController extends Controller{

	// 前置操作验证用户权限
	protected function checkPrimaryScope(){
		TokenService::needPrimaryScope();
	}

	// 前置操作进行权限管理
	protected function checkExclusiveScope(){
		TokenService::needExclusiveScope();
	}
}