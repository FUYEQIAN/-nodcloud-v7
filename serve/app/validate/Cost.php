<?php
namespace app\validate;
use think\Validate;
class Cost extends Validate {
    
    //常规验证规则
    protected $rule = [
        'iet' => ['require','integer'],
        'money' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'iet.require' => '支出类别不可为空!',
        'iet.integer' => '支出类别不正确!',
        'money.require' => '金额不可为空!',
        'money.float' => '金额不正确!'
    ];
}