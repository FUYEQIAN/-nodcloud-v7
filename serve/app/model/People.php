<?php
namespace app\model;
use think\Model;
class People extends Model{
    //人员
    
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
    
    //应收款余额_读取器
    public function getBalanceAttr($val,$data){
        return floatval($val);
    }
    
    //客户积分_读取器
    public function getIntegralAttr($val,$data){
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
        $source['sex']=[0=>'女',1=>'男'][$data['sex']];
        return $source;
    }
}
