<?php
namespace app\validate;
use think\Validate;
class ExtryInfo extends Validate {
    
    //常规验证规则
    protected $rule = [
        'goods' => ['require','integer'],
        'warehouse' => ['integer'],
        'mfd' => ['date'],
        'price' => ['require','float'],
        'nums' => ['require','float'],
        'serial' => ['array'],
        'total' => ['require','float'],
    ];

    //常规规则提示
    protected $message = [
        'goods.require' => '商品信息不可为空!',
        'goods.integer' => '商品信息不正确!',
        'warehouse.integer' => '仓库不正确!',
        'mfd.require' => '生产日期不正确!',
        'price.require' => '成本不可为空!',
        'price.float' => '成本不正确!',
        'nums.require' => '数量不可为空!',
        'nums.float' => '数量不正确!',
        'serial.require' => '序列号不可为空!',
        'serial.array' => '序列号不正确!',
        'total.require' => '总成本不可为空!',
        'total.float' => '总成本不正确!'
    ];
}