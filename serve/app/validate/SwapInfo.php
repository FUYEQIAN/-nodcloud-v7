<?php
namespace app\validate;
use think\Validate;
class SwapInfo extends Validate {
    
    //常规验证规则
    protected $rule = [
        'goods' => ['require','integer'],
        'warehouse' => ['integer'],
        'storehouse' => ['integer'],
        'mfd' => ['date'],
        'price' => ['require','float'],
        'nums' => ['require','float'],
        'serial' => ['array'],
    ];

    //常规规则提示
    protected $message = [
        'goods.require' => '商品信息不可为空!',
        'goods.integer' => '商品信息不正确!',
        'warehouse.integer' => '调出仓库不正确!',
        'storehouse.integer' => '调入仓库不正确!',
        'mfd.require' => '生产日期不正确!',
        'price.require' => '单价不可为空!',
        'price.float' => '单价不正确!',
        'nums.require' => '数量不可为空!',
        'nums.float' => '数量不正确!',
        'serial.require' => '序列号不可为空!',
        'serial.array' => '序列号不正确!'
    ];
}