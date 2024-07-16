<?php
namespace app\model;
use	think\Model;
class Swap extends Model{
    //调拨单
    
    protected $type = [
        'time'=>'timestamp:Y-m-d',
        'logistics'=>'json',
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
    
    //费用详情关联
    public function costData(){
        return $this->hasMany(Cost::class,'class','id')->with(['ietData'])->where([['type','=','swap']])->append(['extension'])->order('id desc');
    }
    
    //记录关联
    public function recordData(){
        return $this->hasMany(Record::class,'source','id')->with(['userData'])->where([['type','=','swap']])->append(['extension'])->order('id desc');
    }
    
    //单据成本_读取器
	public function getTotalAttr($val,$data){
        return floatval($val);
	}
	
	//单据费用_读取器
	public function getCostAttr($val,$data){
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
        //费用状态
        $source['cse']=[0=>'未结算',1=>'部分结算',2=>'已结算',3=>'无需结算'][$data['cse']];
        //联系信息
        $sceneData=$this->sceneData;
        if(empty($sceneData)){
            $source['contact']='';
        }else{
            $contact=$sceneData['contacts'];
            if(empty($contact)){
                $source['contact']='';
            }else{
                $find=search($contact)->where([['main','=',true]])->find();
                $source['contact']=$find['name'].' | '.$find['tel'].' | '.$find['add'];
            }
        }
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
