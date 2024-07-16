<?php
namespace app\validate;
use think\Validate;
class BillInfo extends Validate {
    
    //常规验证规则
    protected $rule = [
        'source' => ['require','integer'],
        'bill' => ['require'],
        'mold' => ['require'],
        'money' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'source.require' => '关联单据不可为空!',
        'source.integer' => '关联单据不正确!',
        'bill.require' => '核销类型不可为空!',
        'mold.require' => '单据类型不可为空!',
        'money.require' => '核销金额不可为空!',
        'money.float' => '核销金额不正确!'
    ];
}