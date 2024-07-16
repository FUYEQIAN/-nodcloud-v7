<?php
namespace app\model;
use	think\Model;
class Bill extends Model{
    //核销单
    
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
    
    //供应商关联
    public function supplierData(){
        return $this->hasOne(Supplier::class,'id','supplier')->append(['extension']);
    }
	
    //制单人关联
    public function userData(){
        return $this->hasOne(User::class,'id','user')->field(['id','name']);
    }
    
    //关联人员关联
    public function peopleData(){
        return $this->hasOne(People::class,'id','people')->field(['id','name']);
    }
    
    //记录关联
    public function recordData(){
        return $this->hasMany(Record::class,'source','id')->with(['userData'])->where([['type','=','bill']])->append(['extension'])->order('id desc');
    }

    //客户_设置器
	public function  setCustomerAttr($val){
	    return empty($val)?0:$val;
	}
	
	//客户_读取器
	public function  getCustomerAttr($val){
		return empty($val)?null:$val;
	}
	
	//供应商_设置器
	public function  setSupplierAttr($val){
	    return empty($val)?0:$val;
	}
    
    //供应商_读取器
	public function  getSupplierAttr($val){
		return empty($val)?null:$val;
	}
    //总核金额_读取器
	public function getPmyAttr($val,$data){
        return floatval($val);
	}
	
	//总销金额_读取器
	public function getSmpAttr($val,$data){
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
	
	//关联人员_设置器
	public function  setPeopleAttr($val){
	    return empty($val)?0:$val;
	}
	
	//关联人员_读取器
	public function  getPeopleAttr($val){
		return empty($val)?null:$val;
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //核销类型
        $source['type']=[0=>'预收冲应收',1=>'预付冲应付',2=>'应收冲应付',3=>'销退冲销售',4=>'购退冲采购'][$data['type']];
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
