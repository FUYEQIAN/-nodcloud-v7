<?php
namespace app\validate;
use think\Validate;
class Attribute extends Validate {
    //常规验证规则
    protected $rule = [
        'name' => ['require','unique:attribute'],
        'info' => ['require','array','checkInfo'],
        'sort' => ['require','integer']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '属性名称不可为空!',
        'name.unique' => '属性名称重复!',
        'info.require' => '属性内容不可为空!',
        'info.array' => '属性内容不正确!',
        'sort.require' => '属性排序不可为空!',
        'sort.integer' => '属性排序不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','info','sort']
    ];
    
    //独立验证器
    protected function checkInfo($value,$rule,$data){
        $column=array_column($value,'name');
        if(count($column)!=count(array_unique($column))){
            $result = '属性内容存在重复!';
        }else if(strpos(json_encode($column),'|')!==false){
            $result = '属性内容不可包含[ | ]保留字符!';
        }else{
            //全局重复判断
            $find=db('attribute_info')->where([['pid','<>',$data['id']],['name','in',$column]])->find();
        	if(empty($find)){
        	    $result=true;
        	}else{
        	    $result='属性内容与其他属性内容重复!';
        	}
        }
        return $result;
    }
}