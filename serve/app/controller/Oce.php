<?php
namespace app\controller;
use app\controller\Acl;
use app\model\{Oce as Oces,OceInfo,Account};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Oce extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['supplier','fullEq'],
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['account','fullEq'],
                ['people','fullEq'],
                ['user','fullEq'],
                ['examine','fullDec1'],
                ['nucleus','fullDec1'],
                ['data','fullLike']
            ]);//构造SQL
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('oce',$sql);//数据鉴权
            $count = Oces::where($sql)->count();//获取总条数
            $info = Oces::with(['frameData','supplierData','accountData','peopleData','userData','billData','recordData'])->where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();//查询分页数据
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
                $class['examine']=0;
                $class['nucleus']=0;
                empty($class['id'])?$this->validate($class,'app\validate\Oce'):$this->validate($class,'app\validate\Oce.update');
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
                    $this->validate($infoVo,'app\validate\OceInfo');
                } catch (ValidateException $e) {
                    return json(['state'=>'error','info'=>'数据表格第'.($infoKey+1).'条'.$e->getError()]);
                    exit;
                }
            }
            
            //处理数据
            Db::startTrans();
            try {
                //CLASS数据
                if(empty($class['id'])){
                    //创建数据
                    $createInfo=Oces::create($class);
                    $class['id']=$createInfo['id'];//转存主键
                    Db::name('record')->insert(['type'=>'oce','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'新增单据']);
                    pushLog('新增其它支出单[ '.$class['number'].' ]');//日志
                }else{
                    //更新数据
                    $updateInfo=Oces::update($class);
                    Db::name('record')->insert(['type'=>'oce','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'更新单据']);
                    pushLog('更新其它支出单[ '.$class['number'].' ]');//日志
                }
                
                //INFO数据
                OceInfo::where([['pid','=',$class['id']]])->delete();
                foreach ($input['info'] as $infoKey=>$infoVo) {
                    $input['info'][$infoKey]['pid']=$class['id'];
                }
                $model = new OceInfo;
                $model->saveAll($input['info']);
                
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
            $class=Oces::where([['id','=',$input['parm']]])->find();
            $info=OceInfo::with(['ietData'])->where([['pid','=',$input['parm']]])->order(['id'=>'asc'])->select();
            $result=['state'=>'success','info'=>[
                'class'=>$class,
                'info'=>$info,
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
            $data=Db::name('oce')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            $search=search($data)->where([['examine','=','1']])->find();
            if(empty($search)){
                Db::startTrans();
                try {
                    Db::name('oce')->where([['id','in',$input['parm']]])->delete();
                    Db::name('oce_info')->where([['pid','in',$input['parm']]])->delete();
                    Db::name('record')->where([['type','=','oce'],['source','in',$input['parm']]])->delete();
                    Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除其它支出单[ '.implode(' | ',array_column($data,'number')).' ]']);
                    
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
            $period=getPeriod();
            $classList=Db::name('oce')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            $infoList=Db::name('oce_info')->where([['pid','in',$input['parm']]])->order(['id'=>'asc'])->select()->toArray();
            //2 综合处理
            foreach ($input['parm'] as $parmVo) {
                //1 匹配数据
                $class=search($classList)->where([['id','=',$parmVo]])->find();
                $info=search($infoList)->where([['pid','=',$parmVo]])->select();
                //2 CLASS验证
                if($class['time']<=$period){
                    return json(['state'=>'error','info'=>'操作单据[ '.$class['number'].' ]失败,原因:单据日期与结账日期冲突!']);
                    exit;
                }
                //3 综合匹配
                $sourceSum=mathArraySum(array_column($info,'source'));
                if(!empty($sourceSum)){
                    //2.1 匹配费用
                    $costList=Db::name('cost')->where([['id','in',array_column($info,'source')]])->select()->toArray();
                    //2.1 构造数据
                    $costGather=array_map(function($row){
                        return [['type','=',$row['type']],['class','=',$row['class']]];
                    },$costList);
                }
                //4 数据验证
                if(empty($class['examine'])){
                    if(!empty($sourceSum)){
                        // 关联验证
                        foreach ($info as $infoKey=>$infoVo) {
                            $cost=search($costList)->where([['id','=',$infoVo['source']]])->find();
                            if(!empty($cost)){
                                if(bccomp(math()->chain($cost['settle'])->add($infoVo['money'])->done(),$cost['money'])==1){
                                    return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行结算金额超出可结算费用!']);
                                    exit;
                                }
                            }
                        }
                    }
                }else{
                    //核销单
                    $bill=Db::name('bill_info')->where([['mold','=','oce'],['source','=',$class['id']]])->find();
                    if(!empty($bill)){
                        return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:该单据存在关联核销单!']);
                        exit;
                    }
                }
                //5 数据处理
                Db::startTrans();
                try {
                    //场景验证
                    if(empty($class['examine'])){
                        //审核
                        //1 关联单据
                        $store=['cost'=>[],'costInfo'=>[]];
                        foreach ($info as $infoKey=>$infoVo){
                            if(!empty($infoVo['source'])){
                                //关联费用
                                $store['cost'][]=['id'=>$infoVo['source'],'settle'=>$infoVo['money']];
                                //关联费用记录
                                $store['costInfo'][]=['pid'=>$infoVo['source'],'oce'=>$class['id'],'time'=>$class['time'],'money'=>$infoVo['money']];
                            }
                        }
                        //1.1 处理数据
                        if(!empty($store['cost'])){
                            //1 更新详情
                            Db::name('cost')->duplicate(['settle'=>Db::raw('settle + VALUES(`settle`)')])->insertAll($store['cost']);
                            //2 更新状态
                            Db::name('cost')->where([['id','in',array_column($store['cost'],'id')]])->update(['state'=>Db::raw('CASE WHEN settle = money THEN 2 ELSE 1 END')]);
                            //3 插入记录
                            Db::name('cost_info')->insertAll($store['costInfo']);
                            //4 更新单据
                            $costGroup=Db::name('cost')->whereOr($costGather)->fieldRaw('type,class,group_concat(state) as states')->group(['type','class'])->select()->toArray();
                            //4.1 组装数据
                            $bill=[];
                            foreach ($costGroup as $costGroupVo) {
                                $states=explode(',',$costGroupVo['states']);
                                $bill[$costGroupVo['type']][]=['id'=>$costGroupVo['class'],'cse'=>mathArraySum($states)==count($states)*2?2:1];
                            }
                            //4.2 更新状态
                            foreach($bill as $billKey => $billVo){
                                Db::name($billKey)->duplicate(['cse'=>Db::raw('VALUES(`cse`)')])->insertAll($billVo);
                            }
                        }
                        //2 资金|核销
                        if(!empty($class['money'])){
                            //1 更新资金账户
                            Db::name('account')->where([['id','=',$class['account']]])->dec('balance',$class['money'])->update();
                            //2 创建资金详情
                            Db::name('account_info')->insert([
                                'pid'=>$class['account'],
                                'type'=>'oce',
                                'class'=>$class['id'],
                                'time'=>$class['time'],
                                'direction'=>0,
                                'money'=>$class['money']
                            ]);
                            //3 创建核销记录
                            Db::name('oce_bill')->insert([
                                'pid'=>$class['id'],
                                'type'=>'oce',
                                'source'=>$class['id'],
                                'time'=>$class['time'],
                                'money'=>$class['money']
                            ]);
                        }
                        //3 供应商|应付款余额
                        if(!empty($class['supplier'])){
                            $balance=math()->chain($class['actual'])->sub($class['money'])->done();
                            if(!empty($balance)){
                                Db::name('supplier')->where([['id','=',$class['supplier']]])->inc('balance',$balance)->update();
                            }
                        }
                        //4 更新单据
                        $nucleus=$class['money']==$class['actual']?2:($class['money']==0?0:1);
                        Db::name('oce')->where([['id','=',$class['id']]])->update(['examine'=>1,'nucleus'=>$nucleus]);
                        //5 单据记录
                        Db::name('record')->insert(['type'=>'oce','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'审核单据']);
                        //6 记录操作
                        pushLog('审核其它支出单[ '.$class['number'].' ]');//单据日志
                    }else{
                        //反审核
                        //1 关联单据
                        $store=['cost'=>[],'costInfo'=>[]];
                        foreach ($info as $infoKey=>$infoVo){
                            if(!empty($infoVo['source'])){
                                //关联费用
                                $store['cost'][]=['id'=>$infoVo['source'],'settle'=>$infoVo['money']];
                                //关联费用记录
                                $store['costInfo'][]=[['pid','=',$infoVo['source']],['oce','=',$class['id']]];
                            }
                        }
                        //1.1 处理数据
                        if(!empty($store['cost'])){
                            //1 更新详情
                            Db::name('cost')->duplicate(['settle'=>Db::raw('settle - VALUES(`settle`)')])->insertAll($store['cost']);
                            //2 更新状态
                            Db::name('cost')->where([['id','in',array_column($store['cost'],'id')]])->update(['state'=>Db::raw('CASE WHEN settle = 0 THEN 0 ELSE 1 END')]);
                            //3 删除记录
                            Db::name('cost_info')->whereOr($store['costInfo'])->delete();
                            //4 更新单据
                            $costGroup=Db::name('cost')->whereOr($costGather)->fieldRaw('type,class,group_concat(state) as states')->group(['type','class'])->select()->toArray();
                            //4.1 组装数据
                            $bill=[];
                            foreach ($costGroup as $costGroupVo) {
                                $states=explode(',',$costGroupVo['states']);
                                $bill[$costGroupVo['type']][]=['id'=>$costGroupVo['class'],'cse'=>empty(mathArraySum($states))?0:1];
                            }
                            //4.2 更新状态
                            foreach($bill as $billKey => $billVo){
                                Db::name($billKey)->duplicate(['cse'=>Db::raw('VALUES(`cse`)')])->insertAll($billVo);
                            }
                        }
                        //2 资金|核销
                        if(!empty($class['money'])){
                            //1 更新资金账户
                            Db::name('account')->where([['id','=',$class['account']]])->inc('balance',$class['money'])->update();
                            //2 删除资金详情
                            Db::name('account_info')->where([['type','=','oce'],['class','=',$class['id']]])->delete();
                            //3 删除核销记录
                            Db::name('oce_bill')->where([['pid','=',$class['id']]])->delete();
                        }
                        //3 供应商|应付款余额
                        if(!empty($class['supplier'])){
                            $balance=math()->chain($class['actual'])->sub($class['money'])->done();
                            if(!empty($balance)){
                                Db::name('supplier')->where([['id','=',$class['supplier']]])->dec('balance',$balance)->update();
                            }
                        }
                        //4 更新单据
                        Db::name('oce')->where([['id','=',$class['id']]])->update(['examine'=>0,'nucleus'=>0]);
                        //5 单据记录
                        Db::name('record')->insert(['type'=>'oce','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'反审核单据']);
                        //6 记录操作
                        pushLog('反审核其它支出单[ '.$class['number'].' ]');//单据日志
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
                $fileInfo=Filesystem::disk('upload')->putFile('oce', $file, 'uniqid');
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
                //供应商匹配
                if(empty($data[3]['A'])){
                    $supplier=['id'=>0];
                }else{
                    $supplier=Db::name('supplier')->where([['name','=',$data[3]['A']]])->find();
                    if(empty($supplier)){
                        throw new ValidateException('供应商[ '.$data[3]['A'].' ]未匹配!');
                    }
                }
                //结算账户匹配
                $account=Db::name('account')->where([['name','=',$data[3]['E']]])->find();
                if(empty($account)){
                    throw new ValidateException('资金账户[ '.$data[3]['E'].' ]未匹配!');
                }
                //关联人员匹配
                if(empty($data[3]['F'])){
                    $people=['id'=>0];
                }else{
                    $people=Db::name('people')->where([['name','=',$data[3]['F']]])->find();
                    if(empty($people)){
                        throw new ValidateException('关联人员[ '.$data[3]['F'].' ]未匹配!');
                    }
                }
                $class=[
                    'frame'=>userInfo(getUserID(),'frame'),
                    'supplier'=>$supplier['id'],
                    'time'=>$data[3]['B'],
                    'number'=>$data[3]['C'],
                    'total'=>0,
                    'account'=>$account['id'],
                    'people'=>$people['id'],
                    'file'=>[],
                    'data'=>$data[3]['G'],
                    'more'=>[],
                    'examine'=>0,
                    'nucleus'=>0,
                    'user'=>getUserID()
                ];
                $this->validate($class,'app\validate\Oce');//数据合法性验证
                //初始化INFO
                $info=[];
                $iet=Db::name('iet')->where([['name','in',array_column($data,'H')],['type','=',1]])->select()->toArray();
                foreach ($data as $dataKey=>$dataVo) {
					$record=[
						'iet'=>$dataVo['H'],
						'money'=>$dataVo['I'],
						'data'=>$dataVo['J']
					];
					//支出类别匹配
					$ietFind=search($iet)->where([['name','=',$record['iet']]])->find();
                    if(empty($ietFind)){
                        throw new ValidateException('模板文件第'.$dataKey.'行支出类别[ '.$record['iet'].' ]未匹配!');
                    }else{
                        $record['iet']=$ietFind['id'];
                    }
					//结算金额匹配
					if(!preg_match("/^(\-)?\d+(\.\d{0,".$fun['digit']['money']."})?$/",$record['money'])){
					    throw new ValidateException('模板文件第'.$dataKey.'行结算金额不正确!');
					}
					try{
                        $this->validate($record,'app\validate\OceInfo');//数据合法性验证
                        //转存数据
                        $class['total']=math()->chain($class['total'])->add($record['money'])->done();//累加单据金额
                        $info[]=$record;
					} catch (ValidateException $e) {
                        return json(['state'=>'error','info'=>'模板文件第'.$dataKey.'行'.$e->getMessage()]);//返回错误信息
                        exit;
                    }
                }
                Db::startTrans();
                try {
                    //新增CLASS
                    $classData=Oces::create($class);
                    //新增INFO
                    foreach ($info as $infoKey=>$infoVo) {
                        $info[$infoKey]['pid']=$classData['id'];
                    }
                    $model = new OceInfo;
                    $model->saveAll($info);
                    Db::name('record')->insert(['type'=>'oce','source'=>$classData['id'],'time'=>time(),'user'=>getUserID(),'info'=>'导入单据']);
                    pushLog('导入其它支出单[ '.$classData['number'].' ]');//日志
                    
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
		    pushLog('导出其它支出单列表');//日志
            $source=Oces::with(['frameData','supplierData','accountData','peopleData','userData'])->where([['id','in',$input['parm']]])->append(['extension'])->order(['id'=>'desc'])->select()->toArray();//查询CLASS数据
            if($input['scene']=='simple'){
                //简易报表
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //标题数据
                $excel[]=['type'=>'title','info'=>'其它支出单列表'];
                //表格数据
                $field=[
                	'frameData|name'=>'所属组织',
                	'supplierData|name'=>'供应商',
                	'time'=>'单据时间',
                	'number'=>'单据编号',
                	'total'=>'单据金额',
                	'actual'=>'实际金额',
                    'money'=>'单据收款',
                    'extension|amount'=>'核销金额',
                	'accountData|name'=>'结算账户',
                	'peopleData|name'=>'关联人员',
                	'extension|examine'=>'审核状态',
                	'extension|nucleus'=>'核销状态',
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
                    '总单据付款:'.mathArraySum(array_column($source,'money')),
                    '总核销金额:'.mathArraySum(arrayColumns($source,['extension','amount']))
                ]];
                //导出execl
                buildExcel('其它支出单列表',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                foreach ($source as $sourceVo) {
                    //开始构造导出数据
                    $excel=[];//初始化导出数据
                    //标题数据
                    $excel[]=['type'=>'title','info'=>'其它支出单'];
                    //节点数据
                    $excel[]=['type'=>'node','info'=>[
                        '供应商:'.$sourceVo['supplierData']['name'],
                        '单据日期:'.$sourceVo['time'],
                        '单据编号:'.$sourceVo['number']]
                    ];
                    //表格数据
                    $field=[
                    	'ietData|name'=>'支出类别',
                    	'money'=>'结算金额',
                    	'data'=>'备注信息'
                    ];
                    //构造表内数据
                    $info=OceInfo::with(['ietData'])->where([['pid','=',$sourceVo['id']]])->order(['id'=>'asc'])->select()->toArray();
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
                        '结算账户:'.arraySeek($sourceVo,'accountData|name'),
                        '核销金额:'.$sourceVo['extension']['amount'],
                        '关联人员:'.arraySeek($sourceVo,'peopleData|name'),
                        '备注信息:'.$sourceVo['data']]
                    ];
                    //生成execl
                    $files[]=buildExcel($sourceVo['number'],$excel,false);
                }
                buildZip('其它支出单_'.time(),$files);
            }
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}