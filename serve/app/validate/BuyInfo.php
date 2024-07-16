<?php
namespace app\validate;
use think\Validate;
class BuyInfo extends Validate {
    
    //常规验证规则
    protected $rule = [
        'source' => ['require','integer'],
        'goods' => ['require','integer'],
        'warehouse' => ['integer'],
        'mfd' => ['date'],
        'price' => ['require','float'],
        'nums' => ['require','float'],
        'serial' => ['array'],
        'discount' => ['require','between:0,100'],
        'dsc' => ['require','float'],
        'total' => ['require','float'],
        'tax' => ['require','between:0,100'],
        'tat' => ['require','float'],
        'tpt' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'source.require' => '关联详情不可为空!',
        'source.integer' => '关联详情不正确!',
        'goods.require' => '商品信息不可为空!',
        'goods.integer' => '商品信息不正确!',
        'warehouse.integer' => '仓库不正确!',
        'mfd.require' => '生产日期不正确!',
        'price.require' => '单价不可为空!',
        'price.float' => '单价不正确!',
        'nums.require' => '数量不可为空!',
        'nums.float' => '数量不正确!',
        'serial.require' => '序列号不可为空!',
        'serial.array' => '序列号不正确!',
        'discount.require' => '折扣率不可为空!',
        'discount.between' => '折扣率不正确!',
        'dsc.require' => '折扣额不可为空!',
        'dsc.float' => '折扣额不正确!',
        'total.require' => '金额不可为空!',
        'total.float' => '金额不正确!',
        'tax.require' => '税率不可为空!',
        'tax.between' => '税率不正确!',
        'tat.require' => '税额不可为空!',
        'tat.float' => '税额不正确!',
        'tpt.require' => '价税合计不可为空!',
        'tpt.float' => '价税合计不正确!'
    ];
}