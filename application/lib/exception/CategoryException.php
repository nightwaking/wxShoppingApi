<?php

namespace app\lib\exception;

class CategoryException extends BaseException{
	public $code = 404;
	public $msg = '指类目不存在，请检查传入id';
	public $errorCode = 50000;
}