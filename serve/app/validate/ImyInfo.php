<?php
namespace app\validate;
use think\Validate;
class ImyInfo extends Validate {
    
    //常规验证规则
    protected $rule = [
        'account' => ['require','integer'],
        'money' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'account.require' => '结算账户不可为空!',
        'account.integer' => '结算账户不正确!',
        'money.require' => '结算金额不可为空!',
        'money.float' => '结算金额不正确!'
    ];
}