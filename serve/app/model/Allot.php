<?php
namespace app\model;
use	think\Model;
class Allot extends Model{
    //转账单
    
    protected $type = [
        'time'=>'timestamp:Y-m-d',
        'file'=>'json'
    ];
    
    //所属组织关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
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
        return $this->hasMany(Record::class,'source','id')->with(['userData'])->where([['type','=','allot']])->append(['extension'])->order('id desc');
    }
    
    //单据金额_读取器
	public function getTotalAttr($val,$data){
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
        //审核状态
        $source['examine']=[0=>'未审核',1=>'已审核'][$data['examine']];
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
