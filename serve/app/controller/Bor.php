<?php
namespace app\controller;
use app\controller\Acl;
use app\model\{Bor as Bors,BorInfo,Goods};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Bor extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['number','fullLike'],
                ['supplier','fullEq'],
                ['people','fullEq'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
                [['startArrival'=>'arrival'],'startTime'],
                [['endArrival'=>'arrival'],'endTime'],
                ['user','fullEq'],
                ['examine','fullDec1'],
                ['state','fullDec1'],
                ['data','fullLike']
            ]);//构造SQL
            //商品信息扩展查询
            if(existFull($input,['goods'])){
                $goods=array_column(Db::name('goods')->where([['name|py','like','%'.$input['goods'].'%']])->select()->toArray(),'id');
                $sql[]=['id','in',array_column(Db::name('bor_info')->where([['goods','in',$goods]])->select()->toArray(),'pid')];
            }
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('bor',$sql);//数据鉴权
            
            //排序
            $column=empty($input['column'])?'id':$input['column'];
            $order=empty($input['order'])?'desc':$input['order'];
            
            $count = Bors::where($sql)->count();//获取总条数
            $info = Bors::with(['frameData','supplierData','peopleData','userData','recordData'])->where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order([$column=>$order])->select()->toArray();//查询分页数据
            //关联单据
            if(!empty($info)){
                $sor=Db::name('sor')->where([['id','in',array_column($info,'source')]])->select()->toArray();
                $buy=Db::name('buy')->where([['source','in',array_column($info,'id')]])->select()->toArray();
                foreach ($info as $infoKey=>$infoVo) {
                    //销售订单
                    $sorData=array_map(function($item){
                        return ['type'=>'销售订单','time'=>date('Y-m-d',$item['time']),'number'=>$item['number'],'sort'=>$item['time'],'sort'=>$item['time'],'types'=>'sor','id'=>$item['id']];
                    },search($sor)->where([['id','=',$infoVo['source']]])->select());
                    //采购单
                    $buyData=array_map(function($item){
                        return ['type'=>'采购单','time'=>date('Y-m-d',$item['time']),'number'=>$item['number'],'sort'=>$item['time'],'sort'=>$item['time'],'types'=>'buy','id'=>$item['id']];
                    },search($buy)->where([['source','=',$infoVo['id']]])->select());
                    //合并排序
                    $merge=array_merge($sorData,$buyData);
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
                $class['examine']=0;
                empty($class['id'])?$this->validate($class,'app\validate\Bor'):$this->validate($class,'app\validate\Bor.update');
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
                    $this->validate($infoVo,'app\validate\BorInfo');
                } catch (ValidateException $e) {
                    return json(['state'=>'error','info'=>'商品信息第'.($infoKey+1).'条'.$e->getError()]);
                    exit;
                }
            }
            
            //处理数据
            Db::startTrans();
            try {
                //CLASS数据
                if(empty($class['id'])){
                    //创建数据
                    $createInfo=Bors::create($class);
                    $class['id']=$createInfo['id'];//转存主键
                    Db::name('record')->insert(['type'=>'bor','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'新增单据']);
                    pushLog('新增采购订单[ '.$class['number'].' ]');//日志
                }else{
                    //更新数据
                    $updateInfo=Bors::update($class);
                    Db::name('record')->insert(['type'=>'bor','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'更新单据']);
                    pushLog('更新采购订单[ '.$class['number'].' ]');//日志
                }
                
                //INFO数据
                BorInfo::where([['pid','=',$class['id']]])->delete();
                foreach ($input['info'] as $infoKey=>$infoVo) {
                    $input['info'][$infoKey]['pid']=$class['id'];
                    $input['info'][$infoKey]['handle']=0;//初始|入库数量
                }
                $model = new BorInfo;
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
            $class=Bors::where([['id','=',$input['parm']]])->find();
            $info=BorInfo::with(['goodsData','warehouseData'])->where([['pid','=',$input['parm']]])->order(['id'=>'asc'])->select();
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
            $exist=moreTableFind([['table'=>'buy','where'=>[['source','in',$input['parm']]]]]);
            if($exist){
                $result=['state'=>'error','info'=>'存在数据关联,删除失败!'];
            }else{
                $data=Db::name('bor')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
                $search=search($data)->where([['examine','=','1']])->find();
                if(empty($search)){
                    Db::startTrans();
                    try {
                        Db::name('bor')->where([['id','in',$input['parm']]])->delete();
                        Db::name('bor_info')->where([['pid','in',$input['parm']]])->delete();
                        Db::name('record')->where([['type','=','bor'],['source','in',$input['parm']]])->delete();
                        Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除采购订单[ '.implode(' | ',array_column($data,'number')).' ]']);
                        
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
    //审核|反审核
    public function examine(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            //1 基础数据
            $period=getPeriod();
            $classList=Db::name('bor')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            //2 综合处理
            foreach ($classList as $class) {
                //1 CLASS验证
                if($class['time']<=$period){
                    return json(['state'=>'error','info'=>'操作单据[ '.$class['number'].' ]失败,原因:单据日期与结账日期冲突!']);
                    exit;
                }
                if(!empty($class['examine'])){
                    //采购单
                    $buy=Db::name('buy')->where([['source','=',$class['id']]])->find();
                    if(!empty($buy)){
                        return json(['state'=>'error','info'=>'反审核单据[ '.$class['number'].' ]失败,原因:该订单存在关联采购单!']);
                        exit;
                    }
                }
                //2 数据处理
                Db::startTrans();
                try {
                    //场景判断
                    if(empty($class['examine'])){
                        //审核
                        Db::name('bor')->where([['id','=',$class['id']]])->update(['examine'=>1]);
                        Db::name('record')->insert(['type'=>'bor','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'审核单据']);
                        pushLog('审核采购订单[ '.$class['number'].' ]');//日志
                    }else{
                        //反审核
                        Db::name('bor')->where([['id','=',$class['id']]])->update(['examine'=>0]);
                        Db::name('record')->insert(['type'=>'bor','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'反审核单据']);
                        pushLog('反审核采购订单[ '.$class['number'].' ]');//日志
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
    //开启|关闭
    public function update(){
        $input=input('post.');
        if(existFull($input,['id'])){
            $period=getPeriod();
            $class=Db::name('bor')->where([['id','=',$input['id']]])->find();
            if($class['time']<=$period){
                return json(['state'=>'error','info'=>'操作单据失败,原因:单据日期与结账日期冲突!']);
                exit;
            }else{
                Db::startTrans();
                try {
                    if($class['state']==3){
                        //开启
                        Db::name('bor')->where([['id','=',$class['id']]])->update(['state'=>1]);
                        Db::name('record')->insert(['type'=>'bor','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'开启单据']);
                        pushLog('开启采购订单[ '.$class['number'].' ]');//日志
                    }else{
                        //关闭
                        Db::name('bor')->where([['id','=',$class['id']]])->update(['state'=>3]);
                        Db::name('record')->insert(['type'=>'bor','source'=>$class['id'],'time'=>time(),'user'=>getUserID(),'info'=>'关闭单据']);
                        pushLog('关闭采购订单[ '.$class['number'].' ]');//日志
                    }
                    
                    Db::commit();
                	$result=['state'=>'success'];
                } catch (\Exception $e) {
                	Db::rollback();
                	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
                }
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return empty($parm)?json($result):$result;
    }
    //生成采购单
    public function buildBuy(){
        $input=input('post.');
        if(existFull($input,['id'])){
            //源数据
            $source=[
                'class'=>Bors::where([['id','=',$input['id']]])->find(),
                'info'=>BorInfo::with(['goodsData','warehouseData'])->where([['pid','=',$input['id']]])->order(['id'=>'asc'])->select()->toArray()
            ];
            //状态验证
            if($source['class']['state']!=2){
                //CLASS数据
                $class=[
                    'source'=>$source['class']['id'],
                    'supplier'=>$source['class']['supplier'],
                    'total'=>0
                ];
                //INFO数据
                $info=[];
                $fun=getSys('fun');
                foreach ($source['info'] as $infoVo) {
                    //判断入库状态
                    if(bccomp($infoVo['nums'],$infoVo['handle'])==1){
                        $infoVo['source']=$infoVo['id'];
                        $infoVo['serial']=[];
                        $infoVo['batch']='';
                        $infoVo['mfd']='';
                        $infoVo['retreat']=0;
                        //重算价格
                        $infoVo['nums']=math()->chain($infoVo['nums'])->sub($infoVo['handle'])->done();
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
            }else{
                $result=['state'=>'warning','info'=>'操作失败,订单状态为已入库!'];
            }
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
                $fileInfo=Filesystem::disk('upload')->putFile('bor', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                $result = ['state'=>'error','info'=>$e->getMessage()];
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
                $supplier=Db::name('supplier')->where([['name','=',$data[3]['A']]])->find();
                if(empty($supplier)){
                    throw new ValidateException('供应商[ '.$data[3]['A'].' ]未匹配!');
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
                    'actual'=>$data[3]['E'],
                    'people'=>$people['id'],
                    'arrival'=>$data[3]['G'],
                    'logistics'=>["key"=>"auto","name"=>"自动识别","number"=>$data[3]['H']],
                    'file'=>[],
                    'data'=>$data[3]['I'],
                    'more'=>[],
                    'examine'=>0,
                    'state'=>0,
                    'user'=>getUserID()
                ];
                $this->validate($class,'app\validate\Bor');//数据合法性验证
                //初始化INFO
                $info=[];
                $goods=Goods::with(['attr'])->where([['name','in',array_column($data,'J')]])->select()->toArray();
                $warehouse=Db::name('warehouse')->where([['name','in',array_column($data,'M')]])->select()->toArray();
                foreach ($data as $dataKey=>$dataVo) {
					$record=[
						'goods'=>$dataVo['J'],
						'attr'=>$dataVo['K'],
						'unit'=>$dataVo['L'],
						'warehouse'=>$dataVo['M'],
						'price'=>$dataVo['N'],
						'nums'=>$dataVo['O'],
						'discount'=>$dataVo['P'],
						'dsc'=>0,
						'total'=>0,
						'tax'=>$dataVo['S'],
						'tat'=>0,
						'tpt'=>0,
						'data'=>$dataVo['V'],
						'handle'=>0,
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
					//单价匹配
					if(!preg_match("/^\d+(\.\d{0,".$fun['digit']['money']."})?$/",$record['price'])){
					    throw new ValidateException('模板文件第'.$dataKey.'行单价不正确!');
					}
					//数量匹配
					if(!preg_match("/^\d+(\.\d{0,".$fun['digit']['nums']."})?$/",$record['nums'])){
					    throw new ValidateException('模板文件第'.$dataKey.'行数量不正确!');
					}
					try{
                        $this->validate($record,'app\validate\BorInfo');//数据合法性验证
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
                //CLASS数据验证
                if(bccomp($class['total'],$class['actual'])==-1){
                    throw new ValidateException('实际金额不可大于单据金额[ '.$class['total'].' ]!');
                }else{
                    Db::startTrans();
                    try {
                        //新增CLASS
                        $classData=Bors::create($class);
                        //新增INFO
                        foreach ($info as $infoKey=>$infoVo) {
                            $info[$infoKey]['pid']=$classData['id'];
                        }
                        $model = new BorInfo;
                        $model->saveAll($info);
                        Db::name('record')->insert(['type'=>'bor','source'=>$classData['id'],'time'=>time(),'user'=>getUserID(),'info'=>'导入单据']);
                        pushLog('导入采购订单[ '.$classData['number'].' ]');//日志
                        
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
		    pushLog('导出采购订单列表');//日志
            $source=Bors::with(['frameData','supplierData','peopleData','userData'])->where([['id','in',$input['parm']]])->append(['extension'])->order(['id'=>'desc'])->select()->toArray();//查询CLASS数据
            if($input['scene']=='simple'){
                //简易报表
                //开始构造导出数据
                $excel=[];//初始化导出数据
                //标题数据
                $excel[]=['type'=>'title','info'=>'采购订单列表'];
                //表格数据
                $field=[
                	'frameData|name'=>'所属组织',
                	'supplierData|name'=>'供应商',
                	'time'=>'单据时间',
                	'number'=>'单据编号',
                	'total'=>'单据金额',
                	'actual'=>'实际金额',
                	'arrival'=>'到货日期',
                	'extension|examine'=>'审核状态',
                	'extension|state'=>'入库状态',
                	'peopleData|name'=>'关联人员',
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
                $excel[]=['type'=>'node','info'=>['总数:'.count($source),'总单据金额:'.mathArraySum(array_column($source,'total')),'总实际金额:'.mathArraySum(array_column($source,'actual'))]];
                //导出execl
                buildExcel('采购订单列表',$excel);
            }else{
                //详细报表
                $files=[];//初始化文件列表
                foreach ($source as $sourceVo) {
                    //开始构造导出数据
                    $excel=[];//初始化导出数据
                    //标题数据
                    $excel[]=['type'=>'title','info'=>'采购订单'];
                    //节点数据
                    $excel[]=['type'=>'node','info'=>[
                        '供应商:'.$sourceVo['supplierData']['name'],
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
                    	'price'=>'单价',
                    	'nums'=>'数量',
                    	'handle'=>'入库数量',
                    	'discount'=>'折扣率',
                    	'dsc'=>'折扣额',
                    	'total'=>'金额',
                    	'tax'=>'税率',
                    	'tat'=>'税额',
                    	'tpt'=>'价税合计',
                    	'data'=>'备注信息'
                    ];
                    //构造表内数据
                    $info=BorInfo::with(['goodsData','warehouseData'])->where([['pid','=',$sourceVo['id']]])->order(['id'=>'asc'])->select()->toArray();
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
                        '实际金额:'.$sourceVo['actual'],
                        '关联人员:'.arraySeek($sourceVo,'peopleData|name'),
                        '到货日期:'.$sourceVo['arrival'],
                        '物流信息:'.$sourceVo['extension']['logistics'],
                        '备注信息:'.$sourceVo['data']]
                    ];
                    //生成execl
                    $files[]=buildExcel($sourceVo['number'],$excel,false);
                }
                buildZip('采购订单_'.time(),$files);
            }
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}
