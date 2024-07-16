<?php
namespace app\validate;
use think\Validate;
class Attr extends Validate {
    
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'buy' => ['require','float'],
        'sell' => ['require','float'],
        'retail' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '属性名称不可为空!',
        'buy.require' => '采购价格不可为空!',
        'buy.float' => '采购价格不正确!',
        'sell.require' => '销售价格不可为空!',
        'sell.float' => '销售价格不正确!',
        'retail.require' => '零售价格不可为空!',
        'retail.float' => '零售价格不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','buy','sell','retail']
    ];
}