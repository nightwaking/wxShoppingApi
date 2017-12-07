<?php

namespace app\lib\exception;

//Runtime和think\Exception的基类Exception
use Exception;
use think\Request;
use think\Log;
use think\exception\Handle;


class ExceptionHandler extends Handle
{
	private $code;
	private $msg;
	private $errorCode;

	/**
	*	重写异常抛出方法
	*	修改congfig中的exception_handle
	*/
	public function render(\Exception $e)
	{
		if($e instanceof BaseException){
			$this->code = $e->code;
			$this->msg = $e->msg;
			$this->errorCode = $e->errorCode;
		}else{
			//将未知进行分类　分为客户端和服务器端两种显示格式
			if (config('app_debug')){
				//调用父类的render方法
				return parent::render($e);
			}else{
				$this->code = 500;
				$this->msg = '服务器内部错误，联系官方';
				$this->errorCode = 999;
				$this->recordErrorLog($e);
			}
		}

		$request = Request::instance();
		$result = [
			'msg' => $this->msg,
			'error_code' => $this->errorCode,
			'request_url' => $request->url()
		];

		return json($result, $this->code);
	}

	//日志记录
	private function recordErrorLog(\Exception $e)
	{
		Log::init([
			'type' => 'File',
			'path' => LOG_PATH,
			'level' => ['error']
		]);
		Log::record($e->getMessage(), 'error');
	}
}