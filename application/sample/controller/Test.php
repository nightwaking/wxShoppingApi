<?php
namespace app\sample\controller;

use think\Request;
use think\Validate;


class Test
{
	public function hello()
	{
		//参数获取
		//获取单个参数
		$id = Request::instance()->param('id');
		$name = Request::instance()->param('name');
		$age = Request::instance()->param('age');
		echo $id. "|".$name.$age."\n";
		//获取所有参数
		$all = Request::instance()->param();
		var_dump($all);
		//获取？后的参数 get
		$get = Request::instance()->get();
		var_dump($get);
		//获取路由中的参数
		$route = Request::instance()->route();
		var_dump($route);
		//获取post body的参数
		$post = Request::instance()->post();
		var_dump($post);
		//使用助手函数
		$input_all = input('param.');
		var_dump($input_all);
		$input_name = input('param.name');
		var_dump($input_name);
		echo "<br>";
		

		//数据验证
		//独立验证
		$data = [
			'name' => 'vendor123123',
			'email' => 'vendorqq.com'
		];

		$validate = new Validate([
			'name' => 'require|max:10',
			'email' => 'email'
		]);
		//batch批量验证
		$result = $validate->batch()->check($data);
		var_dump($validate->getError());
		var_dump($result);

		//验证器
		$TestValidate = validate('TestValidate');
		$TestResult = $TestValidate->batch()->check($data);
		var_dump($TestResult);
		var_dump($TestValidate->getError());

		//REST
		//Rspresentational State Transfer:表述性状态转移
		//JSON描述数据
		//无状态

		//SOAP Simple Object Access Protocol
		//XML描述数据

		//RESRFul API 基于REST的API设计理论
		//基于资源，增删改查都只是对资源状态的改变　
		//使用HTTP动词来操作资源　GET：查询 POST：创建 PUT:更新 DELETE：删除　GET: /movie/:mid
		//语义明确

		$banner = new BannerModel();
		$banner = $banner->get($id);

		// 原生：$result = Db::query('select * from banner_item where banner_id = ?', [$id]);
		// 查询构造器 find select update delete insert 数据库的执行方法
		//fetchSql方法查看原生的sql语句并不返回结果
		$result = Db::table('banner_item')
			->where('banner_id', '=', $id)
			->select();
		// 表达式法
		// where('字段名', '表达式', '查询条件')
		// 闭包
		$check = Db::table('banner_item')
			->where(function($query) use ($id){
				$query->where('banner_id', '=', $id);
			})
			->select();
	}
}