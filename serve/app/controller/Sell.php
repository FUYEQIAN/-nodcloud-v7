<?php
namespace app\controller;
use app\controller\Acl;
use app\model\{Sell as Sells,SellInfo,Cost,Goods,Account};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Sell extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['number','fullLike'],
                ['customer','fullEq'],
                ['user','fullEq'],
                ['people','fullEq'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['examine','fullDec1'],
                ['nucleus','fullDec1'],
                ['cse','fullDec1'],
                ['invoice','fullDec1'],
                ['check','fullDec1'],
                ['data','fullLike']
            ]);//构造SQL
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['id','in',array_column(Db::name('sell_info')->where([['goods','in',$goods]])->select()->toArray(),'pid')];
            }
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('sell',$sql);//数据鉴权
            
            //排序
            $column=empty($input['column'])?'id':$input['column'];
            $order=empty($input['order'])?'desc':$input['order'];
            
            $count = Sells::where($sql)->count();//获取总条数
            $info = Sells::with(['frameData','customerData','peopleData','userData','billData','costData','invoiceData','recordData'])->where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order([$column=>$order])->select()->toArray();//查询分页数据
            //关联单据
            if(!empty($info)){
                $sor=Db::name('sor')->where([['id','in',array_column($info,'source')]])->select()->toArray();
                $sre=Db::name('sre')->where([['source','in',array_column($info,'id')]])->select()->toArray();
                foreach ($info as $infoKey=>$infoVo) {
                    //销售订单
                    $sorData=array_map(function($item){
                        return ['type'=>'销售订单','time'=>date('Y-m-d',$item['time']),'number'=>$item['number'],'sort'=>$item['time'],'sort'=>$item['time'],'types'=>'sor','id'=>$item['id']];
                    },search($sor)->where([['id','=',$infoVo['source']]])->select());
                    //销售退货单
                    $sreData=array_map(function($item){
                        return ['type'=>'销售退货单','time'=>date('Y-m-d',$item['time']),'number'=>$item['number'],'sort'=>$item['time'],'sort'=>$item['time'],'types'=>'sre','id'=>$item['id']];
                    },search($sre)->where([['source','=',$infoVo['id']]])->select());
                    //合并排序
                    $merge=array_merge($sorData,$sreData);
                    array_multisort(array_column($merge,'sort'),SORT_DESC,$merge);
                    $info[$infoKey]['relation']=$merge;
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
    //新增|更新
    public function save(){
        $input=input('post.');
        if(existFull($input,['class','info']) && isset($input['class']['id'])){
            //构造|验证CLASS
            try {
                $class=$input['class'];
                $class['frame']=userInfo(getUserID(),'frame');
                $class['user']=getUserID();
                $class['cse']=empty($class['cost'])?3:0;
                $class['invoice']=empty($class['actual'])?3:0;
                $class['examine']=0;
                $class['nucleus']=0;
                empty($class['id'])?$this->validate($class,'app\validate\Sell'):$this->validate($class,'app\validate\Sell.update');
                $period=getPeriod();
                if(strtotime($class['time'])<=$period){
                    throw new ValidateException('单据日期与结账日期冲突!');
                }
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //验证INFO
            foreach ($input['info'] as $infoKey=>$infoVo) {
                try {
                    $this->validate($infoVo,'app\validate\SellInfo');
                } catch (ValidateException $e) {
                    return json(['state'=>'error','info'=>'商品信息第'.($infoKey+1).'条'.$e->getError()]);
                    exit;
                }
            }
            
            //验证Cost
            foreach ($input['cost'] as $costKey=>$costVo) {
                try {
                    $this->validate($costVo,'app\validate\Cost');
                } catch (ValidateException $e) {
                    return json(['state'=>'error','info'=>'单据费用第'.($infoKey+1).'条'.$e->getError()]);
                    exit;
                }
            }
            
            //处理数据
            Db::startTrans();
            try {
                //CLASS数据
                if(empty($class['id'])){
                    //创建数据
                    $createInfo=Sells::create($class);
                    $class['id']=$createInfo['id'];//转存主键
                    Db::name('record')->insert(['type'=>'sell','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'新增单据']);
                    pushLog('新增销售单[ '.$class['number'].' ]');//日志
                }else{
                    //更新数据
                    $updateInfo=Sells::update($class);
                    Db::name('record')->insert(['type'=>'sell','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'更新单据']);
                    pushLog('更新销售单[ '.$class['number'].' ]');//日志
                }
                
                //INFO数据
                SellInfo::where([['pid','=',$class['id']]])->delete();
                foreach ($input['info'] as $infoKey=>$infoVo) {
                    $input['info'][$infoKey]['pid']=$class['id'];
                    $input['info'][$infoKey]['retreat']=0;//初始|退货数量
                }
                $model = new SellInfo;
                $model->saveAll($input['info']);
                
                //COST数据
                Cost::where([['type','=','sell'],['class','=',$class['id']]])->delete();
                foreach ($input['cost'] as $costKey=>$costVo) {
                    unset($input['cost'][$costKey]['id']);
                    $input['cost'][$costKey]['type']='sell';
                    $input['cost'][$costKey]['class']=$class['id'];
                    $input['cost'][$costKey]['time']=$class['time'];
                    $input['cost'][$costKey]['settle']=0;
                    $input['cost'][$costKey]['state']=0;
                }
                $model = new Cost;
                $model->saveAll($input['cost']);
                
            	Db::commit();
            	$result=['state'=>'success','info'=>$class['id']];
            } catch (\Exception $e) {
            	Db::rollback();
            	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //获取
    public function get(){
        $input=input('post.');
        if(existFull($input,['parm'])){
            $class=Sells::where([['id','=',$input['parm']]])->find();
            $info=SellInfo::with(['goodsData','warehouseData'])->where([['pid','=',$input['parm']]])->order(['id'=>'asc'])->select();
            $cost=Cost::where([['type','=','sell'],['class','=',$input['parm']]])->order(['id'=>'asc'])->select();
            $result=['state'=>'success','info'=>[
                'class'=>$class,
                'info'=>$info,
                'cost'=>$cost,
            ]];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            //关联验证
            $exist=moreTableFind([['table'=>'sre','where'=>[['source','in',$input['parm']]]]]);
            if($exist){
                $result=['state'=>'error','info'=>'存在数据关联,删除失败!'];
            }else{
                $data=Db::name('sell')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
                $search=search($data)->where([['examine','=','1']])->find();
                if(empty($search)){
                    Db::startTrans();
                    try {
                        Db::name('sell')->where([['id','in',$input['parm']]])->delete();
                        Db::name('sell_info')->where([['pid','in',$input['parm']]])->delete();
                        Db::name('cost')->where([['type','=','sell'],['class','in',$input['parm']]])->delete();
                        Db::name('record')->where([['type','=','sell'],['source','in',$input['parm']]])->delete();
                        Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除销售单[ '.implode(' | ',array_column($data,'number')).' ]']);
                        
                    	Db::commit();
                    	$result=['state'=>'success'];
                    } catch (\Exception $e) {
                    	Db::rollback();
                    	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
                    }
                }else{
                    $result=['state'=>'error','info'=>'单据['.$search['number'].']已审核,不可删除!'];
                }
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //核对|反核对
    public function check(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            $period=getPeriod();
            $classList=Db::name('sell')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            foreach ($input['parm'] as $parmVo) {
                $class=search($classList)->where([['id','=',$parmVo]])->find();
                if($class['time']<=$period){
                    return json(['state'=>'error','info'=>'操作单据失败,原因:单据日期与结账日期冲突!']);
                    exit;
                }
                if(empty($class['check'])){
                    Db::name('sell')->where([['id','=',$class['id']]])->update(['check'=>1]);
                    //1 单据记录
                    Db::name('record')->insert(['type'=>'sell','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'核对单据']);
                    //2 记录操作
                    pushLog('核对销售单[ '.$class['number'].' ]');//单据日志
                }else{
                    Db::name('sell')->where([['id','=',$class['id']]])->update(['check'=>0]);
                    //1 单据记录
                    Db::name('record')->insert(['type'=>'sell','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'反核对单据']);
                    //2 记录操作
                    pushLog('反核对销售单[ '.$class['number'].' ]');//单据日志
                }
            }
            $result=['state'=>'success'];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //审核|反审核
    public function examine(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            //1 基础数据
            $fun=getSys('fun');
            $period=getPeriod();
            $classList=Db::name('sell')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            $infoList=Db::name('sell_info')->where([['pid','in',$input['parm']]])->order(['id'=>'asc'])->select()->toArray();
            //2 综合处理
            foreach ($input['parm'] as $parmVo) {
                //1 匹配数据
                $class=search($classList)->where([['id','=',$parmVo]])->find();
                $info=search($infoList)->where([['pid','=',$parmVo]])->select();
                //1.1 商品数据
                $goodsList=Db::name('goods')->where([['id','in',array_unique(array_column($info,'goods'))]])->select()->toArray();
                //1.2 综合匹配
                if(empty($class['examine'])){
                    //1 关联单据
                    empty($class['source'])||$sorInfoList=Db::name('sor_info')->where([['id','in',array_column($info,'source')]])->select()->toArray();
                    //2 构造数据
                    $batchGather=[];
                    $serialGather=[];
                    $roomWhereOrSql=[];
                    foreach ($info as $infoVo) {
                        //1 批次号
                        empty($infoVo['batch'])||$batchGather[]=$infoVo['batch'];
                        //2 序列号
                        $serialGather=array_merge($serialGather,json_decode($infoVo['serial']));
                        //3 仓储条件
                        empty($infoVo['warehouse'])||$roomWhereOrSql[]=[['warehouse','=',$infoVo['warehouse']],['goods','=',$infoVo['goods']],['attr','=',$infoVo['attr']]];
                    }
                    //3 匹配数据
                    empty($batchGather)||$batchList=Db::name('batch')->where([['number','in',$batchGather]])->select()->toArray();
                    empty($serialGather)||$serialList=Db::name('serial')->where([['number','in',$serialGather],['state','<>',2]])->select()->toArray();
                    if(!empty($roomWhereOrSql)){
                        //1 去重转存
                        $roomWhereOrSql=array_unique($roomWhereOrSql,SORT_REGULAR);
                        //2 仓储匹配
                        $roomList=Db::name('room')->whereOr($roomWhereOrSql)->select()->toArray();
                    }
                }
                //2 CLASS验证
                if($class['time']<=$period){
                    return json(['state'=>'error','info'=>'操作单据[ '.$class['number'].' ]失败,原因:单据日期与结账日期冲突!']);
                    exit;
                }
                if(empty($class['examine'])){
                    //销售订单
                    if(!empty($class['source'])){
                        $sor=Db::name('sor')->where([['id','=',$class['source']]])->find();
                        if(in_array($sor['state'],[2,3])){
                            return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:关联订单状态不正确!']);
                            exit;
                        }
                    }
                }else{
                    //1 销售订单
                    if(!empty($class['source'])){
                        $sor=Db::name('sor')->where([['id','=',$class['source']]])->find();
                        if(in_array($sor['state'],[0,3])){
                            return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:关联订单状态不正确!']);
                            exit;
                        }
                    }
                    //2 销售退货单
                    $sre=Db::name('sre')->where([['source','=',$class['id']]])->find();
                    if(!empty($sre)){
                        return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:该单据存在关联销售退货单!']);
                        exit;
                    }
                    //3 核销单
                    $bill=Db::name('bill_info')->where([['mold','=','sell'],['source','=',$class['id']]])->find();
                    if(!empty($bill)){
                        return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:该单据存在关联核销单!']);
                        exit;
                    }
                    //4 单据费用
                    $cost=Db::name('cost')->alias('cost')->where([['type','=','sell'],['class','=',$class['id']]])->whereExists(function($query){
                        $query->name('oce_info')->where([['source','=',Db::raw('cost.id')]])->limit(1);
                    })->find();
                    if(!empty($cost)){
                        return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:该单据存在关联其它支出单!']);
                        exit;
                    }
                    //5 单据发票
                    $invoice=Db::name('invoice')->where([['type','=','sell'],['class','=',$class['id']]])->find();
                    if(!empty($invoice)){
                        return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:该单据存在关联单据发票!']);
                        exit;
                    }
                }
                //3 INFO验证|构造
                foreach ($info as $infoKey=>$infoVo) {
                    //1 匹配商品
                    $goods=search($goodsList)->where([['id','=',$infoVo['goods']]])->find();
                    //2 商品类型
                    if(empty($goods['type'])){
                        //场景验证
                        if(empty($class['examine'])){
                            //1 匹配数据
                            $room=search($roomList)->where([['warehouse','=',$infoVo['warehouse']],['goods','=',$infoVo['goods']],['attr','=',$infoVo['attr']]],true)->find();
                            (empty($room)||empty($infoVo['batch']))||$batch=search($batchList)->where([['room','=',$room['id']],['number','=',$infoVo['batch']],['time','=',$infoVo['mfd']]],true)->find();
                            //2 关联单据
                            if(!empty($infoVo['source'])){
                                $sorInfo=search($sorInfoList)->where([['id','=',$infoVo['source']]])->find();
                                if(!empty($sorInfo)){
                                    if($sorInfo['unit']!=$infoVo['unit']){
                                        //单位匹配
                                        return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行单位与销售订单不匹配!']);
                                        exit;
                                    }elseif(bccomp(math()->chain($sorInfo['handle'])->add($infoVo['nums'])->done(),$sorInfo['nums'])==1){
                                        //数量匹配
                                        return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行超出销售订单可出库数量!']);
                                        exit;
                                    }
                                }
                                
                            }
                            //3 多单位处理
                            if($goods['unit']==-1){
                                //多单位|转存
                                $radix=unitRadix($infoVo['unit'],json_decode($goods['units'],true));
                                $info[$infoKey]['basic']=[
                                    'nums'=>math()->chain($infoVo['nums'])->mul($radix)->done(),
                                    'price'=>math()->chain($infoVo['price'])->div($radix)->round(4)->done()
                                ];
                            }else{
                                //常规单位|转存
                                $info[$infoKey]['basic']=[
                                    'nums'=>$infoVo['nums'],
                                    'price'=>$infoVo['price']
                                ];
                            }
                            //4 序列号
                            $serialData=json_decode($infoVo['serial']);
                            if(empty($serialData)){
                                $info[$infoKey]['serial']=[];
                            }else{
                                //序列号状态[不存在|未销售]
                                $serialCollect=search($serialList)->where([['goods','=',$infoVo['goods']],['number','in',$serialData]])->select();
                                foreach ($serialCollect as $serialCollectVo) {
                                    if($serialCollectVo['state']==0){
                                        if(empty($room) || $room['id']!=$serialCollectVo['room']){
                                            return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行序列号[ '.$serialCollectVo['number'].' ]与仓库不匹配!']);
                                            exit;
                                        }
                                        if((empty($infoVo['batch'])&&!empty($serialCollectVo['batch']))||(!empty($infoVo['batch'])&&(empty($batch)||$batch['id']!=$serialCollectVo['batch']))){
                                            return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行序列号[ '.$serialCollectVo['number'].' ]与批次不匹配!']);
                                            exit;
                                        }
                                    }else{
                                        return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行序列号[ '.$serialCollectVo['number'].' ]状态不正确!']);
                                        exit;
                                    }
                                }
                                $info[$infoKey]['serial']=$serialData;
                            }
                            //5 负库存验证
                            if($fun['overflow']==false){
                                //1 仓储验证
                                if(empty($room)){
                                    return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行仓储信息不存在!']);
                                    exit;
                                }else{
                                    if(bccomp($info[$infoKey]['basic']['nums'],$room['nums'])==1){
                                        return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行仓储库存不足!']);
                                        exit;
                                    }else{
                                        $roomList[$room['rowKey']]['nums']=math()->chain($room['nums'])->sub($info[$infoKey]['basic']['nums'])->done();
                                    }
                                }
                                //2 批次验证
                                if(!empty($infoVo['batch'])){
                                    if(empty($batch)){
                                        return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行批次信息无效!']);
                                        exit;
                                    }else{
                                        if(bccomp($info[$infoKey]['basic']['nums'],$batch['nums'])==1){
                                            return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行批次库存不足!']);
                                            exit;
                                        }else{
                                            $batchList[$batch['rowKey']]['nums']=math()->chain($batch['nums'])->sub($info[$infoKey]['basic']['nums'])->done();
                                        }
                                    }
                                }
                                //3 序列号验证
                                if(!empty($serialData)){
                                    $serialCount=search($serialList)->where([['room','=',$room['id']],['number','in',$serialData]])->count();
                                    if($serialCount != count($serialData)){
                                        return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行存在无效序列号!']);
                                        exit;
                                    }
                                }
                            }
                        }else{
                            //1 验证序列号
                            $serialInfoCollect=Db::name('serial_info')->where([['type','=','sell'],['info','in',array_column($info,'id')]])->select()->toArray();
                            if(!empty($serialInfoCollect)){
                                //序列号状态[已销售]
                                $serialFind=Db::name('serial')->where([['id','in',array_column($serialInfoCollect,'pid')],['state','<>',1]])->find();
                                if(!empty($serialFind)){
                                    return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行序列号[ '.$serialFind['number'].' ]状态不正确!']);
                                    exit;
                                }
                            }
                        }
                    }
                }
                //4 数据处理
                Db::startTrans();
                try {
                    //场景验证
                    if(empty($class['examine'])){
                        //审核
                        //1 构造数据
                        $store=['sorInfo'=>[],'room'=>[],'roomInfo'=>[],'batch'=>[],'batchInfo'=>[],'serial'=>[],'serialInfo'=>[],'serve'=>[],'serveInfo'=>[]];
                        foreach ($info as $infoKey=>$infoVo){
                            //1 关联单据
                            empty($infoVo['source'])||$store['sorInfo'][]=['id'=>$infoVo['source'],'handle'=>$infoVo['nums']];
                            //2 判断商品类型
                            $goods=search($goodsList)->where([['id','=',$infoVo['goods']]])->find();
                            if(empty($goods['type'])){
                                //常规商品
                                //1 仓储
                                $store['room'][]=['warehouse'=>$infoVo['warehouse'],'goods'=>$infoVo['goods'],'attr'=>$infoVo['attr'],'nums'=>$infoVo['basic']['nums']];
                                //2 仓储详情
                                $store['roomInfo'][]=['pid'=>null,'type'=>'sell','class'=>$class['id'],'info'=>$infoVo['id'],'time'=>$class['time'],'direction'=>0,'price'=>$infoVo['basic']['price'],'nums'=>$infoVo['basic']['nums']];
                                //3 批次号
                                if(empty($infoVo['batch'])){
                                    $store['batch'][]=[];
                                    $store['batchInfo'][]=[];
                                }else{
                                    $store['batch'][]=['room'=>null,'warehouse'=>$infoVo['warehouse'],'goods'=>$infoVo['goods'],'number'=>$infoVo['batch'],'time'=>$infoVo['mfd'],'nums'=>$infoVo['basic']['nums']];
                                    $store['batchInfo'][]=['pid'=>null,'type'=>'sell','class'=>$class['id'],'info'=>$infoVo['id'],'direction'=>0,'nums'=>$infoVo['basic']['nums']];
                                }
                                //4 序列号
                                if(empty($infoVo['serial'])){
                                    $store['serial'][]=[];
                                    $store['serialInfo'][]=[];
                                }else{
                                    $serial=[];
                                    $serialInfo=[];
                                    foreach ($infoVo['serial'] as $serialVo) {
                                        $serial[]=['room'=>null,'warehouse'=>$infoVo['warehouse'],'batch'=>null,'goods'=>$infoVo['goods'],'number'=>$serialVo,'state'=>1];
                                        $serialInfo[]=['pid'=>null,'type'=>'sell','class'=>$class['id'],'info'=>$infoVo['id']];
                                    }
                                    $store['serial'][]=$serial;
                                    $store['serialInfo'][]=$serialInfo;
                                }
                            }else{
                                //5 服务
                                $store['serve'][]=['goods'=>$infoVo['goods'],'attr'=>$infoVo['attr'],'nums'=>$infoVo['nums']];
                                $store['serveInfo'][]=['pid'=>null,'type'=>'sell','class'=>$class['id'],'info'=>$infoVo['id'],'time'=>$class['time'],'price'=>$infoVo['price'],'nums'=>$infoVo['nums']];
                            }
                        }
                        //2 关联单据
                        if(!empty($store['sorInfo'])){
                            //1 更新详情
                            Db::name('sor_info')->duplicate(['handle'=>Db::raw('handle + VALUES(`handle`)')])->insertAll($store['sorInfo']);
                            //2 更新CLASS
                            $sorInfo=Db::name('sor_info')->where([['pid','=',$class['source']]])->select()->toArray();
                            $state=mathArraySum(array_column($sorInfo,'nums'))==mathArraySum(array_column($sorInfo,'handle'))?2:1;
                            Db::name('sor')->where([['id','=',$class['source']]])->update(['state'=>$state]);
                        }
                        //3 仓储
                        if(!empty($store['room'])){
                            //1 构造数据
                            $roomInsert=[];
                            foreach ($store['room'] as $roomVo) {
                                $roomFind=search($roomList)->where([['warehouse','=',$roomVo['warehouse']],['goods','=',$roomVo['goods']],['attr','=',$roomVo['attr']]])->find();
                                if(empty($roomFind)){
                                    $roomVo['nums']=0;
                                    $roomInsert[]=$roomVo;
                                }
                            }
                            //2 创建数据|去重
                            empty($roomInsert)||Db::name('room')->insertAll(array_unique($roomInsert,SORT_REGULAR));
                            //3 匹配主键|构造更新
                            $roomDuplicate=[];
                            $room=Db::name('room')->whereOr($roomWhereOrSql)->select()->toArray();
                            foreach ($store['room'] as $roomKey=>$roomVo) {
                                $roomFind=search($room)->where([['warehouse','=',$roomVo['warehouse']],['goods','=',$roomVo['goods']],['attr','=',$roomVo['attr']]])->find();
                                $store['room'][$roomKey]['id']=$roomFind['id'];
                                $roomDuplicate[]=['id'=>$roomFind['id'],'nums'=>$roomVo['nums']];
                            }
                            //4 更新数据
                            Db::name('room')->duplicate(['nums'=>Db::raw('nums - VALUES(`nums`)')])->insertAll($roomDuplicate);
                        }
                        //4 仓储详情
                        if(!empty($store['roomInfo'])){
                            //1 填充数据
                            foreach ($store['roomInfo'] as $roomInfoKey=>$roomInfoVo) {
                                $store['roomInfo'][$roomInfoKey]['pid']=$store['room'][$roomInfoKey]['id'];
                            }
                            //2 创建数据
                            Db::name('room_info')->insertAll($store['roomInfo']);
                        }
                        //5 批次号
                        if(!empty($store['batch'])){
                            //1 构造数据
                            $batchData=[];
                            foreach ($store['batch'] as $batchKey=>$batchVo) {
                                if(!empty($batchVo)){
                                    $store['batch'][$batchKey]['room']=$store['room'][$batchKey]['id'];
                                    $batchData[]=$store['batch'][$batchKey];
                                }
                            }
                            //2 排除数据|[[],[],[]]
                            if(!empty($batchData)){
                                //1 构造数据
                                $batchInsert=[];
                                foreach ($batchData as $batchDataKey=>$batchDataVo) {
                                    $batchFind=search($batchList)->where([['room','=',$batchDataVo['room']],['number','=',$batchDataVo['number']],['time','=',$batchDataVo['time']]])->find();
                                    if(empty($batchFind)){
                                        $batchDataVo['nums']=0;
                                        $batchInsert[]=$batchDataVo;
                                    }
                                }
                                //2 创建数据|去重
                                empty($batchInsert)||Db::name('batch')->insertAll(array_unique($batchInsert,SORT_REGULAR));
                                //3 匹配主键|构造更新
                                $batchDuplicate=[];
                                $batch=Db::name('batch')->where([['number','in',$batchGather]])->select()->toArray();
                                foreach ($store['batch'] as $batchKey=>$batchVo) {
                                    if(!empty($batchVo)){
                                        $batchFind=search($batch)->where([['room','=',$batchVo['room']],['number','=',$batchVo['number']],['time','=',$batchVo['time']]])->find();
                                        $store['batch'][$batchKey]['id']=$batchFind['id'];
                                        $batchDuplicate[]=['id'=>$batchFind['id'],'nums'=>$batchVo['nums']];
                                    }
                                }
                                //4 更新数据
                                Db::name('batch')->duplicate(['nums'=>Db::raw('nums - VALUES(`nums`)')])->insertAll($batchDuplicate);
                            }
                        }
                        //6 批次号详情
                        if(!empty($store['batchInfo'])){
                            //1 构造数据
                            $batchInfoInstall=[];
                            foreach ($store['batchInfo'] as $batchInfoKey=>$batchInfoVo) {
                                if(!empty($batchInfoVo)){
                                    $batchInfoVo['pid']=$store['batch'][$batchInfoKey]['id'];
                                    $batchInfoInstall[]=$batchInfoVo;
                                }
                            }
                            //2 排除数据|[[],[],[]]
                            if(!empty($batchInfoInstall)){
                                //创建数据
                                Db::name('batch_info')->insertAll($batchInfoInstall);
                            }
                        }
                        //7 序列号
                        if(!empty($store['serial'])){
                            //1 构造数据
                            $serialData=[];
                            foreach ($store['serial'] as $serialKey=>$item) {
                                if(!empty($item)){
                                    foreach ($item as $itemKey=>$itemVo) {
                                        $store['serial'][$serialKey][$itemKey]['room']=$store['room'][$serialKey]['id'];
                                        $store['serial'][$serialKey][$itemKey]['batch']=empty($store['batch'][$serialKey])?0:$store['batch'][$serialKey]['id'];
                                        $serialData[]=$store['serial'][$serialKey][$itemKey];
                                    }
                                }
                            }
                            //2 排除数据|[[],[],[]]
                            if(!empty($serialData)){
                                //1 构造数据
                                $serialInsert=[];
                                foreach ($serialData as $serialDataKey=>$serialDataVo) {
                                    $serialFind=search($serialList)->where([['room','=',$serialDataVo['room']],['batch','=',$serialDataVo['batch']],['number','=',$serialDataVo['number']]])->find();
                                    if(empty($serialFind)){
                                        $serialInsert[]=$serialDataVo;
                                    }
                                }
                                //2 创建数据
                                empty($serialInsert)||Db::name('serial')->insertAll($serialInsert);
                                //3 匹配主键|构造更新
                                $serialDuplicate=[];
                                $serial=Db::name('serial')->where([['number','in',$serialGather]])->select()->toArray();
                                foreach ($store['serial'] as $serialKey=>$item) {
                                    if(!empty($item)){
                                        foreach ($item as $itemKey=>$itemVo) {
                                            $serialFind=search($serial)->where([['room','=',$itemVo['room']],['batch','=',$itemVo['batch']],['number','=',$itemVo['number']]])->find();
                                            $store['serial'][$serialKey][$itemKey]['id']=$serialFind['id'];
                                            $serialFind['state']==0&&$serialDuplicate[]=$serialFind['id'];
                                        }
                                    }
                                }
                                //4 更新数据|状态变更
                                empty($serialDuplicate)||Db::name('serial')->where([['id','in',$serialDuplicate]])->update(['state'=>1]);
                            }
                        }
                        //8 序列号详情
                        if(!empty($store['serialInfo'])){
                            //1 构造数据
                            $serialInfoInstall=[];
                            foreach ($store['serialInfo'] as $serialInfoKey=>$item) {
                                if(!empty($item)){
                                    foreach ($item as $itemKey=>$itemVo) {
                                        $itemVo['pid']=$store['serial'][$serialInfoKey][$itemKey]['id'];
                                        $serialInfoInstall[]=$itemVo;
                                    }
                                }
                            }
                            //2 排除数据|[[],[],[]]
                            if(!empty($serialInfoInstall)){
                                //创建数据
                                Db::name('serial_info')->insertAll($serialInfoInstall);
                            }
                        }
                        //9 服务商品
                        if(!empty($store['serve'])){
                            //1 匹配数据|去重
                            $serveWhereOrSql=array_unique(array_map(function($item){
                                return [['goods','=',$item['goods']],['attr','=',$item['attr']]];
                            },$store['serve']),SORT_REGULAR);
                            $serve=Db::name('serve')->whereOr($serveWhereOrSql)->select()->toArray();
                            //2 构造数据
                            $serveInsert=[];
                            foreach ($store['serve'] as $serveVo) {
                                $serveFind=search($serve)->where([['goods','=',$serveVo['goods']],['attr','=',$serveVo['attr']]])->find();
                                if(empty($serveFind)){
                                    $serveVo['nums']=0;
                                    $serveInsert[]=$serveVo;
                                }
                            }
                            //3 创建数据|去重
                            empty($serveInsert)||Db::name('serve')->insertAll(array_unique($serveInsert,SORT_REGULAR));
                            //4 匹配主键|构造更新
                            $serveDuplicate=[];
                            $serve=Db::name('serve')->whereOr($serveWhereOrSql)->select()->toArray();
                            foreach ($store['serve'] as $serveKey=>$serveVo) {
                                $serveFind=search($serve)->where([['goods','=',$serveVo['goods']],['attr','=',$serveVo['attr']]])->find();
                                $store['serve'][$serveKey]['id']=$serveFind['id'];
                                $serveDuplicate[]=['id'=>$serveFind['id'],'nums'=>$serveVo['nums']];
                            }
                            //5 更新数据
                            Db::name('serve')->duplicate(['nums'=>Db::raw('nums + VALUES(`nums`)')])->insertAll($serveDuplicate);
                        }
                        //10 服务商品详情
                        if(!empty($store['serveInfo'])){
                            //1 填充数据
                            foreach ($store['serveInfo'] as $serveInfoKey=>$serveInfoVo) {
                                $store['serveInfo'][$serveInfoKey]['pid']=$store['serve'][$serveInfoKey]['id'];
                            }
                            //2 创建数据
                            Db::name('serve_info')->insertAll($store['serveInfo']);
                        }
                        //11 资金|核销
                        if(!empty($class['money'])){
                            //1 更新资金账户
                            Db::name('account')->where([['id','=',$class['account']]])->inc('balance',$class['money'])->update();
                            //2 创建资金详情
                            Db::name('account_info')->insert([
                                'pid'=>$class['account'],
                                'type'=>'sell',
                                'class'=>$class['id'],
                                'time'=>$class['time'],
                                'direction'=>1,
                                'money'=>$class['money']
                            ]);
                            //3 创建核销记录
                            Db::name('sell_bill')->insert([
                                'pid'=>$class['id'],
                                'type'=>'sell',
                                'source'=>$class['id'],
                                'time'=>$class['time'],
                                'money'=>$class['money']
                            ]);
                        }
                        //12 客户|应收款余额
                        $balance=math()->chain($class['actual'])->sub($class['money'])->done();
                        if(!empty($balance)){
                            Db::name('customer')->where([['id','=',$class['customer']]])->inc('balance',$balance)->update();
                        }
                        //13 更新单据
                        $nucleus=$class['money']==$class['actual']?2:($class['money']==0?0:1);
                        Db::name('sell')->where([['id','=',$class['id']]])->update(['examine'=>1,'nucleus'=>$nucleus]);
                        //14 单据记录
                        Db::name('record')->insert(['type'=>'sell','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'审核单据']);
                        //15 收发记录
                        $summary=new Summary;
                        $summary->note('sell',$class['id'],true);
                        //16 记录操作
                        pushLog('审核销售单[ '.$class['number'].' ]');//单据日志
                    }else{
                        //反审核
                        //1 匹配数据
                        $listSql=[['type','=','sell'],['info','in',array_column($info,'id')]];
                        $roomInfoList=Db::name('room_info')->where($listSql)->select()->toArray();
                        $batchInfoList=Db::name('batch_info')->where($listSql)->select()->toArray();
                        $serialInfoList=Db::name('serial_info')->where($listSql)->select()->toArray();
                        $serveInfoList=Db::name('serve_info')->where($listSql)->select()->toArray();
                        //2 关联单据
                        if(!empty($class['source'])){
                            //1 更新详情
                            $sorInfoDuplicate=[];
                            foreach ($info as $infoVo) {
                                empty($infoVo['source'])||$sorInfoDuplicate[]=['id'=>$infoVo['source'],'handle'=>$infoVo['nums']];
                            }
                            empty($sorInfoDuplicate)||Db::name('sor_info')->duplicate(['handle'=>Db::raw('handle - VALUES(`handle`)')])->insertAll($sorInfoDuplicate);
                            //2 更新CALSS
                            $sorInfo=Db::name('sor_info')->where([['pid','=',$class['source']]])->select()->toArray();
                            $state=empty(mathArraySum(array_column($sorInfo,'handle')))?0:1;
                            Db::name('sor')->where([['id','=',$class['source']]])->update(['state'=>$state]);
                        }
                        //3 仓储
                        $roomDuplicate=[];
                        foreach ($roomInfoList as $roomInfoVo) {
                            $roomDuplicate[]=['id'=>$roomInfoVo['pid'],'nums'=>$roomInfoVo['nums']];
                        }
                        //3.1 更新仓储
                        Db::name('room')->duplicate(['nums'=>Db::raw('nums + VALUES(`nums`)')])->insertAll($roomDuplicate);
                        //3.2 删除仓储详情
                        Db::name('room_info')->where([['id','in',array_column($roomInfoList,'id')]])->delete();
                        //3.3 仓储|冗余
                        $roomPk=array_unique(array_column($roomInfoList,'pid'));
                        $roomInfoData=Db::name('room_info')->where([['pid','in',$roomPk]])->select()->toArray();
                        $roomDiff=array_diff($roomPk,array_unique(array_column($roomInfoData,'pid')));
                        empty($roomDiff)||Db::name('room')->where([['id','in',$roomDiff]])->delete();
                        //4 批次号
                        if(!empty($batchInfoList)){
                            //1 构造数据
                            $batchInfoDuplicate=array_map(function($item){
                                return ['id'=>$item['pid'],'nums'=>$item['nums']];
                            },$batchInfoList);
                            //2 更新批次号
                            Db::name('batch')->duplicate(['nums'=>Db::raw('nums + VALUES(`nums`)')])->insertAll($batchInfoDuplicate);
                            //3 删除批次号详情
                            Db::name('batch_info')->where([['id','in',array_column($batchInfoList,'id')]])->delete();
                            //4 批次号|冗余
                            $batchPk=array_unique(array_column($batchInfoList,'pid'));
                            $batchInfoData=Db::name('batch_info')->where([['pid','in',$batchPk]])->select()->toArray();
                            $batchDiff=array_diff($batchPk,array_unique(array_column($batchInfoData,'pid')));
                            empty($batchDiff)||Db::name('batch')->where([['id','in',$batchDiff]])->delete();
                        }
                        //5 序列号
                        if(!empty($serialInfoList)){
                            //1 更新序列号
                            Db::name('serial')->where([['id','in',array_column($serialInfoList,'pid')]])->update(['state'=>0]);
                            //2 删除序列号详情
                            Db::name('serial_info')->where([['id','in',array_column($serialInfoList,'id')]])->delete();
                            //3 序列号|冗余
                            $serialPk=array_unique(array_column($serialInfoList,'pid'));
                            $serialInfoData=Db::name('serial_info')->where([['pid','in',$serialPk]])->select()->toArray();
                            $serialDiff=array_diff($serialPk,array_unique(array_column($serialInfoData,'pid')));
                            empty($serialDiff)||Db::name('serial')->where([['id','in',$serialDiff]])->delete();
                        }
                        //6 服务
                        if(!empty($serveInfoList)){
                            //1 构造数据
                            $serveInfoDuplicate=array_map(function($item){
                                return ['id'=>$item['pid'],'nums'=>$item['nums']];
                            },$serveInfoList);
                            //2 更新服务
                            Db::name('serve')->duplicate(['nums'=>Db::raw('nums - VALUES(`nums`)')])->insertAll($serveInfoDuplicate);
                            //3 删除服务详情
                            Db::name('serve_info')->where([['id','in',array_column($serveInfoList,'id')]])->delete();
                            //4 服务|冗余
                            $servePk=array_unique(array_column($serveInfoList,'pid'));
                            $serveInfoData=Db::name('serve_info')->where([['pid','in',$servePk]])->select()->toArray();
                            $serveDiff=array_diff($servePk,array_unique(array_column($serveInfoData,'pid')));
                            empty($serveDiff)||Db::name('serve')->where([['id','in',$serveDiff]])->delete();
                        }
                        //7 资金|核销
                        if(!empty($class['money'])){
                            //1 更新资金账户
                            Db::name('account')->where([['id','=',$class['account']]])->dec('balance',$class['money'])->update();
                            //2 删除资金详情
                            Db::name('account_info')->where([['type','=','sell'],['class','=',$class['id']]])->delete();
                            //3 删除核销记录
                            Db::name('sell_bill')->where([['pid','=',$class['id']]])->delete();
                        }
                        //8 客户|应收款余额
                        $balance=math()->chain($class['actual'])->sub($class['money'])->done();
                        if(!empty($balance)){
                            Db::name('customer')->where([['id','=',$class['customer']]])->dec('balance',$balance)->update();
                        }
                        //9 更新单据
                        Db::name('sell')->where([['id','=',$class['id']]])->update(['examine'=>0,'nucleus'=>0]);
                        //10 单据记录
                        Db::name('record')->insert(['type'=>'sell','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'反审核单据']);
                        //11 收发记录
                        $summary=new Summary;
                        $summary->note('sell',$class['id'],false);
                        //12 记录操作
                        pushLog('反审核销售单[ '.$class['number'].' ]');//单据日志
                    }
                    
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return json(['state'=>'error','info'=>'内部错误,操作已撤销!']);
                    exit;
                }
            }
            $result=['state'=>'success'];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //上传
    public function upload(){
		$file = request()->file('file');
        //获取上传文件
        if (empty($file)) {
            $result = ['state' => 'error','info' => '传入数据不完整!'];
        } else {
            //文件限制5MB
            try{
                validate(['file'=>['fileSize'=>5*1024*1024,'fileExt'=>'png,gif,jpg,jpeg,txt,doc,docx,rtf,xls,xlsx,ppt,pptx,pdf,zip,rar']])->check(['file'=>$file]);
                $fileInfo=Filesystem::disk('upload')->putFile('sell', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                $result = ['state' => 'error','info' => $e->getMessage()];
            }
        }
        return json($result);
    }
    //生成销售退货单
    public function buildSre(){
        $input=input('post.');
        if(existFull($input,['id'])){
            //源数据
            $source=[
                'class'=>Sells::where([['id','=',$input['id']]])->find(),
                'info'=>SellInfo::with(['goodsData','warehouseData'])->where([['pid','=',$input['id']]])->order(['id'=>'asc'])->select()->toArray()
            ];
            //状态验证
            if(mathArraySum(array_column($source['info'],'nums'))==mathArraySum(array_column($source['info'],'retreat'))){
                $result=['state'=>'warning','info'=>'操作失败,无可生成的数据!'];
            }else{
                //CLASS数据
                $class=[
                    'source'=>$source['class']['id'],
                    'customer'=>$source['class']['customer'],
                    'total'=>0
                ];
                //INFO数据
                $info=[];
                $fun=getSys('fun');
                foreach ($source['info'] as $infoVo) {
                    //判断入库状态
                    if(bccomp($infoVo['nums'],$infoVo['retreat'])==1){
                        $infoVo['source']=$infoVo['id'];
                        $infoVo['serial']=[];
                        //重算价格
                        $infoVo['nums']=math()->chain($infoVo['nums'])->sub($infoVo['retreat'])->done();
                        $storage=math()->chain($infoVo['price'])->mul($infoVo['nums'])->round($fun['digit']['money'])->done();
                        //折扣额|金额
                        if($infoVo['discount']==0){
                            $infoVo['total']=$storage;
                        }else{
                            $infoVo['dsc']=math()->chain($storage)->div(100)->mul($infoVo['discount'])->round($fun['digit']['money'])->done();
                            $infoVo['total']=math()->chain($storage)->sub($infoVo['dsc'])->done();
                        }
                        //税额|价税合计
                        if($infoVo['tax']==0){
                            $infoVo['tpt']=$infoVo['total'];
                        }else{
                            $infoVo['tat']=math()->chain($infoVo['total'])->div(100)->mul($infoVo['tax'])->round(2)->done();
                            $infoVo['tpt']=math()->chain($infoVo['total'])->add($infoVo['tat'])->done();
                        }
                        //转存数据
                        $info[]=$infoVo;
                        $class['total']=math()->chain($class['total'])->add($infoVo['tpt'])->done();//累加单据金额
                    }
                }
                $result=['state'=>'success','info'=>['class'=>$class,'info'=>$info]];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //导入
    public function import(){
		delOverdueFile('static.upload.xlsx');//删除过期文件
		$file=request()->file('file');//获取上传文件
		if(empty($file)){
		    $result=['state'=>'error','info'=>'传入数据不完整!'];
		}else{
		    $fun=getSys('fun');
            try{
                validate(['file'=>['fileSize'=>2*1024*1024,'fileExt'=>'xlsx']])->check(['file'=>$file]);
                $fileInfo = Filesystem::disk('upload')->putFile('xlsx', $file, 'uniqid');
                $filePath = pathChange('static.upload').$fileInfo;
                $data=getXlsx($filePath);
				unset($data[1]);//删除标题行
				unset($data[2]);//删除列名行
                //初始化CLASS
                //客户匹配
                $customer=Db::name('customer')->where([['name','=',$data[3]['A']]])->find();
                if(empty($customer)){
                    throw new ValidateException('客户[ '.$data[3]['A'].' ]未匹配!');
                }
                //结算账户匹配
                if(empty($data[3]['F'])){
                    $account=['id'=>0];
                }else{
                    $account=Db::name('account')->where([['name','=',$data[3]['G']]])->find();
                    if(empty($account)){
                        throw new ValidateException('结算账户[ '.$data[3]['G'].' ]不正确!');
                    }
                }
                //关联人员匹配
                if(empty($data[3]['H'])){
                    $people=['id'=>0];
                }else{
                    $people=Db::name('people')->where([['name','=',$data[3]['H']]])->find();
                    if(empty($people)){
                        throw new ValidateException('关联人员[ '.$data[3]['H'].' ]未匹配!');
                    }
                }
                $class=[
                    'source'=>0,
                    'frame'=>userInfo(getUserID(),'frame'),
                    'customer'=>$customer['id'],
                    'time'=>$data[3]['B'],
                    'number'=>$data[3]['C'],
                    'total'=>0,
                    'actual'=>$data[3]['E'],
                    'money'=>$data[3]['F'],
                    'account'=>$account['id'],
                    'people'=>$people['id'],
                    'logistics'=>["key"=>"auto","name"=>"自动识别","number"=>$data[3]['I']],
                    'file'=>[],
                    'data'=>$data[3]['J'],
                    'more'=>[],
                    'examine'=>0,
                    'nucleus'=>0,
                    'cse'=>0,
                    'invoice'=>0,
                    'check'=>0,
                    'user'=>getUserID()
                ];
                $this->validate($class,'app\validate\Sell');//数据合法性验证
                //初始化INFO
                $info=[];
                $goods=Goods::with(['attr'])->where([['name','in',array_column($data,'K')]])->select()->toArray();
                $warehouse=Db::name('warehouse')->where([['name','in',array_column($data,'N')]])->select()->toArray();
                foreach ($data as $dataKey=>$dataVo) {
					$record=[
					    'source'=>0,
                        'goods'=>$dataVo['K'],
                        'attr'=>$dataVo['L'],
                        'unit'=>$dataVo['M'],
                        'warehouse'=>$dataVo['N'],
                        'batch'=>$dataVo['O'],
                        'mfd'=>$dataVo['P'],
                        'price'=>$dataVo['Q'],
                        'nums'=>$dataVo['R'],
                        'serial'=>explode(',',$dataVo['S']),
                        'discount'=>$dataVo['T'],
                        'dsc'=>0,
                        'total'=>0,
                        'tax'=>$dataVo['W'],
                        'tat'=>0,
                        'tpt'=>0,
                        'data'=>$dataVo['Z'],
                        'retreat'=>0,
					];
					//商品匹配
					$goodsFind=search($goods)->where([['name','=',$record['goods']]])->find();
					if(empty($goodsFind)){
					    throw new ValidateException('模板文件第'.$dataKey.'行商品名称[ '.$record['goods'].' ]未匹配!');
					}else{
					    $record['goods']=$goodsFind['id'];
					}
					//辅助属性匹配
					if(empty($goodsFind['attr'])){
					    $record['attr']='';
					}else{
					    if(empty($record['attr'])){
                            throw new ValidateException('模板文件第'.$dataKey.'行辅助属性不可为空!');
					    }else{
					        $attrFind=search($goodsFind['attr'])->where([['name','=',$record['attr']]])->find();
                            if(empty($attrFind)){
                                throw new ValidateException('模板文件第'.$dataKey.'行辅助属性[ '.$record['attr'].' ]未匹配!');
                            }
                        }
                    }
					//单位匹配
					if($goodsFind['unit']==-1){
					    if(empty($record['unit'])){
                            throw new ValidateException('模板文件第'.$dataKey.'行单位不可为空!');
					    }else{
					        $unitFind=search($goodsFind['units'])->where([['name','=',$record['unit']]])->find();
                            if(empty($unitFind) && $goodsFind['units'][0]['source']!=$record['unit']){
                                throw new ValidateException('模板文件第'.$dataKey.'行单位[ '.$record['unit'].' ]未匹配!');
                            }
                        }
					}else{
					    $record['unit']=$goodsFind['unit'];
					}
					//仓库匹配
					if(empty($goodsFind['type'])){
					    //常规产品
					    $warehouseFind=search($warehouse)->where([['name','=',$record['warehouse']]])->find();
                        if(empty($warehouseFind)){
                            throw new ValidateException('模板文件第'.$dataKey.'行仓库[ '.$record['warehouse'].' ]未匹配!');
                        }else{
                            $record['warehouse']=$warehouseFind['id'];
                        }
					}else{
					    //服务产品
					    $record['warehouse']=null;
					}
					//批次号匹配
					if(empty($goodsFind['batch'])){
					    $record['batch']='';
					}else{
					    if(empty($record['batch'])){
                            throw new ValidateException('模板文件第'.$dataKey.'行批次号不可为空!');
					    }
                    }
                    //生产日期匹配
					if(empty($goodsFind['validity'])){
					    $record['mfd']='';
					}else{
					    if(empty($record['mfd'])){
                            throw new ValidateException('模板文件第'.$dataKey.'行生产日期不可为空!');
					    }
                    }
					//单价匹配
					if(!preg_match("/^\d+(\.\d{0,".$fun['digit']['money']."})?$/",$record['price'])){
					    throw new ValidateException('模板文件第'.$dataKey.'行单价不正确!');
					}
					//数量匹配
					if(!preg_match("/^\d+(\.\d{0,".$fun['digit']['nums']."})?$/",$record['nums'])){
					    throw new ValidateException('模板文件第'.$dataKey.'行数量不正确!');
					}
					//序列号匹配
					if(empty($goodsFind['serial'])){
					    $record['serial']=[];
					}else{
					    if(count($record['serial'])==1 && empty($record['serial'][0])){
                            throw new ValidateException('模板文件第'.$dataKey.'行序列号不可为空!');
                        }else{
                            if(count($record['serial'])!=$record['nums']){
                                throw new ValidateException('模板文件第'.$dataKey.'行序列号个数与数量不符!');
                            }
                        }
                    }
					try{
                        $this->validate($record,'app\validate\SellInfo');//数据合法性验证
                        $storage=math()->chain($record['price'])->mul($record['nums'])->round($fun['digit']['money'])->done();
                        //折扣额|金额
                        if($record['discount']==0){
                            $record['total']=$storage;
                        }else{
                            $record['dsc']=math()->chain($storage)->div(100)->mul($record['discount'])->round($fun['digit']['money'])->done();
                            $record['total']=math()->chain($storage)->sub($record['dsc'])->done();
                        }
                        //税额|价税合计
                        if($record['tax']==0){
                            $record['tpt']=$record['total'];
                        }else{
                            $record['tat']=math()->chain($record['total'])->div(100)->mul($record['tax'])->round(2)->done();
                            $record['tpt']=math()->chain($record['total'])->add($record['tat'])->done();
                        }
                        //转存数据
                        $class['total']=math()->chain($class['total'])->add($record['tpt'])->done();//累加单据金额
                        $info[]=$record;
					} catch (ValidateException $e) {
                        return json(['state'=>'error','info'=>'模板文件第'.$dataKey.'行'.$e->getMessage()]);//返回错误信息
                        exit;
                    }
                }
                //序列号重复验证
                $serials=[];
                foreach ($info as $infoVo) {
                    $serials = array_merge($serials,$infoVo['serial']);
                }
                if(count($serials)!=count(array_unique($serials))){
                    throw new ValidateException('商品信息中存在重复序列号!');
                }
                //CLASS数据验证
                if(bccomp($class['total'],$class['actual'])==-1){
                    throw new ValidateException('实际金额不可大于单据金额[ '.$class['total'].' ]!');
                }else if(bccomp($class['actual'],$class['money'])==-1){
                    throw new ValidateException('实收金额不可大于实际金额[ '.floatval($class['actual']).' ]!');
                }else{
                    Db::startTrans();
                    try {
                        //新增CLASS
                        $classData=Sells::create($class);
                        //新增INFO
                        foreach ($info as $infoKey=>$infoVo) {
                            $info[$infoKey]['pid']=$classData['id'];
                        }
                        $model = new SellInfo;
                        $model->saveAll($info);
                        Db::name('record')->insert(['type'=>'sell','source'=>$classData['id'],'time'=>time(),'user'=>getUserID(),'info'=>'导入单据']);
                        pushLog('导入销售单[ '.$classData['number'].' ]');//日志
                        
                        Db::commit();
                        $result=['state'=>'success'];
                    } catch (\Exception $e) {
                    	Db::rollback();
                    	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
                    }
                }
            }catch(ValidateException $e) {
                $result=['state'=>'error','info'=>$e->getMessage()];//返回错误信息
            }
		}
		return json($result);
    }
    //导出
	public function exports(){
		$input=input('get.');
		if(existFull($input,['scene','parm']) && is_array($input['parm'])){
		    pushLog('导出销售单列表');//日志
            $source=Sells::with(['frameData','customerData','accountData','peopleData','userData'])->where([['id','in',$input['parm']]])->append(['extension'])->order(['id'=>'desc'])->select()->toArray();//查询CLASS数据
            if($input['scene']=='simple'){
                //简易报表
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //标题数据
                $excel[]=['type'=>'title','info'=>'销售单列表'];
                //表格数据
                $field=[
                	'frameData|name'=>'所属组织',
                	'customerData|name'=>'客户',
                	'time'=>'单据时间',
                	'number'=>'单据编号',
                	'total'=>'单据金额',
                	'actual'=>'实际金额',
                	'money'=>'单据收款',
                	'extension|amount'=>'核销金额',
                	'cost'=>'单据费用',
                	'peopleData|name'=>'关联人员',
                	'extension|examine'=>'审核状态',
                	'extension|nucleus'=>'核销状态',
                	'extension|cse'=>'费用状态',
                    'extension|invoice'=>'发票状态',
                    'extension|check'=>'核对状态',
                	'userData|name'=>'制单人',
                	'data'=>'备注信息'
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
                //统计数据
                $excel[]=['type'=>'node','info'=>[
                    '总数:'.count($source),
                    '总单据金额:'.mathArraySum(array_column($source,'total')),
                    '总实际金额:'.mathArraySum(array_column($source,'actual')),
                    '总单据收款:'.mathArraySum(array_column($source,'money')),
                    '总核销金额:'.mathArraySum(arrayColumns($source,['extension','amount'])),
                    '总单据费用:'.mathArraySum(array_column($source,'cost'))
                ]];
                //导出execl
                buildExcel('销售单列表',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                foreach ($source as $sourceVo) {
                    //开始构造导出数据
                    $excel=[];//初始化导出数据
                    //标题数据
                    $excel[]=['type'=>'title','info'=>'销售单'];
                    //节点数据
                    $excel[]=['type'=>'node','info'=>[
                        '客户:'.$sourceVo['customerData']['name'],
                        '单据日期:'.$sourceVo['time'],
                        '单据编号:'.$sourceVo['number']]
                    ];
                    //表格数据
                    $field=[
                    	'goodsData|name'=>'商品名称',
                    	'goodsData|spec'=>'规格型号',
                    	'attr'=>'辅助属性',
                    	'unit'=>'单位',
                    	'warehouseData|name'=>'仓库',
                    	'batch'=>'批次号',
                    	'mfd'=>'生产日期',
                    	'price'=>'单价',
                    	'nums'=>'数量',
                    	'extension|serial'=>'序列号',
                    	'retreat'=>'退货数量',
                    	'discount'=>'折扣率',
                    	'dsc'=>'折扣额',
                    	'total'=>'金额',
                    	'tax'=>'税率',
                    	'tat'=>'税额',
                    	'tpt'=>'价税合计',
                    	'data'=>'备注信息'
                    ];
                    //构造表内数据
                    $info=SellInfo::with(['goodsData','warehouseData'])->where([['pid','=',$sourceVo['id']]])->order(['id'=>'asc'])->append(['extension'])->select()->toArray();
                    //批次号匹配
                    if(empty(search($info)->where([['goodsData|batch','=',true]])->find())){
                       unset($field['batch']);
                    }
                    //生产日期匹配
                    if(empty(search($info)->where([['goodsData|validity','=',true]])->find())){
                       unset($field['mfd']);
                    }
                    //序列号匹配
                    if(empty(search($info)->where([['goodsData|serial','=',true]])->find())){
                       unset($field['extension|serial']);
                    }
                    //退货数量匹配
                    if(empty(search($info)->where([['retreat','<>',0]])->find())){
                       unset($field['retreat']);
                    }
                    //税金匹配
                    $fun=getSys('fun');
                    if(empty(search($info)->where([['tax','<>',0]])->find()) && !$fun['tax']){
                       unset($field['tax']);
                       unset($field['tat']);
                       unset($field['tpt']);
                    }
                    $thead=array_values($field);//表格标题
                    $tbody=[];//表格内容
                    foreach ($info as $infoVo) {
                        $rowData=[];
                        foreach (array_keys($field) as $fieldVo) {
                            $rowData[]=arraySeek($infoVo,$fieldVo);//多键名数据赋值
                        }
                    	$tbody[]=$rowData;//加入行数据
                    }
                    $excel[]=['type'=>'table','info'=>['thead'=>$thead,'tbody'=>$tbody]];//表格数据
                    //节点数据
                    $excel[]=['type'=>'node','info'=>[
                        '单据金额:'.$sourceVo['total'],
                        '单据费用:'.$sourceVo['cost'],
                        '实际金额:'.$sourceVo['actual'],
                        '核销金额:'.$sourceVo['extension']['amount'],
                        '结算账户:'.arraySeek($sourceVo,'accountData|name'),
                        '发票信息:'.$sourceVo['invoice'],
                        '关联人员:'.arraySeek($sourceVo,'peopleData|name'),
                        '物流信息:'.$sourceVo['extension']['logistics'],
                        '备注信息:'.$sourceVo['data']]
                    ];
                    //生成execl
                    $files[]=buildExcel($sourceVo['number'],$excel,false);
                    
                }
                buildZip('销售单_'.time(),$files);
            }
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}