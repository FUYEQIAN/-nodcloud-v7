<?php
namespace app\validate;
use think\Validate;
class Oce extends Validate {
    
    //常规验证规则
    protected $rule = [
        'supplier'=>['integer'],
        'time' => ['require','date'],
        'number' => ['require','unique:oce'],
        'total' => ['require','float'],
        'actual' => ['require','float'],
        'account' => ['requireWith:money'],
        'account'=>['require','integer'],
        'people' => ['integer'],
        'file' => ['array'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'supplier.integer' => '供应商不正确!',
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
        'account.integer' => '资金账户不正确!',
        'people.integer' => '关联人员不正确!',
        'file.array' => '单据附件不正确!',
        'more.array' => '扩展信息不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['supplier','time','number','total','actual','money','account','people','file','more']
    ];
}