<?php
namespace app\model;
use	think\Model;
class Batch extends Model{
    //批次号
    
    protected $type = [
        'time'=>'timestamp:Y-m-d'
    ];
	
	//库存数量_读取器
	public function getNumsAttr($val,$data){
        return floatval($val);
	}
}
