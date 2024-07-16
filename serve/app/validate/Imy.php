<?php
namespace app\validate;
use think\Validate;
class Imy extends Validate {
    
    //常规验证规则
    protected $rule = [
        'customer' => ['require','integer'],
        'time' => ['require','date'],
        'number' => ['require','unique:imy'],
        'total' => ['require','float'],
        'people' => ['integer'],
        'file' => ['array'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        
        'customer.require' => '客户不可为空!',
        'customer.integer' => '客户不正确!',
        'time.require' => '单据日期不可为空!',
        'time.date' => '单据日期不正确!',
        'number.require' => '单据编号不可为空!',
        'number.unique' => '单据编号重复!',
        'total.require' => '单据金额不可为空!',
        'total.float' => '单据金额不正确!',
        'people.integer' => '关联人员不正确!',
        'file.array' => '单据附件不正确!',
        'more.array' => '扩展信息不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['customer','time','number','total','people','file','more']
    ];
}