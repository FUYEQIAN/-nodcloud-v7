<?php
namespace app\validate;
use think\Validate;
class AllotInfo extends Validate {
    
    //常规验证规则
    protected $rule = [
        'account' => ['require','integer'],
        'tat' => ['require','integer'],
        'money' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'account.require' => '转入账户不可为空!',
        'account.integer' => '转入账户不正确!',
        'tat.require' => '转出账户不可为空!',
        'tat.integer' => '转出账户不正确!',
        'money.require' => '结算金额不可为空!',
        'money.float' => '结算金额不正确!'
    ];
}