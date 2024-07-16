<?php
namespace app\validate;
use think\Validate;
class Invoice extends Validate {
    
    //常规验证规则
    protected $rule = [
        'type' => ['require'],
        'class' => ['require','integer'],
        'time' => ['require','date'],
        'number' => ['require'],
        'title' => ['require'],
        'money' => ['require','float'],
        'file' => ['array'],
    ];

    //常规规则提示
    protected $message = [
        'type.require' => '单据类型不可为空!',
        'class.require' => '所属单据不可为空!',
        'class.integer' => '所属单据不正确!',
        'time.require' => '开票时间不可为空!',
        'time.date'    => '开票时间不正确!',
        'number.require' => '发票号码不可为空!',
        'title.require' => '发票抬头不可为空!',
        'money.require' => '开票金额不可为空!',
        'money.float'  => '开票金额不正确!',
        'file.array' => '发票附件不正确!'
    ];
}