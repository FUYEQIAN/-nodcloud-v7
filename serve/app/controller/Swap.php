<?php
namespace app\controller;
use app\controller\Acl;
use app\model\{Swap as Swaps,SwapInfo,Cost,Goods};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Swap extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['number','fullLike'],
                ['people','fullEq'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['examine','fullDec1'],
                ['cse','fullDec1'],
                ['user','fullEq'],
                ['data','fullLike']
            ]);//构造SQL
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['id','in',array_column(Db::name('swap_info')->where([['goods','in',$goods]])->select()->toArray(),'pid')];
            }
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('swap',$sql);//数据鉴权
            $count = Swaps::where($sql)->count();//获取总条数
            $info = Swaps::with(['frameData','peopleData','userData','costData','recordData'])->where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();//查询分页数据
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
        if(existFull($input,['class','info']) && isset($input['class']['id']) && isset($input['cost'])){
            //构造|验证CLASS
            try {
                $class=$input['class'];
                $class['frame']=userInfo(getUserID(),'frame');
                $class['user']=getUserID();
                $class['cse']=empty($class['cost'])?3:0;
                $class['examine']=0;
                empty($class['id'])?$this->validate($class,'app\validate\Swap'):$this->validate($class,'app\validate\Swap.update');
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
                    $this->validate($infoVo,'app\validate\SwapInfo');
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
                    $createInfo=Swaps::create($class);
                    $class['id']=$createInfo['id'];//转存主键
                    Db::name('record')->insert(['type'=>'swap','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'新增单据']);
                    pushLog('新增调拨单[ '.$class['number'].' ]');//日志
                }else{
                    //更新数据
                    $updateInfo=Swaps::update($class);
                    Db::name('record')->insert(['type'=>'swap','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'更新单据']);
                    pushLog('更新调拨单[ '.$class['number'].' ]');//日志
                }
                
                //INFO数据
                SwapInfo::where([['pid','=',$class['id']]])->delete();
                foreach ($input['info'] as $infoKey=>$infoVo) {
                    $input['info'][$infoKey]['pid']=$class['id'];
                }
                $model = new SwapInfo;
                $model->saveAll($input['info']);
                
                //COST数据
                Cost::where([['type','=','swap'],['class','=',$class['id']]])->delete();
                foreach ($input['cost'] as $costKey=>$costVo) {
                    unset($input['cost'][$costKey]['id']);
                    $input['cost'][$costKey]['type']='swap';
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
            $class=Swaps::where([['id','=',$input['parm']]])->find();
            $info=SwapInfo::with(['goodsData','warehouseData','storehouseData'])->where([['pid','=',$input['parm']]])->order(['id'=>'asc'])->select();
            $cost=Cost::where([['type','=','swap'],['class','=',$input['parm']]])->order(['id'=>'asc'])->select();
            $result=['state'=>'success','info'=>[
                'class'=>$class,
                'info'=>$info,
                'cost'=>$cost
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
            $data=Db::name('swap')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            $search=search($data)->where([['examine','=','1']])->find();
            if(empty($search)){
                Db::startTrans();
                try {
                    Db::name('swap')->where([['id','in',$input['parm']]])->delete();
                    Db::name('swap_info')->where([['pid','in',$input['parm']]])->delete();
                    Db::name('cost')->where([['type','=','swap'],['class','in',$input['parm']]])->delete();
                    Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除调拨单[ '.implode(' | ',array_column($data,'number')).' ]']);
                    
                	Db::commit();
                	$result=['state'=>'success'];
                } catch (\Exception $e) {
                	Db::rollback();
                	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
                }
            }else{
                $result=['state'=>'error','info'=>'单据['.$search['number'].']已审核,不可删除!'];
            }
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
            $classList=Db::name('swap')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            $infoList=Db::name('swap_info')->where([['pid','in',$input['parm']]])->order(['id'=>'asc'])->select()->toArray();
            //2 综合处理
            foreach ($input['parm'] as $parmVo) {
                //1 匹配数据
                $class=search($classList)->where([['id','=',$parmVo]])->find();
                $info=search($infoList)->where([['pid','=',$parmVo]])->select();
                //1.1 商品数据
                $goodsList=Db::name('goods')->where([['id','in',array_unique(array_column($info,'goods'))]])->select()->toArray();
                //1.2 综合匹配
                if(empty($class['examine'])){
                    //1 构造数据
                    $batchGather=[];
                    $serialGather=[];
                    $roomWhereOrSql=[];
                    $toRoomWhereOrSql=[];
                    foreach ($info as $infoVo) {
                        //1 批次号
                        empty($infoVo['batch'])||$batchGather[]=$infoVo['batch'];
                        //2 序列号
                        $serialGather=array_merge($serialGather,json_decode($infoVo['serial']));
                        //3 仓储条件|调出
                        empty($infoVo['warehouse'])||$roomWhereOrSql[]=[['warehouse','=',$infoVo['warehouse']],['goods','=',$infoVo['goods']],['attr','=',$infoVo['attr']]];
                        //4 仓储条件|调入
                        empty($infoVo['storehouse'])||$toRoomWhereOrSql[]=[['warehouse','=',$infoVo['storehouse']],['goods','=',$infoVo['goods']],['attr','=',$infoVo['attr']]];
                    }
                    //3 匹配数据
                    empty($batchGather)||$batchList=Db::name('batch')->where([['number','in',$batchGather]])->select()->toArray();
                    empty($serialGather)||$serialList=Db::name('serial')->where([['number','in',$serialGather]])->select()->toArray();
                    //4 仓储数据|调出
                    if(!empty($roomWhereOrSql)){
                        //1 去重转存
                        $roomWhereOrSql=array_unique($roomWhereOrSql,SORT_REGULAR);
                        //2 仓储匹配
                        $roomList=Db::name('room')->whereOr($roomWhereOrSql)->select()->toArray();
                    }
                    //5 仓储数据|调入
                    if(!empty($toRoomWhereOrSql)){
                        //1 去重转存
                        $toRoomWhereOrSql=array_unique($toRoomWhereOrSql,SORT_REGULAR);
                        //2 仓储匹配
                        $toRoomList=Db::name('room')->whereOr($toRoomWhereOrSql)->select()->toArray();
                    }
                }
                //2 CLASS验证
                if($class['time']<=$period){
                    return json(['state'=>'error','info'=>'操作单据[ '.$class['number'].' ]失败,原因:单据日期与结账日期冲突!']);
                    exit;
                }
                if(empty($class['examine'])){
                    //2 单据费用
                    $cost=Db::name('cost')->alias('cost')->where([['type','=','swap'],['class','=',$class['id']]])->whereExists(function($query){
                        $query->name('oce_info')->where([['source','=',Db::raw('cost.id')]])->limit(1);
                    })->find();
                    if(!empty($cost)){
                        return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:该单据存在关联其它支出单!']);
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
                            //2 多单位处理
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
                            //3 序列号
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
                            //4 负库存验证
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
                                    $batchFind=search($batchList)->where([['room','=',$room['id']],['number','=',$infoVo['batch']],['time','=',$infoVo['mfd']]],true)->find();
                                    if(empty($batchFind)){
                                        return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行批次信息无效!']);
                                        exit;
                                    }else{
                                        if(bccomp($info[$infoKey]['basic']['nums'],$batchFind['nums'])==1){
                                            return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行批次库存不足!']);
                                            exit;
                                        }else{
                                            $batchList[$batchFind['rowKey']]['nums']=math()->chain($batchFind['nums'])->sub($info[$infoKey]['basic']['nums'])->done();
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
                            //1 验证序列号|调出
                            $serialInfoCollect=Db::name('serial_info')->where([['type','=','swapOut'],['info','in',array_column($info,'id')]])->select()->toArray();
                            if(!empty($serialInfoCollect)){
                                //序列号状态对[已调拨]
                                $serialFind=Db::name('serial')->where([['id','in',array_column($serialInfoCollect,'pid')],['state','<>',2]])->find();
                                if(!empty($serialFind)){
                                    return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行序列号[ '.$serialFind['number'].' ]状态不正确!']);
                                    exit;
                                }
                            }
                            //2 验证序列号|调入
                            $serialInfoCollect=Db::name('serial_info')->where([['type','=','swapEnter'],['info','in',array_column($info,'id')]])->select()->toArray();
                            if(!empty($serialInfoCollect)){
                                //序列号状态对[未销售]
                                $serialFind=Db::name('serial')->where([['id','in',array_column($serialInfoCollect,'pid')],['state','<>',0]])->find();
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
                        $outStore=['room'=>[],'roomInfo'=>[],'batch'=>[],'batchInfo'=>[],'serial'=>[],'serialInfo'=>[],'serve'=>[],'serveInfo'=>[]];
                        $enterStore=['room'=>[],'roomInfo'=>[],'batch'=>[],'batchInfo'=>[],'serial'=>[],'serialInfo'=>[],'serve'=>[],'serveInfo'=>[]];
                        foreach ($info as $infoKey=>$infoVo){
                            //判断商品类型
                            $goods=search($goodsList)->where([['id','=',$infoVo['goods']]])->find();
                            if(empty($goods['type'])){
                                //常规商品
                                //--- 调出数据 ---
                                //1 仓储
                                $outStore['room'][]=['warehouse'=>$infoVo['warehouse'],'goods'=>$infoVo['goods'],'attr'=>$infoVo['attr'],'nums'=>$infoVo['basic']['nums']];
                                //2 仓储详情
                                $outStore['roomInfo'][]=['pid'=>null,'type'=>'swapOut','class'=>$class['id'],'info'=>$infoVo['id'],'time'=>$class['time'],'direction'=>0,'price'=>$infoVo['basic']['price'],'nums'=>$infoVo['basic']['nums']];
                                //3 批次号
                                if(empty($infoVo['batch'])){
                                    $outStore['batch'][]=[];
                                    $outStore['batchInfo'][]=[];
                                }else{
                                    $outStore['batch'][]=['room'=>null,'warehouse'=>$infoVo['warehouse'],'goods'=>$infoVo['goods'],'number'=>$infoVo['batch'],'time'=>$infoVo['mfd'],'nums'=>$infoVo['basic']['nums']];
                                    $outStore['batchInfo'][]=['pid'=>null,'type'=>'swapOut','class'=>$class['id'],'info'=>$infoVo['id'],'direction'=>0,'nums'=>$infoVo['basic']['nums']];
                                }
                                //4 序列号
                                if(empty($infoVo['serial'])){
                                    $outStore['serial'][]=[];
                                    $outStore['serialInfo'][]=[];
                                }else{
                                    $serial=[];
                                    $serialInfo=[];
                                    foreach ($infoVo['serial'] as $serialVo) {
                                        $serial[]=['room'=>null,'warehouse'=>$infoVo['warehouse'],'batch'=>null,'goods'=>$infoVo['goods'],'number'=>$serialVo,'state'=>2];
                                        $serialInfo[]=['pid'=>null,'type'=>'swapOut','class'=>$class['id'],'info'=>$infoVo['id']];
                                    }
                                    $outStore['serial'][]=$serial;
                                    $outStore['serialInfo'][]=$serialInfo;
                                }
                                //--- 调入数据 ---
                                //5 仓储
                                $enterStore['room'][]=['warehouse'=>$infoVo['storehouse'],'goods'=>$infoVo['goods'],'attr'=>$infoVo['attr'],'nums'=>$infoVo['basic']['nums']];
                                //6 仓储详情
                                $enterStore['roomInfo'][]=['pid'=>null,'type'=>'swapEnter','class'=>$class['id'],'info'=>$infoVo['id'],'time'=>$class['time'],'direction'=>1,'price'=>$infoVo['basic']['price'],'nums'=>$infoVo['basic']['nums']];
                                //7 批次号
                                if(empty($infoVo['batch'])){
                                    $enterStore['batch'][]=[];
                                    $enterStore['batchInfo'][]=[];
                                }else{
                                    $enterStore['batch'][]=['room'=>null,'warehouse'=>$infoVo['storehouse'],'goods'=>$infoVo['goods'],'number'=>$infoVo['batch'],'time'=>$infoVo['mfd'],'nums'=>$infoVo['basic']['nums']];
                                    $enterStore['batchInfo'][]=['pid'=>null,'type'=>'swapEnter','class'=>$class['id'],'info'=>$infoVo['id'],'direction'=>1,'nums'=>$infoVo['basic']['nums']];
                                }
                                //8 序列号
                                if(empty($infoVo['serial'])){
                                    $enterStore['serial'][]=[];
                                    $enterStore['serialInfo'][]=[];
                                }else{
                                    $serial=[];
                                    $serialInfo=[];
                                    foreach ($infoVo['serial'] as $serialVo) {
                                        $serial[]=['room'=>null,'warehouse'=>$infoVo['storehouse'],'batch'=>null,'goods'=>$infoVo['goods'],'number'=>$serialVo,'state'=>0];
                                        $serialInfo[]=['pid'=>null,'type'=>'swapEnter','class'=>$class['id'],'info'=>$infoVo['id']];
                                    }
                                    $enterStore['serial'][]=$serial;
                                    $enterStore['serialInfo'][]=$serialInfo;
                                }
                            }else{
                                //9 服务商品
                                $outStore['serve'][]=['goods'=>$infoVo['goods'],'attr'=>$infoVo['attr'],'nums'=>$infoVo['nums']];
                                $outStore['serveInfo'][]=['pid'=>null,'type'=>'swap','class'=>$class['id'],'info'=>$infoVo['id'],'time'=>$class['time'],'price'=>$infoVo['price'],'nums'=>$infoVo['nums']];
                            }
                        }
                        //--- 调出数据 ---
                        //2 仓储
                        if(!empty($outStore['room'])){
                            //1 构造数据
                            $roomInsert=[];
                            foreach ($outStore['room'] as $roomVo) {
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
                            foreach ($outStore['room'] as $roomKey=>$roomVo) {
                                $roomFind=search($room)->where([['warehouse','=',$roomVo['warehouse']],['goods','=',$roomVo['goods']],['attr','=',$roomVo['attr']]])->find();
                                $outStore['room'][$roomKey]['id']=$roomFind['id'];
                                $roomDuplicate[]=['id'=>$roomFind['id'],'nums'=>$roomVo['nums']];
                            }
                            //4 更新数据
                            Db::name('room')->duplicate(['nums'=>Db::raw('nums - VALUES(`nums`)')])->insertAll($roomDuplicate);
                        }
                        //3 仓储详情
                        if(!empty($outStore['roomInfo'])){
                            //1 填充数据
                            foreach ($outStore['roomInfo'] as $roomInfoKey=>$roomInfoVo) {
                                $outStore['roomInfo'][$roomInfoKey]['pid']=$outStore['room'][$roomInfoKey]['id'];
                            }
                            //2 创建数据
                            Db::name('room_info')->insertAll($outStore['roomInfo']);
                        }
                        //4 批次号
                        if(!empty($outStore['batch'])){
                            //1 构造数据
                            $batchData=[];
                            foreach ($outStore['batch'] as $batchKey=>$batchVo) {
                                if(!empty($batchVo)){
                                    $outStore['batch'][$batchKey]['room']=$outStore['room'][$batchKey]['id'];
                                    $batchData[]=$outStore['batch'][$batchKey];
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
                                foreach ($outStore['batch'] as $batchKey=>$batchVo) {
                                    if(!empty($batchVo)){
                                        $batchFind=search($batch)->where([['room','=',$batchVo['room']],['number','=',$batchVo['number']],['time','=',$batchVo['time']]])->find();
                                        $outStore['batch'][$batchKey]['id']=$batchFind['id'];
                                        $batchDuplicate[]=['id'=>$batchFind['id'],'nums'=>$batchVo['nums']];
                                    }
                                }
                                //4 更新数据
                                Db::name('batch')->duplicate(['nums'=>Db::raw('nums - VALUES(`nums`)')])->insertAll($batchDuplicate);
                            }
                        }
                        //5 批次号详情
                        if(!empty($outStore['batchInfo'])){
                            //1 构造数据
                            $batchInfoInstall=[];
                            foreach ($outStore['batchInfo'] as $batchInfoKey=>$batchInfoVo) {
                                if(!empty($batchInfoVo)){
                                    $batchInfoVo['pid']=$outStore['batch'][$batchInfoKey]['id'];
                                    $batchInfoInstall[]=$batchInfoVo;
                                }
                            }
                            //2 排除数据|[[],[],[]]
                            if(!empty($batchInfoInstall)){
                                //创建数据
                                Db::name('batch_info')->insertAll($batchInfoInstall);
                            }
                        }
                        //6 序列号
                        if(!empty($outStore['serial'])){
                            //1 构造数据
                            $serialData=[];
                            foreach ($outStore['serial'] as $serialKey=>$item) {
                                if(!empty($item)){
                                    foreach ($item as $itemKey=>$itemVo) {
                                        $outStore['serial'][$serialKey][$itemKey]['room']=$outStore['room'][$serialKey]['id'];
                                        $outStore['serial'][$serialKey][$itemKey]['batch']=empty($outStore['batch'][$serialKey])?0:$outStore['batch'][$serialKey]['id'];
                                        $serialData[]=$outStore['serial'][$serialKey][$itemKey];
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
                                foreach ($outStore['serial'] as $serialKey=>$item) {
                                    if(!empty($item)){
                                        foreach ($item as $itemKey=>$itemVo) {
                                            $serialFind=search($serial)->where([['room','=',$itemVo['room']],['batch','=',$itemVo['batch']],['number','=',$itemVo['number']]])->find();
                                            $outStore['serial'][$serialKey][$itemKey]['id']=$serialFind['id'];
                                            $serialFind['state']==0&&$serialDuplicate[]=$serialFind['id'];
                                        }
                                    }
                                }
                                //4 更新数据|状态变更
                                empty($serialDuplicate)||Db::name('serial')->where([['id','in',$serialDuplicate]])->update(['state'=>2]);
                            }
                        }
                        //7 序列号详情
                        if(!empty($outStore['serialInfo'])){
                            //1 构造数据
                            $serialInfoInstall=[];
                            foreach ($outStore['serialInfo'] as $serialInfoKey=>$item) {
                                if(!empty($item)){
                                    foreach ($item as $itemKey=>$itemVo) {
                                        $itemVo['pid']=$outStore['serial'][$serialInfoKey][$itemKey]['id'];
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
                        //8 服务商品
                        if(!empty($outStore['serve'])){
                            //1 匹配数据|去重
                            $serveWhereOrSql=array_unique(array_map(function($item){
                                return [['goods','=',$item['goods']],['attr','=',$item['attr']]];
                            },$outStore['serve']),SORT_REGULAR);
                            $serve=Db::name('serve')->whereOr($serveWhereOrSql)->select()->toArray();
                            //2 构造数据
                            $serveInsert=[];
                            foreach ($outStore['serve'] as $serveVo) {
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
                            foreach ($outStore['serve'] as $serveKey=>$serveVo) {
                                $serveFind=search($serve)->where([['goods','=',$serveVo['goods']],['attr','=',$serveVo['attr']]])->find();
                                $outStore['serve'][$serveKey]['id']=$serveFind['id'];
                                $serveDuplicate[]=['id'=>$serveFind['id'],'nums'=>$serveVo['nums']];
                            }
                            //5 更新数据
                            Db::name('serve')->duplicate(['nums'=>Db::raw('nums + VALUES(`nums`)')])->insertAll($serveDuplicate);
                        }
                        //9 服务商品详情
                        if(!empty($outStore['serveInfo'])){
                            //1 填充数据
                            foreach ($outStore['serveInfo'] as $serveInfoKey=>$serveInfoVo) {
                                $outStore['serveInfo'][$serveInfoKey]['pid']=$outStore['serve'][$serveInfoKey]['id'];
                            }
                            //2 创建数据
                            Db::name('serve_info')->insertAll($outStore['serveInfo']);
                        }
                        // --- 调入数据 ---
                        //10 仓储
                        if(!empty($enterStore['room'])){
                            //1 构造数据
                            $roomInsert=[];
                            foreach ($enterStore['room'] as $roomVo) {
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
                            $room=Db::name('room')->whereOr($toRoomWhereOrSql)->select()->toArray();
                            foreach ($enterStore['room'] as $roomKey=>$roomVo) {
                                $roomFind=search($room)->where([['warehouse','=',$roomVo['warehouse']],['goods','=',$roomVo['goods']],['attr','=',$roomVo['attr']]])->find();
                                $enterStore['room'][$roomKey]['id']=$roomFind['id'];
                                $roomDuplicate[]=['id'=>$roomFind['id'],'nums'=>$roomVo['nums']];
                            }
                            //4 更新数据
                            Db::name('room')->duplicate(['nums'=>Db::raw('nums + VALUES(`nums`)')])->insertAll($roomDuplicate);
                        }
                        //11 仓储详情
                        if(!empty($enterStore['roomInfo'])){
                            //1 填充数据
                            foreach ($enterStore['roomInfo'] as $roomInfoKey=>$roomInfoVo) {
                                $enterStore['roomInfo'][$roomInfoKey]['pid']=$enterStore['room'][$roomInfoKey]['id'];
                            }
                            //2 创建数据
                            Db::name('room_info')->insertAll($enterStore['roomInfo']);
                        }
                        //12 批次号
                        if(!empty($enterStore['batch'])){
                            //1 构造数据
                            $batchData=[];
                            foreach ($enterStore['batch'] as $batchKey=>$batchVo) {
                                if(!empty($batchVo)){
                                    $enterStore['batch'][$batchKey]['room']=$enterStore['room'][$batchKey]['id'];
                                    $batchData[]=$enterStore['batch'][$batchKey];
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
                                foreach ($enterStore['batch'] as $batchKey=>$batchVo) {
                                    if(!empty($batchVo)){
                                        $batchFind=search($batch)->where([['room','=',$batchVo['room']],['number','=',$batchVo['number']],['time','=',$batchVo['time']]])->find();
                                        $enterStore['batch'][$batchKey]['id']=$batchFind['id'];
                                        $batchDuplicate[]=['id'=>$batchFind['id'],'nums'=>$batchVo['nums']];
                                    }
                                }
                                //4 更新数据
                                Db::name('batch')->duplicate(['nums'=>Db::raw('nums + VALUES(`nums`)')])->insertAll($batchDuplicate);
                            }
                        }
                        //13 批次号详情
                        if(!empty($enterStore['batchInfo'])){
                            //1 构造数据
                            $batchInfoInstall=[];
                            foreach ($enterStore['batchInfo'] as $batchInfoKey=>$batchInfoVo) {
                                if(!empty($batchInfoVo)){
                                    $batchInfoVo['pid']=$enterStore['batch'][$batchInfoKey]['id'];
                                    $batchInfoInstall[]=$batchInfoVo;
                                }
                            }
                            //2 排除数据|[[],[],[]]
                            if(!empty($batchInfoInstall)){
                                //创建数据
                                Db::name('batch_info')->insertAll($batchInfoInstall);
                            }
                        }
                        //14 序列号
                        if(!empty($enterStore['serial'])){
                            //1 构造数据
                            $serialInsert=[];
                            foreach ($enterStore['serial'] as $serialKey=>$item) {
                                if(!empty($item)){
                                    foreach ($item as $itemKey=>$itemVo) {
                                        $enterStore['serial'][$serialKey][$itemKey]['room']=$enterStore['room'][$serialKey]['id'];
                                        $enterStore['serial'][$serialKey][$itemKey]['batch']=empty($enterStore['batch'][$serialKey])?0:$enterStore['batch'][$serialKey]['id'];
                                        $serialInsert[]=$enterStore['serial'][$serialKey][$itemKey];
                                    }
                                }
                            }
                            //2 排除数据|[[],[],[]]
                            if(!empty($serialInsert)){
                                //1 创建数据
                                Db::name('serial')->insertAll($serialInsert);
                                //2 匹配主键
                                $serial=Db::name('serial')->where([['number','in',$serialGather]])->select()->toArray();
                                foreach ($enterStore['serial'] as $serialKey=>$item) {
                                    if(!empty($item)){
                                        foreach ($item as $itemKey=>$itemVo) {
                                            $serialFind=search($serial)->where([['room','=',$itemVo['room']],['batch','=',$itemVo['batch']],['number','=',$itemVo['number']]])->find();
                                            $enterStore['serial'][$serialKey][$itemKey]['id']=$serialFind['id'];
                                        }
                                    }
                                }
                            }
                        }
                        //15 序列号详情
                        if(!empty($enterStore['serialInfo'])){
                            //1 构造数据
                            $serialInfoInstall=[];
                            foreach ($enterStore['serialInfo'] as $serialInfoKey=>$item) {
                                if(!empty($item)){
                                    foreach ($item as $itemKey=>$itemVo) {
                                        $itemVo['pid']=$enterStore['serial'][$serialInfoKey][$itemKey]['id'];
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
                        
                        //16 更新单据
                        Db::name('swap')->where([['id','=',$class['id']]])->update(['examine'=>1]);
                        //17 单据记录
                        Db::name('record')->insert(['type'=>'swap','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'审核单据']);
                        //18 收发记录
                        $summary=new Summary;
                        $summary->note('swapOut',$class['id'],true);
                        $summary->note('swapEnter',$class['id'],true);
                        //19 记录操作
                        pushLog('审核调拨单[ '.$class['number'].' ]');//单据日志
                    }else{
                        //反审核
                        //--- 调出数据 ---
                        //1 匹配数据
                        $roomInfoList=Db::name('room_info')->where([['type','=','swapOut'],['info','in',array_column($info,'id')]])->select()->toArray();
                        $batchInfoList=Db::name('batch_info')->where([['type','=','swapOut'],['info','in',array_column($info,'id')]])->select()->toArray();
                        $serialInfoList=Db::name('serial_info')->where([['type','=','swapOut'],['info','in',array_column($info,'id')]])->select()->toArray();
                        $serveInfoList=Db::name('serve_info')->where([['type','=','swap'],['info','in',array_column($info,'id')]])->select()->toArray();
                        //1 仓储
                        $roomDuplicate=[];
                        foreach ($roomInfoList as $roomInfoVo) {
                            $roomDuplicate[]=['id'=>$roomInfoVo['pid'],'nums'=>$roomInfoVo['nums']];
                        }
                        //2.1 更新仓储
                        Db::name('room')->duplicate(['nums'=>Db::raw('nums + VALUES(`nums`)')])->insertAll($roomDuplicate);
                        //2.2 删除仓储详情
                        Db::name('room_info')->where([['id','in',array_column($roomInfoList,'id')]])->delete();
                        //2.3 仓储|冗余
                        $roomPk=array_unique(array_column($roomInfoList,'pid'));
                        $roomInfoData=Db::name('room_info')->where([['pid','in',$roomPk]])->select()->toArray();
                        $roomDiff=array_diff($roomPk,array_unique(array_column($roomInfoData,'pid')));
                        empty($roomDiff)||Db::name('room')->where([['id','in',$roomDiff]])->delete();
                        //3 批次号
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
                        //4 序列号
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
                        //5 服务
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
                        //--- 调入数据 ---
                        //6 匹配数据
                        $roomInfoList=Db::name('room_info')->where([['type','=','swapEnter'],['info','in',array_column($info,'id')]])->select()->toArray();
                        $batchInfoList=Db::name('batch_info')->where([['type','=','swapEnter'],['info','in',array_column($info,'id')]])->select()->toArray();
                        $serialInfoList=Db::name('serial_info')->where([['type','=','swapEnter'],['info','in',array_column($info,'id')]])->select()->toArray();
                        //7 仓储
                        $roomDuplicate=[];
                        foreach ($roomInfoList as $roomInfoVo) {
                            $roomDuplicate[]=['id'=>$roomInfoVo['pid'],'nums'=>$roomInfoVo['nums']];
                        }
                        //7.1 更新仓储
                        Db::name('room')->duplicate(['nums'=>Db::raw('nums - VALUES(`nums`)')])->insertAll($roomDuplicate);
                        //7.2 删除仓储详情
                        Db::name('room_info')->where([['id','in',array_column($roomInfoList,'id')]])->delete();
                        //7.3 仓储|冗余
                        $roomPk=array_unique(array_column($roomInfoList,'pid'));
                        $roomInfoData=Db::name('room_info')->where([['pid','in',$roomPk]])->select()->toArray();
                        $roomDiff=array_diff($roomPk,array_unique(array_column($roomInfoData,'pid')));
                        empty($roomDiff)||Db::name('room')->where([['id','in',$roomDiff]])->delete();
                        //8 批次号
                        if(!empty($batchInfoList)){
                            //1 构造数据
                            $batchInfoDuplicate=array_map(function($item){
                                return ['id'=>$item['pid'],'nums'=>$item['nums']];
                            },$batchInfoList);
                            //2 更新批次号
                            Db::name('batch')->duplicate(['nums'=>Db::raw('nums - VALUES(`nums`)')])->insertAll($batchInfoDuplicate);
                            //3 删除批次号详情
                            Db::name('batch_info')->where([['id','in',array_column($batchInfoList,'id')]])->delete();
                            //4 批次号|冗余
                            $batchPk=array_unique(array_column($batchInfoList,'pid'));
                            $batchInfoData=Db::name('batch_info')->where([['pid','in',$batchPk]])->select()->toArray();
                            $batchDiff=array_diff($batchPk,array_unique(array_column($batchInfoData,'pid')));
                            empty($batchDiff)||Db::name('batch')->where([['id','in',$batchDiff]])->delete();
                        }
                        //9 序列号
                        if(!empty($serialInfoList)){
                            //1 删除序列号
                            Db::name('serial')->where([['id','in',array_column($serialInfoList,'pid')]])->delete();
                            //2 删除序列号详情
                            Db::name('serial_info')->where([['id','in',array_column($serialInfoList,'id')]])->delete();
                        }
                        //10 更新单据
                        Db::name('swap')->where([['id','=',$class['id']]])->update(['examine'=>0]);
                        //11 单据记录
                        Db::name('record')->insert(['type'=>'swap','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'反审核单据']);
                        //12 收发记录
                        $summary=new Summary;
                        $summary->note('swapOut',$class['id'],false);
                        $summary->note('swapEnter',$class['id'],false);
                        //13 记录操作
                        pushLog('反审核调拨单[ '.$class['number'].' ]');//单据日志
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
                $fileInfo=Filesystem::disk('upload')->putFile('swap', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                $result = ['state' => 'error','info' => $e->getMessage()];
            }
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
				//关联人员匹配
                if(empty($data[3]['D'])){
                    $people=['id'=>0];
                }else{
                    $people=Db::name('people')->where([['name','=',$data[3]['D']]])->find();
                    if(empty($people)){
                        throw new ValidateException('关联人员[ '.$data[3]['D'].' ]未匹配!');
                    }
                }
                $class=[
                    'frame'=>userInfo(getUserID(),'frame'),
                    'time'=>$data[3]['A'],
                    'number'=>$data[3]['B'],
                    'total'=>0,
                    'people'=>$people['id'],
                    'logistics'=>["key"=>"auto","name"=>"自动识别","number"=>$data[3]['E']],
                    'file'=>[],
                    'data'=>$data[3]['F'],
                    'more'=>[],
                    'examine'=>0,
                    'cse'=>0,
                    'user'=>getUserID()
                ];
                $this->validate($class,'app\validate\Swap');//数据合法性验证
                //初始化INFO
                $info=[];
                $goods=Goods::with(['attr'])->where([['name','in',array_column($data,'G')]])->select()->toArray();
                $warehouse=Db::name('warehouse')->where([['name','in',array_column($data,'J')]])->select()->toArray();
                $storehouse=Db::name('warehouse')->where([['name','in',array_column($data,'K')]])->select()->toArray();
                foreach ($data as $dataKey=>$dataVo) {
					$record=[
						'goods'=>$dataVo['G'],
						'attr'=>$dataVo['H'],
						'unit'=>$dataVo['I'],
						'warehouse'=>$dataVo['J'],
						'storehouse'=>$dataVo['K'],
						'batch'=>$dataVo['L'],
						'mfd'=>$dataVo['M'],
						'price'=>$dataVo['N'],
						'nums'=>$dataVo['O'],
						'serial'=>explode(',',$dataVo['P']),
						'total'=>$dataVo['Q'],
						'data'=>$dataVo['R']
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
					//调出仓库匹配
					if(empty($goodsFind['type'])){
					    //常规产品
					    $warehouseFind=search($warehouse)->where([['name','=',$record['warehouse']]])->find();
                        if(empty($warehouseFind)){
                            throw new ValidateException('模板文件第'.$dataKey.'行调出仓库[ '.$record['warehouse'].' ]未匹配!');
                        }else{
                            $record['warehouse']=$warehouseFind['id'];
                        }
					}else{
					    //服务产品
					    $record['warehouse']=null;
					}
					//调入仓库匹配
					if(empty($goodsFind['type'])){
					    //常规产品
					    $storehouseFind=search($storehouse)->where([['name','=',$record['storehouse']]])->find();
                        if(empty($storehouseFind)){
                            throw new ValidateException('模板文件第'.$dataKey.'行调入仓库[ '.$record['storehouse'].' ]未匹配!');
                        }else{
                            $record['storehouse']=$storehouseFind['id'];
                        }
					}else{
					    //服务产品
					    $record['storehouse']=null;
					}
					//调入调出匹配
					if(empty($goodsFind['type'])){
					    if($record['warehouse']==$record['storehouse']){
					        throw new ValidateException('模板文件第'.$dataKey.'行调出调入仓库不可相等!');
					    }
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
					//成本匹配
					if(!preg_match("/^\d+(\.\d{0,".$fun['digit']['money']."})?$/",$record['price'])){
					    throw new ValidateException('模板文件第'.$dataKey.'行成本不正确!');
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
                        $this->validate($record,'app\validate\SwapInfo');//数据合法性验证
                        $record['total']=math()->chain($record['price'])->mul($record['nums'])->round($fun['digit']['money'])->done();
                        //转存数据
                        $class['total']=math()->chain($class['total'])->add($record['total'])->done();//累加单据金额
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
                Db::startTrans();
                try {
                    //新增CLASS
                    $classData=Swaps::create($class);
                    //新增INFO
                    foreach ($info as $infoKey=>$infoVo) {
                        $info[$infoKey]['pid']=$classData['id'];
                    }
                    $model = new SwapInfo;
                    $model->saveAll($info);
                    Db::name('record')->insert(['type'=>'swap','source'=>$classData['id'],'time'=>time(),'user'=>getUserID(),'info'=>'导入单据']);
                    pushLog('导入调拨单[ '.$classData['number'].' ]');//日志
                    
                    Db::commit();
                    $result=['state'=>'success'];
                } catch (\Exception $e) {
                	Db::rollback();
                	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
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
		    pushLog('导出调拨单列表');//日志
            $source=Swaps::with(['frameData','peopleData','userData','recordData'])->where([['id','in',$input['parm']]])->append(['extension'])->order(['id'=>'desc'])->select()->toArray();//查询CLASS数据
            if($input['scene']=='simple'){
                //简易报表
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //标题数据
                $excel[]=['type'=>'title','info'=>'调拨单列表'];
                //表格数据
                $field=[
                	'frameData|name'=>'所属组织',
                	'time'=>'单据时间',
                	'number'=>'单据编号',
                	'total'=>'单据成本',
                    'cost'=>'单据费用',
                	'peopleData|name'=>'关联人员',
                	'extension|examine'=>'审核状态',
                    'extension|cse'=>'费用状态',
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
                    '总单据成本:'.mathArraySum(array_column($source,'total')),
                    '总单据费用:'.mathArraySum(array_column($source,'cost'))
                ]];
                //导出execl
                buildExcel('调拨单列表',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                foreach ($source as $sourceVo) {
                    //开始构造导出数据
                    $excel=[];//初始化导出数据
                    //标题数据
                    $excel[]=['type'=>'title','info'=>'调拨单'];
                    //节点数据
                    $excel[]=['type'=>'node','info'=>[
                        '单据日期:'.$sourceVo['time'],
                        '单据编号:'.$sourceVo['number']]
                    ];
                    //表格数据
                    $field=[
                    	'goodsData|name'=>'商品名称',
                    	'goodsData|spec'=>'规格型号',
                    	'attr'=>'辅助属性',
                    	'unit'=>'单位',
                    	'warehouseData|name'=>'调出仓库',
                    	'storehouseData|name'=>'调入仓库',
                    	'batch'=>'批次号',
                    	'mfd'=>'生产日期',
                    	'price'=>'成本',
                    	'nums'=>'数量',
                    	'extension|serial'=>'序列号',
                    	'total'=>'总成本',
                    	'data'=>'备注信息'
                    ];
                    //构造表内数据
                    $info=SwapInfo::with(['goodsData','warehouseData','storehouseData'])->where([['pid','=',$sourceVo['id']]])->order(['id'=>'asc'])->append(['extension'])->select()->toArray();
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
                        '单据成本:'.$sourceVo['total'],
                        '单据费用:'.$sourceVo['cost'],
                        '关联人员:'.arraySeek($sourceVo,'peopleData|name'),
                        '物流信息:'.$sourceVo['extension']['logistics'],
                        '备注信息:'.$sourceVo['data']]
                    ];
                    //生成execl
                    $files[]=buildExcel($sourceVo['number'],$excel,false);
                    
                }
                buildZip('调拨单_'.time(),$files);
            }
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}