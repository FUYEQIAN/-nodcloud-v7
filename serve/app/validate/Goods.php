<?php
namespace app\validate;
use think\Validate;
class Goods extends Validate {
    
    //常规验证规则
    protected $rule = [
        'name' => ['require','unique:goods'],
        'number' => ['require','unique:goods'],
        'category' => ['require','integer'],
        'buy' => ['require','float'],
        'sell' => ['require','float'],
        'stock' => ['require','float'],
        'type' => ['require','integer'],
        'imgs' => ['array'],
        'units' => ['array'],
        'serial' => ['require','boolean'],
        'batch' => ['require','boolean'],
        'protect' => ['require','integer'],
        'threshold' => ['require','integer'],
        'more' => ['array'],
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '商品名称不可为空!',
        'name.unique' => '商品名称重复!',
        'number.require' => '商品编号不可为空!',
        'number.unique' => '商品编号重复!',
        'category.require' => '商品分类不可为空!',
        'category.integer' => '商品分类不正确!',
        'buy.require' => '采购价格不可为空!',
        'buy.float' => '采购价格不正确!',
        'sell.require' => '销售价格不可为空!',
        'sell.float' => '销售价格不正确!',
        'stock.require' => '库存阈值不可为空!',
        'stock.float' => '库存阈值不正确!',
        'type.require' => '商品类型不可为空!',
        'type.integer' => '商品类型不正确!',
        'imgs.array' => '商品图像不正确!',
        'units.array' => '多单位配置不正确!',
        'serial.require' => '序列商品不可为空!',
        'serial.boolean' => '序列商品不正确!',
        'batch.require' => '批次商品不可为空!',
        'batch.boolean' => '批次商品不正确!',
        'protect.require' => '保质期不可为空!',
        'protect.integer' => '保质期不正确!',
        'threshold.require' => '保质期不可为空!',
        'threshold.integer' => '预警阀值不正确!',
        'more.array' => '扩展信息不正确!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','number','category','buy','sell','stock','type','imgs','units','serial','batch','protect','threshold','more'],
        'imports' => ['name','number','buy','sell','stock','type','imgs','units','serial','batch','protect','threshold','more']
    ];
}