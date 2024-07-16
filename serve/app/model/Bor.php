<?php
namespace app\model;
use	think\Model;
class Bor extends Model{
    //采购订单
    
    protected $type = [
        'time'=>'timestamp:Y-m-d',
        'arrival'=>'timestamp:Y-m-d',
        'logistics'=>'json',
        'file'=>'json'
    ];
    
    //所属组织关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
    }
    
    //供应商关联
    public function supplierData(){
        return $this->hasOne(Supplier::class,'id','supplier')->append(['extension']);
    }
    
    //关联人员关联
    public function peopleData(){
        return $this->hasOne(People::class,'id','people')->field(['id','name']);
    }
    
    //制单人关联
    public function userData(){
        return $this->hasOne(User::class,'id','user')->field(['id','name']);
    }
    
    //记录关联
    public function recordData(){
        return $this->hasMany(Record::class,'source','id')->with(['userData'])->where([['type','=','bor']])->append(['extension'])->order('id desc');
    }
    
    //单据金额_读取器
	public function getTotalAttr($val,$data){
        return floatval($val);
	}
	
	//实际金额_读取器
	public function getActualAttr($val,$data){
        return floatval($val);
	}
    
    //关联人员_设置器
	public function  setPeopleAttr($val){
	    return empty($val)?0:$val;
	}
	
	//关联人员_读取器
	public function  getPeopleAttr($val){
		return empty($val)?null:$val;
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
        //物流信息
        $logistics=json_decode($data['logistics'],true);
        if(empty($logistics['key'])){
            $source['logistics']='';
        }elseif($logistics['key']=='auto'){
            $source['logistics']=$logistics['number'];
        }else{
            $source['logistics']=$logistics['name'].'|'.$logistics['number'];
        }
        //审核状态
        $source['examine']=[0=>'未审核',1=>'已审核'][$data['examine']];
        //入库状态
        $source['state']=[0=>'未入库',1=>'部分入库',2=>'已入库',3=>'关闭'][$data['state']];
        return $source;
	}
	
	//EVENT|更新前
    public static function onBeforeUpdate($model){
        $source=$model::where([['id','=',$model['id']]])->find();
        if(!empty($source['examine'])){
            exit(json(['state'=>'error','info'=>'[ ERROR ] 单据已审核!'],200)->send());
        }
    }
}
