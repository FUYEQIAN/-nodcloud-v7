<?php
namespace app\model;
use	think\Model;
class Imy extends Model{
    //收款单
    
    protected $type = [
        'time'=>'timestamp:Y-m-d',
        'file'=>'json'
    ];
    
    //所属组织关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
    }
    
    //客户关联
    public function customerData(){
        return $this->hasOne(Customer::class,'id','customer')->append(['extension']);
    }
    
    //关联人员关联
    public function peopleData(){
        return $this->hasOne(People::class,'id','people')->field(['id','name']);
    }
	
    //制单人关联
    public function userData(){
        return $this->hasOne(User::class,'id','user')->field(['id','name']);
    }
    
    //核销关联
    public function billData(){
        return $this->hasMany(ImyBill::class,'pid','id')->with(['sourceData'])->visible(['sourceData'=>['id','number']])->append(['extension'])->order('id desc');
    }
    
    //记录关联
    public function recordData(){
        return $this->hasMany(Record::class,'source','id')->with(['userData'])->where([['type','=','imy']])->append(['extension'])->order('id desc');
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
        //核销状态
        $source['nucleus']=[0=>'未核销',1=>'部分核销',2=>'已核销'][$data['nucleus']];
        //已核销金额
        if($data['nucleus']==0){
            $source['amount']=0;
        }else if($data['nucleus']==1){
            $source['amount']=db('imy_bill')->where([['pid','=',$data['id']]])->sum('money');
        }else{
            $source['amount']=floatval($data['total']);
        }
        //未核销金额
        $source['anwo']=math()->chain($data['total'])->sub($source['amount'])->done();
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
