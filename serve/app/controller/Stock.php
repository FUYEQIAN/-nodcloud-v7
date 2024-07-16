<?php
namespace app\controller ;
use app\controller\Acl;
use think\Model;
use app\model\{Goods,RoomInfo};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Stock extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && isset($input['warehouse']) && is_array($input['warehouse']) && isset($input['state']) && in_array($input['state'],[0,1,2])){
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
                ['code','fullLike']
            ]);//构造SQL
            //商品类型
            $sql[]=['type','=',0];
            //辅助属性扩展查询
            $sqlOr=existFull($input,['code'])?[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]]:[];
            //商品分类树结构查询
            existFull($input,['category'])&&$sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
            //查询类型
            if($input['state']==0){
                //获取总条数
                $count = Goods::where($sql)->whereOr($sqlOr)->count();
                //查询分页数据
                $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->page($input['page'],$input['limit'])->append(['extension'])->select()->toArray();
            }elseif($input['state']==1){
                $exists=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['nums','<>',0],['goods','=',Db::raw('goods.id')]])->buildSql(false);
                //获取总条数
                $count = Goods::where($sql)->whereOr($sqlOr)->alias('goods')->whereExists($exists)->count();
                //查询分页数据
                $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->alias('goods')->whereExists($exists)->order(['id'=>'desc'])->page($input['page'],$input['limit'])->append(['extension'])->select()->toArray();
            }else{
                //子查询
                $exists=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['goods','=',Db::raw('goods.id')],['nums','<=',Db::raw('goods.stock')]])->buildSql(false);
                //获取总条数
                $count = Goods::where($sql)->whereOr($sqlOr)->alias('goods')->whereExists($exists)->count();
                //查询分页数据
                $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->alias('goods')->whereExists($exists)->order(['id'=>'desc'])->page($input['page'],$input['limit'])->append(['extension'])->select()->toArray();
            }
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
            //数量匹配|库存处理
            foreach ($info as $infoKey=>$infoVo) {
                //商品
                $g=md5_16($infoVo['id']);
                $info[$infoKey]['summary']=isset($gather['g'][$g])?($infoVo['unit']=='-1'?unitSwitch($gather['g'][$g],$infoVo['units']):$gather['g'][$g]):0;
                //仓库|商品
                foreach ($column as $columnVo) {
                    $wg=md5_16($columnVo['id'].'&'.$infoVo['id']);
                    $info[$infoKey]['stock_'.$columnVo['id']]=isset($gather['wg'][$wg])?($infoVo['unit']=='-1'?unitSwitch($gather['wg'][$wg],$infoVo['units']):$gather['wg'][$wg]):0;
                }
                //匹配辅助属性
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    //商品|属性
                    $ga=md5_16($infoVo['id'].'&'.$attrVo['name']);
                    $info[$infoKey]['attr'][$attrKey]['summary']=isset($gather['ga'][$ga])?($infoVo['unit']=='-1'?unitSwitch($gather['ga'][$ga],$infoVo['units']):$gather['ga'][$ga]):0;
                    //仓库|商品|属性
                    foreach ($column as $columnVo) {
                        $wga=md5_16($columnVo['id'].'&'.$infoVo['id'].'&'.$attrVo['name']);
                        $info[$infoKey]['attr'][$attrKey]['stock_'.$columnVo['id']]=isset($gather['wga'][$wga])?($infoVo['unit']=='-1'?unitSwitch($gather['wga'][$wga],$infoVo['units']):$gather['wga'][$wga]):0;
                        $input['state']==2&&$info[$infoKey]['attr'][$attrKey]['stocks'][]=$info[$infoKey]['attr'][$attrKey]['stock_'.$columnVo['id']];
                    }
                    //非零库存|排除属性为零
                    if($input['state']==1 && $info[$infoKey]['attr'][$attrKey]['summary']==0){
                        unset($info[$infoKey]['attr'][$attrKey]);
                    }
                    //预警库存
                    if($input['state']==2){
                        $exist=false;
                        foreach ($info[$infoKey]['attr'][$attrKey]['stocks'] as $stockVo) {
                            if($stockVo<=$info[$infoKey]['stock']){
                                $exist=true;
                                break;
                            }
                        }
                        if(!$exist)unset($info[$infoKey]['attr'][$attrKey]);
                    }
                }
                //重建索引
                $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
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
		existFull($input,['warehouse'])||$input['warehouse']=[];
		if(isset($input['warehouse']) && is_array($input['warehouse']) && isset($input['state']) && in_array($input['state'],[0,1,2])){
		    pushLog('导出库存列表');//日志
		    //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',$input['warehouse']]])->order(['id'=>'desc'])->select()->toArray();
            //构造表头
            $column=[];
            foreach ($warehouse as $warehouseVo) {
                $column['stock_'.$warehouseVo['id']]=$warehouseVo['name'];
            }
            //匹配商品
            $sql=fastSql($input,[
                [['name'=>'name|py'],'fullLike'],
                ['number','fullLike'],
                ['spec','fullLike'],
                ['brand','fullEq'],
                ['code','fullLike']
            ]);//构造SQL
            //商品类型
            $sql[]=['type','=',0];
            //辅助属性扩展查询
            $sqlOr=existFull($input,['code'])?[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]]:[];
            //商品分类树结构查询
            existFull($input,['category'])&&$sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
            //查询类型
            if($input['state']==0){
                //查询分页数据
                $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            }elseif($input['state']==1){
                $exists=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['nums','<>',0],['goods','=',Db::raw('goods.id')]])->buildSql(false);
                //查询分页数据
                $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->alias('goods')->whereExists($exists)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            }else{
                //子查询
                $exists=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['goods','=',Db::raw('goods.id')],['nums','<=',Db::raw('goods.stock')]])->buildSql(false);
                //获取总条数
                $count = Goods::where($sql)->whereOr($sqlOr)->alias('goods')->whereExists($exists)->count();
                //查询分页数据
                $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->alias('goods')->whereExists($exists)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            }
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
            
            //数量匹配|库存处理
            foreach ($info as $infoKey=>$infoVo) {
                //商品
                $g=md5_16($infoVo['id']);
                $info[$infoKey]['summary']=isset($gather['g'][$g])?($infoVo['unit']=='-1'?unitSwitch($gather['g'][$g],$infoVo['units']):$gather['g'][$g]):0;
                //仓库|商品
                foreach ($warehouse as $warehouseVo) {
                    $wg=md5_16($warehouseVo['id'].'&'.$infoVo['id']);
                    $info[$infoKey]['stock_'.$warehouseVo['id']]=isset($gather['wg'][$wg])?($infoVo['unit']=='-1'?unitSwitch($gather['wg'][$wg],$infoVo['units']):$gather['wg'][$wg]):0;
                    
                }
                //匹配辅助属性
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    //商品|属性
                    $ga=md5_16($infoVo['id'].'&'.$attrVo['name']);
                    $info[$infoKey]['attr'][$attrKey]['summary']=isset($gather['ga'][$ga])?($infoVo['unit']=='-1'?unitSwitch($gather['ga'][$ga],$infoVo['units']):$gather['ga'][$ga]):0;
                    //仓库|商品|属性
                    foreach ($warehouse as $warehouseVo) {
                        $wga=md5_16($warehouseVo['id'].'&'.$infoVo['id'].'&'.$attrVo['name']);
                        $info[$infoKey]['attr'][$attrKey]['stock_'.$warehouseVo['id']]=isset($gather['wga'][$wga])?($infoVo['unit']=='-1'?unitSwitch($gather['wga'][$wga],$infoVo['units']):$gather['wga'][$wga]):0;
                        $input['state']==2&&$info[$infoKey]['attr'][$attrKey]['stocks'][]=$info[$infoKey]['attr'][$attrKey]['stock_'.$warehouseVo['id']];
                    }
                    //非零库存|排除属性为零
                    if($input['state']==1 && $info[$infoKey]['attr'][$attrKey]['summary']==0){
                        unset($info[$infoKey]['attr'][$attrKey]);
                    }
                    //预警库存
                    if($input['state']==2){
                        $exist=false;
                        foreach ($info[$infoKey]['attr'][$attrKey]['stocks'] as $stockVo) {
                            if($stockVo<=$info[$infoKey]['stock']){
                                $exist=true;
                                break;
                            }
                        }
                        if(!$exist)unset($info[$infoKey]['attr'][$attrKey]);
                    }
                }
                //重建索引
                $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
            }
		    //结构重组
		    $source=[];
		    foreach ($info as $infoVo) {
		        $source[]=$infoVo;
		        if(!empty($infoVo['attr'])){
		            foreach ($infoVo['attr'] as $attrVo) {
		                $attrVo['name']='|- '.$attrVo['name'];
		                $source[]=$attrVo;
		            }
		        }
		    }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'库存列表'];
            //表格数据
            $field=array_merge(['name'=>'商品名称','summary'=>'库存数量'],$column,['stock'=>'预警阈值','number'=>'商品编号','spec'=>'规格型号','categoryData|name'=>'商品分类','brand'=>'商品品牌','extension|unit'=>'商品单位','code'=>'商品条码','data'=>'商品备注']);
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
            buildExcel('库存列表',$excel);
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
	//详情列表
    public function detailRecord(){
        $input=input('post.');
        $sheet=['buy','bre','sell','sre','swapOut','swapEnter','entry','extry'];
        existFull($input,['type'])||$input['type']=$sheet;
        if(existFull($input,['page','limit','goods','warehouse']) && is_arrays($input,['warehouse','type']) && arrayInArray($input['type'],$sheet)){
            //构造SQL|ROOM
            $roomSql=fastSql($input,[
                ['warehouse','fullIn'],
                ['goods','fullEq']
            ]);
            isset($input['attr'])&&$roomSql[]=['attr','=',$input['attr']];
            //查询仓储数据
            $room=Db::name('room')->where($roomSql)->field(['id'])->select()->toArray();
            if(empty($room)){
                $count=0;
                $info=[];
            }else{
                //构造SQL|ROOMINFO
                $infoSql=fastSql($input,[['type','fullIn']]);
                $infoSql[]=['pid','in',array_column($room,'id')];
                //子查询SQL
                $existsSql=fastSql($input,[
                    ['number','fullLike'],
                    [['startTime'=>'time'],'startTime'],
                    [['endTime'=>'time'],'endTime']
                ]);
                $existsSql[]=['id','=',Db::raw('info.class')];
                $existsSql=frameScope($existsSql);
                //多源匹配
                $union=[];
                //数据关系表
                $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
                foreach ($table as $k=>$v) {
                    //匹配类型|减少查询
                    if(in_array($k,$input['type'])){
                        $union[]=Db::name($v)->where([['info.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
                    }
                }
                //合并子查询
                $union=implode(' UNION ALL ',$union);
                $count=RoomInfo::alias('info')->where($infoSql)->whereExists($union)->count();
                $info=RoomInfo::with(['sourceData'=>['frameData']])->alias('info')->where($infoSql)->whereExists($union)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
                //处理多单位
                if(!empty($info)){
                    $goods=Db::name('goods')->where([['id','=',$input['goods']]])->find();
                    if($goods['unit']=='-1'){
                        foreach ($info as $infoKey=>$infoVo) {
                            $info[$infoKey]['nums']=unitSwitch($infoVo['nums'],json_decode($goods['units'],true));
                        }
                    }
                }
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$info
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //详情导出
	public function detailExports(){
		$input=input('get.');
		$sheet=['buy','bre','sell','sre','swapOut','swapEnter','entry','extry'];
        existFull($input,['type'])||$input['type']=$sheet;
        if(existFull($input,['goods','warehouse']) && is_arrays($input,['warehouse','type']) && arrayInArray($input['type'],$sheet)){
            pushLog('导出库存详情');//日志
            //商品数据
            $goods=Db::name('goods')->where([['id','=',$input['goods']]])->find();
            //构造SQL|ROOM
            $roomSql=fastSql($input,[
                ['warehouse','fullIn'],
                ['goods','fullEq']
            ]);
            isset($input['attr'])&&$roomSql[]=['attr','=',$input['attr']];
            //查询仓储数据
            $room=Db::name('room')->where($roomSql)->field(['id'])->select()->toArray();
            if(empty($room)){
                $source=[];
            }else{
                //构造SQL|ROOMINFO
                $infoSql=fastSql($input,[['type','fullIn']]);
                $infoSql[]=['pid','in',array_column($room,'id')];
                //子查询SQL
                $existsSql=fastSql($input,[
                    ['number','fullLike'],
                    [['startTime'=>'time'],'startTime'],
                    [['endTime'=>'time'],'endTime']
                ]);
                $existsSql[]=['id','=',Db::raw('info.class')];
                $existsSql=frameScope($existsSql);
                //多源匹配
                $union=[];
                //数据关系表
                $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
                foreach ($table as $k=>$v) {
                	//匹配类型|减少查询
                	if(in_array($k,$input['type'])){
                		$union[]=Db::name($v)->where([['info.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
                	}
                }
                //合并子查询
                $union=implode(' UNION ALL ',$union);
                $source=RoomInfo::with(['sourceData'=>['frameData']])->alias('info')->where($infoSql)->whereExists($union)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
                //处理多单位
                if(!empty($source)){
                    $goods=Db::name('goods')->where([['id','=',$input['goods']]])->find();
                    if($goods['unit']=='-1'){
                        foreach ($source as $sourceKey=>$sourceVo) {
                            $source[$sourceKey]['nums']=unitSwitch($sourceVo['nums'],json_decode($goods['units'],true));
                        }
                    }
                }
            }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'库存详情'];
            //表格数据
            $field=[
                'sourceData|frameData|name'=>'所属组织',
                'sourceData|time'=>'操作时间',
                'extension|type'=>'单据类型',
                'sourceData|number'=>'单据编号',
                'extension|direction'=>'操作类型',
                'nums'=>'操作数量'
            ];
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
            buildExcel('库存详情',$excel);
        }else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}