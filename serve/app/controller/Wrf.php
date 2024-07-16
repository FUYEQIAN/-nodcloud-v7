<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Summary;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Wrf extends Acl{
    //商品库存余额表
    public function wbs(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && isset($input['warehouse']) && is_array($input['warehouse'])){
            //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',$input['warehouse']]])->order(['id'=>'desc'])->select()->toArray();
            //构造表头
            $column=[];
            foreach ($warehouse as $vo) {
                $column[]=['id'=>$vo['id'],'name'=>$vo['name'],'uct'=>'uct_'.$vo['id'],'uns'=>'uns_'.$vo['id'],'bct'=>'bct_'.$vo['id']];
            }
            //匹配数据
            $sql=fastSql($input,[
                ['warehouse','fullIn'],
                ['time','endTime']
            ]);
            $sql=sqlAuth('summary',$sql);
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['goods','in',$goods];
            }
            $count=Summary::where($sql)->group(['goods'])->count();
            $info=Summary::with(['goodsData'])->where($sql)->group(['goods'])->page($input['page'],$input['limit'])->order(['goods'])->select()->toArray();
            foreach ($info as $key=>$vo) {
                //查询条件
                $where=fastSql($input,[['time','endTime']]);
                $where[]=['goods','=',$vo['goods']];
                //单位数据
                $info[$key]['unit']=$vo['goodsData']['unit']==-1?'多单位':$vo['goodsData']['unit'];
                //分仓数据
                $info[$key]['uns']=0;
                foreach ($column as $v) {
                    $wb=Db::name('summary')->where(array_merge($where,[['warehouse','=',$v['id']]]))->order(['id'=>'DESC'])->find();
                    
                    $wb=empty($wb)?['exist'=>[0,0,0,0],'balance'=>[0,0,0,0]]:['exist'=>json_decode($wb['exist']),'balance'=>json_decode($wb['balance'])];
                    if(empty($wb['exist'][1]) && empty($wb['balance'][1])){
                        $info[$key]['wb_'.$v['id']]=['uct'=>'','uns'=>'','bct'=>''];
                    }else{
                        $info[$key]['wb_'.$v['id']]=[
                            'uct'=>empty($wb['exist'][1])?0:math()->chain($wb['balance'][1])->div($wb['exist'][1])->round(2)->done(),
                            'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($wb['exist'][1],$vo['goodsData']['units']):$wb['exist'][1],
                            'bct'=>$wb['balance'][1] 
                        ];
                    }
                    if(!empty($info[$key]['wb_'.$v['id']]['uns']) && $vo['goodsData']['unit'] != -1){
                        $info[$key]['uns']+=$info[$key]['wb_'.$v['id']]['uns'];
                    }elseif(!empty($info[$key]['wb_'.$v['id']]['uns']) && $vo['goodsData']['unit'] == -1){
                        $info[$key]['uns']+-$wb['exist'][1];
                    }
                    
                }
                //汇总数据
                $balance=Db::name('summary')->where($where)->order(['id'=>'DESC'])->find();
                $balance=['exist'=>json_decode($balance['exist']),'balance'=>json_decode($balance['balance'])];
                $info[$key]['balance']=[];
                $info[$key]['balance']['uct']=empty($balance['exist'][0])?0:math()->chain($balance['balance'][0])->div($balance['exist'][0])->round(2)->done();
                $info[$key]['balance']['uns']=$vo['goodsData']['unit']==-1?unitSwitch($info[$key]['uns'],$vo['goodsData']['units']):$info[$key]['uns'];
                $info[$key]['balance']['bct']=math()->chain($info[$key]['balance']['uct'])->mul($info[$key]['balance']['uns'])->round(2)->done();
                
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
    //商品库存余额表-导出
    public function wbsExports(){
        $input=input('get.');
        existFull($input,['warehouse'])||$input['warehouse']=[];
        if(isset($input['warehouse']) && is_array($input['warehouse'])){
            //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',$input['warehouse']]])->order(['id'=>'desc'])->select()->toArray();
            //构造表头
            $column=[];
            foreach ($warehouse as $vo) {
                $column[]=['id'=>$vo['id'],'name'=>$vo['name'],'uct'=>'uct_'.$vo['id'],'uns'=>'uns_'.$vo['id'],'bct'=>'bct_'.$vo['id']];
            }
            //匹配数据
            $sql=fastSql($input,[
                ['warehouse','fullIn'],
                ['time','endTime']
            ]);
            $sql=sqlAuth('summary',$sql);
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['goods','in',$goods];
            }
            $count=Summary::where($sql)->group(['goods'])->count();
            $info=Summary::with(['goodsData'])->where($sql)->group(['goods'])->order(['goods'])->select()->toArray();
            foreach ($info as $key=>$vo) {
                //查询条件
                $where=fastSql($input,[['time','endTime']]);
                $where[]=['goods','=',$vo['goods']];
                //单位数据
                $info[$key]['unit']=$vo['goodsData']['unit']==-1?'多单位':$vo['goodsData']['unit'];
                //分仓数据
                foreach ($column as $v) {
                    $wb=Db::name('summary')->where(array_merge($where,[['warehouse','=',$v['id']]]))->order(['id'=>'DESC'])->find();
                    $wb=empty($wb)?['exist'=>[0,0,0,0],'balance'=>[0,0,0,0]]:['exist'=>json_decode($wb['exist']),'balance'=>json_decode($wb['balance'])];
                    if(empty($wb['exist'][1]) && empty($wb['balance'][1])){
                        $info[$key]['wb_'.$v['id']]=['uct'=>'','uns'=>'','bct'=>''];
                    }else{
                        $info[$key]['wb_'.$v['id']]=[
                            'uct'=>empty($wb['exist'][1])?0:math()->chain($wb['balance'][1])->div($wb['exist'][1])->round(2)->done(),
                            'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($wb['exist'][1],$vo['goodsData']['units']):$wb['exist'][1],
                            'bct'=>$wb['balance'][1] 
                        ];
                    }
                }
                //汇总数据
                $balance=Db::name('summary')->where($where)->order(['id'=>'DESC'])->find();
                $balance=['exist'=>json_decode($balance['exist']),'balance'=>json_decode($balance['balance'])];
                $info[$key]['balance']=[
                    'uct'=>empty($balance['exist'][0])?0:math()->chain($balance['balance'][0])->div($balance['exist'][0])->round(2)->done(),
                    'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($balance['exist'][0],$vo['goodsData']['units']):$balance['exist'][0],
                    'bct'=>$balance['balance'][0]
                ];
            }
            $source=$info;
            $columns=[];
            foreach ($column as $v) {
                $columns['wb_'.$v['id'].'|uct']=$v['name'].'成本';
                $columns['wb_'.$v['id'].'|uns']=$v['name'].'数量';
                $columns['wb_'.$v['id'].'|bct']=$v['name'].'总成本';
            }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'商品库存余额表'];
            //表格数据
            $field=array_merge(
                [
                    'goodsData|name'=>'商品名称',
                    'unit'=>'单位'
                ],
                $columns,
                [
                    'balance|uct'=>'汇总成本',
                    'balance|uns'=>'汇总数量',
                    'balance|bct'=>'汇总总成本'
                ]
            );
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
            buildExcel('商品库存余额表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //商品收发明细表
    public function wds(){
        $input=input('post.');
        $sheet=['buy','bre','sell','sre','swapOut','swapEnter','entry','extry'];
		existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_arrays($input,['warehouse','mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=fastSql($input,[
                ['warehouse','fullIn'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                [['mold'=>'type'],'fullIn']
            ]);
            $sql=sqlAuth('summary',$sql);
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['goods','in',$goods];
            }
            //子查询
            $existsSql=[['id','=',Db::raw('summary.class')]];
            $existsSql=frameScope($existsSql);
            //多源匹配
            $union=[];
            //数据关系表
            $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
            foreach ($table as $k=>$v) {
            	//匹配类型|减少查询
            	if(in_array($k,$input['mold'])){
            		$union[]=Db::name($v)->where([['summary.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
            	}
            }
            //合并子查询
            $union=implode(' UNION ALL ',$union);
            $count=Summary::alias('summary')->where($sql)->whereExists($union)->count();
            $source=Summary::with(['sourceData'=>['frameData'],'goodsData','warehouseData'])->alias('summary')->where($sql)->whereExists($union)->page($input['page'],$input['limit'])->order(['goods','id'])->append(['extension'])->select()->toArray();
            //匹配往来单位
            $currentList=['customer'=>[],'supplier'=>[]];
            //匹配客戶
            foreach (search($source)->where([['type','in',['sell','sre','extry']]])->select() as $item) {
                $currentList['customer'][]=$item['sourceData']['customer'];
            }
            empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
            //匹配供应商
            foreach (search($source)->where([['type','in',['buy','bre','entry']]])->select() as $item) {
                $currentList['supplier'][]=$item['sourceData']['supplier'];
            }
            empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
            $data=[];
            $cur=0;
            foreach ($source as $vo) {
                
                if($cur!=$vo['goods']){
                    $state=Db::name('summary')->where([['id','<',$vo['id']],['goods','=',$vo['goods']],['warehouse','=',$vo['warehouse']]])->order(['id'=>'DESC'])->find();
                    $state=empty($state)?['exist'=>[0,0,0,0],'balance'=>[0,0,0,0]]:['exist'=>json_decode($state['exist']),'balance'=>json_decode($state['balance'])];
                    $data[]=[
                        'extension'=>['type'=>'期初余额'],
                        'goodsData'=>['name'=>$vo['goodsData']['name']],
                        'balance'=>[
                            'uct'=>empty($state['exist'][0])?0:math()->chain($state['balance'][0])->div($state['exist'][0])->round(2)->done(),
                            'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($state['exist'][0],$vo['goodsData']['units']):$state['exist'][0],
                            'bct'=>$state['balance'][0]
                        ]
                    ];
                    $cur=$vo['goods'];
                }
                $row=$vo;
                //往来单位
                if(in_array($vo['type'],['buy','bre','entry'])){
                    $row['current']=search($currentList['supplier'])->where([['id','=',$vo['sourceData']['supplier']]])->find();
                }else if(in_array($vo['type'],['sell','sre','extry'])){
                    $row['current']=search($currentList['customer'])->where([['id','=',$vo['sourceData']['customer']]])->find();
                }else{
                    $row['current']=[];
                }
                $row['unit']=$vo['goodsData']['unit']==-1?'多单位':$vo['goodsData']['unit'];
                
                //出入方向
                $uns=$vo['goodsData']['unit']==-1?unitSwitch($vo['nums'],$vo['goodsData']['units']):$vo['nums'];
                if(empty($vo['direction'])){
                    //出库
                    $row['in']=['uct'=>'','uns'=>'','bct'=>''];
                    $row['out']=['uct'=>$vo['uct'],'uns'=>$uns,'bct'=>$vo['bct']];
                }else{
                    //入库
                    $row['in']=['uct'=>$vo['uct'],'uns'=>$uns,'bct'=>$vo['bct']];
                    $row['out']=['uct'=>'','uns'=>'','bct'=>''];
                }
                //汇总
                $exist=json_decode($vo['exist']);
                $balance=json_decode($vo['balance']);
                $row['balance']=[
                    'uct'=>empty($exist[0])?0:math()->chain($balance[0])->div($exist[0])->round(2)->done(),
                    'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($exist[0],$vo['goodsData']['units']):$exist[0],
                    'bct'=>$balance[0]
                ];
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
    //商品收发明细表-导出
    public function wdsExports(){
        $input=input('get.');
        $sheet=['buy','bre','sell','sre','swapOut','swapEnter','entry','extry'];
        existFull($input,['warehouse'])||$input['warehouse']=[];
		existFull($input,['mold'])||$input['mold']=$sheet;
        if(is_arrays($input,['warehouse','mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=fastSql($input,[
                ['warehouse','fullIn'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                [['mold'=>'type'],'fullIn']
            ]);
            $sql=sqlAuth('summary',$sql);
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['goods','in',$goods];
            }
            //子查询
            $existsSql=[['id','=',Db::raw('summary.class')]];
            $existsSql=frameScope($existsSql);
            //多源匹配
            $union=[];
            //数据关系表
            $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
            foreach ($table as $k=>$v) {
            	//匹配类型|减少查询
            	if(in_array($k,$input['mold'])){
            		$union[]=Db::name($v)->where([['summary.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
            	}
            }
            //合并子查询
            $union=implode(' UNION ALL ',$union);
            $source=Summary::with(['sourceData'=>['frameData'],'goodsData','warehouseData'])->alias('summary')->where($sql)->whereExists($union)->order(['goods','id'])->append(['extension'])->select()->toArray();
            //匹配往来单位
            $currentList=['customer'=>[],'supplier'=>[]];
            //匹配客戶
            foreach (search($source)->where([['type','in',['sell','sre','extry']]])->select() as $item) {
                $currentList['customer'][]=$item['sourceData']['customer'];
            }
            empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
            //匹配供应商
            foreach (search($source)->where([['type','in',['buy','bre','entry']]])->select() as $item) {
                $currentList['supplier'][]=$item['sourceData']['supplier'];
            }
            empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
            $data=[];
            $cur=0;
            foreach ($source as $vo) {
                if($cur!=$vo['goods']){
                    $state=Db::name('summary')->where([['id','<',$vo['id']],['goods','=',$vo['goods']]])->order(['id'=>'DESC'])->find();
                    $state=empty($state)?['exist'=>[0,0,0,0],'balance'=>[0,0,0,0]]:['exist'=>json_decode($state['exist']),'balance'=>json_decode($state['balance'])];
                    $data[]=[
                        'extension'=>['type'=>'期初余额'],
                        'goodsData'=>['name'=>$vo['goodsData']['name']],
                        'balance'=>[
                            'uct'=>empty($state['exist'][0])?0:math()->chain($state['balance'][0])->div($state['exist'][0])->round(2)->done(),
                            'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($state['exist'][0],$vo['goodsData']['units']):$state['exist'][0],
                            'bct'=>$state['balance'][0]
                        ]
                    ];
                    $cur=$vo['goods'];
                }
                $row=$vo;
                //往来单位
                if(in_array($vo['type'],['buy','bre','entry'])){
                    $row['current']=search($currentList['supplier'])->where([['id','=',$vo['sourceData']['supplier']]])->find();
                }else if(in_array($vo['type'],['sell','sre','extry'])){
                    $row['current']=search($currentList['customer'])->where([['id','=',$vo['sourceData']['customer']]])->find();
                }else{
                    $row['current']=[];
                }
                $row['unit']=$vo['goodsData']['unit']==-1?'多单位':$vo['goodsData']['unit'];
                
                //出入方向
                $uns=$vo['goodsData']['unit']==-1?unitSwitch($vo['nums'],$vo['goodsData']['units']):$vo['nums'];
                if(empty($vo['direction'])){
                    //出库
                    $row['in']=['uct'=>'','uns'=>'','bct'=>''];
                    $row['out']=['uct'=>$vo['uct'],'uns'=>$uns,'bct'=>$vo['bct']];
                }else{
                    //入库
                    $row['in']=['uct'=>$vo['uct'],'uns'=>$uns,'bct'=>$vo['bct']];
                    $row['out']=['uct'=>'','uns'=>'','bct'=>''];
                }
                //汇总
                $exist=json_decode($vo['exist']);
                $balance=json_decode($vo['balance']);
                $row['balance']=[
                    'uct'=>empty($exist[0])?0:math()->chain($balance[0])->div($exist[0])->round(2)->done(),
                    'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($exist[0],$vo['goodsData']['units']):$exist[0],
                    'bct'=>$balance[0]
                ];
                $data[]=$row;
            }
            $source=$data;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'商品收发明细表'];
            //表格数据
            $field=[
                'extension|type'=>'单据类型',
                'sourceData|frameData|name'=>'所属组织',
                'current|name'=>'往来单位',
                'sourceData|time'=>'单据时间',
                'sourceData|number'=>'单据编号',
                'goodsData|name'=>'商品名称',
                'attr'=>'辅助属性',
                'warehouseData|name'=>'仓库',
                'unit'=>'单位',
                'in|uct'=>'入库成本',
                'in|uns'=>'入库数量',
                'in|bct'=>'入库总成本',
                'out|uct'=>'出库成本',
                'out|uns'=>'出库数量',
                'out|bct'=>'出库总成本',
                'balance|uct'=>'汇总成本',
                'balance|uns'=>'汇总数量',
                'balance|bct'=>'汇总总成本'
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
            buildExcel('商品收发明细表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //商品收发汇总表
    public function wss(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && is_array($input['warehouse'])){
            $sql=fastSql($input,[
                ['warehouse','fullIn'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $sql=sqlAuth('summary',$sql);
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['goods','in',$goods];
            }
            //子查询
            $existsSql=[['id','=',Db::raw('summary.class')]];
            $existsSql=frameScope($existsSql);
            //多源匹配
            $union=[];
            //数据关系表
            $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
            foreach ($table as $k=>$v) {
                $union[]=Db::name($v)->where([['summary.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
            }
            //合并子查询
            $union=implode(' UNION ALL ',$union);
            $record=Db::name('summary')->alias('summary')->where($sql)->whereExists($union)->order(['id'])->select()->toArray();
            //分页数据
            $count=Summary::where([['id','in',array_column($record,'id')]])->group(['goods','warehouse'])->count();
            $data=Summary::with(['goodsData','warehouseData'])->where([['id','in',array_column($record,'id')]])->group(['goods','warehouse'])->page($input['page'],$input['limit'])->order(['goods'])->select()->toArray();
            foreach ($data as $key=>$vo) {
                $data[$key]['unit']=$vo['goodsData']['unit']==-1?'多单位':$vo['goodsData']['unit'];
                //期初
                $scope=search($record)->where([['goods','=',$vo['goods']]])->select();
                $state=Db::name('summary')->where([['id','<',$scope[0]['id']],['goods','=',$vo['goods']]])->order(['id'=>'DESC'])->find();
                $state=empty($state)?['exist'=>[0,0,0,0],'balance'=>[0,0,0,0]]:['exist'=>json_decode($state['exist']),'balance'=>json_decode($state['balance'])];
                $data[$key]['state']=[
                    'uct'=>empty($state['exist'][0])?0:math()->chain($state['balance'][0])->div($state['exist'][0])->round(2)->done(),
                    'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($state['exist'][0],$vo['goodsData']['units']):$state['exist'][0],
                    'bct'=>$state['balance'][0]
                ];
                $list=search($scope)->where([['goods','=',$vo['goods']],['warehouse','=',$vo['warehouse']]])->select();
                foreach ($table as $t=>$m) {
                    $group=search($list)->where([['type','=',$t]])->select();
                    if(empty($group)){
                        $data[$key][$t]=['uct'=>'','uns'=>'','bct'=>''];
                    }else{
                        $uns=0;
                        $bct=0;
                        foreach ($group as $v) {
                            $uns=math()->chain($uns)->add($v['nums'])->done();
                            $bct=math()->chain($bct)->add($v['bct'])->done();
                        }
                        $data[$key][$t]=[
                            'uct'=>math()->chain($bct)->div($uns)->round(2)->done(),
                            'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($uns,$vo['goodsData']['units']):$uns,
                            'bct'=>$bct
                        ];
                    }
                }
                //汇总
                $balance=$scope[count($scope)-1];
                $balance=['exist'=>json_decode($balance['exist']),'balance'=>json_decode($balance['balance'])];
                $sum=Db::name('summary')->where([['goods','=',$vo['goods']],['warehouse','=',$vo['warehouse']]])->order(['id'=>'desc'])->find();
                $data[$key]['balance']=[];
                $data[$key]['balance']['uct']=empty($balance['exist'][0])?0:math()->chain($balance['balance'][0])->div($balance['exist'][0])->round(2)->done();
                $data[$key]['balance']['uns']=$vo['goodsData']['unit']==-1?unitSwitch(json_decode($sum['exist'])[1],$vo['goodsData']['units']):json_decode($sum['exist'])[1];
                $data[$key]['balance']['bct']=math()->chain($data[$key]['balance']['uct'])->mul($data[$key]['balance']['uns'])->round(2)->done();
                
                
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
    //商品收发汇总表-导出
    public function wssExports(){
        $input=input('get.');
        existFull($input,['warehouse'])||$input['warehouse']=[];
        if(is_array($input['warehouse'])){
            $sql=fastSql($input,[
                ['warehouse','fullIn'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $sql=sqlAuth('summary',$sql);
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['goods','in',$goods];
            }
            //子查询
            $existsSql=[['id','=',Db::raw('summary.class')]];
            $existsSql=frameScope($existsSql);
            //多源匹配
            $union=[];
            //数据关系表
            $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
            foreach ($table as $k=>$v) {
                $union[]=Db::name($v)->where([['summary.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
            }
            //合并子查询
            $union=implode(' UNION ALL ',$union);
            $record=Db::name('summary')->alias('summary')->where($sql)->whereExists($union)->order(['id'])->select()->toArray();
            //分页数据
            $count=Summary::where([['id','in',array_column($record,'id')]])->group(['goods','warehouse'])->count();
            $data=Summary::with(['goodsData','warehouseData'])->where([['id','in',array_column($record,'id')]])->group(['goods','warehouse'])->order(['goods'])->select()->toArray();
            foreach ($data as $key=>$vo) {
                $data[$key]['unit']=$vo['goodsData']['unit']==-1?'多单位':$vo['goodsData']['unit'];
                //期初
                $scope=search($record)->where([['goods','=',$vo['goods']]])->select();
                $state=Db::name('summary')->where([['id','<',$scope[0]['id']],['goods','=',$vo['goods']]])->order(['id'=>'DESC'])->find();
                $state=empty($state)?['exist'=>[0,0,0,0],'balance'=>[0,0,0,0]]:['exist'=>json_decode($state['exist']),'balance'=>json_decode($state['balance'])];
                $data[$key]['state']=[
                    'uct'=>empty($state['exist'][0])?0:math()->chain($state['balance'][0])->div($state['exist'][0])->round(2)->done(),
                    'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($state['exist'][0],$vo['goodsData']['units']):$state['exist'][0],
                    'bct'=>$state['balance'][0]
                ];
                $list=search($scope)->where([['goods','=',$vo['goods']],['warehouse','=',$vo['warehouse']]])->select();
                foreach ($table as $t=>$m) {
                    $group=search($list)->where([['type','=',$t]])->select();
                    if(empty($group)){
                        $data[$key][$t]=['uct'=>'','uns'=>'','bct'=>''];
                    }else{
                        $uns=0;
                        $bct=0;
                        foreach ($group as $v) {
                            $uns=math()->chain($uns)->add($v['nums'])->done();
                            $bct=math()->chain($bct)->add($v['bct'])->done();
                        }
                        $data[$key][$t]=[
                            'uct'=>math()->chain($bct)->div($uns)->round(2)->done(),
                            'uns'=>$vo['goodsData']['unit']==-1?unitSwitch($uns,$vo['goodsData']['units']):$uns,
                            'bct'=>$bct
                        ];
                    }
                }
                //汇总
                $balance=$scope[count($scope)-1];
                $balance=['exist'=>json_decode($balance['exist']),'balance'=>json_decode($balance['balance'])];
                $sum=Db::name('summary')->where([['goods','=',$vo['goods']],['warehouse','=',$vo['warehouse']]])->order(['id'=>'desc'])->find();
                $data[$key]['balance']=[];
                $data[$key]['balance']['uct']=empty($balance['exist'][0])?0:math()->chain($balance['balance'][0])->div($balance['exist'][0])->round(2)->done();
                $data[$key]['balance']['uns']=$vo['goodsData']['unit']==-1?unitSwitch(json_decode($sum['exist'])[1],$vo['goodsData']['units']):json_decode($sum['exist'])[1];
                $data[$key]['balance']['bct']=math()->chain($data[$key]['balance']['uct'])->mul($data[$key]['balance']['uns'])->round(2)->done();
            }
            $source=$data;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'商品收发汇总表'];
            //表格数据
            $field=[
                'goodsData|name'=>'商品名称',
                'warehouseData|name'=>'仓库',
                'unit'=>'单位',
                'state|uct'=>'期初成本',
                'state|uns'=>'期初数量',
                'state|bct'=>'期初成本',
                'buy|uct'=>'采购成本',
                'buy|uns'=>'采购数量',
                'buy|bct'=>'采购总成本',
                'bre|uct'=>'购退成本',
                'bre|uns'=>'购退数量',
                'bre|bct'=>'购退总成本',
                'sell|uct'=>'销售成本',
                'sell|uns'=>'销售数量',
                'sell|bct'=>'销售总成本',
                'sre|uct'=>'销退成本',
                'sre|uns'=>'销退数量',
                'sre|bct'=>'销退总成本',
                'swapOut|uct'=>'调出成本',
                'swapOut|uns'=>'调出数量',
                'swapOut|bct'=>'调出总成本',
                'swapEnter|uct'=>'调入成本',
                'swapEnter|uns'=>'调入数量',
                'swapEnter|bct'=>'调入总成本',
                'entry|uct'=>'其入成本',
                'entry|uns'=>'其入数量',
                'entry|bct'=>'其入总成本',
                'extry|uct'=>'其出成本',
                'extry|uns'=>'其出数量',
                'extry|bct'=>'其出总成本',
                'balance|uct'=>'汇总成本',
                'balance|uns'=>'汇总数量',
                'balance|bct'=>'汇总总成本'
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
            buildExcel('商品收发汇总表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
}
