<?php
namespace app\controller ;
use app\controller\Acl;
use think\Model;
use app\model\{Goods,RoomInfo,SerialInfo};
use think\facade\Db;
use think\exception\ValidateException;
class Serial extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && isset($input['warehouse']) && is_array($input['warehouse'])){
            //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',$input['warehouse']]])->select()->toArray();
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
            //批次查询
            $serialSql=fastSql($input,[
                [['serial'=>'number'],'fullLike'],
                ['state','fullDec1']
            ]);//构造SQL
            $serialSql[]=['warehouse','in',array_column($warehouse,'id')];
            //查询操作
            if(existFull($input,['batch'])){
                $exists=Db::name('batch')->where([['number','like','%'.$input['batch'].'%'],['id','=',Db::raw('serial.batch')]])->buildSql(false);
                $serial=Db::name('serial')->where($serialSql)->alias('serial')->whereExists($exists)->select()->toArray();
            }else{
                $serial=Db::name('serial')->where($serialSql)->select()->toArray();
            }
            //查询商品
            $sql[]=['id','in',array_column($serial,'goods')];
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
            //库存集合[g:商品|a:属性]
            $gather=['g'=>[],'ga'=>[]];
            //二次匹配
            $serial=search($serial)->where([['goods','in',array_column($info,'id')]])->select();
            //查询库存数据-仓储
            $room=Db::name('room')->where([['id','in',array_unique(array_column($serial,'room'))]])->select()->toArray();
            //构造序列数据
            foreach ($serial as $serialKey=>$serialVo) {
                //商品
                $g=md5_16($serialVo['goods']);
                $gather['g'][$g]=math()->chain($gather['g'][$g]??0)->add(1)->done();
                //判断属性
                $find=search($room)->where([['id','=',$serialVo['room']]])->find();
                if(empty($find['attr'])){
                    $serial[$serialKey]['attr']=null;
                }else{
                    //商品|属性
                    $ga=md5_16($serialVo['goods'].'&'.$find['attr']);
                    $gather['ga'][$ga]=math()->chain($gather['ga'][$ga]??0)->add(1)->done();
                    $serial[$serialKey]['attr']=$find['attr'];
                }
            }
            //匹配数据
            $batch=Db::name('batch')->where([['id','in',array_unique(array_column($serial,'batch'))]])->select()->toArray();
            foreach ($info as $infoKey=>$infoVo) {
                //商品
                $g=md5_16($infoVo['id']);
                $info[$infoKey]['summary']=isset($gather['g'][$g])?$gather['g'][$g]:0;
                if(empty($infoVo['attr'])){
                    $list=search($serial)->where([['goods','=',$infoVo['id']],['attr','=',null]])->select();
                    foreach ($list as $listVo) {
                        $row=[
                            'key'=>md5_16($infoVo['id'].'&'.$listVo['id']),
                            'serial'=>$listVo['id'],
                            'name'=>$listVo['number'],
                            'summary'=>1,
                            'state'=>['未销售','已销售','已调拨','已退货'][$listVo['state']]
                        ];
                        //仓库信息
                        $warehouseFind=search($warehouse)->where([['id','=',$listVo['warehouse']]])->find();
                        $row['warehouse']=$warehouseFind['name'];
                        //批次信息
                        if(empty($listVo['batch'])){
                            $row['batch']='';
                        }else{
                            $batchFind=search($batch)->where([['id','=',$listVo['batch']]])->find();
                            $row['batch']=$batchFind['number'];
                        }
                        $info[$infoKey]['attr'][]=$row;
                    }
                }else{
                    $list=search($serial)->where([['goods','=',$infoVo['id']],['attr','<>',null]])->select();
                    //匹配辅助属性
                    foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                        //商品|属性
                        $ga=md5_16($infoVo['id'].'&'.$attrVo['name']);
                        $info[$infoKey]['attr'][$attrKey]['summary']=isset($gather['ga'][$ga])?$gather['ga'][$ga]:0;
                        $select=search($list)->where([['attr','=',$attrVo['name']]])->select();
                        foreach ($select as $selectVo) {
                            $row=[
                                'key'=>md5_16($infoVo['id'].'&'.$selectVo['id']),
                                'serial'=>$selectVo['id'],
                                'name'=>$selectVo['number'],
                                'summary'=>1,
                                'state'=>['未销售','已销售','已调拨','已退货'][$selectVo['state']]
                            ];
                            //仓库信息
                            $warehouseFind=search($warehouse)->where([['id','=',$selectVo['warehouse']]])->find();
                            $row['warehouse']=$warehouseFind['name'];
                            //批次信息
                            if(empty($selectVo['batch'])){
                                $row['batch']='';
                            }else{
                                $batchFind=search($batch)->where([['id','=',$selectVo['batch']]])->find();
                                $row['batch']=$batchFind['number'];
                            }
                            $info[$infoKey]['attr'][$attrKey]['attr'][]=$row;
                        }
                        if(empty($select))unset($info[$infoKey]['attr'][$attrKey]);
                    }
                    $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
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
    
    //导出
	public function exports(){
		$input=input('get.');
        existFull($input,['warehouse'])||$input['warehouse']=[];
	    if(isset($input['warehouse']) && is_array($input['warehouse'])){
		    pushLog('导出序列列表');//日志
		    //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',$input['warehouse']]])->select()->toArray();
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
            //批次查询
            $serialSql=fastSql($input,[
                [['serial'=>'number'],'fullLike'],
                ['state','fullDec1']
            ]);//构造SQL
            $serialSql[]=['warehouse','in',array_column($warehouse,'id')];
            //查询操作
            if(existFull($input,['batch'])){
                $exists=Db::name('batch')->where([['number','like','%'.$input['batch'].'%'],['id','=',Db::raw('serial.batch')]])->buildSql(false);
                $serial=Db::name('serial')->where($serialSql)->alias('serial')->whereExists($exists)->select()->toArray();
            }else{
                $serial=Db::name('serial')->where($serialSql)->select()->toArray();
            }
            //查询商品
            $sql[]=['id','in',array_column($serial,'goods')];
            //获取总条数
            $count = Goods::where($sql)->whereOr($sqlOr)->count();
            //查询分页数据
            $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            //唯一标识|属性处理
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
            //库存集合[g:商品|a:属性]
            $gather=['g'=>[],'ga'=>[]];
            //二次匹配
            $serial=search($serial)->where([['goods','in',array_column($info,'id')]])->select();
            //查询库存数据-仓储
            $room=Db::name('room')->where([['id','in',array_unique(array_column($serial,'room'))]])->select()->toArray();
            //构造序列数据
            foreach ($serial as $serialKey=>$serialVo) {
                //商品
                $g=md5_16($serialVo['goods']);
                $gather['g'][$g]=math()->chain($gather['g'][$g]??0)->add(1)->done();
                //判断属性
                $find=search($room)->where([['id','=',$serialVo['room']]])->find();
                if(empty($find['attr'])){
                    $serial[$serialKey]['attr']=null;
                }else{
                    //商品|属性
                    $ga=md5_16($serialVo['goods'].'&'.$find['attr']);
                    $gather['ga'][$ga]=math()->chain($gather['ga'][$ga]??0)->add(1)->done();
                    $serial[$serialKey]['attr']=$find['attr'];
                }
            }
            //匹配数据
            $batch=Db::name('batch')->where([['id','in',array_unique(array_column($serial,'batch'))]])->select()->toArray();
            foreach ($info as $infoKey=>$infoVo) {
                //商品
                $g=md5_16($infoVo['id']);
                $info[$infoKey]['summary']=isset($gather['g'][$g])?$gather['g'][$g]:0;
                if(empty($infoVo['attr'])){
                    $list=search($serial)->where([['goods','=',$infoVo['id']],['attr','=',null]])->select();
                    foreach ($list as $listVo) {
                        $row=[
                            'name'=>$listVo['number'],
                            'summary'=>1,
                            'state'=>['未销售','已销售','已调拨','已退货'][$listVo['state']]
                        ];
                        //仓库信息
                        $warehouseFind=search($warehouse)->where([['id','=',$listVo['warehouse']]])->find();
                        $row['warehouse']=$warehouseFind['name'];
                        //批次信息
                        if(empty($listVo['batch'])){
                            $row['batch']='';
                        }else{
                            $batchFind=search($batch)->where([['id','=',$listVo['batch']]])->find();
                            $row['batch']=$batchFind['number'];
                        }
                        $info[$infoKey]['attr'][]=$row;
                    }
                }else{
                    $list=search($serial)->where([['goods','=',$infoVo['id']],['attr','<>',null]])->select();
                    //匹配辅助属性
                    foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                        //商品|属性
                        $ga=md5_16($infoVo['id'].'&'.$attrVo['name']);
                        $info[$infoKey]['attr'][$attrKey]['summary']=isset($gather['ga'][$ga])?$gather['ga'][$ga]:0;
                        $select=search($list)->where([['attr','=',$attrVo['name']]])->select();
                        foreach ($select as $selectVo) {
                            $row=[
                                'name'=>$selectVo['number'],
                                'summary'=>1,
                                'state'=>['未销售','已销售','已调拨','已退货'][$selectVo['state']]
                            ];
                            //仓库信息
                            $warehouseFind=search($warehouse)->where([['id','=',$selectVo['warehouse']]])->find();
                            $row['warehouse']=$warehouseFind['name'];
                            //批次信息
                            if(empty($selectVo['batch'])){
                                $row['batch']='';
                            }else{
                                $batchFind=search($batch)->where([['id','=',$selectVo['batch']]])->find();
                                $row['batch']=$batchFind['number'];
                            }
                            $info[$infoKey]['attr'][$attrKey]['attr'][]=$row;
                        }
                        if(empty($select))unset($info[$infoKey]['attr'][$attrKey]);
                    }
                    $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
                }
            }
		    //结构重组
		    $source=[];
		    foreach ($info as $infoVo) {
		        $source[]=$infoVo;
		        if(!empty($infoVo['attr'])){
		            foreach ($infoVo['attr'] as $attrVo) {
		                $attrVo['name']='|- '.$attrVo['name'];
		                $source[]=$attrVo;
		                if(existFull($attrVo,['attr'])){
        		            foreach ($attrVo['attr'] as $subVo) {
        		                $subVo['name']='|-- '.$subVo['name'];
        		                $source[]=$subVo;
        		            }
        		        }
		            }
		        }
		    }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'序列列表'];
            //表格数据
            $field=array_merge(['name'=>'商品名称','summary'=>'库存数量','state'=>'序列状态','warehouse'=>'所属仓库','batch'=>'所属批次','number'=>'商品编号','spec'=>'规格型号','categoryData|name'=>'商品分类','brand'=>'商品品牌','extension|unit'=>'商品单位','code'=>'商品条码','data'=>'商品备注']);
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
            buildExcel('序列列表',$excel);
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
	
	
	//详情列表
    public function detailRecord(){
        $input=input('post.');
        $sheet=['buy','bre','sell','sre','swapOut','swapEnter','entry','extry'];
        existFull($input,['type'])||$input['type']=$sheet;
        if(existFull($input,['page','limit','serial']) && is_arrays($input,['serial','type']) && arrayInArray($input['type'],$sheet)){
            //构造SQL|serial
            $sql=fastSql($input,[
                [['serial'=>'id'],'fullIn']
            ]);
            //查询仓储数据
            $serial=Db::name('serial')->where($sql)->field(['id'])->select()->toArray();
            if(empty($serial)){
                $count=0;
                $info=[];
            }else{
                //构造SQL|SERIALINFO
                $infoSql=fastSql($input,[['type','fullIn']]);
                $infoSql[]=['pid','in',array_column($serial,'id')];
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
                $count=SerialInfo::alias('info')->where($infoSql)->whereExists($union)->count();
                $info=SerialInfo::with(['sourceData'=>['frameData']])->alias('info')->where($infoSql)->whereExists($union)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
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
		existFull($input,['serial'])||$input['serial']=[];
		existFull($input,['type'])||$input['type']=$sheet;
        if(existFull($input,['serial']) && is_arrays($input,['serial','type']) && arrayInArray($input['type'],$sheet)){
            pushLog('导出序列详情');//日志
            //构造SQL|serial
            $sql=fastSql($input,[
                [['serial'=>'id'],'fullIn']
            ]);
            //查询仓储数据
            $serial=Db::name('serial')->where($sql)->field(['id','number'])->select()->toArray();
            if(empty($serial)){
                $source=[];
            }else{
                //构造SQL|SERIALINFO
                $infoSql=fastSql($input,[['type','fullIn']]);
                $infoSql[]=['pid','in',array_column($serial,'id')];
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
                $source=SerialInfo::with(['sourceData'=>['frameData']])->alias('info')->where($infoSql)->whereExists($union)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'序列详情'];
            //表格数据
            $field=[
                'sourceData|frameData|name'=>'所属组织',
                'sourceData|time'=>'操作时间',
                'extension|type'=>'单据类型',
                'sourceData|number'=>'单据编号'
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
            buildExcel('序列详情',$excel);
        }else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}