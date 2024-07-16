<?php
namespace app\model;
use	think\Model;
class Account extends Model{
    //资金账户
    
    //数据类型转换
    protected $type = [
        'time'=>'timestamp:Y-m-d',
    ];
    
    //组织属性关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
    }
    
    //期初余额_读取器
	public function getInitialAttr($val,$data){
        return floatval($val);
	}
	
	//账户余额_读取器
	public function getBalanceAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //实际余额
        $source['money']=math()->chain($data['balance'])->add($data['initial'])->done();
        return $source;
	}
}
