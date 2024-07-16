<?php
namespace app\controller;
use app\controller\Acl;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Srt extends Acl{
    //销售订单跟踪表
    public function stt(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && is_arrays($input,['state','warehouse']) && isset($input['type'])){
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                [['startArrival'=>'arrival'],'startTime'],
                [['endArrival'=>'arrival'],'endTime']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            $sql['class']=sqlAuth('sor',$sql['class']);//数据鉴权
            //INFO语句
            $sql['info']=fastSql($input,[['warehouse','fullIn']]);
            //商品匹配
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql['info'][]=['goods','in',$goods];
            }
            $sql['or']=[];
            //状态匹配
            if(existFull($input,['state'])){
                foreach ($input['state'] as $stateVo) {
                    $sql['or'][]=[['handle','=',0],Db::raw('handle > 0 AND handle < nums'),['handle','=',Db::raw('nums')]][$stateVo];
                }
            }
            //判断排序
            if(empty($input['type'])){
                //单据排序
                $record=Db::name('sor')->alias('class')->join(['is_sor_info'=>'info'],'class.id=info.pid')->where($sql['class'])->where($sql['info'])->where(function($query)use($sql){$query->whereOr($sql['or']);})->fieldRaw('class.id as class,group_concat(info.id) as info')->group('class.id')->order('class.id', 'desc')->select()->toArray();
                $data=[];
                $count=count($record);
                if(!empty($record)){
                    $classList = \app\model\Sor::with(['frameData','customerData'])->where([['id','in',array_column($record,'class')]])->append(['extension'])->page($input['page'],$input['limit'])->select()->toArray();
                    $infoList = \app\model\SorInfo::with(['goodsData','warehouseData'])->where([['pid','in',array_column($classList,'id')],['id','in',array_unique(explode(',',implode(',',array_column($record,'info'))))]])->select()->toArray();
                    foreach ($classList as $class) {
                        $class['key']=$class['id'];
                        $class['money']=$class['actual'];
                        $class['nmy']=0;
                        $info=search($infoList)->where([['pid','=',$class['id']]])->select();
                        foreach ($info as $key=>$vo) {
                            $info[$key]['key']=$class['id'].'_'.$vo['id'];
                            $info[$key]['price']=math()->chain($vo['tpt'])->div($vo['nums'])->round(2)->done();
                            $info[$key]['money']=$vo['tpt'];
                            $info[$key]['extension']['state']=$vo['handle']==0?'未出库':($vo['handle']==$vo['nums']?'已出库':'部分出库');
                            $info[$key]['nns']=math()->chain($vo['nums'])->sub($vo['handle'])->done();
                            $info[$key]['nmy']=math()->chain($info[$key]['price'])->mul($info[$key]['nns'])->done();
                            //汇总数据
                            $class['nmy']=math()->chain($class['nmy'])->add($info[$key]['nmy'])->done();
                        }
                        $class['node']=$info;
                        $data[]=$class;
                    }
                }
            }else{
                //商品排序
                $record=Db::name('sor_info')->alias('info')->join(['is_sor'=>'class'],'info.pid=class.id')->where($sql['class'])->where($sql['info'])->where(function($query)use($sql){$query->whereOr($sql['or']);})->fieldRaw('info.id as row,group_concat(info.id) as info')->group('info.goods,info.attr,info.warehouse')->order('info.id', 'desc')->select()->toArray();
                $data=[];
                $count=count($record);
                if(!empty($record)){
                    $record = array_slice($record,$input['limit']*($input['page']-1),$input['limit']);
                    $infoList = \app\model\SorInfo::with(['goodsData','warehouseData'])->where([['id','in',array_unique(explode(',',implode(',',array_column($record,'info'))))]])->select()->toArray();
                    $classList = \app\model\Sor::with(['frameData','customerData'])->where([['id','in',array_unique(array_column($infoList,'pid'))]])->append(['extension'])->select()->toArray();
                    foreach ($record as $recordVo) {
                        $info=search($infoList)->where([['id','in',explode(',',$recordVo['info'])]])->select();
                        $row=$info[0];
                        $row['key']=$row['id'];
                        $row['unit']='';
                        $row['price']='';
                        $row['nums']=0;
                        $row['money']=0;
                        $row['nns']=0;
                        $row['nmy']=0;
                        foreach ($info as $vo) {
                            $class=search($classList)->where([['id','=',$vo['pid']]])->find();
                            $class['key']=$vo['id'].'_'.$class['id'];
                            $class['unit']=$vo['unit'];
                            $class['price']=math()->chain($vo['tpt'])->div($vo['nums'])->round(2)->done();
                            $class['nums']=$vo['nums'];
                            $class['money']=$vo['tpt'];
                            $class['extension']['state']=$vo['handle']==0?'未出库':($vo['handle']==$vo['nums']?'已出库':'部分出库');
                            $class['nns']=math()->chain($vo['nums'])->sub($vo['handle'])->done();
                            $class['nmy']=math()->chain($class['price'])->mul($class['nns'])->done();
                            $class['data']=$vo['data'];
                            $class['basic']=['nums'=>$vo['nums'],'nns'=>$class['nns']];
                            //汇总数据
                            $row['money']=math()->chain($row['money'])->add($vo['tpt'])->done();
                            $row['nmy']=math()->chain($row['nmy'])->add($class['nmy'])->done();
                            //单位转换
                            if($vo['goodsData']['unit']=='-1'){
                                $radix=unitRadix($vo['unit'],$vo['goodsData']['units']);
                                $row['nums']=math()->chain($class['nums'])->mul($radix)->add($row['nums'])->done();
                                $row['nns']=math()->chain($class['nns'])->mul($radix)->add($row['nns'])->done();
                            }else{
                                $row['nums']=math()->chain($class['nums'])->add($row['nums'])->done();
                                $row['nns']=math()->chain($class['nns'])->add($row['nns'])->done();
                            }
                            $row['node'][]=$class;
                        }
                        $row['extension']['state']=$row['nns']==0?'已出库':($row['nns']==$row['nums']?'未出库':'部分出库');
                        //单位处理
                        if($row['goodsData']['unit']=='-1'){
                            $row['nums']=unitSwitch($row['nums'],$row['goodsData']['units']);
                            $row['nns']=unitSwitch($row['nns'],$row['goodsData']['units']);
                        }
                        $data[]=$row;
                    }
                }
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
    //销售订单跟踪表-导出
    public function sttExports(){
        $input=input('get.');
        existFull($input,['state'])||$input['state']=[];
        existFull($input,['warehouse'])||$input['warehouse']=[];
        if(is_arrays($input,['state','warehouse']) && isset($input['type'])){
            pushLog('导出销售订单跟踪表');//日志
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                [['startArrival'=>'arrival'],'startTime'],
                [['endArrival'=>'arrival'],'endTime']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            $sql['class']=sqlAuth('sor',$sql['class']);//数据鉴权
            //INFO语句
            $sql['info']=fastSql($input,[['warehouse','fullDivisionIn']]);
            //商品匹配
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql['info'][]=['goods','in',$goods];
            }
            $sql['or']=[];
            //状态匹配
            if(existFull($input,['state'])){
                foreach ($input['state'] as $stateVo) {
                    $sql['or'][]=[['handle','=',0],Db::raw('handle > 0 AND handle < nums'),['handle','=',Db::raw('nums')]][$stateVo];
                }
            }
            //判断排序
            if(empty($input['type'])){
                //单据排序
                $record=Db::name('sor')->alias('class')->join(['is_sor_info'=>'info'],'class.id=info.pid')->where($sql['class'])->where($sql['info'])->where(function($query)use($sql){$query->whereOr($sql['or']);})->fieldRaw('class.id as class,group_concat(info.id) as info')->group('class.id')->order('class.id', 'desc')->select()->toArray();
                $data=[];
                $count=count($record);
                if(!empty($record)){
                    $classList = \app\model\Sor::with(['frameData','customerData'])->where([['id','in',array_column($record,'class')]])->append(['extension'])->select()->toArray();
                    $infoList = \app\model\SorInfo::with(['goodsData','warehouseData'])->where([['pid','in',array_column($classList,'id')],['id','in',array_unique(explode(',',implode(',',array_column($record,'info'))))]])->select()->toArray();
                    foreach ($classList as $class) {
                        $class['key']=$class['id'];
                        $class['money']=$class['actual'];
                        $class['nmy']=0;
                        $info=search($infoList)->where([['pid','=',$class['id']]])->select();
                        foreach ($info as $key=>$vo) {
                            $info[$key]['key']=$class['id'].'_'.$vo['id'];
                            $info[$key]['price']=math()->chain($vo['tpt'])->div($vo['nums'])->round(2)->done();
                            $info[$key]['money']=$vo['tpt'];
                            $info[$key]['extension']['state']=$vo['handle']==0?'未出库':($vo['handle']==$vo['nums']?'已出库':'部分出库');
                            $info[$key]['nns']=math()->chain($vo['nums'])->sub($vo['handle'])->done();
                            $info[$key]['nmy']=math()->chain($info[$key]['price'])->mul($info[$key]['nns'])->done();
                            //汇总数据
                            $class['nmy']=math()->chain($class['nmy'])->add($info[$key]['nmy'])->done();
                        }
                        $class['node']=$info;
                        $data[]=$class;
                    }
                }
            }else{
                //商品排序
                $record=Db::name('sor_info')->alias('info')->join(['is_sor'=>'class'],'info.pid=class.id')->where($sql['class'])->where($sql['info'])->where(function($query)use($sql){$query->whereOr($sql['or']);})->fieldRaw('info.id as row,group_concat(info.id) as info')->group('info.goods,info.attr,info.warehouse')->order('info.id', 'desc')->select()->toArray();
                $data=[];
                $count=count($record);
                if(!empty($record)){
                    $infoList = \app\model\SorInfo::with(['goodsData','warehouseData'])->where([['id','in',array_unique(explode(',',implode(',',array_column($record,'info'))))]])->select()->toArray();
                    $classList = \app\model\Sor::with(['frameData','customerData'])->where([['id','in',array_unique(array_column($infoList,'pid'))]])->append(['extension'])->select()->toArray();
                    foreach ($record as $recordVo) {
                        $info=search($infoList)->where([['id','in',explode(',',$recordVo['info'])]])->select();
                        $row=$info[0];
                        $row['key']=$row['id'];
                        $row['unit']='';
                        $row['price']='';
                        $row['nums']=0;
                        $row['money']=0;
                        $row['nns']=0;
                        $row['nmy']=0;
                        foreach ($info as $vo) {
                            $class=search($classList)->where([['id','=',$vo['pid']]])->find();
                            $class['key']=$vo['id'].'_'.$class['id'];
                            $class['unit']=$vo['unit'];
                            $class['price']=math()->chain($vo['tpt'])->div($vo['nums'])->round(2)->done();
                            $class['nums']=$vo['nums'];
                            $class['money']=$vo['tpt'];
                            $class['extension']['state']=$vo['handle']==0?'未出库':($vo['handle']==$vo['nums']?'已出库':'部分出库');
                            $class['nns']=math()->chain($vo['nums'])->sub($vo['handle'])->done();
                            $class['nmy']=math()->chain($class['price'])->mul($class['nns'])->done();
                            $class['data']=$vo['data'];
                            $class['basic']=['nums'=>$vo['nums'],'nns'=>$class['nns']];
                            //汇总数据
                            $row['money']=math()->chain($row['money'])->add($vo['tpt'])->done();
                            $row['nmy']=math()->chain($row['nmy'])->add($class['nmy'])->done();
                            //单位转换
                            if($vo['goodsData']['unit']=='-1'){
                                $radix=unitRadix($vo['unit'],$vo['goodsData']['units']);
                                $row['nums']=math()->chain($class['nums'])->mul($radix)->add($row['nums'])->done();
                                $row['nns']=math()->chain($class['nns'])->mul($radix)->add($row['nns'])->done();
                            }else{
                                $row['nums']=math()->chain($class['nums'])->add($row['nums'])->done();
                                $row['nns']=math()->chain($class['nns'])->add($row['nns'])->done();
                            }
                            $row['node'][]=$class;
                        }
                        $row['extension']['state']=$row['nns']==0?'已出库':($row['nns']==$row['nums']?'未出库':'部分出库');
                        //单位处理
                        if($row['goodsData']['unit']=='-1'){
                            $row['nums']=unitSwitch($row['nums'],$row['goodsData']['units']);
                            $row['nns']=unitSwitch($row['nns'],$row['goodsData']['units']);
                        }
                        $data[]=$row;
                    }
                }
            }
            //结构重组
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
            $excel[]=['type'=>'title','info'=>'销售订单跟踪表'];
            //表格数据
            $field=[
                [
                    'frameData|name'=>'所属组织',
                    'customerData|name'=>'客户',
                    'time'=>'单据时间',
                    'number'=>'单据编号',
                    'goodsData|name'=>'商品名称',
                    'attr'=>'辅助属性',
                    'warehouseData|name'=>'仓库',
                ],[
                    'goodsData|name'=>'商品名称',
                    'attr'=>'辅助属性',
                    'warehouseData|name'=>'仓库',
                    'frameData|name'=>'所属组织',
                    'customerData|name'=>'客户',
                    'time'=>'单据时间',
                    'number'=>'单据编号'
                ],[
                    'unit'=>'单位',
                    'price'=>'单价',
                    'nums'=>'数量',
                    'money'=>'金额',
                    'extension|state'=>'出库状态',
                    'nns'=>'未出库数量',
                    'nmy'=>'未出库金额',
                    'arrival'=>'到货日期',
                    'data'=>'备注信息'
                ]
            ];
            $field=array_merge(empty($input['type'])?$field[0]:$field[1],$field[2]);
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
            buildExcel('销售订单跟踪表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //销售明细表
    public function slt(){
        $input=input('post.');
        $sheet=['sell','sre'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_arrays($input,['warehouse','mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                [['data'=>'class.data'],'fullLike']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            //数据鉴权[结构一致]
            $sql['class']=sqlAuth('sell',$sql['class']);
            //INFO语句
            $sql['info']=fastSql($input,[['warehouse','fullIn']]);
            //商品匹配
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql['info'][]=['goods','in',$goods];
            }
            //组装查询语句
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold.'_info')->alias('info')->join(['is_'.$mold=>'class'],'info.pid=class.id')->where($sql['info'])->where($sql['class'])->fieldRaw('info.id as info,class.id as class,time,"'.$mold.'" as mold')->buildSql();
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
                $list[$mold]['info']=$table['info']::with(['goodsData','warehouseData'])->where([['id','in',array_column($gather,'info')]])->select()->toArray();
                $list[$mold]['class']=$table['class']::with(['frameData','customerData'])->where([['id','in',array_column($list[$mold]['info'],'pid')]])->append(['extension'])->select()->toArray();
            }
            $data=[];
            foreach ($record as $recordVo) {
                $mold=$recordVo['mold'];
                $data[]=[
                    'mold'=>$mold,
                    'name'=>['sell'=>'销售单','sre'=>'销售退货单'][$mold],
                    'class'=>search($list[$mold]['class'])->where([['id','=',$recordVo['class']]])->find(),
                    'info'=>search($list[$mold]['info'])->where([['id','=',$recordVo['info']]])->find()
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
    //销售明细表-导出
    public function sltExports(){
        $input=input('get.');
        $sheet=['sell','sre'];
        existFull($input,['warehouse'])||$input['warehouse']=[];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(is_arrays($input,['warehouse','mold']) && arrayInArray($input['mold'],$sheet)){
            pushLog('导出销售明细表');//日志
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                [['data'=>'class.data'],'fullLike']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            //数据鉴权[结构一致]
            $sql['class']=sqlAuth('sell',$sql['class']);
            //INFO语句
            $sql['info']=fastSql($input,[['warehouse','fullDivisionIn']]);
            //商品匹配
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql['info'][]=['goods','in',$goods];
            }
            //组装查询语句
            $union=[];
            foreach ($input['mold'] as $mold) {
                    $union[]=Db::name($mold.'_info')->alias('info')->join(['is_'.$mold=>'class'],'info.pid=class.id')->where($sql['info'])->where($sql['class'])->fieldRaw('info.id as info,class.id as class,time,"'.$mold.'" as mold')->buildSql();
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
                $list[$mold]['info']=$table['info']::with(['goodsData','warehouseData'])->where([['id','in',array_column($gather,'info')]])->select()->toArray();
                $list[$mold]['class']=$table['class']::with(['frameData','customerData'])->where([['id','in',array_column($list[$mold]['info'],'pid')]])->append(['extension'])->select()->toArray();
            }
            $data=[];
            foreach ($record as $recordVo) {
                $mold=$recordVo['mold'];
                $data[]=[
                    'mold'=>$mold,
                    'name'=>['sell'=>'销售单','sre'=>'销售退货单'][$mold],
                    'class'=>search($list[$mold]['class'])->where([['id','=',$recordVo['class']]])->find(),
                    'info'=>search($list[$mold]['info'])->where([['id','=',$recordVo['info']]])->find()
                ];
            }
            $source=$data;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'销售明细表'];
            //表格数据
            $field=[
                'name'=>'单据类型',
                'class|frameData|name'=>'所属组织',
                'class|customerData|name'=>'客户',
                'class|time'=>'单据时间',
                'class|number'=>'单据编号',
                'info|goodsData|name'=>'商品名称',
                'info|attr'=>'辅助属性',
                'info|warehouseData|name'=>'仓库',
                'info|unit'=>'单位',
                'info|price'=>'单价',
                'info|nums'=>'数量',
                'info|dsc'=>'折扣额',
                'info|total'=>'金额',
                'info|tat'=>'税额',
                'info|tpt'=>'价税合计',
                'class|data'=>'备注信息',
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
            $math=['sell'=>[],'sre'=>[]];
            foreach ($source as $sourceVo) {
                $math[$sourceVo['mold']][]=$sourceVo['info']['tpt'];
            }
            $excel[]=['type'=>'node','info'=>[
                '总数:'.count($source),
                '销售总金额:'.mathArraySum($math['sell']),
                '销售退货总金额:'.mathArraySum($math['sre'])
            ]];
            //导出execl
            buildExcel('销售明细表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //销售汇总表
    public function ssy(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && is_array($input['warehouse']) && isset($input['type'])){
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['customer','fullEq'],
                ['user','fullEq'],
                ['people','fullEq'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            $sql['class']=sqlAuth('sell',$sql['class']);//数据鉴权[结构一致]
            //INFO语句
            $sql['info']=fastSql($input,[['warehouse','fullIn']]);
            //商品匹配
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql['info'][]=['goods','in',$goods];
            }
            //构造语句
            $union=[];
            $tab=['sell','sre'];
            foreach ($tab as $t) {
                $union[]=Db::name($t.'_info')->alias('info')->join(['is_'.$t=>'class'],'info.pid=class.id')->where($sql['class'])->where($sql['info'])->fieldRaw('"'.$t.'" as mold,class.customer as customer,class.user as user,class.people as people,info.id as info,goods,attr,warehouse,unit,nums,tpt')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //判断类型
            if($input['type']==0){
                //按商品
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by goods,attr,warehouse  ORDER BY `goods` DESC  LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            }else if($input['type']==1){
                //按客户
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by customer,goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,customer,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by customer,goods,attr,warehouse ORDER BY `customer` DESC LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            }else if($input['type']==2){
                //按用户
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by user,goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,user,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by user,goods,attr,warehouse ORDER BY `user` DESC LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            }else{
                //按人员
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by people,goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,people,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by people,goods,attr,warehouse ORDER BY `people` DESC LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            }
            //构造数据
            $group = [];
            foreach($record as $vo){
                $moldList = explode(",", $vo['mold']);
                $infoList = explode(",", $vo['info']);
                $unitList = explode(",", $vo['unit']);
                $numsList = explode(",", $vo['nums']);
                $tptList = explode(",", $vo['tpt']);
                $row=['mold'=>$moldList[0],'info'=>$infoList[0]];
                foreach ($moldList as $key => $mold) {
                    $row[$mold]['unit'][]=$unitList[$key];
                    $row[$mold]['nums'][]=$numsList[$key];
                    $row[$mold]['tpt'][]=$tptList[$key];
                }
                $input['type']==1&&$row['customer']=$vo['customer'];//客户转存
                $input['type']==2&&$row['user']=$vo['user'];//用户转存
                $input['type']==3&&$row['people']=$vo['people'];//人员转存
                $group[]=$row;
            }
            //数据匹配
            $infoList=[];
            foreach ($tab as $t) {
                $mold="app\\model\\".ucfirst($t).'Info';
                $gather=search($group)->where([['mold','=',$t]])->select();
                $infoList[$t]=$mold::with(['goodsData','warehouseData'])->where([['id','in',array_column($gather,'info')]])->select()->toArray();
            }
            //查询类型-匹配客户
            $input['type']==1&&$customerList=db::name('customer')->where([['id','in',array_column($group,'customer')]])->select()->toArray();
            //查询类型-匹配用户
            $input['type']==2&&$userList=db::name('user')->where([['id','in',array_column($group,'user')]])->select()->toArray();
            //查询类型-匹配人员
            $input['type']==3&&$peopleList=db::name('people')->where([['id','in',array_column($group,'people')]])->select()->toArray();
            //数据处理
            $data=[];
            foreach ($group as $groupVo) {
                $row=search($infoList[$groupVo['mold']])->where([['id','=',$groupVo['info']]])->find();
                $row['unit']=$row['goodsData']['unit']==-1?'多单位':$row['unit'];
                foreach ($tab as $t) {
                    if(isset($groupVo[$t])){
                        if($row['goodsData']['unit']==-1){
                            $base=0;
                            foreach ($groupVo[$t]['unit'] as $key=> $unit) {
                                $radix=unitRadix($unit,$row['goodsData']['units']);
                                $base=math()->chain($groupVo[$t]['nums'][$key])->mul($radix)->add($base)->done();
                            }
                            $row[$t]=['base'=>$base,'nums'=>unitSwitch($base,$row['goodsData']['units']),'money'=>mathArraySum($groupVo[$t]['tpt'])];
                            $row[$t]['price']=math()->chain($row[$t]['money'])->div($base)->round(2)->done();
                        }else{
                            $row[$t]=['nums'=>mathArraySum($groupVo[$t]['nums']),'money'=>mathArraySum($groupVo[$t]['tpt'])];
                            $row[$t]['price']=math()->chain($row[$t]['money'])->div($row[$t]['nums'])->round(2)->done();
                            $row[$t]['base']=$row[$t]['nums'];
                        }
                    }else{
                        $row[$t]=['base'=>0,'price'=>0,'nums'=>0,'money'=>0];
                    }
                }
                $row['summary']['nums']=math()->chain($row['sell']['base'])->sub($row['sre']['base'])->done();
                $row['goodsData']['unit']==-1&&$row['summary']['nums']=unitSwitch($row['summary']['nums'],$row['goodsData']['units']);
                $row['summary']['money']=math()->chain($row['sell']['money'])->sub($row['sre']['money'])->done();
                //类型匹配
                $input['type']==1&&$row['customer']=search($customerList)->where([['id','=',$groupVo['customer']]])->find();//匹配客户
                $input['type']==2&&$row['user']=search($userList)->where([['id','=',$groupVo['user']]])->find();//匹配用户
                $input['type']==3&&$row['people']=search($peopleList)->where([['id','=',$groupVo['people']]])->find();//匹配人员
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
    //销售汇总表-导出
    public function ssyExports(){
        $input=input('get.');
        existFull($input,['warehouse'])||$input['warehouse']=[];
        if(is_array($input['warehouse']) && isset($input['type'])){
            pushLog('导出销售汇总表');//日志
            $sql=[];
            //CLASS语句
            $sql['class']=fastSql($input,[
                ['customer','fullEq'],
                ['user','fullEq'],
                ['people','fullEq'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $sql['class'][]=['examine','=',1];
            $sql['class']=frameScope($sql['class']);//组织数据
            $sql['class']=sqlAuth('sell',$sql['class']);//数据鉴权[结构一致]
            //INFO语句
            $sql['info']=fastSql($input,[['warehouse','fullDivisionIn']]);
            //商品匹配
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql['info'][]=['goods','in',$goods];
            }
            //构造语句
            $union=[];
            $tab=['sell','sre'];
            foreach ($tab as $t) {
                $union[]=Db::name($t.'_info')->alias('info')->join(['is_'.$t=>'class'],'info.pid=class.id')->where($sql['class'])->where($sql['info'])->fieldRaw('"'.$t.'" as mold,class.customer as customer,class.user as user,class.people as people,info.id as info,goods,attr,warehouse,unit,nums,tpt')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //判断类型
            if($input['type']==0){
                //按商品
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by goods,attr,warehouse  ORDER BY `goods` DESC');
            }else if($input['type']==1){
                //按客户
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by customer,goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,customer,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by customer,goods,attr,warehouse ORDER BY `customer` DESC');
            }else if($input['type']==2){
                //按用户
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by user,goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,user,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by user,goods,attr,warehouse ORDER BY `user` DESC');
            }else{
                //按人员
                $count=count(DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud group by people,goods,attr,warehouse'));
                $record = DB::query('SELECT group_concat(mold) as mold,people,group_concat(info) as info,group_concat(unit) as unit,group_concat(nums) as nums,group_concat(tpt) as tpt FROM ( '.$union.' ) as nodcloud group by people,goods,attr,warehouse ORDER BY `people` DESC');
            }
            //构造数据
            $group = [];
            foreach($record as $vo){
                $moldList = explode(",", $vo['mold']);
                $infoList = explode(",", $vo['info']);
                $unitList = explode(",", $vo['unit']);
                $numsList = explode(",", $vo['nums']);
                $tptList = explode(",", $vo['tpt']);
                $row=['mold'=>$moldList[0],'info'=>$infoList[0]];
                foreach ($moldList as $key => $mold) {
                    $row[$mold]['unit'][]=$unitList[$key];
                    $row[$mold]['nums'][]=$numsList[$key];
                    $row[$mold]['tpt'][]=$tptList[$key];
                }
                $input['type']==1&&$row['customer']=$vo['customer'];//客户转存
                $input['type']==2&&$row['user']=$vo['user'];//用户转存
                $input['type']==3&&$row['people']=$vo['people'];//人员转存
                $group[]=$row;
            }
            //数据匹配
            $infoList=[];
            foreach ($tab as $t) {
                $mold="app\\model\\".ucfirst($t).'Info';
                $gather=search($group)->where([['mold','=',$t]])->select();
                $infoList[$t]=$mold::with(['goodsData','warehouseData'])->where([['id','in',array_column($gather,'info')]])->select()->toArray();
            }
            //查询类型-匹配客户
            $input['type']==1&&$customerList=db::name('customer')->where([['id','in',array_column($group,'customer')]])->select()->toArray();
            //查询类型-匹配用户
            $input['type']==2&&$userList=db::name('user')->where([['id','in',array_column($group,'user')]])->select()->toArray();
            //查询类型-匹配人员
            $input['type']==3&&$peopleList=db::name('people')->where([['id','in',array_column($group,'people')]])->select()->toArray();
            //数据处理
            $data=[];
            foreach ($group as $groupVo) {
                $row=search($infoList[$groupVo['mold']])->where([['id','=',$groupVo['info']]])->find();
                $row['unit']=$row['goodsData']['unit']==-1?'多单位':$row['unit'];
                foreach ($tab as $t) {
                    if(isset($groupVo[$t])){
                        if($row['goodsData']['unit']==-1){
                            $base=0;
                            foreach ($groupVo[$t]['unit'] as $key=> $unit) {
                                $radix=unitRadix($unit,$row['goodsData']['units']);
                                $base=math()->chain($groupVo[$t]['nums'][$key])->mul($radix)->add($base)->done();
                            }
                            $row[$t]=['base'=>$base,'nums'=>unitSwitch($base,$row['goodsData']['units']),'money'=>mathArraySum($groupVo[$t]['tpt'])];
                            $row[$t]['price']=math()->chain($row[$t]['money'])->div($base)->round(2)->done();
                        }else{
                            $row[$t]=['nums'=>mathArraySum($groupVo[$t]['nums']),'money'=>mathArraySum($groupVo[$t]['tpt'])];
                            $row[$t]['price']=math()->chain($row[$t]['money'])->div($row[$t]['nums'])->round(2)->done();
                            $row[$t]['base']=$row[$t]['nums'];
                        }
                    }else{
                        $row[$t]=['base'=>0,'price'=>0,'nums'=>0,'money'=>0];
                    }
                }
                $row['summary']['nums']=math()->chain($row['sell']['base'])->sub($row['sre']['base'])->done();
                $row['goodsData']['unit']==-1&&$row['summary']['nums']=unitSwitch($row['summary']['nums'],$row['goodsData']['units']);
                $row['summary']['money']=math()->chain($row['sell']['money'])->sub($row['sre']['money'])->done();
                //类型匹配
                $input['type']==1&&$row['customer']=search($customerList)->where([['id','=',$groupVo['customer']]])->find();//匹配客户
                $input['type']==2&&$row['user']=search($userList)->where([['id','=',$groupVo['user']]])->find();//匹配用户
                $input['type']==3&&$row['people']=search($peopleList)->where([['id','=',$groupVo['people']]])->find();//匹配人员
                $data[]=$row;
            }
            $source=$data;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'销售汇总表'];
            //表格数据
            $field=[
                [
                    "customer|name"=>"客户"
                ],
                [
                    "user|name"=>"用户"
                ],
                [
                    "people|name"=>"关联人员"
                ],
                [
                    "goodsData|name"=>"商品名称",
                    "attr"=>"辅助属性",
                    "warehouseData|name"=>"仓库",
                    "unit"=>"单位",
                    "sell|price"=>"销售单价",
                    "sell|nums"=>"销售数量",
                    "sell|money"=>"销售金额",
                    "sre|price"=>"购退单价",
                    "sre|nums"=>"购退数量",
                    "sre|money"=>"购退金额",
                    "summary|nums"=>"汇总数量",
                    "summary|money"=>"汇总金额"
                ]
            ];
            $field=[$field[3],array_merge($field[0],$field[3]),array_merge($field[1],$field[3]),array_merge($field[2],$field[3]),][$input['type']];
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
                '销售总金额:'.mathArraySum(arrayColumns($source,['sell','money'])),
                '销售退货总金额:'.mathArraySum(arrayColumns($source,['sre','money'])),
                '汇总金额:'.mathArraySum(arrayColumns($source,['summary','money']))
            ]];
            //导出execl
            buildExcel('销售汇总表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //销售收款表
    public function sbt(){
        $input=input('post.');
        $sheet=['sell','sre'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_arrays($input,['nucleus','mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=[];
            //CLASS语句
            $sql=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['nucleus','fullIn']
            ]);
            $sql[]=['examine','=',1];
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('sell',$sql);//数据鉴权[结构一致]
            //组装语句
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->where($sql)->fieldRaw('"'.$mold.'" as mold,id,time')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //获取总条数
            $count=DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud')[0]["count"];
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud ORDER BY `time` DESC LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            //匹配数据
            $list=[];
            foreach ($input['mold'] as $mold) {
                $gather=search($record)->where([['mold','=',$mold]])->select();
                $db="app\\model\\".ucfirst($mold);
                $list[$mold]=$db::with(['frameData','customerData','billData'])->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray();
            }
            //构造数据
            $data=[];
            foreach ($record as $recordVo) {
                $mold=$recordVo['mold'];
                $row=search($list[$mold])->where([['id','=',$recordVo['id']]])->find();
                $row['key']=$mold.'_'.$recordVo['id'];
                $row['name']=['sell'=>'销售单','sre'=>'销售退货单'][$mold];
                $row['balance']=$row['extension']['anwo'];
                $row['rate']=in_array($row['nucleus'],['0','2'])?['0%','','100%'][$row['nucleus']]:math()->chain($row['extension']['amount'])->div($row['actual'])->mul(100)->round(2)->done().'%';
                $row['node']=[];
                $bill=search($row['billData'])->where([['type','=','bill']])->select();
                foreach ($bill as $billVo) {
                    $node=[
                        'key'=>$row['key'].'_'.$billVo['id'],
                        'name'=>'核销单',
                        'time'=>$billVo['time'],
                        'number'=>$billVo['sourceData']['number'],
                        'money'=>$billVo['money'],
                    ];
                    //反转金额
                    in_array($mold,['sre'])&&$node['money']*=-1;
                    $row['node'][]=$node;
                }
                //反转金额
                if(in_array($mold,['sre'])){
                    $row['total']*=-1;
                    $row['actual']*=-1;
                    $row['money']*=-1;
                    $row['balance']*=-1;
                }
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
    //销售收款表-导出
    public function sbtExports(){
        $input=input('get.');
        $sheet=['sell','sre'];
        existFull($input,['nucleus'])||$input['nucleus']=[];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(is_arrays($input,['nucleus','mold']) && arrayInArray($input['mold'],$sheet)){
            pushLog('导出销售收款表');//日志
            $sql=[];
            //CLASS语句
            $sql=fastSql($input,[
                ['customer','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['nucleus','fullDivisionIn']
            ]);
            $sql[]=['examine','=',1];
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('sell',$sql);//数据鉴权[结构一致]
            //组装语句
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->where($sql)->fieldRaw('"'.$mold.'" as mold,id,time')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            //获取总条数
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud ORDER BY `time` DESC');
            //匹配数据
            $list=[];
            foreach ($input['mold'] as $mold) {
                $gather=search($record)->where([['mold','=',$mold]])->select();
                $db="app\\model\\".ucfirst($mold);
                $list[$mold]=$db::with(['frameData','customerData','billData'])->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray();
            }
            //构造数据
            $data=[];
            foreach ($record as $recordVo) {
                $mold=$recordVo['mold'];
                $row=search($list[$mold])->where([['id','=',$recordVo['id']]])->find();
                $row['key']=$mold.'_'.$recordVo['id'];
                $row['name']=['sell'=>'销售单','sre'=>'销售退货单'][$mold];
                $row['balance']=$row['extension']['anwo'];
                $row['rate']=in_array($row['nucleus'],['0','2'])?['0%','','100%'][$row['nucleus']]:math()->chain($row['extension']['amount'])->div($row['actual'])->mul(100)->round(2)->done().'%';
                $row['node']=[];
                $bill=search($row['billData'])->where([['type','=','bill']])->select();
                foreach ($bill as $billVo) {
                    $node=[
                        'key'=>$row['key'].'_'.$billVo['id'],
                        'name'=>'核销单',
                        'time'=>$billVo['time'],
                        'number'=>$billVo['sourceData']['number'],
                        'money'=>$billVo['money'],
                    ];
                    //反转金额
                    in_array($mold,['sre'])&&$node['money']*=-1;
                    $row['node'][]=$node;
                }
                //反转金额
                if(in_array($mold,['sre'])){
                    $row['total']*=-1;
                    $row['actual']*=-1;
                    $row['money']*=-1;
                    $row['balance']*=-1;
                }
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
            $excel[]=['type'=>'title','info'=>'销售收款表'];
            //表格数据
            $field=[
                "name"=>"单据类型",
                "frameData|name"=>"所属组织",
                "customerData|name"=>"客户", 
                "time"=>"单据时间",
                "number"=>"单据编号",
                "total"=>"单据金额",
                "actual"=>"实际金额",
                "money"=>"单据付款",
                "balance"=>"应付款余额",
                "rate"=>"付款率",
                "extension|nucleus"=>"核销状态",
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
                '应收款总余额:'.mathArraySum(array_column($source,'balance'))
            ]];
            //导出execl
            buildExcel('销售收款表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
}
