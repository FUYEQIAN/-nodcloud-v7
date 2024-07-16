<?php
namespace app\validate;
use think\Validate;
class Bor extends Validate {
    
    //常规验证规则
    protected $rule = [
        'supplier' => ['require','integer'],
        'time' => ['require','date'],
        'number' => ['require','unique:bor'],
        'total' => ['require','float'],
        'actual' => ['require','float'],
        'people' => ['integer'],
        'arrival' => ['date'],
        'logistics' => ['array'],
        'file' => ['array'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'supplier.require' => '供应商不可为空!',
        'supplier.integer' => '供应商不正确!',
        'time.require' => '单据日期不可为空!',
        'time.date' => '单据日期不正确!',
        'number.require' => '单据编号不可为空!',
        'number.unique' => '单据编号重复!',
        'total.require' => '单据金额不可为空!',
        'total.float' => '单据金额不正确!',
        'actual.require' => '实际金额不可为空!',
        'actual.float' => '实际金额不正确!',
        'people.integer' => '关联人员不正确!',
        'arrival.date' => '单据日期不正确!',
        'logistics.array' => '物流信息不正确!',
        'file.array' => '单据附件不正确!',
        'more.array' => '扩展信息不正确!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['supplier','time','number','total','actual','people','arrival','logistics','file','more']
    ];
}