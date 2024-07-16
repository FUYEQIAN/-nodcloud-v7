<?php
namespace app\validate;
use think\Validate;
class Sre extends Validate {
    
    //常规验证规则
    protected $rule = [
        'source' => ['require','integer'],
        'customer' => ['require','integer'],
        'time' => ['require','date'],
        'number' => ['require','unique:sre'],
        'total' => ['require','float'],
        'actual' => ['require','float'],
        'money' => ['require','float'],
        'account' => ['requireWith:money'],
        'people' => ['integer'],
        'logistics' => ['array'],
        'file' => ['array'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'source.require' => '关联单据不可为空!',
        'source.integer' => '关联单据不正确!',
        'customer.require' => '客户不可为空!',
        'customer.integer' => '客户不正确!',
        'time.require' => '单据日期不可为空!',
        'time.date' => '单据日期不正确!',
        'number.require' => '单据编号不可为空!',
        'number.unique' => '单据编号重复!',
        'total.require' => '单据金额不可为空!',
        'total.float' => '单据金额不正确!',
        'actual.require' => '实际金额不可为空!',
        'actual.float' => '实际金额不正确!',
        'money.require' => '实付金额不可为空!',
        'money.float' => '实付金额不正确!',
        'account.requireWith' => '结算账户不可为空!',
        'people.integer' => '关联人员不正确!',
        'logistics.array' => '物流信息不正确!',
        'file.array' => '单据附件不正确!',
        'more.array' => '扩展信息不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['source','customer','time','number','total','actual','money','account','people','logistics','file','more']
    ];
}