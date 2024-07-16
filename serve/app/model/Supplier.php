<?php
namespace app\model;
use	think\Model;
class supplier extends Model{
    //供应商
    
    protected $type = [
        'contacts' => 'json'
    ];
    
    //所属组织关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
    }
    
    //所属用户关联
    public function userData(){
        return $this->hasOne(User::class,'id','user')->field(['id','name']);
    }
	
	//增值税税率_读取器
	public function getRateAttr($val,$data){
        return floatval($val);
	}
	
	//应付款余额_读取器
	public function getBalanceAttr($val,$data){
        return floatval($val);
	}
	
	//扩展信息_设置器
	public function  setMoreAttr($val){
	    //兼容Api|修复PHP空对象json编码为[]
	    return json_encode((object)$val);
	}
	
	//扩展信息_读取器
	public function  getMoreAttr($val){
		return json_decode($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //主联系人
        $contact=json_decode($data['contacts'],true);
        if(empty($contact)){
            $source['contact']='';
        }else{
            $find=search($contact)->where([['main','=',true]])->find();
            $source['contact']=$find['name'].' | '.$find['tel'].' | '.$find['add'];
        }
        return $source;
	}
	
}
