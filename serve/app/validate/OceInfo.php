<?php
namespace app\validate;
use think\Validate;
class OceInfo extends Validate {
    
   //常规验证规则
    protected $rule = [
        'source' => ['require','integer'],
        'iet' => ['require','integer'],
        'money' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'source.require' => '关联详情不可为空!',
        'source.integer' => '关联详情不正确!',
        'iet.require' => '收入类型不可为空!',
        'iet.integer' => '收入类型不正确!',
        'money.require' => '结算金额不可为空!',
        'money.float' => '结算金额不正确!'
    ];
}