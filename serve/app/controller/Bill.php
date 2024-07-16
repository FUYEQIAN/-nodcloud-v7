<?php
namespace app\controller;
use app\controller\Acl;
use app\model\{Bill as Bills,BillInfo};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Bill extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['customer','fullEq'],
                ['supplier','fullEq'],
                ['people','fullEq'],
                ['number','fullLike'],
                ['type','fullDec1'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                ['user','fullEq'],
                ['examine','fullDec1'],
                ['data','fullLike']
            ]);//构造SQL
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('bill',$sql);//数据鉴权
            $count = Bills::where($sql)->count();//获取总条数
            $info = Bills::with(['frameData','customerData','supplierData','userData','peopleData','recordData'])->where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();//查询分页数据
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
                empty($class['id'])?$this->validate($class,'app\validate\Bill'):$this->validate($class,'app\validate\Bill.update');
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
                    $this->validate($infoVo,'app\validate\BillInfo');
                } catch (ValidateException $e) {
                    return json(['state'=>'error','info'=>'单据数据第'.($infoKey+1).'条'.$e->getError()]);
                    exit;
                }
            }
            
            //处理数据
            Db::startTrans();
            try {
                //CLASS数据
                if(empty($class['id'])){
                    //创建数据
                    $createInfo=Bills::create($class);
                    $class['id']=$createInfo['id'];//转存主键
                    Db::name('record')->insert(['type'=>'bill','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'新增单据']);
                    pushLog('新增核销单[ '.$class['number'].' ]');//日志
                }else{
                    //更新数据
                    $updateInfo=Bills::update($class);
                    Db::name('record')->insert(['type'=>'bill','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'更新单据']);
                    pushLog('更新核销单[ '.$class['number'].' ]');//日志
                }
                //INFO数据
                BillInfo::where([['pid','=',$class['id']]])->delete();
                foreach ($input['info'] as $infoKey=>$infoVo) {
                    $input['info'][$infoKey]['pid']=$class['id'];
                }
                $model = new BillInfo;
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
            $class=Bills::where([['id','=',$input['parm']]])->find();
            $info=BillInfo::with(['sourceData'])->where([['pid','=',$input['parm']]])->append(['extension'])->order(['id'=>'asc'])->select()->each(function($item){
                $item->sourceData->append(['extension']);
            })->toArray();
            //数据处理
            foreach ($info as $key=>$vo) {
                in_array($vo['mold'],['buy','bre','sell','sre','ice','oce'])&&$info[$key]['sourceData']['total']=$vo['sourceData']['actual'];
            }
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
            //关联验证
            $data=Db::name('bill')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            $search=search($data)->where([['examine','=','1']])->find();
            if(empty($search)){
                Db::startTrans();
                try {
                    Db::name('bill')->where([['id','in',$input['parm']]])->delete();
                    Db::name('bill_info')->where([['pid','in',$input['parm']]])->delete();
                    Db::name('record')->where([['type','=','bill'],['source','in',$input['parm']]])->delete();
                    Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除核销单[ '.implode(' | ',array_column($data,'number')).' ]']);
                    
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
            $classList=Db::name('bill')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            //2 综合处理
            foreach ($input['parm'] as $parmVo) {
                //1 匹配数据
                $class=search($classList)->where([['id','=',$parmVo]])->find();
                $info=BillInfo::with(['sourceData'])->where([['pid','=',$parmVo]])->append(['extension'])->order(['id'=>'asc'])->select()->each(function($item){
                    $item->sourceData->append(['extension']);
                })->toArray();
                //2 CLASS验证
                if($class['time']<=$period){
                    return json(['state'=>'error','info'=>'操作单据[ '.$class['number'].' ]失败,原因:单据日期与结账日期冲突!']);
                    exit;
                }
                //3 INFO验证
                foreach ($info as $infoKey=>$infoVo) {
                    //场景验证
                    if(empty($class['examine'])){
                        //核销金额验证
                        $anwo=$infoVo['sourceData']['extension']['anwo'];
                        if(bccomp(abs($infoVo['money']),abs($anwo))==1){
                            return json(['state'=>'error','info'=>'审核单据[ '.$class['number'].' ]失败,原因:第'.($infoKey+1).'行可核销金额不足!']);
                            exit;
                        }
                    }
                }
                //4 数据处理
                Db::startTrans();
                try {
                    //场景验证
                    if(empty($class['examine'])){
                        //审核
                        
                        //1 数据处理
                        foreach ($info as $infoVo){
                            $mold=$infoVo['mold'];
                            $money=in_array($infoVo['mold'],['bre','sre'])?abs($infoVo['money']):$infoVo['money'];
                            //1.1 添加核销记录
                            Db::name($mold.'_bill')->insert([
                                'pid'=>$infoVo['source'],
                                'type'=>'bill',
                                'source'=>$class['id'],
                                'time'=>$class['time'],
                                'money'=>$money
                            ]);
                            //1.2 读取核销状态
                            $sum=Db::name($mold.'_bill')->where(['pid'=>$infoVo['source']])->sum('money');
                            $total=in_array($infoVo['mold'],['buy','bre','sell','sre','ice','oce'])?$infoVo['sourceData']['actual']:$infoVo['sourceData']['total'];
                            $nucleus=bccomp($sum,$total)==0?2:1;
                            //1.3 更新核销状态
                            Db::name($mold)->where([['id','=',$infoVo['source']]])->update(['nucleus'=>$nucleus]);
                        }
                        //2 更新单据
                        Db::name('bill')->where([['id','=',$class['id']]])->update(['examine'=>1]);
                        //3 单据记录
                        Db::name('record')->insert(['type'=>'bill','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'审核单据']);
                        //4 记录操作
                        pushLog('审核核销单[ '.$class['number'].' ]');//单据日志
                    }else{
                        //反审核
                        
                        //1 数据处理
                        foreach ($info as $infoVo){
                            $mold=$infoVo['mold'];
                            $money=in_array($infoVo['mold'],['bre','sre'])?abs($infoVo['money']):$infoVo['money'];
                            //1.1 删除核销记录
                            Db::name($mold.'_bill')->where([
                                ['pid','=',$infoVo['source']],
                                ['type','=','bill'],
                                ['source','=',$class['id']]
                            ])->delete();
                            //1.2 读取核销状态
                            $sum=Db::name($mold.'_bill')->where(['pid'=>$infoVo['source']])->sum('money');
                            $nucleus=empty($sum)?0:1;
                            //1.3 更新核销状态
                            Db::name($mold)->where([['id','=',$infoVo['source']]])->update(['nucleus'=>$nucleus]);
                        }
                        //2 更新单据
                        Db::name('bill')->where([['id','=',$class['id']]])->update(['examine'=>0]);
                        //3 单据记录
                        Db::name('record')->insert(['type'=>'bill','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'反审核单据']);
                        //11 记录操作
                        pushLog('反审核核销单[ '.$class['number'].' ]');//单据日志
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
                $fileInfo=Filesystem::disk('upload')->putFile('bill', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                $result = ['state' => 'error','info' => $e->getMessage()];
            }
        }
        return json($result);
    }
    //导出
	public function exports(){
		$input=input('get.');
		if(existFull($input,['scene','parm']) && is_array($input['parm'])){
		    pushLog('导出核销单列表');//日志
            $source=Bills::with(['frameData','customerData','supplierData','userData','peopleData'])->where([['id','in',$input['parm']]])->append(['extension'])->order(['id'=>'desc'])->select()->toArray();//查询CLASS数据
            if($input['scene']=='simple'){
                //简易报表
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //标题数据
                $excel[]=['type'=>'title','info'=>'核销单列表'];
                //表格数据
                $field=[
                	'frameData|name'=>'所属组织',
                	'customerData|name'=>'客户',
                	'supplierData|name'=>'供应商',
                	'time'=>'单据时间',
                	'number'=>'单据编号',
                	'extension|type'=>'核销类型',
                	'pmy'=>'核销金额',
                	'peopleData|name'=>'关联人员',
                	'extension|examine'=>'审核状态',
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
                    '总核销金额:'.mathArraySum(array_column($source,'pmy')),
                ]];
                //导出execl
                buildExcel('核销单列表',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                foreach ($source as $sourceVo) {
                    //开始构造导出数据
                    $excel=[];//初始化导出数据
                    //标题数据
                    $excel[]=['type'=>'title','info'=>'核销单'];
                    //节点数据
                    $excel[]=['type'=>'node','info'=>[
                        '客户:'.arraySeek($sourceVo,'customerData|name'),
                        '供应商:'.arraySeek($sourceVo,'supplierData|name'),
                        '单据日期:'.$sourceVo['time'],
                        '单据编号:'.$sourceVo['number']]
                    ];
                    //表格数据
                    $field=[
                    	'extension|bill'=>'核销类型',
                    	'extension|mold'=>'单据类型',
                    	'sourceData|time'=>'单据日期',
                    	'sourceData|number'=>'单据编号',
                    	'sourceData|total'=>'单据金额',
                    	'sourceData|extension|amount'=>'已核销',
                    	'sourceData|extension|anwo'=>'未核销',
                    	'money'=>'核销金额'
                    ];
                    //构造表内数据
                    $info=BillInfo::with(['sourceData'])->where([['pid','=',$sourceVo['id']]])->order(['id'=>'asc'])->append(['extension'])->select()->each(function($item){
                        $item->sourceData->append(['extension']);
                    })->toArray();
                    //数据处理
                    foreach ($info as $key=>$vo) {
                        in_array($vo['mold'],['buy','bre','sell','sre','ice','oce'])&&$info[$key]['sourceData']['total']=$vo['sourceData']['actual'];
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
                        '核销类型:'.arraySeek($sourceVo,'extension|type'),
                        '核销金额:'.$sourceVo['pmy'],
                        '关联人员:'.arraySeek($sourceVo,'peopleData|name'),
                        '备注信息:'.$sourceVo['data']]
                    ];
                    //生成execl
                    $files[]=buildExcel($sourceVo['number'],$excel,false);
                    
                }
                buildZip('核销单_'.time(),$files);
            }
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}