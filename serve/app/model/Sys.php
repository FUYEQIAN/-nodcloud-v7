<?php
namespace app\model;
use	think\Model;
class Sys extends Model{
    //系统设置
    
    // 内容字段_设置器
	public function  setInfoAttr($val){
	    //兼容数组数据
	    return is_array($val)?json_encode($val):$val;
	}
	
	//内容字段_读取器
	public function  getInfoAttr($val){
		return isJson($val)?json_decode($val):$val;
	}
}
