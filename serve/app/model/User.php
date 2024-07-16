<?php
namespace app\model;
use	think\Model;
class User extends Model{
    //用户
    
    //组织属性关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
    }
    
    //所属角色关联
    public function roleData(){
        return $this->hasOne(Role::class,'id','role')->field(['id','name']);
    }
    
    //用户密码_设置器
	public function setPwdAttr($val){
		return md5($val);
	}
	
	//用户角色_设置器
	public function setRoleAttr($val){
		return empty($val)?0:$val;
	}
	
    //扩展信息_设置器
	public function setMoreAttr($val){
	    //兼容Api|修复PHP空对象json编码为[]
	    return json_encode((object)$val);
	}
	
	//扩展信息_读取器
	public function getMoreAttr($val){
		return json_decode($val);
	}
}
