<?php
namespace app\validate;
use think\Validate;
class Bill extends Validate {
    
    //常规验证规则
    protected $rule = [
        'customer' => ['integer'],
        'supplier' => ['integer'],
        'time' => ['require','date'],
        'number' => ['require','unique:bill'],
        'type' => ['require'],
        'pmy' => ['require','float'],
        'smp' => ['require','float'],
        'people' => ['integer'],
        'file' => ['array'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'customer.integer' => '客户不正确!',
        'supplier.integer' => '供应商不正确!',
        'time.require' => '单据日期不可为空!',
        'time.date' => '单据日期不正确!',
        'number.require' => '单据编号不可为空!',
        'number.unique' => '单据编号重复!',
        'type.require' => '核销类型不可为空!',
        'pmy.require' => '总核金额不可为空!',
        'pmy.float' => '总核金额不正确!',
        'smp.require' => '总销金额不可为空!',
        'smp.float' => '总销金额不正确!',
        'people.integer' => '关联人员不正确!',
        'file.array' => '单据附件不正确!',
        'more.array' => '扩展信息不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['customer','supplier','time','number','type','pmy','smp','people','file','more']
    ];
}