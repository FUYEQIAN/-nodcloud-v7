<?php
namespace app\controller;
use app\controller\Acl;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Mrt extends Acl{
    //销售利润表
    public function mpt(){
        $input=input('post.');
        $sheet=['sell','sre'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && isset($input['type']) && is_array($input['mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['user','fullEq'],
                ['people','fullEq']
            ]);
            $sql[]=['examine','=',1];
			$sql=frameScope($sql);//组织数据
			$sql=sqlAuth('sell',$sql);//数据鉴权[结构一致]
			$union=[];
            $tab=['sell','sre'];
            foreach ($tab as $t) {
                if(in_array($t,$input['mold'])){
                    $union[]=Db::name($t)->where($sql)->fieldRaw('"'.$t.'" as mold,id')->buildSql();
                }
            }
            $union=implode(' UNION ALL ',$union);
            $count=DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud')[0]["count"];
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            $bill=['class'=>[],'summary'=>[],'info'=>[],''=>[]];
            foreach ($tab as $t) {
                if(in_array($t,$input['mold'])){
                    $gather=search($record)->where([['mold','=',$t]])->select();
                    $db=['class'=>"app\\model\\".ucfirst($t),'info'=>"app\\model\\".ucfirst($t).'Info'];
                    $bill['class']=array_merge($bill['class'],$db['class']::with(['frameData','customerData','userData','peopleData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
                    $bill['summary']=array_merge($bill['summary'],Db::name('summary')->where([['type','=',$t],['class','in',array_column($gather,'id')]])->field(['type','class','info','bct'])->select()->toArray());
                    //匹配明细
                    if(!empty($input['type'])){
                        $bill['info'][$t]=$db['info']::with(['goodsData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }
                }
            }
            arraySort($bill['class'],'t',SORT_ASC);
            $data=[];
            //匹配数据
            foreach ($bill['class'] as $classVo) {
                $row=$classVo;
                $row['key']=$row['id'].'_'.$row['mold'];
                $row['bill']=['sell'=>'销售单','sre'=>'销售退货单'][$row['mold']];
                if($row['mold']=='sell'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='sre'){
                    $row['total']=math()->chain($row['total'])->mul(-1)->done();
                    $row['actual']=math()->chain($row['actual'])->mul(-1)->done();
                    $row['money']=math()->chain($row['money'])->mul(-1)->done();
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }
                $summary=search($bill['summary'])->where([['type','=',$row['mold']],['class','=',$row['id']]])->select();
                $row['act']=mathArraySum(array_column($summary,'bct'));
                if($row['bill']=='销售退货单' || $row['bill']=='零售退货单'){
                   $row['act']=math()->chain($row['act'])->mul(-1)->done();
                   $row['gpt']=math()->chain($row['actual'])->sub($row['act'])->done();
                   $row['gpr']='-'.(empty($row['actual'])?(empty($row['gpt'])?'0':'-100'):math()->chain($row['gpt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                   $row['npt']=math()->chain($row['gpt'])->sub($row['cost'])->done();
                   $row['npr']='-'.(empty($row['actual'])?(empty($row['npt'])?'0':'-100'):math()->chain($row['npt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                }else{
                    $row['gpt']=math()->chain($row['actual'])->sub($row['act'])->done();
                    $row['gpr']=(empty($row['actual'])?(empty($row['gpt'])?'0':'-100'):math()->chain($row['gpt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                    $row['npt']=math()->chain($row['gpt'])->sub($row['cost'])->done();
                    $row['npr']=(empty($row['actual'])?(empty($row['npt'])?'0':'-100'):math()->chain($row['npt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                }
               
                
                
                
                //匹配明细
                $node=[];
                if(!empty($input['type'])){
                    $list=search($bill['info'][$row['mold']])->where([['pid','=',$row['id']]])->select();
                    foreach ($list as $listVo) {
                        $detail=[
                            'name'=>$listVo['goodsData']['name'],
                            'attr'=>$listVo['attr'],
                            'unit'=>$listVo['unit'],
                            'price'=>$listVo['price'],
                            'nums'=>$listVo['nums'],
                            'dsc'=>$listVo['dsc'],
                            'total'=>$listVo['total'],
                            'tat'=>$listVo['tat'],
                            'tpt'=>$listVo['tpt']
                        ];
                        $calc=[];
                        $calc['act']=empty($listVo['goodsData']['type'])?floatval(search($summary)->where([['info','=',$listVo['id']]])->find()['bct']):0;
                        $calc['gpt']=math()->chain($detail['tpt'])->sub($calc['act'])->done();
                        $calc['gpr']=(empty($detail['tpt'])?(empty($calc['gpt'])?'0':'-100'):math()->chain($calc['gpt'])->div($detail['tpt'])->mul(100)->round(2)->done()).'%';
                        $node[]=['key'=>$row['id'].'_'.$listVo['id'].'_'.$row['mold'],'detail'=>$detail,'act'=>$calc['act'],'gpt'=>$calc['gpt'],'gpr'=>$calc['gpr']];
                    }
                }
                $row['node']=$node;
                $data[]=$row;
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$data
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //销售利润表-导出
    public function mptExports(){
        $input=input('get.');
        $sheet=['sell','sre'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(isset($input['type']) && is_array($input['mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['user','fullEq'],
                ['people','fullEq']
            ]);
            $sql[]=['examine','=',1];
			$sql=frameScope($sql);//组织数据
			$sql=sqlAuth('sell',$sql);//数据鉴权[结构一致]
			$union=[];
            $tab=['sell','sre'];
            foreach ($tab as $t) {
                if(in_array($t,$input['mold'])){
                    $union[]=Db::name($t)->where($sql)->fieldRaw('"'.$t.'" as mold,id')->buildSql();
                }
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            $bill=['class'=>[],'summary'=>[],'info'=>[],''=>[]];
            foreach ($tab as $t) {
                if(in_array($t,$input['mold'])){
                    $gather=search($record)->where([['mold','=',$t]])->select();
                    $db=['class'=>"app\\model\\".ucfirst($t),'info'=>"app\\model\\".ucfirst($t).'Info'];
                    $bill['class']=array_merge($bill['class'],$db['class']::with(['frameData','customerData','userData','peopleData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
                    $bill['summary']=array_merge($bill['summary'],Db::name('summary')->where([['type','=',$t],['class','in',array_column($gather,'id')]])->field(['type','class','info','bct'])->select()->toArray());
                    //匹配明细
                    if(!empty($input['type'])){
                        $bill['info'][$t]=$db['info']::with(['goodsData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }
                }
            }
            arraySort($bill['class'],'t',SORT_ASC);
            $data=[];
            //匹配数据
            foreach ($bill['class'] as $classVo) {
                $row=$classVo;
                $row['bill']=['sell'=>'销售单','sre'=>'销售退货单'][$row['mold']];
                if($row['mold']=='sell'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='sre'){
                    $row['total']=math()->chain($row['total'])->mul(-1)->done();
                    $row['actual']=math()->chain($row['actual'])->mul(-1)->done();
                    $row['money']=math()->chain($row['money'])->mul(-1)->done();
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }
                $summary=search($bill['summary'])->where([['type','=',$row['mold']],['class','=',$row['id']]])->select();
                $row['act']=mathArraySum(array_column($summary,'bct'));
                if($row['bill']=='销售退货单' || $row['bill']=='零售退货单'){
                   $row['act']=math()->chain($row['act'])->mul(-1)->done();
                   $row['gpt']=math()->chain($row['actual'])->sub($row['act'])->done();
                   $row['gpr']='-'.(empty($row['actual'])?(empty($row['gpt'])?'0':'-100'):math()->chain($row['gpt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                   $row['npt']=math()->chain($row['gpt'])->sub($row['cost'])->done();
                   $row['npr']='-'.(empty($row['actual'])?(empty($row['npt'])?'0':'-100'):math()->chain($row['npt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                }else{
                    $row['gpt']=math()->chain($row['actual'])->sub($row['act'])->done();
                    $row['gpr']=(empty($row['actual'])?(empty($row['gpt'])?'0':'-100'):math()->chain($row['gpt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                    $row['npt']=math()->chain($row['gpt'])->sub($row['cost'])->done();
                    $row['npr']=(empty($row['actual'])?(empty($row['npt'])?'0':'-100'):math()->chain($row['npt'])->div($row['actual'])->mul(100)->round(2)->done()).'%';
                }
                //匹配明细
                $node=[];
                if(!empty($input['type'])){
                    $list=search($bill['info'][$row['mold']])->where([['pid','=',$row['id']]])->select();
                    foreach ($list as $listVo) {
                        $detail=[
                            'name'=>$listVo['goodsData']['name'],
                            'attr'=>$listVo['attr'],
                            'unit'=>$listVo['unit'],
                            'price'=>$listVo['price'],
                            'nums'=>$listVo['nums'],
                            'dsc'=>$listVo['dsc'],
                            'total'=>$listVo['total'],
                            'tat'=>$listVo['tat'],
                            'tpt'=>$listVo['tpt']
                        ];
                        $calc=[];
                        $calc['act']=empty($listVo['goodsData']['type'])?floatval(search($summary)->where([['info','=',$listVo['id']]])->find()['bct']):0;
                        $calc['gpt']=math()->chain($detail['tpt'])->sub($calc['act'])->done();
                        $calc['gpr']=(empty($detail['tpt'])?(empty($calc['gpt'])?'0':'-100'):math()->chain($calc['gpt'])->div($detail['tpt'])->mul(100)->round(2)->done()).'%';
                        $node[]=['detail'=>$detail,'act'=>$calc['act'],'gpt'=>$calc['gpt'],'gpr'=>$calc['gpr']];
                    }
                }
                $row['node']=$node;
                $data[]=$row;
            }
            $source=[];
            foreach ($data as $dataVo) {
    	        $source[]=$dataVo;
    	        if(!empty($dataVo['node'])){
    	            foreach ($dataVo['node'] as $node) {
    	                $source[]=$node;
    	            }
    	        }
    	    }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'销售利润表'];
            //表格数据
            $field=[
                [
                    'bill'=>'单据类型',
                    'frameData|name'=>'所属组织',
                    'customerData|name'=>'客户',
                    'time'=>'单据时间',
                    'number'=>'单据编号'
                ],
                [
                    'detail|name'=>'商品名称',
                    'detail|attr'=>'辅助属性',
                    'detail|unit'=>'单位',
                    'detail|price'=>'单价',
                    'detail|nums'=>'数量',
                    'detail|dsc'=>'折扣额',
                    'detail|total'=>'金额',
                    'detail|tat'=>'税额',
                    'detail|tpt'=>'价税合计'
                ],
                [
                    'total'=>'单据金额',
                    'discount'=>'优惠金额',
                    'actual'=>'实际金额',
                    'act'=>'成本',
                    'gpt'=>'毛利润',
                    'gpr'=>'毛利率',
                    'cost'=>'单据费用',
                    'npt'=>'净利润',
                    'npr'=>'净利率',
                    'extension|amount'=>'核销金额',
                    'extension|nucleus'=>'核销状态',
                    'userData|name'=>'制单人',
                    'peopleData|name'=>'关联人员',
                    'data'=>'备注信息'
                ]
            ];
            $field=empty($input['type'])?array_merge($field[0],$field[2]):array_merge($field[0],$field[1],$field[2]);
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
            //汇总数据
            $excel[]=['type'=>'node','info'=>[
                '总数:'.count($source),
            ]];
            //导出execl
            buildExcel('销售利润表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //销售排行表
    public function mot(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                [['name'=>'name|py'],'fullLike'],
                ['number','fullLike']
            ]);
            $goods=Db::name('goods')->where($sql)->select()->toArray();
            //数据匹配
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['goods','in',array_column($goods,'id')];
            $existsSql[]=['examine','=',1];
            $existsSql=frameScope($existsSql);
            $existsSql=sqlAuth('sell',$existsSql);//结构一致
            //多源匹配
            $union=[];
            $tab=['sell','sre'];
            foreach ($tab as $t) {
                $union[0][]=Db::name($t.'_info')->alias('info')->join(['is_'.$t=>'class'],'info.pid=class.id')->where($existsSql)->fieldRaw('goods,attr,sum(nums) as nums')->group(['goods,attr'])->buildSql();
            }
            $union_0=implode(' UNION ALL ',$union[0]);
            $count=DB::query('select count(*) as count from ( SELECT count(*) FROM ('.$union_0.') as f GROUP BY goods,attr ) as nodcloud')[0]["count"];
            $record=DB::query('SELECT * FROM ('.$union_0.') as nodcloud GROUP BY goods,attr ORDER BY `nums` DESC LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            //构造条件|减少查询
            foreach ($existsSql as $k=>$v) {
                if($v[0]=='goods'){
                    $existsSql[$k][2]=array_column($record,'goods');
                    break;
                }
            }
            foreach ($tab as $t) {
                $union[1][]=Db::name($t.'_info')->alias('info')->join(['is_'.$t=>'class'],'info.pid=class.id')->where($existsSql)->fieldRaw('"'.$t.'" as mold,goods,attr,group_concat(info.id) as id,sum(dsc) as dsc,'.($t=='barter'?'0 as tat,sum(info.total) as tpt':'sum(tat) as tat,sum(tpt) as tpt'))->group(['goods,attr'])->buildSql();
            }
            $union_1=implode(' UNION ALL ',$union[1]);
            $list=DB::query('SELECT * FROM ('.$union_1.') as nodcloud');
            //获取数据
            $summary=[];
            foreach ($tab as $t) {
                $gather=explode(',',implode(',',array_column(search($list)->where([['mold','=',$t]])->select(),'id')));
                $summary[$t]=Db::name('summary')->where([['type','=',$t],['info','in',$gather]])->select()->toArray();
            }
            $data=[];
            //统计数据
            foreach ($record as $vo) {
                $row=[];
                $g=search($goods)->where([['id','=',$vo['goods']]])->find();
                $row['goodsData']=$g;
                $row['attr']=$vo['attr'];
                $row['unit']=$g['unit']==-1?'多单位':$g['unit'];
                $base=[['goods','=',$vo['goods']],['attr','=',$vo['attr']]];
                $gather=[];
                foreach ($tab as $t) {
                    $b=search($list)->where(array_merge($base,[['mold','=',$t]]))->find();
                    $s=search($summary[$t])->where(array_merge($base,[['type','=',$t]]))->select();
                    $gather['nums'][$t]=mathArraySum(array_column($s,'nums'));
                    $gather['dsc'][$t]=empty($b)?0:$b['dsc'];
                    $gather['tat'][$t]=empty($b)?0:$b['tat'];
                    $gather['tpt'][$t]=empty($b)?0:$b['tpt'];
                    $gather['bct'][$t]=mathArraySum(array_column($s,'bct'));
                }
                $nums=math()->chain($gather['nums']['sell'])->sub($gather['nums']['sre'])->done();
                $row['nums']=$g['unit']==-1?unitSwitch($nums,json_decode($g['units'],true)):$nums;
                $row['dsc']=math()->chain($gather['dsc']['sell'])->sub($gather['dsc']['sre'])->done();
                $row['tat']=math()->chain($gather['tat']['sell'])->sub($gather['tat']['sre'])->done();
                $row['tpt']=math()->chain($gather['tpt']['sell'])->sub($gather['tpt']['sre'])->done();
                $row['bct']=math()->chain($gather['bct']['sell'])->sub($gather['bct']['sre'])->done();
                $row['uct']=empty($nums)?0:math()->chain($row['bct'])->div($nums)->round(2)->abs()->done();
                $total=math()->chain($row['tpt'])->sub($row['tat'])->done();
                $row['gpt']=math()->chain($total)->sub($row['bct'])->done();
                $row['gpr']=(empty($total)?(empty($row['gpt'])?'0':'-100'):math()->chain($row['gpt'])->div($total)->mul(100)->round(2)->done()).'%';
                $data[]=$row;
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$data
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //销售排行表-导出
    public function motExports(){
        $input=input('get.');
        pushLog('导出销售排行表');//日志
        $sql=fastSql($input,[
            [['name'=>'name|py'],'fullLike'],
            ['number','fullLike']
        ]);
        $goods=Db::name('goods')->where($sql)->select()->toArray();
        //数据匹配
        $existsSql=fastSql($input,[
            [['startTime'=>'time'],'startTime'],
            [['endTime'=>'time'],'endTime']
        ]);
        $existsSql[]=['goods','in',array_column($goods,'id')];
        $existsSql[]=['examine','=',1];
        $existsSql=frameScope($existsSql);
        $existsSql=sqlAuth('buy',$existsSql);//结构一致
        //多源匹配
        $union=[];
        $tab=['sell','sre'];
        foreach ($tab as $t) {
            $union[0][]=Db::name($t.'_info')->alias('info')->join(['is_'.$t=>'class'],'info.pid=class.id')->where($existsSql)->fieldRaw('goods,attr,sum(nums) as nums')->group(['goods,attr'])->buildSql();
        }
        $union_0=implode(' UNION ALL ',$union[0]);
        $count=DB::query('select count(*) as count from ( SELECT count(*) FROM ('.$union_0.') as f GROUP BY goods,attr ) as nodcloud')[0]["count"];
        $record=DB::query('SELECT * FROM ('.$union_0.') as nodcloud GROUP BY goods,attr ORDER BY `nums` DESC');
        //匹配数据
        foreach ($tab as $t) {
            $union[1][]=Db::name($t.'_info')->alias('info')->join(['is_'.$t=>'class'],'info.pid=class.id')->where($existsSql)->fieldRaw('"'.$t.'" as mold,goods,attr,group_concat(info.id) as id,sum(dsc) as dsc,'.($t=='barter'?'0 as tat,sum(info.total) as tpt':'sum(tat) as tat,sum(tpt) as tpt'))->group(['goods,attr'])->buildSql();
        }
        $union_1=implode(' UNION ALL ',$union[1]);
        $list=DB::query('SELECT * FROM ('.$union_1.') as nodcloud');
        //获取数据
        $summary=[];
        foreach ($tab as $t) {
            $gather=explode(',',implode(',',array_column(search($list)->where([['mold','=',$t]])->select(),'id')));
            $summary[$t]=Db::name('summary')->where([['type','=',$t],['info','in',$gather]])->select()->toArray();
        }
        $data=[];
        //统计数据
        foreach ($record as $vo) {
            $row=[];
            $g=search($goods)->where([['id','=',$vo['goods']]])->find();
            $row['goodsData']=$g;
            $row['attr']=$vo['attr'];
            $row['unit']=$g['unit']==-1?'多单位':$g['unit'];
            $base=[['goods','=',$vo['goods']],['attr','=',$vo['attr']]];
            $gather=[];
            foreach ($tab as $t) {
                $b=search($list)->where(array_merge($base,[['mold','=',$t]]))->find();
                $s=search($summary[$t])->where(array_merge($base,[['type','=',$t]]))->select();
                $gather['nums'][$t]=mathArraySum(array_column($s,'nums'));
                $gather['dsc'][$t]=empty($b)?0:$b['dsc'];
                $gather['tat'][$t]=empty($b)?0:$b['tat'];
                $gather['tpt'][$t]=empty($b)?0:$b['tpt'];
                $gather['bct'][$t]=mathArraySum(array_column($s,'bct'));
            }
            $nums=math()->chain($gather['nums']['sell'])->sub($gather['nums']['sre'])->done();
            $row['nums']=$g['unit']==-1?unitSwitch($nums,json_decode($g['units'],true)):$nums;
            $row['dsc']=math()->chain($gather['dsc']['sell'])->sub($gather['dsc']['sre'])->done();
            $row['tat']=math()->chain($gather['tat']['sell'])->sub($gather['tat']['sre'])->done();
            $row['tpt']=math()->chain($gather['tpt']['sell'])->sub($gather['tpt']['sre'])->done();
            $row['bct']=math()->chain($gather['bct']['sell'])->sub($gather['bct']['sre'])->done();
            $row['uct']=empty($nums)?0:math()->chain($row['bct'])->div($nums)->round(2)->abs()->done();
            $total=math()->chain($row['tpt'])->sub($row['tat'])->done();
            $row['gpt']=math()->chain($total)->sub($row['bct'])->done();
            $row['gpr']=(empty($total)?(empty($row['gpt'])?'0':'-100'):math()->chain($row['gpt'])->div($total)->mul(100)->round(2)->done()).'%';
            $data[]=$row;
        }
        $source=$data;
        //开始构造导出数据
        $excel=[];//初始化导出数据
        //标题数据
        $excel[]=['type'=>'title','info'=>'销售排行表'];
        //表格数据
        $field=[
            'goodsData|name'=>'商品名称',
            'attr'=>'辅助属性',
            'goodsData|number'=>'商品编号',
            'goodsData|spec'=>'规格型号',
            'unit'=>'单位',
            'nums'=>'数量',
            'dsc'=>'折扣额',
            'tat'=>'税额',
            'tpt'=>'价税合计',
            'uct'=>'成本',
            'bct'=>'总成本',
            'gpt'=>'毛利润',
            'gpr'=>'毛利率'
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
        //汇总数据
        $excel[]=['type'=>'node','info'=>[
            '总数:'.count($source)
        ]];
        //导出execl
        buildExcel('销售排行表',$excel);
    }
}
