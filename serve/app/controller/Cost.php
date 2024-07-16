<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Cost as Costs;
use app\model\CostInfo;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Cost extends Acl{
    //购销费用
    public function record(){
        $input=input('post.');
        $sheet=['buy','bre','sell','sre','swap','entry','extry'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_arrays($input,['iet','state','mold']) && arrayInArray($input['mold'],$sheet)){
            $sql=[];
            //查询语句
            $sql['cost']=fastSql($input,[
                [['mold'=>'type'],'fullIn'],
                ['iet','fullIn'],
                ['state','fullIn']
            ]);
            //基础语句
            $sql['base']=fastSql($input,[
                [['number'=>'number'],'fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $sql['base'][]=['examine','=',1];
            $sql['base'][]=['id','=',Db::raw('cost.class')];
            $sql['base']=frameScope($sql['base']);
            //场景匹配
            foreach ($input['mold'] as $mold) {
                if(in_array($mold,['buy','bre','entry'])){
                    //供应商
                    $sql[$mold]=array_merge($sql['base'],fastSql($input,[['supplier','fullEq']]));
                }else if(in_array($mold,['sell','sre','extry'])){
                    //客户
                    $sql[$mold]=array_merge($sql['base'],fastSql($input,[['customer','fullEq']]));
                }else{
                    //调拨单
                    $sql[$mold]=$sql['base'];
                }
                $sql[$mold]=sqlAuth($mold,$sql[$mold]);//数据鉴权
            }
            //构造查询
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->alias('class')->where([['cost.type','=',$mold]])->where($sql[$mold])->limit(1)->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $count=Costs::alias('cost')->where($sql['cost'])->whereExists($union)->count();
            $info=Costs::with(['sourceData'=>['frameData'],'ietData'])->alias('cost')->where($sql['cost'])->whereExists($union)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            //匹配往来单位
            $currentList=['customer'=>[],'supplier'=>[]];
            //匹配客戶
            foreach (search($info)->where([['type','in',['sell','sre','extry']]])->select() as $item) {
                $currentList['customer'][]=$item['sourceData']['customer'];
            }
            empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
            //匹配供应商
            foreach (search($info)->where([['type','in',['buy','bre','entry']]])->select() as $item) {
                $currentList['supplier'][]=$item['sourceData']['supplier'];
            }
            empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
            foreach ($info as $key=>$vo) {
                //未结算金额
                $info[$key]['uat']=math()->chain($vo['money'])->sub($vo['settle'])->done();
                //往来单位
                if(in_array($vo['type'],['buy','bre','entry'])){
                    $info[$key]['current']=search($currentList['supplier'])->where([['id','=',$vo['sourceData']['supplier']]])->find();
                }else if(in_array($vo['type'],['sell','sre','extry'])){
                    $info[$key]['current']=search($currentList['customer'])->where([['id','=',$vo['sourceData']['customer']]])->find();
                }else{
                    $info[$key]['current']=[];
                }
                $info[$key]['csa']='';
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
    //购销费用-导出
    public function exports(){
        $input=input('get.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            pushLog('导出购销费用');//日志
            $info=Costs::with(['sourceData'=>['frameData'],'ietData'])->alias('cost')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            //匹配往来单位
            $currentList=['customer'=>[],'supplier'=>[]];
            //匹配客戶
            foreach (search($info)->where([['type','in',['sell','sre','extry']]])->select() as $item) {
                $currentList['customer'][]=$item['sourceData']['customer'];
            }
            empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
            //匹配供应商
            foreach (search($info)->where([['type','in',['buy','bre','entry']]])->select() as $item) {
                $currentList['supplier'][]=$item['sourceData']['supplier'];
            }
            empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
            foreach ($info as $key=>$vo) {
                //未结算金额
                $info[$key]['uat']=math()->chain($vo['money'])->sub($vo['settle'])->done();
                //往来单位
                if(in_array($vo['type'],['buy','bre','entry'])){
                    $info[$key]['current']=search($currentList['supplier'])->where([['id','=',$vo['sourceData']['supplier']]])->find();
                }else if(in_array($vo['type'],['sell','sre','extry'])){
                    $info[$key]['current']=search($currentList['customer'])->where([['id','=',$vo['sourceData']['customer']]])->find();
                }else{
                    $info[$key]['current']=[];
                }
            }
            $source=$info;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'购销费用'];
            //表格数据 
            $field=[
                'extension|type'=>'单据类型',
                'sourceData|frameData|name'=>'所属组织',
                'current|name'=>'往来单位',
                'sourceData|time'=>'单据时间',
                'sourceData|number'=>'单据编号',
                'ietData|name'=>'支出类别',
                'extension|state'=>'结算状态',
                'money'=>'金额',
                'settle'=>'已结算金额',
                'uat'=>'未结算金额'
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
            $excel[]=['type'=>'node','info'=>[
                '总数:'.count($source),
                '单据总金额:'.mathArraySum(array_column($source,'money')),
                '已结算总金额:'.mathArraySum(array_column($source,'settle')),
                '未结算总金额:'.mathArraySum(array_column($source,'uat'))
            ]];
            //导出execl
            buildExcel('购销费用',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
    //生成其它支出单
    public function buildOce(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            //源数据
            $list=Costs::with(['ietData'])->where([['id','in',array_column($input['parm'],'id')],['state','<>',2]])->order(['id'=>'asc'])->select()->toArray();
            if(empty($list)){
                $result=['state'=>'warning','info'=>'操作失败,无可结算数据!'];
            }else{
                //CLASS数据
                $class=[
                    'total'=>0
                ];
                //INFO数据
                $info=[];
                foreach ($list as $vo) {
                    $find=search($input['parm'])->where([['id','=',$vo['id']]])->find();
                    //判断结算金额
                    if(bccomp($find['csa'],math()->chain($vo['money'])->sub($vo['settle'])->done())==1){
                        $item=Costs::with(['sourceData'])->where([['id','=',$vo['id']]])->find();
                        return json(['state'=>'warning','info'=>'单据编号[ '.$item['sourceData']['number'].' ]结算金额不可大于未结算金额!']);
                        exit;
                    }else{
                        //转存数据
                        $info[]=[
                            'source'=>$vo['id'],
                            'iet'=>$vo['iet'],
                            'ietData'=>$vo['ietData'],
                            'money'=>$find['csa'],
                            'data'=>''
                        ];
                        $class['total']=math()->chain($class['total'])->add($find['csa'])->done();//累加单据金额
                    }
                }
                $result=['state'=>'success','info'=>['class'=>$class,'info'=>$info]];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    
	//报表数据
	public function form(){
	    $input=input('post.');
	    $sheet=['buy','bre','sell','sre','entry','extry'];
        existFull($input,['state'])||$input['state']=[1,2];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_arrays($input,['iet','state','mold']) && arrayInArray($input['state'],[1,2]) && arrayInArray($input['mold'],$sheet)){
            $sql=[];
            //查询语句
            $sql['cost']=fastSql($input,[
                [['mold'=>'type'],'fullIn'],
                ['iet','fullIn'],
                ['state','fullIn']
            ]);
            //基础语句
            $sql['base']=fastSql($input,[
                [['number'=>'number'],'fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $sql['base'][]=['examine','=',1];
            $sql['base'][]=['id','=',Db::raw('cost.class')];
            $sql['base']=frameScope($sql['base']);
            //场景匹配
            foreach ($input['mold'] as $mold) {
                if(in_array($mold,['buy','bre','entry'])){
                    $sql[$mold]=array_merge($sql['base'],fastSql($input,[['supplier','fullEq']]));
                }else{
                    $sql[$mold]=array_merge($sql['base'],fastSql($input,[['customer','fullEq']]));
                }
                $sql[$mold]=sqlAuth($mold,$sql[$mold]);//数据鉴权
            }
            //构造查询
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->alias('class')->where([['cost.type','=',$mold]])->where($sql[$mold])->limit(1)->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $count=Costs::alias('cost')->where($sql['cost'])->whereExists($union)->count();
            $info=Costs::with(['sourceData'=>['frameData'],'ietData'])->alias('cost')->where($sql['cost'])->whereExists($union)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            //匹配往来单位
            $currentList=['customer'=>[],'supplier'=>[]];
            //匹配客戶
            foreach (search($info)->where([['type','in',['sell','sre','extry']]])->select() as $item) {
                $currentList['customer'][]=$item['sourceData']['customer'];
            }
            empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
            //匹配供应商
            foreach (search($info)->where([['type','in',['buy','bre','entry']]])->select() as $item) {
                $currentList['supplier'][]=$item['sourceData']['supplier'];
            }
            empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
            //查询子节点
            if(!empty($info)){
                $gather=CostInfo::with(['oceData'=>['frameData','supplierData']])->where([['pid','in',array_column($info,'id')]])->select()->toArray();
            }
            foreach ($info as $key=>$vo) {
                $info[$key]['key']=$vo['id'];
                //往来单位
                if(in_array($vo['type'],['buy','bre','entry'])){
                    $info[$key]['current']=search($currentList['supplier'])->where([['id','=',$vo['sourceData']['supplier']]])->find();
                }else if(in_array($vo['type'],['sell','sre','extry'])){
                    $info[$key]['current']=search($currentList['customer'])->where([['id','=',$vo['sourceData']['customer']]])->find();
                }else{
                    $info[$key]['current']=[];
                }
                $node=[];
                $costInfo=search($gather)->where([['pid','=',$vo['id']]])->select();
                foreach($costInfo as $costInfoVo){
                    $node[]=[
                        'key'=>$costInfoVo['pid'].'_'.$costInfoVo['id'],
                        'extension'=>['type'=>'其它支出单','state'=>'-'],
                        'sourceData'=>$costInfoVo['oceData'],
                        'current'=>empty($costInfoVo['oceData']['supplier'])?['name'=>'']:$costInfoVo['oceData']['supplierData'],
                        'ietData'=>['name'=>'-'],
                        'money'=>$costInfoVo['money']
                    ];
                }
                //匹配子节点
                $info[$key]['node']=$node;
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
    //购销费用报表-导出
    public function formExports(){
        $input=input('get.');
        $sheet=['buy','bre','sell','sre','entry','extry'];
        existFull($input,['iet'])||$input['iet']=[];
        existFull($input,['state'])||$input['state']=[1,2];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(is_arrays($input,['iet','state','mold']) && arrayInArray($input['state'],[1,2]) && arrayInArray($input['mold'],$sheet)){
            pushLog('导出购销费用报表');//日志
            $sql=[];
            //查询语句
            $sql['cost']=fastSql($input,[
                [['mold'=>'type'],'fullIn'],
                ['iet','fullIn'],
                ['state','fullIn']
            ]);
            //基础语句
            $sql['base']=fastSql($input,[
                [['number'=>'number'],'fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);
            $sql['base'][]=['examine','=',1];
            $sql['base'][]=['id','=',Db::raw('cost.class')];
            $sql['base']=frameScope($sql['base']);
            //场景匹配
            foreach ($input['mold'] as $mold) {
                if(in_array($mold,['buy','bre','entry'])){
                    $sql[$mold]=array_merge($sql['base'],fastSql($input,[['supplier','fullEq']]));
                }else{
                    $sql[$mold]=array_merge($sql['base'],fastSql($input,[['customer','fullEq']]));
                }
                $sql[$mold]=sqlAuth($mold,$sql[$mold]);//数据鉴权
            }
            //构造查询
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->alias('class')->where([['cost.type','=',$mold]])->where($sql[$mold])->limit(1)->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $info=Costs::with(['sourceData'=>['frameData'],'ietData'])->alias('cost')->where($sql['cost'])->whereExists($union)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            //匹配往来单位
            $currentList=['customer'=>[],'supplier'=>[]];
            //匹配客戶
            foreach (search($info)->where([['type','in',['sell','sre','extry']]])->select() as $item) {
                $currentList['customer'][]=$item['sourceData']['customer'];
            }
            empty($currentList['customer'])||$currentList['customer']=Db::name('customer')->where([['id','in',array_unique($currentList['customer'])]])->select()->toArray();
            //匹配供应商
            foreach (search($info)->where([['type','in',['buy','bre','entry']]])->select() as $item) {
                $currentList['supplier'][]=$item['sourceData']['supplier'];
            }
            empty($currentList['supplier'])||$currentList['supplier']=Db::name('supplier')->where([['id','in',array_unique($currentList['supplier'])]])->select()->toArray();
            //查询子节点
            if(!empty($info)){
                $gather=CostInfo::with(['oceData'=>['frameData','supplierData']])->where([['pid','in',array_column($info,'id')]])->select()->toArray();
            }
            foreach ($info as $key=>$vo) {
                $info[$key]['key']=$vo['id'];
                //往来单位
                if(in_array($vo['type'],['buy','bre','entry'])){
                    $info[$key]['current']=search($currentList['supplier'])->where([['id','=',$vo['sourceData']['supplier']]])->find();
                }else if(in_array($vo['type'],['sell','sre','extry'])){
                    $info[$key]['current']=search($currentList['customer'])->where([['id','=',$vo['sourceData']['customer']]])->find();
                }else{
                    $info[$key]['current']=[];
                }
                $node=[];
                $costInfo=search($gather)->where([['pid','=',$vo['id']]])->select();
                foreach($costInfo as $costInfoVo){
                    $node[]=[
                        'key'=>$costInfoVo['pid'].'_'.$costInfoVo['id'],
                        'extension'=>['type'=>'其它支出单','state'=>'-'],
                        'sourceData'=>$costInfoVo['oceData'],
                        'current'=>empty($costInfoVo['oceData']['supplier'])?['name'=>'']:$costInfoVo['oceData']['supplierData'],
                        'ietData'=>['name'=>'-'],
                        'money'=>$costInfoVo['money']
                    ];
                }
                //匹配子节点
                $info[$key]['node']=$node;
            }
            //结构重组
		    $source=[];
		    foreach ($info as $infoVo) {
		        $source[]=$infoVo;
		        if(!empty($infoVo['node'])){
		            foreach ($infoVo['node'] as $nodeVo) {
		                $nodeVo['extension']['type']='|- '.$nodeVo['extension']['type'];
		                $source[]=$nodeVo;
		            }
		        }
		    }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'购销费用报表'];
            //表格数据 
            $field=[
                'extension|type'=>'单据类型',
                'sourceData|frameData|name'=>'所属组织',
                'current|name'=>'往来单位',
                'sourceData|time'=>'单据时间',
                'sourceData|number'=>'单据编号',
                'ietData|name'=>'支出类别',
                'extension|state'=>'结算状态',
                'money'=>'金额'
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
            $excel[]=['type'=>'node','info'=>[
                '总数:'.count($source)
            ]];
            //导出execl
            buildExcel('购销费用报表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
}
