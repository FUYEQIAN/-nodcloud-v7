<?php
namespace app\controller;
use app\controller\Acl;
use app\model\AccountInfo;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Crt extends Acl{
    //现金银行报表
    public function cbf(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=[];
            //CLASS语句
            $sql=fastSql($input,[
                [['account'=>'id'],'fullEq'],
            ]);
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('account',$sql);//数据鉴权
            //数据查询
            $count=Db::name('account')->where($sql)->count();
            $data=Db::name('account')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['user','fullEq']
            ]);
            $existsSql[]=['id','=',Db::raw('info.class')];
            $existsSql=frameScope($existsSql);
            //多源匹配
            $union=[];
            $where=[['pid','in',array_column($data,'id')]];
            //数据关系表
            $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','imy'=>'imy','omy'=>'omy','allotOut'=>'allot','allotEnter'=>'allot','ice'=>'ice','oce'=>'oce'];
            foreach ($table as $k=>$v) {
                $unionSql=array_merge([['info.type','=',$k]],$existsSql);
                if(existFull($input,['supplier','customer'])){
                    //供应商-客户
                    if(!in_array($v,['allot'])){
                        in_array($v,['buy','bre','omy','oce'])&&$unionSql=array_merge($unionSql,fastSql($input,[['supplier','fullEq']]));
                        in_array($v,['sell','sre','imy','ice'])&&$unionSql=array_merge($unionSql,fastSql($input,[['customer','fullEq']]));
                        $unionSql=sqlAuth($v,$unionSql);
                        $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                    }
                    count($where)==1&&$where[]=['type','in',['buy','bre','omy','oce','sell','sre','imy','ice']];
                }else{
                    if(existFull($input,['supplier'])){
                        //供应商
                        if(in_array($v,['buy','bre','omy','oce'])){
                            $unionSql=array_merge($unionSql,fastSql($input,[['supplier','fullEq']]));
                            $unionSql=sqlAuth($v,$unionSql);
                            $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                        }
                        count($where)==1&&$where[]=['type','in',['buy','bre','omy','oce']];
                    }elseif(existFull($input,['customer'])){
                        //客户
                        if(in_array($v,['sell','sre','imy','ice'])){
                            $unionSql=array_merge($unionSql,fastSql($input,[['customer','fullEq']]));
                            $unionSql=sqlAuth($v,$unionSql);
                            $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                        }
                        count($where)==1&&$where[]=['type','in',['sell','sre','imy','ice']];
                    }else{
                        //空
                        $unionSql=sqlAuth($v,$unionSql);
                        $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                    }
                }
            }
            //合并子查询
            $union=implode(' UNION ALL ',$union);
            $infoList=AccountInfo::with(['sourceData'=>['frameData','userData']])->alias('info')->where($where)->whereExists($union)->append(['extension'])->order('time asc')->select()->toArray();
            //匹配往来单位
            $currentList=['customer'=>[],'supplier'=>[]];
            //匹配客戶
            foreach (search($infoList)->where([['type','in',['sell','sre','imy','ice']]])->select() as $item) {
                $currentList['customer'][]=$item['sourceData']['customer'];
            }
            empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
            //匹配供应商
            foreach (search($infoList)->where([['type','in',['buy','bre','omy','oce']]])->select() as $item) {
                $currentList['supplier'][]=$item['sourceData']['supplier'];
            }
            empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
            //匹配节点
            foreach($data as $key => $vo){
                $data[$key]['key'] = $vo['id'];
                $data[$key]['number'] = '';
                $data[$key]['time'] = '';
                $node = search($infoList)->where([['pid', '=', $vo['id']]])->select();
                //计算期初
                $stats=Db::name('account_info')->where([['pid','=',$vo['id']],['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]])->group('direction')->fieldRaw('direction,sum(money) as money')->order('direction desc')->select()->toArray();
                if(empty($stats)){
                    $balance=floatval($vo['initial']);
                }else if(count($stats)==1){
                    if(empty($stats[0]['direction'])){
                        //纯出
                        $balance=math()->chain($stats[0]['money'])->mul(-1)->add($vo['initial'])->done();
                    }else{
                        //纯入
                        $balance=math()->chain($stats[0]['money'])->add($vo['initial'])->done();
                    }
                }else{
                    $balance=math()->chain($stats[0]['money'])->sub($stats[1]['money'])->add($vo['initial'])->done();
                }
                //赋值期初
                array_unshift($node,['key'=>$vo['id'].'_'.'0','extension'=>['type'=>'期初余额'],'balance'=>$balance]);
                //节点赋值
                foreach($node as $nodeKey => $nodeVo){
                    //排除期初  
                    if(!empty($nodeKey)){
                        $node[$nodeKey]['key'] = $nodeVo['pid']."_".$nodeVo['id'];
                        //类型判断
                        if(empty($nodeVo['direction'])){
                            $node[$nodeKey]['in'] = 0;
                            $node[$nodeKey]['out'] = $nodeVo['money'];
                            $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->sub($nodeVo['money'])->done();
                        }else{
                            $node[$nodeKey]['in'] = $nodeVo['money'];
                            $node[$nodeKey]['out'] = 0;
                            $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->add($nodeVo['money'])->done();
                        }
                        //往来单位
                        if(in_array($nodeVo['type'],['buy','bre','omy','oce'])){
                            $node[$nodeKey]['current']=search($currentList['supplier'])->where([['id','=',$nodeVo['sourceData']['supplier']]])->find();
                        }else if(in_array($nodeVo['type'],['sell','sre','imy','ice'])){
                            $node[$nodeKey]['current']=search($currentList['customer'])->where([['id','=',$nodeVo['sourceData']['customer']]])->find();
                        }else{
                            $node[$nodeKey]['current']=[];
                        }
                    }
                }
                //汇总数据
                $data[$key]['in']=mathArraySum(array_column($node,'in'));
                $data[$key]['out']=mathArraySum(array_column($node,'out'));
                $data[$key]['balance']=$node[count($node)-1]['balance'];
                $data[$key]['node'] = $node;
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
    //现金银行报表-导出
    public function cbfExports(){
        $input=input('get.');
        $sql=[];
        //CLASS语句
        $sql=fastSql($input,[
            [['account'=>'id'],'fullEq'],
        ]);
        $sql=frameScope($sql);//组织数据
        $sql=sqlAuth('account',$sql);//数据鉴权
        //数据查询
        $data=Db::name('account')->where($sql)->order(['id'=>'desc'])->select()->toArray();
        //子查询
        $existsSql=fastSql($input,[
            [['startTime'=>'time'],'startTime'],
            [['endTime'=>'time'],'endTime'],
            ['user','fullEq']
        ]);
        $existsSql[]=['id','=',Db::raw('info.class')];
        $existsSql=frameScope($existsSql);
        //多源匹配
        $union=[];
        $where=[['pid','in',array_column($data,'id')]];
        //数据关系表
        $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','imy'=>'imy','omy'=>'omy','allotOut'=>'allot','allotEnter'=>'allot','ice'=>'ice','oce'=>'oce'];
        foreach ($table as $k=>$v) {
            $unionSql=array_merge([['info.type','=',$k]],$existsSql);
            if(existFull($input,['supplier','customer'])){
                //供应商-客户
                if(!in_array($v,['allot'])){
                    in_array($v,['buy','bre','omy','oce'])&&$unionSql=array_merge($unionSql,fastSql($input,[['supplier','fullEq']]));
                    in_array($v,['sell','sre','imy','ice'])&&$unionSql=array_merge($unionSql,fastSql($input,[['customer','fullEq']]));
                    $unionSql=sqlAuth($v,$unionSql);
                    $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                }
                count($where)==1&&$where[]=['type','in',['buy','bre','omy','oce','sell','sre','imy','ice']];
            }else{
                if(existFull($input,['supplier'])){
                    //供应商
                    if(in_array($v,['buy','bre','omy','oce'])){
                        $unionSql=array_merge($unionSql,fastSql($input,[['supplier','fullEq']]));
                        $unionSql=sqlAuth($v,$unionSql);
                        $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                    }
                    count($where)==1&&$where[]=['type','in',['buy','bre','omy','oce']];
                }elseif(existFull($input,['customer'])){
                    //客户
                    if(in_array($v,['sell','sre','imy','ice'])){
                        $unionSql=array_merge($unionSql,fastSql($input,[['customer','fullEq']]));
                        $unionSql=sqlAuth($v,$unionSql);
                        $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                    }
                    count($where)==1&&$where[]=['type','in',['sell','sre','imy','ice']];
                }else{
                    //空
                    $unionSql=sqlAuth($v,$unionSql);
                    $union[]=Db::name($v)->where($unionSql)->limit(1)->buildSql();
                }
            }
        }
        //合并子查询
        $union=implode(' UNION ALL ',$union);
        $infoList=AccountInfo::with(['sourceData'=>['frameData','userData']])->alias('info')->where($where)->whereExists($union)->append(['extension'])->order('time asc')->select()->toArray();
        //匹配往来单位
        $currentList=['customer'=>[],'supplier'=>[]];
        //匹配客戶
        foreach (search($infoList)->where([['type','in',['sell','sre','imy','ice']]])->select() as $item) {
            $currentList['customer'][]=$item['sourceData']['customer'];
        }
        empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
        //匹配供应商
        foreach (search($infoList)->where([['type','in',['buy','bre','omy','oce']]])->select() as $item) {
            $currentList['supplier'][]=$item['sourceData']['supplier'];
        }
        empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
        //匹配节点
        foreach($data as $key => $vo){
            $data[$key]['key'] = $vo['id'];
            $data[$key]['number'] = '';
            $data[$key]['time'] = '';
            $node = search($infoList)->where([['pid', '=', $vo['id']]])->select();
            //计算期初
            $stats=Db::name('account_info')->where([['pid','=',$vo['id']],['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]])->group('direction')->fieldRaw('direction,sum(money) as money')->order('direction desc')->select()->toArray();
            if(empty($stats)){
                $balance=floatval($vo['initial']);
            }else if(count($stats)==1){
                if(empty($stats[0]['direction'])){
                    //纯出
                    $balance=math()->chain($stats[0]['money'])->mul(-1)->add($vo['initial'])->done();
                }else{
                    //纯入
                    $balance=math()->chain($stats[0]['money'])->add($vo['initial'])->done();
                }
            }else{
                $balance=math()->chain($stats[0]['money'])->sub($stats[1]['money'])->add($vo['initial'])->done();
            }
            //赋值期初
            array_unshift($node,['key'=>$vo['id'].'_'.'0','extension'=>['type'=>'期初余额'],'balance'=>$balance]);
            //节点赋值
            foreach($node as $nodeKey => $nodeVo){
                //排除期初  
                if(!empty($nodeKey)){
                    $node[$nodeKey]['key'] = $nodeVo['pid']."_".$nodeVo['id'];
                    //类型判断
                    if(empty($nodeVo['direction'])){
                        $node[$nodeKey]['in'] = 0;
                        $node[$nodeKey]['out'] = $nodeVo['money'];
                        $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->sub($nodeVo['money'])->done();
                    }else{
                        $node[$nodeKey]['in'] = $nodeVo['money'];
                        $node[$nodeKey]['out'] = 0;
                        $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->add($nodeVo['money'])->done();
                    }
                    //往来单位
                    if(in_array($nodeVo['type'],['buy','bre','omy','oce'])){
                        $node[$nodeKey]['current']=search($currentList['supplier'])->where([['id','=',$nodeVo['sourceData']['supplier']]])->find();
                    }else if(in_array($nodeVo['type'],['sell','sre','imy','ice'])){
                        $node[$nodeKey]['current']=search($currentList['customer'])->where([['id','=',$nodeVo['sourceData']['customer']]])->find();
                    }else{
                        $node[$nodeKey]['current']=[];
                    }
                }
            }
            //汇总数据
            $data[$key]['in']=mathArraySum(array_column($node,'in'));
            $data[$key]['out']=mathArraySum(array_column($node,'out'));
            $data[$key]['balance']=$node[count($node)-1]['balance'];
            $data[$key]['node'] = $node;
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
        $excel[]=['type'=>'title','info'=>'现金银行报表'];
        //表格数据
        $field=[
            'name'=>'账户名称',
            'extension|type'=>'单据类型',
            'sourceData|frameData|name'=>'所属组织',
            'current|name'=>'往来单位',
            'sourceData|time'=>'单据时间',
            'sourceData|number'=>'单据编号',
            'in'=>'收入',
            'out'=>'支出',
            'balance'=>'账户余额',
            'sourceData|userData|name'=>'制单人',
            'sourceData|data'=>'备注',
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
            '总数:'.count($source),
        ]];
        //导出execl
        buildExcel('现金银行报表',$excel);
    }
    //应收账款明细表
    public function crs(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && isset($input['type'])){
            $sql=[];
            //CLASS语句
            $sql=fastSql($input,[
                [['customer'=>'id'],'fullEq'],
            ]);
            //查询类型
            empty($input['type'])||$sql[]=['balance','>',0];
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('customer',$sql);//数据鉴权
            //数据查询
            $count=Db::name('customer')->where($sql)->count();
            $data=Db::name('customer')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['customer','in',array_column($data,'id')];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['sell','sre','imy','ice'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=[];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db="app\\model\\".ucfirst($t);
                $bill=array_merge($bill,$db::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
            }
            //匹配数据
            foreach ($data as $key=>$vo) {
                $data[$key]['key']=$vo['id'];
                $data[$key]['cw']=0;
                $data[$key]['pia']=0;
                $data[$key]['balance']=0;
                $node=search($bill)->where([['customer','=',$vo['id']]])->select();
                arraySort($node,'t',SORT_ASC);
                //期初查询
                $nodeUnion=[];
                $nodeUnionSql=[
                    ['examine','=',1],
                    ['customer','=',$vo['id']],
                    ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
                ];
                $nodeUnionSql=frameScope($nodeUnionSql);
                
                foreach ($tab as $t) {
                    if(!in_array($t,['vend','vre'])){
                        $nodeUnion[]=Db::name($t)->where(sqlAuth($t,$nodeUnionSql))->fieldRaw('"'.$t.'" as mold,sum('.(in_array($t,['sell','sre','ice'])?'actual - money':'total').') as calc')->group(['mold'])->buildSql();
                    }
                }
                $nodeUnion=implode(' UNION ALL ',$nodeUnion);
                $stats=DB::query('SELECT * FROM ('.$nodeUnion.') as nodcloud');
                $calc=[];
                foreach ($tab as $t) {
                    if(!in_array($t,['vend','vre'])){
                        $find=search($stats)->where([['mold','=',$t]])->find();
                        if(empty($find)){
                            $calc[$t]=0;
                        }else{
                            $calc[$t]=$find['calc'];
                        }
                    }
                }
                $balance=math()->chain($calc['sell'])->sub($calc['sre'])->sub($calc['imy'])->add($calc['ice'])->done();
                array_unshift($node,['key'=>$vo['id'].'_'.'0','bill'=>'期初余额','balance'=>$balance]);
                foreach ($node as $nodeKey=>$nodeVo) {
                    //跳过期初
                    if(!empty($nodeKey)){
                        $node[$nodeKey]['key']=$vo['id'].'_'.$nodeVo['id'].'_'.$nodeVo['mold'];
                        $node[$nodeKey]['bill']=['sell'=>'销售单','sre'=>'销售退货单','vend'=>'零售单','vre'=>'零售退货单','imy'=>'收款单','ice'=>'其它收入单'][$nodeVo['mold']];
                        if($nodeVo['mold']=='sell'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='sre'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->mul(-1)->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='vend'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='vre'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='imy'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=$nodeVo['total'];
                        }else if($nodeVo['mold']=='ice'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }
                        $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->add($node[$nodeKey]['cw'])->sub($node[$nodeKey]['pia'])->done();
                    }
                }
                $data[$key]['cw']=mathArraySum(array_column($node,'cw'));
                $data[$key]['pia']=mathArraySum(array_column($node,'pia'));
                $data[$key]['balance']=$node[count($node)-1]['balance'];
                $data[$key]['node']=$node;
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
    //应收账款明细表-导出
    public function crsExports(){
        $input=input('get.');
        if(isset($input['type'])){
            $sql=[];
            //CLASS语句
            $sql=fastSql($input,[
                [['customer'=>'id'],'fullEq'],
            ]);
            //查询类型
            empty($input['type'])||$sql[]=['balance','>',0];
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('customer',$sql);//数据鉴权
            //数据查询
            $count=Db::name('customer')->where($sql)->count();
            $data=Db::name('customer')->where($sql)->order(['id'=>'desc'])->select()->toArray();
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['customer','in',array_column($data,'id')];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['sell','sre','imy','ice'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=[];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db="app\\model\\".ucfirst($t);
                $bill=array_merge($bill,$db::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
            }
            //匹配数据
            foreach ($data as $key=>$vo) {
                $data[$key]['cw']=0;
                $data[$key]['pia']=0;
                $data[$key]['balance']=0;
                $node=search($bill)->where([['customer','=',$vo['id']]])->select();
                arraySort($node,'t',SORT_ASC);
                //期初查询
                $nodeUnion=[];
                $nodeUnionSql=[
                    ['examine','=',1],
                    ['customer','=',$vo['id']],
                    ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
                ];
                $nodeUnionSql=frameScope($nodeUnionSql);
                foreach ($tab as $t) {
                    if(!in_array($t,['vend','vre'])){
                        $nodeUnion[]=Db::name($t)->where(sqlAuth($t,$nodeUnionSql))->fieldRaw('"'.$t.'" as mold,sum('.(in_array($t,['sell','sre','ice'])?'actual - money':'total').') as calc')->group(['mold'])->buildSql();
                    }
                }
                $nodeUnion=implode(' UNION ALL ',$nodeUnion);
                $stats=DB::query('SELECT * FROM ('.$nodeUnion.') as nodcloud');
                $calc=[];
                foreach ($tab as $t) {
                    if(!in_array($t,['vend','vre'])){
                        $find=search($stats)->where([['mold','=',$t]])->find();
                        if(empty($find)){
                            $calc[$t]=0;
                        }else{
                            $calc[$t]=$find['calc'];
                        }
                    }
                }
                $balance=math()->chain($calc['sell'])->sub($calc['sre'])->sub($calc['imy'])->add($calc['ice'])->done();
                array_unshift($node,['bill'=>'期初余额','balance'=>$balance]);
                foreach ($node as $nodeKey=>$nodeVo) {
                    //跳过期初
                    if(!empty($nodeKey)){
                        $node[$nodeKey]['bill']=['sell'=>'销售单','sre'=>'销售退货单','vend'=>'零售单','vre'=>'零售退货单','imy'=>'收款单','ice'=>'其它收入单'][$nodeVo['mold']];
                        if($nodeVo['mold']=='sell'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='sre'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->mul(-1)->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='vend'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='vre'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='imy'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=$nodeVo['total'];
                        }else if($nodeVo['mold']=='ice'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }
                        $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->add($node[$nodeKey]['cw'])->sub($node[$nodeKey]['pia'])->done();
                    }
                }
                $data[$key]['cw']=mathArraySum(array_column($node,'cw'));
                $data[$key]['pia']=mathArraySum(array_column($node,'pia'));
                $data[$key]['balance']=$node[count($node)-1]['balance'];
                $data[$key]['node']=$node;
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
            $excel[]=['type'=>'title','info'=>'应收账款明细表'];
            //表格数据
            $field=[
                'name'=>'客户',
                'bill'=>'单据类型',
                'frameData|name'=>'所属组织',
                'time'=>'单据时间',
                'number'=>'单据编号',
                'cw'=>'增加应收款',
                'pia'=>'增加预收款',
                'balance'=>'应收款余额',
                'data'=>'备注',
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
                '总数:'.count($source),
            ]];
            //导出execl
            buildExcel('应收账款明细表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //应付账款明细表
    public function cps(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && isset($input['type'])){
            $sql=[];
            //CLASS语句
            $sql=fastSql($input,[
                [['supplier'=>'id'],'fullEq'],
            ]);
            //查询类型
            empty($input['type'])||$sql[]=['balance','>',0];
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('supplier',$sql);//数据鉴权
            //数据查询
            $count=Db::name('supplier')->where($sql)->count();
            $data=Db::name('supplier')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['supplier','in',array_column($data,'id')];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['buy','bre','omy','oce'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=[];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db="app\\model\\".ucfirst($t);
                $bill=array_merge($bill,$db::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
            }
            //匹配数据
            foreach ($data as $key=>$vo) {
                $data[$key]['key']=$vo['id'];
                $data[$key]['cw']=0;
                $data[$key]['pia']=0;
                $data[$key]['balance']=0;
                $node=search($bill)->where([['supplier','=',$vo['id']]])->select();
                arraySort($node,'t',SORT_ASC);
                //期初查询
                $nodeUnion=[];
                $nodeUnionSql=[
                    ['examine','=',1],
                    ['supplier','=',$vo['id']],
                    ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
                ];
                $nodeUnionSql=frameScope($nodeUnionSql);
                foreach ($tab as $t) {
                    $nodeUnion[]=Db::name($t)->where(sqlAuth($t,$nodeUnionSql))->fieldRaw('"'.$t.'" as mold,sum('.(in_array($t,['buy','bre','oce'])?'actual - money':'total').') as calc')->group(['mold'])->buildSql();
                }
                $nodeUnion=implode(' UNION ALL ',$nodeUnion);
                $stats=DB::query('SELECT * FROM ('.$nodeUnion.') as nodcloud');
                $calc=[];
                foreach ($tab as $t) {
                    $find=search($stats)->where([['mold','=',$t]])->find();
                    if(empty($find)){
                        $calc[$t]=0;
                    }else{
                        $calc[$t]=$find['calc'];
                    }
                }
                $balance=math()->chain($calc['buy'])->sub($calc['bre'])->sub($calc['omy'])->add($calc['oce'])->done();
                array_unshift($node,['key'=>$vo['id'].'_'.'0','bill'=>'期初余额','balance'=>$balance]);
                foreach ($node as $nodeKey=>$nodeVo) {
                    //跳过期初
                    if(!empty($nodeKey)){
                        $node[$nodeKey]['key']=$vo['id'].'_'.$nodeVo['id'].'_'.$nodeVo['mold'];
                        $node[$nodeKey]['bill']=['buy'=>'采购单','bre'=>'采购退货单','omy'=>'付款单','oce'=>'其它支出单'][$nodeVo['mold']];
                        if($nodeVo['mold']=='buy'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='bre'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->mul(-1)->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='omy'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=$nodeVo['total'];
                        }else if($nodeVo['mold']=='oce'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }
                        $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->add($node[$nodeKey]['cw'])->sub($node[$nodeKey]['pia'])->done();
                    }
                }
                $data[$key]['cw']=mathArraySum(array_column($node,'cw'));
                $data[$key]['pia']=mathArraySum(array_column($node,'pia'));
                $data[$key]['balance']=$node[count($node)-1]['balance'];
                $data[$key]['node']=$node;
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
    //应付账款明细表-导出
    public function cpsExports(){
        $input=input('get.');
        if(isset($input['type'])){
            $sql=[];
            //CLASS语句
            $sql=fastSql($input,[
                [['supplier'=>'id'],'fullEq'],
            ]);
            //查询类型
            empty($input['type'])||$sql[]=['balance','>',0];
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('supplier',$sql);//数据鉴权
            //数据查询
            $count=Db::name('supplier')->where($sql)->count();
            $data=Db::name('supplier')->where($sql)->order(['id'=>'desc'])->select()->toArray();
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['supplier','in',array_column($data,'id')];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['buy','bre','omy','oce'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=[];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db="app\\model\\".ucfirst($t);
                $bill=array_merge($bill,$db::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
            }
            //匹配数据
            foreach ($data as $key=>$vo) {
                $data[$key]['cw']=0;
                $data[$key]['pia']=0;
                $data[$key]['balance']=0;
                $node=search($bill)->where([['supplier','=',$vo['id']]])->select();
                arraySort($node,'t',SORT_ASC);
                //期初查询
                $nodeUnion=[];
                $nodeUnionSql=[
                    ['examine','=',1],
                    ['supplier','=',$vo['id']],
                    ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
                ];
                $nodeUnionSql=frameScope($nodeUnionSql);
                foreach ($tab as $t) {
                    $nodeUnion[]=Db::name($t)->where(sqlAuth($t,$nodeUnionSql))->fieldRaw('"'.$t.'" as mold,sum('.(in_array($t,['buy','bre','oce'])?'actual - money':'total').') as calc')->group(['mold'])->buildSql();
                }
                $nodeUnion=implode(' UNION ALL ',$nodeUnion);
                $stats=DB::query('SELECT * FROM ('.$nodeUnion.') as nodcloud');
                $calc=[];
                foreach ($tab as $t) {
                    $find=search($stats)->where([['mold','=',$t]])->find();
                    if(empty($find)){
                        $calc[$t]=0;
                    }else{
                        $calc[$t]=$find['calc'];
                    }
                }
                $balance=math()->chain($calc['buy'])->sub($calc['bre'])->sub($calc['omy'])->add($calc['oce'])->done();
                array_unshift($node,['bill'=>'期初余额','balance'=>$balance]);
                foreach ($node as $nodeKey=>$nodeVo) {
                    //跳过期初
                    if(!empty($nodeKey)){
                        $node[$nodeKey]['bill']=['buy'=>'采购单','bre'=>'采购退货单','omy'=>'付款单','oce'=>'其它支出单'][$nodeVo['mold']];
                        if($nodeVo['mold']=='buy'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='bre'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->mul(-1)->done();
                            $node[$nodeKey]['pia']=0;
                        }else if($nodeVo['mold']=='omy'){
                            $node[$nodeKey]['cw']=0;
                            $node[$nodeKey]['pia']=$nodeVo['total'];
                        }else if($nodeVo['mold']=='oce'){
                            $node[$nodeKey]['cw']=math()->chain($nodeVo['actual'])->sub($nodeVo['money'])->done();
                            $node[$nodeKey]['pia']=0;
                        }
                        $node[$nodeKey]['balance'] = math()->chain($node[$nodeKey-1]['balance'])->add($node[$nodeKey]['cw'])->sub($node[$nodeKey]['pia'])->done();
                    }
                }
                $data[$key]['cw']=mathArraySum(array_column($node,'cw'));
                $data[$key]['pia']=mathArraySum(array_column($node,'pia'));
                $data[$key]['balance']=$node[count($node)-1]['balance'];
                $data[$key]['node']=$node;
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
            $excel[]=['type'=>'title','info'=>'应付账款明细表'];
            //表格数据
            $field=[
                'name'=>'供应商',
                'bill'=>'单据类型',
                'frameData|name'=>'所属组织',
                'time'=>'单据时间',
                'number'=>'单据编号',
                'cw'=>'增加应付款',
                'pia'=>'增加预付款',
                'balance'=>'应付款余额',
                'data'=>'备注',
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
                '总数:'.count($source),
            ]];
            //导出execl
            buildExcel('应付账款明细表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //客户对账单
    public function cct(){
        $input=input('post.');
        if(existFull($input,['customer']) && isset($input['type'])){
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['customer','=',$input['customer']];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['sell','sre','imy','ice'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=['class'=>[],'info'=>[]];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db=['class'=>"app\\model\\".ucfirst($t),'info'=>"app\\model\\".ucfirst($t).'Info'];
                $bill['class']=array_merge($bill['class'],$db['class']::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
                //匹配明细
                if(!empty($input['type'])){
                    if(in_array($t,['sell','sre'])){
                        $detail=$db['info']::with(['goodsData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='imy'){
                        $detail=$db['info']::with(['accountData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='ice'){
                        $detail=$db['info']::with(['ietData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }
                    $bill['info'][$t]=$detail;
                }
            }
            arraySort($bill['class'],'t',SORT_ASC);
            //期初查询
            $firstUnion=[];
            $firstUnionSql=[
                ['examine','=',1],
                ['customer','=',$input['customer']],
                ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
            ];
            $firstUnionSql=frameScope($firstUnionSql);
            
            $firstUnion=implode(' UNION ALL ',$firstUnion);
            $stats=DB::query('SELECT * FROM ('.$firstUnion.') as nodcloud');
            $calc=[];
            
            $balance=math()->chain($calc['sell'])->sub($calc['sre'])->sub($calc['imy'])->add($calc['ice'])->done();
            $data=[
                ['key'=>'0','node'=>[],'bill'=>'期初余额','balance'=>$balance],
            ];
            //匹配数据
            foreach ($bill['class'] as $classVo) {
                $row=$classVo;
                $row['key']=$row['id'].'_'.$row['mold'];
                $row['bill']=['sell'=>'销售单','sre'=>'销售退货单','imy'=>'收款单','ice'=>'其它收入单'][$row['mold']];
                if($row['mold']=='sell'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='sre'){
                    $row['total']=math()->chain($row['total'])->mul(-1)->done();
                    $row['actual']=math()->chain($row['actual'])->mul(-1)->done();
                    $row['money']=math()->chain($row['money'])->mul(-1)->done();
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='imy'){
                    $row['actual']=0;
                    $row['money']=$row['total'];
                    $row['discount']=0;
                }else if($row['mold']=='ice'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }
                $row['balance'] = math()->chain($data[count($data)-1]['balance'])->add($row['actual'])->sub($row['money'])->done();
                //匹配明细
                $node=[];
                if(!empty($input['type'])){
                    $list=search($bill['info'][$row['mold']])->where([['pid','=',$row['id']]])->select();
                    foreach ($list as $listVo) {
                        if(in_array($row['mold'],['sell','sre'])){
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
                        }else if($row['mold']=='imy'){
                            $detail=[
                                'name'=>$listVo['accountData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else if($row['mold']=='ice'){
                            $detail=[
                                'name'=>$listVo['ietData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else{
                            $detail=[];
                        }
                        $node[]=['key'=>$row['id'].'_'.$listVo['id'].'_'.$row['mold'],'detail'=>$detail];
                    }
                }
                $row['node']=$node;
                $data[]=$row;
            }
            $result=[
                'state'=>'success',
                'info'=>$data
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //客户对账单-导出
    public function cctExports(){
        $input=input('get.');
        if(existFull($input,['customer']) && isset($input['type'])){
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['customer','=',$input['customer']];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['sell','sre','imy','ice'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=['class'=>[],'info'=>[]];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db=['class'=>"app\\model\\".ucfirst($t),'info'=>"app\\model\\".ucfirst($t).'Info'];
                $bill['class']=array_merge($bill['class'],$db['class']::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
                //匹配明细
                if(!empty($input['type'])){
                    if(in_array($t,['sell','sre'])){
                        $detail=$db['info']::with(['goodsData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='imy'){
                        $detail=$db['info']::with(['accountData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='ice'){
                        $detail=$db['info']::with(['ietData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }
                    $bill['info'][$t]=$detail;
                }
            }
            arraySort($bill['class'],'t',SORT_ASC);
            //期初查询
            $firstUnion=[];
            $firstUnionSql=[
                ['examine','=',1],
                ['customer','=',$input['customer']],
                ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
            ];
            $firstUnionSql=frameScope($firstUnionSql);
            
            $firstUnion=implode(' UNION ALL ',$firstUnion);
            $stats=DB::query('SELECT * FROM ('.$firstUnion.') as nodcloud');
            $calc=[];
            
            $balance=math()->chain($calc['sell'])->sub($calc['sre'])->sub($calc['imy'])->add($calc['ice'])->done();
            $data=[
                ['node'=>[],'bill'=>'期初余额','balance'=>$balance],
            ];
            //匹配数据
            foreach ($bill['class'] as $classVo) {
                $row=$classVo;
                $row['bill']=['sell'=>'销售单','sre'=>'销售退货单','imy'=>'收款单','ice'=>'其它收入单'][$row['mold']];
                if($row['mold']=='sell'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='sre'){
                    $row['total']=math()->chain($row['total'])->mul(-1)->done();
                    $row['actual']=math()->chain($row['actual'])->mul(-1)->done();
                    $row['money']=math()->chain($row['money'])->mul(-1)->done();
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='imy'){
                    $row['actual']=0;
                    $row['money']=$row['total'];
                    $row['discount']=0;
                }else if($row['mold']=='ice'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }
                $row['balance'] = math()->chain($data[count($data)-1]['balance'])->add($row['actual'])->sub($row['money'])->done();
                //匹配明细
                $node=[];
                if(!empty($input['type'])){
                    $list=search($bill['info'][$row['mold']])->where([['pid','=',$row['id']]])->select();
                    foreach ($list as $listVo) {
                        if(in_array($row['mold'],['sell','sre'])){
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
                        }else if($row['mold']=='imy'){
                            $detail=[
                                'name'=>$listVo['accountData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else if($row['mold']=='ice'){
                            $detail=[
                                'name'=>$listVo['ietData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else{
                            $detail=[];
                        }
                        $node[]=['detail'=>$detail];
                    }
                }
                $row['node']=$node;
                $data[]=$row;
            }
            $customer=Db::name('customer')->where([['id','=',$input['customer']]])->find();
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
            $excel[]=['type'=>'title','info'=>'客户对账单 [ '.$customer['name'].' ]'];
            //表格数据
            $field=[
                [
                    'bill'=>'单据类型',
                    'frameData|name'=>'所属组织',
                    'time'=>'单据时间',
                    'number'=>'单据编号'
                ],
                [
                    'detail|name'=>'名称',
                    'detail|attr'=>'属性',
                    'detail|unit'=>'单位',
                    'detail|price'=>'单价',
                    'detail|nums'=>'数量',
                    'detail|dsc'=>'折扣额',
                    'detail|total'=>'金额',
                    'detail|tat'=>'税额',
                    'detail|tpt'=>'价税合计',
                ],
                [
                    'total'=>'单据金额',
                    'discount'=>'优惠金额',
                    'actual'=>'应收金额',
                    'money'=>'实收金额',
                    'balance'=>'应收款余额',
                    'data'=>'备注'
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
            buildExcel('客户对账单',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //供应商对账单
    public function cst(){
        $input=input('post.');
        if(existFull($input,['supplier']) && isset($input['type'])){
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['supplier','=',$input['supplier']];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['buy','bre','omy','oce'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=['class'=>[],'info'=>[]];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db=['class'=>"app\\model\\".ucfirst($t),'info'=>"app\\model\\".ucfirst($t).'Info'];
                $bill['class']=array_merge($bill['class'],$db['class']::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
                //匹配明细
                if(!empty($input['type'])){
                    if(in_array($t,['buy','bre'])){
                        $detail=$db['info']::with(['goodsData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='omy'){
                        $detail=$db['info']::with(['accountData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='oce'){
                        $detail=$db['info']::with(['ietData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }
                    $bill['info'][$t]=$detail;
                }
            }
            arraySort($bill['class'],'t',SORT_ASC);
            //期初查询
            $firstUnion=[];
            $firstUnionSql=[
                ['examine','=',1],
                ['supplier','=',$input['supplier']],
                ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
            ];
            $firstUnionSql=frameScope($firstUnionSql);
            foreach ($tab as $t) {
                $firstUnion[]=Db::name($t)->where(sqlAuth($t,$firstUnionSql))->fieldRaw('"'.$t.'" as mold,sum('.(in_array($t,['buy','bre','oce'])?'actual - money':'total').') as calc')->group(['mold'])->buildSql();
            }
            $firstUnion=implode(' UNION ALL ',$firstUnion);
            $stats=DB::query('SELECT * FROM ('.$firstUnion.') as nodcloud');
            $calc=[];
            foreach ($tab as $t) {
                $find=search($stats)->where([['mold','=',$t]])->find();
                if(empty($find)){
                    $calc[$t]=0;
                }else{
                    $calc[$t]=$find['calc'];
                }
            }
            $balance=math()->chain($calc['buy'])->sub($calc['bre'])->sub($calc['omy'])->add($calc['oce'])->done();
            $data=[
                ['key'=>'0','node'=>[],'bill'=>'期初余额','balance'=>$balance],
            ];
            //匹配数据
            foreach ($bill['class'] as $classVo) {
                $row=$classVo;
                $row['key']=$row['id'].'_'.$row['mold'];
                $row['bill']=['buy'=>'采购单','bre'=>'采购退货单','omy'=>'付款单','oce'=>'其它收入单'][$row['mold']];
                if($row['mold']=='buy'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='bre'){
                    $row['total']=math()->chain($row['total'])->mul(-1)->done();
                    $row['actual']=math()->chain($row['actual'])->mul(-1)->done();
                    $row['money']=math()->chain($row['money'])->mul(-1)->done();
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='omy'){
                    $row['actual']=0;
                    $row['money']=$row['total'];
                    $row['discount']=0;
                }else if($row['mold']=='oce'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }
                $row['balance'] = math()->chain($data[count($data)-1]['balance'])->add($row['actual'])->sub($row['money'])->done();
                //匹配明细
                $node=[];
                if(!empty($input['type'])){
                    $list=search($bill['info'][$row['mold']])->where([['pid','=',$row['id']]])->select();
                    foreach ($list as $listVo) {
                        if(in_array($row['mold'],['buy','bre'])){
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
                        }else if($row['mold']=='omy'){
                            $detail=[
                                'name'=>$listVo['accountData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else if($row['mold']=='oce'){
                            $detail=[
                                'name'=>$listVo['ietData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else{
                            $detail=[];
                        }
                        $node[]=['key'=>$row['id'].'_'.$listVo['id'].'_'.$row['mold'],'detail'=>$detail];
                    }
                }
                $row['node']=$node;
                $data[]=$row;
            }
            $result=[
                'state'=>'success',
                'info'=>$data
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //供应商对账单-导出
    public function cstExports(){
        $input=input('get.');
        if(existFull($input,['supplier']) && isset($input['type'])){
            //子查询
            $existsSql=fastSql($input,[
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $existsSql[]=['examine','=',1];
            $existsSql[]=['supplier','=',$input['supplier']];
            $existsSql=frameScope($existsSql);
            //构造语句
            $union=[];
            $tab=['buy','bre','omy','oce'];
            foreach ($tab as $t) {
                $union[]=Db::name($t)->where(sqlAuth($t,$existsSql))->fieldRaw('"'.$t.'" as mold,id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //匹配单据
            $bill=['class'=>[],'info'=>[]];
            foreach ($tab as $t) {
                $gather=search($record)->where([['mold','=',$t]])->select();
                $db=['class'=>"app\\model\\".ucfirst($t),'info'=>"app\\model\\".ucfirst($t).'Info'];
                $bill['class']=array_merge($bill['class'],$db['class']::with(['frameData'])->fieldRaw('*,"'.$t.'" as mold,time as t')->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray());
                //匹配明细
                if(!empty($input['type'])){
                    if(in_array($t,['buy','bre'])){
                        $detail=$db['info']::with(['goodsData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='omy'){
                        $detail=$db['info']::with(['accountData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }else if($t=='oce'){
                        $detail=$db['info']::with(['ietData'])->where([['pid','in',array_column($gather,'id')]])->select()->toArray();
                    }
                    $bill['info'][$t]=$detail;
                }
            }
            arraySort($bill['class'],'t',SORT_ASC);
            //期初查询
            $firstUnion=[];
            $firstUnionSql=[
                ['examine','=',1],
                ['supplier','=',$input['supplier']],
                ['time','<',existFull($input,['startTime'])?strtotime($input['startTime']):0]
            ];
            $firstUnionSql=frameScope($firstUnionSql);
            foreach ($tab as $t) {
                  $firstUnion[]=Db::name($t)->where(sqlAuth($t,$firstUnionSql))->fieldRaw('"'.$t.'" as mold,sum('.(in_array($t,['buy','bre','oce'])?'actual - money':'total').') as calc')->group(['mold'])->buildSql();
            }
            $firstUnion=implode(' UNION ALL ',$firstUnion);
            $stats=DB::query('SELECT * FROM ('.$firstUnion.') as nodcloud');
            $calc=[];
            foreach ($tab as $t) {
				$find=search($stats)->where([['mold','=',$t]])->find();
				if(empty($find)){
					$calc[$t]=0;
				}else{
					$calc[$t]=$find['calc'];
				}
            }
            $balance=math()->chain($calc['buy'])->sub($calc['bre'])->sub($calc['omy'])->add($calc['oce'])->done();
            $data=[
                ['node'=>[],'bill'=>'期初余额','balance'=>$balance],
            ];
            //匹配数据
            foreach ($bill['class'] as $classVo) {
                $row=$classVo;
                $row['bill']=['buy'=>'采购单','bre'=>'采购退货单','omy'=>'付款单','oce'=>'其它收入单'][$row['mold']];
                if($row['mold']=='buy'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='bre'){
                    $row['total']=math()->chain($row['total'])->mul(-1)->done();
                    $row['actual']=math()->chain($row['actual'])->mul(-1)->done();
                    $row['money']=math()->chain($row['money'])->mul(-1)->done();
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }else if($row['mold']=='omy'){
                    $row['actual']=0;
                    $row['money']=$row['total'];
                    $row['discount']=0;
                }else if($row['mold']=='oce'){
                    $row['discount']=math()->chain($row['total'])->sub($row['actual'])->done();
                }
                $row['balance'] = math()->chain($data[count($data)-1]['balance'])->add($row['actual'])->sub($row['money'])->done();
                //匹配明细
                $node=[];
                if(!empty($input['type'])){
                    $list=search($bill['info'][$row['mold']])->where([['pid','=',$row['id']]])->select();
                    foreach ($list as $listVo) {
                        if(in_array($row['mold'],['buy','bre'])){
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
                        }else if($row['mold']=='omy'){
                            $detail=[
                                'name'=>$listVo['accountData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else if($row['mold']=='oce'){
                            $detail=[
                                'name'=>$listVo['ietData']['name'],
                                'attr'=>'',
                                'unit'=>'',
                                'price'=>$listVo['money'],
                                'nums'=>1,
                                'dsc'=>0,
                                'total'=>$listVo['money'],
                                'tat'=>0,
                                'tpt'=>$listVo['money']
                            ];
                        }else{
                            $detail=[];
                        }
                        $node[]=['detail'=>$detail];
                    }
                }
                $row['node']=$node;
                $data[]=$row;
            }
            $supplier=Db::name('supplier')->where([['id','=',$input['supplier']]])->find();
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
            $excel[]=['type'=>'title','info'=>'供应商对账单 [ '.$supplier['name'].' ]'];
            //表格数据
            $field=[
                [
                    'bill'=>'单据类型',
                    'frameData|name'=>'所属组织',
                    'time'=>'单据时间',
                    'number'=>'单据编号'
                ],
                [
                    'detail|name'=>'名称',
                    'detail|attr'=>'属性',
                    'detail|unit'=>'单位',
                    'detail|price'=>'单价',
                    'detail|nums'=>'数量',
                    'detail|dsc'=>'折扣额',
                    'detail|total'=>'金额',
                    'detail|tat'=>'税额',
                    'detail|tpt'=>'价税合计',
                ],
                [
                    'total'=>'单据金额',
                    'discount'=>'优惠金额',
                    'actual'=>'应付金额',
                    'money'=>'实付金额',
                    'balance'=>'应付款余额',
                    'data'=>'备注'
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
            buildExcel('供应商对账单',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //其它收支明细表
    public function cos(){
        $input=input('post.');
        $sheet=['ice','oce'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_array($input['mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['account','fullEq'],
                [['data'=>'class.data'],'fullLike']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            
            $sql_class=[];
            foreach ($input['mold'] as $mold) {
                $sql_class[$mold]=sqlAuth($mold,$sql['class']);
            }
            //INFO语句
            $sql['info']=fastSql($input,[['iet','fullEq']]);
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold.'_info')->alias('info')->join(['is_'.$mold=>'class'],'info.pid=class.id')->where($sql['info'])->where($sql_class[$mold])->fieldRaw('info.id as info,class.id as class,time,"'.$mold.'" as mold')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //获取总条数
            $count=DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud')[0]["count"];
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud ORDER BY `time` DESC LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            $list=[];
            foreach ($input['mold'] as $mold) {
                $gather=search($record)->where([['mold','=',$mold]])->select();
                $table=[
                    "class"=>"app\\model\\".ucfirst($mold),
                    'info'=>"app\\model\\".ucfirst($mold).'Info',
                ];
                $list[$mold]['info']=$table['info']::with(['ietData'])->where([['id','in',array_column($gather,'info')]])->select()->toArray();
                $list[$mold]['class']=$table['class']::with(['frameData','accountData',$mold=='ice'?'customerData':'supplierData'])->where([['id','in',array_column($list[$mold]['info'],'pid')]])->append(['extension'])->select()->toArray();
            }
            $data=[];
            foreach ($record as $recordVo) {
                $mold=$recordVo['mold'];
                $class=search($list[$mold]['class'])->where([['id','=',$recordVo['class']]])->find();
                $info=search($list[$mold]['info'])->where([['id','=',$recordVo['info']]])->find();
                $data[]=[
                    'mold'=>$mold, 
                    'name'=>['ice'=>'其它收入单','oce'=>'其它支出单'][$mold],
                    'current'=>$mold=='ice'?$class['customerData']??[]:$class['supplierData']??[],
                    'class'=>$class,
                    'info'=>$info,
                    'in'=>$mold=='ice'?$info['money']:0,
                    'out'=>$mold=='ice'?0:$info['money']
                ];
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
    //其它收支明细表-导出
    public function cosExports(){
        $input=input('get.');
        $sheet=['ice','oce'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(is_array($input['mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['account','fullEq'],
                [['data'=>'class.data'],'fullLike']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            $sql_class=[];
            foreach ($input['mold'] as $mold) {
                $sql_class[$mold]=sqlAuth($mold,$sql['class']);
            }
            //INFO语句
            $sql['info']=fastSql($input,[['iet','fullEq']]);
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold.'_info')->alias('info')->join(['is_'.$mold=>'class'],'info.pid=class.id')->where($sql['info'])->where($sql_class[$mold])->fieldRaw('info.id as info,class.id as class,time,"'.$mold.'" as mold')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //获取总条数
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud ORDER BY `time` DESC');
            $list=[];
            foreach ($input['mold'] as $mold) {
                $gather=search($record)->where([['mold','=',$mold]])->select();
                $table=[
                    "class"=>"app\\model\\".ucfirst($mold),
                    'info'=>"app\\model\\".ucfirst($mold).'Info',
                ];
                $list[$mold]['info']=$table['info']::with(['ietData'])->where([['id','in',array_column($gather,'info')]])->select()->toArray();
                $list[$mold]['class']=$table['class']::with(['frameData','accountData',$mold=='ice'?'customerData':'supplierData'])->where([['id','in',array_column($list[$mold]['info'],'pid')]])->append(['extension'])->select()->toArray();
            }
            $data=[];
            foreach ($record as $recordVo) {
                $mold=$recordVo['mold'];
                $class=search($list[$mold]['class'])->where([['id','=',$recordVo['class']]])->find();
                $info=search($list[$mold]['info'])->where([['id','=',$recordVo['info']]])->find();
                $data[]=[
                    'mold'=>$mold, 
                    'name'=>['ice'=>'其它收入单','oce'=>'其它支出单'][$mold],
                    'current'=>$mold=='ice'?$class['customerData']??[]:$class['supplierData']??[],
                    'class'=>$class,
                    'info'=>$info,
                    'in'=>$mold=='ice'?$info['money']:0,
                    'out'=>$mold=='ice'?0:$info['money']
                ];
            }
            $source=$data;
            
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'其它收支明细表'];
            //表格数据
            $field=[
                "name"=>"单据类型",
                "class|frameData|name"=>"所属组织",
                "current|name"=>"往来单位",
                "class|time"=>"单据时间",
                "class|number"=>"单据编号",
                "info|ietData|name"=>"收支类别",
                "in"=>"收入",
                "out"=>"支出",
                "class|accountData|name"=>"结算账户",
                "class|data"=>"备注信息"
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
                '总数:'.count($source),
                '总收入:'.mathArraySum(array_column($source,'in')),
                '总支出:'.mathArraySum(array_column($source,'out'))
            ]];
            //导出execl
            buildExcel('其它收支明细表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //利润表
    public function cit(){
        $input=input('post.');
        $sql=fastSql($input,[
            [['startTime'=>'time'],'startTime'],
            [['endTime'=>'time'],'endTime']
        ]);
        $sql[]=['examine','=',1];
		$sql=frameScope($sql);//组织数据
		$sql=sqlAuth('sell',$sql);//数据鉴权[结构一致]
        //匹配数据
        $union=[];
        $tab=['sell','sre'];
        foreach ($tab as $t) {
            $union[]=Db::name($t)->where($sql)->fieldRaw('"'.$t.'" as mold,group_concat(id) as id,sum(actual) as money,sum(cost) as cost')->buildSql();
        }
        $union=implode(' UNION ALL ',$union);
        $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
        //成本统计
        $summary=[];
        foreach ($record as $v) {
            if(empty($v['id'])){
                $summary[]=['mold'=>$v['mold'],'bct'=>0];
            }else{
                $where=[['class','in',explode(',',$v['id'])],['type','=',$v['mold']]
                ];
                $summary[]=Db::name('summary')->where($where)->fieldRaw('"'.$v['mold'].'" as mold,sum(bct) as bct')->select()->toArray()[0];
            }
        }
        //计算数据
        $si=math()->chain($record[0]['money'])->sub($record[1]['money'])->add($record[2]['money'])->sub($record[3]['money'])->done();
        $cost=math()->chain($record[0]['cost'])->add($record[1]['cost'])->add($record[2]['cost'])->add($record[3]['cost'])->done();
        $bct=math()->chain($summary[0]['bct'])->sub($summary[1]['bct'])->add($summary[2]['bct'])->sub($summary[3]['bct'])->done();
        $profit=math()->chain($si)->sub($cost)->sub($bct)->done();
        $data=[
            ['name'=>'主营业务','money'=>''],
            ['name'=>'| - 销售收入','money'=>$si],
            ['name'=>'| - 业务成本','money'=>$cost],
            ['name'=>'| - 销售成本','money'=>$bct],
            ['name'=>'','money'=>''],
            ['name'=>'利润','money'=>$profit]
        ];
        $result=[
            'state'=>'success',
            'info'=>$data
        ];//返回数据
        return json($result);
    }
    //利润表-导出
    public function citExports(){
        $input=input('post.');
        $sql=fastSql($input,[
            [['startTime'=>'time'],'startTime'],
            [['endTime'=>'time'],'endTime']
        ]);
        $sql[]=['examine','=',1];
		$sql=frameScope($sql);//组织数据
		$sql=sqlAuth('sell',$sql);//数据鉴权[结构一致]
        //匹配数据
        $union=[];
        $tab=['sell','sre'];
        foreach ($tab as $t) {
            $union[]=Db::name($t)->where($sql)->fieldRaw('"'.$t.'" as mold,group_concat(id) as id,sum(actual) as money,sum(cost) as cost')->buildSql();
        }
        $union=implode(' UNION ALL ',$union);
        $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
        //成本统计
        $summary=[];
        foreach ($record as $v) {
            if(empty($v['id'])){
                $summary[]=['mold'=>$v['mold'],'bct'=>0];
            }else{
                $where=[
                    ['class','in',explode(',',$v['id'])],
                    ['type','=',$v['mold']]
                ];
                $summary[]=Db::name('summary')->where($where)->fieldRaw('"'.$v['mold'].'" as mold,sum(bct) as bct')->select()->toArray()[0];
            }
        }
        //计算数据
        $si=math()->chain($record[0]['money'])->sub($record[1]['money'])->add($record[2]['money'])->sub($record[3]['money'])->done();
        $cost=math()->chain($record[0]['cost'])->add($record[1]['cost'])->add($record[2]['cost'])->add($record[3]['cost'])->done();
        $bct=math()->chain($summary[0]['bct'])->sub($summary[1]['bct'])->add($summary[2]['bct'])->sub($summary[3]['bct'])->done();
        $profit=math()->chain($si)->sub($cost)->sub($bct)->done();
        $data=[
            ['name'=>'主营业务','money'=>''],
            ['name'=>'销售收入','money'=>$si],
            ['name'=>'业务成本','money'=>$cost],
            ['name'=>'销售成本','money'=>$bct],
            ['name'=>'','money'=>''],
            ['name'=>'利润','money'=>$profit]
        ];
        $source=$data;
        //开始构造导出数据
        $excel=[];//初始化导出数据
        //标题数据
        $excel[]=['type'=>'title','info'=>'利润表'];
        //表格数据
        $field=[
            "name"=>"项目",
            "money"=>"金额"
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
        buildExcel('利润表',$excel);
    }
    //往来单位欠款表
    public function cds(){
        $input=input('post.');
        $sheet=['customer','supplier'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_array($input['mold']) && arrayInArray($input['mold'],$sheet)){
            $base=fastSql($input,[
                ['name','fullLike'],
                ['number','fullLike'],
                ['data','fullLike']
            ]);
            $base=frameScope($base);//组织数据
            $sql=[];
            foreach ($input['mold'] as $mold) {
                if($mold=='customer'){
                    $sql[$mold]=sqlAuth('customer',$base);//数据鉴权
                }else{
                    $sql[$mold]=sqlAuth('supplier',$base);//数据鉴权
                }
            }
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->where($sql[$mold])->fieldRaw('"'.$mold.'" as mold,id,name,number,data,balance')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //获取总条数
            $count=DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud')[0]["count"];
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            foreach ($record as $key=>$vo) {
                $record[$key]['mold']=['customer'=>'客户','supplier'=>'供应商'][$vo['mold']];
                if($vo['mold']=='customer'){
                    $record[$key]['collection']=floatval($vo['balance']);
                    $record[$key]['payment']=0;
                }else{
                    $record[$key]['collection']=0;
                    $record[$key]['payment']=floatval($vo['balance']);
                }
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$record
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //往来单位欠款表-导出
    public function cdsExports(){
        $input=input('get.');
        $sheet=['customer','supplier'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(is_array($input['mold']) && arrayInArray($input['mold'],$sheet)){
            $base=fastSql($input,[
                ['name','fullLike'],
                ['number','fullLike'],
                ['data','fullLike']
            ]);
            $base=frameScope($base);//组织数据
            $sql=[];
            foreach ($input['mold'] as $mold) {
                if($mold=='customer'){
                    $sql[$mold]=sqlAuth('customer',$base);//数据鉴权
                }else{
                    $sql[$mold]=sqlAuth('supplier',$base);//数据鉴权
                }
            }
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->where($sql[$mold])->fieldRaw('"'.$mold.'" as mold,id,name,number,data,balance')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //获取总条数
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            foreach ($record as $key=>$vo) {
                $record[$key]['mold']=['customer'=>'客户','supplier'=>'供应商'][$vo['mold']];
                if($vo['mold']=='customer'){
                    $record[$key]['collection']=floatval($vo['balance']);
                    $record[$key]['payment']=0;
                }else{
                    $record[$key]['collection']=0;
                    $record[$key]['payment']=floatval($vo['balance']);
                }
            }
            $source=$record;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'往来单位欠款表'];
            //表格数据
            $field=[
                "mold"=>"单位类型",
                "name"=>"单位名称",
                "number"=>"单位编号",
                "collection"=>"应收款余额",
                "payment"=>"应付款余额",
                "data"=>"备注信息"
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
                '总数:'.count($source),
                '总应收款余额:'.mathArraySum(array_column($source,'collection')),
                '总应付款余额:'.mathArraySum(array_column($source,'payment'))
            ]];
            //导出execl
            buildExcel('往来单位欠款表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
}
