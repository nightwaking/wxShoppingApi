<?php
namespace app\api\controller\v1;

use app\api\model\Banner as BannerModel;
use app\lib\exception\BannerMissException;
use think\Exception;
use app\api\validate\IDMustBePostiveInt;

class Banner
{
	/**
	*  获取指定id的banner信息
	*　　@id banner的id号
	*  @url  /banner/:id
	*  @http GET
	*/
	public function getBanner($id)
	{	
		(new IDMustBePostiveInt())->goCheck();
		$banner = BannerModel::getBannerByID($id);
		//hidden visible
		if (!$banner){
			throw new BannerMissException();
		}
		return $banner;
	}
}