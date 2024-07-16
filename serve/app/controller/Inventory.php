<?php
namespace app\controller ;
use app\controller\Acl;
use think\Model;
use app\model\{Goods,Warehouse};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Inventory extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && isset($input['warehouse']) && is_array($input['warehouse'])){
            //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',$input['warehouse']]])->order(['id'=>'desc'])->select()->toArray();
            //构造表头|集合
            $column=[];
            foreach ($warehouse as $warehouseVo) {
                $column[]=['id'=>$warehouseVo['id'],'key'=>'stock_'.$warehouseVo['id'],'name'=>$warehouseVo['name']];
            }
            //匹配商品
            $sql=fastSql($input,[
                [['name'=>'name|py'],'fullLike'],
                ['number','fullLike'],
                ['spec','fullLike'],
                ['brand','fullEq'],
                ['code','fullLike'],
                ['data','fullLike']
            ]);//构造SQL
            //商品类型
            $sql[]=['type','=',0];
            //辅助属性扩展查询
            $sqlOr=existFull($input,['code'])?[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]]:[];
            //商品分类树结构查询
            existFull($input,['category'])&&$sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
            //获取总条数
            $count = Goods::where($sql)->whereOr($sqlOr)->count();
            //查询分页数据
            $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->page($input['page'],$input['limit'])->append(['extension'])->select()->toArray();
            //唯一标识|属性处理
            foreach ($info as $infoKey=>$infoVo) {
                $info[$infoKey]['key']=$infoVo['id'];
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    $info[$infoKey]['attr'][$attrKey]['key']=$infoVo['id'].'.'.$attrVo['id'];
                    //属性处理
                    if(existFull($input,['code']) && !in_array($input['code'],[$infoVo['code'],$attrVo['code']])){
                        unset($info[$infoKey]['attr'][$attrKey]);
                    }
                }
                //重建索引
                $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
            }
            //查询库存数据
            $room=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['goods','in',array_column($info,'id')]])->select()->toArray();
            //库存集合[w:仓库|g:商品|a:属性]
            $gather=['g'=>[],'wg'=>[],'ga'=>[],'wga'=>[]];
            foreach ($room as $roomVo) {
                //商品
                $g=md5_16($roomVo['goods']);
                $gather['g'][$g]=math()->chain($gather['g'][$g]??0)->add($roomVo['nums'])->done();
                //仓库|商品
                $wg=md5_16($roomVo['warehouse'].'&'.$roomVo['goods']);
                $gather['wg'][$wg]=math()->chain($gather['wg'][$wg]??0)->add($roomVo['nums'])->done();
                //判断属性
                if(!empty($roomVo['attr'])){
                    //商品|属性
                    $ga=md5_16($roomVo['goods'].'&'.$roomVo['attr']);
                    $gather['ga'][$ga]=math()->chain($gather['ga'][$ga]??0)->add($roomVo['nums'])->done();
                    //仓库|商品|属性
                    $wga=md5_16($roomVo['warehouse'].'&'.$roomVo['goods'].'&'.$roomVo['attr']);
                    $gather['wga'][$wga]=math()->chain($gather['wga'][$wga]??0)->add($roomVo['nums'])->done();
                }
            }
            //数量匹配|库存处理|结构处理
            foreach ($info as $infoKey=>$infoVo) {
                //商品
                $g=md5_16($infoVo['id']);
                $info[$infoKey]['summary']=isset($gather['g'][$g])?($infoVo['unit']=='-1'?unitSwitch($gather['g'][$g],$infoVo['units']):$gather['g'][$g]):0;
                //仓库|商品
                $record=[];
                //单位数据
                if($infoVo['unit']=='-1'){
                    $enter=[];
                    $unit=array_reverse(array_merge([['name'=>$infoVo['units'][0]['source']]],$infoVo['units']));
                    foreach ($unit as $unitVo) {
                        $enter[]=['name'=>$unitVo['name'],'nums'=>''];
                    }
                }else{
                    $enter='';
                }
                foreach ($column as $columnVo) {
                    $wg=md5_16($columnVo['id'].'&'.$infoVo['id']);
                    $record['stock_'.$columnVo['id']]=[
                        'warehouse'=>$columnVo['id'],
                        'basis'=>$gather['wg'][$wg]??0,
                        'basisAlias'=>isset($gather['wg'][$wg])?($infoVo['unit']=='-1'?unitSwitch($gather['wg'][$wg],$infoVo['units']):$gather['wg'][$wg]):0,
                        'enter'=>$enter,
                        'diff'=>0,
                        'diffAlias'=>''
                    ];
                }
                $info[$infoKey]['record']=$record;
                //匹配辅助属性
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    //商品|属性
                    $ga=md5_16($infoVo['id'].'&'.$attrVo['name']);
                    $info[$infoKey]['attr'][$attrKey]['summary']=isset($gather['ga'][$ga])?($infoVo['unit']=='-1'?unitSwitch($gather['ga'][$ga],$infoVo['units']):$gather['ga'][$ga]):0;
                    //仓库|商品|属性
                    $record=[];
                    foreach ($column as $columnVo) {
                        $wga=md5_16($columnVo['id'].'&'.$infoVo['id'].'&'.$attrVo['name']);
                        $record['stock_'.$columnVo['id']]=[
                            'warehouse'=>$columnVo['id'],
                            'basis'=>$gather['wga'][$wga]??0,
                            'basisAlias'=>isset($gather['wga'][$wga])?($infoVo['unit']=='-1'?unitSwitch($gather['wga'][$wga],$infoVo['units']):$gather['wga'][$wga]):0,
                            'enter'=>$enter,
                            'diff'=>0,
                            'diffAlias'=>''
                        ];
                    }
                    $info[$infoKey]['attr'][$attrKey]['record']=$record;
                }
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$info,
                'column'=>$column
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //导出
	public function exports(){
		$input=input('get.');
		pushLog('导出库存盘点单');//日志
        //匹配商品
        $sql=fastSql($input,[
            [['name'=>'name|py'],'fullLike'],
            ['number','fullLike'],
            ['spec','fullLike'],
            ['brand','fullEq'],
            ['code','fullLike'],
            ['data','fullLike']
        ]);//构造SQL
        //商品类型
        $sql[]=['type','=',0];
        //辅助属性扩展查询
        $sqlOr=existFull($input,['code'])?[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]]:[];
        //商品分类树结构查询
        existFull($input,['category'])&&$sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
        //查询分页数据
        $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
        //属性处理
        foreach ($info as $infoKey=>$infoVo) {
            foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                //属性处理
                if(existFull($input,['code']) && !in_array($input['code'],[$infoVo['code'],$attrVo['code']])){
                    unset($info[$infoKey]['attr'][$attrKey]);
                }
            }
            //重建索引
            $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
        }
	    //结构重组
	    $source=[];
	    foreach ($info as $infoVo) {
	        $infoVo['enter']='';
	        $source[]=$infoVo;
	        if(!empty($infoVo['attr'])){
	            foreach ($infoVo['attr'] as $attrVo) {
	                $attrVo['name']='|- '.$attrVo['name'];
	                $attrVo['enter']='';
	                $source[]=$attrVo;
	            }
	        }
	    }
        //开始构造导出数据
        $excel=[];//初始化导出数据
        //标题数据
        $excel[]=['type'=>'title','info'=>'库存盘点单'];
        //表格数据
        $field=['name'=>'商品名称','enter'=>'盘点数','number'=>'商品编号','spec'=>'规格型号','categoryData|name'=>'商品分类','brand'=>'商品品牌','extension|unit'=>'商品单位','code'=>'商品条码','data'=>'商品备注'];
        $thead=array_values($field);//表格标题
        $tbody=[];//表格内容
        //构造表内数据
        foreach ($source as $sourceVo) {
            $rowData=[];
            foreach (array_keys($field) as $fieldVo) {
                $rowData[]=arraySeek($sourceVo,$fieldVo);//多键名数据赋值
            }
        	$tbody[]=$rowData;//加入行数据
        }
        $excel[]=['type'=>'table','info'=>['thead'=>$thead,'tbody'=>$tbody]];//表格数据
        //导出execl
        buildExcel('库存盘点单',$excel);
	}
	//生成盘盈单
    public function buildEntry(){
        $input=input('post.');
        if(existFull($input,['info'])){
            $class=['total'=>0,'type'=>1];
            $fun=getSys('fun');
            $list=[
                'goods'=>Goods::where([['id','in',array_unique(array_column($input['info'],'goods'))]])->select()->toArray(),
                'warehouse'=>Warehouse::where([['id','in',array_unique(array_column($input['info'],'warehouse'))]])->select()->toArray(),
            ];
            foreach ($input['info'] as $infoVo) {
                $record=[];
                $goods=search($list['goods'])->where([['id','=',$infoVo['goods']]])->find();
                $warehouse=search($list['warehouse'])->where([['id','=',$infoVo['warehouse']]])->find();
                $record['goods']=$infoVo['goods'];
                $record['goodsData']=$goods;
                $record['attr']=$infoVo['attr'];
                $record['unit']=$infoVo['unit'];
                $record['warehouse']=$infoVo['warehouse'];
                $record['warehouseData']=$warehouse;
                $record['batch']='';
                $record['mfd']='';
                $record['price']=$goods['buy'];
                $record['nums']=$infoVo['nums'];
                $record['serial']=[];
                $record['total']=math()->chain($record['price'])->mul($record['nums'])->round($fun['digit']['money'])->done();
                $record['data']='';
                //转存数据
                $info[]=$record;
                $class['total']=math()->chain($class['total'])->add($record['total'])->done();//累加单据成本
            }
            $result=['state'=>'success','info'=>['class'=>$class,'info'=>$info]];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //生成盘亏单
    public function buildExtry(){
        $input=input('post.');
        if(existFull($input,['info']) && is_array($input['info'])){
            $class=['total'=>0,'type'=>1];
            $fun=getSys('fun');
            $list=[
                'goods'=>Goods::where([['id','in',array_unique(array_column($input['info'],'goods'))]])->select()->toArray(),
                'warehouse'=>Warehouse::where([['id','in',array_unique(array_column($input['info'],'warehouse'))]])->select()->toArray(),
            ];
            foreach ($input['info'] as $infoVo) {
                $record=[];
                $goods=search($list['goods'])->where([['id','=',$infoVo['goods']]])->find();
                $warehouse=search($list['warehouse'])->where([['id','=',$infoVo['warehouse']]])->find();
                $record['goods']=$infoVo['goods'];
                $record['goodsData']=$goods;
                $record['attr']=$infoVo['attr'];
                $record['unit']=$infoVo['unit'];
                $record['warehouse']=$infoVo['warehouse'];
                $record['warehouseData']=$warehouse;
                $record['batch']='';
                $record['mfd']='';
                $record['price']=$goods['buy'];
                $record['nums']=abs($infoVo['nums']);
                $record['serial']=[];
                $record['total']=math()->chain($record['price'])->mul($record['nums'])->round($fun['digit']['money'])->done();
                $record['data']='';
                //转存数据
                $info[]=$record;
                $class['total']=math()->chain($class['total'])->add($record['total'])->done();//累加单据成本
            }
            $result=['state'=>'success','info'=>['class'=>$class,'info'=>$info]];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
}